<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function editar(): View
    {
        return view('auth.cambiar-password');
    }

    public function actualizar(CambiarPasswordRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $user = $request->user();

        Auth::logoutOtherDevices($request->input('actual'));

        $user->forceFill([
            'password' => $request->input('nueva'),
            'debe_cambiar_password' => false,
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $auditoria->registrar(
            'CAMBIO_PASSWORD',
            "El usuario '{$user->usuario}' cambió su contraseña. Se cerraron sus demás sesiones.",
            $user
        );

        return redirect()->route('inicio')->with('success', 'Contraseña actualizada correctamente.');
    }
}
