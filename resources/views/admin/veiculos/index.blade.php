@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Veículos</h1>
    <a href="{{ route('admin.veiculos.create') }}" class="bg-slate-800 text-white px-4 py-2 rounded">+ Novo veículo</a>
</div>
<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50"><th class="p-3">Placa</th><th class="p-3">Tipo</th><th class="p-3">Marca/Modelo</th><th class="p-3">Renavam</th><th class="p-3">Motoristas</th><th class="p-3">Ativo</th><th class="p-3"></th></tr></thead>
    <tbody>
    @foreach($veiculos as $v)
        <tr class="border-b">
            <td class="p-3 font-mono font-bold">{{ $v->placa }}</td>
            <td class="p-3">{{ $v->tipo }}</td>
            <td class="p-3">{{ $v->marca }} {{ $v->modelo }}</td>
            <td class="p-3">{{ $v->renavam }}</td>
            <td class="p-3">{{ $v->motoristas->pluck('nome')->join(', ') }}</td>
            <td class="p-3">{{ $v->ativo ? 'Sim' : 'Não' }}</td>
            <td class="p-3 text-right">
                <a href="{{ route('admin.veiculos.edit', $v) }}" class="text-blue-700 hover:underline mr-3">Editar</a>
                <form method="POST" action="{{ route('admin.veiculos.destroy', $v) }}" class="inline" onsubmit="return confirm('Remover?')">
                    @csrf @method('DELETE')
                    <button class="text-red-700 hover:underline">Remover</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $veiculos->links() }}</div>
@endsection
