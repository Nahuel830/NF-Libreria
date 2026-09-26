<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    protected int $maxIntentos = 5;

    protected int $segundosBloqueo = 60;

    public function mostrarFormulario(): View
    {
        return view('auth.login');
    }

    public function entrar(LoginRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $usuario = $request->input('usuario');
        $clave = $this->claveLimite($usuario, $request);

        if (RateLimiter::tooManyAttempts($clave, $this->maxIntentos)) {
            $auditoria->registrar(
                'LOGIN_FALLIDO',
                "Intento de acceso bloqueado por exceso de intentos para el usuario '{$usuario}'."
            );

            $segundos = RateLimiter::availableIn($clave);

            return back()
                ->withInput($request->only('usuario'))
                ->withErrors(['usuario' => "Demasiados intentos. Inténtalo de nuevo en {$segundos} segundos."])
                ->setStatusCode(429);
        }

        $user = User::where('usuario', $usuario)->first();

        if (! $user || ! $user->activo || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($clave, $this->segundosBloqueo);

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

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('ultima_actividad', now()->timestamp);

        $user->forceFill(['ultimo_acceso' => now()])->save();

        $auditoria->registrar('LOGIN', "El usuario '{$user->usuario}' inició sesión.");

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
}
