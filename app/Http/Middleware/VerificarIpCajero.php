<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use App\Services\ConfiguracionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarIpCajero
{
    public function __construct(protected ConfiguracionService $configuracion) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->rol === Rol::Cajero) {
            $permitidas = array_filter(array_map(
                'trim',
                explode(',', (string) $this->configuracion->get('ips_cajero', ''))
            ));

            if ($permitidas !== [] && ! in_array($request->ip(), $permitidas, true)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
