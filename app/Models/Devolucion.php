<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    protected $table = 'devoluciones';

    protected $fillable = [
        'venta_id', 'user_id', 'fecha', 'motivo', 'total_devuelto', 'metodo_reembolso',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'total_devuelto' => 'decimal:2',
        ];
    }

    public function detalles()
    {
        return $this->hasMany(DetalleDevolucion::class, 'devolucion_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function numero(): string
    {
        return '#'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
