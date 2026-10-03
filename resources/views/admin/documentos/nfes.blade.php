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
        <th>Valor (R$)</th><th>Volumes</th><th>Peso (kg)</th><th>Cancelada</th><th>XML</th>
    </tr></thead>
    <tbody>
    @foreach($nfes as $nfe)
        <tr style="{{ $nfe->cancelada ? 'opacity:.5;text-decoration:line-through' : '' }}">
            <td style="font-weight:600">{{ $nfe->numero }}</td>
            <td>{{ $nfe->emissao?->format('d/m/Y') }}</td>
            <td>{{ $nfe->destinatario_nome }}</td>
            <td>{{ $nfe->destinatario_cidade }}/{{ $nfe->destinatario_uf }}</td>
            <td>{{ number_format((float) $nfe->valor_total, 2, ',', '.') }}</td>
            <td>{{ $nfe->volumes }}</td>
            <td>{{ $nfe->peso_bruto !== null ? number_format((float) $nfe->peso_bruto, 3, ',', '.') : '' }}</td>
            <td>{{ $nfe->cancelada ? 'Sim' : '' }}</td>
            <td><a href="{{ route('admin.documentos.xml', ['tipo' => 'nfe', 'id' => $nfe->id]) }}" target="_blank" style="color:var(--c-primary);font-weight:500;font-size:.8125rem">abrir</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">{{ $nfes->links() }}</div>
@endsection
