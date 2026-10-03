<?php

namespace Tests\Unit;

use App\Models\Cte;
use App\Models\Nfe;
use App\Services\ConciliacaoService;
use App\Services\MimeMailParser;
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
        $xml = <<<'XML'
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
        $xmlNfe = <<<'XML'
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
        $nfe = Nfe::create([
            'chave_acesso' => '35261012345678000191550010000000011234567890',
            'numero' => '1',
            'cancelada' => false,
        ]);

        $xmlEvento = <<<'XML'
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

    public function test_processa_cte_com_lixo_de_email_ou_boundary_no_final(): void
    {
        $xmlBrutoComLixo = <<<'XML'
<cte:CTe xmlns:cte="http://www.portalfiscal.inf.br/cte">
    <cte:infCte versao="4.00" Id="CTe35261000000000000000570010000000011234567891">
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
        </cte:emit>
        <cte:vPrest>
            <cte:vTPrest>200.00</cte:vTPrest>
            <cte:vRec>200.00</cte:vRec>
        </cte:vPrest>
        <cte:infCTeNorm>
            <cte:infCarga>
                <cte:vCarga>5000.00</cte:vCarga>
                <cte:proPred>Diversos</cte:proPred>
            </cte:infCarga>
            <cte:infDoc>
                <cte:infNFe>
                    <cte:chNFe>35261012345678000191550010000000011234567891</cte:chNFe>
                </cte:infNFe>
            </cte:infDoc>
        </cte:infCTeNorm>
    </cte:infCte>
</cte:CTe>
--0000000000003b879c060f1b5b2e
Content-Type: text/plain; charset="UTF-8"
Enviado do meu iPhone
--0000000000003b879c060f1b5b2e--
XML;

        $resultado = $this->service->processarXml($xmlBrutoComLixo);
        $this->assertTrue($resultado['ok'], $resultado['erro'] ?? '');
        $this->assertEquals('cte', $resultado['tipo']);

        $cte = Cte::find($resultado['id']);
        $this->assertNotNull($cte);
        $this->assertEquals('35261000000000000000570010000000011234567891', $cte->chave_acesso);
    }

    public function test_processa_cte_exemplo_com_comentario_xml_e_tag_chave_interna(): void
    {
        $xmlComentario = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!-- CT-e de EXEMPLO (modelo 57, layout 4.00) - ambiente de HOMOLOGACAO - dados ficticios -->
<CTe xmlns="http://www.portalfiscal.inf.br/cte">
  <infCte Id="CTe35261005990385000103570010000001231482159372" versao="4.00">
    <ide>
      <cUF>35</cUF>
      <cCT>48215937</cCT>
      <CFOP>6353</CFOP>
      <natOp>PRESTACAO DE SERVICO DE TRANSPORTE</natOp>
      <mod>57</mod>
      <serie>1</serie>
      <nCT>123</nCT>
      <dhEmi>2026-10-03T09:30:00-03:00</dhEmi>
      <tpImp>1</tpImp>
      <tpEmis>1</tpEmis>
      <cDV>2</cDV>
      <tpAmb>2</tpAmb>
      <tpCTe>0</tpCTe>
      <procEmi>0</procEmi>
      <verProc>1.0.0</verProc>
      <cMunEnv>3550308</cMunEnv>
      <xMunEnv>SAO PAULO</xMunEnv>
      <UFEnv>SP</UFEnv>
      <modal>01</modal>
      <tpServ>0</tpServ>
      <cMunIni>3550308</cMunIni>
      <xMunIni>SAO PAULO</xMunIni>
      <UFIni>SP</UFIni>
      <cMunFim>3304557</cMunFim>
      <xMunFim>RIO DE JANEIRO</xMunFim>
      <UFFim>RJ</UFFim>
      <retira>1</retira>
      <indIEToma>1</indIEToma>
      <toma3>
        <toma>0</toma>
      </toma3>
    </ide>
    <compl>
      <xObs>DOCUMENTO DE TESTE - SEM VALOR FISCAL</xObs>
    </compl>
    <emit>
      <CNPJ>05990385000103</CNPJ>
      <IE>110042490114</IE>
      <xNome>TRANSPORTADORA EXEMPLO LTDA</xNome>
      <xFant>TRANSPORTADORA EXEMPLO</xFant>
      <enderEmit>
        <xLgr>AVENIDA PAULISTA</xLgr>
        <nro>1000</nro>
        <xBairro>BELA VISTA</xBairro>
        <cMun>3550308</cMun>
        <xMun>SAO PAULO</xMun>
        <CEP>01310100</CEP>
        <UF>SP</UF>
        <fone>1130000000</fone>
      </enderEmit>
    </emit>
    <rem>
      <CNPJ>74660289000101</CNPJ>
      <IE>110042490115</IE>
      <xNome>INDUSTRIA REMETENTE S/A</xNome>
      <xFant>REMETENTE</xFant>
      <fone>1140000000</fone>
      <enderReme>
        <xLgr>RUA DAS INDUSTRIAS</xLgr>
        <nro>250</nro>
        <xBairro>VILA LEOPOLDINA</xBairro>
        <cMun>3550308</cMun>
        <xMun>SAO PAULO</xMun>
        <CEP>05314000</CEP>
        <UF>SP</UF>
        <cPais>1058</cPais>
        <xPais>BRASIL</xPais>
      </enderReme>
    </rem>
    <dest>
      <CNPJ>56619784000195</CNPJ>
      <IE>82000123</IE>
      <xNome>COMERCIAL DESTINATARIA LTDA</xNome>
      <fone>2130000000</fone>
      <enderDest>
        <xLgr>RUA DO COMERCIO</xLgr>
        <nro>77</nro>
        <xBairro>CENTRO</xBairro>
        <cMun>3304557</cMun>
        <xMun>RIO DE JANEIRO</xMun>
        <CEP>20010000</CEP>
        <UF>RJ</UF>
        <cPais>1058</cPais>
        <xPais>BRASIL</xPais>
      </enderDest>
    </dest>
    <vPrest>
      <vTPrest>1500.00</vTPrest>
      <vRec>1500.00</vRec>
      <Comp>
        <xNome>FRETE VALOR</xNome>
        <vComp>1500.00</vComp>
      </Comp>
    </vPrest>
    <imp>
      <ICMS>
        <ICMS00>
          <CST>00</CST>
          <vBC>1500.00</vBC>
          <pICMS>12.00</pICMS>
          <vICMS>180.00</vICMS>
        </ICMS00>
      </ICMS>
      <vTotTrib>0.00</vTotTrib>
    </imp>
    <infCTeNorm>
      <infCarga>
        <vCarga>25000.00</vCarga>
        <proPred>MATERIAL DE CONSTRUCAO</proPred>
        <infQ>
          <cUnid>01</cUnid>
          <tpMed>PESO BRUTO</tpMed>
          <qCarga>1200.0000</qCarga>
        </infQ>
      </infCarga>
      <infDoc>
        <infNFe>
          <chave>35261074660289000101550010000045671318640529</chave>
        </infNFe>
      </infDoc>
      <infModal versaoModal="4.00">
        <rodo>
          <RNTRC>12345678</RNTRC>
        </rodo>
      </infModal>
    </infCTeNorm>
  </infCte>
</CTe>
XML;

        $resultado = $this->service->processarXml($xmlComentario);
        $this->assertTrue($resultado['ok'], $resultado['erro'] ?? '');
        $this->assertEquals('cte', $resultado['tipo']);

        $cte = Cte::find($resultado['id']);
        $this->assertNotNull($cte);
        $this->assertEquals('35261005990385000103570010000001231482159372', $cte->chave_acesso);
        $this->assertEquals('123', $cte->numero);
        $this->assertEquals(1500.00, $cte->valor_frete);
        $this->assertEquals('INDUSTRIA REMETENTE S/A', $cte->remetente_nome);
        $this->assertEquals('COMERCIAL DESTINATARIA LTDA', $cte->destinatario_nome);

        // Testa extração de chave de NF-e
        $conciliacao = new ConciliacaoService();
        $chaves = $conciliacao->extrairChavesNfeDoXml($xmlComentario);
        $this->assertContains('35261074660289000101550010000045671318640529', $chaves);
    }

    public function test_extrai_xml_de_email_gmail_com_corpo_html_e_entidades(): void
    {
        $mimeGmail = "MIME-Version: 1.0\r\n"
            . "Date: Sat, 3 Oct 2026 02:01:31 -0300\r\n"
            . "Subject: cte\r\n"
            . "From: luizbisselli@gmail.com\r\n"
            . "Content-Type: multipart/alternative; boundary=\"000000000000fc5d4c060f1b5b2e\"\r\n\r\n"
            . "--000000000000fc5d4c060f1b5b2e\r\n"
            . "Content-Type: text/plain; charset=\"UTF-8\"\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . "<CTe xmlns=3D\"http://www.portalfiscal.inf.br/cte\"><infCte Id=3D\"CTe35261005990385000103570010000001231482159372\" versao=3D\"4.00\"><ide><nCT>123</nCT></ide></infCte></CTe>\r\n\r\n"
            . "--000000000000fc5d4c060f1b5b2e\r\n"
            . "Content-Type: text/html; charset=\"UTF-8\"\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . "<div dir=\"ltr\">&lt;CTe xmlns=&quot;http://www.portalfiscal.inf.br/cte&quot;&gt;&lt;infCte Id=&quot;CTe35261005990385000103570010000001231482159372&quot; versao=&quot;4.00&quot;&gt;&lt;ide&gt;&lt;nCT&gt;123&lt;/nCT&gt;&lt;/ide&gt;&lt;/infCte&gt;&lt;/CTe&gt;</div>\r\n"
            . "--000000000000fc5d4c060f1b5b2e--";

        $xmls = MimeMailParser::extrairXmls($mimeGmail);
        $this->assertNotEmpty($xmls);
        $this->assertStringContainsString('CTe35261005990385000103570010000001231482159372', $xmls[0]);

        $resultado = $this->service->processarXml($xmls[0]);
        $this->assertTrue($resultado['ok'], $resultado['erro'] ?? '');
    }
}
