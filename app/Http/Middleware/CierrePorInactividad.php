<?php

namespace App\Http\Middleware;

use App\Services\ConfiguracionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CierrePorInactividad
{
    public function __construct(protected ConfiguracionService $configuracion) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $minutos = (int) $this->configuracion->get('minutos_inactividad', 60);
            $minutos = $minutos < 1 ? 60 : $minutos;

            $ultima = $request->session()->get('ultima_actividad');

            if (is_numeric($ultima) && (now()->timestamp - (int) $ultima) > ($minutos * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('warning', 'Tu sesión se cerró por inactividad.');
            }

            $request->session()->put('ultima_actividad', now()->timestamp);
        }

        return $next($request);
    }
}
