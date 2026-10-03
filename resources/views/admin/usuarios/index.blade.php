@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">Usuários do Sistema</h1>
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">+ Novo Usuário</a>
</div>

<div class="table-wrap">
    <table class="resp-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Motorista Vinculado</th>
                <th>Criado em</th>
                <th style="text-align:right;">Ações</th>
            </tr>
        </thead>
        <tbody>
        @foreach($usuarios as $u)
            <tr>
                <td style="font-weight:600;">{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                    <span class="badge {{ $u->isAdmin() ? 'badge-primary' : 'badge-info' }}">
                        {{ ucfirst($u->profile) }}
                    </span>
                </td>
                <td>{{ $u->motorista?->nome ?? '—' }}</td>
                <td style="white-space:nowrap; font-size:0.8rem; color:var(--text-muted);">{{ $u->created_at?->format('d/m/Y') }}</td>
                <td style="text-align:right; white-space:nowrap;">
                    <a href="{{ route('admin.usuarios.edit', $u) }}" class="btn btn-secondary btn-sm" style="margin-right:4px;">Editar</a>
                    @if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.usuarios.destroy', $u) }}" style="display:inline;" onsubmit="return confirm('Remover este usuário?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Remover</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div style="margin-top:1rem;">{{ $usuarios->links() }}</div>
@endsection
