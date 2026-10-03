@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">👤 Motoristas</h1>
    <a href="{{ route('admin.motoristas.create') }}" class="btn btn-dark btn-sm">+ Novo motorista</a>
</div>
<div class="table-wrap">
<table class="resp-table">
    <thead><tr><th>Nome</th><th>CNH</th><th>Telefone</th><th>Agregado</th><th>Veículos</th><th>Login</th><th></th></tr></thead>
    <tbody>
    @foreach($motoristas as $m)
        <tr>
            <td style="font-weight:600">{{ $m->nome }}</td>
            <td>{{ $m->cnh }}</td>
            <td>{{ $m->telefone }}</td>
            <td><span class="badge {{ $m->agregado ? 'badge-yellow' : 'badge-slate' }}">{{ $m->agregado ? 'Sim' : 'Não' }}</span></td>
            <td>{{ $m->veiculos->pluck('placa')->join(', ') ?: '—' }}</td>
            <td style="font-size:.8125rem">{{ $m->user?->email ?? 'sem login' }}</td>
            <td style="text-align:right;white-space:nowrap">
                <a href="{{ route('admin.motoristas.edit', $m) }}" style="color:var(--c-primary);font-weight:500;font-size:.8125rem;margin-right:.75rem">Editar</a>
                <form method="POST" action="{{ route('admin.motoristas.destroy', $m) }}" style="display:inline" onsubmit="return confirm('Remover?')">
                    @csrf @method('DELETE')<button style="background:none;border:none;color:var(--c-danger);font-weight:500;font-size:.8125rem;cursor:pointer">Remover</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">{{ $motoristas->links() }}</div>
@endsection
