<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => mb_strtoupper(fake()->unique()->bothify('PRD-####')),
            'nombre' => fake()->words(3, true),
            'descripcion' => null,
            'categoria_id' => Categoria::factory(),
            'marca' => null,
            'unidad' => 'unidad',
            'precio_compra' => '5.00',
            'precio_venta' => '8.00',
            'stock_minimo' => 0,
            'controla_stock' => true,
            'activo' => true,
        ];
    }
}
