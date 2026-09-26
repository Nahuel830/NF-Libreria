<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriasSeeder extends Seeder
{
    /**
     * @var string[]
     */
    protected array $nombres = [
        'Cuadernos',
        'Lapiceros',
        'Lápices',
        'Material escolar',
        'Material de oficina',
        'Libros',
        'Carpetas',
        'Hojas y papel',
        'Accesorios',
        'Fotocopias e impresiones',
        'Otros',
    ];

    public function run(): void
    {
        foreach ($this->nombres as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
