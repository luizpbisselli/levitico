@extends('layouts.app')
@section('title', 'Entrega')
@section('content')
<div style="max-width:32rem;margin:0 auto">
    <a href="{{ route('motorista.home') }}" style="display:inline-flex;align-items:center;gap:.375rem;color:var(--c-primary);font-size:.875rem;font-weight:500;margin-bottom:.75rem">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Voltar
    </a>

    {{-- Dados da Entrega --}}
    <div class="card" style="margin-bottom:1rem">
        <div class="card-body">
            <h1 style="font-size:1.125rem;font-weight:700;color:var(--c-text)">{{ $entrega->cte?->destinatario_nome ?? 'Cliente' }}</h1>
            <p style="font-size:.875rem;color:var(--c-text-muted);margin-top:.375rem">📍 {{ $entrega->endereco_entrega ?: 'Endereço não informado no XML' }}</p>
            <p style="font-size:.875rem;color:var(--c-text-light)">{{ $entrega->cidade_entrega }}/{{ $entrega->uf_entrega }}</p>

            <div style="margin-top:.75rem;display:grid;grid-template-columns:1fr 1fr;gap:.5rem;font-size:.75rem;color:var(--c-text-muted)">
                <div><span style="font-weight:600;color:var(--c-text)">CT-e:</span> {{ $entrega->cte?->numero }}</div>
                <div><span style="font-weight:600;color:var(--c-text)">NF-e:</span> {{ $entrega->cte?->nfes->pluck('numero')->join(', ') ?: '—' }}</div>
                <div><span style="font-weight:600;color:var(--c-text)">Veículo:</span> {{ strtoupper((string) $entrega->veiculo?->placa) }}</div>
                <div><span style="font-weight:600;color:var(--c-text)">Motorista:</span> {{ $entrega->motorista?->nome ?? auth()->user()->motorista?->nome }}</div>
                <div><span style="font-weight:600;color:var(--c-text)">Volumes:</span> {{ $entrega->cte?->nfes->sum('volumes') ?: '—' }}</div>
                <div><span style="font-weight:600;color:var(--c-text)">Peso:</span> {{ number_format((float) $entrega->cte?->nfes->sum('peso_bruto'), 1, ',', '.') }} kg</div>
            </div>

            <div style="margin-top:.75rem;display:flex;align-items:center;gap:.5rem;font-size:.875rem">
                <span style="color:var(--c-text-muted)">Status:</span>
                <span class="badge badge-slate" style="font-size:.8125rem">{{ $entrega->statusLabel() }}</span>
            </div>

            @if($entrega->temComprovante())
                <div style="margin-top:.75rem;padding:.75rem;background:#ecfdf5;border:1px solid #86efac;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:space-between;gap:.5rem">
                    <div>
                        <span style="font-weight:700;color:#166534;font-size:.875rem">📸 Canhoto Anexado</span>
                        <span style="display:block;font-size:.75rem;color:var(--c-text-muted)">{{ $entrega->comprovante_enviado_em?->format('d/m/Y H:i') }}</span>
                    </div>
                    <a href="{{ route('motorista.entregas.comprovante', $entrega) }}" target="_blank" class="btn btn-success btn-sm">Ver Foto</a>
                </div>
            @endif

            @if($entrega->observacao_ocorrencia)
                <div style="margin-top:.75rem;padding:.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);font-size:.875rem;color:#991b1b">
                    <strong>Ocorrência:</strong> {{ $entrega->observacao_ocorrencia }}
                </div>
            @endif
        </div>
    </div>

    {{-- Ações --}}
    @if(in_array($entrega->status, ['a_coleter', 'em_transito']))
    <div style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:1rem">
        @if($entrega->status === 'a_coleter')
        <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}">
            @csrf <input type="hidden" name="status" value="em_transito">
            <button class="btn btn-primary btn-block btn-lg" style="font-size:1.0625rem;padding:1rem">🚚 Saí para entrega</button>
        </form>
        @endif

        <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}" enctype="multipart/form-data" class="card">
            <div class="card-body" style="display:flex;flex-direction:column;gap:.75rem">
                @csrf
                <input type="hidden" name="status" value="entregue">

                <div>
                    <label class="form-label">📸 Foto do Canhoto / Comprovante (opcional)</label>
                    <input type="file" name="comprovante" accept="image/*" capture="environment" id="fotoInput" class="form-file">
                    <div id="fotoPreviewContainer" class="hidden" style="margin-top:.5rem">
                        <img id="fotoPreview" src="" alt="Prévia do canhoto" style="max-height:10rem;border-radius:var(--radius-sm);border:1px solid var(--c-border);margin:0 auto">
                    </div>
                </div>

                <button class="btn btn-success btn-block btn-lg" style="font-size:1.0625rem;padding:1rem">✅ Marcar como Entregue</button>
            </div>
        </form>

        <details class="card">
            <summary style="padding:1rem;font-weight:600;color:var(--c-danger);cursor:pointer;font-size:.9375rem">⚠️ Registrar Ocorrência</summary>
            <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}" class="card-body" style="padding:.75rem 1rem 1rem;display:flex;flex-direction:column;gap:.75rem;border-top:1px solid var(--c-border)">
                @csrf <input type="hidden" name="status" value="ocorrencia">
                <textarea name="observacao" rows="2" placeholder="Descreva a ocorrência (ex.: destinatário ausente, recusa...)"
                          class="form-textarea" required></textarea>
                <button class="btn btn-danger btn-block">Salvar ocorrência</button>
            </form>
        </details>
    </div>
    @elseif(! $entrega->temComprovante())
    <div class="card" style="margin-bottom:1rem">
        <div class="card-body">
            <h2 style="font-weight:600;font-size:.9375rem;margin-bottom:.75rem;color:var(--c-text)">📸 Anexar Canhoto / Foto do Comprovante</h2>
            <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:.75rem">
                @csrf
                <input type="hidden" name="status" value="{{ $entrega->status }}">
                <input type="file" name="comprovante" accept="image/*" capture="environment" required class="form-file">
                <button class="btn btn-dark btn-block">Enviar Comprovante</button>
            </form>
        </div>
    </div>
    @endif

    {{-- WhatsApp --}}
    <div class="card" style="margin-bottom:1rem">
        <div class="card-body">
            <h2 style="font-weight:600;font-size:.9375rem;margin-bottom:.75rem">💬 Mensagem para o cliente (WhatsApp)</h2>
            <textarea id="msg" rows="3" class="form-textarea">{{ $mensagem }}</textarea>
            <a id="waLink" href="{{ $waUrl }}" target="_blank" rel="noopener"
               class="btn btn-block" style="background:#25d366;color:#fff;margin-top:.75rem;padding:.875rem;font-size:1rem">
                📲 Abrir WhatsApp
            </a>
            <p style="font-size:.75rem;color:var(--c-text-light);margin-top:.5rem">O texto acima pode ser editado antes de abrir. Ao voltar do WhatsApp, marque o status da entrega.</p>
        </div>
    </div>
</div>

<script>
    // WhatsApp link update
    const link = document.getElementById('waLink');
    const msg = document.getElementById('msg');
    const base = @json(explode('?', $waUrl)[0] ?? $waUrl);
    if (msg && link) {
        msg.addEventListener('input', () => {
            const semNumero = @json(! str_contains($waUrl, 'wa.me/55') && ! preg_match('#wa\.me/\d#', $waUrl));
            link.href = semNumero
                ? 'https://wa.me/?text=' + encodeURIComponent(msg.value)
                : '{{ \Illuminate\Support\Str::before($waUrl, '?') }}?text=' + encodeURIComponent(msg.value);
        });
    }

    // Photo preview
    const fotoInput = document.getElementById('fotoInput');
    const previewContainer = document.getElementById('fotoPreviewContainer');
    const preview = document.getElementById('fotoPreview');
    if (fotoInput && preview && previewContainer) {
        fotoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
                previewContainer.classList.remove('hidden');
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    }
</script>
@endsection
