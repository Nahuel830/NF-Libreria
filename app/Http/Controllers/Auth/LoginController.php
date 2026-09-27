<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    use CompletaLogin;

    protected int $maxIntentos = 5;

    protected int $segundosBloqueo = 60;

    protected int $maxIntentosIp = 20;

    protected int $segundosBloqueoIp = 900;

    public function mostrarFormulario(): View
    {
        return view('auth.login');
    }

    public function entrar(LoginRequest $request, AuditoriaService $auditoria, TotpService $totp): RedirectResponse
    {
        $usuario = $request->input('usuario');
        $clave = $this->claveLimite($usuario, $request);
        $claveIp = $this->claveLimiteIp($request);

        if (RateLimiter::tooManyAttempts($clave, $this->maxIntentos)
            || RateLimiter::tooManyAttempts($claveIp, $this->maxIntentosIp)) {
            $auditoria->registrar(
                'LOGIN_FALLIDO',
                "Intento de acceso bloqueado por exceso de intentos para el usuario '{$usuario}'."
            );

            $segundos = max(RateLimiter::availableIn($clave), RateLimiter::availableIn($claveIp));

            return back()
                ->withInput($request->only('usuario'))
                ->withErrors(['usuario' => "Demasiados intentos. Inténtalo de nuevo en {$segundos} segundos."])
                ->setStatusCode(429);
        }

        $user = User::where('usuario', $usuario)->first();

        if (! $user || ! $user->activo || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($clave, $this->segundosBloqueo);
            RateLimiter::hit($claveIp, $this->segundosBloqueoIp);

            $auditoria->registrar(
                'LOGIN_FALLIDO',
                "Intento de acceso fallido para el usuario '{$usuario}'.",
                $user && $user->exists ? $user : null
            );

            return back()
                ->withInput($request->only('usuario'))
                ->withErrors(['usuario' => 'Usuario o contraseña incorrectos.']);
        }

        RateLimiter::clear($clave);
        $request->session()->forget('totp_intentos');

        if (in_array($user->rol, [Rol::Admin, Rol::Encargado], true)) {
            if ($totp->obligatorioPara($user) && ! $totp->activoPara($user)) {
                $this->completarLogin($request, $user, $auditoria);

                return redirect()->route('totp.configurar');
            }

            if ($totp->activoPara($user)) {
                $request->session()->put('totp_pendiente', $user->id);

                return redirect()->route('totp.verificar');
            }
        }

        $this->completarLogin($request, $user, $auditoria);

        return redirect()->intended(route('inicio'));
    }

    public function salir(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $usuario = $request->user()?->usuario;

        $auditoria->registrar('LOGOUT', "El usuario '{$usuario}' cerró sesión.");

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function claveLimite(string $usuario, Request $request): string
    {
        return 'login:'.$usuario.'|'.$request->ip();
    }

    protected function claveLimiteIp(Request $request): string
    {
        return 'login-ip:'.$request->ip();
    }
}
