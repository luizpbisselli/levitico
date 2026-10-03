@extends('layouts.app')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Modelos de Mensagem WhatsApp</h1>
        <p style="font-size:0.875rem; color:var(--text-muted); margin:0.25rem 0 0 0;">
            Tags disponíveis: <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-size:0.8rem;">{cliente}</code> <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-size:0.8rem;">{nfe}</code> <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-size:0.8rem;">{placa}</code> <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-size:0.8rem;">{status}</code> <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-size:0.8rem;">{motorista}</code> <code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; font-size:0.8rem;">{cidade}</code>
        </p>
    </div>
</div>

<div class="table-wrap">
    <table class="resp-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Gatilho</th>
                <th>Template</th>
                <th>Status</th>
                <th style="text-align:right;">Ações</th>
            </tr>
        </thead>
        <tbody>
        @foreach($modelos as $m)
            <tr>
                <td style="font-weight:600;">{{ $m->nome }}</td>
                <td><span class="badge badge-info">{{ $m->gatilho }}</span></td>
                <td style="color:var(--text-muted); max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $m->template }}</td>
                <td>
                    <span class="badge {{ $m->ativo ? 'badge-success' : 'badge-danger' }}">{{ $m->ativo ? 'Ativo' : 'Inativo' }}</span>
                </td>
                <td style="text-align:right;">
                    <a href="{{ route('admin.modelos.edit', $m) }}" class="btn btn-secondary btn-sm">Editar</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
