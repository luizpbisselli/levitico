@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $veiculo->exists ? 'Editar veículo' : 'Novo veículo' }}</h1>
<form method="POST" action="{{ $veiculo->exists ? route('admin.veiculos.update', $veiculo) : route('admin.veiculos.store') }}"
      class="bg-white rounded shadow p-6 max-w-xl space-y-4">
    @csrf
    @if($veiculo->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium mb-1">Placa *</label>
        <input name="placa" value="{{ old('placa', $veiculo->placa) }}" required class="w-full border rounded p-2 uppercase"></div>
    <div><label class="block text-sm font-medium mb-1">Tipo (Truck, Carreta, VUC...)</label>
        <input name="tipo" value="{{ old('tipo', $veiculo->tipo) }}" class="w-full border rounded p-2"></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium mb-1">Marca</label>
            <input name="marca" value="{{ old('marca', $veiculo->marca) }}" class="w-full border rounded p-2"></div>
        <div><label class="block text-sm font-medium mb-1">Modelo</label>
            <input name="modelo" value="{{ old('modelo', $veiculo->modelo) }}" class="w-full border rounded p-2"></div>
    </div>
    <div><label class="block text-sm font-medium mb-1">Renavam</label>
        <input name="renavam" value="{{ old('renavam', $veiculo->renavam) }}" class="w-full border rounded p-2"></div>
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="ativo" value="1" {{ old('ativo', $veiculo->ativo ?? true) ? 'checked' : '' }}> Ativo</label>
    <div><button class="bg-slate-800 text-white px-6 py-2 rounded">Salvar</button></div>
</form>
@endsection
