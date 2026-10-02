<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Configurações persistidas no banco (tabela `configuracoes`).
 *
 * Prioridade de leitura: valor salvo aqui > valor do .env > padrão.
 * Isso permite configurar MySQL e a caixa de e-mail de ingestão pela
 * interface web, sem acesso SSH/terminal à hospedagem.
 */
class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = ['chave', 'valor', 'sigilosa'];

    protected $casts = ['sigilosa' => 'boolean'];

    /** Cache em memória por requisição. */
    protected static ?array $cache = null;

    protected static array $padroes = [
        'db.driver'   => 'mysql',
        'db.charset'  => 'utf8mb4',
        'db.collation' => 'utf8mb4_unicode_ci',
        'db.prefixo_tabela' => '',
        'email.port'  => '993',
        'email.box'   => 'INBOX',
        'email.pasta_processados' => 'Processados',
    ];

    public static function get(string $chave, ?string $default = null): ?string
    {
        self::carregarCache();

        // Chaves db.* têm prioridade do .env: garante que um banco configurado
        // via arquivo (deploy FTP) nunca fique inalcançável por dados antigos
        // salvos na própria tabela de configurações.
        if (str_starts_with($chave, 'db.') && ($varEnv = self::variavelEnvDoBanco($chave))) {
            $v = self::fromEnv($varEnv);
            if ($v !== null && $v !== '' && ! self::isPlaceholder($v)) {
                return $v;
            }
        }

        $valor = self::$cache[$chave] ?? null;

        if ($valor !== null && $valor !== '') {
            return $valor;
        }

        return $default ?? (self::$padroes[$chave] ?? null);
    }

    protected static function variavelEnvDoBanco(string $chave): ?string
    {
        return [
            'db.database' => 'DB_DATABASE',
            'db.username' => 'DB_USERNAME',
            'db.password' => 'DB_PASSWORD',
            'db.host'     => 'DB_HOST',
            'db.port'     => 'DB_PORT',
        ][$chave] ?? null;
    }

    public static function fromEnv(string $varEnv, ?string $default = null): ?string
    {
        $valor = env($varEnv);

        return ($valor !== null && $valor !== '') ? (string) $valor : $default;
    }

    /** Lê com prioridade: banco > .env > padrão. */
    public static function comFallback(string $chave, ?string $varEnv = null, ?string $default = null): ?string
    {
        $valor = self::get($chave);

        if ($valor !== null && $valor !== '' && ! self::isPlaceholder($valor)) {
            return $valor;
        }

        if ($varEnv) {
            $v = self::fromEnv($varEnv, $default);
            if ($v !== null && $v !== '') {
                return $v;
            }
        }

        return $default;
    }

    public static function put(string $chave, ?string $valor): void
    {
        $valor = $valor === null ? null : trim($valor);

        if ($valor !== '' && $valor !== null) {
            // Senhas nunca ficam em texto puro no banco (defesa em profundidade:
            // se alguém baixar o .sqlite ou ler a tabela via outro script, não vê credenciais).
            $valor = self::eSigilosa($chave) ? encrypt($valor) : $valor;
        }

        static::updateOrCreate(
            ['chave' => $chave],
            ['valor' => ($valor === '' ? null : $valor), 'sigilosa' => self::eSigilosa($chave) ? 1 : 0]
        );

        self::$cache = null; // invalida cache da requisição
    }

    /** Chaves que carregam credenciais e devem ser cifradas/mascaradas. */
    public static function eSigilosa(string $chave): bool
    {
        return str_contains($chave, 'password');
    }

    /** Descriptografa transparentemente valores sigilosos lidos do banco. */
    protected static function decodificar(string $chave, string $valor): string
    {
        if (! self::eSigilosa($chave)) {
            return $valor;
        }

        try {
            return (string) decrypt($valor);
        } catch (\Throwable) {
            return $valor; // valor antigo em texto puro (legado) — segue funcionando
        }
    }

    /** Remove todas as chaves de um grupo (ex.: 'db.' ou 'email.'). */
    public static function limparGrupo(string $prefixo): void
    {
        static::where('chave', 'like', $prefixo . '%')->delete();
        self::$cache = null;
    }

    public static function allValues(): array
    {
        self::carregarCache();
        return self::$cache;
    }

    protected static function carregarCache(): void
    {
        if (self::$cache !== null) {
            return;
        }

        try {
            $linhas = DB::table('configuracoes')->get(['chave', 'valor']);

            self::$cache = $linhas
                ->mapWithKeys(fn ($l) => [$l->chave => $l->valor === null ? '' : self::decodificar($l->chave, (string) $l->valor)])
                ->all();
        } catch (\Throwable) {
            self::$cache = [];
        }
    }

    /** Valores de exemplo vindos do .env que não devem ser tratados como configuração real. */
    public static function isPlaceholder(?string $valor): bool
    {
        return in_array($valor, ['laravel', 'root', '127.0.0.1'], true);
    }
}
