@extends('layouts.app')
@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $veiculo->exists ? '✏️ Editar veículo' : '🚛 Novo veículo' }}</h1>
</div>
<form method="POST" action="{{ $veiculo->exists ? route('admin.veiculos.update', $veiculo) : route('admin.veiculos.store') }}"
      class="card" style="max-width:36rem">
    <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
        @csrf
        @if($veiculo->exists) @method('PUT') @endif
        <div>
            <label class="form-label">Placa *</label>
            <input name="placa" value="{{ old('placa', $veiculo->placa) }}" required class="form-input uppercase">
        </div>
        <div>
            <label class="form-label">Tipo (Truck, Carreta, VUC...)</label>
            <input name="tipo" value="{{ old('tipo', $veiculo->tipo) }}" class="form-input">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div>
                <label class="form-label">Marca</label>
                <input name="marca" value="{{ old('marca', $veiculo->marca) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Modelo</label>
                <input name="modelo" value="{{ old('modelo', $veiculo->modelo) }}" class="form-input">
            </div>
        </div>
        <div>
            <label class="form-label">Renavam</label>
            <input name="renavam" value="{{ old('renavam', $veiculo->renavam) }}" class="form-input">
        </div>
        <label style="display:inline-flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500">
            <input type="checkbox" name="ativo" value="1" {{ old('ativo', $veiculo->ativo ?? true) ? 'checked' : '' }}> Ativo
        </label>
        <div><button class="btn btn-dark">Salvar</button></div>
    </div>
</form>
@endsection
