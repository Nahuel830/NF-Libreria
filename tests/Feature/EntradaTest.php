<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\EntradaStock;
use App\Models\Producto;
use App\Models\User;
use App\Services\EntradaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntradaTest extends TestCase
{
    use RefreshDatabase;

    protected function servicio(): EntradaService
    {
        return app(EntradaService::class);
    }

    public function test_registrar_entrada_suma_stock_y_crea_movimientos_con_referencia(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $a = Producto::factory()->create(['precio_compra' => '5.00']);
        $b = Producto::factory()->create(['precio_compra' => '3.00']);

        $entrada = $this->servicio()->registrar(
            ['proveedor' => 'Distribuidora', 'actualizar_precio_compra' => false],
            [
                ['producto_id' => $a->id, 'cantidad' => 10, 'costo_unitario' => '5.00'],
                ['producto_id' => $b->id, 'cantidad' => 4, 'costo_unitario' => '3.00'],
            ],
            $admin
        );

        $this->assertSame(10, $a->fresh()->stock);
        $this->assertSame(4, $b->fresh()->stock);
        $this->assertSame('62.00', $entrada->total);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $a->id, 'tipo' => 'ENTRADA', 'cantidad' => 10,
            'referencia_tipo' => 'entrada', 'referencia_id' => $entrada->id,
        ]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR', 'entidad_id' => $entrada->id]);
    }

    public function test_total_se_calcula_en_el_servidor(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $a = Producto::factory()->create();

        $respuesta = $this->actingAs($admin)->post('/entradas', [
            'proveedor' => 'X',
            'total' => '9999.99',
            'items' => [
                ['producto_id' => $a->id, 'cantidad' => 2, 'costo_unitario' => '5.00'],
            ],
        ]);

        $entrada = EntradaStock::latest('id')->first();
        $respuesta->assertRedirect(route('entradas.ver', $entrada));
        $this->assertSame('10.00', $entrada->total);
    }

    public function test_productos_repetidos_se_agrupan_en_una_linea(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $a = Producto::factory()->create(['precio_compra' => '1.00']);

        $entrada = $this->servicio()->registrar(
            ['actualizar_precio_compra' => false],
            [
                ['producto_id' => $a->id, 'cantidad' => 3, 'costo_unitario' => '5.00'],
                ['producto_id' => $a->id, 'cantidad' => 2, 'costo_unitario' => '6.00'],
            ],
            $admin
        );

        $this->assertCount(1, $entrada->detalles);
        $this->assertSame(5, $entrada->detalles->first()->cantidad);
        $this->assertSame('6.00', $entrada->detalles->first()->costo_unitario);
        $this->assertSame(5, $a->fresh()->stock);
    }

    public function test_actualiza_precio_compra_solo_si_la_opcion_esta_activa(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $a = Producto::factory()->create(['precio_compra' => '5.00']);
        $b = Producto::factory()->create(['precio_compra' => '5.00']);

        $this->servicio()->registrar(
            ['actualizar_precio_compra' => true],
            [['producto_id' => $a->id, 'cantidad' => 1, 'costo_unitario' => '7.00']],
            $admin
        );
        $this->assertSame('7.00', $a->fresh()->precio_compra);
        $this->assertDatabaseHas('auditoria', ['accion' => 'CAMBIO_PRECIO', 'entidad_id' => $a->id]);

        $this->servicio()->registrar(
            ['actualizar_precio_compra' => false],
            [['producto_id' => $b->id, 'cantidad' => 1, 'costo_unitario' => '7.00']],
            $admin
        );
        $this->assertSame('5.00', $b->fresh()->precio_compra);
    }

    public function test_item_invalido_no_guarda_nada(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $a = Producto::factory()->create();
        $inactivo = Producto::factory()->create(['activo' => false]);

        $respuesta = $this->actingAs($admin)->post('/entradas', [
            'items' => [
                ['producto_id' => $a->id, 'cantidad' => 2, 'costo_unitario' => '5.00'],
                ['producto_id' => $inactivo->id, 'cantidad' => 1, 'costo_unitario' => '5.00'],
            ],
        ]);

        $respuesta->assertSessionHasErrors('items');
        $this->assertSame(0, EntradaStock::count());
        $this->assertSame(0, $a->fresh()->stock);
        $this->assertDatabaseMissing('movimientos_stock', ['producto_id' => $a->id]);
    }

    public function test_anular_revierte_stock_y_no_se_puede_anular_dos_veces(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $a = Producto::factory()->create(['precio_compra' => '5.00']);

        $entrada = $this->servicio()->registrar(
            ['actualizar_precio_compra' => false],
            [['producto_id' => $a->id, 'cantidad' => 6, 'costo_unitario' => '5.00']],
            $admin
        );
        $this->assertSame(6, $a->fresh()->stock);

        $this->servicio()->anular($entrada, 'Pedido duplicado', $admin);

        $this->assertSame(0, $a->fresh()->stock);
        $this->assertSame('ANULADA', $entrada->fresh()->estado);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $a->id, 'tipo' => 'ANULACION_ENTRADA', 'cantidad' => -6,
        ]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'ANULAR', 'entidad_id' => $entrada->id]);

        try {
            $this->servicio()->anular($entrada->fresh(), 'Otra vez', $admin);
            $this->fail('Debió fallar al anular dos veces.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }
    }

    public function test_anular_desde_la_interfaz_requiere_motivo(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $a = Producto::factory()->create(['precio_compra' => '5.00']);
        $entrada = $this->servicio()->registrar(
            ['actualizar_precio_compra' => false],
            [['producto_id' => $a->id, 'cantidad' => 2, 'costo_unitario' => '5.00']],
            $admin
        );

        $this->actingAs($admin)->post("/entradas/{$entrada->id}/anular", ['motivo' => 'abc'])
            ->assertSessionHasErrors('motivo');
        $this->assertSame('REGISTRADA', $entrada->fresh()->estado);

        $this->actingAs($admin)->post("/entradas/{$entrada->id}/anular", ['motivo' => 'Error en el pedido'])
            ->assertRedirect(route('entradas.ver', $entrada));
        $this->assertSame('ANULADA', $entrada->fresh()->estado);
    }

    public function test_cajero_recibe_403(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        $this->actingAs($cajero)->get('/entradas')->assertForbidden();
        $this->actingAs($cajero)->get('/entradas/crear')->assertForbidden();
        $this->actingAs($cajero)->post('/entradas', ['items' => []])->assertForbidden();
    }
}
