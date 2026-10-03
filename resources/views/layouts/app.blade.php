<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Conciliação de Fretes')</title>
    {{-- Tailwind servido localmente: o CDN viola a CSP da página de login (script-src 'self') --}}
    <style>
*,::before,::after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}
html{line-height:1.5;-webkit-text-size-adjust:100%}body{margin:0;line-height:inherit}
h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}
button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}
button,select{text-transform:none}[type=submit],button{-webkit-appearance:button;appearance:button;cursor:pointer}
[hidden]{display:none}img,svg{display:block;vertical-align:middle}img{max-width:100%;height:auto}
table{border-collapse:collapse;border-color:inherit;text-indent:0}
body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.bg-gray-100{background:#f3f4f6}.bg-white{background:#fff}.bg-slate-50{background:#f8fafc}.bg-slate-100{background:#f1f5f9}
.bg-slate-200{background:#e2e8f0}.bg-slate-600{background:#475569}.bg-slate-700{background:#334155}.bg-slate-800{background:#1e293b}
.bg-blue-50{background:#eff6ff}.bg-blue-600{background:#2563eb}.bg-green-100{background:#dcfce7}.bg-yellow-50{background:#fefce8}
.bg-yellow-100{background:#fef9c3}.bg-red-100{background:#fee2e2}.bg-red-600{background:#dc2626}
.text-white{color:#fff}.text-gray-500{color:#6b7280}.text-gray-600{color:#4b5563}.text-slate-500{color:#64748b}
.text-slate-600{color:#475569}.text-slate-700{color:#334155}.text-slate-800{color:#1e293b}.text-slate-900{color:#0f172a}
.text-blue-700{color:#1d4ed8}.text-green-800{color:#166534}.text-yellow-800{color:#854d0e}.text-red-700{color:#b91c1c}
.text-red-800{color:#991b1b}.text-xs{font-size:.75rem;line-height:1rem}.text-sm{font-size:.875rem;line-height:1.25rem}
.text-base{font-size:1rem;line-height:1.5rem}.text-lg{font-size:1.125rem;line-height:1.75rem}.text-xl{font-size:1.25rem;line-height:1.75rem}
.text-2xl{font-size:1.5rem;line-height:2rem}
.font-normal{font-weight:400}.font-medium{font-weight:500}.font-semibold{font-weight:600}.font-bold{font-weight:700}
.uppercase{text-transform:uppercase}.italic{font-style:italic}.underline{text-decoration:underline}
.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}
.mt-0{margin-top:0}.mt-1{margin-top:.25rem}.mt-2{margin-top:.5rem}.mt-4{margin-top:1rem}.mt-6{margin-top:1.5rem}.mt-16{margin-top:4rem}
.mb-0{margin-bottom:0}.mb-1{margin-bottom:.25rem}.mb-2{margin-bottom:.5rem}.mb-3{margin-bottom:.75rem}.mb-4{margin-bottom:1rem}.mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}
.ml-auto{margin-left:auto}.mr-2{margin-right:.5rem}.mr-4{margin-right:1rem}
.mx-auto{margin-left:auto;margin-right:auto}
.block{display:block}.inline-block{display:inline-block}.flex{display:flex}.table{display:table}.hidden{display:none}
.flex-wrap{flex-wrap:wrap}.items-center{align-items:center}.justify-between{justify-content:space-between}
.gap-2{gap:.5rem}.gap-3{gap:.75rem}.w-full{width:100%}.min-h-screen{min-height:100vh}
.max-w-md{max-width:28rem}.max-w-7xl{max-width:80rem}
.p-1{padding:.25rem}.p-2{padding:.5rem}.p-3{padding:.75rem}.p-4{padding:1rem}.p-6{padding:1.5rem}.p-8{padding:2rem}
.px-2{padding-left:.5rem;padding-right:.5rem}.px-3{padding-left:.75rem;padding-right:.75rem}.px-4{padding-left:1rem;padding-right:1rem}
.py-1{padding-top:.25rem;padding-bottom:.25rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}.py-3{padding-top:.75rem;padding-bottom:.75rem}
.pt-2{padding-top:.5rem}.border{border-width:1px;border-style:solid}.border-b{border-bottom-width:1px;border-bottom-style:solid}
.border-gray-200{border-color:#e5e7eb}.border-gray-300{border-color:#d1d5db}.border-green-300{border-color:#86efac}
.border-yellow-300{border-color:#fde047}.border-red-300{border-color:#fca5a1}.border-slate-300{border-color:#cbd5e1}
.rounded{border-radius:.25rem}.rounded-lg{border-radius:.5rem}.shadow{box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06)}
.hover\:bg-slate-500:hover{background:#64748b}.hover\:bg-slate-700:hover{background:#334155}.hover\:bg-blue-700:hover{background:#1d4ed8}
.hover\:bg-red-700:hover{background:#b91c1c}.hover\:underline:hover{text-decoration:underline}
.overflow-x-auto{overflow-x:auto}
    </style>
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
        <a href="{{ route('admin.configuracoes') }}" class="hover:underline text-sm">⚙️ Configurações</a>
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
    @foreach(['sucesso_banco', 'sucesso_email'] as $k)
        @if(session($k))
            <div class="bg-green-100 border border-green-300 text-green-800 rounded p-3 mb-4">{{ session($k) }}</div>
        @endif
    @endforeach
    @foreach(['erro_banco', 'erro_email'] as $k)
        @if(session($k))
            <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4">{{ session($k) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
