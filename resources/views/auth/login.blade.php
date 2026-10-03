@extends('layouts.app')
@section('title', 'Entrar')
@section('content')
<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100dvh - 6rem);padding:1rem">
    <div class="card" style="width:100%;max-width:24rem">
        <div class="card-body" style="padding:2rem">
            <div style="text-align:center;margin-bottom:1.5rem">
                <img src="/icons/icon.svg" alt="Logo" style="width:48px;height:48px;margin:0 auto .75rem">
                <h1 style="font-size:1.25rem;font-weight:700;color:var(--c-text)">Entrar no sistema</h1>
                <p style="font-size:.875rem;color:var(--c-text-muted);margin-top:.25rem">Conciliação de Fretes</p>
            </div>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div style="margin-bottom:1rem">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="form-input" placeholder="seu@email.com" autocomplete="email">
                </div>
                <div style="margin-bottom:1.25rem">
                    <label class="form-label">Senha</label>
                    <input type="password" name="password" required
                           class="form-input" placeholder="••••••••" autocomplete="current-password">
                </div>
                <button class="btn btn-dark btn-block btn-lg">Entrar</button>
            </form>
        </div>
    </div>
</div>
@endsection
