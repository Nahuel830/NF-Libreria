<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ConfiguracionSeeder::class,
            UsuarioAdminSeeder::class,
            CategoriasSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                UsuariosDemoSeeder::class,
                ProductosDemoSeeder::class,
                VentasDemoSeeder::class,
            ]);
        }
    }
}
