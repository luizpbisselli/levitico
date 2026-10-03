<?php

namespace App\Services;

use App\Models\Cte;
use App\Models\Nfe;
use DOMDocument;
use DOMXPath;

/**
 * Identifica, valida e processa arquivos XML fiscais (NF-e, CT-e, Eventos/Cancelamento).
 * Suporta XMLs completos (proc), standalone (apenas CTe/NFe), qualquer variação de namespace
 * e sanitização contra resíduos MIME/assinaturas/BOM.
 */
class XmlFiscalService
{
    /**
     * Sanitiza o conteúdo XML, removendo BOM UTF-8, entidades HTML escapadas
     * e qualquer lixo/assinatura ou MIME boundary existente antes ou depois da tag raiz.
     */
    public function sanitizarXml(string $xml): string
    {
        $xml = trim($xml);
        if ($xml === '') {
            return '';
        }

        // Remove BOM UTF-8 se presente
        $xml = preg_replace('/^\xEF\xBB\xBF/', '', $xml);

        // Se o XML veio com entidades codificadas (ex: copiado de corpo HTML &lt;cte:CTe...)
        if (str_contains($xml, '&lt;') && str_contains($xml, '&gt;')) {
            $decodificado = html_entity_decode($xml, ENT_QUOTES | ENT_XML1, 'UTF-8');
            if (str_contains($decodificado, '<CTe') || str_contains($decodificado, '<cte:') || str_contains($decodificado, '<NFe') || str_contains($decodificado, '<nfeProc') || str_contains($decodificado, '<cteProc')) {
                $xml = $decodificado;
            }
        }

        $tagsRaiz = 'cteProc|CTe|nfeProc|NFe|procEventoNFe|procEventoCTe|eventoNFe|eventoCTe|procInutNFe|procInutCTe|procinutl|inutNFe|inutCTe|mdfeProc|MDFe|CompNfse|InfNfse|retConsStatServ|retConsSitCTe|retConsSitNFe';

        // Localiza onde começa o bloco fiscal (ou <?xml ou <!-- ou <tagRaiz)
        $patternInicio = '/((?:<\?xml[^>]*\?>\s*)?(?:<!--.*?-->\s*)*<([a-zA-Z0-9_\-]+:)?(' . $tagsRaiz . ')[^>]*>)/is';
        if (preg_match($patternInicio, $xml, $m, PREG_OFFSET_CAPTURE)) {
            $posInicio = $m[0][1];
            $prefixo = $m[2][0] ?? '';
            $nomeRaiz = $m[3][0];

            $xml = substr($xml, $posInicio);

            // Procura o fechamento correspondente </prefixo:nomeRaiz> ou </nomeRaiz>
            $fechamento1 = "</{$prefixo}{$nomeRaiz}>";
            $fechamento2 = "</{$nomeRaiz}>";

            $posFim = strripos($xml, $fechamento1);
            $tamFechamento = strlen($fechamento1);
            if ($posFim === false) {
                $posFim = strripos($xml, $fechamento2);
                $tamFechamento = strlen($fechamento2);
            }

            if ($posFim !== false) {
                $xml = substr($xml, 0, $posFim + $tamFechamento);
            }
        }

        return trim($xml);
    }

