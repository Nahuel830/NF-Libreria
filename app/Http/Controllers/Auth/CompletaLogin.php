<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait CompletaLogin
{
    protected function completarLogin(Request $request, User $usuario, AuditoriaService $auditoria): void
    {
        $request->session()->forget(['totp_pendiente', 'totp_intentos']);

        Auth::login($usuario);
        $request->session()->regenerate();
        $request->session()->put('ultima_actividad', now()->timestamp);

        $esNuevaIp = ! \App\Models\Auditoria::where('accion', 'LOGIN')
            ->where('user_id', $usuario->id)
            ->where('ip', $request->ip())
            ->exists();

        $usuario->forceFill(['ultimo_acceso' => now()])->save();

        $auditoria->registrar(
            'LOGIN',
            "El usuario '{$usuario->usuario}' inició sesión.".($esNuevaIp ? ' Dispositivo/IP nueva.' : '')
        );
    }
}
