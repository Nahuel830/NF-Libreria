<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use App\Services\DevolucionService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevolucionTest extends TestCase
{
    use RefreshDatabase;

    protected function vender(User $usuario, Producto $producto, int $cantidad = 5): \App\Models\Venta
    {
        $this->actingAs($usuario);

        if (! \App\Models\Caja::abiertaDe($usuario)) {
            app(\App\Services\CajaService::class)->abrir($usuario, '0.00');
        }

        return app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => $cantidad]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'EFECTIVO'],
            $usuario
        );
    }

    public function test_devolucion_parcial_devuelve_stock_y_audita(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->vender($encargado, $producto, 5);
        $this->assertSame(-5, $producto->fresh()->stock);

        $devolucion = app(DevolucionService::class)->devolver(
            $venta,
            [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 2]],
            'Producto fallado',
            'EFECTIVO',
            $encargado
        );

        $this->assertSame('20.00', $devolucion->total_devuelto);
        $this->assertSame(-3, $producto->fresh()->stock);
        $this->assertDatabaseHas('movimientos_caja', [
            'tipo' => 'EGRESO', 'monto' => '20.00',
        ]);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id, 'tipo' => 'DEVOLUCION', 'cantidad' => 2,
            'referencia_tipo' => 'devolucion', 'referencia_id' => $devolucion->id,
        ]);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'CREAR', 'entidad' => 'devoluciones', 'entidad_id' => $devolucion->id,
        ]);
    }

    public function test_no_se_puede_devolver_mas_de_lo_vendido(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->vender($encargado, $producto, 5);

        app(DevolucionService::class)->devolver(
            $venta,
            [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 4]],
            'Primera devolución',
            'QR',
            $encargado
        );

        try {
            app(DevolucionService::class)->devolver(
                $venta->fresh(),
                [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 2]],
                'Segunda devolución',
                'QR',
                $encargado
            );
            $this->fail('Debió fallar por exceso.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $this->assertSame(-1, $producto->fresh()->stock);
    }

    public function test_devolucion_sin_control_no_mueve_stock(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $foto = Producto::factory()->create(['controla_stock' => false, 'precio_venta' => '0.30']);
        $venta = $this->vender($encargado, $foto, 10);

        $devolucion = app(DevolucionService::class)->devolver(
            $venta,
            [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 4]],
            'Salieron mal',
            'EFECTIVO',
            $encargado
        );

        $this->assertSame('1.20', $devolucion->total_devuelto);
        $this->assertSame(0, $foto->fresh()->stock);
        $this->assertDatabaseMissing('movimientos_stock', ['producto_id' => $foto->id]);
    }

    public function test_reportes_restan_devoluciones(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->vender($encargado, $producto, 2);

        app(DevolucionService::class)->devolver(
            $venta,
            [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 1]],
            'Una unidad',
            'EFECTIVO',
            $encargado
        );

        $hoy = today()->toDateString();
        $this->actingAs($encargado)->get("/reportes/resumen?desde={$hoy}&hasta={$hoy}")
            ->assertOk()
            ->assertSee('Bs. 10,00');
    }

    public function test_permisos_y_motivo(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->vender($encargado, $producto, 1);

        $this->actingAs($cajero)->get("/ventas/{$venta->id}/devoluciones/crear")->assertForbidden();
        $this->actingAs($cajero)->post("/ventas/{$venta->id}/devoluciones", [])->assertForbidden();

        $this->actingAs($encargado)->post("/ventas/{$venta->id}/devoluciones", [
            'motivo' => 'abc',
            'metodo_reembolso' => 'EFECTIVO',
            'items' => [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('motivo');
    }

    public function test_ticket_devolucion_y_anular_despues_revierte_neto(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->vender($encargado, $producto, 4);

        $devolucion = app(DevolucionService::class)->devolver(
            $venta,
            [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 1]],
            'Una unidad',
            'EFECTIVO',
            $encargado
        );

        $this->actingAs($encargado)->get("/devoluciones/{$devolucion->id}/ticket")
            ->assertOk()
            ->assertSee('DEVOLUCIÓN')
            ->assertSee('Bs. 10,00');

        app(VentaService::class)->anular($venta->fresh(), 'Anulación tras devolución', $encargado);

        // Vendió 4, devolvió 1, anula 3 → stock vuelve a 0.
        $this->assertSame(0, $producto->fresh()->stock);
    }

    public function test_cajero_no_ve_ticket_de_devolucion_ajena(): void
    {
        $cajero1 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'cj1']);
        $cajero2 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'cj2']);
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $this->actingAs($encargado);

        if (! \App\Models\Caja::abiertaDe($encargado)) {
            app(\App\Services\CajaService::class)->abrir($encargado, '0.00');
        }

        if (! \App\Models\Caja::abiertaDe($cajero1)) {
            app(\App\Services\CajaService::class)->abrir($cajero1, '0.00');
        }

        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            ['token' => (string) \Illuminate\Support\Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero1
        );
        $devolucion = app(\App\Services\DevolucionService::class)->devolver(
            $venta,
            [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 1]],
            'Falla de prueba',
            'QR',
            $encargado
        );

        $this->actingAs($cajero2)->get("/devoluciones/{$devolucion->id}/ticket")->assertForbidden();
        $this->actingAs($cajero1)->get("/devoluciones/{$devolucion->id}/ticket")->assertOk();
    }
}
