<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Caja;
use App\Models\Producto;
use App\Models\User;
use App\Services\CajaService;
use App\Services\ConfiguracionService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CajaModuloTest extends TestCase
{
    use RefreshDatabase;

    protected function servicio(): CajaService
    {
        return app(CajaService::class);
    }

    protected function abrirPara(User $usuario): Caja
    {
        $this->actingAs($usuario);

        return $this->servicio()->abrir($usuario, '100.00');
    }

    protected function vender(User $usuario, Producto $producto, int $cantidad, string $metodo = 'EFECTIVO'): \App\Models\Venta
    {
        return app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => $cantidad]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => $metodo],
            $usuario
        );
    }

    public function test_no_se_puede_vender_sin_caja_abierta_y_si_con_opcion_off(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);

        app(ConfiguracionService::class)->set('exigir_caja_abierta', '1');

        $this->actingAs($cajero)->post('/ventas', [
            'token' => (string) Str::uuid(),
            'metodo_pago' => 'QR',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('venta');

        app(ConfiguracionService::class)->set('exigir_caja_abierta', '0');

        $this->actingAs($cajero)->post('/ventas', [
            'token' => (string) Str::uuid(),
            'metodo_pago' => 'QR',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertRedirect();

        $this->assertNull(\App\Models\Venta::latest('id')->first()->caja_id);
    }

    public function test_usuario_no_puede_abrir_dos_cajas(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->abrirPara($cajero);

        $this->actingAs($cajero)->post('/caja/abrir', ['monto_inicial' => '50.00'])
            ->assertSessionHasErrors('monto_inicial');

        $this->assertSame(1, Caja::where('user_id', $cajero->id)->where('estado', 'ABIERTA')->count());
    }

    public function test_efectivo_esperado_correcto_y_diferencia(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero);
        $caja = $this->servicio()->abrir($cajero, '100.00');
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->vender($cajero, $producto, 2, 'EFECTIVO'); // 20
        $this->vender($cajero, $producto, 1, 'QR'); // no suma
        $anulada = $this->vender($cajero, $producto, 1, 'EFECTIVO');
        app(VentaService::class)->anular($anulada, 'Prueba caja', User::factory()->create(['rol' => Rol::Encargado]));

        $this->servicio()->movimiento($caja, 'INGRESO', '15.00', 'Aporte', $cajero);
        $this->servicio()->movimiento($caja, 'EGRESO', '5.00', 'Fotocopias', $cajero);

        // 100 + 20 + 15 − 5 = 130
        $this->assertSame('130.00', $this->servicio()->efectivoEsperado($caja->fresh()));

        // Sobrante.
        $this->servicio()->cerrar($caja->fresh(), '135.00', [], 'Sobrante de prueba', $cajero);
        $this->assertSame('5.00', $caja->fresh()->diferencia);

        // Faltante con observaciones.
        $caja2 = $this->servicio()->abrir($cajero, '0.00');
        $this->servicio()->cerrar($caja2, '3.00', [], 'Faltan monedas', $cajero);
        $this->assertSame('3.00', $caja2->fresh()->diferencia);
    }

    public function test_cierre_sin_observaciones_con_diferencia_falla(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero);
        $caja = $this->servicio()->abrir($cajero, '10.00');

        $this->actingAs($cajero)->post("/caja/{$caja->id}/cerrar", [])
            ->assertSessionHasErrors('observaciones');

        $this->assertSame('ABIERTA', $caja->fresh()->estado);
    }

    public function test_caja_cerrada_no_acepta_movimientos_ni_ventas(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero);
        $caja = $this->servicio()->abrir($cajero, '10.00');
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $this->servicio()->cerrar($caja->fresh(), '10.00', [], null, $cajero);

        try {
            $this->servicio()->movimiento($caja->fresh(), 'INGRESO', '5.00', 'X', $cajero);
            $this->fail('Debió fallar en caja cerrada.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $this->actingAs($cajero)->post('/ventas', [
            'token' => (string) Str::uuid(),
            'metodo_pago' => 'QR',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('venta');
    }

    public function test_encargado_cierra_caja_de_cajero_y_cajero_no_la_de_otro(): void
    {
        $cajero1 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'c1']);
        $cajero2 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'c2']);
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);

        $caja1 = $this->servicio()->abrir($cajero1, '10.00');
        $caja2 = $this->servicio()->abrir($cajero2, '10.00');

        $this->actingAs($encargado)->post("/caja/{$caja1->id}/cerrar", ['observaciones' => 'Cierre por encargado'])
            ->assertRedirect(route('caja.ver', $caja1));
        $this->assertSame($encargado->id, $caja1->fresh()->cerrada_por);

        $this->actingAs($cajero1)->post("/caja/{$caja2->id}/cerrar", [])
            ->assertForbidden();
        $this->assertSame('ABIERTA', $caja2->fresh()->estado);
    }

    public function test_mi_caja_e_historial_permisos(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);

        $this->actingAs($cajero)->get('/caja')->assertOk();
        $this->actingAs($cajero)->get('/caja-historial')->assertForbidden();
        $this->actingAs($encargado)->get('/caja-historial')->assertOk();
    }
}
