<?php

namespace App\Http\Controllers\Auth;

use App\Models\AuditoriaLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    private const MAX_TENTATIVAS = 5;
    private const BLOQUEIO_MINUTOS = 10;

    public function form()
    {
        if (Auth::check()) {
            return $this->redirecionarPorPerfil(Auth::user());
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credenciais = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = \App\Models\User::where('email', $credenciais['email'])->first();

        // Limite de tentativas de login (README seção 5)
        if ($user && $user->isLocked()) {
            return back()->withErrors(['email' => 'Conta bloqueada temporariamente por excesso de tentativas. Tente novamente mais tarde.']);
        }

        if (! Auth::attempt($credenciais)) {
            if ($user) {
                $user->login_attempts++;
                if ($user->login_attempts >= self::MAX_TENTATIVAS) {
                    $user->locked_until = now()->addMinutes(self::BLOQUEIO_MINUTOS);
                    $user->login_attempts = 0;
                }
                $user->save();
            }

            return back()->withInput()->withErrors(['email' => 'Credenciais inválidas.']);
        }

        $request->session()->regenerate();
        $user = Auth::user();
        $user->forceFill(['login_attempts' => 0, 'locked_until' => null])->save();

        AuditoriaLog::create(['user_id' => $user->id, 'acao' => 'login', 'ip' => $request->ip()]);

        return $this->redirecionarPorPerfil($user);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }

    private function redirecionarPorPerfil($user)
    {
        return $user->isMotorista()
            ? redirect()->route('motorista.home')
            : redirect()->route('admin.dashboard');
    }
}
