<?php

namespace App\Services;

/**
 * Extrai anexos XML (inclusive dentro de .zip) do conteúdo bruto de uma
 * mensagem MIME. Funciona sem a extensão php-imap, usando apenas
 * decode_mime_header implícito + zlib — adequado para hospedagem compartilhada.
 */
class MimeMailParser
{
    /**
     * @return string[] conteúdos XML decodificados
     */
    public static function extrairXmls(string $rawMime): array
    {
        $xmls = [];

        foreach (self::corposAnexos($rawMime) as $nome => $corpo) {
            $nome = strtolower($nome);
            $dados = self::decodificarCorpo($corpo);

            if (str_ends_with($nome, '.xml')) {
                $xmls[] = $dados;
            } elseif (str_ends_with($nome, '.zip')) {
                $xmls = array_merge($xmls, self::xmlsDoZip($dados));
            }
        }

        // Fallback: alguns gateways enviam o XML inline no corpo text/xml
        if (! $xmls && preg_match('/<\?xml[^>]*\?>\s*<(nfeProc|ctesProc|CFe|procCancInfPrest|envEvento)/i', $rawMime, $m)) {
            $ini = strpos($rawMime, $m[0]);
            $xmls[] = substr($rawMime, $ini);
        }

        return $xmls;
    }

    /**
     * Varre o MIME procurando partes com Content-Disposition attachment
     * ou Content-Type application/xml|zip|octet-stream.
     *
     * @return array<string,string> nome => corpo bruto (ainda codificado)
     */
    protected static function corposAnexos(string $raw): array
    {
        $anexos = [];

        // Boundary raiz
        if (! preg_match('/boundary="?([^"\r\n;]+)"?/i', $raw, $mb)) {
            return $anexos;
        }
        $boundary = $mb[1];

        // Divide em partes de nível 1
        $blocos = preg_split('/--' . preg_quote($boundary, '/') . '(?:\r?\n|$)/', $raw);

        foreach ((array) $blocos as $bloco) {
            if (trim($bloco) === '' || trim($bloco) === '--') {
                continue;
            }

            [$headersRaw, $corpo] = array_pad(preg_split('/\r?\n\r?\n/', $bloco, 2), 2, '');

            // Content-Transfer-Encoding da parte (base64 / quoted-printable / 8bit)
            $encoding = '';
            if (preg_match('/content-transfer-encoding:\s*([^\s;\r\n]+)/i', (string) $headersRaw, $me)) {
                $encoding = strtolower($me[1]);
            }

            $nome = self::parametroHeader((string) $headersRaw, 'filename')
                 ?? self::parametroHeader((string) $headersRaw, 'name');

            $ehAnexo = stripos((string) $headersRaw, 'content-disposition') !== false
                && stripos((string) $headersRaw, 'attachment') !== false;
            $ehTipoXml = preg_match('/content-type:\s*(application\/(xml|zip|octet-stream)|text\/xml)/i', (string) $headersRaw) === 1;

            if (($ehAnexo || $ehTipoXml) && $nome) {
                $dados = self::decodificarCorpo((string) $corpo, $encoding);
                $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
                if ($ext === 'xml' || $ext === 'zip' || $ehTipoXml) {
                    $anexos[$nome] = $dados;
                }
            }

            // Parte multipart interna (ex.: mixed dentro de related): recursão leve
            if (preg_match('/multipart\/\w+.*boundary="?([^"\r\n;]+)"?/is', (string) $headersRaw, $mi)) {
                $sub = self::dividirPorBoundary((string) $corpo, $mi[1]);
                foreach ($sub as $s) {
                    [$h2, $c2] = array_pad(preg_split('/\r?\n\r?\n/', $s, 2), 2, '');
                    $enc2 = '';
                    if (preg_match('/content-transfer-encoding:\s*([^\s;\r\n]+)/i', (string) $h2, $me2)) {
                        $enc2 = strtolower($me2[1]);
                    }
                    $n2 = self::parametroHeader((string) $h2, 'filename') ?? self::parametroHeader((string) $h2, 'name');
                    $att2 = stripos((string) $h2, 'attachment') !== false;
                    $xml2 = preg_match('/content-type:\s*(application\/(xml|zip|octet-stream)|text\/xml)/i', (string) $h2) === 1;
                    if ($n2 && ($att2 || $xml2)) {
                        $anexos[$n2] = self::decodificarCorpo((string) $c2, $enc2);
                    }
                }
            }
        }

        return $anexos;
    }

