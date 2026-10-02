@extends('layouts.app')
@section('content')
<div class="flex flex-wrap justify-between items-center gap-2 mb-4">
    <h1 class="text-2xl font-bold">NF-e recebidas</h1>
    <form class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Chave, número, destinatário..." class="border rounded p-2 text-sm w-72">
        <button class="bg-slate-800 text-white px-4 rounded">Buscar</button>
    </form>
</div>
<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50">
        <th class="p-3">Nº</th><th class="p-3">Emissão</th><th class="p-3">Destinatário</th><th class="p-3">Cidade/UF</th>
        <th class="p-3">Valor (R$)</th><th class="p-3">Volumes</th><th class="p-3">Peso (kg)</th><th class="p-3">Cancelada</th><th class="p-3">XML</th>
    </tr></thead>
    <tbody>
    @foreach($nfes as $nfe)
        <tr class="border-b {{ $nfe->cancelada ? 'opacity-50 line-through' : '' }}">
            <td class="p-3">{{ $nfe->numero }}</td>
            <td class="p-3">{{ $nfe->emissao?->format('d/m/Y') }}</td>
            <td class="p-3">{{ $nfe->destinatario_nome }}</td>
            <td class="p-3">{{ $nfe->destinatario_cidade }}/{{ $nfe->destinatario_uf }}</td>
            <td class="p-3">{{ number_format((float) $nfe->valor_total, 2, ',', '.') }}</td>
            <td class="p-3">{{ $nfe->volumes }}</td>
            <td class="p-3">{{ $nfe->peso_bruto !== null ? number_format((float) $nfe->peso_bruto, 3, ',', '.') : '' }}</td>
            <td class="p-3">{{ $nfe->cancelada ? 'Sim' : '' }}</td>
            <td class="p-3"><a target="_blank" href="{{ route('admin.documentos.xml', ['tipo' => 'nfe', 'id' => $nfe->id]) }}" class="text-blue-700 hover:underline">abrir</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $nfes->links() }}</div>
@endsection
