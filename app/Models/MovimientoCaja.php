<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    public $timestamps = false;

    protected $table = 'movimientos_caja';

    protected $fillable = ['caja_id', 'tipo', 'user_id', 'monto', 'concepto', 'created_at'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
