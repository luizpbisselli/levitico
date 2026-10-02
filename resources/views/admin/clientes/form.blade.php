@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $cliente->exists ? 'Editar cliente' : 'Novo cliente' }}</h1>
<form method="POST" action="{{ $cliente->exists ? route('admin.clientes.update', $cliente) : route('admin.clientes.store') }}"
      class="bg-white rounded shadow p-6 max-w-xl space-y-4">
    @csrf
    @if($cliente->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium mb-1">Nome *</label>
        <input name="nome" value="{{ old('nome', $cliente->nome) }}" required class="w-full border rounded p-2"></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium mb-1">CNPJ/CPF</label>
            <input name="documento" value="{{ old('documento', $cliente->documento) }}" class="w-full border rounded p-2"></div>
        <div><label class="block text-sm font-medium mb-1">CEP</label>
            <input name="cep" value="{{ old('cep', $cliente->cep) }}" class="w-full border rounded p-2"></div>
        <div><label class="block text-sm font-medium mb-1">Telefone</label>
            <input name="telefone" value="{{ old('telefone', $cliente->telefone) }}" class="w-full border rounded p-2"></div>
        <div><label class="block text-sm font-medium mb-1">WhatsApp</label>
            <input name="whatsapp" value="{{ old('whatsapp', $cliente->whatsapp) }}" class="w-full border rounded p-2"></div>
    </div>
    <div><label class="block text-sm font-medium mb-1">Endereço</label>
        <input name="endereco" value="{{ old('endereco', $cliente->endereco) }}" class="w-full border rounded p-2"></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium mb-1">Cidade</label>
            <input name="cidade" value="{{ old('cidade', $cliente->cidade) }}" class="w-full border rounded p-2"></div>
        <div><label class="block text-sm font-medium mb-1">UF</label>
            <input name="uf" maxlength="2" value="{{ old('uf', $cliente->uf) }}" class="w-full border rounded p-2 uppercase"></div>
    </div>
    <div><button class="bg-slate-800 text-white px-6 py-2 rounded">Salvar</button></div>
</form>
@endsection
