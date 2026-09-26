<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function vender(User $usuario, Producto $producto, int $cantidad = 1): Venta
    {
        $this->actingAs($usuario);

        return app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => $cantidad]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $usuario
        );
    }

    public function test_cajero_ve_solo_sus_ventas_del_dia_aunque_manipule_filtros(): void
    {
        $cajero1 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'cajero1']);
        $cajero2 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'cajero2']);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);

        $propia = $this->vender($cajero1, $producto);
        $ajena = $this->vender($cajero2, $producto);

        $respuesta = $this->actingAs($cajero1)->get('/ventas?cajero_id='.$cajero2->id);

        $respuesta->assertOk()
            ->assertSee($propia->numero())
            ->assertDontSee($ajena->numero());
    }

    public function test_encargado_ve_todas_y_filtra(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);

        $venta = $this->vender($cajero, $producto);

        $this->actingAs($encargado)->get('/ventas')
            ->assertOk()
            ->assertSee($venta->numero());

        $this->actingAs($encargado)->get('/ventas?cajero_id='.$cajero->id)
            ->assertOk()
            ->assertSee($venta->numero());

        $this->actingAs($encargado)->get('/ventas?cajero_id='.$encargado->id)
            ->assertOk()
            ->assertDontSee($venta->numero());
    }

    public function test_resumen_suma_solo_completadas(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);

        $v1 = $this->vender($encargado, $producto, 1);
        $v2 = $this->vender($encargado, $producto, 2);
        app(VentaService::class)->anular($v2, 'Prueba resumen', $encargado);

        $respuesta = $this->actingAs($encargado)->get('/ventas');

        $respuesta->assertOk()->assertSee('Bs. 10,00');
        $this->assertStringNotContainsString('Bs. 30,00', $respuesta->getContent());
    }

    public function test_anular_desde_la_interfaz_devuelve_stock_y_cajero_recibe_403(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        \Illuminate\Support\Facades\DB::table('productos')->where('id', $producto->id)->update(['stock' => 5]);

        $venta = $this->vender($cajero, $producto, 2);
        $this->assertSame(3, $producto->fresh()->stock);

        $this->actingAs($cajero)->post("/ventas/{$venta->id}/anular", ['motivo' => 'Me equivoqué'])
            ->assertForbidden();

        $this->actingAs($encargado)->post("/ventas/{$venta->id}/anular", ['motivo' => 'Me equivoqué'])
            ->assertRedirect(route('ventas.ver', $venta));

        $this->assertSame(5, $producto->fresh()->stock);
        $this->assertSame('ANULADA', $venta->fresh()->estado);
        $this->assertDatabaseHas('auditoria', ['accion' => 'ANULAR', 'entidad_id' => $venta->id]);
    }

    public function test_no_se_puede_anular_sin_motivo(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->vender($encargado, $producto);

        $this->actingAs($encargado)->post("/ventas/{$venta->id}/anular", ['motivo' => 'abc'])
            ->assertSessionHasErrors('motivo');

        $this->assertSame('COMPLETADA', $venta->fresh()->estado);
    }

    public function test_cajero_no_ve_boton_anular_en_el_detalle(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->vender($cajero, $producto);

        $this->actingAs($cajero)->get("/ventas/{$venta->id}")
            ->assertOk()
            ->assertDontSee('Anular venta');
    }
}
