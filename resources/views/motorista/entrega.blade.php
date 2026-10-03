@extends('layouts.app')
@section('title', 'Entrega')
@section('content')
<div class="max-w-md mx-auto">
    <a href="{{ route('motorista.home') }}" class="text-blue-700 text-sm hover:underline">← Voltar</a>
    <div class="bg-white rounded-lg shadow p-4 mt-2">
        <h1 class="text-lg font-bold">{{ $entrega->cte?->destinatario_nome ?? 'Cliente' }}</h1>
        <p class="text-sm text-gray-700 mt-1">📍 {{ $entrega->endereco_entrega ?: 'Endereço não informado no XML' }}</p>
        <p class="text-sm text-gray-600">{{ $entrega->cidade_entrega }}/{{ $entrega->uf_entrega }}</p>
        <div class="text-xs text-gray-500 mt-2 space-y-1">
            <div>CT-e: {{ $entrega->cte?->numero }} ({{ $entrega->cte?->chave_acesso }})</div>
            <div>NF-e: {{ $entrega->cte?->nfes->pluck('numero')->join(', ') ?: '—' }}</div>
            <div>Veículo: {{ strtoupper((string) $entrega->veiculo?->placa) }} · Motorista: {{ $entrega->motorista?->nome ?? auth()->user()->motorista?->nome }}</div>
            <div>Volumes: {{ $entrega->cte?->nfes->sum('volumes') ?: '—' }} · Peso: {{ number_format((float) $entrega->cte?->nfes->sum('peso_bruto'), 1, ',', '.') }} kg</div>
        </div>
        <div class="mt-2 text-sm">Status atual:
            <span class="px-2 py-1 rounded bg-gray-200 font-semibold">{{ $entrega->statusLabel() }}</span>
        </div>
        @if($entrega->temComprovante())
            <div class="mt-3 p-3 bg-emerald-50 border border-green-300 rounded text-sm flex items-center justify-between">
                <div>
                    <span class="font-bold text-green-800">📸 Canhoto Anexado</span>
                    <span class="block text-xs text-gray-600">{{ $entrega->comprovante_enviado_em?->format('d/m/Y H:i') }}</span>
                </div>
                <a href="{{ route('motorista.entregas.comprovante', $entrega) }}" target="_blank"
                   class="bg-emerald-600 text-white text-xs px-3 py-1.5 rounded font-semibold hover:bg-emerald-700">Ver Foto</a>
            </div>
        @endif
        @if($entrega->observacao_ocorrencia)
            <div class="mt-2 text-sm bg-red-50 border border-red-200 rounded p-2">Ocorrência: {{ $entrega->observacao_ocorrencia }}</div>
        @endif
    </div>

    @if(in_array($entrega->status, ['a_coleter', 'em_transito']))
    <div class="space-y-3 mt-4">
        @if($entrega->status === 'a_coleter')
        <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}">
            @csrf <input type="hidden" name="status" value="em_transito">
            <button class="w-full bg-blue-600 text-white rounded-lg py-4 text-lg font-bold active:bg-blue-700">🚚 Saí para entrega</button>
        </form>
        @endif
        
        <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-4 space-y-3">
            @csrf
            <input type="hidden" name="status" value="entregue">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">📸 Foto do Canhoto / Comprovante (opcional)</label>
                <input type="file" name="comprovante" accept="image/*" capture="environment" id="fotoInput"
                       class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <div id="fotoPreviewContainer" class="hidden mt-2">
                    <img id="fotoPreview" src="" alt="Prévia do canhoto" class="max-h-40 rounded border mx-auto">
                </div>
            </div>

            <button class="w-full bg-green-600 text-white rounded-lg py-4 text-lg font-bold active:bg-green-700">✅ Marcar como Entregue</button>
        </form>

        <details class="bg-white rounded-lg shadow p-3">
            <summary class="text-red-700 font-semibold cursor-pointer">⚠️ Registrar Ocorrência</summary>
            <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}" class="mt-2 space-y-2">
                @csrf <input type="hidden" name="status" value="ocorrencia">
                <textarea name="observacao" rows="2" placeholder="Descreva a ocorrência (ex.: destinatário ausente, recusa...)"
                          class="w-full border rounded p-2 text-sm" required></textarea>
                <button class="w-full bg-red-600 text-white rounded py-2 font-semibold">Salvar ocorrência</button>
            </form>
        </details>
    </div>
    @elseif(! $entrega->temComprovante())
    <div class="bg-white rounded-lg shadow p-4 mt-4">
        <h2 class="font-semibold text-sm mb-2 text-slate-700">📸 Anexar Canhoto / Foto do Comprovante</h2>
        <form method="POST" action="{{ route('motorista.entregas.status', $entrega) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="hidden" name="status" value="{{ $entrega->status }}">
            <input type="file" name="comprovante" accept="image/*" capture="environment" required
                   class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <button class="w-full bg-slate-700 text-white rounded py-2 font-semibold hover:bg-slate-800">Enviar Comprovante</button>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-4 mt-4">
        <h2 class="font-semibold text-sm mb-2">Mensagem para o cliente (WhatsApp)</h2>
        <textarea id="msg" rows="3" class="w-full border rounded p-2 text-sm">{{ $mensagem }}</textarea>
        <a id="waLink" href="{{ $waUrl }}" target="_blank" rel="noopener"
           class="block text-center bg-emerald-500 text-white rounded-lg py-3 font-bold mt-2">📲 Abrir WhatsApp</a>
        <p class="text-xs text-gray-500 mt-2">O texto acima pode ser editado antes de abrir. Ao voltar do WhatsApp, marque o status da entrega.</p>
    </div>
</div>
<script>
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
