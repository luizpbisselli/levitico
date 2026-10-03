@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $usuario->exists ? 'Editar Usuário' : 'Novo Usuário' }}</h1>
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary btn-sm">← Voltar</a>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" style="display:flex; flex-direction:column; gap:1rem;">
            @csrf
            @if($usuario->exists) @method('PUT') @endif

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Nome Completo *</label>
                <input name="name" value="{{ old('name', $usuario->name) }}" required class="form-input" placeholder="Ex: João da Silva">
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">E-mail (Login) *</label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required class="form-input" placeholder="usuario@exemplo.com">
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Perfil de Acesso *</label>
                <select name="profile" class="form-select">
                    <option value="admin" @selected(old('profile', $usuario->profile) === 'admin')>👑 Administrador (Acesso Total)</option>
                    <option value="motorista" @selected(old('profile', $usuario->profile) === 'motorista')>🚚 Motorista (Apenas Entregas)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Senha {{ $usuario->exists ? '(opcional)' : '*' }}</label>
                    <input type="password" name="password" {{ $usuario->exists ? '' : 'required' }} class="form-input" autocomplete="new-password" placeholder="{{ $usuario->exists ? 'Manter atual' : 'Mínimo 8 caracteres' }}">
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Confirmar Senha</label>
                    <input type="password" name="password_confirmation" {{ $usuario->exists ? '' : 'required' }} class="form-input" autocomplete="new-password" placeholder="Repita a senha">
                </div>
            </div>

            @if($usuario->isLocked())
                <div class="alert alert-danger" style="margin:0;">
                    🔒 Esta conta está bloqueada até <b>{{ $usuario->locked_until->format('d/m/Y H:i') }}</b> por excesso de tentativas incorretas.
                </div>
            @endif

            <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Salvar Usuário</button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
