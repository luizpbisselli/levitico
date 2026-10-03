@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $cliente->exists ? 'Editar Cliente' : 'Novo Cliente' }}</h1>
    <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary btn-sm">← Voltar</a>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ $cliente->exists ? route('admin.clientes.update', $cliente) : route('admin.clientes.store') }}" style="display:flex; flex-direction:column; gap:1rem;">
            @csrf
            @if($cliente->exists) @method('PUT') @endif

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Nome / Razão Social *</label>
                <input name="nome" value="{{ old('nome', $cliente->nome) }}" required class="form-input" placeholder="Ex: Transportes Silva Ltda">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">CNPJ / CPF</label>
                    <input name="documento" value="{{ old('documento', $cliente->documento) }}" class="form-input" placeholder="00.000.000/0000-00">
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">CEP</label>
                    <input name="cep" value="{{ old('cep', $cliente->cep) }}" class="form-input" placeholder="00000-000">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Telefone</label>
                    <input name="telefone" value="{{ old('telefone', $cliente->telefone) }}" class="form-input" placeholder="(00) 0000-0000">
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">WhatsApp</label>
                    <input name="whatsapp" value="{{ old('whatsapp', $cliente->whatsapp) }}" class="form-input" placeholder="(00) 90000-0000">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Endereço Completo</label>
                <input name="endereco" value="{{ old('endereco', $cliente->endereco) }}" class="form-input" placeholder="Rua, número, complemento, bairro">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">Cidade</label>
                    <input name="cidade" value="{{ old('cidade', $cliente->cidade) }}" class="form-input" placeholder="São Paulo">
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.375rem;">UF</label>
                    <input name="uf" maxlength="2" value="{{ old('uf', $cliente->uf) }}" class="form-input" style="text-transform:uppercase;" placeholder="SP">
                </div>
            </div>

            <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Salvar Cliente</button>
                <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
