<?php

use App\Http\Middleware\AuditAdminAction;
use App\Http\Middleware\EnsureProfile;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'profile' => EnsureProfile::class, // profile:admin | profile:motorista
            'audit'   => AuditAdminAction::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

/*
 * Override da conexão de banco a partir das configurações salvas no sistema
 * (tabela `configuracoes`), permitindo apontar para um MySQL criado no cPanel
 * sem editar .env e sem SSH. Executado antes de qualquer uso do DB, inclusive
 * pelo AutoInstall. Falha silenciosa: mantém o padrão do config/database.php.
 */
\App\Support\DatabaseConfigurator::apply();

return $app;
