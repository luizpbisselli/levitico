@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">Pendências e Alertas</h1>
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-weight:700; font-size:1rem; margin:0;">Entregas sem Veículo Atribuído</h2>
            <span class="badge badge-warning">{{ $semVeiculo->count() }}</span>
        </div>
        <div class="card-body" style="padding:0.75rem 1.25rem;">
            @forelse($semVeiculo as $e)
                <div style="border-bottom:1px solid var(--border); padding:0.75rem 0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                    <div>
                        <div style="font-weight:600; font-size:0.9rem;">CT-e {{ $e->cte?->numero ?: 'N/A' }}</div>
                        <div style="font-size:0.8rem; color:var(--text-muted);">{{ $e->cte?->destinatario_nome }} · {{ $e->cidade_entrega }}/{{ $e->uf_entrega }}</div>
                    </div>
                    <a href="{{ route('admin.documentos.ctes') }}" class="btn btn-primary btn-sm">Atribuir CT-e</a>
                </div>
            @empty
                <p style="font-size:0.875rem; color:var(--text-muted); padding:1rem 0; margin:0; text-align:center;">Nenhuma entrega pendente de veículo! 🎉</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-weight:700; font-size:1rem; margin:0;">Erros de Ingestão de E-mail</h2>
            <span class="badge {{ $errosEmail->count() > 0 ? 'badge-danger' : 'badge-success' }}">{{ $errosEmail->count() }}</span>
        </div>
        <div class="card-body" style="padding:0.75rem 1.25rem;">
            @forelse($errosEmail as $log)
                <div style="border-bottom:1px solid var(--border); padding:0.75rem 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:600; font-size:0.875rem;">{{ $log->assunto ?: 'Sem assunto' }}</span>
                        <span style="font-size:0.75rem; color:var(--text-muted);">{{ $log->created_at->format('d/m H:i') }}</span>
                    </div>
                    <div style="font-size:0.8rem; color:var(--danger); margin-top:0.25rem;">{{ $log->detalhes }}</div>
                </div>
            @empty
                <p style="font-size:0.875rem; color:var(--text-muted); padding:1rem 0; margin:0; text-align:center;">Nenhum erro de leitura de e-mails registrado.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
