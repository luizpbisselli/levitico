<?php

namespace App\Console\Commands;

use App\Models\Cte;
use App\Models\EmailIngestao;
use App\Services\ConciliacaoService;
use App\Services\XmlFiscalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Lê a caixa de e-mail dedicada via IMAP, extrai anexos .xml (e .zip com XMLs),
 * processa NF-e/CT-e/eventos e concilia. Roda pelo scheduler a cada 1-2 minutos.
 * Requer a extensão php-imap no servidor.
 */
class IngestaoEmailCommand extends Command
{
    protected $signature = 'app:ingestao-email';

    protected $description = 'Lê e-mails da caixa dedicada e ingere XMLs de NF-e/CT-e';

    public function handle(XmlFiscalService $xmlService, ConciliacaoService $conciliacao): int
    {
        if (! function_exists('imap_open')) {
            $this->error('Extensão php-imap não disponível. Instale-a para habilitar a ingestão por e-mail.');
            return self::FAILURE;
        }

        $host = config('services.ingestao_email.host');
        if (! $host) {
            $this->warn('Caixa de e-mail não configurada (INGESTAO_EMAIL_HOST). Pulando ingestão.');
            return self::SUCCESS;
        }

        $port = config('services.ingestao_email.port') ?: 993;
        $box  = config('services.ingestao_email.box') ?: 'INBOX';
        $mailbox = sprintf('{%s:%d/imap/ssl}%s', $host, $port, $box);

        $mbox = @imap_open($mailbox, config('services.ingestao_email.user'), config('services.ingestao_email.password'));
        if (! $mbox) {
            EmailIngestao::create([
                'assunto'  => 'CONEXAO_IMAP',
                'status'   => 'erro',
                'detalhes' => (string) (imap_last_error() ?: 'Falha ao conectar IMAP'),
                'lido_em'  => now(),
            ]);
            $this->error('Falha ao conectar na caixa IMAP.');
            return self::FAILURE;
        }

        $mensagens = imap_search($mbox, 'UNSEEN') ?: [];
        $totalPendencias = 0;

        foreach ($mensagens as $msgNo) {
            try {
                $header = imap_headerinfo($mbox, $msgNo);
                $messageId = $header->message_id ?? null;
                $from = $header->from[0] ?? null;
                $remetente = $from ? $from->mailbox.'@'.$from->host : null;

                // Idempotência por Message-ID: reprocessar o mesmo e-mail não duplica nada
                if ($messageId && EmailIngestao::where('message_id', $messageId)->exists()) {
                    EmailIngestao::create(['message_id' => $messageId, 'assunto' => $header->subject ?? null, 'remetente' => $remetente, 'lido_em' => now(), 'status' => 'duplicado']);
                    imap_setflag_full($mbox, (string) $msgNo, '\\Seen');
                    continue;
                }

                $xmls = $this->coletarXmlsDosAnexos($mbox, (int) $msgNo);
                $erros = [];

                foreach ($xmls as $conteudo) {
                    $resultado = $xmlService->processarXml($conteudo);
                    if (! ($resultado['ok'] ?? false)) {
                        $erros[] = $resultado['erro'] ?? 'Erro desconhecido';
                        continue;
                    }
                    if (($resultado['tipo'] ?? '') === 'cte') {
                        $cte = Cte::find($resultado['id']);
                        if ($cte) {
                            $pendencias = $conciliacao->conciliarCte($cte);
                            $totalPendencias += count($pendencias);
                            foreach ($pendencias as $p) {
                                Log::info('[ingestao] pendencia: '.$p);
                            }
                        }
                    }
                }

                EmailIngestao::create([
                    'message_id' => $messageId,
                    'assunto'    => $header->subject ?? null,
                    'remetente'  => $remetente,
                    'lido_em'    => now(),
                    'status'     => $erros ? 'erro' : 'processado',
                    'detalhes'   => $erros ? implode("\n", $erros) : count($xmls).' xml(s) processados',
                ]);

                $this->marcarProcessado($mbox, (int) $msgNo);
            } catch (\Throwable $e) {
                EmailIngestao::create(['assunto' => 'EXCECAO', 'status' => 'erro', 'detalhes' => $e->getMessage(), 'lido_em' => now()]);
                Log::error('[ingestao] '.$e->getMessage());
            }
        }

        imap_close($mbox);
        $this->info(count($mensagens).' mensagem(ns) lida(s), '.$totalPendencias.' pendência(s) registrada(s).');

        return self::SUCCESS;
    }

    /**
     * Baixa anexos .xml e também .zip com XMLs dentro.
     *
     * @return string[] conteúdos XML
     */
    private function coletarXmlsDosAnexos($mbox, int $msgNo): array
    {
        $xmls = [];
        $structure = imap_fetchstructure($mbox, $msgNo);

        foreach ($this->achatarParts($structure) as $part) {
            $nome = strtolower((string) ($part->dparameters[0]->value ?? $part->parameters[0]->value ?? ''));
            if (! str_ends_with($nome, '.xml') && ! str_ends_with($nome, '.zip')) {
                continue;
            }

            $raw  = imap_fetchbody($mbox, $msgNo, $part->partNumber);
            $data = match ((int) ($part->encoding ?? 0)) {
                3       => base64_decode($raw),
                4       => quoted_printable_decode($raw),
                default => $raw,
            };

            if (str_ends_with($nome, '.zip')) {
                $tmp = tempnam(sys_get_temp_dir(), 'cte');
                file_put_contents($tmp, (string) $data);
                $zip = new \ZipArchive();
                if ($zip->open($tmp) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entry = $zip->getNameIndex($i);
                        if ($entry && str_ends_with(strtolower($entry), '.xml')) {
                            $content = $zip->getFromName($entry);
                            if ($content !== false) {
                                $xmls[] = $content;
                            }
                        }
                    }
                    $zip->close();
                }
                @unlink($tmp);
            } else {
                $xmls[] = (string) $data;
            }
        }

        return $xmls;
    }

    private function achatarParts($structure, string $prefixo = ''): array
    {
        $lista = [];
        foreach ($structure->parts ?? [] as $i => $part) {
            $partNumber = $prefixo === '' ? (string) ($i + 1) : $prefixo.'.'.($i + 1);
            $clone = clone $part;
            $clone->partNumber = $partNumber;
            $lista[] = $clone;
            $lista = array_merge($lista, $this->achatarParts($part, $partNumber));
        }

        return $lista;
    }

    /**
     * Move para a pasta "Processados" ou marca como vista.
     */
    private function marcarProcessado($mbox, int $msgNo): void
    {
        $destino = config('services.ingestao_email.pasta_processados');
        if (! $destino) {
            imap_setflag_full($mbox, (string) $msgNo, '\\Seen');
            return;
        }

        $host = rtrim((string) config('services.ingestao_email.host'), '{}');
        $dest = sprintf('{%s}%s', $host, $destino);
        if (@imap_status($mbox, $dest, SA_STATUS) === false) {
            @imap_createmailbox($mbox, $dest);
        }
        @imap_mail_move($mbox, (string) $msgNo, $destino);
    }
}
