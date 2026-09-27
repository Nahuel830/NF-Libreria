<?php

namespace App\Http\Middleware;

use App\Services\TotpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigirTotp
{
    public function __construct(protected TotpService $totp) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (
            $usuario
            && $this->totp->obligatorioPara($usuario)
            && ! $this->totp->activoPara($usuario)
            && ! $request->routeIs('login*', 'logout', 'password.*', 'totp.*')
        ) {
            return redirect()->route('totp.configurar')
                ->with('warning', 'Debes activar la verificación en dos pasos antes de continuar.');
        }

        return $next($request);
    }
}
