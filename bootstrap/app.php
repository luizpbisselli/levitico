<?php

use App\Http\Middleware\AuditAdminAction;
use App\Http\Middleware\EnsureProfile;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\SecurityHeaders;
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

        // --- Segurança (README seção 5) ---
        // Hospedagem compartilhada atrás de proxy/CDN: confia em X-Forwarded-*
        $middleware->trustProxies(
            at: '*',
            headers: \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR
                | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST
                | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT
                | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO
                | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_AWS_ELB,
        );
        // HTTPS forçado + cookie de sessão com Secure quando em produção
        $middleware->web(append: [
            ForceHttps::class,
            SecurityHeaders::class,
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
