<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Conciliação de Fretes')</title>

    {{-- CSRF Token para chamadas assíncronas --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PWA & Mobile Web App Meta Tags --}}
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Fretes">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-512.svg">

    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
/* ===== RESET & BASE ===== */
*,::before,::after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}
html{line-height:1.5;-webkit-text-size-adjust:100%;-webkit-tap-highlight-color:transparent;scroll-behavior:smooth}
body{margin:0;line-height:inherit;font-family:'Inter',ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
     -webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}
a{color:inherit;text-decoration:inherit}
button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}
button,select{text-transform:none}[type=submit],button{-webkit-appearance:button;appearance:button;cursor:pointer}
[hidden]{display:none}img,svg{display:block;vertical-align:middle}img{max-width:100%;height:auto}
table{border-collapse:collapse;border-color:inherit;text-indent:0}

/* ===== DESIGN TOKENS ===== */
:root{
  --c-bg:#f1f5f9;--c-surface:#ffffff;--c-surface-hover:#f8fafc;
  --c-nav:#0f172a;--c-nav-light:#1e293b;--c-nav-border:#334155;
  --c-primary:#3b82f6;--c-primary-hover:#2563eb;
  --c-success:#10b981;--c-success-hover:#059669;
  --c-warning:#f59e0b;--c-danger:#ef4444;--c-danger-hover:#dc2626;
  --c-text:#0f172a;--c-text-muted:#64748b;--c-text-light:#94a3b8;
  --c-border:#e2e8f0;--c-border-dark:#cbd5e1;
  --radius:0.75rem;--radius-sm:0.5rem;--radius-full:9999px;
  --shadow-sm:0 1px 2px rgba(0,0,0,.05);
  --shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06);
  --shadow-md:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -2px rgba(0,0,0,.1);
  --shadow-lg:0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -4px rgba(0,0,0,.1);
  --safe-bottom:env(safe-area-inset-bottom,0px);
  --nav-h:3.5rem;
  --transition:150ms cubic-bezier(.4,0,.2,1);
}

/* ===== LAYOUT ===== */
.app-bg{background:var(--c-bg);min-height:100vh;min-height:100dvh}
.app-main{padding:1rem;padding-bottom:calc(1rem + var(--safe-bottom));max-width:80rem;margin:0 auto;width:100%}
@media(min-width:768px){.app-main{padding:1.5rem 2rem}}

/* ===== TYPOGRAPHY ===== */
.text-xs{font-size:.75rem;line-height:1rem}.text-sm{font-size:.875rem;line-height:1.25rem}
.text-base{font-size:1rem;line-height:1.5rem}.text-lg{font-size:1.125rem;line-height:1.75rem}
.text-xl{font-size:1.25rem;line-height:1.75rem}.text-2xl{font-size:1.5rem;line-height:2rem}
.text-3xl{font-size:1.875rem;line-height:2.25rem}
.font-normal{font-weight:400}.font-medium{font-weight:500}.font-semibold{font-weight:600}.font-bold{font-weight:700}.font-extrabold{font-weight:800}
.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}
.uppercase{text-transform:uppercase}.italic{font-style:italic}
.underline{text-decoration:underline}.line-through{text-decoration-line:line-through}
.underline-offset-4{text-underline-offset:4px}
.font-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
.leading-normal{line-height:1.5}.leading-tight{line-height:1.25}
.tracking-tight{letter-spacing:-.025em}

