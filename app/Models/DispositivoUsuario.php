<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispositivoUsuario extends Model
{
    protected $table = 'dispositivos_usuario';

    protected $fillable = [
        'user_id',
        'hash',
        'user_agent',
        'ip',
        'primer_uso',
        'ultimo_uso',
    ];

    protected function casts(): array
    {
        return [
            'primer_uso' => 'datetime',
            'ultimo_uso' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
