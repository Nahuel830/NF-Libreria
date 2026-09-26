<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['usuario' => 'encargado'],
            [
                'nombre' => 'Encargado Demo',
                'password' => 'demo12345',
                'rol' => Rol::Encargado,
                'activo' => true,
                'debe_cambiar_password' => false,
            ]
        );

        User::updateOrCreate(
            ['usuario' => 'cajero1'],
            [
                'nombre' => 'Cajero Demo',
                'password' => 'demo12345',
                'rol' => Rol::Cajero,
                'activo' => true,
                'debe_cambiar_password' => false,
            ]
        );
    }
}
