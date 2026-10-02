<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        return view('admin.clientes.index', ['clientes' => Cliente::orderBy('nome')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.clientes.form', ['cliente' => new Cliente()]);
    }

    public function store(Request $request)
    {
        Cliente::create($this->validar($request));

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente cadastrado.');
    }

    public function edit(Cliente $cliente)
    {
        return view('admin.clientes.form', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request));

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente atualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente removido.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nome'      => ['required', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:25'],
            'telefone'  => ['nullable', 'string', 'max:30'],
            'whatsapp'  => ['nullable', 'string', 'max:30'],
            'endereco'  => ['nullable', 'string', 'max:255'],
            'cidade'    => ['nullable', 'string', 'max:100'],
            'uf'        => ['nullable', 'string', 'size:2'],
            'cep'       => ['nullable', 'string', 'max:10'],
        ]);
    }
}
