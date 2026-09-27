<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function editar(): View
    {
        abort_unless(in_array(auth()->user()->rol, [Rol::Admin, Rol::Encargado], true), 403);

        return view('auth.cambiar-password');
    }

    public function actualizar(CambiarPasswordRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->input('nueva'),
            'debe_cambiar_password' => false,
        ])->save();

        $auditoria->registrar(
            'CAMBIO_PASSWORD',
            "El usuario '{$user->usuario}' cambió su contraseña.",
            $user
        );

        return redirect()->route('inicio')->with('success', 'Contraseña actualizada correctamente.');
    }
}
