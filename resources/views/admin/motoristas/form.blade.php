@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $motorista->exists ? 'Editar motorista' : 'Novo motorista' }}</h1>
<form method="POST" action="{{ $motorista->exists ? route('admin.motoristas.update', $motorista) : route('admin.motoristas.store') }}"
      class="bg-white rounded shadow p-6 max-w-xl space-y-4">
    @csrf
    @if($motorista->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium mb-1">Nome *</label>
        <input name="nome" value="{{ old('nome', $motorista->nome) }}" required class="w-full border rounded p-2"></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium mb-1">CNH</label>
            <input name="cnh" value="{{ old('cnh', $motorista->cnh) }}" class="w-full border rounded p-2"></div>
        <div><label class="block text-sm font-medium mb-1">Telefone/WhatsApp</label>
            <input name="telefone" value="{{ old('telefone', $motorista->telefone) }}" class="w-full border rounded p-2"></div>
    </div>
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="agregado" value="1" {{ old('agregado', $motorista->agregado) ? 'checked' : '' }}> Agregado</label>
    <div>
        <label class="block text-sm font-medium mb-1">Veículos vinculados</label>
        <div class="border rounded p-2 max-h-40 overflow-auto space-y-1">
            @foreach($veiculos as $v)
                <label class="flex gap-2 items-center text-sm">
                    <input type="checkbox" name="veiculos[]" value="{{ $v->id }}"
                        {{ in_array($v->id, old('veiculos', $motorista->veiculos->pluck('id')->all())) ? 'checked' : '' }}>
                    {{ $v->placa }} — {{ $v->tipo }} {{ $v->marca }} {{ $v->modelo }}
                </label>
            @endforeach
        </div>
    </div>
    @if(!$motorista->exists || !$motorista->user_id)
    <fieldset class="border rounded p-3">
        <legend class="text-sm font-medium px-2">
            <label class="inline-flex gap-2 items-center"><input type="checkbox" name="criar_login" value="1" {{ old('criar_login') ? 'checked' : '' }}> Criar login do motorista</label>
        </legend>
        <div class="grid grid-cols-2 gap-4 mt-2">
            <input type="email" name="email" placeholder="E-mail de login" value="{{ old('email') }}" class="border rounded p-2 text-sm">
            <input type="password" name="password" placeholder="Senha provisória" class="border rounded p-2 text-sm">
        </div>
    </fieldset>
    @endif
    <div><button class="bg-slate-800 text-white px-6 py-2 rounded">Salvar</button></div>
</form>
@endsection
