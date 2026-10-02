<?php

use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentoController;
use App\Http\Controllers\Admin\MotoristaController;
use App\Http\Controllers\Admin\PendenciaController;
use App\Http\Controllers\Admin\RelatorioController;
use App\Http\Controllers\Admin\VeiculoController;
use App\Http\Controllers\Admin\WhatsappModeloController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Motorista\HomeController as MotoristaHome;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// ---------- Gatilho de cron via HTTP (alternativa ao public/cron.php) ----------
// Para planos onde o PHP CLI está bloqueado: agende cron-job.org / UptimeRobot
// chamando https://seu-dominio.com/run-cron?token=CRON_TOKEN a cada minuto.
Route::get('/run-cron', function (Illuminate\Http\Request $request) {
    $token = trim((string) env('CRON_TOKEN', ''));

    if ($token === '') {
        abort(403, 'Defina CRON_TOKEN no .env para habilitar o gatilho HTTP.');
    }

    if (! hash_equals($token, (string) $request->query('token', ''))) {
        abort(403);
    }

    Illuminate\Support\Facades\Artisan::call('schedule:run');

    return response('ok', 200);
})->withoutMiddleware(['web']); // sem sessão/CSRF: é um webhook interno

// ---------- Autenticação ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'form'])->name('login.form');
    Route::post('/login', [LoginController::class, 'store'])->name('login');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---------- Área Administrativa (perfil admin) ----------
Route::middleware(['auth', 'profile:admin', 'audit'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('veiculos', VeiculoController::class)->except(['show']);
    Route::resource('motoristas', MotoristaController::class)->except(['show']);
    Route::resource('clientes', ClienteController::class)->except(['show']);

    Route::get('documentos/ctes', [DocumentoController::class, 'ctes'])->name('documentos.ctes');
    Route::get('documentos/nfes', [DocumentoController::class, 'nfes'])->name('documentos.nfes');
    Route::get('documentos/{tipo}/{id}/xml', [DocumentoController::class, 'xml'])->name('documentos.xml');
    Route::post('documentos/ctes/{cte}/atribuir-veiculo', [DocumentoController::class, 'atribuirVeiculo'])->name('documentos.atribuirVeiculo');

    Route::get('pendencias', [PendenciaController::class, 'index'])->name('pendencias');
    Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios');
    Route::resource('modelos', WhatsappModeloController::class)
        ->only(['index', 'edit', 'update'])
        ->parameters(['modelos' => 'modelo']);
});

// ---------- Área do Motorista (mobile first, perfil motorista) ----------
Route::middleware(['auth', 'profile:motorista'])->prefix('motorista')->name('motorista.')->group(function () {
    Route::get('/', [MotoristaHome::class, 'index'])->name('home');
    Route::get('entregas/{entrega}', [MotoristaHome::class, 'show'])->name('entregas.show');
    Route::post('entregas/{entrega}/status', [MotoristaHome::class, 'atualizarStatus'])->name('entregas.status');
});
