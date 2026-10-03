@extends('layouts.app')
@section('content')
<style>
    .doc-actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .danfe-container {
        background: #fff;
        color: #111827;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 1.5rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        max-width: 960px;
        margin: 0 auto;
    }
    .danfe-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        border-left: 1px solid #000;
        border-top: 1px solid #000;
        margin-bottom: 0.5rem;
    }
    .danfe-cell {
        border-right: 1px solid #000;
        border-bottom: 1px solid #000;
        padding: 4px 6px;
        font-size: 0.72rem;
        line-height: 1.25;
        min-height: 32px;
    }
    .danfe-cell .label {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #4b5563;
        display: block;
        margin-bottom: 2px;
    }
    .danfe-cell .val {
        font-weight: 600;
        color: #000;
        word-break: break-word;
    }
    .danfe-cell.header-title {
        text-align: center;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .danfe-barcode {
        letter-spacing: 2px;
        font-family: "Courier New", Courier, monospace;
        font-size: 0.8rem;
        font-weight: bold;
        text-align: center;
        background: #f1f5f9;
        padding: 6px 4px;
        border-radius: 4px;
        border: 1px dashed #94a3b8;
    }
    .section-title {
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        background: #e2e8f0;
        color: #0f172a;
        padding: 3px 6px;
        border-left: 1px solid #000;
        border-right: 1px solid #000;
        border-top: 1px solid #000;
        margin-top: 0.5rem;
    }
    .tab-btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .2s;
    }
    .tab-btn.active {
        background: var(--c-primary, #2563eb);
        color: #fff;
    }
    .tab-btn:not(.active) {
        background: #f1f5f9;
        color: #475569;
    }
    @media print {
        body { background: #fff !important; color: #000 !important; }
        .nav-bar, .doc-actions-bar, .drawer, .drawer-overlay, .sync-badge, .nav-actions, footer { display: none !important; }
        .danfe-container { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
        .page-wrap { padding: 0 !important; margin: 0 !important; }
        #tabXml { display: none !important; }
        #tabDanfe { display: block !important; }
    }
</style>

<div class="doc-actions-bar">
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <a href="{{ route('admin.documentos.nfes') }}" class="btn btn-outline btn-sm">⬅️ Voltar às NF-e</a>
        <div style="display:inline-flex; gap:0.25rem; background:#f1f5f9; padding:3px; border-radius:8px;">
            <button type="button" class="tab-btn active" id="btnTabDanfe" onclick="alternarAba('danfe')">👁️ Visualização DANFE</button>
            <button type="button" class="tab-btn" id="btnTabXml" onclick="alternarAba('xml')">📄 XML Bruto</button>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <button type="button" onclick="window.print()" class="btn btn-dark btn-sm">🖨️ Imprimir DANFE</button>
        <a href="{{ route('admin.documentos.xml', ['tipo' => 'nfe', 'id' => $nfe->id]) }}" target="_blank" class="btn btn-outline btn-sm">⬇️ Baixar XML</a>
    </div>
</div>

{{-- BANNER CANCELAMENTO --}}
@if($danfe['cancelada'])
    <div style="background:#fee2e2; border:2px solid #ef4444; border-radius:8px; padding:0.75rem 1rem; color:#991b1b; font-weight:700; margin-bottom:1rem; text-align:center;">
        ⚠️ NOTA FISCAL CANCELADA JUNTO À SEFAZ
    </div>
@endif

{{-- ABA DANFE --}}
<div id="tabDanfe" class="danfe-container">
    {{-- CABEÇALHO DANFE --}}
    <div class="dacte-grid">
        <div class="danfe-cell header-title" style="grid-column: span 3;">
            <div style="font-size:1.15rem; font-weight:800; letter-spacing:0.5px;">DANFE</div>
            <div style="font-size:0.65rem; color:#475569; margin-top:2px;">Documento Auxiliar da Nota Fiscal Eletrônica</div>
            <div style="font-weight:700; font-size:0.75rem; margin-top:4px;">{{ $danfe['tipo_operacao'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 4; text-align:center; display:flex; flex-direction:column; justify-content:center;">
            <div class="label">EMITENTE</div>
            <div class="val" style="font-size:0.85rem; font-weight:800;">{{ $danfe['emitente']['nome'] }}</div>
            @if($danfe['emitente']['fantasia'])
                <div style="font-size:0.7rem; color:#475569;">{{ $danfe['emitente']['fantasia'] }}</div>
            @endif
            <div style="font-size:0.68rem; margin-top:2px;">
                {{ $danfe['emitente']['endereco'] ?: 'Endereço cadastrado' }}
            </div>
            <div style="font-size:0.68rem; margin-top:2px;">
                <b>CNPJ:</b> {{ $danfe['emitente']['cnpj'] }} | <b>IE:</b> {{ $danfe['emitente']['ie'] ?: '—' }}
            </div>
        </div>
        <div class="danfe-cell" style="grid-column: span 5; display:flex; flex-direction:column; justify-content:center;">
            <div class="label">CHAVE DE ACESSO</div>
            <div class="danfe-barcode">{{ $danfe['chave_formatada'] }}</div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                <span style="font-size:0.6rem; color:#64748b;">Consulte em www.nfe.fazenda.gov.br</span>
                <button type="button" onclick="navigator.clipboard.writeText('{{ $danfe['chave_acesso'] }}'); alert('Chave copiada com sucesso!');"
                        style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; font-size:0.65rem; padding:2px 6px; cursor:pointer;">
                    📋 Copiar
                </button>
            </div>
        </div>
    </div>

    {{-- DADOS DA NOTA & PROTOCOLO --}}
    <div class="danfe-grid">
        <div class="danfe-cell" style="grid-column: span 4;">
            <div class="label">NATUREZA DA OPERAÇÃO</div>
            <div class="val">{{ $danfe['natureza_operacao'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 4;">
            <div class="label">PROTOCOLO DE AUTORIZAÇÃO DE USO</div>
            <div class="val">{{ $danfe['protocolo'] ?: 'Autorizado em contingência / homologação' }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">Nº DA NOTA</div>
            <div class="val" style="font-size:0.85rem; font-weight:800;">{{ $danfe['numero'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">SÉRIE / DATA EMISSÃO</div>
            <div class="val">{{ $danfe['serie'] }} · {{ $danfe['emissao_formatada'] }}</div>
        </div>
    </div>

    {{-- DESTINATÁRIO / REMETENTE --}}
    <div class="section-title">DESTINATÁRIO / REMETENTE</div>
    <div class="danfe-grid">
        <div class="danfe-cell" style="grid-column: span 7;">
            <div class="label">NOME / RAZÃO SOCIAL</div>
            <div class="val" style="font-size:0.85rem; font-weight:700;">{{ $danfe['destinatario']['nome'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">CNPJ / CPF</div>
            <div class="val">{{ $danfe['destinatario']['cnpj'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">INSCRIÇÃO ESTADUAL</div>
            <div class="val">{{ $danfe['destinatario']['ie'] ?: 'ISENTO' }}</div>
        </div>
    </div>
    <div class="danfe-grid" style="margin-top:-0.5rem;">
        <div class="danfe-cell" style="grid-column: span 6;">
            <div class="label">ENDEREÇO</div>
            <div class="val">{{ $danfe['destinatario']['endereco'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">MUNICÍPIO</div>
            <div class="val">{{ $danfe['destinatario']['municipio'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 1;">
            <div class="label">UF</div>
            <div class="val">{{ $danfe['destinatario']['uf'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">FONE / CEP</div>
            <div class="val">{{ $danfe['destinatario']['telefone'] ?: '—' }} {{ $danfe['destinatario']['cep'] ? ('· ' . $danfe['destinatario']['cep']) : '' }}</div>
        </div>
    </div>

    {{-- CÁLCULO DO IMPOSTO --}}
    <div class="section-title">CÁLCULO DO IMPOSTO & TOTAIS</div>
    <div class="danfe-grid">
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">BASE DE CÁLCULO ICMS</div>
            <div class="val">R$ {{ number_format($danfe['totais']['base_icms'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">VALOR DO ICMS</div>
            <div class="val">R$ {{ number_format($danfe['totais']['valor_icms'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">BASE ICMS ST</div>
            <div class="val">R$ {{ number_format($danfe['totais']['base_icms_st'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">VALOR ICMS ST</div>
            <div class="val">R$ {{ number_format($danfe['totais']['valor_icms_st'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 4; background:#f0fdf4;">
            <div class="label" style="color:#166534;">VALOR TOTAL DOS PRODUTOS</div>
            <div class="val" style="font-weight:700; color:#15803d;">R$ {{ number_format($danfe['totais']['valor_produtos'], 2, ',', '.') }}</div>
        </div>
    </div>
    <div class="danfe-grid" style="margin-top:-0.5rem;">
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">VALOR DO FRETE</div>
            <div class="val">R$ {{ number_format($danfe['totais']['valor_frete'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">VALOR DO SEGURO</div>
            <div class="val">R$ {{ number_format($danfe['totais']['valor_seguro'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">DESCONTO</div>
            <div class="val">R$ {{ number_format($danfe['totais']['desconto'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">OUTRAS DESPESAS</div>
            <div class="val">R$ {{ number_format($danfe['totais']['outras_desp'], 2, ',', '.') }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 4; background:#ecfdf5;">
            <div class="label" style="color:#047857; font-weight:800;">VALOR TOTAL DA NOTA FISCAL</div>
            <div class="val" style="font-size:1.15rem; font-weight:900; color:#065f46;">R$ {{ number_format($danfe['totais']['valor_total'], 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- TRANSPORTADOR / VOLUMES --}}
    <div class="section-title">TRANSPORTADOR / VOLUMES TRANSPORTADOS</div>
    <div class="danfe-grid">
        <div class="danfe-cell" style="grid-column: span 4;">
            <div class="label">RAZÃO SOCIAL / TRANSPORTADORA</div>
            <div class="val">{{ $danfe['transporte']['transportadora'] ?: 'Não informada no XML' }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">FRETE POR CONTA</div>
            <div class="val">{{ $danfe['transporte']['modalidade_frete'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 2;">
            <div class="label">PLACA VEÍCULO</div>
            <div class="val font-mono">{{ $danfe['transporte']['placa'] ?: '—' }} {{ $danfe['transporte']['uf'] ? ('/ ' . $danfe['transporte']['uf']) : '' }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">CNPJ / CPF DO TRANSPORTADOR</div>
            <div class="val">{{ $danfe['transporte']['cnpj'] ?: '—' }}</div>
        </div>
    </div>
    <div class="danfe-grid" style="margin-top:-0.5rem;">
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">QUANTIDADE DE VOLUMES</div>
            <div class="val">{{ $danfe['transporte']['volumes'] ?? '—' }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">ESPÉCIE</div>
            <div class="val">{{ $danfe['transporte']['especie'] }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">PESO BRUTO (KG)</div>
            <div class="val">{{ $danfe['transporte']['peso_bruto'] !== null ? number_format($danfe['transporte']['peso_bruto'], 3, ',', '.') : '—' }}</div>
        </div>
        <div class="danfe-cell" style="grid-column: span 3;">
            <div class="label">PESO LÍQUIDO (KG)</div>
            <div class="val">{{ $danfe['transporte']['peso_liquido'] ? number_format($danfe['transporte']['peso_liquido'], 3, ',', '.') : '—' }}</div>
        </div>
    </div>

    {{-- DADOS DOS PRODUTOS / SERVIÇOS --}}
    <div class="section-title">DADOS DOS PRODUTOS / SERVIÇOS</div>
    <div class="danfe-grid">
        <div class="danfe-cell" style="grid-column: span 12; padding:0;">
            @if(!empty($danfe['itens']))
                <table style="width:100%; border-collapse:collapse; font-size:0.73rem;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #cbd5e1; text-align:left;">
                            <th style="padding:4px 6px; width:40px;">#</th>
                            <th style="padding:4px 6px; width:90px;">Cód. Produto</th>
                            <th style="padding:4px 6px;">Descrição do Produto / Serviço</th>
                            <th style="padding:4px 6px; width:70px;">NCM</th>
                            <th style="padding:4px 6px; width:50px;">CFOP</th>
                            <th style="padding:4px 6px; width:40px;">UN</th>
                            <th style="padding:4px 6px; width:60px; text-align:right;">Qtd.</th>
                            <th style="padding:4px 6px; width:80px; text-align:right;">Vlr. Unit.</th>
                            <th style="padding:4px 6px; width:90px; text-align:right;">Vlr. Total</th>
                            <th style="padding:4px 6px; width:50px; text-align:right;">ICMS %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($danfe['itens'] as $item)
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:4px 6px; color:#64748b;">{{ $item['nItem'] }}</td>
                                <td style="padding:4px 6px; font-family:monospace;">{{ $item['codigo'] }}</td>
                                <td style="padding:4px 6px; font-weight:600;">{{ $item['descricao'] }}</td>
                                <td style="padding:4px 6px;">{{ $item['ncm'] }}</td>
                                <td style="padding:4px 6px;">{{ $item['cfop'] }}</td>
                                <td style="padding:4px 6px;">{{ $item['unidade'] }}</td>
                                <td style="padding:4px 6px; text-align:right;">{{ number_format($item['quantidade'], 2, ',', '.') }}</td>
                                <td style="padding:4px 6px; text-align:right;">{{ number_format($item['valor_unit'], 2, ',', '.') }}</td>
                                <td style="padding:4px 6px; text-align:right; font-weight:700;">{{ number_format($item['valor_total'], 2, ',', '.') }}</td>
                                <td style="padding:4px 6px; text-align:right;">{{ $item['aliq_icms'] > 0 ? (number_format($item['aliq_icms'], 1, ',', '.') . '%') : '0%' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="padding:10px 12px; font-size:0.75rem; color:#64748b;">Itens detalhados não discriminados individualmente no XML.</div>
            @endif
        </div>
    </div>

    {{-- DADOS ADICIONAIS --}}
    @if($danfe['informacoes_adicionais'])
    <div class="section-title">INFORMAÇÕES COMPLEMENTARES / DADOS ADICIONAIS</div>
    <div class="danfe-grid">
        <div class="danfe-cell" style="grid-column: span 12; font-size:0.72rem; color:#334155; line-height:1.4;">
            {{ $danfe['informacoes_adicionais'] }}
        </div>
    </div>
    @endif
</div>

{{-- ABA XML BRUTO --}}
<div id="tabXml" class="danfe-container" style="display:none;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
        <h3 style="font-weight:700; font-size:1rem; margin:0;">Estrutura do XML Original (Guarda Fiscal)</h3>
        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('rawXmlCode').innerText); alert('XML copiado!');" class="btn btn-outline btn-sm">📋 Copiar Conteúdo</button>
    </div>
    <pre id="rawXmlCode" style="background:#0f172a; color:#e2e8f0; padding:1.25rem; border-radius:6px; font-size:0.8rem; overflow:auto; max-height:600px; line-height:1.5;">{{ $nfe->xml_original }}</pre>
</div>

<script>
function alternarAba(tipo) {
    const tabDanfe = document.getElementById('tabDanfe');
    const tabXml = document.getElementById('tabXml');
    const btnDanfe = document.getElementById('btnTabDanfe');
    const btnXml = document.getElementById('btnTabXml');

    if (tipo === 'danfe') {
        tabDanfe.style.display = 'block';
        tabXml.style.display = 'none';
        btnDanfe.classList.add('active');
        btnXml.classList.remove('active');
    } else {
        tabDanfe.style.display = 'none';
        tabXml.style.display = 'block';
        btnDanfe.classList.remove('active');
        btnXml.classList.add('active');
    }
}
</script>
@endsection
