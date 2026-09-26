<?php

namespace App\Services;

use App\Models\Configuracion;

class ConfiguracionService
{
    /**
     * Caché en memoria durante el request actual.
     *
     * @var array<string, mixed>
     */
    protected array $cache = [];

    protected bool $cargadoTodo = false;

    public function get(string $clave, $defecto = null)
    {
        if (array_key_exists($clave, $this->cache)) {
            return $this->cache[$clave];
        }

        $valor = Configuracion::where('clave', $clave)->value('valor');

        if ($valor === null) {
            return $defecto;
        }

        $this->cache[$clave] = $valor;

        return $valor;
    }

    public function set(string $clave, $valor): void
    {
        Configuracion::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor === null ? null : (string) $valor]
        );

        $this->cache[$clave] = $valor === null ? null : (string) $valor;
    }

    public function olvidar(?string $clave = null): void
    {
        if ($clave === null) {
            $this->cache = [];
            $this->cargadoTodo = false;

            return;
        }

        unset($this->cache[$clave]);
    }
}
