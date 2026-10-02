<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redireciona HTTP -> HTTPS quando APP_URL está em https.
 * Necessário porque as hospedagens compartilhadas ficam atrás de proxy/CDN:
 * sem trust proxies, $request->isSecure() seria sempre false.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.secure' => $request->isSecure() ?: null]);

        if (! $request->secure() && str_starts_with((string) config('app.url'), 'https://')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