    /**
     * Valida e diagnostica um arquivo XML, informando com precisão
     * se é bem-formado, seu tipo fiscal e eventuais inconsistências.
     *
     * @return array{
     *     valido: bool,
     *     sintaxe_valida: bool,
     *     tipo: ?string,
     *     descricao_tipo: string,
     *     raiz: ?string,
     *     versao: ?string,
     *     chave_acesso: ?string,
     *     mensagem: string,
     *     detalhes: array<string, mixed>
     * }
     */
    public function validarXml(string $xml): array
    {
        $xmlSanitizado = $this->sanitizarXml($xml);
        if ($xmlSanitizado === '') {
            return [
                'valido'         => false,
                'sintaxe_valida' => false,
                'tipo'           => null,
                'descricao_tipo' => 'Arquivo vazio',
                'raiz'           => null,
                'versao'         => null,
                'chave_acesso'   => null,
                'mensagem'       => 'O conteúdo do XML está vazio.',
                'detalhes'       => [],
            ];
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $carregou = @$dom->loadXML($xmlSanitizado, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $errosLibxml = libxml_get_errors();
        libxml_clear_errors();

        if (! $carregou || ! $dom->documentElement) {
            $msgErro = 'O arquivo enviado não é um XML válido ou está corrompido.';
            if (! empty($errosLibxml)) {
                $primeiroErro = trim($errosLibxml[0]->message);
                $msgErro .= " Detalhe do parser: {$primeiroErro}";
            }

            return [
                'valido'         => false,
                'sintaxe_valida' => false,
                'tipo'           => null,
                'descricao_tipo' => 'XML Inválido / Corrompido',
                'raiz'           => null,
                'versao'         => null,
                'chave_acesso'   => null,
                'mensagem'       => $msgErro,
                'detalhes'       => ['erros_parser' => array_map(fn ($e) => trim($e->message), $errosLibxml)],
            ];
        }

        $raiz = $dom->documentElement->localName ?: $dom->documentElement->nodeName;
        $xpath = new DOMXPath($dom);

        $tipo = $this->identificarTipoPorDom($dom, $xpath);
        $descricao = $this->descreverTipo($tipo, $raiz);
        $versao = $this->extrairVersao($xpath);
        $chave = $this->extrairChaveAcesso($xpath, $tipo);

        if ($tipo === null) {
            return [
                'valido'         => false,
                'sintaxe_valida' => true,
                'tipo'           => null,
                'descricao_tipo' => $descricao,
                'raiz'           => $raiz,
                'versao'         => $versao,
                'chave_acesso'   => null,
                'mensagem'       => "O arquivo é um XML bem-formado (raiz <{$raiz}>), porém não corresponde a um CT-e (mod. 57/67), NF-e (mod. 55/65) ou evento fiscal suportado.",
                'detalhes'       => ['raiz' => $raiz, 'namespace' => $dom->documentElement->namespaceURI],
            ];
        }

        if (in_array($tipo, ['mdfe', 'nfse', 'retorno_sefaz'], true)) {
            return [
                'valido'         => false,
                'sintaxe_valida' => true,
                'tipo'           => $tipo,
                'descricao_tipo' => $descricao,
                'raiz'           => $raiz,
                'versao'         => $versao,
                'chave_acesso'   => $chave,
                'mensagem'       => "O documento é um {$descricao}, que não é processado nesta ingestão de frete (suportados: CT-e e NF-e).",
                'detalhes'       => ['raiz' => $raiz],
            ];
        }

        // Validação de nós essenciais para CT-e e NF-e
        if ($tipo === 'cte' && ! $chave) {
            return [
                'valido'         => false,
                'sintaxe_valida' => true,
                'tipo'           => 'cte',
                'descricao_tipo' => $descricao,
                'raiz'           => $raiz,
                'versao'         => $versao,
                'chave_acesso'   => null,
                'mensagem'       => 'XML identificado como CT-e, mas não foi possível localizar a tag <infCte> com o atributo Id (chave de 44 dígitos).',
                'detalhes'       => ['raiz' => $raiz],
            ];
        }

        if ($tipo === 'nfe' && ! $chave) {
            return [
                'valido'         => false,
                'sintaxe_valida' => true,
                'tipo'           => 'nfe',
                'descricao_tipo' => $descricao,
                'raiz'           => $raiz,
                'versao'         => $versao,
                'chave_acesso'   => null,
                'mensagem'       => 'XML identificado como NF-e, mas não foi possível localizar a tag <infNFe> com o atributo Id (chave de 44 dígitos).',
                'detalhes'       => ['raiz' => $raiz],
            ];
        }

        return [
            'valido'         => true,
            'sintaxe_valida' => true,
            'tipo'           => $tipo,
            'descricao_tipo' => $descricao,
            'raiz'           => $raiz,
            'versao'         => $versao,
            'chave_acesso'   => $chave,
            'mensagem'       => "XML fiscal reconhecido com sucesso ({$descricao}).",
            'detalhes'       => ['raiz' => $raiz, 'versao' => $versao, 'chave' => $chave],
        ];
    }

    /**
     * Identifica o tipo do XML ('nfe', 'cte', 'evento' ou null).
     */
    public function identificarTipo(string $xml): ?string
    {
        $diag = $this->validarXml($xml);
        return in_array($diag['tipo'], ['nfe', 'cte', 'evento'], true) ? $diag['tipo'] : null;
    }

    /**
     * Processa um XML de NF-e. Idempotente pela chave de acesso.
     */
    public function processarNfe(string $xml): array
    {
        $xmlSanitizado = $this->sanitizarXml($xml);
        $dom = new DOMDocument();
        if (! @$dom->loadXML($xmlSanitizado, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return ['ok' => false, 'erro' => 'XML de NF-e com sintaxe inválida ou corrompido.'];
        }

        $xpath = new DOMXPath($dom);
        $infNFeNode = $xpath->query("//*[local-name()='infNFe']")->item(0);
        if (! $infNFeNode) {
            return ['ok' => false, 'erro' => 'XML NF-e sem estrutura esperada (infNFe ausente).'];
        }

        $idAttr = $infNFeNode->attributes->getNamedItem('Id')?->nodeValue ?? '';
        $chave = preg_replace('/\D/', '', $idAttr);
        if (! preg_match('/^\d{44}$/', $chave)) {
            $chave = $this->xpathValue($xpath, "//*[local-name()='protNFe']//*[local-name()='chNFe']") ?: $chave;
        }

        if (! preg_match('/^\d{44}$/', $chave)) {
            return ['ok' => false, 'erro' => 'Chave de acesso de 44 dígitos da NF-e não encontrada.'];
        }

        $nNF = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='nNF']");
        $serie = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='serie']");
        $dhEmi = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='dhEmi']")
            ?: $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='dEmi']");

