<?php

namespace Tests\Feature;

use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\ConfiguracionService;
use App\Services\VentaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected function servicio(): VentaService
    {
        return app(VentaService::class);
    }

    protected function vendedor(Rol $rol = Rol::Cajero): User
    {
        $usuario = User::factory()->create(['rol' => $rol]);
        $this->actingAs($usuario);

        if (! \App\Models\Caja::abiertaDe($usuario)) {
            app(\App\Services\CajaService::class)->abrir($usuario, '0.00');
        }

        return $usuario;
    }

    protected function permitirNegativo(string $valor): void
    {
        app(ConfiguracionService::class)->set('permitir_stock_negativo', $valor);
    }

    /**
     * @return array{items: array<int, array{producto_id: int, cantidad: int}>, datos: array<string, mixed>}
     */
    protected function ventaSimple(Producto $a, Producto $b, int $cantA = 2, int $cantB = 1): array
    {
        return [
            'items' => [
                ['producto_id' => $a->id, 'cantidad' => $cantA],
                ['producto_id' => $b->id, 'cantidad' => $cantB],
            ],
            'datos' => [
                'token' => (string) Str::uuid(),
                'metodo_pago' => MetodoPago::Efectivo->value,
            ],
        ];
    }

    public function test_venta_de_dos_productos_descuenta_stock_y_calcula_totales(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '10.00']);
        $b = Producto::factory()->create(['precio_venta' => '5.00']);
        ['items' => $items, 'datos' => $datos] = $this->ventaSimple($a, $b);

        $venta = $this->servicio()->registrar($items, $datos, $cajero);

        $this->assertSame('25.00', $venta->subtotal);
        $this->assertSame('0.00', $venta->descuento);
        $this->assertSame('25.00', $venta->total);
        $this->assertSame(-2, $a->fresh()->stock);
        $this->assertSame(-1, $b->fresh()->stock);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $a->id, 'tipo' => 'VENTA', 'cantidad' => -2,
            'referencia_tipo' => 'venta', 'referencia_id' => $venta->id,
        ]);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $b->id, 'tipo' => 'VENTA', 'cantidad' => -1,
            'referencia_tipo' => 'venta', 'referencia_id' => $venta->id,
        ]);
    }

    public function test_precio_usado_es_el_de_la_bd(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1, 'precio_unitario' => '1.00', 'precio' => '0.01']],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'EFECTIVO'],
            $cajero
        );

        $this->assertSame('10.00', $venta->total);
        $this->assertSame('10.00', $venta->detalles->first()->precio_unitario);
    }

    public function test_detalle_guarda_foto_del_momento(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['nombre' => 'Nombre Viejo', 'codigo' => 'FOTO-001', 'precio_venta' => '10.00']);

        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );

        $a->forceFill(['nombre' => 'Nombre Nuevo', 'precio_venta' => '99.00'])->save();

        $detalle = $venta->detalles->first()->fresh();
        $this->assertSame('Nombre Viejo', $detalle->nombre_producto);
        $this->assertSame('FOTO-001', $detalle->codigo_producto);
        $this->assertSame('10.00', $detalle->precio_unitario);
    }

    public function test_producto_repetido_se_agrupa(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '4.00']);

        $venta = $this->servicio()->registrar(
            [
                ['producto_id' => $a->id, 'cantidad' => 2],
                ['producto_id' => $a->id, 'cantidad' => 3],
            ],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );

        $this->assertCount(1, $venta->detalles);
        $this->assertSame(5, $venta->detalles->first()->cantidad);
        $this->assertSame('20.00', $venta->total);
    }

    public function test_mismo_token_dos_veces_crea_una_sola_venta(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '4.00']);
        $token = (string) Str::uuid();
        $items = [['producto_id' => $a->id, 'cantidad' => 1]];

        $primera = $this->servicio()->registrar($items, ['token' => $token, 'metodo_pago' => 'QR'], $cajero);
        $segunda = $this->servicio()->registrar($items, ['token' => $token, 'metodo_pago' => 'QR'], $cajero);

        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame(1, Venta::where('token', $token)->count());
        $this->assertSame(-1, $a->fresh()->stock);
    }

    public function test_producto_inactivo_falla_sin_guardar_nada(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['activo' => false]);

        try {
            $this->servicio()->registrar(
                [['producto_id' => $a->id, 'cantidad' => 1]],
                ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
                $cajero
            );
            $this->fail('Debió fallar con producto inactivo.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, $a->fresh()->stock);
        $this->assertDatabaseMissing('movimientos_stock', ['producto_id' => $a->id]);
    }

    public function test_stock_insuficiente_falla_con_negativo_bloqueado_y_pasa_permitido(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '4.00']);

        $this->permitirNegativo('0');

        try {
            $this->servicio()->registrar(
                [['producto_id' => $a->id, 'cantidad' => 5]],
                ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
                $cajero
            );
            $this->fail('Debió fallar por stock insuficiente.');
        } catch (\App\Exceptions\StockInsuficienteException) {
            $this->assertTrue(true);
        }

        $this->assertSame(0, Venta::count());

        $this->permitirNegativo('1');

        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 5]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );

        $this->assertSame(-5, $a->fresh()->stock);
        $this->assertSame('COMPLETADA', $venta->estado);
    }

    public function test_producto_sin_control_de_stock_se_vende_sin_mover(): void
    {
        $cajero = $this->vendedor();
        $foto = Producto::factory()->create(['controla_stock' => false, 'precio_venta' => '0.30']);

        $venta = $this->servicio()->registrar(
            [['producto_id' => $foto->id, 'cantidad' => 10]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'EFECTIVO'],
            $cajero
        );

        $this->assertSame('3.00', $venta->total);
        $this->assertSame(0, $foto->fresh()->stock);
        $this->assertDatabaseMissing('movimientos_stock', ['producto_id' => $foto->id]);
    }

    public function test_descuento_solo_con_permiso_y_sin_superar_subtotal(): void
    {
        $cajero = $this->vendedor(Rol::Cajero);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        try {
            $this->servicio()->registrar(
                [['producto_id' => $a->id, 'cantidad' => 1]],
                ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR', 'descuento' => '2.00'],
                $cajero
            );
            $this->fail('El cajero no debería aplicar descuentos.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $encargado = $this->vendedor(Rol::Encargado);

        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR', 'descuento' => '2.00'],
            $encargado
        );

        $this->assertSame('10.00', $venta->subtotal);
        $this->assertSame('2.00', $venta->descuento);
        $this->assertSame('8.00', $venta->total);
        $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR', 'entidad_id' => $venta->id]);

        try {
            $this->servicio()->registrar(
                [['producto_id' => $a->id, 'cantidad' => 1]],
                ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR', 'descuento' => '50.00'],
                $encargado
            );
            $this->fail('El descuento no puede superar el subtotal.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }
    }

    public function test_efectivo_valida_recibido_y_calcula_cambio(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '25.00']);
        $b = Producto::factory()->create(['precio_venta' => '12.50']);

        try {
            $this->servicio()->registrar(
                [['producto_id' => $a->id, 'cantidad' => 1], ['producto_id' => $b->id, 'cantidad' => 1]],
                ['token' => (string) Str::uuid(), 'metodo_pago' => 'EFECTIVO', 'monto_recibido' => '30.00'],
                $cajero
            );
            $this->fail('Debió rechazar monto menor al total.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1], ['producto_id' => $b->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'EFECTIVO', 'monto_recibido' => '50.00'],
            $cajero
        );

        $this->assertSame('37.50', $venta->total);
        $this->assertSame('50.00', $venta->monto_recibido);
        $this->assertSame('12.50', $venta->cambio);
    }

    public function test_precision_decimal_exacta(): void
    {
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '0.30']);
        $b = Producto::factory()->create(['precio_venta' => '12.35']);

        $v1 = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 3]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );
        $this->assertSame('0.90', $v1->total);

        $v2 = $this->servicio()->registrar(
            [['producto_id' => $b->id, 'cantidad' => 7]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );
        $this->assertSame('86.45', $v2->total);
    }

    public function test_anulacion_devuelve_stock_audita_y_no_se_repite(): void
    {
        $encargado = $this->vendedor(Rol::Encargado);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);
        DB::table('productos')->where('id', $a->id)->update(['stock' => 5]);

        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 2]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $encargado
        );
        $this->assertSame(3, $a->fresh()->stock);

        $this->servicio()->anular($venta, 'Producto equivocado', $encargado);

        $this->assertSame(5, $a->fresh()->stock);
        $this->assertSame('ANULADA', $venta->fresh()->estado);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $a->id, 'tipo' => 'ANULACION_VENTA', 'cantidad' => 2,
        ]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'ANULAR', 'entidad_id' => $venta->id]);

        try {
            $this->servicio()->anular($venta->fresh(), 'Otra vez', $encargado);
            $this->fail('No se puede anular dos veces.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }
    }

    public function test_cajero_no_puede_anular(): void
    {
        $cajero = $this->vendedor(Rol::Cajero);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );

        $this->expectException(AuthorizationException::class);
        $this->servicio()->anular($venta, 'No me gusta', $cajero);
    }

    public function test_anular_venta_con_limite_de_intentos(): void
    {
        $encargado = $this->vendedor(Rol::Encargado);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);
        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $encargado
        );

        $this->actingAs($encargado)
            ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Error de cobro'])
            ->assertRedirect();

        for ($i = 0; $i < 29; $i++) {
            $this->actingAs($encargado)
                ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Error de cobro'])
                ->assertSessionHasErrors('motivo');
        }

        $this->actingAs($encargado)
            ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Error de cobro'])
            ->assertStatus(429);
    }

    public function test_concurrencia_simulada_segunda_venta_falla(): void
    {
        // Con stock negativo desactivado, dos ventas seguidas del último ítem:
        // la primera pasa y la segunda falla. El bloqueo real entre cajas
        // concurrentes lo garantiza lockForUpdate() dentro de la transacción.
        $this->permitirNegativo('0');
        $cajero = $this->vendedor();
        $a = Producto::factory()->create(['precio_venta' => '10.00']);
        DB::table('productos')->where('id', $a->id)->update(['stock' => 1]);

        $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );

        try {
            $this->servicio()->registrar(
                [['producto_id' => $a->id, 'cantidad' => 1]],
                ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
                $cajero
            );
            $this->fail('La segunda venta debió fallar por falta de stock.');
        } catch (\App\Exceptions\StockInsuficienteException) {
            $this->assertSame(0, $a->fresh()->stock);
        }
    }

    public function test_verificar_sin_diferencias_despues_de_ventas_y_anulaciones(): void
    {
        $encargado = $this->vendedor(Rol::Encargado);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $venta = $this->servicio()->registrar(
            [['producto_id' => $a->id, 'cantidad' => 2]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $encargado
        );
        $this->servicio()->anular($venta, 'Prueba de consistencia', $encargado);

        $this->assertCount(0, app(\App\Services\StockService::class)->verificarConsistencia());
    }
}
