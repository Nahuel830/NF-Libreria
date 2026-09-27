<?php

namespace App\Services;

use App\Models\DispositivoUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * WEB-1 1.7: cookie de dispositivo + aviso de dispositivo nuevo.
 */
class DispositivoService
{
    public const COOKIE = 'nf_dispositivo';

    public function __construct(protected AuditoriaService $auditoria) {}

    /**
     * Registra el uso y devuelve true si el dispositivo es nuevo para el usuario.
     */
    public function registrarUso(User $usuario, Request $request): bool
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            $token = Str::random(40);
        }

        $hash = hash('sha256', $token);

        $dispositivo = DispositivoUsuario::where('user_id', $usuario->id)
            ->where('hash', $hash)
            ->first();

        cookie()->queue(cookie(
            self::COOKIE,
            $token,
            60 * 24 * 365 * 5
        ));

        if ($dispositivo) {
            $dispositivo->forceFill([
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'ip' => $request->ip(),
                'ultimo_uso' => now(),
            ])->save();

            return false;
        }

        DispositivoUsuario::create([
            'user_id' => $usuario->id,
            'hash' => $hash,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'ip' => $request->ip(),
            'primer_uso' => now(),
            'ultimo_uso' => now(),
        ]);

        $this->auditoria->registrar(
            'LOGIN_NUEVO_DISPOSITIVO',
            "El usuario '{$usuario->usuario}' inició sesión desde un dispositivo nuevo.",
            $usuario,
            null,
            null,
            $usuario
        );

        return true;
    }

    public function hashActual(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && $token !== '' ? hash('sha256', $token) : null;
    }
}
