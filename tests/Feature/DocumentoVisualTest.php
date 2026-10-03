<?php

namespace Tests\Feature;

use App\Models\Cte;
use App\Models\Nfe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentoVisualTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin']);
    }

    public function test_admin_pode_visualizar_dacte_formatado_do_cte(): void
    {
        $cte = Cte::create([
            'chave_acesso'        => '35261005990385000103570010000001231482159372',
            'numero'              => '123',
            'serie'               => '1',
            'emissao'             => '2026-10-03',
            'tomador_nome'        => 'TRANSPORTADORA EXEMPLO LTDA',
            'remetente_nome'      => 'INDUSTRIA REMETENTE S/A',
            'destinatario_nome'   => 'COMERCIAL DESTINATARIA LTDA',
            'destinatario_cidade' => 'RIO DE JANEIRO',
            'destinatario_uf'     => 'RJ',
            'valor_frete'         => 1500.00,
            'placa_informada'     => 'ABC1234',
            'xml_original'        => '<CTe><infCte Id="CTe35261005990385000103570010000001231482159372"><ide><nCT>123</nCT></ide></infCte></CTe>',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.documentos.dacte', $cte));

        $response->assertStatus(200);
        $response->assertSee('DACTE');
        $response->assertSee('Documento Auxiliar do Conhecimento de Transporte Eletrônico');
        $response->assertSee('123');
        $response->assertSee('1.500,00');
        $response->assertSee('3526 1005 9903 8500 0103 5700 1000 0001 2314 8215 9372');
    }

    public function test_admin_pode_visualizar_danfe_formatado_da_nfe(): void
    {
        $nfe = Nfe::create([
            'chave_acesso'           => '35261074660289000101550010000045671318640529',
            'numero'                 => '4567',
            'serie'                  => '1',
            'emissao'                => '2026-10-03',
            'emitente_nome'          => 'EMPRESA FORNECEDORA LTDA',
            'emitente_cnpj'          => '74660289000101',
            'destinatario_nome'      => 'CLIENTE COMPRADOR SA',
            'destinatario_documento' => '56619784000195',
            'destinatario_cidade'    => 'RIO DE JANEIRO',
            'destinatario_uf'        => 'RJ',
            'valor_total'            => 2500.00,
            'volumes'                => 10,
            'peso_bruto'             => 350.50,
            'xml_original'           => '<NFe><infNFe Id="NFe35261074660289000101550010000045671318640529"><ide><nNF>4567</nNF></ide></infNFe></NFe>',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.documentos.danfe', $nfe));

        $response->assertStatus(200);
        $response->assertSee('DANFE');
        $response->assertSee('Documento Auxiliar da Nota Fiscal Eletrônica');
        $response->assertSee('4567');
        $response->assertSee('2.500,00');
    }
}