/* ===== COLORS ===== */
.bg-surface{background:var(--c-surface)}.bg-page{background:var(--c-bg)}
.bg-white{background:#fff}.bg-gray-50{background:#f9fafb}.bg-gray-100{background:#f3f4f6}.bg-gray-200{background:#e5e7eb}
.bg-slate-50{background:#f8fafc}.bg-slate-100{background:#f1f5f9}.bg-slate-200{background:#e2e8f0}
.bg-slate-500{background:#64748b}.bg-slate-600{background:#475569}.bg-slate-700{background:#334155}.bg-slate-800{background:#1e293b}
.bg-blue-50{background:#eff6ff}.bg-blue-100{background:#dbeafe}.bg-blue-600{background:#2563eb}
.bg-green-50{background:#f0fdf4}.bg-green-100{background:#dcfce7}.bg-green-600{background:#16a34a}.bg-green-700{background:#15803d}
.bg-yellow-50{background:#fefce8}.bg-yellow-100{background:#fef9c3}
.bg-red-50{background:#fef2f2}.bg-red-100{background:#fee2e2}.bg-red-600{background:#dc2626}
.bg-emerald-50{background:#ecfdf5}.bg-emerald-500{background:#10b981}.bg-emerald-600{background:#059669}
.bg-amber-600{background:#d97706}
.text-white{color:#fff}.text-gray-500{color:#6b7280}.text-gray-600{color:#4b5563}.text-gray-700{color:#374151}.text-gray-800{color:#1f2937}
.text-slate-500{color:#64748b}.text-slate-600{color:#475569}.text-slate-700{color:#334155}.text-slate-800{color:#1e293b}.text-slate-900{color:#0f172a}
.text-blue-700{color:#1d4ed8}.text-blue-800{color:#1e40af}
.text-green-700{color:#15803d}.text-green-800{color:#166534}
.text-yellow-700{color:#a16207}.text-yellow-800{color:#854d0e}
.text-red-600{color:#dc2626}.text-red-700{color:#b91c1c}.text-red-800{color:#991b1b}

/* ===== SPACING ===== */
.mt-0{margin-top:0}.mt-1{margin-top:.25rem}.mt-2{margin-top:.5rem}.mt-3{margin-top:.75rem}.mt-4{margin-top:1rem}.mt-6{margin-top:1.5rem}.mt-16{margin-top:4rem}
.mb-0{margin-bottom:0}.mb-1{margin-bottom:.25rem}.mb-2{margin-bottom:.5rem}.mb-3{margin-bottom:.75rem}.mb-4{margin-bottom:1rem}.mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}
.-mt-4{margin-top:-1rem}
.ml-0{margin-left:0}.ml-1{margin-left:.25rem}.ml-2{margin-left:.5rem}.ml-5{margin-left:1.25rem}.ml-auto{margin-left:auto}
.mr-2{margin-right:.5rem}.mr-3{margin-right:.75rem}.mr-4{margin-right:1rem}
.mx-auto{margin-left:auto;margin-right:auto}
.p-1{padding:.25rem}.p-2{padding:.5rem}.p-3{padding:.75rem}.p-4{padding:1rem}.p-5{padding:1.25rem}.p-6{padding:1.5rem}.p-8{padding:2rem}
.px-2{padding-left:.5rem;padding-right:.5rem}.px-3{padding-left:.75rem;padding-right:.75rem}.px-4{padding-left:1rem;padding-right:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}
.py-0\.5{padding-top:.125rem;padding-bottom:.125rem}.py-1{padding-top:.25rem;padding-bottom:.25rem}
.py-1\.5{padding-top:.375rem;padding-bottom:.375rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}
.py-3{padding-top:.75rem;padding-bottom:.75rem}.py-4{padding-top:1rem;padding-bottom:1rem}
.pt-2{padding-top:.5rem}.pt-3{padding-top:.75rem}.pb-12{padding-bottom:3rem}

/* ===== DISPLAY & FLEX ===== */
.block{display:block}.inline{display:inline}.inline-block{display:inline-block}.inline-flex{display:inline-flex}
.flex{display:flex}.grid{display:grid}.table{display:table}.hidden{display:none}
.flex-1{flex:1 1 0%}.shrink-0{flex-shrink:0}.flex-col{flex-direction:column}.flex-wrap{flex-wrap:wrap}
.items-start{align-items:flex-start}.items-center{align-items:center}
.justify-center{justify-content:center}.justify-end{justify-content:flex-end}.justify-between{justify-content:space-between}
.grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.gap-1{gap:.25rem}.gap-2{gap:.5rem}.gap-3{gap:.75rem}.gap-4{gap:1rem}
.space-y-1>*+*{margin-top:.25rem}.space-y-2>*+*{margin-top:.5rem}.space-y-3>*+*{margin-top:.75rem}.space-y-4>*+*{margin-top:1rem}
.w-full{width:100%}.w-72{width:18rem}.min-h-screen{min-height:100vh;min-height:100dvh}
.max-w-md{max-width:28rem}.max-w-xl{max-width:36rem}.max-w-3xl{max-width:48rem}.max-w-7xl{max-width:80rem}
.max-h-40{max-height:10rem}
.align-top{vertical-align:top}
.relative{position:relative}.absolute{position:absolute}.fixed{position:fixed}.inset-0{inset:0}
.overflow-auto{overflow:auto}.overflow-x-auto{overflow-x:auto}.overflow-hidden{overflow:hidden}
.cursor-pointer{cursor:pointer}
.opacity-50{opacity:.5}.opacity-80{opacity:.8}
.list-disc{list-style-type:disc}.list-inside{list-style-position:inside}
.border{border-width:1px;border-style:solid}.border-b{border-bottom-width:1px;border-bottom-style:solid}.border-t{border-top-width:1px;border-top-style:solid}
.border-gray-200{border-color:#e5e7eb}.border-gray-300{border-color:#d1d5db}
.border-green-300{border-color:#86efac}.border-yellow-300{border-color:#fde047}
.border-red-200{border-color:#fecaca}.border-red-300{border-color:#fca5a1}.border-slate-300{border-color:#cbd5e1}
.rounded{border-radius:.25rem}.rounded-sm{border-radius:.125rem}.rounded-lg{border-radius:var(--radius)}.rounded-full{border-radius:var(--radius-full)}
.shadow{box-shadow:var(--shadow)}.shadow-sm{box-shadow:var(--shadow-sm)}.shadow-md{box-shadow:var(--shadow-md)}.shadow-lg{box-shadow:var(--shadow-lg)}
.active\:bg-gray-50:active{background:#f9fafb}
.active\:bg-blue-700:active{background:#1d4ed8}.active\:bg-green-700:active{background:#15803d}

/* ===== RESPONSIVE GRID ===== */
@media(min-width:768px){
  .md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
  .md\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
  .md\:grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}
}
@media(min-width:1024px){
  .lg\:flex-row{flex-direction:row}
  .lg\:max-w-4xl{max-width:56rem}
  .lg\:p-8{padding:2rem}.lg\:p-20{padding:5rem}
}

/* ===== HOVER STATES ===== */
.hover\:underline:hover{text-decoration:underline}
.hover\:bg-slate-400:hover{background:#94a3b8}.hover\:bg-slate-500:hover{background:#64748b}
.hover\:bg-slate-700:hover{background:#334155}.hover\:bg-blue-700:hover{background:#1d4ed8}
.hover\:bg-red-700:hover{background:#b91c1c}.hover\:bg-emerald-500:hover{background:#10b981}
.hover\:bg-emerald-700:hover{background:#047857}

/* ===== TOP NAVBAR ===== */
.top-nav{
  background:var(--c-nav);color:#fff;
  height:var(--nav-h);
  padding:0 1rem;
  display:flex;align-items:center;gap:.75rem;
  position:sticky;top:0;z-index:40;
  box-shadow:0 1px 3px rgba(0,0,0,.3);
}
.top-nav .brand{display:flex;align-items:center;gap:.5rem;font-weight:700;font-size:1.05rem;flex-shrink:0;white-space:nowrap}
.top-nav .brand img{width:26px;height:26px;min-width:26px;object-fit:contain}
.top-nav .nav-actions{margin-left:auto;display:flex;align-items:center;gap:.5rem}

/* Desktop nav links (hidden on mobile) */
.nav-links{display:none;align-items:center;gap:.125rem;flex:1;overflow-x:auto;padding:0 .5rem}
.nav-links a{
  font-size:.8125rem;font-weight:500;color:#cbd5e1;padding:.375rem .625rem;border-radius:var(--radius-sm);
  white-space:nowrap;transition:background var(--transition),color var(--transition);
}
.nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,.1);color:#fff}
@media(min-width:1024px){
  .nav-links{display:flex}
  .hamburger-btn{display:none !important}
}

/* ===== HAMBURGER BUTTON ===== */
.hamburger-btn{
  display:flex;align-items:center;justify-content:center;
  width:2.25rem;height:2.25rem;border-radius:var(--radius-sm);
  background:transparent;color:#fff;border:none;cursor:pointer;
  transition:background var(--transition);flex-shrink:0;
}
.hamburger-btn:hover{background:rgba(255,255,255,.1)}
.hamburger-btn svg{width:1.25rem;height:1.25rem}

/* ===== SLIDE-IN DRAWER (MOBILE NAV) ===== */
.drawer-overlay{
  position:fixed;inset:0;z-index:50;
  background:rgba(0,0,0,.5);backdrop-filter:blur(4px);
  opacity:0;pointer-events:none;
  transition:opacity 250ms ease;
}
.drawer-overlay.open{opacity:1;pointer-events:auto}

.drawer{
  position:fixed;top:0;left:0;bottom:0;z-index:51;
  width:min(80vw,20rem);
  background:var(--c-nav);color:#fff;
  transform:translateX(-100%);
  transition:transform 300ms cubic-bezier(.32,.72,0,1);
  display:flex;flex-direction:column;
  padding-top:env(safe-area-inset-top,0px);
  box-shadow:4px 0 24px rgba(0,0,0,.3);
  overflow-y:auto;-webkit-overflow-scrolling:touch;
}
.drawer.open{transform:translateX(0)}

.drawer-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:1rem 1.25rem;border-bottom:1px solid var(--c-nav-border);
}
.drawer-header .brand{display:flex;align-items:center;gap:.5rem;font-weight:700;font-size:1.1rem}
.drawer-header .brand img{width:28px;height:28px}
.drawer-close{
  background:transparent;border:none;color:#94a3b8;cursor:pointer;
  width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;
  border-radius:var(--radius-sm);transition:background var(--transition),color var(--transition);
}
.drawer-close:hover{background:rgba(255,255,255,.1);color:#fff}

.drawer-nav{padding:.75rem 0;flex:1}
.drawer-nav a{
  display:flex;align-items:center;gap:.75rem;
  padding:.75rem 1.25rem;font-size:.9375rem;font-weight:500;
  color:#cbd5e1;transition:background var(--transition),color var(--transition);
}
.drawer-nav a:hover,.drawer-nav a:active{background:rgba(255,255,255,.08);color:#fff}
.drawer-nav a .nav-icon{width:1.25rem;text-align:center;font-size:1rem;flex-shrink:0}
.drawer-nav .nav-section{font-size:.6875rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#64748b;padding:.75rem 1.25rem .25rem}

.drawer-footer{
  padding:.75rem 1.25rem;border-top:1px solid var(--c-nav-border);
  margin-top:auto;
}

/* ===== EMAIL SYNC BADGE ===== */
.sync-badge{
  display:inline-flex;align-items:center;gap:.375rem;
  font-size:.6875rem;background:rgba(255,255,255,.08);
  padding:.3rem .625rem;border-radius:var(--radius-full);
  border:1px solid rgba(255,255,255,.1);
  white-space:nowrap;
}
.sync-pulse{width:.5rem;height:.5rem;border-radius:50%;flex-shrink:0;transition:background var(--transition)}
.sync-pulse.ok{background:#34d399}
.sync-pulse.busy{background:#fbbf24;animation:pulse-anim 1s ease-in-out infinite}
.sync-pulse.err{background:#f87171}
.sync-pulse.off{background:#64748b}
@keyframes pulse-anim{0%,100%{opacity:1}50%{opacity:.4}}

/* ===== CARDS & SURFACES ===== */
.card{background:var(--c-surface);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.card-body{padding:1rem}
@media(min-width:768px){.card-body{padding:1.25rem}}

/* ===== RESPONSIVE TABLE → CARDS ===== */
.resp-table{width:100%;background:var(--c-surface);border-radius:var(--radius);box-shadow:var(--shadow);font-size:.875rem;overflow:hidden}
.resp-table thead tr{text-align:left;border-bottom:2px solid var(--c-border);background:#f8fafc}
.resp-table th{padding:.75rem 1rem;font-weight:600;color:var(--c-text);font-size:.8125rem;text-transform:uppercase;letter-spacing:.03em}
.resp-table td{padding:.75rem 1rem}
.resp-table tbody tr{border-bottom:1px solid var(--c-border);transition:background var(--transition)}
.resp-table tbody tr:last-child{border-bottom:none}
.resp-table tbody tr:hover{background:var(--c-surface-hover)}

/* On mobile, make tables scrollable horizontally */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border-radius:var(--radius);box-shadow:var(--shadow)}
.table-wrap .resp-table{box-shadow:none;border-radius:0}

/* ===== BUTTONS ===== */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:.5rem;
  padding:.625rem 1.25rem;border-radius:var(--radius-sm);font-weight:600;
  font-size:.875rem;line-height:1.25rem;
  transition:background var(--transition),transform 80ms ease,box-shadow var(--transition);
  cursor:pointer;border:none;white-space:nowrap;
  -webkit-tap-highlight-color:transparent;
}
.btn:active{transform:scale(.97)}
.btn-primary{background:var(--c-primary);color:#fff}.btn-primary:hover{background:var(--c-primary-hover)}
.btn-success{background:var(--c-success);color:#fff}.btn-success:hover{background:var(--c-success-hover)}
.btn-danger{background:var(--c-danger);color:#fff}.btn-danger:hover{background:var(--c-danger-hover)}
.btn-dark{background:var(--c-nav-light);color:#fff}.btn-dark:hover{background:#273548}
.btn-ghost{background:transparent;color:var(--c-text-muted);border:1px solid var(--c-border)}.btn-ghost:hover{background:var(--c-surface-hover)}
.btn-sm{padding:.375rem .75rem;font-size:.8125rem}
.btn-lg{padding:.875rem 1.5rem;font-size:1rem}
.btn-block{width:100%}

/* ===== FORM INPUTS ===== */
.form-input,.form-select,.form-textarea{
  width:100%;border:1px solid var(--c-border);border-radius:var(--radius-sm);
  padding:.625rem .75rem;font-size:.9375rem;line-height:1.5;
  background:var(--c-surface);color:var(--c-text);
  transition:border-color var(--transition),box-shadow var(--transition);
  -webkit-appearance:none;appearance:none;
}
.form-input:focus,.form-select:focus,.form-textarea:focus{
  outline:none;border-color:var(--c-primary);
  box-shadow:0 0 0 3px rgba(59,130,246,.15);
}
.form-label{display:block;font-size:.875rem;font-weight:500;margin-bottom:.375rem;color:var(--c-text)}

/* Touch-friendly sizing on mobile */
@media(max-width:767px){
  .form-input,.form-select,.form-textarea{font-size:1rem;padding:.75rem}
}

/* File input styling */
.form-file{width:100%;font-size:.8125rem;color:var(--c-text-muted)}
.form-file::file-selector-button{
  margin-right:.75rem;padding:.5rem 1rem;border-radius:var(--radius-sm);
  border:none;font-size:.8125rem;font-weight:600;cursor:pointer;
  background:#eff6ff;color:#1d4ed8;transition:background var(--transition);
}
.form-file::file-selector-button:hover{background:#dbeafe}

/* ===== ALERT TOASTS ===== */
.alert{padding:.75rem 1rem;border-radius:var(--radius-sm);font-size:.875rem;margin-bottom:1rem;border:1px solid}
.alert-success{background:#f0fdf4;border-color:#86efac;color:#166534}
.alert-danger{background:#fef2f2;border-color:#fca5a1;color:#991b1b}
.alert-warning{background:#fefce8;border-color:#fde047;color:#854d0e}

/* ===== FLOATING TOAST ===== */
.toast{
  position:fixed;bottom:1.25rem;right:1.25rem;z-index:60;
  background:var(--c-nav);color:#fff;border:1px solid rgba(16,185,129,.3);
  padding:.875rem 1.25rem;border-radius:var(--radius);
  box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:.75rem;
  transform:translateY(0);opacity:1;
  transition:transform 300ms ease,opacity 300ms ease;
  max-width:calc(100vw - 2.5rem);
}
.toast.hidden{transform:translateY(1rem);opacity:0;pointer-events:none}

/* ===== STAT CARDS (DASHBOARD) ===== */
.stat-card{
  background:var(--c-surface);border-radius:var(--radius);box-shadow:var(--shadow);
  padding:1rem;transition:transform var(--transition),box-shadow var(--transition);
}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.stat-value{font-size:1.75rem;font-weight:800;color:var(--c-text);line-height:1.2}
.stat-label{font-size:.8125rem;color:var(--c-text-muted);margin-top:.25rem;font-weight:500}

/* ===== BADGE ===== */
.badge{
  display:inline-flex;align-items:center;padding:.125rem .5rem;
  border-radius:var(--radius-full);font-size:.75rem;font-weight:600;
  line-height:1.25rem;white-space:nowrap;
}
.badge-blue{background:#dbeafe;color:#1e40af}
.badge-green{background:#dcfce7;color:#166534}
.badge-yellow{background:#fef9c3;color:#854d0e}
.badge-red{background:#fee2e2;color:#991b1b}
.badge-slate{background:#e2e8f0;color:#334155}

/* ===== PAGE HEADER ===== */
.page-header{
  display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;
  margin-bottom:1.25rem;
}
.page-title{font-size:1.375rem;font-weight:700;color:var(--c-text);letter-spacing:-.01em}
@media(min-width:768px){.page-title{font-size:1.5rem}}

/* ===== ENTREGA CARD (MOTORISTA) ===== */
.entrega-card{
  display:block;background:var(--c-surface);border-radius:var(--radius);
  box-shadow:var(--shadow);padding:1rem;margin-bottom:.75rem;
  transition:background var(--transition),transform 80ms ease;
  -webkit-tap-highlight-color:transparent;border-left:4px solid transparent;
}
.entrega-card:active{transform:scale(.985);background:var(--c-surface-hover)}
.entrega-card.status-transit{border-left-color:var(--c-primary)}
.entrega-card.status-pending{border-left-color:var(--c-warning)}
.entrega-card.status-done{border-left-color:var(--c-success)}
.entrega-card.status-issue{border-left-color:var(--c-danger)}

/* ===== PWA INSTALL BUTTON ===== */
.pwa-install-btn{
  font-size:.75rem;background:var(--c-success);color:#fff;
  font-weight:600;padding:.3rem .625rem;border-radius:var(--radius-full);
  border:none;cursor:pointer;white-space:nowrap;
  transition:background var(--transition);box-shadow:var(--shadow-sm);
}
.pwa-install-btn:hover{background:var(--c-success-hover)}

/* ===== USER MENU ===== */
.user-btn{
  font-size:.8125rem;background:var(--c-nav-light);color:#e2e8f0;
  font-weight:500;padding:.375rem .75rem;border-radius:var(--radius-sm);
  border:none;cursor:pointer;transition:background var(--transition);
  white-space:nowrap;
}
.user-btn:hover{background:#475569}

/* ===== ANIMATIONS ===== */
@keyframes fadeInUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.animate-in{animation:fadeInUp 350ms ease forwards}

/* ===== MISC UTILITY ===== */
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}
.truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.w-5{width:1.25rem}.w-6{width:1.5rem}.h-5{height:1.25rem}.h-6{height:1.5rem}
.w-2{width:.5rem}.h-2{height:.5rem}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:#94a3b8}

/* ===== PRINT ===== */
@media print{.top-nav,.drawer,.drawer-overlay,.toast,.hamburger-btn,.pwa-install-btn,.sync-badge{display:none!important}}
    </style>
</head>
<body class="app-bg">
@auth
{{-- ===== TOP NAVBAR ===== --}}
<nav class="top-nav" id="topNav">
    {{-- Hamburger (mobile only) --}}
    <button class="hamburger-btn" id="hamburgerBtn" aria-label="Abrir menu">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Brand --}}
    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('motorista.home') }}" class="brand">
        <img src="/icons/icon.svg" alt="Logo">
        <span>Fretes</span>
    </a>

    {{-- Desktop nav links --}}
    @if(auth()->user()->isAdmin())
    <div class="nav-links">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.documentos.ctes') }}" class="{{ request()->routeIs('admin.documentos.ctes') ? 'active' : '' }}">CT-e</a>
        <a href="{{ route('admin.documentos.nfes') }}" class="{{ request()->routeIs('admin.documentos.nfes') ? 'active' : '' }}">NF-e</a>
        <a href="{{ route('admin.veiculos.index') }}" class="{{ request()->routeIs('admin.veiculos.*') ? 'active' : '' }}">Veículos</a>
        <a href="{{ route('admin.motoristas.index') }}" class="{{ request()->routeIs('admin.motoristas.*') ? 'active' : '' }}">Motoristas</a>
        <a href="{{ route('admin.clientes.index') }}" class="{{ request()->routeIs('admin.clientes.*') ? 'active' : '' }}">Clientes</a>
        <a href="{{ route('admin.pendencias') }}" class="{{ request()->routeIs('admin.pendencias') ? 'active' : '' }}">Pendências</a>
        <a href="{{ route('admin.modelos.index') }}" class="{{ request()->routeIs('admin.modelos.*') ? 'active' : '' }}">WhatsApp</a>
        <a href="{{ route('admin.relatorios') }}" class="{{ request()->routeIs('admin.relatorios') ? 'active' : '' }}">Relatórios</a>
        <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">Usuários</a>
        <a href="{{ route('admin.configuracoes') }}" class="{{ request()->routeIs('admin.configuracoes*') ? 'active' : '' }}">⚙️ Config</a>
    </div>
    @else
    <div class="nav-links">
        <a href="{{ route('motorista.home') }}" class="active">Minhas entregas</a>
    </div>
    @endif

    {{-- Right-side actions --}}
    <div class="nav-actions">
        @if(auth()->user()->isAdmin())
        <div class="sync-badge" id="emailSyncContainer" title="Leitura automática de e-mails ativa enquanto logado">
            <span id="syncPulse" class="sync-pulse ok"></span>
            <span id="syncText"><b id="syncTimerCount">60s</b></span>
            <button id="btnManualSync" type="button" style="background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;font-size:.75rem;line-height:1" title="Verificar caixa agora">🔄</button>
        </div>
        @endif
        <button id="pwaInstallBtn" class="pwa-install-btn hidden">📲 Instalar</button>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button class="user-btn">{{ Str::before(auth()->user()->name, ' ') }} · Sair</button>
        </form>
    </div>
</nav>

{{-- ===== MOBILE DRAWER ===== --}}
<div class="drawer-overlay" id="drawerOverlay"></div>
<aside class="drawer" id="drawerPanel">
    <div class="drawer-header">
        <div class="brand">
            <img src="/icons/icon.svg" alt="Logo">
            <span>Fretes</span>
        </div>
        <button class="drawer-close" id="drawerClose" aria-label="Fechar menu">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:1.25rem;height:1.25rem">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    <div class="drawer-nav">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}"><span class="nav-icon">📊</span> Dashboard</a>
        <div class="nav-section">Documentos</div>
        <a href="{{ route('admin.documentos.ctes') }}"><span class="nav-icon">📄</span> CT-e</a>
        <a href="{{ route('admin.documentos.nfes') }}"><span class="nav-icon">📑</span> NF-e</a>
        <div class="nav-section">Cadastros</div>
        <a href="{{ route('admin.veiculos.index') }}"><span class="nav-icon">🚛</span> Veículos</a>
        <a href="{{ route('admin.motoristas.index') }}"><span class="nav-icon">👤</span> Motoristas</a>
        <a href="{{ route('admin.clientes.index') }}"><span class="nav-icon">🏢</span> Clientes</a>
        <div class="nav-section">Gestão</div>
        <a href="{{ route('admin.pendencias') }}"><span class="nav-icon">⚠️</span> Pendências</a>
        <a href="{{ route('admin.modelos.index') }}"><span class="nav-icon">💬</span> WhatsApp</a>
        <a href="{{ route('admin.relatorios') }}"><span class="nav-icon">📈</span> Relatórios</a>
        <a href="{{ route('admin.usuarios.index') }}"><span class="nav-icon">🔑</span> Usuários</a>
        <div class="nav-section">Sistema</div>
        <a href="{{ route('admin.configuracoes') }}"><span class="nav-icon">⚙️</span> Configurações</a>
        @else
        <a href="{{ route('motorista.home') }}"><span class="nav-icon">📦</span> Minhas entregas</a>
        @endif
    </div>
    <div class="drawer-footer">
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button class="btn btn-ghost btn-block" style="color:#f87171;border-color:rgba(248,113,113,.2)">
                Sair · {{ auth()->user()->name }}
            </button>
        </form>
    </div>
</aside>
@endauth

{{-- ===== TOAST DE SINCRONIZAÇÃO ===== --}}
<div id="syncToast" class="toast hidden">
    <span style="font-size:1.25rem">📩</span>
    <div>
        <div id="syncToastTitle" style="font-weight:700;font-size:.875rem;color:#34d399">Novos XMLs Processados</div>
        <div id="syncToastDesc" style="font-size:.75rem;color:#94a3b8">A caixa de e-mails foi lida com sucesso.</div>
    </div>
    <button onclick="document.getElementById('syncToast').classList.add('hidden')" style="background:none;border:none;color:#64748b;cursor:pointer;padding:.25rem;font-size:.875rem">✕</button>
</div>

{{-- ===== MAIN CONTENT ===== --}}
<main class="app-main animate-in">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @foreach(['sucesso_banco', 'sucesso_email'] as $k)
        @if(session($k))
            <div class="alert alert-success">{{ session($k) }}</div>
        @endif
    @endforeach
    @foreach(['erro_banco', 'erro_email'] as $k)
        @if(session($k))
            <div class="alert alert-danger">{{ session($k) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>

<script>
    // ===== Drawer Menu (Mobile) =====
    (function() {
        const hamburger = document.getElementById('hamburgerBtn');
        const overlay = document.getElementById('drawerOverlay');
        const drawer = document.getElementById('drawerPanel');
        const closeBtn = document.getElementById('drawerClose');

        function openDrawer() {
            if (overlay) overlay.classList.add('open');
            if (drawer) drawer.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            if (overlay) overlay.classList.remove('open');
            if (drawer) drawer.classList.remove('open');
            document.body.style.overflow = '';
        }

        if (hamburger) hamburger.addEventListener('click', openDrawer);
        if (overlay) overlay.addEventListener('click', closeDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

        // Close on escape
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });
    })();

    // ===== Service Worker PWA =====
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch((err) => {
                console.debug('ServiceWorker falhou:', err);
            });
        });
    }

    // ===== PWA Install Button =====
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
                    await deferredPrompt.userChoice;
                    deferredPrompt = null;
                }
            });
        }
    });
    window.addEventListener('appinstalled', () => {
        if (installBtn) installBtn.classList.add('hidden');
        deferredPrompt = null;
    });

    // ===== Auto-Sync Timer (Admin only) =====
    @auth
    @if(auth()->user()->isAdmin())
    (function() {
        const INTERVALO_SEGUNDOS = 60;
        let segundosRestantes = INTERVALO_SEGUNDOS;
        let emExecucao = false;
        let ultimoStatus = 'ok'; // 'ok' | 'erro' | 'offline' | 'nao_configurado'
        let ultimoErroMsg = '';

        const textEl = document.getElementById('syncText');
        const pulseEl = document.getElementById('syncPulse');
        const btnManual = document.getElementById('btnManualSync');
        const toastEl = document.getElementById('syncToast');
        const toastTitle = document.getElementById('syncToastTitle');
        const toastDesc = document.getElementById('syncToastDesc');

        function exibirToast(titulo, descricao, sucesso = true) {
            if (!toastEl) return;
            toastTitle.textContent = titulo;
            toastTitle.style.color = sucesso ? '#34d399' : '#fbbf24';
            toastDesc.textContent = descricao;
            toastEl.classList.remove('hidden');
            setTimeout(() => { toastEl.classList.add('hidden'); }, 6000);
        }

        function renderizarTimer() {
            if (!textEl) return;

            if (emExecucao) {
                if (pulseEl) pulseEl.className = 'sync-pulse busy';
                textEl.innerHTML = '<span style="color:#fbbf24;font-size:0.75rem;font-weight:600">Lendo…</span>';
                return;
            }

            if (ultimoStatus === 'nao_configurado') {
                if (pulseEl) pulseEl.className = 'sync-pulse off';
                textEl.innerHTML = '<span style="color:#64748b;font-size:0.75rem" title="Configure a caixa de e-mail em Configurações">Não config.</span>';
                return;
            }

            if (ultimoStatus === 'erro') {
                if (pulseEl) pulseEl.className = 'sync-pulse err';
                textEl.innerHTML = '<span style="color:#f87171;font-size:0.75rem;font-weight:600" title="' + (ultimoErroMsg || 'Falha na conexão IMAP/E-mail') + '">Falha (' + segundosRestantes + 's)</span>';
                return;
            }

            if (ultimoStatus === 'offline') {
                if (pulseEl) pulseEl.className = 'sync-pulse off';
                textEl.innerHTML = '<span style="color:#94a3b8;font-size:0.75rem" title="Sem conexão com o servidor">Offline (' + segundosRestantes + 's)</span>';
                return;
            }

            // Status OK
            if (pulseEl) pulseEl.className = 'sync-pulse ok';
            textEl.innerHTML = '<b id="syncTimerCount">' + segundosRestantes + 's</b>';
        }

        async function sincronizarEmails() {
            if (emExecucao) return;
            emExecucao = true;
            renderizarTimer();

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const resp = await fetch('{{ route('admin.ingestao.autoSync') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    }
                });

                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }

                const data = await resp.json();

                if (data.motivo === 'nao_configurado') {
                    ultimoStatus = 'nao_configurado';
                } else if (data.ok) {
                    ultimoStatus = 'ok';
                    ultimoErroMsg = '';

                    if (data.processados > 0) {
                        exibirToast('🎉 ' + data.processados + ' novo(s) documento(s)', 'Foram baixados e conciliados ' + data.processados + ' XML(s) às ' + data.horario + '.');
                    }
                } else {
                    ultimoStatus = 'erro';
                    ultimoErroMsg = data.erros?.join('; ') || 'Erro ao sincronizar caixa de e-mail';
                }
            } catch (err) {
                ultimoStatus = 'offline';
                ultimoErroMsg = err.message || 'Falha de rede';
            } finally {
                emExecucao = false;
                segundosRestantes = INTERVALO_SEGUNDOS;
                renderizarTimer();
            }
        }

        setInterval(() => {
            if (emExecucao) return;
            segundosRestantes--;
            if (segundosRestantes <= 0) {
                sincronizarEmails();
            } else {
                renderizarTimer();
            }
        }, 1000);

        if (btnManual) {
            btnManual.addEventListener('click', (e) => {
                e.preventDefault();
                segundosRestantes = 0;
                sincronizarEmails();
            });
        }
    })();
    @endif
    @endauth
</script>
</body>
</html>
