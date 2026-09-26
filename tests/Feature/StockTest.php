<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Exceptions\StockInsuficienteException;
use App\Models\Producto;
use App\Models\User;
use App\Services\ConfiguracionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    protected function servicio(): StockService
    {
        return app(StockService::class);
    }

    protected function actuarComoAdmin(): User
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);

        return $admin;
    }

    protected function permitirNegativo(string $valor): void
    {
        app(ConfiguracionService::class)->set('permitir_stock_negativo', $valor);
    }

    public function test_mover_fuera_de_transaccion_lanza_excepcion(): void
    {
        // RefreshDatabase envuelve cada test en una transacción: se sale
        // de ella para probar el caso sin transacción y luego se restaura.
        DB::rollBack();

        try {
            $this->servicio()->mover(1, 5, 'ENTRADA');
            $this->fail('Debió lanzar LogicException.');
        } catch (LogicException) {
            $this->assertTrue(true);
        } finally {
            DB::beginTransaction();
        }
    }

    public function test_mover_con_cantidad_cero_lanza_excepcion(): void
    {
        $this->actuarComoAdmin();
        $producto = Producto::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        DB::transaction(fn () => $this->servicio()->mover($producto->id, 0, 'ENTRADA'));
    }

    public function test_entrada_suma_stock_y_crea_movimiento(): void
    {
        $admin = $this->actuarComoAdmin();
        $producto = Producto::factory()->create();
        DB::table('productos')->where('id', $producto->id)->update(['stock' => 5]);

        DB::transaction(fn () => $this->servicio()->mover($producto->id, 10, 'ENTRADA', 'Compra'));

        $this->assertSame(15, $producto->fresh()->stock);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'ENTRADA',
            'cantidad' => 10,
            'stock_anterior' => 5,
            'stock_nuevo' => 15,
            'user_id' => $admin->id,
        ]);
    }

    public function test_salida_con_negativo_permitido(): void
    {
        $this->actuarComoAdmin();
        $this->permitirNegativo('1');
        $producto = Producto::factory()->create();

        DB::transaction(fn () => $this->servicio()->mover($producto->id, -3, 'VENTA'));

        $this->assertSame(-3, $producto->fresh()->stock);
    }

    public function test_salida_con_negativo_bloqueado_lanza_excepcion_y_no_cambia_nada(): void
    {
        $this->actuarComoAdmin();
        $this->permitirNegativo('0');
        $producto = Producto::factory()->create();

        try {
            DB::transaction(fn () => $this->servicio()->mover($producto->id, -3, 'VENTA'));
            $this->fail('Debió lanzar StockInsuficienteException.');
        } catch (StockInsuficienteException $e) {
            $this->assertStringContainsString($producto->nombre, $e->getMessage());
        }

        $this->assertSame(0, $producto->fresh()->stock);
        $this->assertDatabaseMissing('movimientos_stock', ['producto_id' => $producto->id]);
    }

    public function test_producto_sin_control_de_stock_no_mueve_ni_crea_movimiento(): void
    {
        $this->actuarComoAdmin();
        $producto = Producto::factory()->create(['controla_stock' => false]);

        $resultado = DB::transaction(fn () => $this->servicio()->mover($producto->id, 5, 'ENTRADA'));

        $this->assertNull($resultado);
        $this->assertSame(0, $producto->fresh()->stock);
        $this->assertDatabaseMissing('movimientos_stock', ['producto_id' => $producto->id]);
    }

    public function test_ajustar_crea_movimiento_negativo_y_audita(): void
    {
        $admin = $this->actuarComoAdmin();
        $producto = Producto::factory()->create();
        DB::table('productos')->where('id', $producto->id)->update(['stock' => 20]);

        $movimiento = $this->servicio()->ajustar($producto, 12, 'Conteo físico');

        $this->assertNotNull($movimiento);
        $this->assertSame('AJUSTE_NEGATIVO', $movimiento->tipo);
        $this->assertSame(-8, $movimiento->cantidad);
        $this->assertSame(12, $producto->fresh()->stock);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'AJUSTE_STOCK',
            'user_id' => $admin->id,
            'entidad_id' => $producto->id,
        ]);
    }

    public function test_ajustar_con_la_misma_cantidad_no_crea_movimiento(): void
    {
        $this->actuarComoAdmin();
        $producto = Producto::factory()->create();
        DB::table('productos')->where('id', $producto->id)->update(['stock' => 12]);

        $this->assertNull($this->servicio()->ajustar($producto, 12, 'Conteo físico'));
        $this->assertDatabaseMissing('movimientos_stock', ['producto_id' => $producto->id]);
    }

    public function test_crear_producto_con_stock_inicial(): void
    {
        $admin = $this->actuarComoAdmin();
        $categoria = \App\Models\Categoria::factory()->create();

        $this->actingAs($admin)->post('/productos', [
            'codigo' => 'STK-001',
            'nombre' => 'Producto con stock',
            'categoria_id' => $categoria->id,
            'unidad' => 'unidad',
            'precio_compra' => '5.00',
            'precio_venta' => '8.00',
            'stock_minimo' => 0,
            'controla_stock' => '1',
            'stock_inicial' => 25,
        ])->assertRedirect(route('productos.index'));

        $producto = Producto::where('codigo', 'STK-001')->first();
        $this->assertSame(25, $producto->stock);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'INICIAL',
            'cantidad' => 25,
            'stock_anterior' => 0,
            'stock_nuevo' => 25,
        ]);
    }

    public function test_cajero_recibe_403_al_ajustar_stock(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $producto = Producto::factory()->create();

        $this->actingAs($cajero)->get("/productos/{$producto->id}/ajustar")->assertForbidden();
        $this->actingAs($cajero)->put("/productos/{$producto->id}/ajustar", [
            'stock_real' => 10,
            'motivo' => 'Conteo físico',
        ])->assertForbidden();
    }

    public function test_verificar_detecta_diferencia_si_se_modifica_stock_directo(): void
    {
        $this->actuarComoAdmin();
        $producto = Producto::factory()->create();
        DB::transaction(fn () => $this->servicio()->mover($producto->id, 10, 'ENTRADA'));

        $this->artisan('stock:verificar')->assertSuccessful();

        DB::table('productos')->where('id', $producto->id)->update(['stock' => 999]);

        $this->artisan('stock:verificar')->assertFailed();
        $this->assertCount(1, $this->servicio()->verificarConsistencia());
    }

    public function test_editar_producto_no_modifica_stock(): void
    {
        $admin = $this->actuarComoAdmin();
        $producto = Producto::factory()->create();
        DB::transaction(fn () => $this->servicio()->mover($producto->id, 7, 'ENTRADA'));

        $this->actingAs($admin)->put("/productos/{$producto->id}", [
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'categoria_id' => $producto->categoria_id,
            'unidad' => 'unidad',
            'precio_compra' => '5.00',
            'precio_venta' => '8.00',
            'stock_minimo' => 0,
            'controla_stock' => '1',
            'stock' => 9999,
        ])->assertRedirect(route('productos.index'));

        $this->assertSame(7, $producto->fresh()->stock);
    }
}
