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

        if (! $user || $user->profile !== $profile) {
            abort(403, 'Acesso restrito.');
        }

        return $next($request);
    }
}
