@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">Clientes</h1>
    <a href="{{ route('admin.clientes.create') }}" class="btn btn-primary">+ Novo cliente</a>
</div>
<div class="table-wrap">
    <table class="resp-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Documento</th>
                <th>Telefone</th>
                <th>WhatsApp</th>
                <th>Cidade/UF</th>
                <th style="text-align:right;">Ações</th>
            </tr>
        </thead>
        <tbody>
        @forelse($clientes as $c)
            <tr>
                <td style="font-weight:600;">{{ $c->nome }}</td>
                <td>{{ $c->documento ?: '—' }}</td>
                <td>{{ $c->telefone ?: '—' }}</td>
                <td>{{ $c->whatsapp ?: '—' }}</td>
                <td>{{ $c->cidade ? $c->cidade . '/' . $c->uf : '—' }}</td>
                <td style="text-align:right; white-space:nowrap;">
                    <a href="{{ route('admin.clientes.edit', $c) }}" class="btn btn-secondary btn-sm" style="margin-right:4px;">Editar</a>
                    <form method="POST" action="{{ route('admin.clientes.destroy', $c) }}" style="display:inline;" onsubmit="return confirm('Remover este cliente?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Remover</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">
                    Nenhum cliente cadastrado ainda.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:1rem;">{{ $clientes->links() }}</div>
@endsection
