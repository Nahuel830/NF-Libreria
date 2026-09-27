<?php

namespace App\Http\Middleware;

use App\Support\ProxiesDeConfianza;
use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica TRUSTED_PROXIES en tiempo de request (el .env aún no está
 * cargado cuando se configura el stack en bootstrap/app.php).
 * Debe correr antes del TrustProxies global de Laravel.
 */
class ConfiarProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        $lista = ProxiesDeConfianza::lista();

        if ($lista !== null) {
            TrustProxies::at($lista);
        }

        return $next($request);
    }
}
