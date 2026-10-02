@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Motoristas</h1>
    <a href="{{ route('admin.motoristas.create') }}" class="bg-slate-800 text-white px-4 py-2 rounded">+ Novo motorista</a>
</div>
<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50"><th class="p-3">Nome</th><th class="p-3">CNH</th><th class="p-3">Telefone</th><th class="p-3">Agregado</th><th class="p-3">Veículos</th><th class="p-3">Login</th><th class="p-3"></th></tr></thead>
    <tbody>
    @foreach($motoristas as $m)
        <tr class="border-b">
            <td class="p-3">{{ $m->nome }}</td>
            <td class="p-3">{{ $m->cnh }}</td>
            <td class="p-3">{{ $m->telefone }}</td>
            <td class="p-3">{{ $m->agregado ? 'Sim' : 'Não' }}</td>
            <td class="p-3">{{ $m->veiculos->pluck('placa')->join(', ') ?: '—' }}</td>
            <td class="p-3">{{ $m->user?->email ?? 'sem login' }}</td>
            <td class="p-3 text-right">
                <a href="{{ route('admin.motoristas.edit', $m) }}" class="text-blue-700 hover:underline mr-3">Editar</a>
                <form method="POST" action="{{ route('admin.motoristas.destroy', $m) }}" class="inline" onsubmit="return confirm('Remover?')">
                    @csrf @method('DELETE')<button class="text-red-700 hover:underline">Remover</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $motoristas->links() }}</div>
@endsection
