<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuarioAdminSeeder extends Seeder
{
    public function run(): void
    {
        $clave = env('ADMIN_PASSWORD_INICIAL');

        if (! is_string($clave) || $clave === '') {
            throw new \RuntimeException(
                'Falta la variable ADMIN_PASSWORD_INICIAL en el .env para crear el usuario admin.'
            );
        }

        User::updateOrCreate(
            ['usuario' => 'admin'],
            [
                'nombre' => 'Administrador',
                'password' => $clave,
                'rol' => Rol::Admin,
                'activo' => true,
                'debe_cambiar_password' => true,
            ]
        );
    }
}
