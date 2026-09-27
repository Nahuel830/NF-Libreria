<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TotpController extends Controller
{
    use CompletaLogin;
    protected function usuarioPendiente(Request $request): ?User
    {
        $id = $request->session()->get('totp_pendiente');

        return $id ? User::find($id) : null;
    }

    protected function claveLimite(User $usuario, Request $request): string
    {
        return 'login:'.mb_strtolower($usuario->usuario).'|'.$request->ip();
    }

    public function estado(Request $request): View
    {
        return view('auth.totp-estado', ['usuario' => $request->user()]);
    }

    public function configurar(Request $request, TotpService $totp): View|RedirectResponse
    {
        $usuario = $request->user() ?? $this->usuarioPendiente($request);

        abort_unless($usuario && in_array($usuario->rol, [Rol::Admin, Rol::Encargado], true), 404);

        if ($usuario->totp_activo) {
            return redirect()->route('totp.estado');
        }

        $secreto = session('totp_secreto');

        if (! is_string($secreto) || $secreto === '') {
            $secreto = $totp->generarSecreto();
            session(['totp_secreto' => $secreto]);
        }

        return view('auth.totp-configurar', [
            'qr' => $totp->qrSvg($secreto, $usuario),
            'secreto' => $secreto,
        ]);
    }

    public function guardarConfigurar(Request $request, TotpService $totp, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string']]);

        $usuario = $request->user() ?? $this->usuarioPendiente($request);
        $secreto = $request->session()->get('totp_secreto');

        abort_unless($usuario && is_string($secreto) && $secreto !== '', 404);

        $usuario->forceFill(['totp_secreto' => $secreto])->save();

        if (! $totp->verificar($usuario, $request->input('codigo'))) {
            return back()->withErrors(['codigo' => 'El código no es válido. Revisa la hora de tu teléfono e inténtalo de nuevo.']);
        }

        $codigos = $totp->activar($usuario, $secreto);
        $request->session()->forget('totp_secreto');

        if (! Auth::check()) {
            $this->completarLogin($request, $usuario, $auditoria);

            return redirect()->route('totp.estado')
                ->with('codigos_recuperacion', $codigos)
                ->with('success', 'Verificación en dos pasos activada. Guarda tus códigos de recuperación.');
        }

        return redirect()->route('totp.estado')
            ->with('codigos_recuperacion', $codigos)
            ->with('success', 'Verificación en dos pasos activada. Guarda tus códigos de recuperación.');
    }

    public function verificar(Request $request): View
    {
        $usuario = $this->usuarioPendiente($request);

        abort_unless($usuario && $usuario->totp_activo, 404);

        return view('auth.totp-verificar');
    }

    public function comprobar(Request $request, TotpService $totp, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string']]);

        $usuario = $this->usuarioPendiente($request);

        abort_unless($usuario && $usuario->totp_activo, 404);

        $clave = $this->claveLimite($usuario, $request);

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $auditoria->registrar('LOGIN_FALLIDO', "Acceso bloqueado por exceso de intentos para '{$usuario->usuario}'.", $usuario);

            return back()->withErrors(['codigo' => 'Demasiados intentos. Inténtalo de nuevo en '.RateLimiter::availableIn($clave).' segundos.'])->setStatusCode(429);
        }

        $codigo = $request->input('codigo');

        if ($totp->verificar($usuario, $codigo) || $totp->verificarRecuperacion($usuario, $codigo)) {
            RateLimiter::clear($clave);
            $this->completarLogin($request, $usuario, $auditoria);

            return redirect()->intended(route('inicio'));
        }

        RateLimiter::hit($clave, 60);
        $auditoria->registrar('LOGIN_FALLIDO', "Código de verificación incorrecto para '{$usuario->usuario}'.", $usuario);

        return back()->withErrors(['codigo' => 'El código no es válido.']);
    }

    public function desactivar(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate(['actual' => ['required', 'string', 'current_password']]);

        $usuario = $request->user();

        abort_unless($usuario && $usuario->rol === Rol::Encargado && $usuario->totp_activo, 403);

        $totp->desactivar($usuario);

        return redirect()->route('totp.estado')->with('success', 'Verificación en dos pasos desactivada.');
    }
}
