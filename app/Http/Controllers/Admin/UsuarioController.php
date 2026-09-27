<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrearUsuarioRequest;
use App\Http\Requests\Admin\EditarUsuarioRequest;
use App\Http\Requests\Admin\RestablecerPasswordRequest;
use App\Models\User;
use App\Services\TotpService;
use App\Services\UsuarioService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $usuarios = User::query()
            ->when($request->input('q'), function ($consulta, $q): void {
                $consulta->where(function ($sub) use ($q): void {
                    $sub->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('usuario', 'ilike', "%{$q}%");
                });
            })
            ->when($request->input('rol'), fn ($consulta, $rol) => $consulta->where('rol', $rol))
            ->when($request->input('estado') !== null && $request->input('estado') !== '', fn ($consulta) => $consulta->where('activo', $request->boolean('estado')))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => Rol::cases(),
        ]);
    }

    public function crear(): View
    {
        return view('usuarios.crear', ['roles' => Rol::cases()]);
    }

    public function guardar(CrearUsuarioRequest $request, UsuarioService $servicio): RedirectResponse
    {
        $usuario = $servicio->crear($request->validated());

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$usuario->usuario}' creado. Debe cambiar su contraseña al entrar.");
    }

    public function editar(User $usuario): View
    {
        return view('usuarios.editar', [
            'usuario' => $usuario,
            'roles' => Rol::cases(),
        ]);
    }

    public function actualizar(EditarUsuarioRequest $request, User $usuario, UsuarioService $servicio): RedirectResponse
    {
        try {
            $servicio->actualizar($usuario, $request->validated(), $request->user());
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['rol' => $e->getMessage()]);
        }

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$usuario->usuario}' actualizado.");
    }

    public function password(User $usuario): View
    {
        return view('usuarios.password', ['usuario' => $usuario]);
    }

    public function actualizarPassword(RestablecerPasswordRequest $request, User $usuario, UsuarioService $servicio): RedirectResponse
    {
        $servicio->restablecerPassword($usuario, $request->validated()['password']);

        return redirect()->route('usuarios.index')
            ->with('success', "Contraseña de '{$usuario->usuario}' restablecida. Deberá cambiarla al entrar.");
    }

    public function restablecerTotp(Request $request, User $usuario, TotpService $totp): RedirectResponse
    {
        abort_unless(in_array($usuario->rol, [Rol::Admin, Rol::Encargado], true), 404);
        abort_unless($totp->activoPara($usuario->fresh()), 404);

        $totp->restablecerPorAdmin($usuario->fresh(), $request->user());

        return redirect()->route('usuarios.editar', $usuario)
            ->with('success', "Verificación en dos pasos de '{$usuario->usuario}' restablecida. Deberá configurarla al entrar.");
    }

    public function estado(Request $request, User $usuario, UsuarioService $servicio): RedirectResponse
    {
        try {
            $servicio->cambiarActivo($usuario, ! $usuario->activo, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['usuario' => $e->getMessage()]);
        }

        $mensaje = $usuario->fresh()->activo ? 'activado' : 'desactivado';

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$usuario->usuario}' {$mensaje}.");
    }
}
