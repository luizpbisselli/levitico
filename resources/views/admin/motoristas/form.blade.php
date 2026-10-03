@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $motorista->exists ? '✏️ Editar motorista' : '👤 Novo motorista' }}</h1>
</div>
<form method="POST" action="{{ $motorista->exists ? route('admin.motoristas.update', $motorista) : route('admin.motoristas.store') }}"
      class="card" style="max-width:36rem">
    <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
        @csrf
        @if($motorista->exists) @method('PUT') @endif
        <div>
            <label class="form-label">Nome *</label>
            <input name="nome" value="{{ old('nome', $motorista->nome) }}" required class="form-input">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div>
                <label class="form-label">CNH</label>
                <input name="cnh" value="{{ old('cnh', $motorista->cnh) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Telefone/WhatsApp</label>
                <input name="telefone" value="{{ old('telefone', $motorista->telefone) }}" class="form-input">
            </div>
        </div>
        <label style="display:inline-flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500">
            <input type="checkbox" name="agregado" value="1" {{ old('agregado', $motorista->agregado) ? 'checked' : '' }}> Agregado
        </label>
        <div>
            <label class="form-label">Veículos vinculados</label>
            <div style="border:1px solid var(--c-border);border-radius:var(--radius-sm);padding:.75rem;max-height:12rem;overflow:auto;display:flex;flex-direction:column;gap:.375rem">
                @foreach($veiculos as $v)
                    <label style="display:flex;gap:.5rem;align-items:center;font-size:.875rem">
                        <input type="checkbox" name="veiculos[]" value="{{ $v->id }}"
                            {{ in_array($v->id, old('veiculos', $motorista->veiculos->pluck('id')->all())) ? 'checked' : '' }}>
                        {{ $v->placa }} — {{ $v->tipo }} {{ $v->marca }} {{ $v->modelo }}
                    </label>
                @endforeach
            </div>
        </div>
        @if(!$motorista->exists || !$motorista->user_id)
        <fieldset style="border:1px solid var(--c-border);border-radius:var(--radius-sm);padding:1rem">
            <legend style="font-size:.875rem;font-weight:500;padding:0 .5rem">
                <label style="display:inline-flex;gap:.5rem;align-items:center">
                    <input type="checkbox" name="criar_login" value="1" {{ old('criar_login') ? 'checked' : '' }}> Criar login do motorista
                </label>
            </legend>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-top:.5rem">
                <input type="email" name="email" placeholder="E-mail de login" value="{{ old('email') }}" class="form-input">
                <input type="password" name="password" placeholder="Senha provisória" class="form-input">
            </div>
        </fieldset>
        @endif
        <div><button class="btn btn-dark">Salvar</button></div>
    </div>
</form>
@endsection
