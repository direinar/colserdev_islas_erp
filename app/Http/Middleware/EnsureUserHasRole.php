<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Misma comparación que User::hasAnyRole (la que usa el menú), para que menú y rutas no difieran.
        if (! $request->user()?->hasAnyRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}
