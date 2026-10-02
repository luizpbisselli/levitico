@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">Editar modelo: {{ $modelo->nome }}</h1>
<form method="POST" action="{{ route('admin.modelos.update', $modelo) }}" class="bg-white rounded shadow p-6 max-w-xl space-y-4">
    @csrf @method('PUT')
    <div><label class="block text-sm font-medium mb-1">Nome</label>
        <input name="nome" value="{{ old('nome', $modelo->nome) }}" class="w-full border rounded p-2"></div>
    <div><label class="block text-sm font-medium mb-1">Gatilho</label>
        <select name="gatilho" class="w-full border rounded p-2">
            @foreach(['em_transito' => 'Ao sair para entrega', 'entregue' => 'Ao entregar', 'ocorrencia' => 'Em ocorrência', 'manual' => 'Manual'] as $val => $rot)
                <option value="{{ $val }}" @selected(old('gatilho', $modelo->gatilho) === $val)>{{ $rot }}</option>
            @endforeach
        </select></div>
    <div><label class="block text-sm font-medium mb-1">Template</label>
        <textarea name="template" rows="4" class="w-full border rounded p-2">{{ old('template', $modelo->template) }}</textarea></div>
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $modelo->ativo))> Ativo</label>
    <div><button class="bg-slate-800 text-white px-6 py-2 rounded">Salvar</button></div>
</form>
@endsection
