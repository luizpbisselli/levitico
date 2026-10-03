@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">Editar Modelo: {{ $modelo->nome }}</h1>
    <a href="{{ route('admin.modelos.index') }}" class="btn btn-secondary btn-sm">← Voltar</a>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.modelos.update', $modelo) }}" style="display:flex; flex-direction:column; gap:1rem;">
            @csrf @method('PUT')

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Nome do Modelo</label>
                <input name="nome" value="{{ old('nome', $modelo->nome) }}" required class="form-input">
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Gatilho de Disparo</label>
                <select name="gatilho" class="form-select">
                    @foreach(['em_transito' => 'Ao sair para entrega', 'entregue' => 'Ao entregar', 'ocorrencia' => 'Em ocorrência', 'manual' => 'Manual'] as $val => $rot)
                        <option value="{{ $val }}" @selected(old('gatilho', $modelo->gatilho) === $val)>{{ $rot }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Template da Mensagem</label>
                <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.5rem;">
                    Tags: <code>{cliente}</code> <code>{nfe}</code> <code>{placa}</code> <code>{status}</code> <code>{motorista}</code> <code>{cidade}</code>
                </p>
                <textarea name="template" rows="5" class="form-textarea">{{ old('template', $modelo->template) }}</textarea>
            </div>

            <div>
                <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.875rem; font-weight:600; cursor:pointer;">
                    <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $modelo->ativo)) style="width:1.125rem; height:1.125rem; accent-color:var(--primary);">
                    Modelo Ativo
                </label>
            </div>

            <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Salvar Modelo</button>
                <a href="{{ route('admin.modelos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
