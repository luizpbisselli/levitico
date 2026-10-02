@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Clientes</h1>
    <a href="{{ route('admin.clientes.create') }}" class="bg-slate-800 text-white px-4 py-2 rounded">+ Novo cliente</a>
</div>
<table class="w-full bg-white rounded shadow text-sm">
    <thead><tr class="text-left border-b bg-gray-50"><th class="p-3">Nome</th><th class="p-3">Documento</th><th class="p-3">Telefone</th><th class="p-3">WhatsApp</th><th class="p-3">Cidade/UF</th><th class="p-3"></th></tr></thead>
    <tbody>
    @foreach($clientes as $c)
        <tr class="border-b">
            <td class="p-3">{{ $c->nome }}</td>
            <td class="p-3">{{ $c->documento }}</td>
            <td class="p-3">{{ $c->telefone }}</td>
            <td class="p-3">{{ $c->whatsapp }}</td>
            <td class="p-3">{{ $c->cidade }}/{{ $c->uf }}</td>
            <td class="p-3 text-right">
                <a href="{{ route('admin.clientes.edit', $c) }}" class="text-blue-700 hover:underline mr-3">Editar</a>
                <form method="POST" action="{{ route('admin.clientes.destroy', $c) }}" class="inline" onsubmit="return confirm('Remover?')">
                    @csrf @method('DELETE')<button class="text-red-700 hover:underline">Remover</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $clientes->links() }}</div>
@endsection
