@extends('layouts.app')
@section('title', 'Entrar')
@section('content')
<div class="max-w-md mx-auto mt-16 bg-white rounded-lg shadow p-8">
    <h1 class="text-xl font-bold mb-6">Entrar no sistema</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label class="block mb-1 text-sm font-medium">E-mail</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full border rounded p-2 mb-4">
        <label class="block mb-1 text-sm font-medium">Senha</label>
        <input type="password" name="password" required class="w-full border rounded p-2 mb-4">
        <button class="w-full bg-slate-800 text-white rounded p-2 hover:bg-slate-700">Entrar</button>
    </form>
</div>
@endsection
