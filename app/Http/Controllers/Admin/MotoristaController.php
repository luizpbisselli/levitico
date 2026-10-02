<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Motorista;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MotoristaController extends Controller
{
    public function index()
    {
        return view('admin.motoristas.index', [
            'motoristas' => Motorista::with('veiculos')->orderBy('nome')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.motoristas.form', [
            'motorista' => new Motorista(),
            'veiculos'  => Veiculo::orderBy('placa')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);

        $motorista = Motorista::create(collect($dados)->except(['veiculos', 'email', 'password', 'criar_login'])->all());

        // Cria o login do motorista quando solicitado
        if ($request->boolean('criar_login') && $request->filled('email')) {
            $user = User::create([
                'name'     => $dados['nome'],
                'email'    => $dados['email'],
                'password' => Hash::make($dados['password']),
                'profile'  => User::PROFILE_MOTORISTA,
            ]);
            $motorista->update(['user_id' => $user->id]);
        }

        $motorista->veiculos()->sync($dados['veiculos'] ?? []);

        return redirect()->route('admin.motoristas.index')->with('status', 'Motorista cadastrado.');
    }

    public function edit(Motorista $motorista)
    {
        return view('admin.motoristas.form', [
            'motorista' => $motorista,
            'veiculos'  => Veiculo::orderBy('placa')->get(),
        ]);
    }

    public function update(Request $request, Motorista $motorista)
    {
        $dados = $this->validar($request, $motorista->id);
        $motorista->update(collect($dados)->except(['veiculos', 'email', 'password', 'criar_login'])->all());
        $motorista->veiculos()->sync($dados['veiculos'] ?? []);

        return redirect()->route('admin.motoristas.index')->with('status', 'Motorista atualizado.');
    }

    public function destroy(Motorista $motorista)
    {
        $motorista->delete();

        return redirect()->route('admin.motoristas.index')->with('status', 'Motorista removido.');
    }

    private function validar(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nome'        => ['required', 'string', 'max:120'],
            'cnh'         => ['nullable', 'string', 'max:30'],
            'telefone'    => ['nullable', 'string', 'max:30'],
            'agregado'    => ['boolean'],
            'veiculos'    => ['array'],
            'veiculos.*'  => ['integer', 'exists:veiculos,id'],
            'email'       => ['nullable', 'email', 'unique:users,email'],
            'password'    => ['nullable', 'string', 'min:8', 'required_if:criar_login,1'],
            'criar_login' => ['boolean'],
        ]) + ['agregado' => $request->boolean('agregado')];
    }
}
