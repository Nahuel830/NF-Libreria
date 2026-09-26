<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolMiddleware
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $permitidos = collect($roles)
            ->map(fn (string $rol) => Rol::tryFrom($rol))
            ->filter()
            ->all();

        $rolUsuario = $request->user()?->rol;

        if (! $rolUsuario instanceof Rol || ! in_array($rolUsuario, $permitidos, true)) {
            abort(403);
        }

        return $next($request);
    }
}
