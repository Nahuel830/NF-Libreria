<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\User;
use DomainException;

class UsuarioService
{
    public function __construct(protected AuditoriaService $auditoria) {}

    public function crear(array $datos): User
    {
        // El cajero mantiene contraseña fija: solo admin/encargado deben cambiarla al entrar.
        $debeCambiar = Rol::from($datos['rol']) !== Rol::Cajero;

        $usuario = User::create([
            'nombre' => $datos['nombre'],
            'usuario' => $datos['usuario'],
            'password' => $datos['password'],
            'rol' => $datos['rol'],
            'activo' => true,
            'debe_cambiar_password' => $debeCambiar,
        ]);

        $this->auditoria->registrar(
            'CREAR',
            "Se creó el usuario '{$usuario->usuario}'.",
            $usuario,
            null,
            $this->datosPublicos($usuario)
        );

        return $usuario;
    }

    public function actualizar(User $usuario, array $datos, User $actor): User
    {
        $antes = $this->datosPublicos($usuario);

        $nuevoRol = Rol::from($datos['rol']);

        $this->protegerRolAdmin($usuario, $nuevoRol, $actor);

        $usuario->forceFill([
            'nombre' => $datos['nombre'],
            'rol' => $nuevoRol,
        ])->save();

        $this->auditoria->registrar(
            'EDITAR',
            "Se editó el usuario '{$usuario->usuario}'.",
            $usuario,
            $antes,
            $this->datosPublicos($usuario->fresh())
        );

        return $usuario;
    }

    public function restablecerPassword(User $usuario, string $nueva): void
    {
        $debeCambiar = $usuario->rol !== Rol::Cajero;

        $usuario->forceFill([
            'password' => $nueva,
            'debe_cambiar_password' => $debeCambiar,
        ])->save();

        $this->auditoria->registrar(
            'CAMBIO_PASSWORD',
            "El administrador restableció la contraseña del usuario '{$usuario->usuario}'."
                .($debeCambiar ? ' Debe cambiarla al entrar.' : ''),
            $usuario
        );
    }

    public function cambiarActivo(User $usuario, bool $activo, User $actor): void
    {
        if ($usuario->id === $actor->id) {
            throw new DomainException('No puedes desactivar tu propio usuario.');
        }

        if (! $activo) {
            $this->protegerUltimoAdmin($usuario);
        }

        $usuario->forceFill(['activo' => $activo])->save();

        $accion = $activo ? 'ACTIVAR' : 'DESACTIVAR';
        $this->auditoria->registrar(
            $accion,
            "Se ".($activo ? 'activó' : 'desactivó')." el usuario '{$usuario->usuario}'.",
            $usuario,
            ['activo' => ! $activo],
            ['activo' => $activo]
        );
    }

    /**
     * Evita que un admin se quite el rol a sí mismo o deje al sistema sin admins.
     */
    protected function protegerRolAdmin(User $usuario, Rol $nuevoRol, User $actor): void
    {
        if ($usuario->rol !== Rol::Admin || $nuevoRol === Rol::Admin) {
            return;
        }

        if ($usuario->id === $actor->id) {
            throw new DomainException('No puedes quitarte el rol de administrador a ti mismo.');
        }

        $this->protegerUltimoAdmin($usuario);
    }

    /**
     * Evita desactivar o degradar al último administrador activo.
     */
    protected function protegerUltimoAdmin(User $usuario): void
    {
        if ($usuario->rol !== Rol::Admin || ! $usuario->activo) {
            return;
        }

        $otrosAdmins = User::where('rol', Rol::Admin)
            ->where('activo', true)
            ->where('id', '!=', $usuario->id)
            ->count();

        if ($otrosAdmins === 0) {
            throw new DomainException('No se puede desactivar ni quitar el rol al último administrador activo.');
        }
    }

    protected function datosPublicos(User $usuario): array
    {
        return [
            'nombre' => $usuario->nombre,
            'usuario' => $usuario->usuario,
            'rol' => $usuario->rol instanceof Rol ? $usuario->rol->value : $usuario->rol,
            'activo' => $usuario->activo,
        ];
    }
}
