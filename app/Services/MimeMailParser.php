<?php

namespace App\Services;

/**
 * Extrai anexos e blocos XML (inclusive dentro de .zip, texto puro ou HTML) do conteúdo bruto MIME de e-mails.
 * Suporta recursão de multipartes, decodificação base64, quoted-printable e conversão de entidades HTML.
 */
class MimeMailParser
{
    /**
     * @return string[] conteúdos XML decodificados e prontos para processamento
     */
    public static function extrairXmls(string $rawMime): array
    {
        $partes = self::extrairTodasAsPartes($rawMime);
        $xmls = [];

        foreach ($partes as $parte) {
            $tipo = strtolower($parte['content_type'] ?? '');
            $disp = strtolower($parte['disposition'] ?? '');
            $nome = strtolower($parte['filename'] ?? '');
            $dados = $parte['corpo'];

            // 1. Arquivo .zip em anexo
            if (str_ends_with($nome, '.zip') || str_contains($tipo, 'zip')) {
                $xmlsDoZip = self::xmlsDoZip($dados);
                if (! empty($xmlDoZip)) {
                    $xmls = array_merge($xmls, $xmlDoZip);
                }
                continue;
            }

            // 2. Arquivo .xml em anexo formal
            if (str_ends_with($nome, '.xml') || str_contains($tipo, 'xml')) {
                $bloco = self::extrairBlocoFiscal($dados);
                if ($bloco !== null) {
                    $xmls[] = $bloco;
                    continue;
                }
            }

            // 3. Qualquer corpo de texto/HTML ou anexo que contenha XML fiscal (colado ou inline)
            $blocosTexto = self::extrairBlocosFiscaisDeTexto($dados);
            foreach ($blocosTexto as $bloco) {
                $xmls[] = $bloco;
            }
        }

        // Se ainda não encontrou nada, faz varredura completa na mensagem bruta
        if (empty($xmls)) {
            $xmls = self::extrairBlocosFiscaisDeTexto($rawMime);
        }

        return array_values(array_unique(array_filter($xmls)));
    }

    /**
     * Divide recursivamente o MIME em todas as suas partes e decodifica cada uma.
     *
     * @return array<int, array{filename: string, content_type: string, disposition: string, corpo: string}>
     */
    public static function extrairTodasAsPartes(string $raw): array
    {
        $partes = [];
        $headersGlobais = '';
        $corpoGlobal = $raw;

        if (preg_match('/^(.*?)\r?\n\r?\n(.*)$/s', $raw, $m)) {
            $headersGlobais = $m[1];
            $corpoGlobal = $m[2];
        }

        // Procura boundary nos headers
        $boundary = self::extrairBoundary($headersGlobais) ?: self::extrairBoundary($raw);

        if (! $boundary) {
            // Mensagem simples (sem multipart)
            $enc = self::extrairHeader($headersGlobais, 'content-transfer-encoding');
            $tipo = self::extrairHeader($headersGlobais, 'content-type');
            $disp = self::extrairHeader($headersGlobais, 'content-disposition');
            $nome = self::parametroHeader($headersGlobais, 'filename') ?? self::parametroHeader($headersGlobais, 'name') ?? '';

            $partes[] = [
                'filename'     => $nome,
                'content_type' => $tipo,
                'disposition'  => $disp,
                'corpo'        => self::decodificarCorpo($corpoGlobal, $enc),
            ];

            return $partes;
        }

        // Divide pelas boundaries
        $blocos = preg_split('/--' . preg_quote($boundary, '/') . '(?:\r?\n|$|--)/', $raw);

        foreach ((array) $blocos as $bloco) {
            $blocoTrim = trim($bloco);
            if ($blocoTrim === '' || $blocoTrim === '--') {
                continue;
            }

            [$hRaw, $cRaw] = array_pad(preg_split('/\r?\n\r?\n/', $bloco, 2), 2, '');

            $subBoundary = self::extrairBoundary($hRaw);
            if ($subBoundary && $subBoundary !== $boundary) {
                // Recursão para multipart interno
                $subPartes = self::extrairTodasAsPartes($cRaw);
                $partes = array_merge($partes, $subPartes);
                continue;
            }

            $enc = self::extrairHeader($hRaw, 'content-transfer-encoding');
            $tipo = self::extrairHeader($hRaw, 'content-type');
            $disp = self::extrairHeader($hRaw, 'content-disposition');
            $nome = self::parametroHeader($hRaw, 'filename') ?? self::parametroHeader($hRaw, 'name') ?? '';

            $partes[] = [
                'filename'     => $nome,
                'content_type' => $tipo,
                'disposition'  => $disp,
                'corpo'        => self::decodificarCorpo((string) $cRaw, $enc),
            ];
        }

        return $partes;
    }

