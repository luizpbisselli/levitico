@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-header">
    <h1 class="page-title">📊 Dashboard</h1>
</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <div class="stat-card">
        <div class="stat-value">{{ $entregasHoje }}</div>
        <div class="stat-label">Entregas hoje</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $entregesAbertas }}</div>
        <div class="stat-label">Em aberto</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $entreguesHoje }}</div>
        <div class="stat-label">Entregues hoje</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $nfesTotal }}</div>
        <div class="stat-label">NF-e</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $ctesTotal }}</div>
        <div class="stat-label">CT-e</div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div class="card">
        <div class="card-body">
            <h2 style="font-weight:600;margin-bottom:.75rem;font-size:1rem">⚠️ Entrega sem veículo</h2>
            @forelse($docsSemVeiculo as $e)
                <div style="border-bottom:1px solid var(--c-border);padding:.625rem 0;font-size:.875rem">
                    <span style="font-weight:600">CT-e {{ $e->cte?->numero }}</span>
                    <span style="color:var(--c-text-muted)"> — {{ $e->cidade_entrega }}/{{ $e->uf_entrega }}</span>
                    <span class="badge badge-yellow" style="margin-left:.5rem">{{ $e->status }}</span>
                </div>
            @empty
                <p style="font-size:.875rem;color:var(--c-text-muted)">Nenhuma pendência. 🎉</p>
            @endforelse
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h2 style="font-weight:600;margin-bottom:.75rem;font-size:1rem">📧 Erros de ingestão por e-mail</h2>
            @forelse($pendenciasEmail as $log)
                <div style="border-bottom:1px solid var(--c-border);padding:.625rem 0;font-size:.875rem">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem">
                        <div style="font-weight:600">{{ $log->assunto ?: 'Sem assunto' }}</div>
                        <span style="font-size:.75rem;color:var(--c-text-muted);white-space:nowrap;font-variant-numeric:tabular-nums">
                            {{ $log->lido_em ? $log->lido_em->format('d/m/Y H:i:s') : ($log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—') }}
                        </span>
                    </div>
                    <div style="font-size:.8125rem;color:var(--c-text-muted)">{{ $log->remetente ?: '—' }}</div>
                    <div style="color:var(--c-danger);font-size:.8125rem;margin-top:.25rem;word-break:break-word">{{ $log->detalhes }}</div>
                </div>
            @empty
                <p style="font-size:.875rem;color:var(--c-text-muted)">Nenhum erro registrado.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
