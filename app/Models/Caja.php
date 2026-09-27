<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $fillable = [
        'user_id', 'abierta_en', 'monto_inicial', 'cerrada_en', 'cerrada_por',
        'efectivo_esperado', 'efectivo_contado', 'diferencia', 'detalle_conteo',
        'observaciones_cierre', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'abierta_en' => 'datetime',
            'cerrada_en' => 'datetime',
            'monto_inicial' => 'decimal:2',
            'efectivo_esperado' => 'decimal:2',
            'efectivo_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'detalle_conteo' => 'array',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cerradaPor()
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'caja_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'caja_id');
    }

    public static function abiertaDe(User $usuario): ?self
    {
        return static::where('user_id', $usuario->id)->where('estado', 'ABIERTA')->first();
    }
}