    /**
     * Localiza e isola blocos fiscais válidos dentro de qualquer string de texto ou HTML.
     *
     * @return string[]
     */
    public static function extrairBlocosFiscaisDeTexto(string $texto): array
    {
        $resultados = [];
        $candidatos = [$texto];

        // Se tem entidades HTML (&lt; / &gt;), gera também a versão decodificada
        if (str_contains($texto, '&lt;') && str_contains($texto, '&gt;')) {
            $candidatos[] = html_entity_decode($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        // Se for HTML, remove tags HTML externas para isolar XMLs colados em parágrafos
        if (str_contains($texto, '<html') || str_contains($texto, '<div') || str_contains($texto, '<body')) {
            $limpoHtml = strip_tags(html_entity_decode($texto, ENT_QUOTES | ENT_XML1, 'UTF-8'));
            $candidatos[] = $limpoHtml;
        }

        $tagsRaiz = 'cteProc|CTe|nfeProc|NFe|procEventoNFe|procEventoCTe|eventoNFe|eventoCTe|procInutNFe|procInutCTe|procinutl|inutNFe|inutCTe|mdfeProc|MDFe|CompNfse|InfNfse';
        $pattern = '/((?:<\?xml[^>]*\?>\s*)?(?:<!--.*?-->\s*)*<([a-zA-Z0-9_\-]+:)?(' . $tagsRaiz . ')[^>]*>.*?<\/(?:\2\3|\3)>)/is';

        foreach ($candidatos as $cand) {
            if (preg_match_all($pattern, $cand, $m)) {
                foreach ($m[0] as $bloco) {
                    $blocoLimpo = trim($bloco);
                    if ($blocoLimpo !== '') {
                        $resultados[] = $blocoLimpo;
                    }
                }
            }
        }

        return array_values(array_unique($resultados));
    }

    /**
     * Tenta isolar um bloco fiscal único a partir de uma string.
     */
    public static function extrairBlocoFiscal(string $dados): ?string
    {
        $blocos = self::extrairBlocosFiscaisDeTexto($dados);
        return $blocos[0] ?? (self::pareceXml($dados) ? trim($dados) : null);
    }

    /** Decodifica base64/quoted-printable conforme Content-Transfer-Encoding. */
    public static function decodificarCorpo(string $corpo, string $encoding = ''): string
    {
        $enc = strtolower(trim($encoding));

        if ($enc === 'base64') {
            $dec = base64_decode(preg_replace('/\s+/', '', $corpo), true);
            return $dec !== false ? $dec : $corpo;
        }

        if ($enc === 'quoted-printable') {
            return quoted_printable_decode($corpo);
        }

        // Quando o encoding não foi explicitado, testa se o corpo é base64 puro
        $limpo = preg_replace('/\s+/', '', $corpo) ?? '';
        if (strlen($limpo) > 60 && preg_match('#^[A-Za-z0-9+/=]+$#', substr($limpo, 0, 512))) {
            $dec = base64_decode($limpo, true);
            if ($dec !== false && (str_contains($dec, '<') || str_contains($dec, 'PK') || str_contains($dec, '&lt;'))) {
                return $dec;
            }
        }

        // Verifica se há resquícios de quoted-printable (ex: =3D, =20, =C3=A3)
        if (str_contains($corpo, '=3D') || str_contains($corpo, "=\r\n") || str_contains($corpo, "=\n")) {
            return quoted_printable_decode($corpo);
        }

        return $corpo;
    }

    /** Lê entradas .xml de um zip em memória. */
    protected static function xmlsDoZip(string $dados): array
    {
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

        // Fallback manual para zip deflate
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

    protected static function extrairBoundary(string $headers): ?string
    {
        if (preg_match('/boundary="?([^"\r\n;]+)"?/i', $headers, $m)) {
            return $m[1];
        }
        return null;
    }

    protected static function extrairHeader(string $headers, string $headerName): string
    {
        if (preg_match('/' . preg_quote($headerName, '/') . ':\s*([^\r\n;]+)/i', $headers, $m)) {
            return trim($m[1]);
        }
        return '';
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

    protected static function pareceXml(string $dados): bool
    {
        $inicio = ltrim($dados);
        return str_starts_with($inicio, '<?xml') || str_starts_with($inicio, '<') || str_starts_with($inicio, '&lt;');
    }
}