    /** @return string[] */
    protected static function dividirPorBoundary(string $corpo, string $boundary): array
    {
        return preg_split('/--' . preg_quote($boundary, '/') . '(?:\r?\n|$)/', $corpo) ?: [];
    }

    protected static function parametroHeader(string $headers, string $param): ?string
    {
        if (preg_match('/' . preg_quote($param, '/') . '\s*=\s*"([^"]+)"/i', $headers, $m)) {
            return $m[1];
        }
        if (preg_match('/' . preg_quote($param, '/') . '\s*=\s*([^\s;"\'\r\n]+)/i', $headers, $m)) {
            return $m[1];
        }

        return null;
    }

    /** Decodifica base64/quoted-printable conforme Content-Transfer-Encoding. */
    protected static function decodificarCorpo(string $corpo, string $encoding = ''): string
    {
        $corpo = trim($corpo);

        // Detecta pelo próprio conteúdo quando o header não acompanha o bloco
        if ($encoding === '') {
            $limpo = preg_replace('/\s+/', '', $corpo) ?? '';
            if (preg_match('#^[A-Za-z0-9+/=]+$#', substr($limpo, 0, 512))) {
                $dec = base64_decode($limpo, true);
                if ($dec !== false) {
                    return $dec;
                }
            }
        }

        return match ($encoding) {
            'base64'            => (string) base64_decode(preg_replace('/\s+/', '', $corpo)),
            'quoted-printable'  => quoted_printable_decode($corpo),
            default             => $corpo,
        };
    }

    /** Lê entradas .xml de um zip em memória (sem ZipArchive/temp files). */
    protected static function xmlsDoZip(string $dados): array
    {
        // Método principal: stream via data:// (usa ZipArchive, presente em ~99% das hospedagens)
        if (class_exists(\ZipArchive::class)) {
            $xmls = [];
            $zip = new \ZipArchive();
            if ($zip->open('data://text/plain;base64,' . base64_encode($dados)) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if ($entry && str_ends_with(strtolower($entry), '.xml')) {
                        $content = $zip->getFromName($entry);
                        if ($content !== false && $content !== '') {
                            $xmls[] = $content;
                        }
                    }
                }
                $zip->close();

                return $xmls;
            }
        }

        // Fallback manual: parser de local headers (deflate/stored)
        $xmls = [];
        $offset = 0;
        $tamanho = strlen($dados);

        while (($pos = strpos($dados, "PK\x03\x04", $offset)) !== false) {
            $hdr = substr($dados, $pos, 30);
            $info = unpack('Vver/vflags/vmetodo/vtempo/vdata/vcrc/Vcompactado/Vdescompactado/vnlen/vxlen', substr($hdr, 4));
            if (! $info) {
                break;
            }
            $nomeLen = $info['nlen'];
            $extraLen = $info['xlen'];
            $nome = substr($dados, $pos + 30, $nomeLen);
            $tamCompactado = $info['compactado'];
            $metodo = $info['metodo'];

            // Data descriptor (flag bit 3): tamanho vem depois — tratado abaixo
            if (($info['flags'] & 0x08) && $tamCompactado === 0) {
                $busca = strpos($dados, "PK", $pos + 30 + $nomeLen + $extraLen);
                $tamCompactado = $busca !== false ? $busca - ($pos + 30 + $nomeLen + $extraLen) : 0;
            }

            $payload = substr($dados, $pos + 30 + $nomeLen + $extraLen, $tamCompactado);

            if (str_ends_with(strtolower($nome), '.xml') && $payload !== false && $payload !== '') {
                if ($metodo === 8 && function_exists('gzinflate')) {
                    $inflado = @gzinflate(substr($payload, 2));
                    $conteudo = $inflado !== false ? $inflado : $payload;
                } elseif ($metodo === 0) {
                    $conteudo = $payload;
                } else {
                    $conteudo = '';
                }

                if ($conteudo !== '') {
                    $xmls[] = $conteudo;
                }
            }

            $offset = $pos + 30 + $nomeLen + $extraLen + max($tamCompactado, 1);
            if ($offset >= $tamanho) {
                break;
            }
        }

        return $xmls;
    }
}
