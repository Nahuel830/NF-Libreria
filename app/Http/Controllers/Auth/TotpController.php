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
use Illuminate\View\View;

class TotpController extends Controller
{
    use CompletaLogin;

    protected int $maxIntentosTotp = 5;

    /**
     * Resuelve el usuario del flujo TOTP: autenticado (activación/Mi seguridad)
     * o pendiente de verificación (sesión intermedia de login).
     */
    protected function usuarioTotp(Request $request): ?User
    {
        $autenticado = $request->user();

        if ($autenticado && in_array($autenticado->rol, [Rol::Admin, Rol::Encargado], true)) {
            return $autenticado->fresh();
        }

        $id = $request->session()->get('totp_pendiente');

        return $id ? User::find($id) : null;
    }

    protected function cerrarPorIntentos(Request $request, AuditoriaService $auditoria, User $usuario): RedirectResponse
    {
        $intentos = (int) $request->session()->get('totp_intentos', 0) + 1;
        $request->session()->put('totp_intentos', $intentos);

        if ($intentos < $this->maxIntentosTotp) {
            return back()->withErrors(['codigo' => "El código no es válido. Te quedan {$this->restantes($intentos)} intentos."]);
        }

        $auditoria->registrar(
            'LOGIN_FALLIDO',
            "Sesión cerrada por superar los {$this->maxIntentosTotp} intentos de verificación para '{$usuario->usuario}'.",
            $usuario
        );

        $request->session()->forget(['totp_pendiente', 'totp_intentos']);

        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['usuario' => 'Superaste el máximo de intentos. Vuelve a intentarlo.']);
    }

    protected function restantes(int $intentos): int
    {
        return max(0, $this->maxIntentosTotp - $intentos);
    }

    public function estado(Request $request, TotpService $totp): View
    {
        $usuario = $request->user()?->fresh();

        abort_unless($usuario, 404);

        return view('auth.totp-estado', [
            'usuario' => $usuario,
            'obligatorio' => $totp->obligatorioPara($usuario),
        ]);
    }

    public function configurar(Request $request, TotpService $totp): View|RedirectResponse
    {
        $usuario = $this->usuarioTotp($request);

        abort_unless($usuario, 404);

        if ($totp->activoPara($usuario)) {
            return redirect()->route('totp.estado');
        }

        $secreto = $totp->generarSecretoPara($usuario);

        return view('auth.totp-configurar', [
            'qr' => $totp->qrSvg($secreto, $usuario),
            'secreto' => $secreto,
            'obligatorio' => $totp->obligatorioPara($usuario),
        ]);
    }

    public function guardarConfigurar(Request $request, TotpService $totp, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string']]);

        $usuario = $this->usuarioTotp($request);

        abort_unless($usuario && ! $totp->activoPara($usuario), 404);

        $paso = $totp->verificar($usuario->fresh(), $request->input('codigo'));

        if ($paso === false) {
            return $this->cerrarPorIntentos($request, $auditoria, $usuario);
        }

        $request->session()->forget('totp_intentos');
        $codigos = $totp->confirmar($usuario->fresh(), $paso);

        if (! Auth::check()) {
            $this->completarLogin($request, $usuario->fresh(), $auditoria);
        }

        return redirect()->route('totp.estado')
            ->with('codigos_recuperacion', $codigos)
            ->with('success', 'Verificación en dos pasos activada. Guarda tus códigos de recuperación.');
    }

    public function verificar(Request $request, TotpService $totp): View
    {
        $id = $request->session()->get('totp_pendiente');
        $usuario = $id ? User::find($id) : null;

        abort_unless($usuario && $totp->activoPara($usuario), 404);

        return view('auth.totp-verificar');
    }

    public function comprobar(Request $request, TotpService $totp, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string']]);

        $id = $request->session()->get('totp_pendiente');
        $usuario = $id ? User::find($id) : null;

        abort_unless($usuario && $totp->activoPara($usuario), 404);

        $usuario = $usuario->fresh();
        $codigo = $request->input('codigo');

        $paso = $totp->verificar($usuario, $codigo);

        if ($paso !== false) {
            $request->session()->forget('totp_intentos');
            $totp->marcarPasoUsado($usuario, $paso);
            $this->completarLogin($request, $usuario, $auditoria);

            return redirect()->intended(route('inicio'));
        }

        if ($totp->usarCodigoRecuperacion($usuario, $codigo)) {
            $request->session()->forget('totp_intentos');
            $this->completarLogin($request, $usuario->fresh(), $auditoria);

            return redirect()->intended(route('inicio'));
        }

        return $this->cerrarPorIntentos($request, $auditoria, $usuario);
    }

    public function regenerar(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate([
            'actual' => ['required', 'string', 'current_password'],
            'codigo' => ['required', 'string'],
        ]);

        $usuario = $request->user()?->fresh();

        abort_unless($usuario && $totp->activoPara($usuario), 403);
        abort_unless($totp->verificar($usuario, $request->input('codigo')) !== false, 403);

        $codigos = $totp->regenerarCodigos($usuario);

        return redirect()->route('totp.estado')
            ->with('codigos_recuperacion', $codigos)
            ->with('success', 'Códigos de recuperación regenerados. Los anteriores ya no sirven.');
    }

    public function desactivar(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate([
            'actual' => ['required', 'string', 'current_password'],
            'codigo' => ['required', 'string'],
        ]);

        $usuario = $request->user()?->fresh();

        abort_unless($usuario && $totp->activoPara($usuario), 403);
        abort_if($totp->obligatorioPara($usuario), 403);

        abort_unless($totp->verificar($usuario, $request->input('codigo')) !== false, 403);

        $totp->desactivar($usuario);

        return redirect()->route('totp.estado')->with('success', 'Verificación en dos pasos desactivada.');
    }
}
