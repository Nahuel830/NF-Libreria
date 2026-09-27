<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    /**
     * Claves que nunca deben guardarse en la auditoría.
     *
     * @var string[]
     */
    protected array $clavesProhibidas = [
        'password',
        'password_confirmation',
        'contraseña',
        'remember_token',
        'token',
    ];

    public function registrar(
        string $accion,
        string $descripcion,
        Model|string|null $entidad = null,
        ?array $antes = null,
        ?array $despues = null,
        ?User $comoUsuario = null
    ): Auditoria {
        return Auditoria::create([
            'user_id' => $comoUsuario?->id ?? auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad instanceof Model ? $entidad->getTable() : $entidad,
            'entidad_id' => $entidad instanceof Model ? $entidad->getKey() : null,
            'descripcion' => $descripcion,
            'datos_anteriores' => $this->limpiar($antes),
            'datos_nuevos' => $this->limpiar($despues),
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Quita contraseñas y tokens de los datos antes de guardarlos.
     */
    protected function limpiar(?array $datos): ?array
    {
        if ($datos === null) {
            return null;
        }

        foreach ($datos as $clave => $valor) {
            if (in_array((string) $clave, $this->clavesProhibidas, true)) {
                unset($datos[$clave]);
                continue;
            }

            if (is_array($valor)) {
                $datos[$clave] = $this->limpiar($valor);
            }
        }

        return $datos;
    }
}
