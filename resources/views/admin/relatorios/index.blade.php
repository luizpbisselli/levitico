@extends('layouts.app')
@section('content')
<div class="page-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
        <h1 class="page-title">Relatório de Entregas</h1>
        <a href="{{ route('admin.relatorios', array_merge(request()->query(), ['exportar' => 'csv'])) }}" class="btn btn-success btn-sm">📥 Exportar CSV</a>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.relatorios') }}" class="card" style="width:100%;">
        <div class="card-body" style="padding:0.75rem 1rem;">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; align-items:flex-end;">
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:var(--text-muted); margin-bottom:0.25rem;">De</label>
                    <input type="date" name="de" value="{{ request('de') }}" class="form-input" style="padding:0.4rem 0.6rem; font-size:0.875rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:var(--text-muted); margin-bottom:0.25rem;">Até</label>
                    <input type="date" name="ate" value="{{ request('ate') }}" class="form-input" style="padding:0.4rem 0.6rem; font-size:0.875rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:var(--text-muted); margin-bottom:0.25rem;">Status</label>
                    <select name="status" class="form-select" style="padding:0.4rem 0.6rem; font-size:0.875rem;">
                        <option value="">Todos os status</option>
                        @foreach(\App\Models\Entrega::STATUS as $val => $rot)
                            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $rot }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Filtrar</button>
                    @if(request('de') || request('ate') || request('status'))
                        <a href="{{ route('admin.relatorios') }}" class="btn btn-secondary">Limpar</a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table class="resp-table">
        <thead>
            <tr>
                <th>CT-e</th>
                <th>Placa</th>
                <th>Motorista</th>
                <th>Cliente / Destinatário</th>
                <th>Cidade/UF</th>
                <th>Status</th>
                <th>Frete (R$)</th>
                <th>Canhoto</th>
                <th>Criada em</th>
                <th>Entregue em</th>
            </tr>
        </thead>
        <tbody>
        @forelse($entregas as $e)
            <tr>
                <td style="font-weight:600;">{{ $e->cte?->numero ?: '—' }}</td>
                <td><span class="badge badge-info">{{ strtoupper((string) $e->veiculo?->placa) ?: '—' }}</span></td>
                <td>{{ $e->motorista?->nome ?? '—' }}</td>
                <td>{{ $e->cliente?->nome ?? $e->cte?->destinatario_nome }}</td>
                <td>{{ $e->cidade_entrega }}/{{ $e->uf_entrega }}</td>
                <td>
                    <span class="badge {{ $e->status === 'entregue' ? 'badge-success' : ($e->status === 'ocorrencia' ? 'badge-danger' : ($e->status === 'em_transito' ? 'badge-info' : 'badge-warning')) }}">
                        {{ $e->statusLabel() }}
                    </span>
                </td>
                <td style="font-weight:600;">{{ $e->cte ? number_format((float) $e->cte->valor_frete, 2, ',', '.') : '—' }}</td>
                <td>
                    @if($e->temComprovante())
                        <a href="{{ route('admin.entregas.comprovante', $e) }}" target="_blank" class="btn btn-success btn-sm" style="padding:2px 8px; font-size:0.75rem;">📸 Ver</a>
                    @else
                        <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                    @endif
                </td>
                <td style="white-space:nowrap; font-size:0.8rem; color:var(--text-muted);">{{ $e->created_at->format('d/m/Y H:i') }}</td>
                <td style="white-space:nowrap; font-size:0.8rem; color:var(--text-muted);">{{ $e->entregue_em?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" style="text-align:center; padding:2rem; color:var(--text-muted);">
                    Nenhuma entrega encontrada para os filtros selecionados.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
