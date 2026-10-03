@extends('layouts.app')
@section('title', 'Minhas entregas')
@section('content')
<div class="page-header">
    <h1 class="page-title">📦 Minhas entregas</h1>
</div>

<h2 style="font-weight:600;color:var(--c-text);margin-bottom:.75rem;font-size:1rem">Em andamento</h2>
@forelse($abertas as $e)
    <a href="{{ route('motorista.entregas.show', $e) }}"
       class="entrega-card {{ $e->status === 'em_transito' ? 'status-transit' : 'status-pending' }}">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
            <span style="font-weight:700;font-size:.9375rem">{{ $e->cte?->destinatario_nome ?? 'Cliente #' . $e->cliente_id }}</span>
            <span class="badge {{ $e->status === 'em_transito' ? 'badge-blue' : 'badge-yellow' }}">{{ $e->statusLabel() }}</span>
        </div>
        <div style="font-size:.875rem;color:var(--c-text-muted);margin-top:.375rem">
            📍 {{ $e->endereco_entrega ?: ($e->cidade_entrega ?? '') }} {{ $e->uf_entrega ? '/'.$e->uf_entrega : '' }}
        </div>
        <div style="font-size:.75rem;color:var(--c-text-light);margin-top:.375rem">
            CT-e {{ $e->cte?->numero }} · NF-e: {{ $e->cte?->nfes->pluck('numero')->join(', ') ?: '—' }} · 🚛 {{ strtoupper((string) $e->veiculo?->placa) }}
        </div>
    </a>
@empty
    <div class="card" style="margin-bottom:1rem">
        <div class="card-body" style="text-align:center;padding:2rem">
            <div style="font-size:2rem;margin-bottom:.5rem">🎉</div>
            <p style="color:var(--c-text-muted);font-size:.875rem">Nenhuma entrega em andamento.</p>
        </div>
    </div>
@endforelse

<h2 style="font-weight:600;color:var(--c-text);margin-top:1.5rem;margin-bottom:.75rem;font-size:1rem">Concluídas</h2>
@forelse($concluidas as $e)
    <a href="{{ route('motorista.entregas.show', $e) }}"
       class="entrega-card {{ $e->status === 'entregue' ? 'status-done' : 'status-issue' }}" style="opacity:.8">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
            <span style="font-weight:600;font-size:.9375rem">{{ $e->cte?->destinatario_nome ?? 'Cliente' }}</span>
            <span class="badge {{ $e->status === 'entregue' ? 'badge-green' : 'badge-red' }}">{{ $e->statusLabel() }}</span>
        </div>
        <div style="font-size:.75rem;color:var(--c-text-light);margin-top:.25rem">{{ $e->entregue_em?->format('d/m/Y H:i') ?? $e->created_at->format('d/m/Y') }}</div>
    </a>
@empty
    <p style="color:var(--c-text-muted);font-size:.875rem">Nenhuma entrega concluída ainda.</p>
@endforelse
@endsection
