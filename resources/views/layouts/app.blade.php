<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Conciliação de Fretes')</title>

    {{-- PWA & Mobile Web App Meta Tags --}}
    <meta name="theme-color" content="#1e293b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Fretes">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-512.svg">

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
.mt-0{margin-top:0}.mt-1{margin-top:.25rem}.mt-2{margin-top:.5rem}.mt-3{margin-top:.75rem}.mt-4{margin-top:1rem}.mt-6{margin-top:1.5rem}.mt-16{margin-top:4rem}
.mb-0{margin-bottom:0}.mb-1{margin-bottom:.25rem}.mb-2{margin-bottom:.5rem}.mb-3{margin-bottom:.75rem}.mb-4{margin-bottom:1rem}.mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}
.-mt-4{margin-top:-1rem}
.ml-0{margin-left:0}.ml-1{margin-left:.25rem}.ml-2{margin-left:.5rem}.ml-5{margin-left:1.25rem}.ml-auto{margin-left:auto}
.mr-2{margin-right:.5rem}.mr-3{margin-right:.75rem}.mr-4{margin-right:1rem}
.mx-auto{margin-left:auto;margin-right:auto}
.block{display:block}.inline{display:inline}.inline-block{display:inline-block}.inline-flex{display:inline-flex}.flex{display:flex}.grid{display:grid}.table{display:table}.hidden{display:none}
.flex-1{flex:1 1 0%}.shrink-0{flex-shrink:0}.flex-col{flex-direction:column}.flex-wrap{flex-wrap:wrap}
.items-start{align-items:flex-start}.items-center{align-items:center}
.justify-center{justify-content:center}.justify-end{justify-content:flex-end}.justify-between{justify-content:space-between}
.grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
@media (min-width:768px){.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.md\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}.md\:grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}}
@media (min-width:1024px){.lg\:flex-row{flex-direction:row}.lg\:max-w-4xl{max-width:56rem}.lg\:p-8{padding:2rem}.lg\:p-20{padding:5rem}}
.gap-1{gap:.25rem}.gap-2{gap:.5rem}.gap-3{gap:.75rem}.gap-4{gap:1rem}
.space-y-1>*+*{margin-top:.25rem}.space-y-2>*+*{margin-top:.5rem}.space-y-3>*+*{margin-top:.75rem}.space-y-4>*+*{margin-top:1rem}
.w-full{width:100%}.w-72{width:18rem}.min-h-screen{min-height:100vh}
.max-w-md{max-width:28rem}.max-w-xl{max-width:36rem}.max-w-3xl{max-width:48rem}.max-w-7xl{max-width:80rem}
.max-h-40{max-height:10rem}
.p-1{padding:.25rem}.p-2{padding:.5rem}.p-3{padding:.75rem}.p-4{padding:1rem}.p-5{padding:1.25rem}.p-6{padding:1.5rem}.p-8{padding:2rem}
.px-2{padding-left:.5rem;padding-right:.5rem}.px-3{padding-left:.75rem;padding-right:.75rem}.px-4{padding-left:1rem;padding-right:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}
.py-0\.5{padding-top:.125rem;padding-bottom:.125rem}.py-1{padding-top:.25rem;padding-bottom:.25rem}.py-1\.5{padding-top:.375rem;padding-bottom:.375rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}.py-3{padding-top:.75rem;padding-bottom:.75rem}.py-4{padding-top:1rem;padding-bottom:1rem}
.pt-2{padding-top:.5rem}.pt-3{padding-top:.75rem}.pb-12{padding-bottom:3rem}
.align-top{vertical-align:top}
.border{border-width:1px;border-style:solid}.border-b{border-bottom-width:1px;border-bottom-style:solid}.border-t{border-top-width:1px;border-top-style:solid}
.border-gray-200{border-color:#e5e7eb}.border-gray-300{border-color:#d1d5db}.border-green-300{border-color:#86efac}
.border-yellow-300{border-color:#fde047}.border-red-200{border-color:#fecaca}.border-red-300{border-color:#fca5a1}.border-slate-300{border-color:#cbd5e1}
.rounded{border-radius:.25rem}.rounded-sm{border-radius:.125rem}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:.5rem}
.shadow{box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06)}
.text-3xl{font-size:1.875rem;line-height:2.25rem}
.font-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
.leading-normal{line-height:1.5}
.bg-gray-50{background:#f9fafb}.bg-blue-50{background:#eff6ff}.bg-blue-100{background:#dbeafe}.bg-emerald-500{background:#10b981}.bg-emerald-600{background:#059669}
.bg-amber-600{background:#d97706}.bg-green-50{background:#f0fdf4}.bg-green-600{background:#16a34a}.bg-green-700{background:#15803d}
.bg-red-50{background:#fef2f2}.bg-slate-500{background:#64748b}.hover\:bg-slate-400:hover{background:#94a3b8}
.text-gray-700{color:#374151}.text-gray-800{color:#1f2937}.text-blue-800{color:#1e40af}.text-yellow-700{color:#a16207}
.text-green-700{color:#15803d}.text-red-600{color:#dc2626}
.list-disc{list-style-type:disc}.list-inside{list-style-position:inside}
.bg-gray-200{background:#e5e7eb}.active\:bg-gray-50:active{background:#f9fafb}
.opacity-50{opacity:.5}.opacity-80{opacity:.8}
.line-through{text-decoration-line:line-through}.underline-offset-4{text-underline-offset:4px}
.relative{position:relative}.absolute{position:absolute}.inset-0{inset:0}
.overflow-auto{overflow:auto}
.hover\:bg-slate-500:hover{background:#64748b}.hover\:bg-slate-700:hover{background:#334155}.hover\:bg-blue-700:hover{background:#1d4ed8}
.hover\:bg-red-700:hover{background:#b91c1c}.hover\:bg-emerald-500:hover{background:#10b981}.hover\:underline:hover{text-decoration:underline}
.active\:bg-blue-700:active{background:#1d4ed8}.active\:bg-green-700:active{background:#15803d}
.overflow-x-auto{overflow-x:auto}
.cursor-pointer{cursor:pointer}
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
@auth
<nav class="bg-slate-800 text-white px-4 py-3 flex flex-wrap items-center gap-3">
    <span class="font-bold mr-4 flex items-center gap-1.5">
        <img src="/icons/icon.svg" alt="Logo" class="w-6 h-6 inline-block">
        <span>Fretes</span>
    </span>
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
        <a href="{{ route('admin.usuarios.index') }}" class="hover:underline text-sm">Usuários</a>
        <a href="{{ route('admin.configuracoes') }}" class="hover:underline text-sm">⚙️ Configurações</a>
    @else
        <a href="{{ route('motorista.home') }}" class="hover:underline text-sm font-semibold">Minhas entregas</a>
    @endif
    
    <div class="ml-auto flex items-center gap-2">
        <button id="pwaInstallBtn" class="hidden text-xs bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-2.5 py-1 rounded shadow">📲 Instalar App</button>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-sm bg-slate-600 hover:bg-slate-500 px-3 py-1 rounded">{{ auth()->user()->name }} · Sair</button>
        </form>
    </div>
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

<script>
    // Registro do Service Worker para PWA (Admin + Motorista)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').then((reg) => {
                // Sucesso no registro
            }).catch((err) => {
                console.debug('ServiceWorker falhou:', err);
            });
        });
    }

    // Intercepta e gerencia o botão de instalação nativo do PWA
    let deferredPrompt;
    const installBtn = document.getElementById('pwaInstallBtn');
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (installBtn) {
            installBtn.classList.remove('hidden');
            installBtn.addEventListener('click', async () => {
                installBtn.classList.add('hidden');
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                }
            });
        }
    });

    window.addEventListener('appinstalled', () => {
        if (installBtn) installBtn.classList.add('hidden');
        deferredPrompt = null;
    });
</script>
</body>
</html>
