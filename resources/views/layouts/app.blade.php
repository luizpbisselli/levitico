<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Conciliação de Fretes')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
@auth
<nav class="bg-slate-800 text-white px-4 py-3 flex flex-wrap items-center gap-3">
    <span class="font-bold mr-4">🚛 Fretes</span>
    @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="hover:underline text-sm">Dashboard</a>
        <a href="{{ route('admin.documentos.ctes') }}" class="hover:underline text-sm">CT-e</a>
        <a href="{{ route('admin.documentos.nfes') }}" class="hover:underline text-sm">NF-e</a>
        <a href="{{ route('admin.veiculos.index') }}" class="hover:underline text-sm">Veículos</a>
        <a href="{{ route('admin.motoristas.index') }}" class="hover:underline text-sm">Motoristas</a>
        <a href="{{ route('admin.clientes.index') }}" class="hover:underline text-sm">Clientes</a>
        <a href="{{ route('admin.pendencias') }}" class="hover:underline text-sm">Pendências</a>
        <a href="{{ route('admin.modelos.index') }}" class="hover:underline text-sm">WhatsApp</a>
        <a href="{{ route('admin.relatorios') }}" class="hover:underline text-sm">Relatórios</a>
    @else
        <a href="{{ route('motorista.home') }}" class="hover:underline text-sm font-semibold">Minhas entregas</a>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="ml-auto">
        @csrf
        <button class="text-sm bg-slate-600 hover:bg-slate-500 px-3 py-1 rounded">{{ auth()->user()->name }} · Sair</button>
    </form>
</nav>
@endauth

<main class="p-4 max-w-7xl mx-auto">
    @if(session('status'))
        <div class="bg-green-100 border border-green-300 text-green-800 rounded p-3 mb-4">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
