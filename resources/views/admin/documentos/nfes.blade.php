@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">📑 NF-e recebidas</h1>
    <form style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <input name="q" value="{{ request('q') }}" placeholder="Chave, número, destinatário..."
               class="form-input" style="width:auto;min-width:0;flex:1;max-width:20rem">
        <button class="btn btn-dark btn-sm">Buscar</button>
    </form>
</div>
<div class="table-wrap">
<table class="resp-table">
    <thead><tr>
        <th>Nº</th><th>Emissão</th><th>Destinatário</th><th>Cidade/UF</th>
        <th>Valor (R$)</th><th>Volumes</th><th>Peso (kg)</th><th>Status</th><th>Visualizar</th>
    </tr></thead>
    <tbody>
    @foreach($nfes as $nfe)
        <tr style="{{ $nfe->cancelada ? 'opacity:.6;background:#fef2f2;' : '' }}">
            <td>
                <a href="{{ route('admin.documentos.danfe', $nfe) }}" style="font-weight:700; color:var(--c-primary); text-decoration:none;" title="Abrir DANFE">
                    {{ $nfe->numero }}
                </a>
            </td>
            <td>{{ $nfe->emissao?->format('d/m/Y') }}</td>
            <td>{{ $nfe->destinatario_nome }}</td>
            <td>{{ $nfe->destinatario_cidade }}/{{ $nfe->destinatario_uf }}</td>
            <td style="font-weight:600;">R$ {{ number_format((float) $nfe->valor_total, 2, ',', '.') }}</td>
            <td>{{ $nfe->volumes ?? '—' }}</td>
            <td>{{ $nfe->peso_bruto !== null ? number_format((float) $nfe->peso_bruto, 3, ',', '.') : '—' }}</td>
            <td>
                @if($nfe->cancelada)
                    <span class="badge badge-danger">Cancelada</span>
                @else
                    <span class="badge badge-success">Autorizada</span>
                @endif
            </td>
            <td>
                <div style="display:flex; gap:0.35rem; align-items:center;">
                    <a href="{{ route('admin.documentos.danfe', $nfe) }}" class="btn btn-sm" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:0.75rem; padding:0.25rem 0.5rem; text-decoration:none;" title="Ver DANFE">
                        👁️ DANFE
                    </a>
                    <a href="{{ route('admin.documentos.xml', ['tipo' => 'nfe', 'id' => $nfe->id]) }}" target="_blank" class="btn btn-sm" style="background:#f8fafc; color:#475569; border:1px solid #cbd5e1; font-size:0.75rem; padding:0.25rem 0.5rem; text-decoration:none;" title="Ver XML">
                        XML
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">{{ $nfes->links() }}</div>
@endsection
