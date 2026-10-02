<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfile
{
    public function handle(Request $request, Closure $next, string $profile): Response
    {
        $user = $request->user();

        abort(403, 'Acesso restrito.', ! $user || $user->profile !== $profile);

        return $next($request);
    }
}
