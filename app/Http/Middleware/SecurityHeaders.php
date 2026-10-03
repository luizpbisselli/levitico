<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança globais (README seção 5):
 * - HSTS quando o app roda em HTTPS;
 * - X-Frame-Options / frame-ancestors: bloqueia clickjacking;
 * - X-Content-Type-Options: evita sniffing de MIME;
 * - Referrer-Policy: não vaza URLs internas para sites externos;
 * - Permissions-Policy: desliga sensores que o sistema não usa.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Em rotas de escrita, o token CSRF pode ter expirado (sessão nova, aba antiga).
        // O Laravel devolve 419 "Page Expired", que não renova o token e vira um loop:
        // ao renderizar a página do formulário novamente, regenera-se o token para que
        // o próximo envio funcione em vez de manter o erro 419.
        if ($response->getStatusCode() === 419
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            && $request->hasSession()
            && $request->session()->token() === $request->input('_token')) {
            $request->session()->regenerateToken();
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = "frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
        if ($this->isLoginPage($request)) {
            // Página de login: também restringe scripts/styles ao próprio domínio
            // (defesa em profundidade contra XSS que injete exfiltração de credenciais).
            $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; " . $csp;
        }

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }

    protected function isLoginPage(Request $request): bool
    {
        return in_array($request->path(), ['login', 'public/login'], true)
            || str_contains((string) $request->route()?->getAction('controller') ?? '', 'LoginController');
    }
}
