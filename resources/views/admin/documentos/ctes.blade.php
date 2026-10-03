@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">📄 CT-e recebidos</h1>
    <form style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <input name="q" value="{{ request('q') }}" placeholder="Chave, número, placa, destinatário..."
               class="form-input" style="width:auto;min-width:0;flex:1;max-width:20rem">
        <button class="btn btn-dark btn-sm">Buscar</button>
    </form>
</div>
@php $veiculos = \App\Models\Veiculo::orderBy('placa')->get(); @endphp
<div class="table-wrap">
<table class="resp-table">
    <thead><tr>
        <th>Nº</th><th>Emissão</th><th>Destinatário</th><th>Cidade/UF</th>
        <th>Frete (R$)</th><th>Placa XML</th><th>NF-e vinc.</th><th>Entrega</th><th>XML</th>
    </tr></thead>
    <tbody>
    @foreach($ctes as $cte)
        <tr>
            <td style="font-weight:600">{{ $cte->numero }}</td>
            <td>{{ $cte->emissao?->format('d/m/Y') }}</td>
            <td>{{ $cte->destinatario_nome }}</td>
            <td>{{ $cte->destinatario_cidade }}/{{ $cte->destinatario_uf }}</td>
            <td>{{ number_format((float) $cte->valor_frete, 2, ',', '.') }}</td>
            <td class="font-mono">{{ $cte->placa_informada ?? '—' }}</td>
            <td>{{ $cte->nfes->count() }}/{{ count(app(\App\Services\ConciliacaoService::class)->extrairChavesNfeDoXml((string) $cte->xml_original)) }}</td>
            <td>
                @php $entrega = $cte->entregas()->latest()->first(); @endphp
                @if($entrega && $entrega->veiculo_id)
                    <div style="font-size:.8125rem">{{ strtoupper($entrega->veiculo->placa) }} · <span class="badge badge-slate">{{ $entrega->statusLabel() }}</span></div>
                    @if($entrega->temComprovante())
                        <a href="{{ route('admin.entregas.comprovante', $entrega) }}" target="_blank"
                           class="btn btn-sm" style="margin-top:.375rem;background:#ecfdf5;color:#166534;border:1px solid #86efac;font-size:.75rem;padding:.25rem .5rem">📸 Canhoto</a>
                    @endif
                @else
                    <form method="POST" action="{{ route('admin.documentos.atribuirVeiculo', $cte) }}" style="display:flex;gap:.25rem;align-items:center">
                        @csrf
                        <select name="veiculo_id" class="form-select" style="font-size:.75rem;padding:.375rem .5rem;width:auto;min-width:8rem">
                            <option value="">Atribuir…</option>
                            @foreach($veiculos as $v)
                                <option value="{{ $v->id }}">{{ $v->placa }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm" style="background:#d97706;color:#fff;padding:.375rem .5rem">OK</button>
                    </form>
                @endif
            </td>
            <td><a href="{{ route('admin.documentos.xml', ['tipo' => 'cte', 'id' => $cte->id]) }}" target="_blank" style="color:var(--c-primary);font-weight:500;font-size:.8125rem">abrir</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">{{ $ctes->links() }}</div>
@endsection
