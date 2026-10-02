@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">Pendências</h1>
<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">Entregas sem veículo</h2>
        @forelse($semVeiculo as $e)
            <div class="border-b py-2 text-sm">
                CT-e {{ $e->cte?->numero }} — {{ $e->cte?->destinatario_nome }} · {{ $e->cidade_entrega }}/{{ $e->uf_entrega }}
                <a href="{{ route('admin.documentos.ctes') }}" class="text-blue-700 hover:underline ml-2">atribuir na tela de CT-e</a>
            </div>
        @empty
            <p class="text-sm text-gray-500">Nenhuma pendência. 🎉</p>
        @endforelse
    </div>
    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">Erros de ingestão de e-mail</h2>
        @forelse($errosEmail as $log)
            <div class="border-b py-2 text-sm">
                <b>{{ $log->assunto }}</b> · {{ $log->created_at->format('d/m H:i') }}<br>
                <span class="text-red-600">{{ $log->detalhes }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-500">Nenhum erro.</p>
        @endforelse
    </div>
</div>
@endsection
