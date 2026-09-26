<?php

namespace App\Services;

use App\Models\Auditoria;
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
        ?Model $entidad = null,
        ?array $antes = null,
        ?array $despues = null
    ): Auditoria {
        return Auditoria::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad ? $entidad->getTable() : null,
            'entidad_id' => $entidad?->getKey(),
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
