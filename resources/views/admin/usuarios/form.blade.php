@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $usuario->exists ? 'Editar usuário' : 'Novo usuário' }}</h1>
<form method="POST" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}"
      class="bg-white rounded shadow p-6 max-w-xl space-y-4">
    @csrf
    @if($usuario->exists) @method('PUT') @endif

    <div><label class="block text-sm font-medium mb-1">Nome *</label>
        <input name="name" value="{{ old('name', $usuario->name) }}" required class="w-full border rounded p-2"></div>

    <div><label class="block text-sm font-medium mb-1">E-mail (login) *</label>
        <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required class="w-full border rounded p-2"></div>

    <div><label class="block text-sm font-medium mb-1">Perfil *</label>
        <select name="profile" class="w-full border rounded p-2">
            <option value="admin" @selected(old('profile', $usuario->profile) === 'admin')>Administrador</option>
            <option value="motorista" @selected(old('profile', $usuario->profile) === 'motorista')>Motorista</option>
        </select></div>

    <div><label class="block text-sm font-medium mb-1">Senha {{ $usuario->exists ? '(deixe em branco para manter)' : '*' }}</label>
        <input type="password" name="password" {{ $usuario->exists ? '' : 'required' }} class="w-full border rounded p-2" autocomplete="new-password"></div>

    <div><label class="block text-sm font-medium mb-1">Confirmar senha</label>
        <input type="password" name="password_confirmation" {{ $usuario->exists ? '' : 'required' }} class="w-full border rounded p-2" autocomplete="new-password"></div>

    @if($usuario->isLocked())
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded p-3 text-sm">
            Esta conta está bloqueada até {{ $usuario->locked_until->format('d/m/Y H:i') }} por excesso de tentativas de login.
        </div>
    @endif

    <div class="flex gap-3">
        <button class="bg-slate-800 text-white px-6 py-2 rounded">Salvar</button>
        <a href="{{ route('admin.usuarios.index') }}" class="px-4 py-2 rounded border">Cancelar</a>
    </div>
</form>
@endsection
