<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use App\Services\ConfiguracionService;
use App\Services\EntradaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    protected function vender(User $usuario, Producto $producto, int $cantidad = 1, string $metodo = 'EFECTIVO'): \App\Models\Venta
    {
        $this->actingAs($usuario);

        return app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => $cantidad]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => $metodo],
            $usuario
        );
    }

    public function test_totales_del_dia_excluyen_anuladas(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->vender($encargado, $producto, 1);
        $anulada = $this->vender($encargado, $producto, 2);
        app(VentaService::class)->anular($anulada, 'Prueba reportes', $encargado);

        $this->actingAs($encargado)->get('/')
            ->assertOk()
            ->assertSee('Bs. 10,00');
    }

    public function test_productos_mas_vendidos_ordena_y_excluye_anuladas(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $top = Producto::factory()->create(['nombre' => 'Producto Top', 'codigo' => 'TOP-001', 'precio_venta' => '5.00']);
        $otro = Producto::factory()->create(['nombre' => 'Producto Otro', 'codigo' => 'OTR-999', 'precio_venta' => '5.00']);

        $this->vender($encargado, $top, 5);
        $this->vender($encargado, $otro, 1);
        $anulada = $this->vender($encargado, $otro, 10);
        app(VentaService::class)->anular($anulada, 'Prueba top', $encargado);

        $respuesta = $this->actingAs($encargado)->get('/reportes/productos');
        $respuesta->assertOk();

        $contenido = $respuesta->getContent();
        $this->assertTrue(strpos($contenido, 'Producto Top') < strpos($contenido, 'Producto Otro'));
    }

    public function test_exportacion_csv_devuelve_contenido_y_encabezados(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);

        $this->actingAs($encargado)->get('/reportes/resumen?formato=csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('fecha;cantidad;total;descuentos;anuladas');
    }

    public function test_cajero_recibe_403_en_reportes(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        $this->actingAs($cajero)->get('/reportes')->assertForbidden();
        $this->actingAs($cajero)->get('/reportes/resumen')->assertForbidden();
        $this->actingAs($cajero)->get('/')->assertOk();
    }

    public function test_inventario_valorizado_calcula_bien(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $producto = Producto::factory()->create(['precio_compra' => '10.00', 'precio_venta' => '15.00']);

        app(EntradaService::class)->registrar(
            ['actualizar_precio_compra' => false],
            [['producto_id' => $producto->id, 'cantidad' => 5, 'costo_unitario' => '10.00']],
            $admin
        );

        app(ConfiguracionService::class)->set('permitir_stock_negativo', '1');

        $this->actingAs($admin)->get('/reportes/inventario')
            ->assertOk()
            ->assertSee('Bs. 50,00')
            ->assertSee('Bs. 75,00');
    }

    public function test_cierre_muestra_totales_y_anuladas_con_motivo(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->vender($encargado, $producto, 1);
        $anulada = $this->vender($encargado, $producto, 1);
        app(\App\Services\VentaService::class)->anular($anulada, 'Cierre prueba', $encargado);

        $respuesta = $this->actingAs($encargado)->get('/reportes/cierre?fecha='.today()->toDateString());

        $respuesta->assertOk()
            ->assertSee('Bs. 10,00')
            ->assertSee('Cierre prueba')
            ->assertSee($anulada->numero());
    }

    public function test_estilos_no_accesible_fuera_de_local(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);

        // En testing (no local) responde 404 aunque sea admin.
        $this->actingAs($admin)->get('/estilos')->assertNotFound();
    }
}
