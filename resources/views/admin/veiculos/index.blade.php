@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">🚛 Veículos</h1>
    <a href="{{ route('admin.veiculos.create') }}" class="btn btn-dark btn-sm">+ Novo veículo</a>
</div>
<div class="table-wrap">
<table class="resp-table">
    <thead><tr><th>Placa</th><th>Tipo</th><th>Marca/Modelo</th><th>Renavam</th><th>Motoristas</th><th>Ativo</th><th></th></tr></thead>
    <tbody>
    @foreach($veiculos as $v)
        <tr>
            <td class="font-mono" style="font-weight:700">{{ $v->placa }}</td>
            <td>{{ $v->tipo }}</td>
            <td>{{ $v->marca }} {{ $v->modelo }}</td>
            <td>{{ $v->renavam }}</td>
            <td>{{ $v->motoristas->pluck('nome')->join(', ') }}</td>
            <td><span class="badge {{ $v->ativo ? 'badge-green' : 'badge-red' }}">{{ $v->ativo ? 'Sim' : 'Não' }}</span></td>
            <td style="text-align:right;white-space:nowrap">
                <a href="{{ route('admin.veiculos.edit', $v) }}" style="color:var(--c-primary);font-weight:500;font-size:.8125rem;margin-right:.75rem">Editar</a>
                <form method="POST" action="{{ route('admin.veiculos.destroy', $v) }}" style="display:inline" onsubmit="return confirm('Remover?')">
                    @csrf @method('DELETE')<button style="background:none;border:none;color:var(--c-danger);font-weight:500;font-size:.8125rem;cursor:pointer">Remover</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">{{ $veiculos->links() }}</div>
@endsection
