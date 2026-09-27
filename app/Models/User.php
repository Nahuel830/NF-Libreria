<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nombre', 'usuario', 'password', 'rol', 'activo', 'debe_cambiar_password', 'ultimo_acceso'])]
#[Hidden(['password', 'remember_token', 'totp_secreto', 'codigos_recuperacion'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rol' => Rol::class,
            'activo' => 'boolean',
            'debe_cambiar_password' => 'boolean',
            'totp_secreto' => 'encrypted',
            'totp_confirmado_en' => 'datetime',
            'totp_ultimo_paso' => 'integer',
            'codigos_recuperacion' => 'encrypted:array',
            'ultimo_acceso' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tieneTotpActivo(): bool
    {
        return $this->totp_confirmado_en !== null;
    }
}
