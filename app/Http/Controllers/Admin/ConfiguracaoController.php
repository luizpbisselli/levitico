<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaLog;
use App\Models\Configuracao;
use App\Support\DatabaseConfigurator;
use App\Services\ImapMailboxService;
use App\Services\IngestaoRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Tela "Configurações" da área administrativa.
 *
 * Permite, sem SSH e sem editar .env:
 *  - apontar o sistema para um MySQL criado no cPanel (com botão Testar);
 *  - configurar a caixa de e-mail IMAP que recebe os XMLs de NF-e/CT-e
 *    (com botão Testar conexão);
 *  - disparar a ingestão manualmente ("Processar agora").
 */
class ConfiguracaoController extends Controller
{
    public function index()
    {
        return view('admin.configuracoes', [
            // Apenas campos NÃO sigilosos vão para a tela; senhas ficam no servidor
            // e são representadas por emailConfigurado/dbPasswordConfigurada.
            'config'          => $this->valoresExibiveis(),
            'dbPasswordConfigurada' => ! empty(Configuracao::get('db.password')),
            'driverAtual'     => Configuracao::get('db.driver', env('DB_CONNECTION', 'sqlite')),
            'imapDisponivel'  => ImapMailboxService::usandoExtImap(),
            'emailConfigurado' => ImapMailboxService::estaConfigurado(),
            'statusUltimos'   => \App\Models\EmailIngestao::latest()->limit(10)->get(['id', 'message_id', 'assunto', 'remetente', 'status', 'lido_em']),
        ]);
    }

    /**
     * Whitelist de chaves exibíveis na UI. Qualquer chave nova com credencial
     * fica automaticamente fora da tela, pois só aparece se listada aqui.
     */
    private function valoresExibiveis(): array
    {
        $exibiveis = ['db.host', 'db.port', 'db.database', 'db.username', 'db.prefixo_tabela',
                      'email.host', 'email.port', 'email.user', 'email.box', 'email.pasta_processados'];

        $todos = Configuracao::allValues();

        return array_intersect_key($todos, array_flip($exibiveis));
    }

    public function salvarBanco(Request $request)
    {
        $dados = $request->validate([
            'db_driver'    => ['required', 'in:sqlite,mysql'],
            'db_host'      => ['nullable', 'string', 'max:190'],
            'db_port'      => ['nullable', 'integer', 'min:1', 'max:65535'],
            'db_database'  => ['nullable', 'string', 'max:190'],
            'db_username'  => ['nullable', 'string', 'max:190'],
            'db_password'  => ['nullable', 'string', 'max:190'],
            'db_prefixo'   => ['nullable', 'string', 'max:50'],
        ]);

        Configuracao::put('db.driver', $dados['db_driver']);

        if ($dados['db_driver'] === 'mysql') {
            Configuracao::put('db.host', $dados['db_host'] ?? null);
            Configuracao::put('db.port', (string) ($dados['db_port'] ?? 3306));
            Configuracao::put('db.database', $dados['db_database'] ?? null);
            Configuracao::put('db.username', $dados['db_username'] ?? null);
            if (($dados['db_password'] ?? '') !== '') {
                Configuracao::put('db.password', $dados['db_password']);
            }
            Configuracao::put('db.prefixo_tabela', $dados['db_prefixo'] ?? '');

            // Valida antes de travar o app numa conexão quebrada
            $teste = DatabaseConfigurator::testarMysql([
                'host'      => Configuracao::get('db.host'),
                'port'      => Configuracao::get('db.port', '3306'),
                'database'  => Configuracao::get('db.database'),
                'username'  => Configuracao::get('db.username'),
                'password'  => Configuracao::get('db.password', ''),
            ]);

            if (! $teste['ok']) {
                return back()->with('erro_banco', 'Não foi possível conectar ao MySQL: ' . $teste['erro']);
            }
        } else {
            Configuracao::limparGrupo('db.');
            Configuracao::put('db.driver', 'sqlite');
        }

        AuditoriaLog::create([
            'user_id' => $request->user()?->id,
            'acao'    => 'config.banco',
            'modelo'  => 'configuracoes',
            'payload' => ['driver' => $dados['db_driver'], 'host' => $dados['db_host'] ?? null],
            'ip'      => $request->ip(),
        ]);

        return back()->with('sucesso_banco', 'Configurações de banco salvas. As tabelas serão criadas/atualizadas automaticamente no próximo acesso.');
    }

    public function salvarEmail(Request $request)
    {
        $dados = $request->validate([
            'email_host'            => ['nullable', 'string', 'max:190'],
            'email_port'            => ['nullable', 'integer', 'min:1', 'max:65535'],
            'email_user'            => ['nullable', 'email', 'max:190'],
            'email_password'        => ['nullable', 'string', 'max:190'],
            'email_box'             => ['nullable', 'string', 'max:100'],
            'email_pasta_processados' => ['nullable', 'string', 'max:100'],
        ]);

        Configuracao::put('email.host', $dados['email_host'] ?? null);
        Configuracao::put('email.port', (string) ($dados['email_port'] ?? 993));
        Configuracao::put('email.user', $dados['email_user'] ?? null);
        if (($dados['email_password'] ?? '') !== '') {
            Configuracao::put('email.password', $dados['email_password']);
        }
        Configuracao::put('email.box', $dados['email_box'] ?? 'INBOX');
        Configuracao::put('email.pasta_processados', $dados['email_pasta_processados'] ?? 'Processados');

        AuditoriaLog::create([
            'user_id' => $request->user()?->id,
            'acao'    => 'config.email',
            'modelo'  => 'configuracoes',
            'payload' => ['host' => $dados['email_host'] ?? null, 'user' => $dados['email_user'] ?? null],
            'ip'      => $request->ip(),
        ]);

        return back()->with('sucesso_email', 'Configurações de e-mail salvas.');
    }

    public function testarBanco(Request $request)
    {
        $dados = $request->validate([
            'db_host'     => ['required', 'string'],
            'db_port'     => ['nullable', 'integer'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        $senha = $dados['db_password'] ?? '';
        if ($senha === '') {
            $senha = (string) Configuracao::get('db.password', '');
        }

        $teste = DatabaseConfigurator::testarMysql([
            'host' => $dados['db_host'],
            'port' => $dados['db_port'] ?? 3306,
            'database' => $dados['db_database'],
            'username' => $dados['db_username'],
            'password' => $senha,
        ]);

        return back()->with($teste['ok'] ? 'sucesso_banco' : 'erro_banco',
            $teste['ok'] ? ('MySQL OK — ' . $teste['info']) : ('Falha no MySQL: ' . $teste['erro']));
    }

    public function testarEmail()
    {
        $resultado = (new ImapMailboxService())->testarConexao();

        return back()->with($resultado['ok'] ? 'sucesso_email' : 'erro_email',
            $resultado['ok'] ? $resultado['info'] : ('Falha na conexão IMAP: ' . $resultado['erro']));
    }

    public function processarAgora(IngestaoRunner $runner)
    {
        $r = $runner->executar(25);

        $msg = sprintf('%d mensagem(ns) lida(s), %d processada(s)', $r['lidas'], $r['processados']);
        if ($r['pendencias']) {
            $msg .= sprintf(', %d pendência(s) detectada(s)', $r['pendencias']);
        }

        if ($r['erros']) {
            return back()->with('erro_email', $msg . '. Erros: ' . implode(' | ', array_slice($r['erros'], 0, 3)));
        }

        return back()->with('sucesso_email', $msg . '.');
    }
}
