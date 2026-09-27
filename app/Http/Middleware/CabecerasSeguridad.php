<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad (WEB-1 1.3). Global, en respuestas HTML y JSON.
 * HSTS NO va aquí (lo pone Nginx en WEB-2).
 */
class CabecerasSeguridad
{
    public const CSP = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->set('X-Frame-Options', 'DENY');
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (config('seguridad.csp_activa')) {
            $cabecera = config('seguridad.csp_solo_reporte')
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $respuesta->headers->set($cabecera, self::CSP);
        }

        return $respuesta;
    }
}
