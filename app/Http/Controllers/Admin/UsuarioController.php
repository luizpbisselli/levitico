<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Cadastro e administração dos usuários do sistema (perfis admin/motorista).
 */
class UsuarioController extends Controller
{
    public function index()
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::orderBy('profile')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.usuarios.form', ['usuario' => new User()]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'profile'  => ['required', Rule::in([User::PROFILE_ADMIN, User::PROFILE_MOTORISTA])],
        ]);

        User::create($dados); // o cast 'hashed' já criptografa a senha

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuário cadastrado.');
    }

    public function edit(User $usuario)
    {
        return view('admin.usuarios.form', ['usuario' => $usuario]);
    }

    public function update(Request $request, User $usuario)
    {
        $dados = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'profile'  => ['required', Rule::in([User::PROFILE_ADMIN, User::PROFILE_MOTORISTA])],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Proteção contra lockout: não permite remover/despromover o próprio acesso admin
        if ($request->user()->id === $usuario->id && $dados['profile'] !== User::PROFILE_ADMIN) {
            return back()->withErrors(['profile' => 'Você não pode remover seu próprio perfil de administrador.']);
        }

        if ($usuario->isAdmin() && $dados['profile'] !== User::PROFILE_ADMIN && User::admins()->count() <= 1) {
            return back()->withErrors(['profile' => 'O sistema precisa ter ao menos um administrador.']);
        }

        if (empty($dados['password'])) {
            unset($dados['password']);
        }

        $usuario->update($dados);

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $usuario)
    {
        if ($request->user()->id === $usuario->id) {
            return back()->withErrors(['usuarios' => 'Você não pode excluir o próprio usuário.']);
        }

        if ($usuario->isAdmin() && User::admins()->count() <= 1) {
            return back()->withErrors(['usuarios' => 'O sistema precisa ter ao menos um administrador.']);
        }

        // Desvincula o login do motorista, se houver cadastro vinculado
        $usuario->motorista()?->update(['user_id' => null]);
        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuário removido.');
    }
}
