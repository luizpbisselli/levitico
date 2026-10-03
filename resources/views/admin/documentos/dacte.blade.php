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
    .dacte-container {
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
    .dacte-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        border-left: 1px solid #000;
        border-top: 1px solid #000;
        margin-bottom: 0.5rem;
    }
    .dacte-cell {
        border-right: 1px solid #000;
        border-bottom: 1px solid #000;
        padding: 4px 6px;
        font-size: 0.72rem;
        line-height: 1.25;
        min-height: 32px;
    }
    .dacte-cell .label {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #4b5563;
        display: block;
        margin-bottom: 2px;
    }
    .dacte-cell .val {
        font-weight: 600;
        color: #000;
        word-break: break-word;
    }
    .dacte-cell.header-title {
        text-align: center;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .dacte-barcode {
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
        .dacte-container { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
        .page-wrap { padding: 0 !important; margin: 0 !important; }
        #tabXml { display: none !important; }
        #tabDacte { display: block !important; }
    }
</style>

<div class="doc-actions-bar">
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <a href="{{ route('admin.documentos.ctes') }}" class="btn btn-outline btn-sm">⬅️ Voltar aos CT-e</a>
        <div style="display:inline-flex; gap:0.25rem; background:#f1f5f9; padding:3px; border-radius:8px;">
            <button type="button" class="tab-btn active" id="btnTabDacte" onclick="alternarAba('dacte')">👁️ Visualização DACTE</button>
            <button type="button" class="tab-btn" id="btnTabXml" onclick="alternarAba('xml')">📄 XML Bruto</button>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <button type="button" onclick="window.print()" class="btn btn-dark btn-sm">🖨️ Imprimir DACTE</button>
        <a href="{{ route('admin.documentos.xml', ['tipo' => 'cte', 'id' => $cte->id]) }}" target="_blank" class="btn btn-outline btn-sm">⬇️ Baixar XML</a>
    </div>
</div>

{{-- ABA DACTE --}}
<div id="tabDacte" class="dacte-container">
    {{-- CABEÇALHO DACTE --}}
    <div class="dacte-grid">
        <div class="dacte-cell header-title" style="grid-column: span 4;">
            <div style="font-size:1.15rem; font-weight:800; letter-spacing:0.5px;">DACTE</div>
            <div style="font-size:0.65rem; color:#475569; margin-top:2px;">Documento Auxiliar do Conhecimento de Transporte Eletrônico</div>
            <div style="font-weight:700; font-size:0.75rem; margin-top:4px;">MODAL RODOVIÁRIO</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 3; text-align:center; display:flex; flex-direction:column; justify-content:center;">
            <div class="label">MODELO</div>
            <div class="val" style="font-size:0.9rem;">{{ $dacte['modelo'] }}</div>
            <div style="display:flex; justify-content:space-around; margin-top:4px;">
                <div><span class="label">SÉRIE</span><span class="val">{{ $dacte['serie'] }}</span></div>
                <div><span class="label">NÚMERO</span><span class="val">{{ $dacte['numero'] }}</span></div>
            </div>
        </div>
        <div class="dacte-cell" style="grid-column: span 5; display:flex; flex-direction:column; justify-content:center;">
            <div class="label">CHAVE DE ACESSO</div>
            <div class="dacte-barcode">{{ $dacte['chave_formatada'] }}</div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                <span style="font-size:0.6rem; color:#64748b;">Consulte em www.cte.fazenda.gov.br</span>
                <button type="button" onclick="navigator.clipboard.writeText('{{ $dacte['chave_acesso'] }}'); alert('Chave copiada com sucesso!');"
                        style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; font-size:0.65rem; padding:2px 6px; cursor:pointer;">
                    📋 Copiar
                </button>
            </div>
        </div>
    </div>

    {{-- DADOS DO EMITENTE & PROTOCOLO --}}
    <div class="dacte-grid">
        <div class="dacte-cell" style="grid-column: span 7;">
            <div class="label">EMITENTE (TRANSPORTADOR)</div>
            <div class="val" style="font-size:0.85rem; font-weight:800;">{{ $dacte['emitente']['nome'] }}</div>
            @if($dacte['emitente']['fantasia'])
                <div style="font-size:0.7rem; color:#475569;">{{ $dacte['emitente']['fantasia'] }}</div>
            @endif
            <div style="font-size:0.7rem; margin-top:2px;">
                {{ $dacte['emitente']['logradouro'] }} {{ $dacte['emitente']['numero'] }} - {{ $dacte['emitente']['bairro'] }} · {{ $dacte['emitente']['municipio'] }}/{{ $dacte['emitente']['uf'] }} · CEP: {{ $dacte['emitente']['cep'] }}
            </div>
            <div style="font-size:0.7rem; margin-top:2px; display:flex; gap:1rem;">
                <span><b>CNPJ:</b> {{ $dacte['emitente']['cnpj'] }}</span>
                <span><b>IE:</b> {{ $dacte['emitente']['ie'] ?: '—' }}</span>
                @if($dacte['emitente']['telefone']) <span><b>Tel:</b> {{ $dacte['emitente']['telefone'] }}</span> @endif
            </div>
        </div>
        <div class="dacte-cell" style="grid-column: span 5; display:flex; flex-direction:column; justify-content:space-around;">
            <div>
                <div class="label">NATUREZA DA OPERAÇÃO</div>
                <div class="val">{{ $dacte['natureza_operacao'] }} (CFOP: {{ $dacte['cfop'] ?: '—' }})</div>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <div>
                    <div class="label">PROTOCOLO DE AUTORIZAÇÃO</div>
                    <div class="val">{{ $dacte['protocolo'] ?: 'Autorizado em contingência / homologação' }}</div>
                </div>
                <div>
                    <div class="label">DATA/HORA EMISSÃO</div>
                    <div class="val">{{ $dacte['emissao_formatada'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- INÍCIO E FIM DA PRESTAÇÃO & TOMADOR --}}
    <div class="section-title">PERCURSO DO TRANSPORTE & TOMADOR DO SERVIÇO</div>
    <div class="dacte-grid">
        <div class="dacte-cell" style="grid-column: span 3;">
            <div class="label">INÍCIO DA PRESTAÇÃO (ORIGEM)</div>
            <div class="val">{{ $dacte['inicio_prestacao'] ?: ($dacte['emitente']['municipio'] . '/' . $dacte['emitente']['uf']) }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 3;">
            <div class="label">FIM DA PRESTAÇÃO (DESTINO)</div>
            <div class="val">{{ $dacte['fim_prestacao'] ?: ($dacte['destinatario']['municipio'] . '/' . $dacte['destinatario']['uf']) }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 6;">
            <div class="label">TOMADOR DO SERVIÇO</div>
            <div class="val">{{ $dacte['tomador']['nome'] }}</div>
        </div>
    </div>

    {{-- REMETENTE E DESTINATÁRIO --}}
    <div class="section-title">PARTES DA OPERAÇÃO</div>
    <div class="dacte-grid">
        <div class="dacte-cell" style="grid-column: span 6;">
            <div class="label">REMETENTE</div>
            <div class="val" style="font-weight:700;">{{ $dacte['remetente']['nome'] }}</div>
            <div style="font-size:0.7rem;">{{ $dacte['remetente']['endereco'] ?: 'Endereço não informado' }} · {{ $dacte['remetente']['municipio'] }}/{{ $dacte['remetente']['uf'] }}</div>
            <div style="font-size:0.7rem; margin-top:2px;"><b>CNPJ/CPF:</b> {{ $dacte['remetente']['cnpj'] ?: '—' }} | <b>IE:</b> {{ $dacte['remetente']['ie'] ?: '—' }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 6;">
            <div class="label">DESTINATÁRIO</div>
            <div class="val" style="font-weight:700;">{{ $dacte['destinatario']['nome'] }}</div>
            <div style="font-size:0.7rem;">{{ $dacte['destinatario']['endereco'] ?: 'Endereço não informado' }} · {{ $dacte['destinatario']['municipio'] }}/{{ $dacte['destinatario']['uf'] }}</div>
            <div style="font-size:0.7rem; margin-top:2px;"><b>CNPJ/CPF:</b> {{ $dacte['destinatario']['cnpj'] ?: '—' }} | <b>IE:</b> {{ $dacte['destinatario']['ie'] ?: '—' }}</div>
        </div>
    </div>

    {{-- VALORES DA PRESTAÇÃO E IMPOSTOS --}}
    <div class="section-title">VALORES DO SERVIÇO & TRIBUTOS</div>
    <div class="dacte-grid">
        <div class="dacte-cell" style="grid-column: span 3; background:#f0fdf4;">
            <div class="label" style="color:#166534;">VALOR TOTAL DA PRESTAÇÃO</div>
            <div class="val" style="font-size:1.05rem; font-weight:800; color:#15803d;">R$ {{ number_format($dacte['valor_frete'], 2, ',', '.') }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 3; background:#f0fdf4;">
            <div class="label" style="color:#166534;">VALOR A RECEBER</div>
            <div class="val" style="font-size:1.05rem; font-weight:800; color:#15803d;">R$ {{ number_format($dacte['valor_receber'], 2, ',', '.') }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 2;">
            <div class="label">CST ICMS</div>
            <div class="val">{{ $dacte['tributos']['cst'] ?: '00' }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 2;">
            <div class="label">BASE DE CÁLCULO</div>
            <div class="val">R$ {{ number_format($dacte['tributos']['base_icms'], 2, ',', '.') }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 2;">
            <div class="label">VALOR DO ICMS</div>
            <div class="val">R$ {{ number_format($dacte['tributos']['valor_icms'], 2, ',', '.') }} ({{ number_format($dacte['tributos']['aliq_icms'], 2, ',', '.') }}%)</div>
        </div>
    </div>

    @if(!empty($dacte['componentes_frete']))
    <div class="dacte-grid" style="margin-top:-0.5rem;">
        <div class="dacte-cell" style="grid-column: span 12; background:#fafafa;">
            <div class="label">COMPONENTES DO VALOR DA PRESTAÇÃO</div>
            <div style="display:flex; gap:1.5rem; flex-wrap:wrap; font-size:0.75rem;">
                @foreach($dacte['componentes_frete'] as $comp)
                    <span><b>{{ $comp['nome'] }}:</b> R$ {{ number_format($comp['valor'], 2, ',', '.') }}</span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- CARGA E MODAL RODOVIÁRIO --}}
    <div class="section-title">INFORMAÇÕES DA CARGA & VEÍCULO</div>
    <div class="dacte-grid">
        <div class="dacte-cell" style="grid-column: span 3;">
            <div class="label">PRODUTO PREDOMINANTE</div>
            <div class="val">{{ $dacte['carga']['produto_pred'] }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 3;">
            <div class="label">VALOR DA CARGA (DECLARADO)</div>
            <div class="val">R$ {{ number_format($dacte['carga']['valor'], 2, ',', '.') }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 2;">
            <div class="label">PESO BRUTO (KG)</div>
            <div class="val">{{ $dacte['carga']['peso_bruto'] !== null ? number_format($dacte['carga']['peso_bruto'], 3, ',', '.') : '—' }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 2;">
            <div class="label">VOLUMES</div>
            <div class="val">{{ $dacte['carga']['volumes'] ?? '—' }}</div>
        </div>
        <div class="dacte-cell" style="grid-column: span 2;">
            <div class="label">PLACA VEÍCULO / RNTRC</div>
            <div class="val font-mono">{{ $dacte['rodoviario']['placa'] ?: 'Não informada' }} {{ $dacte['rodoviario']['rntrc'] ? ('(' . $dacte['rodoviario']['rntrc'] . ')') : '' }}</div>
        </div>
    </div>

    {{-- NF-E VINCULADAS --}}
    <div class="section-title">DOCUMENTOS FISCAIS VINCULADOS (NF-E)</div>
    <div class="dacte-grid">
        <div class="dacte-cell" style="grid-column: span 12; padding:0;">
            @if(!empty($dacte['nfes_vinculadas']))
                <table style="width:100%; border-collapse:collapse; font-size:0.75rem;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #cbd5e1; text-align:left;">
                            <th style="padding:6px 8px;">Chave de Acesso NF-e</th>
                            <th style="padding:6px 8px;">Número</th>
                            <th style="padding:6px 8px;">Destinatário</th>
                            <th style="padding:6px 8px;">Valor</th>
                            <th style="padding:6px 8px;">Status no Sistema</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dacte['nfes_vinculadas'] as $nfeDoc)
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:6px 8px; font-family:monospace;">{{ $nfeDoc['chave_formatada'] }}</td>
                                <td style="padding:6px 8px; font-weight:600;">{{ $nfeDoc['numero'] ?: '—' }}</td>
                                <td style="padding:6px 8px;">{{ $nfeDoc['destinatario'] ?: '—' }}</td>
                                <td style="padding:6px 8px;">{{ $nfeDoc['valor'] ? ('R$ ' . number_format($nfeDoc['valor'], 2, ',', '.')) : '—' }}</td>
                                <td style="padding:6px 8px;">
                                    @if($nfeDoc['recebida'])
                                        <a href="{{ route('admin.documentos.danfe', $nfeDoc['nfe_id']) }}" target="_blank"
                                           class="badge badge-success" style="text-decoration:none;">✓ Recebida (Ver DANFE)</a>
                                    @else
                                        <span class="badge badge-warning">Aguardando e-mail</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="padding:8px 12px; font-size:0.75rem; color:#64748b;">Nenhuma NF-e referenciada no XML deste CT-e.</div>
            @endif
        </div>
    </div>

    @if($dacte['observacoes'])
    <div class="section-title">OBSERVAÇÕES GERAIS</div>
    <div class="dacte-grid">
        <div class="dacte-cell" style="grid-column: span 12; font-size:0.75rem; color:#334155;">
            {{ $dacte['observacoes'] }}
        </div>
    </div>
    @endif
</div>

{{-- ABA XML BRUTO --}}
<div id="tabXml" class="dacte-container" style="display:none;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
        <h3 style="font-weight:700; font-size:1rem; margin:0;">Estrutura do XML Original (Guarda Fiscal)</h3>
        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('rawXmlCode').innerText); alert('XML copiado!');" class="btn btn-outline btn-sm">📋 Copiar Conteúdo</button>
    </div>
    <pre id="rawXmlCode" style="background:#0f172a; color:#e2e8f0; padding:1.25rem; border-radius:6px; font-size:0.8rem; overflow:auto; max-height:600px; line-height:1.5;">{{ $cte->xml_original }}</pre>
</div>

<script>
function alternarAba(tipo) {
    const tabDacte = document.getElementById('tabDacte');
    const tabXml = document.getElementById('tabXml');
    const btnDacte = document.getElementById('btnTabDacte');
    const btnXml = document.getElementById('btnTabXml');

    if (tipo === 'dacte') {
        tabDacte.style.display = 'block';
        tabXml.style.display = 'none';
        btnDacte.classList.add('active');
        btnXml.classList.remove('active');
    } else {
        tabDacte.style.display = 'none';
        tabXml.style.display = 'block';
        btnDacte.classList.remove('active');
        btnXml.classList.add('active');
    }
}
</script>
@endsection
