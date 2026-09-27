<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntradaStock extends Model
{
    protected $table = 'entradas_stock';

    protected $fillable = [
        'fecha',
        'proveedor',
        'proveedor_id',
        'documento_referencia',
        'observaciones',
        'total',
        'estado',
        'user_id',
        'anulada_por',
        'anulada_en',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'total' => 'decimal:2',
            'anulada_en' => 'datetime',
        ];
    }

    public function detalles()
    {
        return $this->hasMany(DetalleEntrada::class, 'entrada_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proveedorVinculado()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function numero(): string
    {
        return '#'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
