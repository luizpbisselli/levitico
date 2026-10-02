<?php

namespace App\Services;

/**
 * Acesso IMAP à caixa de e-mail dedicada que recebe os XMLs de NF-e/CT-e.
 *
 * Fonte de configuração (nesta ordem): tabela `configuracoes` (editável na
 * área administrativa) > variáveis INGESTAO_EMAIL_* do .env.
 *
 * Usa a extensão php-imap quando disponível (padrão em quase toda hospedagem
 * compartilhada cPanel). Caso contrário, cai para um cliente IMAP leve em
 * sockets SSL — sem dependências externas e sem etapa de build.
 */
class ImapMailboxService
{
    /** @return array{host:string,port:int,user:string,password:string,box:string} */
    public static function config(): array
    {
        return [
            'host'     => (string) \App\Models\Configuracao::comFallback('email.host', 'INGESTAO_EMAIL_HOST', ''),
            'port'     => (int) \App\Models\Configuracao::comFallback('email.port', 'INGESTAO_EMAIL_PORT', '993'),
            'user'     => (string) \App\Models\Configuracao::comFallback('email.user', 'INGESTAO_EMAIL_USER', ''),
            'password' => (string) \App\Models\Configuracao::comFallback('email.password', 'INGESTAO_EMAIL_PASSWORD', ''),
            'box'      => (string) \App\Models\Configuracao::comFallback('email.box', 'INGESTAO_EMAIL_BOX', 'INBOX'),
        ];
    }

    public static function estaConfigurado(): bool
    {
        $c = self::config();

        return $c['host'] !== '' && $c['user'] !== '';
    }

    public static function usandoExtImap(): bool
    {
        return function_exists('imap_open');
    }

    /**
     * Lista mensagens não lidas: [['uid' => int|string, 'message_id' => ?string,
     * 'assunto' => ?string, 'remetente' => ?string], ...]
     */
    public function listarNaoLidas(): array
    {
        if (self::usandoExtImap()) {
            return $this->listarComExtensao();
        }

        return $this->listarComSocket();
    }

    /** Retorna o conteúdo bruto completo de uma mensagem (headers + corpo MIME). */
    public function obterMensagem($uid): string
    {
        if (self::usandoExtImap()) {
            $mbox = $this->abrirExtensao();
            $msgNo = is_string($uid) && str_starts_with($uid, 'uid:')
                ? (int) imap_msgno($mbox, (int) substr($uid, 4))
                : (int) $uid;
            $raw = imap_fetchheader($mbox, $msgNo) . imap_body($mbox, $msgNo);
            imap_close($mbox);

            return $raw;
        }

        return $this->fetchComSocket((int) $uid);
    }

    /** Marca a mensagem como lida (\Seen). */
    public function marcarLida($uid): void
    {
        if (self::usandoExtImap()) {
            $mbox = $this->abrirExtensao();
            $msgNo = is_string($uid) && str_starts_with($uid, 'uid:')
                ? (int) imap_msgno($mbox, (int) substr($uid, 4))
                : (int) $uid;
            @imap_setflag_full($mbox, (string) $msgNo, '\\Seen');
            imap_close($mbox);

            return;
        }

        $this->storeComSocket((int) $uid, '+FLAGS (\\Seen)');
    }

    /** Move a mensagem para a pasta de processados (cria a pasta se necessário). */
    public function moverParaProcessados($uid, ?string $pasta = null): bool
    {
        $pasta ??= (string) \App\Models\Configuracao::comFallback(
            'email.pasta_processados', 'INGESTAO_EMAIL_PASTA', 'Processados'
        );

        if (self::usandoExtImap()) {
            $mbox = $this->abrirExtensao();
            $msgNo = is_string($uid) && str_starts_with($uid, 'uid:')
                ? (int) imap_msgno($mbox, (int) substr($uid, 4))
                : (int) $uid;

            if (! @imap_status($mbox, $pasta, SA_STATUS)) {
                @imap_createmailbox($mbox, imap_utf7_encode($pasta));
            }

            $ok = @imap_mail_move($mbox, (string) $msgNo, $pasta);
            @imap_expunge($mbox);
            imap_close($mbox);

            return (bool) $ok;
        }

        return $this->moveComSocket((int) $uid, $pasta);
    }

