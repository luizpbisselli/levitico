@extends('layouts.app')
@section('content')
<div class="flex flex-wrap justify-between items-center gap-2 mb-4">
    <h1 class="text-2xl font-bold">Relatório de entregas</h1>
    <form class="flex flex-wrap gap-2 items-center text-sm">
        <input type="date" name="de" value="{{ request('de') }}" class="border rounded p-1">
        <input type="date" name="ate" value="{{ request('ate') }}" class="border rounded p-1">
        <select name="status" class="border rounded p-1">
            <option value="">Todos status</option>
            @foreach(\App\Models\Entrega::STATUS as $val => $rot)
                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $rot }}</option>
            @endforeach
        </select>
        <button class="bg-slate-800 text-white px-3 rounded">Filtrar</button>
        <a href="{{ route('admin.relatorios', array_merge(request()->query(), ['exportar' => 'csv'])) }}"
           class="bg-green-700 text-white px-3 rounded">Exportar CSV</a>
    </form>
</div>
<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50">
        <th class="p-3">CT-e</th><th class="p-3">Placa</th><th class="p-3">Motorista</th><th class="p-3">Cliente</th>
        <th class="p-3">Cidade/UF</th><th class="p-3">Status</th><th class="p-3">Frete (R$)</th><th class="p-3">Canhoto</th><th class="p-3">Criada</th><th class="p-3">Entregue</th>
    </tr></thead>
    <tbody>
    @foreach($entregas as $e)
        <tr class="border-b">
            <td class="p-3">{{ $e->cte?->numero }}</td>
            <td class="p-3 font-mono">{{ strtoupper((string) $e->veiculo?->placa) ?: '—' }}</td>
            <td class="p-3">{{ $e->motorista?->nome ?? '—' }}</td>
            <td class="p-3">{{ $e->cliente?->nome ?? $e->cte?->destinatario_nome }}</td>
            <td class="p-3">{{ $e->cidade_entrega }}/{{ $e->uf_entrega }}</td>
            <td class="p-3">{{ $e->statusLabel() }}</td>
            <td class="p-3">{{ $e->cte ? number_format((float) $e->cte->valor_frete, 2, ',', '.') : '' }}</td>
            <td class="p-3">
                @if($e->temComprovante())
                    <a href="{{ route('admin.entregas.comprovante', $e) }}" target="_blank"
                       class="text-xs bg-emerald-100 text-green-800 border border-green-300 px-2 py-0.5 rounded font-semibold hover:bg-emerald-200">📸 Ver</a>
                @else
                    <span class="text-xs text-gray-400">—</span>
                @endif
            </td>
            <td class="p-3">{{ $e->created_at->format('d/m/Y H:i') }}</td>
            <td class="p-3">{{ $e->entregue_em?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
