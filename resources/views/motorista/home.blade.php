@extends('layouts.app')
@section('title', 'Minhas entregas')
@section('content')
<h1 class="text-xl font-bold mb-4">Minhas entregas</h1>

<h2 class="font-semibold text-gray-700 mb-2">Em andamento</h2>
@forelse($abertas as $e)
    <a href="{{ route('motorista.entregas.show', $e) }}"
       class="block bg-white rounded-lg shadow p-4 mb-3 active:bg-gray-50">
        <div class="flex justify-between items-center">
            <span class="font-bold">{{ $e->cte?->destinatario_nome ?? 'Cliente #' . $e->cliente_id }}</span>
            <span class="text-xs px-2 py-1 rounded {{ $e->status === 'em_transito' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">{{ $e->statusLabel() }}</span>
        </div>
        <div class="text-sm text-gray-600 mt-1">📍 {{ $e->endereco_entrega ?: ($e->cidade_entrega ?? '') }} {{ $e->uf_entrega ? '/'.$e->uf_entrega : '' }}</div>
        <div class="text-xs text-gray-500 mt-1">CT-e {{ $e->cte?->numero }} · NF-e: {{ $e->cte?->nfes->pluck('numero')->join(', ') ?: '—' }} · 🚛 {{ strtoupper((string) $e->veiculo?->placa) }}</div>
    </a>
@empty
    <p class="text-gray-500 text-sm mb-4">Nenhuma entrega em andamento.</p>
@endforelse

<h2 class="font-semibold text-gray-700 mt-6 mb-2">Concluídas</h2>
@forelse($concluidas as $e)
    <a href="{{ route('motorista.entregas.show', $e) }}" class="block bg-white rounded-lg shadow p-4 mb-2 opacity-80">
        <div class="flex justify-between items-center">
            <span class="font-semibold">{{ $e->cte?->destinatario_nome ?? 'Cliente' }}</span>
            <span class="text-xs px-2 py-1 rounded {{ $e->status === 'entregue' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $e->statusLabel() }}</span>
        </div>
        <div class="text-xs text-gray-500">{{ $e->entregue_em?->format('d/m/Y H:i') ?? $e->created_at->format('d/m/Y') }}</div>
    </a>
@empty
    <p class="text-gray-500 text-sm">Nenhuma entrega concluída ainda.</p>
@endforelse
@endsection
