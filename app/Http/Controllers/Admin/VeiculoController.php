<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Veiculo;
use Illuminate\Http\Request;

class VeiculoController extends Controller
{
    public function index()
    {
        return view('admin.veiculos.index', ['veiculos' => Veiculo::orderBy('placa')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.veiculos.form', ['veiculo' => new Veiculo()]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        Veiculo::create($dados);

        return redirect()->route('admin.veiculos.index')->with('status', 'Veículo cadastrado.');
    }

    public function edit(Veiculo $veiculo)
    {
        return view('admin.veiculos.form', compact('veiculo'));
    }

    public function update(Request $request, Veiculo $veiculo)
    {
        $dados = $this->validar($request, $veiculo->id);
        $veiculo->update($dados);

        return redirect()->route('admin.veiculos.index')->with('status', 'Veículo atualizado.');
    }

    public function destroy(Veiculo $veiculo)
    {
        $veiculo->delete();

        return redirect()->route('admin.veiculos.index')->with('status', 'Veículo removido.');
    }

    private function validar(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'placa'    => ['required', 'string', 'max:10', 'unique:veiculos,placa'.($ignoreId ? ','.$ignoreId : '')],
            'tipo'     => ['nullable', 'string', 'max:50'],
            'marca'    => ['nullable', 'string', 'max:50'],
            'modelo'   => ['nullable', 'string', 'max:50'],
            'renavam'  => ['nullable', 'string', 'max:30'],
            'ativo'    => ['boolean'],
        ]) + ['placa' => strtoupper($request->placa), 'ativo' => $request->boolean('ativo', true)];
    }
}
