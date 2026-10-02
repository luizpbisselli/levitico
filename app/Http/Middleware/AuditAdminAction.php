<?php

namespace App\Http\Middleware;

use App\Models\AuditoriaLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log de auditoria das ações de escrita do admin (README seção 5).
 */
class AuditAdminAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])
            && $request->user()?->isAdmin()
            && $response->getStatusCode() < 400) {
            AuditoriaLog::create([
                'user_id' => $request->user()->id,
                'acao'    => strtolower($request->method()),
                'modelo'  => $request->route()?->getRouteName(),
                'payload' => ['uri' => $request->fullUrl(), 'input' => $request->except(['_token', 'password'])],
                'ip'      => $request->ip(),
            ]);
        }

        return $response;
    }
}
