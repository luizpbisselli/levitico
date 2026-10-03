<?php

namespace Tests\Unit;

use App\Models\Cte;
use App\Models\Nfe;
use App\Services\ConciliacaoService;
use App\Services\XmlFiscalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XmlFiscalServiceTest extends TestCase
{
    use RefreshDatabase;

    private XmlFiscalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new XmlFiscalService();
    }

    public function test_reconhece_e_processa_exemplo_cte_receita_federal_com_prefixo_namespace(): void
    {
        $xml = <<<XML
<cte:CTe xmlns:cte="http://www.portalfiscal.inf.br/cte">
    <cte:infCte versao="4.00" Id="CTe35261000000000000000570010000000011234567890">
        <cte:ide>
            <cte:cUF>35</cte:cUF>
            <cte:cCT>12345678</cte:cCT>
            <cte:CFOP>3535</cte:CFOP>
            <cte:natOp>Prestação de serviço de transporte</cte:natOp>
            <cte:mod>57</cte:mod>
            <cte:serie>1</cte:serie>
            <cte:nCT>1</cte:nCT>
            <cte:dhEmi>2026-10-03T10:00:00-03:00</cte:dhEmi>
            <cte:tpImp>1</cte:tpImp>
            <cte:tpEmis>1</cte:tpEmis>
            <cte:cDV>0</cte:cDV>
            <cte:tpAmb>2</cte:tpAmb>
            <cte:tpCTe>0</cte:tpCTe>
            <cte:procEmi>0</cte:procEmi>
            <cte:verProc>4.00</cte:verProc>
            <cte:cMunEnv>3550308</cte:cMunEnv>
            <cte:xMunEnv>SÃO PAULO</cte:xMunEnv>
            <cte:UFEnv>SP</cte:UFEnv>
            <cte:modal>01</cte:modal>
            <cte:tpServ>0</cte:tpServ>
            <cte:cMunIni>3550308</cte:cMunIni>
            <cte:xMunIni>SÃO PAULO</cte:xMunIni>
            <cte:UFIni>SP</cte:UFIni>
            <cte:cMunFim>3534708</cte:cMunFim>
            <cte:xMunFim>OURINHOS</cte:xMunFim>
            <cte:UFFim>SP</cte:UFFim>
            <cte:retira>0</cte:retira>
        </cte:ide>
        <cte:emit>
            <cte:CNPJ>00000000000191</cte:CNPJ>
            <cte:IE>123456789012</cte:IE>
            <cte:xNome>Transportadora Exemplo SP Ltda</cte:xNome>
            <cte:enderEmit>
                <cte:xLgr>Avenida Paulista</cte:xLgr>
                <cte:nro>1000</cte:nro>
                <cte:xBairro>Bela Vista</cte:xBairro>
                <cte:cMun>3550308</cte:cMun>
                <cte:xMun>SÃO PAULO</cte:xMun>
                <cte:CEP>01310100</cte:CEP>
                <cte:UF>SP</cte:UF>
            </cte:enderEmit>
        </cte:emit>
        <cte:vPrest>
            <cte:vTPrest>200.00</cte:vTPrest>
            <cte:vRec>200.00</cte:vRec>
            <cte:Comp>
                <cte:xNome>Frete Peso</cte:xNome>
                <cte:vComp>200.00</cte:vComp>
            </cte:Comp>
        </cte:vPrest>
        <cte:imp>
            <cte:ICMS>
                <cte:ICMS00>
                    <cte:CST>00</cte:CST>
                    <cte:vBC>200.00</cte:vBC>
                    <cte:pICMS>12.00</cte:pICMS>
                    <cte:vICMS>24.00</cte:vICMS>
                </cte:ICMS00>
            </cte:ICMS>
        </cte:imp>
        <cte:infCTeNorm>
            <cte:infCarga>
                <cte:vCarga>5000.00</cte:vCarga>
                <cte:proPred>Diversos</cte:proPred>
                <cte:infQ>
                    <cte:cUnid>01</cte:cUnid>
                    <cte:tpMed>PESO BRUTO</cte:tpMed>
                    <cte:qCarga>150.0000</cte:qCarga>
                </cte:infQ>
            </cte:infCarga>
            <cte:infDoc>
                <cte:infNFe>
                    <cte:chNFe>35261012345678000191550010000000011234567890</cte:chNFe>
                </cte:infNFe>
            </cte:infDoc>
        </cte:infCTeNorm>
    </cte:infCte>
</cte:CTe>
XML;

        $tipo = $this->service->identificarTipo($xml);
        $this->assertEquals('cte', $tipo);

        $resultado = $this->service->processarXml($xml);
        $this->assertTrue($resultado['ok'], $resultado['erro'] ?? '');
        $this->assertEquals('cte', $resultado['tipo']);

        $cte = Cte::find($resultado['id']);
        $this->assertNotNull($cte);
        $this->assertEquals('35261000000000000000570010000000011234567890', $cte->chave_acesso);
        $this->assertEquals('1', $cte->numero);
        $this->assertEquals(200.00, $cte->valor_frete);

        // Testa extração de chave NF-e pela conciliação
        $conciliacao = new ConciliacaoService();
        $chaves = $conciliacao->extrairChavesNfeDoXml($xml);
        $this->assertContains('35261012345678000191550010000000011234567890', $chaves);
    }

    public function test_validacao_retorna_diagnostico_claro_para_xml_nao_fiscal(): void
    {
        $xmlRss = '<?xml version="1.0"?><rss version="2.0"><channel><title>News</title></channel></rss>';
        $diag = $this->service->validarXml($xmlRss);

        $this->assertFalse($diag['valido']);
        $this->assertTrue($diag['sintaxe_valida']);
        $this->assertEquals('rss', $diag['raiz']);
        $this->assertStringContainsString('não corresponde a um CT-e', $diag['mensagem']);

        $res = $this->service->processarXml($xmlRss);
        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('rss', $res['erro']);
    }

    public function test_reconhece_e_processa_nfe_standalone_e_com_protocolo(): void
    {
        $xmlNfe = <<<XML
<nfeProc versao="4.00" xmlns="http://www.portalfiscal.inf.br/nfe">
    <NFe>
        <infNFe versao="4.00" Id="NFe35261012345678000191550010000000011234567890">
            <ide>
                <nNF>999</nNF>
                <serie>1</serie>
                <dhEmi>2026-10-03T10:00:00-03:00</dhEmi>
            </ide>
            <emit>
                <CNPJ>12345678000191</CNPJ>
                <xNome>Empresa Vendedora Ltda</xNome>
            </emit>
            <dest>
                <CNPJ>98765432000188</CNPJ>
                <xNome>Cliente Comprador SA</xNome>
                <enderDest>
                    <xLgr>Rua das Flores</xLgr>
                    <nro>123</nro>
                    <xMun>Campinas</xMun>
                    <UF>SP</UF>
                </enderDest>
            </dest>
            <total>
                <ICMSTot>
                    <vNF>1500.50</vNF>
                </ICMSTot>
            </total>
        </infNFe>
    </NFe>
</nfeProc>
XML;

        $resultado = $this->service->processarXml($xmlNfe);
        $this->assertTrue($resultado['ok'], $resultado['erro'] ?? '');
        $this->assertEquals('nfe', $resultado['tipo']);

        $nfe = Nfe::find($resultado['id']);
        $this->assertNotNull($nfe);
        $this->assertEquals('35261012345678000191550010000000011234567890', $nfe->chave_acesso);
        $this->assertEquals('999', $nfe->numero);
        $this->assertEquals(1500.50, $nfe->valor_total);
    }

    public function test_diagnostica_mdfe_e_nfse_informando_que_sao_documentos_distintos(): void
    {
        $xmlMdfe = '<mdfeProc versao="3.00"><MDFe><infMDFe Id="MDFe35261000000000000000580010000000011234567890"></infMDFe></MDFe></mdfeProc>';
        $diagMdfe = $this->service->validarXml($xmlMdfe);

        $this->assertFalse($diagMdfe['valido']);
        $this->assertEquals('mdfe', $diagMdfe['tipo']);
        $this->assertStringContainsString('MDF-e', $diagMdfe['mensagem']);

        $xmlNfse = '<CompNfse><InfNfse><Numero>1234</Numero></InfNfse></CompNfse>';
        $diagNfse = $this->service->validarXml($xmlNfse);

        $this->assertFalse($diagNfse['valido']);
        $this->assertEquals('nfse', $diagNfse['tipo']);
        $this->assertStringContainsString('NFS-e', $diagNfse['mensagem']);
    }

    public function test_processa_evento_de_cancelamento(): void
    {
        // Cria CT-e e NF-e prévios
        $nfe = Nfe::create([
            'chave_acesso' => '35261012345678000191550010000000011234567890',
            'numero' => '1',
            'cancelada' => false,
        ]);

        $xmlEvento = <<<XML
<procEventoNFe versao="1.00" xmlns="http://www.portalfiscal.inf.br/nfe">
    <evento versao="1.00">
        <infEvento Id="ID1101113526101234567800019155001000000001123456789001">
            <chNFe>35261012345678000191550010000000011234567890</chNFe>
            <tpEvento>110111</tpEvento>
            <detEvento versao="1.00">
                <descEvento>Cancelamento</descEvento>
            </detEvento>
        </infEvento>
    </evento>
</procEventoNFe>
XML;

        $res = $this->service->processarXml($xmlEvento);
        $this->assertTrue($res['ok']);
        $this->assertEquals('evento_cancelamento', $res['tipo']);

        $nfe->refresh();
        $this->assertTrue((bool) $nfe->cancelada);
    }
}
