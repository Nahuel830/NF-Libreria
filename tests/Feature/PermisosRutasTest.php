<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use App\Services\CajaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermisosRutasTest extends TestCase
{
    use RefreshDatabase;

    protected function abrirCaja(User $usuario): void
    {
        if (! \App\Models\Caja::abiertaDe($usuario)) {
            app(CajaService::class)->abrir($usuario, '0.00');
        }
    }

    public function test_encargado_recibe_403_en_gestion_de_usuarios_y_configuracion(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $otro = User::factory()->create();

        $this->actingAs($encargado)->post('/usuarios', [])->assertForbidden();
        $this->actingAs($encargado)->put("/usuarios/{$otro->id}", [])->assertForbidden();
        $this->actingAs($encargado)->patch("/usuarios/{$otro->id}/estado")->assertForbidden();
        $this->actingAs($encargado)->put('/configuracion', [])->assertForbidden();
        $this->actingAs($encargado)->get('/auditoria')->assertForbidden();
    }

    public function test_cajero_recibe_403_en_rutas_que_modifican(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $categoria = \App\Models\Categoria::factory()->create();
        $producto = Producto::factory()->create();
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $entrada = app(\App\Services\EntradaService::class)->registrar(
            ['actualizar_precio_compra' => false],
            [['producto_id' => $producto->id, 'cantidad' => 1, 'costo_unitario' => '1.00']],
            $admin
        );

        $this->actingAs($cajero)->post('/categorias', [])->assertForbidden();
        $this->actingAs($cajero)->put("/categorias/{$categoria->id}", [])->assertForbidden();
        $this->actingAs($cajero)->patch("/categorias/{$categoria->id}/estado")->assertForbidden();
        $this->actingAs($cajero)->post('/productos', [])->assertForbidden();
        $this->actingAs($cajero)->put("/productos/{$producto->id}", [])->assertForbidden();
        $this->actingAs($cajero)->patch("/productos/{$producto->id}/estado")->assertForbidden();
        $this->actingAs($cajero)->put("/productos/{$producto->id}/ajustar", [])->assertForbidden();
        $this->actingAs($cajero)->post('/entradas', [])->assertForbidden();
        $this->actingAs($cajero)->post("/entradas/{$entrada->id}/anular", [])->assertForbidden();
        $this->actingAs($cajero)->post('/productos/importar/confirmar', [])->assertForbidden();
        $this->actingAs($cajero)->post('/clientes', [])->assertForbidden();
        $this->actingAs($cajero)->post('/proveedores', [])->assertForbidden();
        $this->actingAs($cajero)->get('/reportes')->assertForbidden();
        $this->actingAs($cajero)->get('/caja-historial')->assertForbidden();
        $this->actingAs($cajero)->get('/estilos')->assertForbidden();
    }

    public function test_movimiento_de_caja_por_http_con_advertencia(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero);
        $this->abrirCaja($cajero);
        $caja = \App\Models\Caja::abiertaDe($cajero);

        $this->actingAs($cajero)->post("/caja/{$caja->id}/movimiento", [
            'tipo' => 'INGRESO', 'monto' => '20.00', 'concepto' => 'Aporte',
        ])->assertRedirect(route('caja.mi-caja'));

        // Egreso mayor al disponible: primero advierte...
        $this->actingAs($cajero)->post("/caja/{$caja->id}/movimiento", [
            'tipo' => 'EGRESO', 'monto' => '500.00', 'concepto' => 'Grande',
        ])->assertSessionHas('warning');

        // ...y con confirmación se guarda.
        $this->actingAs($cajero)->post("/caja/{$caja->id}/movimiento", [
            'tipo' => 'EGRESO', 'monto' => '500.00', 'concepto' => 'Grande', 'confirmar_egreso' => '1',
        ])->assertRedirect(route('caja.mi-caja'));

        $this->assertDatabaseHas('movimientos_caja', ['caja_id' => $caja->id, 'tipo' => 'EGRESO']);
    }

    public function test_devolucion_por_http_flujo_completo(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $this->actingAs($encargado);
        $this->abrirCaja($encargado);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => 2]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $encargado
        );

        $this->actingAs($encargado)->get("/ventas/{$venta->id}/devoluciones/crear")->assertOk();

        $respuesta = $this->actingAs($encargado)->post("/ventas/{$venta->id}/devoluciones", [
            'motivo' => 'Devolución por HTTP',
            'metodo_reembolso' => 'EFECTIVO',
            'items' => [['detalle_venta_id' => $venta->detalles->first()->id, 'cantidad' => 1]],
        ]);

        $devolucion = \App\Models\Devolucion::latest('id')->first();
        $respuesta->assertRedirect(route('devoluciones.ticket', $devolucion));
        $this->assertSame('10.00', $devolucion->total_devuelto);
    }
}
