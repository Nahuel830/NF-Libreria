<?php

namespace App\Enums;

enum MetodoPago: string
{
    case Efectivo = 'EFECTIVO';
    case Qr = 'QR';
    case Transferencia = 'TRANSFERENCIA';
    case Tarjeta = 'TARJETA';
    case Otro = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Qr => 'QR',
            self::Transferencia => 'Transferencia',
            self::Tarjeta => 'Tarjeta',
            self::Otro => 'Otro',
        };
    }
}
