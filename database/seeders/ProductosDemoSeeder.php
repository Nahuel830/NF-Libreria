<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductosDemoSeeder extends Seeder
{
    /**
     * [codigo, nombre, categoria, marca, unidad, precio_compra, precio_venta, stock_minimo, controla_stock]
     *
     * @var array<int, array{string, string, string, ?string, string, string, string, int, bool}>
     */
    protected array $productos = [
        ['CUA-001', 'Cuaderno universitario 100 hojas', 'Cuadernos', 'Norma', 'unidad', '8.50', '12.00', 10, true],
        ['CUA-002', 'Cuaderno cuadriculado 50 hojas', 'Cuadernos', 'Loro', 'unidad', '4.00', '6.50', 10, true],
        ['CUA-003', 'Cuaderno de dibujo espiralado', 'Cuadernos', 'Norma', 'unidad', '6.00', '9.00', 5, true],
        ['LAP-001', 'Lapicero azul punto fino', 'Lapiceros', 'Bic', 'unidad', '1.50', '2.50', 50, true],
        ['LAP-002', 'Lapicero negro punto medio', 'Lapiceros', 'Bic', 'unidad', '1.50', '2.50', 50, true],
        ['LAP-003', 'Lapicero rojo', 'Lapiceros', 'Faber-Castell', 'unidad', '1.80', '3.00', 20, true],
        ['LAP-004', 'Resaltador amarillo', 'Lapiceros', 'Stabilo', 'unidad', '3.50', '6.00', 15, true],
        ['LPI-001', 'Lápiz negro HB caja x12', 'Lápices', 'Faber-Castell', 'caja', '9.00', '14.00', 10, true],
        ['LPI-002', 'Lápices de colores x12', 'Lápices', 'Norma', 'caja', '12.00', '18.50', 8, true],
        ['LPI-003', 'Borrador blanco', 'Lápices', 'Miga', 'unidad', '0.80', '1.50', 30, true],
        ['LPI-004', 'Tajador plástico doble', 'Lápices', 'Miga', 'unidad', '1.20', '2.50', 20, true],
        ['MES-001', 'Regla plástica 30 cm', 'Material escolar', 'Artesco', 'unidad', '1.50', '3.00', 20, true],
        ['MES-002', 'Pegamento en barra 21 g', 'Material escolar', 'Uhu', 'unidad', '4.50', '7.50', 15, true],
        ['MES-003', 'Tijera escolar punta roma', 'Material escolar', 'Artesco', 'unidad', '3.00', '5.50', 12, true],
        ['MES-004', 'Colores de plastilina x10', 'Material escolar', 'Play-Doh', 'caja', '10.00', '16.00', 6, true],
        ['MOF-001', 'Resma papel carta 75 g', 'Material de oficina', 'Chamex', 'resma', '30.00', '38.00', 10, true],
        ['MOF-002', 'Resma papel oficio 75 g', 'Material de oficina', 'Chamex', 'resma', '32.00', '40.00', 8, true],
        ['MOF-003', 'Perforadora mediana', 'Material de oficina', 'Artesco', 'unidad', '18.00', '28.00', 3, true],
        ['MOF-004', 'Engrampadora + grapas', 'Material de oficina', 'Artesco', 'unidad', '22.00', '34.00', 3, true],
        ['LIB-001', 'Diccionario escolar', 'Libros', 'Santillana', 'unidad', '35.00', '48.00', 4, true],
        ['LIB-002', 'Atlas de Bolivia', 'Libros', 'Wálter', 'unidad', '25.00', '36.00', 4, true],
        ['CAR-001', 'Carpeta archivadora lomo ancho', 'Carpetas', 'Artesco', 'unidad', '9.00', '14.00', 10, true],
        ['CAR-002', 'Folder plástico con elástico', 'Carpetas', 'Artesco', 'unidad', '3.50', '6.00', 15, true],
        ['HPA-001', 'Papel bond carta x100 hojas', 'Hojas y papel', 'Chamex', 'paquete', '7.00', '11.00', 12, true],
        ['HPA-002', 'Papel lustre surtido x10', 'Hojas y papel', 'Loro', 'paquete', '2.50', '4.50', 15, true],
        ['ACC-001', 'Mochila escolar mediana', 'Accesorios', 'Totto', 'unidad', '120.00', '165.00', 3, true],
        ['ACC-002', 'Cartuchera con 3 compartimentos', 'Accesorios', 'Norma', 'unidad', '18.00', '28.00', 5, true],
        ['FOT-001', 'Fotocopia blanco y negro', 'Fotocopias e impresiones', null, 'hoja', '0.15', '0.30', 0, false],
        ['FOT-002', 'Impresión a color', 'Fotocopias e impresiones', null, 'hoja', '0.80', '1.50', 0, false],
        ['OTR-001', 'Cinta adhesiva transparente', 'Otros', 'Pegafan', 'unidad', '2.00', '3.50', 10, true],
    ];

    public function run(): void
    {
        $categorias = Categoria::pluck('id', 'nombre');

        $creados = [];

        foreach ($this->productos as [$codigo, $nombre, $categoria, $marca, $unidad, $compra, $venta, $minimo, $controla]) {
            $creados[] = Producto::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'categoria_id' => $categorias[$categoria],
                    'marca' => $marca,
                    'unidad' => $unidad,
                    'precio_compra' => $compra,
                    'precio_venta' => $venta,
                    'stock_minimo' => $minimo,
                    'controla_stock' => $controla,
                ]
            );
        }

        $admin = User::where('usuario', 'admin')->first();

        if (! $admin) {
            return;
        }

        Auth::login($admin);

        DB::transaction(function () use ($creados): void {
            $stock = app(StockService::class);

            foreach ($creados as $producto) {
                if (! $producto->controla_stock) {
                    continue;
                }

                $inicial = random_int(0, 80);

                if ($inicial > 0) {
                    $stock->mover($producto->id, $inicial, 'INICIAL', 'Carga inicial');
                }
            }
        });
    }
}
