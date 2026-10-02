@extends('layouts.app')
@section('content')
<div class="flex flex-wrap justify-between items-center gap-2 mb-4">
    <h1 class="text-2xl font-bold">CT-e recebidos</h1>
    <form class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Chave, número, placa, destinatário..." class="border rounded p-2 text-sm w-72">
        <button class="bg-slate-800 text-white px-4 rounded">Buscar</button>
    </form>
</div>
@php $veiculos = \App\Models\Veiculo::orderBy('placa')->get(); @endphp
<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50">
        <th class="p-3">Nº</th><th class="p-3">Emissão</th><th class="p-3">Destinatário</th><th class="p-3">Cidade/UF</th>
        <th class="p-3">Frete (R$)</th><th class="p-3">Placa XML</th><th class="p-3">NF-e vinculadas</th><th class="p-3">Entrega</th><th class="p-3">XML</th>
    </tr></thead>
    <tbody>
    @foreach($ctes as $cte)
        <tr class="border-b align-top">
            <td class="p-3">{{ $cte->numero }}</td>
            <td class="p-3">{{ $cte->emissao?->format('d/m/Y') }}</td>
            <td class="p-3">{{ $cte->destinatario_nome }}</td>
            <td class="p-3">{{ $cte->destinatario_cidade }}/{{ $cte->destinatario_uf }}</td>
            <td class="p-3">{{ number_format((float) $cte->valor_frete, 2, ',', '.') }}</td>
            <td class="p-3 font-mono">{{ $cte->placa_informada ?? '—' }}</td>
            <td class="p-3">{{ $cte->nfes->count() }}/{{ count(app(\App\Services\ConciliacaoService::class)->extrairChavesNfeDoXml((string) $cte->xml_original)) }}</td>
            <td class="p-3">
                @php $entrega = $cte->entregas()->latest()->first(); @endphp
                @if($entrega && $entrega->veiculo_id)
                    {{ strtoupper($entrega->veiculo->placa) }} · {{ $entrega->statusLabel() }}
                @else
                    <form method="POST" action="{{ route('admin.documentos.atribuirVeiculo', $cte) }}" class="flex gap-1">
                        @csrf
                        <select name="veiculo_id" class="border rounded p-1 text-xs">
                            <option value="">Atribuir veículo…</option>
                            @foreach($veiculos as $v)
                                <option value="{{ $v->id }}">{{ $v->placa }}</option>
                            @endforeach
                        </select>
                        <button class="bg-amber-600 text-white px-2 rounded text-xs">OK</button>
                    </form>
                @endif
            </td>
            <td class="p-3"><a target="_blank" href="{{ route('admin.documentos.xml', ['tipo' => 'cte', 'id' => $cte->id]) }}" class="text-blue-700 hover:underline">abrir</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $ctes->links() }}</div>
@endsection
