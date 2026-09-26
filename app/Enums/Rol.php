<?php

namespace App\Enums;

enum Rol: string
{
    case Admin = 'admin';
    case Encargado = 'encargado';
    case Cajero = 'cajero';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Encargado => 'Encargado',
            self::Cajero => 'Cajero',
        };
    }
}