        $emitNome = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='xNome']");
        $emitCnpj = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='CNPJ']")
            ?: $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='CPF']");

        $destNome = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='xNome']");
        $destDoc = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='CNPJ']")
            ?: $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='CPF']");
        $destFone = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='fone']")
            ?: $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='phone']");

        $enderLgr = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xLgr']");
        $enderNro = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='nro']");
        $enderMun = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xMun']");
        $enderUf  = $this->xpathValue($xpath, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='UF']");

        $vTot = (float) (
            $this->xpathValue($xpath, "//*[local-name()='total']/*[local-name()='ICMSTot']/*[local-name()='vNF']")
            ?: $this->xpathValue($xpath, "//*[local-name()='vNF']")
            ?: 0
        );

        $qVol  = (int) ($this->xpathValue($xpath, "//*[local-name()='transp']/*[local-name()='vol']/*[local-name()='qVol']") ?: 0);
        $pesoB = (float) ($this->xpathValue($xpath, "//*[local-name()='transp']/*[local-name()='vol']/*[local-name()='pesoB']") ?: 0);

        $enderecoCompleto = trim(sprintf('%s, %s - %s/%s', $enderLgr ?: '', $enderNro ?: '', $enderMun ?: '', $enderUf ?: ''), " ,-/");

        $dados = [
            'chave_acesso'           => $chave,
            'numero'                 => $nNF,
            'serie'                  => $serie,
            'emissao'                => !empty($dhEmi) ? substr($dhEmi, 0, 10) : null,
            'emitente_nome'          => $emitNome ?: '',
            'emitente_cnpj'          => $emitCnpj ?: '',
            'destinatario_nome'      => $destNome ?: '',
            'destinatario_documento' => $destDoc ?: '',
            'destinatario_telefone'  => $destFone ?: '',
            'destinatario_endereco'  => $enderecoCompleto,
            'destinatario_cidade'    => $enderMun ?: '',
            'destinatario_uf'        => $enderUf ?: '',
            'valor_total'            => $vTot,
            'volumes'                => $qVol ?: null,
            'peso_bruto'             => $pesoB ?: null,
            'xml_original'           => $xmlSanitizado,
        ];

        $nfe = Nfe::updateOrCreate(['chave_acesso' => $chave], $dados);

        return ['ok' => true, 'tipo' => 'nfe', 'id' => $nfe->id, 'chave' => $chave];
    }

    /**
     * Processa um XML de CT-e. Idempotente pela chave de acesso.
     */
    public function processarCte(string $xml): array
    {
        $xmlSanitizado = $this->sanitizarXml($xml);
        $dom = new DOMDocument();
        if (! @$dom->loadXML($xmlSanitizado, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return ['ok' => false, 'erro' => 'XML de CT-e com sintaxe inválida ou corrompido.'];
        }

        $xpath = new DOMXPath($dom);
        $infCteNode = $xpath->query("//*[local-name()='infCte']")->item(0);
        if (! $infCteNode) {
            return ['ok' => false, 'erro' => 'XML CT-e sem estrutura esperada (infCte ausente).'];
        }

        $idAttr = $infCteNode->attributes->getNamedItem('Id')?->nodeValue ?? '';
        $chave = preg_replace('/\D/', '', $idAttr);
        if (! preg_match('/^\d{44}$/', $chave)) {
            $chave = $this->xpathValue($xpath, "//*[local-name()='protCTe']//*[local-name()='chCTe']") ?: $chave;
        }

        if (! preg_match('/^\d{44}$/', $chave)) {
            return ['ok' => false, 'erro' => 'Chave de acesso de 44 dígitos do CT-e não encontrada.'];
        }

        $nCT = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='nCT']");
        $serie = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='serie']");
        $dhEmi = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='dhEmi']")
            ?: $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='dEmi']");

        $emitNome = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='xNome']");
        $remNome  = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='xNome']");
        $destNome = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='xNome']");
        $destMun  = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xMun']");
        $destUf   = $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='UF']");

        // Resolução do tomador (toma4->xNome, ou toma3->toma indicando rem/dest/exp/rec/emit)
        $tomaNome = $this->xpathValue($xpath, "//*[local-name()='infCte']//*[local-name()='toma4']/*[local-name()='xNome']")
            ?: $this->xpathValue($xpath, "//*[local-name()='infCte']//*[local-name()='toma']/*[local-name()='xNome']");

        if (! $tomaNome) {
            $tomaTipo = $this->xpathValue($xpath, "//*[local-name()='infCte']//*[local-name()='toma3']/*[local-name()='toma']")
                ?: $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='toma03']/*[local-name()='toma']");

            $tomaNome = match ($tomaTipo) {
                '0' => $remNome,
                '1' => $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='exped']/*[local-name()='xNome']"),
                '2' => $this->xpathValue($xpath, "//*[local-name()='infCte']/*[local-name()='receb']/*[local-name()='xNome']"),
                '3' => $destNome,
                default => $remNome ?: $destNome ?: $emitNome,
            };
        }

        // Valor do frete: vPrest -> vTPrest / vRec ou vCTe -> vPCteTTC
        $vFrete = (float) (
            $this->xpathValue($xpath, "//*[local-name()='vPrest']/*[local-name()='vTPrest']")
            ?: $this->xpathValue($xpath, "//*[local-name()='vPrest']/*[local-name()='vRec']")
            ?: $this->xpathValue($xpath, "//*[local-name()='vCTe']/*[local-name()='vPCteTTC']")
            ?: $this->xpathValue($xpath, "//*[local-name()='vTPrest']")
            ?: 0
        );

        // Placa do veículo informado no modal rodoviário
        $placa = $this->xpathValue($xpath, "//*[local-name()='rodo']//*[local-name()='placa']")
            ?: $this->xpathValue($xpath, "//*[local-name()='veicTran']//*[local-name()='placa']")
            ?: $this->xpathValue($xpath, "//*[local-name()='veic']/*[local-name()='placa']");
        $placa = $placa ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placa)) : null;

        $dados = [
            'chave_acesso'        => $chave,
            'numero'              => $nCT,
            'serie'               => $serie,
            'emissao'             => !empty($dhEmi) ? substr($dhEmi, 0, 10) : null,
            'tomador_nome'        => $tomaNome ?: '',
            'remetente_nome'      => $remNome ?: '',
            'destinatario_nome'   => $destNome ?: '',
            'destinatario_cidade' => $destMun ?: '',
            'destinatario_uf'     => $destUf ?: '',
            'valor_frete'         => $vFrete,
            'placa_informada'     => $placa,
            'xml_original'        => $xmlSanitizado,
        ];

        $cte = Cte::updateOrCreate(['chave_acesso' => $chave], $dados);

        return ['ok' => true, 'tipo' => 'cte', 'id' => $cte->id, 'chave' => $chave];
    }

    /**
     * Marca cancelamento a partir de um evento (procInutl / procEvento / evento de cancelamento).
     */
    public function processarEvento(string $xml): array
    {
        $xmlSanitizado = $this->sanitizarXml($xml);
        $dom = new DOMDocument();
        if (@$dom->loadXML($xmlSanitizado, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            $xpath = new DOMXPath($dom);
            $tpEvento = $this->xpathValue($xpath, "//*[local-name()='tpEvento']");
            $descEvento = $this->xpathValue($xpath, "//*[local-name()='descEvento']");
            $chDoc = $this->xpathValue($xpath, "//*[local-name()='chNFe']")
                ?: $this->xpathValue($xpath, "//*[local-name()='chCTe']")
                ?: $this->xpathValue($xpath, "//*[local-name()='cChave']");

            // 110111 = Cancelamento Homologado
            $ehCancelamento = $tpEvento === '110111'
                || str_contains(strtolower($descEvento ?: ''), 'cancelamento')
                || str_contains(strtolower($xmlSanitizado), 'cancelamento');

            if ($ehCancelamento && $chDoc && preg_match('/^\d{44}$/', $chDoc)) {
                Nfe::where('chave_acesso', $chDoc)->update(['cancelada' => true]);
                Cte::where('chave_acesso', $chDoc)->update(['cancelado' => true]);
                return ['ok' => true, 'tipo' => 'evento_cancelamento', 'chave' => $chDoc];
            }
        }

        if (preg_match('/descRef\s*>\s*110111/i', $xmlSanitizado) || str_contains($xmlSanitizado, 'Cancelamento')) {
            if (preg_match('/<(?:cChave|chNFe|chCTe)>(\d{44})<\/(?:cChave|chNFe|chCTe)>|Chave[=:]\s*"?(\d{44})/i', $xmlSanitizado, $m)) {
                $chave = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
                if ($chave) {
                    Nfe::where('chave_acesso', $chave)->update(['cancelada' => true]);
                    Cte::where('chave_acesso', $chave)->update(['cancelado' => true]);
                    return ['ok' => true, 'tipo' => 'evento_cancelamento', 'chave' => $chave];
                }
            }
        }

        return ['ok' => true, 'tipo' => 'evento_ignorado'];
    }

    /**
     * Ponto de entrada principal: valida o XML e roteia conforme o tipo identificado.
     */
    public function processarXml(string $xml): array
    {
        $validacao = $this->validarXml($xml);
        if (! $validacao['valido']) {
            return ['ok' => false, 'erro' => $validacao['mensagem'], 'validacao' => $validacao];
        }

        return match ($validacao['tipo']) {
            'nfe'    => $this->processarNfe($xml),
            'cte'    => $this->processarCte($xml),
            'evento' => $this->processarEvento($xml),
            default  => ['ok' => false, 'erro' => $validacao['mensagem'], 'validacao' => $validacao],
        };
    }

    // -------------------------------------------------------------------------
    // Métodos Auxiliares de Inspeção e Diagnóstico
    // -------------------------------------------------------------------------

    private function identificarTipoPorDom(DOMDocument $dom, DOMXPath $xpath): ?string
    {
        $raiz = strtolower($dom->documentElement->localName ?: $dom->documentElement->nodeName);

        // 1. Verificação por nós específicos
        if ($xpath->query("//*[local-name()='infCte']")->length > 0 || in_array($raiz, ['cte', 'cteproc', 'envicte'], true)) {
            return 'cte';
        }

        if ($xpath->query("//*[local-name()='infNFe']")->length > 0 || in_array($raiz, ['nfe', 'nfeproc', 'envinfe'], true)) {
            return 'nfe';
        }

        if ($xpath->query("//*[local-name()='infEvento']")->length > 0
            || in_array($raiz, ['proceventonfe', 'proceventocte', 'eventonfe', 'eventocte', 'procinutnfe', 'procinutcte', 'procinutl', 'inutnfe', 'inutcte'], true)
        ) {
            return 'evento';
        }

        if ($xpath->query("//*[local-name()='infMDFe']")->length > 0 || in_array($raiz, ['mdfe', 'mdfeproc', 'envimdfe'], true)) {
            return 'mdfe';
        }

        if (str_contains($raiz, 'nfse') || $xpath->query("//*[local-name()='CompNfse' or local-name()='InfNfse']")->length > 0) {
            return 'nfse';
        }

        if (str_starts_with($raiz, 'retcons') || str_starts_with($raiz, 'retenvi')) {
            return 'retorno_sefaz';
        }

        return null;
    }

    private function descreverTipo(?string $tipo, string $raiz): string
    {
        return match ($tipo) {
            'cte'           => 'Conhecimento de Transporte Eletrônico (CT-e - Modelo 57/67)',
            'nfe'           => 'Nota Fiscal Eletrônica (NF-e - Modelo 55/65)',
            'evento'        => 'Evento Fiscal / Cancelamento SEFAZ',
            'mdfe'          => 'Manifesto Eletrônico de Documentos Fiscais (MDF-e - Modelo 58)',
            'nfse'          => 'Nota Fiscal de Serviços Eletrônica (NFS-e Municipal)',
            'retorno_sefaz' => 'Mensagem de Retorno / Consulta SEFAZ',
            default         => "Documento não fiscal ou não suportado (tag raiz: <{$raiz}>)",
        };
    }

    private function extrairVersao(DOMXPath $xpath): ?string
    {
        $versaoNode = $xpath->query("//*[local-name()='infCte' or local-name()='infNFe' or local-name()='infEvento']/@versao")->item(0);
        return $versaoNode?->nodeValue;
    }

    private function extrairChaveAcesso(DOMXPath $xpath, ?string $tipo): ?string
    {
        $idNode = $xpath->query("//*[local-name()='infCte' or local-name()='infNFe']/@Id")->item(0);
        if ($idNode) {
            $chave = preg_replace('/\D/', '', $idNode->nodeValue);
            if (preg_match('/^\d{44}$/', $chave)) {
                return $chave;
            }
        }

        $chDoc = $this->xpathValue($xpath, "//*[local-name()='chCTe' or local-name()='chNFe' or local-name()='chDoc']");
        if ($chDoc && preg_match('/^\d{44}$/', $chDoc)) {
            return $chDoc;
        }

        return null;
    }

    private function xpathValue(DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);
        if (! $node) {
            return null;
        }
        $val = trim($node->nodeValue ?? '');
        return $val !== '' ? $val : null;
    }
}
