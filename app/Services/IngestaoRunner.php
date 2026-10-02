<?php

namespace App\Services;

use App\Models\Cte;
use App\Models\EmailIngestao;
use Illuminate\Support\Facades\Log;

/**
 * Processa mensagens da caixa de ingestão (NF-e/CT-e em anexo).
 *
 * Compartilhado entre o comando artisan `app:ingestao-email` e a tela
 * "Configurações" do painel admin (botão "Processar agora"), que roda em
 * ambiente web — por isso limita tempo/memória e usa ImapMailboxService,
 * que funciona tanto com a extensão php-imap quanto via socket SSL nativo.
 */
class IngestaoRunner
{
    /**
     * @return array{lidas:int, processados:int, erros:string[], pendencias:int}
     */
    public function executar(int $limite = 50): array
    {
        @set_time_limit(120);
        @ini_set('memory_limit', '512M');

        $resultado = ['lidas' => 0, 'processados' => 0, 'erros' => [], 'pendencias' => 0];

        if (! ImapMailboxService::estaConfigurado()) {
            $resultado['erros'][] = 'Caixa de e-mail não configurada (Configurações → E-mail de ingestão).';

            return $resultado;
        }

        $imap = new ImapMailboxService();
        $xmlService = app(XmlFiscalService::class);
        $conciliacao = app(ConciliacaoService::class);

        try {
            $mensagens = $imap->listarNaoLidas();
        } catch (\Throwable $e) {
            EmailIngestao::create([
                'assunto'  => 'CONEXAO_IMAP',
                'status'   => 'erro',
                'detalhes' => $e->getMessage(),
                'lido_em'  => now(),
            ]);
            $resultado['erros'][] = 'Falha IMAP: ' . $e->getMessage();

            return $resultado;
        }

        foreach (array_slice($mensagens, 0, $limite) as $msg) {
            $resultado['lidas']++;

            try {
                // Idempotência por Message-ID: reprocessar o mesmo e-mail não duplica nada
                if ($msg['message_id'] && EmailIngestao::where('message_id', $msg['message_id'])->exists()) {
                    EmailIngestao::create([
                        'message_id' => $msg['message_id'],
                        'assunto'    => $msg['assunto'],
                        'remetente'  => $msg['remetente'],
                        'lido_em'    => now(),
                        'status'     => 'duplicado',
                    ]);
                    $imap->marcarLida($msg['uid']);
                    continue;
                }

                $raw = $imap->obterMensagem($msg['uid']);
                $xmls = MimeMailParser::extrairXmls($raw);

                $erros = [];
                foreach ($xmls as $conteudo) {
                    $r = $xmlService->processarXml($conteudo);
                    if (! ($r['ok'] ?? false)) {
                        $erros[] = $r['erro'] ?? 'Erro desconhecido';
                        continue;
                    }
                    if (($r['tipo'] ?? '') === 'cte') {
                        $cte = Cte::find($r['id'] ?? null);
                        if ($cte) {
                            $pendencias = $conciliacao->conciliarCte($cte);
                            $resultado['pendencias'] += count($pendencias);
                            foreach ($pendencias as $p) {
                                Log::info('[ingestao] pendencia: ' . $p);
                            }
                        }
                    }
                }

                EmailIngestao::create([
                    'message_id' => $msg['message_id'],
                    'assunto'    => $msg['assunto'],
                    'remetente'  => $msg['remetente'],
                    'lido_em'    => now(),
                    'status'     => $erros ? 'erro' : 'processado',
                    'detalhes'   => $erros ? implode("\n", $erros) : count($xmls) . ' xml(s) processados',
                ]);

                if (! $erros) {
                    $resultado['processados']++;
                } else {
                    $resultado['erros'] = array_merge($resultado['erros'], $erros);
                }

                $imap->moverParaProcessados($msg['uid']) || $imap->marcarLida($msg['uid']);
            } catch (\Throwable $e) {
                EmailIngestao::create([
                    'assunto'   => 'EXCECAO',
                    'status'    => 'erro',
                    'detalhes'  => $e->getMessage(),
                    'lido_em'   => now(),
                ]);
                $resultado['erros'][] = $e->getMessage();
                Log::error('[ingestao] ' . $e->getMessage());
            }
        }

        return $resultado;
    }
}
