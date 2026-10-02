@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1 class="text-2xl font-bold mb-4">Dashboard</h1>
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded shadow p-4"><div class="text-3xl font-bold">{{ $entregasHoje }}</div><div class="text-sm text-gray-500">Entregas hoje</div></div>
    <div class="bg-white rounded shadow p-4"><div class="text-3xl font-bold">{{ $entregesAbertas }}</div><div class="text-sm text-gray-500">Em aberto</div></div>
    <div class="bg-white rounded shadow p-4"><div class="text-3xl font-bold">{{ $entreguesHoje }}</div><div class="text-sm text-gray-500">Entregues hoje</div></div>
    <div class="bg-white rounded shadow p-4"><div class="text-3xl font-bold">{{ $nfesTotal }}</div><div class="text-sm text-gray-500">NF-e</div></div>
    <div class="bg-white rounded shadow p-4"><div class="text-3xl font-bold">{{ $ctesTotal }}</div><div class="text-sm text-gray-500">CT-e</div></div>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">⚠️ Entrega sem veículo (placa não veio no XML)</h2>
        @forelse($docsSemVeiculo as $e)
            <div class="border-b py-2 text-sm">CT-e {{ $e->cte?->numero }} — {{ $e->cidade_entrega }}/{{ $e->uf_entrega }} · {{ $e->status }}</div>
        @empty
            <p class="text-sm text-gray-500">Nenhuma pendência. 🎉</p>
        @endforelse
    </div>
    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">📧 Erros de ingestão por e-mail</h2>
        @forelse($pendenciasEmail as $log)
            <div class="border-b py-2 text-sm">
                <b>{{ $log->assunto }}</b> ({{ $log->remetente }})<br>
                <span class="text-red-600">{{ $log->detalhes }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-500">Nenhum erro registrado.</p>
        @endforelse
    </div>
</div>
@endsection