    /** Teste de conexão (usado pela tela Configurações). */
    public function testarConexao(): array
    {
        $c = self::config();
        if ($c['host'] === '' || $c['user'] === '') {
            return ['ok' => false, 'erro' => 'Preencha host e usuário da caixa de e-mail.'];
        }

        try {
            $mensagens = $this->listarNaoLidas();

            return [
                'ok'   => true,
                'info' => sprintf(
                    'Conectado a %s:%d como %s (%s). Não lidas: %d.',
                    $c['host'], $c['port'], $c['user'],
                    self::usandoExtImap() ? 'extensão imap' : 'socket SSL nativo',
                    count($mensagens)
                ),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    // ------------------------------------------------------------------
    // Caminho 1: extensão php-imap
    // ------------------------------------------------------------------

    protected function abrirExtensao()
    {
        $c = self::config();
        $mailbox = sprintf('{%s:%d/imap/ssl/novalidate-cert}%s', $c['host'], $c['port'], $c['box']);
        $mbox = @imap_open($mailbox, $c['user'], $c['password']);

        if (! $mbox) {
            throw new \RuntimeException('Falha IMAP: ' . (imap_last_error() ?: 'conexão recusada'));
        }

        return $mbox;
    }

    protected function listarComExtensao(): array
    {
        $mbox = $this->abrirExtensao();
        $numeros = imap_search($mbox, 'UNSEEN') ?: [];
        $lista = [];

        foreach ($numeros as $n) {
            $h = imap_headerinfo($mbox, $n);
            $from = $h->from[0] ?? null;
            $lista[] = [
                'uid'       => (string) $n,
                'message_id' => $h->message_id ?? null,
                'assunto'   => $h->subject ?? null,
                'remetente' => $from ? $from->mailbox . '@' . $from->host : null,
            ];
        }

        imap_close($mbox);

        return $lista;
    }

    // ------------------------------------------------------------------
    // Caminho 2: cliente IMAP leve sobre fsockopen (SSL) — sem dependências
    // ------------------------------------------------------------------

    protected function conectarSocket()
    {
        $c = self::config();
        $remote = sprintf('ssl://%s:%d', $c['host'], $c['port']);
        $sock = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $this->contextSsl());

        if (! $sock) {
            throw new \RuntimeException("Não foi possível conectar em {$remote}: {$errstr} ({$errno})");
        }

        stream_set_timeout($sock, 30);
        $this->esperaResposta($sock); // banner *OK

        $this->comando($sock, 'LOGIN', sprintf('"%s" "%s"', $this->escapa($c['user']), $this->escapa($c['password'])));
        $this->comando($sock, 'SELECT', '"' . $this->escapa($c['box']) . '"');

        return $sock;
    }

    protected function contextSsl()
    {
        return stream_context_create(['ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]]);
    }

    protected function escapa(string $s): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $s);
    }

    protected function esperaResposta($sock, ?string $tag = null): string
    {
        $buf = '';
        while (! feof($sock)) {
            $linha = fgets($sock, 8192);
            if ($linha === false) {
                break;
            }
            $buf .= $linha;
            if ($tag === null && (str_starts_with($linha, '* OK') || str_starts_with($linha, '+ '))) {
                break;
            }
            if ($tag !== null && (preg_match('/^' . preg_quote($tag, '/') . ' (OK|NO|BAD)/i', $linha))) {
                break;
            }
        }

        return $buf;
    }

    protected function comando($sock, string $comando, string $args = ''): string
    {
        $tag = 'T' . bin2hex(random_bytes(4));
        fwrite($sock, $tag . ' ' . $comando . ($args !== '' ? ' ' . $args : '') . "\r\n");
        $resposta = $this->esperaResposta($sock, $tag);

        if (! preg_match('/^' . preg_quote($tag, '/') . ' OK/im', $resposta)) {
            throw new \RuntimeException(trim($resposta) ?: "Comando {$comando} falhou");
        }

        return $resposta;
    }

    protected function listarComSocket(): array
    {
        $sock = $this->conectarSocket();
        $resposta = $this->comando($sock, 'SEARCH', 'UNSEEN');
        $nums = [];
        if (preg_match('/^\* SEARCH(.*)$/mi', $resposta, $m)) {
            $nums = preg_split('/\s+/', trim($m[1]), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $lista = [];
        foreach ($nums as $n) {
            $hdr = $this->comando($sock, 'FETCH', $n . ' (BODY[HEADER.FIELDS (SUBJECT MESSAGE-ID FROM)] FLAGS)');
            $assunto = $remetente = $messageId = null;
            if (preg_match('/Subject:\s*(.+?)\r?\n(?=[A-Z-]+:|\))/is', $hdr, $m)) {
                $assunto = trim($m[1]);
            }
            if (preg_match('/Message-ID:\s*<?([^>\r\n]+)>?/i', $hdr, $m)) {
                $messageId = trim($m[1]);
            }
            if (preg_match('/From:\s*(.+?)\r?\n(?=[A-Z-]+:|\))/is', $hdr, $m)) {
                if (preg_match('/[\w.+-]+@[\w.-]+/', $m[1], $me)) {
                    $remetente = $me[0];
                }
            }
            $lista[] = ['uid' => (string) $n, 'message_id' => $messageId, 'assunto' => $assunto, 'remetente' => $remetente];
        }

        fclose($sock);

        return $lista;
    }

    protected function fetchComSocket(int $n): string
    {
        $sock = $this->conectarSocket();
        $resposta = $this->comando($sock, 'FETCH', $n . ' BODY[]');
        fclose($sock);

        // Extrai o bloco literal {bytes}\r\n...
        if (preg_match('/BODY\[\]\s*\{(\d+)\}\r\n/s', $resposta, $m, PREG_OFFSET_CAPTURE)) {
            $ini = $m[0][1] + strlen($m[0][0]);
            $len = (int) $m[1][0];

            return substr($resposta, $ini, $len);
        }

        return '';
    }

    protected function storeComSocket(int $n, string $flags): void
    {
        $sock = $this->conectarSocket();
        $this->comando($sock, 'STORE', $n . ' ' . $flags);
        fclose($sock);
    }

    protected function moveComSocket(int $n, string $pasta): bool
    {
        try {
            $sock = $this->conectarSocket();
            try {
                $this->comando($sock, 'CREATE', '"' . $this->escapa($pasta) . '"');
            } catch (\Throwable) {
                // já existe — ok
            }
            $this->comando($sock, 'MOVE', $n . ' "' . $this->escapa($pasta) . '"');
            $this->comando($sock, 'EXPUNGE');
            fclose($sock);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
