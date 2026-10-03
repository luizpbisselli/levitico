<?php

namespace App\Services;

use App\Models\Cte;
use App\Models\Nfe;
use DOMDocument;
use DOMXPath;

/**
 * Extrai todos os campos detalhados do XML original para renderização
 * visual em formato DACTE (CT-e) e DANFE (NF-e).
 */
class DanfeVisualService
{
    /**
     * Extrai os dados completos para visualização do DACTE (CT-e).
     */
    public function extrairDadosDacte(Cte $cte): array
    {
        $xml = (string) $cte->xml_original;
        $xmlSanitizado = (new XmlFiscalService())->sanitizarXml($xml);

        $dados = [
            'id'                  => $cte->id,
            'chave_acesso'        => $cte->chave_acesso,
            'chave_formatada'     => $this->formatarChave($cte->chave_acesso),
            'numero'              => $cte->numero,
            'serie'               => $cte->serie ?? '1',
            'modelo'              => '57',
            'emissao'             => $cte->emissao,
            'emissao_formatada'   => $cte->emissao?->format('d/m/Y') ?? '—',
            'dh_emissao'          => null,
            'tipo_cte'            => '0 - Normal',
            'tipo_servico'        => '0 - Normal',
            'natureza_operacao'   => 'Prestação de Serviço de Transporte',
            'cfop'                => '',
            'modal'               => '01 - Rodoviário',
            'ambiente'            => '1 - Produção',
            'protocolo'           => null,
            'dh_protocolo'        => null,
            // Emitente
            'emitente' => [
                'nome'      => $cte->remetente_nome ?: '—',
                'fantasia'  => '',
                'cnpj'      => '',
                'ie'        => '',
                'logradouro'=> '',
                'numero'    => '',
                'bairro'    => '',
                'municipio' => '',
                'uf'        => '',
                'cep'       => '',
                'telefone'  => '',
            ],
            // Tomador
            'tomador' => [
                'nome'      => $cte->tomador_nome ?: '—',
                'cnpj'      => '',
                'municipio' => '',
                'uf'        => '',
            ],
            // Remetente
            'remetente' => [
                'nome'      => $cte->remetente_nome ?: '—',
                'fantasia'  => '',
                'cnpj'      => '',
                'ie'        => '',
                'endereco'  => '',
                'municipio' => '',
                'uf'        => '',
                'cep'       => '',
                'telefone'  => '',
            ],
            // Destinatário
            'destinatario' => [
                'nome'      => $cte->destinatario_nome ?: '—',
                'cnpj'      => '',
                'ie'        => '',
                'endereco'  => '',
                'municipio' => $cte->destinatario_cidade ?: '',
                'uf'        => $cte->destinatario_uf ?: '',
                'cep'       => '',
                'telefone'  => '',
            ],
            // Prestação
            'inicio_prestacao'   => '',
            'fim_prestacao'      => '',
            // Valores
            'valor_frete'        => (float) $cte->valor_frete,
            'valor_receber'      => (float) $cte->valor_frete,
            'componentes_frete'  => [],
            // Tributos
            'tributos' => [
                'cst'        => '',
                'base_icms'  => 0.0,
                'aliq_icms'  => 0.0,
                'valor_icms' => 0.0,
                'tot_trib'   => 0.0,
            ],
            // Carga
            'carga' => [
                'valor'          => 0.0,
                'produto_pred'   => 'Diversos',
                'peso_bruto'     => null,
                'peso_liquido'   => null,
                'volumes'        => null,
                'outras_unidades'=> [],
            ],
            // Modal Rodoviário / Veículo
            'rodoviario' => [
                'rntrc' => '',
                'placa' => $cte->placa_informada,
                'uf'    => '',
            ],
            // NF-e Vinculadas
            'nfes_vinculadas'    => [],
            'observacoes'        => '',
            'xml_original'       => $xml,
        ];

        if ($xmlSanitizado === '') {
            return $dados;
        }

        $dom = new DOMDocument();
        if (! @$dom->loadXML($xmlSanitizado, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return $dados;
        }

        $xp = new DOMXPath($dom);

        // Identificação
        $dados['modelo']            = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='mod']") ?: '57';
        $dados['serie']             = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='serie']") ?: $dados['serie'];
        $dados['numero']            = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='nCT']") ?: $dados['numero'];
        $dados['natureza_operacao'] = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='natOp']") ?: $dados['natureza_operacao'];
        $dados['cfop']              = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='CFOP']") ?: '';
        $dados['dh_emissao']        = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='dhEmi']")
            ?: $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='dEmi']");
        if ($dados['dh_emissao']) {
            $dados['emissao_formatada'] = substr($dados['dh_emissao'], 8, 2) . '/' . substr($dados['dh_emissao'], 5, 2) . '/' . substr($dados['dh_emissao'], 0, 4) . ' ' . substr($dados['dh_emissao'], 11, 8);
        }

        $tpAmb = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='tpAmb']");
        if ($tpAmb === '2') {
            $dados['ambiente'] = '2 - Homologação (Sem Valor Fiscal)';
        }

        // Início e Fim da Prestação
        $munIni = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='xMunIni']");
        $ufIni  = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='UFIni']");
        $munFim = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='xMunFim']");
        $ufFim  = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='ide']/*[local-name()='UFFim']");
        $dados['inicio_prestacao'] = trim("{$munIni} - {$ufIni}", " -");
        $dados['fim_prestacao']    = trim("{$munFim} - {$ufFim}", " -");

        // Protocolo
        $dados['protocolo']    = $this->v($xp, "//*[local-name()='protCTe']//*[local-name()='nProt']");
        $dados['dh_protocolo'] = $this->v($xp, "//*[local-name()='protCTe']//*[local-name()='dhRecbto']");

        // Emitente
        $dados['emitente']['nome']      = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='xNome']") ?: $dados['emitente']['nome'];
        $dados['emitente']['fantasia']  = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='xFant']") ?: '';
        $dados['emitente']['cnpj']      = $this->formatarCpfCnpj($this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='CNPJ']"));
        $dados['emitente']['ie']        = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='IE']") ?: '';
        $dados['emitente']['logradouro']= $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='xLgr']") ?: '';
        $dados['emitente']['numero']    = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='nro']") ?: '';
        $dados['emitente']['bairro']    = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='xBairro']") ?: '';
        $dados['emitente']['municipio'] = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='xMun']") ?: '';
        $dados['emitente']['uf']        = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='UF']") ?: '';
        $dados['emitente']['cep']       = $this->formatarCep($this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='CEP']"));
        $dados['emitente']['telefone']  = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='fone']") ?: '';

        // Remetente
        $dados['remetente']['nome']      = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='xNome']") ?: $dados['remetente']['nome'];
        $dados['remetente']['fantasia']  = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='xFant']") ?: '';
        $dados['remetente']['cnpj']      = $this->formatarCpfCnpj($this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='CNPJ']") ?: $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='CPF']"));
        $dados['remetente']['ie']        = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='IE']") ?: '';
        $dados['remetente']['endereco']  = trim(sprintf('%s, %s - %s',
            $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='enderReme']/*[local-name()='xLgr']"),
            $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='enderReme']/*[local-name()='nro']"),
            $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='enderReme']/*[local-name()='xBairro']")
        ), " ,-");
        $dados['remetente']['municipio'] = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='enderReme']/*[local-name()='xMun']") ?: '';
        $dados['remetente']['uf']        = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='enderReme']/*[local-name()='UF']") ?: '';
        $dados['remetente']['cep']       = $this->formatarCep($this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='enderReme']/*[local-name()='CEP']"));
        $dados['remetente']['telefone']  = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='rem']/*[local-name()='fone']") ?: '';

        // Destinatário
        $dados['destinatario']['nome']      = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='xNome']") ?: $dados['destinatario']['nome'];
        $dados['destinatario']['cnpj']      = $this->formatarCpfCnpj($this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='CNPJ']") ?: $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='CPF']"));
        $dados['destinatario']['ie']        = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='IE']") ?: '';
        $dados['destinatario']['endereco']  = trim(sprintf('%s, %s - %s',
            $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xLgr']"),
            $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='nro']"),
            $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xBairro']")
        ), " ,-");
        $dados['destinatario']['municipio'] = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xMun']") ?: $dados['destinatario']['municipio'];
        $dados['destinatario']['uf']        = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='UF']") ?: $dados['destinatario']['uf'];
        $dados['destinatario']['cep']       = $this->formatarCep($this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='CEP']"));
        $dados['destinatario']['telefone']  = $this->v($xp, "//*[local-name()='infCte']/*[local-name()='dest']/*[local-name()='fone']") ?: '';

        // Valores de Frete e Componentes
        $vTPrest = $this->v($xp, "//*[local-name()='vPrest']/*[local-name()='vTPrest']");
        $vRec    = $this->v($xp, "//*[local-name()='vPrest']/*[local-name()='vRec']");
        if ($vTPrest) {
            $dados['valor_frete'] = (float) $vTPrest;
        }
        if ($vRec) {
            $dados['valor_receber'] = (float) $vRec;
        }

        $compNodes = $xp->query("//*[local-name()='vPrest']/*[local-name()='Comp']");
        foreach ($compNodes as $comp) {
            $nomeComp = $xp->query("./*[local-name()='xNome']", $comp)->item(0)?->nodeValue;
            $valComp  = $xp->query("./*[local-name()='vComp']", $comp)->item(0)?->nodeValue;
            if ($nomeComp) {
                $dados['componentes_frete'][] = [
                    'nome'  => trim($nomeComp),
                    'valor' => (float) $valComp,
                ];
            }
        }

        // Impostos
        $dados['tributos']['cst']        = $this->v($xp, "//*[local-name()='imp']//*[local-name()='CST']") ?: '';
        $dados['tributos']['base_icms']  = (float) ($this->v($xp, "//*[local-name()='imp']//*[local-name()='vBC']") ?: 0);
        $dados['tributos']['aliq_icms']  = (float) ($this->v($xp, "//*[local-name()='imp']//*[local-name()='pICMS']") ?: 0);
        $dados['tributos']['valor_icms'] = (float) ($this->v($xp, "//*[local-name()='imp']//*[local-name()='vICMS']") ?: 0);
        $dados['tributos']['tot_trib']   = (float) ($this->v($xp, "//*[local-name()='imp']//*[local-name()='vTotTrib']") ?: 0);

        // Carga
        $dados['carga']['valor']        = (float) ($this->v($xp, "//*[local-name()='infCarga']/*[local-name()='vCarga']") ?: 0);
        $dados['carga']['produto_pred'] = $this->v($xp, "//*[local-name()='infCarga']/*[local-name()='proPred']") ?: $dados['carga']['produto_pred'];

        $infQNodes = $xp->query("////*[local-name()='infCarga']/*[local-name()='infQ']");
        foreach ($infQNodes as $qNode) {
            $tpMed = $xp->query("./*[local-name()='tpMed']", $qNode)->item(0)?->nodeValue;
            $qCarga = (float) ($xp->query("./*[local-name()='qCarga']", $qNode)->item(0)?->nodeValue ?: 0);
            $cUnid = $xp->query("./*[local-name()='cUnid']", $qNode)->item(0)?->nodeValue;

            if (stripos($tpMed, 'PESO BRUTO') !== false || $tpMed === 'PESO') {
                $dados['carga']['peso_bruto'] = $qCarga;
            } elseif (stripos($tpMed, 'PESO LIQUIDO') !== false) {
                $dados['carga']['peso_liquido'] = $qCarga;
            } elseif (stripos($tpMed, 'VOLUMES') !== false || stripos($tpMed, 'QUANTIDADE') !== false || $cUnid === '03') {
                $dados['carga']['volumes'] = (int) $qCarga;
            } else {
                $dados['carga']['outras_unidades'][] = [
                    'tipo'       => trim($tpMed ?: 'UNIDADE'),
                    'quantidade' => $qCarga,
                ];
            }
        }

        // Modal Rodoviário
        $dados['rodoviario']['rntrc'] = $this->v($xp, "//*[local-name()='rodo']//*[local-name()='RNTRC']") ?: '';
        $dados['rodoviario']['placa'] = $this->v($xp, "//*[local-name()='rodo']//*[local-name()='placa']") ?: $dados['rodoviario']['placa'];

        // Observações
        $dados['observacoes'] = $this->v($xp, "//*[local-name()='compl']/*[local-name()='xObs']")
            ?: $this->v($xp, "//*[local-name()='infCte']/*[local-name()='compl']/*[local-name()='xObs']") ?: '';

        // NF-e Vinculadas
        $chavesNfe = (new ConciliacaoService())->extrairChavesNfeDoXml($xmlSanitizado);
        $nfesExistentes = Nfe::whereIn('chave_acesso', $chavesNfe)->get()->keyBy('chave_acesso');

        foreach ($chavesNfe as $ch) {
            $nfeModel = $nfesExistentes->get($ch);
            $dados['nfes_vinculadas'][] = [
                'chave'           => $ch,
                'chave_formatada' => $this->formatarChave($ch),
                'nfe_id'          => $nfeModel?->id,
                'numero'          => $nfeModel?->numero,
                'valor'           => $nfeModel?->valor_total,
                'destinatario'    => $nfeModel?->destinatario_nome,
                'recebida'        => (bool) $nfeModel,
            ];
        }

        return $dados;
    }

    /**
     * Extrai os dados completos para visualização do DANFE (NF-e).
     */
    public function extrairDadosDanfe(Nfe $nfe): array
    {
        $xml = (string) $nfe->xml_original;
        $xmlSanitizado = (new XmlFiscalService())->sanitizarXml($xml);

        $dados = [
            'id'                  => $nfe->id,
            'chave_acesso'        => $nfe->chave_acesso,
            'chave_formatada'     => $this->formatarChave($nfe->chave_acesso),
            'numero'              => $nfe->numero,
            'serie'               => $nfe->serie ?? '1',
            'modelo'              => '55',
            'tipo_operacao'       => '1 - Saída',
            'natureza_operacao'   => 'Venda de mercadorias',
            'emissao'             => $nfe->emissao,
            'emissao_formatada'   => $nfe->emissao?->format('d/m/Y') ?? '—',
            'dh_emissao'          => null,
            'cancelada'           => (bool) $nfe->cancelada,
            'protocolo'           => null,
            'dh_protocolo'        => null,
            // Emitente
            'emitente' => [
                'nome'      => $nfe->emitente_nome ?: '—',
                'fantasia'  => '',
                'cnpj'      => $this->formatarCpfCnpj($nfe->emitente_cnpj),
                'ie'        => '',
                'endereco'  => '',
                'municipio' => '',
                'uf'        => '',
                'cep'       => '',
                'telefone'  => '',
            ],
            // Destinatário
            'destinatario' => [
                'nome'      => $nfe->destinatario_nome ?: '—',
                'cnpj'      => $this->formatarCpfCnpj($nfe->destinatario_documento),
                'ie'        => '',
                'endereco'  => $nfe->destinatario_endereco ?: '—',
                'municipio' => $nfe->destinatario_cidade ?: '—',
                'uf'        => $nfe->destinatario_uf ?: '—',
                'cep'       => '',
                'telefone'  => $nfe->destinatario_telefone ?: '',
            ],
            // Totais e Impostos
            'totais' => [
                'base_icms'     => 0.0,
                'valor_icms'    => 0.0,
                'base_icms_st'  => 0.0,
                'valor_icms_st' => 0.0,
                'valor_produtos'=> (float) $nfe->valor_total,
                'valor_frete'   => 0.0,
                'valor_seguro'  => 0.0,
                'desconto'      => 0.0,
                'outras_desp'   => 0.0,
                'valor_ipi'     => 0.0,
                'valor_total'   => (float) $nfe->valor_total,
            ],
            // Transporte
            'transporte' => [
                'modalidade_frete' => '0 - Emitente',
                'transportadora'   => '',
                'cnpj'             => '',
                'ie'               => '',
                'placa'            => '',
                'uf'               => '',
                'volumes'          => $nfe->volumes,
                'especie'          => 'VOLUMES',
                'peso_bruto'       => $nfe->peso_bruto,
                'peso_liquido'     => null,
            ],
            // Itens / Produtos
            'itens'               => [],
            // Duplicatas / Cobrança
            'duplicatas'          => [],
            // Dados Adicionais
            'informacoes_adicionais' => '',
            'xml_original'        => $xml,
        ];

        if ($xmlSanitizado === '') {
            return $dados;
        }

        $dom = new DOMDocument();
        if (! @$dom->loadXML($xmlSanitizado, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return $dados;
        }

        $xp = new DOMXPath($dom);

        // Identificação
        $dados['modelo']            = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='mod']") ?: '55';
        $dados['serie']             = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='serie']") ?: $dados['serie'];
        $dados['numero']            = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='nNF']") ?: $dados['numero'];
        $dados['natureza_operacao'] = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='natOp']") ?: $dados['natureza_operacao'];
        $dados['dh_emissao']        = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='dhEmi']")
            ?: $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='dEmi']");
        if ($dados['dh_emissao']) {
            $dados['emissao_formatada'] = substr($dados['dh_emissao'], 8, 2) . '/' . substr($dados['dh_emissao'], 5, 2) . '/' . substr($dados['dh_emissao'], 0, 4) . ' ' . substr($dados['dh_emissao'], 11, 8);
        }

        $tpNF = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='ide']/*[local-name()='tpNF']");
        $dados['tipo_operacao'] = $tpNF === '0' ? '0 - Entrada' : '1 - Saída';

        // Protocolo
        $dados['protocolo']    = $this->v($xp, "//*[local-name()='protNFe']//*[local-name()='nProt']");
        $dados['dh_protocolo'] = $this->v($xp, "//*[local-name()='protNFe']//*[local-name()='dhRecbto']");

        // Emitente
        $dados['emitente']['nome']      = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='xNome']") ?: $dados['emitente']['nome'];
        $dados['emitente']['fantasia']  = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='xFant']") ?: '';
        $dados['emitente']['cnpj']      = $this->formatarCpfCnpj($this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='CNPJ']") ?: $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='CPF']"));
        $dados['emitente']['ie']        = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='IE']") ?: '';
        $dados['emitente']['endereco']  = trim(sprintf('%s, %s - %s',
            $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='xLgr']"),
            $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='nro']"),
            $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='xBairro']")
        ), " ,-");
        $dados['emitente']['municipio'] = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='xMun']") ?: '';
        $dados['emitente']['uf']        = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='UF']") ?: '';
        $dados['emitente']['cep']       = $this->formatarCep($this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='CEP']"));
        $dados['emitente']['telefone']  = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='fone']") ?: '';

        // Destinatário
        $dados['destinatario']['nome']      = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='xNome']") ?: $dados['destinatario']['nome'];
        $dados['destinatario']['cnpj']      = $this->formatarCpfCnpj($this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='CNPJ']") ?: $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='CPF']"));
        $dados['destinatario']['ie']        = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='IE']") ?: '';
        $dados['destinatario']['endereco']  = trim(sprintf('%s, %s - %s',
            $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xLgr']"),
            $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='nro']"),
            $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xBairro']")
        ), " ,-") ?: $dados['destinatario']['endereco'];
        $dados['destinatario']['municipio'] = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='xMun']") ?: $dados['destinatario']['municipio'];
        $dados['destinatario']['uf']        = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='UF']") ?: $dados['destinatario']['uf'];
        $dados['destinatario']['cep']       = $this->formatarCep($this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='CEP']"));
        $dados['destinatario']['telefone']  = $this->v($xp, "//*[local-name()='infNFe']/*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='fone']") ?: $dados['destinatario']['telefone'];

        // Totais
        $icmsTot = $xp->query("//*[local-name()='total']/*[local-name()='ICMSTot']")->item(0);
        if ($icmsTot) {
            $dados['totais']['base_icms']     = (float) ($this->v($xp, "./*[local-name()='vBC']", $icmsTot) ?: 0);
            $dados['totais']['valor_icms']    = (float) ($this->v($xp, "./*[local-name()='vICMS']", $icmsTot) ?: 0);
            $dados['totais']['base_icms_st']  = (float) ($this->v($xp, "./*[local-name()='vBCST']", $icmsTot) ?: 0);
            $dados['totais']['valor_icms_st'] = (float) ($this->v($xp, "./*[local-name()='vST']", $icmsTot) ?: 0);
            $dados['totais']['valor_produtos']= (float) ($this->v($xp, "./*[local-name()='vProd']", $icmsTot) ?: $dados['totais']['valor_produtos']);
            $dados['totais']['valor_frete']   = (float) ($this->v($xp, "./*[local-name()='vFrete']", $icmsTot) ?: 0);
            $dados['totais']['valor_seguro']  = (float) ($this->v($xp, "./*[local-name()='vSeg']", $icmsTot) ?: 0);
            $dados['totais']['desconto']      = (float) ($this->v($xp, "./*[local-name()='vDesc']", $icmsTot) ?: 0);
            $dados['totais']['outras_desp']   = (float) ($this->v($xp, "./*[local-name()='vOutro']", $icmsTot) ?: 0);
            $dados['totais']['valor_ipi']     = (float) ($this->v($xp, "./*[local-name()='vIPI']", $icmsTot) ?: 0);
            $dados['totais']['valor_total']   = (float) ($this->v($xp, "./*[local-name()='vNF']", $icmsTot) ?: $dados['totais']['valor_total']);
        }

        // Transporte
        $transpNode = $xp->query("//*[local-name()='transp']")->item(0);
        if ($transpNode) {
            $modFrete = $this->v($xp, "./*[local-name()='modFrete']", $transpNode);
            $dados['transporte']['modalidade_frete'] = match ($modFrete) {
                '0' => '0 - Por conta do Emitente (CIF)',
                '1' => '1 - Por conta do Destinatário (FOB)',
                '2' => '2 - Por conta de Terceiros',
                '3' => '3 - Próprio / Remetente',
                '4' => '4 - Próprio / Destinatário',
                '9' => '9 - Sem Ocorrência de Transporte',
                default => ($modFrete ? "{$modFrete} - Outro" : '0 - Emitente'),
            };

            $dados['transporte']['transportadora'] = $this->v($xp, "./*[local-name()='transporta']/*[local-name()='xNome']", $transpNode) ?: '';
            $dados['transporte']['cnpj']           = $this->formatarCpfCnpj($this->v($xp, "./*[local-name()='transporta']/*[local-name()='CNPJ']", $transpNode) ?: $this->v($xp, "./*[local-name()='transporta']/*[local-name()='CPF']", $transpNode));
            $dados['transporte']['ie']             = $this->v($xp, "./*[local-name()='transporta']/*[local-name()='IE']", $transpNode) ?: '';
            $dados['transporte']['placa']          = $this->v($xp, "./*[local-name()='veicTransp']/*[local-name()='placa']", $transpNode) ?: '';
            $dados['transporte']['uf']             = $this->v($xp, "./*[local-name()='veicTransp']/*[local-name()='UF']", $transpNode) ?: '';

            $qVol = $this->v($xp, "./*[local-name()='vol']/*[local-name()='qVol']", $transpNode);
            if ($qVol) {
                $dados['transporte']['volumes'] = (int) $qVol;
            }
            $dados['transporte']['especie']      = $this->v($xp, "./*[local-name()='vol']/*[local-name()='esp']", $transpNode) ?: $dados['transporte']['especie'];
            $dados['transporte']['peso_bruto']   = (float) ($this->v($xp, "./*[local-name()='vol']/*[local-name()='pesoB']", $transpNode) ?: $dados['transporte']['peso_bruto']);
            $dados['transporte']['peso_liquido'] = (float) ($this->v($xp, "./*[local-name()='vol']/*[local-name()='pesoL']", $transpNode) ?: 0);
        }

        // Itens / Produtos
        $detNodes = $xp->query("//*[local-name()='det']");
        foreach ($detNodes as $det) {
            $nItem = $det->attributes->getNamedItem('nItem')?->nodeValue ?: (count($dados['itens']) + 1);
            $prod = $xp->query("./*[local-name()='prod']", $det)->item(0);
            $imposto = $xp->query("./*[local-name()='imposto']", $det)->item(0);

            if ($prod) {
                $dados['itens'][] = [
                    'nItem'      => $nItem,
                    'codigo'     => $this->v($xp, "./*[local-name()='cProd']", $prod) ?: '',
                    'descricao'  => $this->v($xp, "./*[local-name()='xProd']", $prod) ?: 'Produto',
                    'ncm'        => $this->v($xp, "./*[local-name()='NCM']", $prod) ?: '',
                    'cfop'       => $this->v($xp, "./*[local-name()='CFOP']", $prod) ?: '',
                    'unidade'    => $this->v($xp, "./*[local-name()='uCom']", $prod) ?: 'UN',
                    'quantidade' => (float) ($this->v($xp, "./*[local-name()='qCom']", $prod) ?: 1),
                    'valor_unit' => (float) ($this->v($xp, "./*[local-name()='vUnCom']", $prod) ?: 0),
                    'valor_total'=> (float) ($this->v($xp, "./*[local-name()='vProd']", $prod) ?: 0),
                    'cst'        => $imposto ? ($this->v($xp, ".//*[local-name()='CST'] | .//*[local-name()='CSOSN']", $imposto) ?: '') : '',
                    'aliq_icms'  => $imposto ? (float) ($this->v($xp, ".//*[local-name()='pICMS']", $imposto) ?: 0) : 0.0,
                ];
            }
        }

        // Informações Adicionais
        $dados['informacoes_adicionais'] = $this->v($xp, "//*[local-name()='infAdic']/*[local-name()='infCpl']")
            ?: $this->v($xp, "//*[local-name()='infAdic']/*[local-name()='infAdFisco']") ?: '';

        return $dados;
    }

    private function v(DOMXPath $xp, string $query, ?\DOMNode $context = null): ?string
    {
        $node = $context ? $xp->query($query, $context)->item(0) : $xp->query($query)->item(0);
        if (! $node) {
            return null;
        }
        $val = trim($node->nodeValue ?? '');
        return $val !== '' ? $val : null;
    }

    private function formatarChave(string $chave): string
    {
        $ch = preg_replace('/\D/', '', $chave);
        if (strlen($ch) !== 44) {
            return $chave;
        }
        return chunk_split($ch, 4, ' ');
    }

    private function formatarCpfCnpj(?string $doc): string
    {
        $doc = preg_replace('/\D/', '', (string) $doc);
        if (strlen($doc) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }
        if (strlen($doc) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        }
        return $doc ?: '—';
    }

    private function formatarCep(?string $cep): string
    {
        $c = preg_replace('/\D/', '', (string) $cep);
        if (strlen($c) === 8) {
            return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $c);
        }
        return $cep ?: '';
    }
}
