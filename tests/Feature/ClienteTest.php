<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_permisos_y_auditoria(): void
    {
        foreach ([Rol::Admin, Rol::Encargado] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'usuario' => 'u-'.$rol->value]);
            $this->actingAs($usuario)->get('/clientes')->assertOk();
            $this->actingAs($usuario)->post('/clientes', ['nombre' => 'Cli '.$rol->value])
                ->assertRedirect(route('clientes.index'));
        }

        $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR', 'entidad' => 'clientes']);

        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero)->get('/clientes')->assertForbidden();
        $this->actingAs($cajero)->post('/clientes', ['nombre' => 'X'])->assertForbidden();
    }

    public function test_venta_con_y_sin_cliente(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $cliente = Cliente::factory()->create(['nombre' => 'Juan Perez']);

        $sin = app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero
        );
        $this->assertNull($sin->cliente_id);
        $this->assertNull($sin->cliente_nombre);

        $con = app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR', 'cliente_id' => $cliente->id],
            $cajero
        );
        $this->assertSame($cliente->id, $con->cliente_id);
        $this->assertSame('Juan Perez', $con->cliente_nombre);

        $manual = app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR', 'cliente_nombre' => 'Sin registro'],
            $cajero
        );
        $this->assertNull($manual->cliente_id);
        $this->assertSame('Sin registro', $manual->cliente_nombre);
    }

    public function test_venta_con_cliente_invalido_falla(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $inactivo = Cliente::factory()->create(['activo' => false]);

        try {
            app(VentaService::class)->registrar(
                [['producto_id' => $producto->id, 'cantidad' => 1]],
                ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR', 'cliente_id' => 999999],
                $cajero
            );
            $this->fail('Debió fallar con cliente inexistente.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $this->actingAs($cajero)->post('/ventas', [
            'token' => (string) Str::uuid(),
            'metodo_pago' => 'QR',
            'cliente_id' => $inactivo->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('cliente_id');

        $this->assertSame(0, Venta::count());
    }

    public function test_cajero_puede_crear_cliente_rapido_y_buscar(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        $respuesta = $this->actingAs($cajero)->postJson('/clientes/rapido', ['nombre' => 'Rápido Uno']);
        $respuesta->assertOk();
        $this->assertDatabaseHas('clientes', ['nombre' => 'Rápido Uno']);

        $this->actingAs($cajero)->getJson('/clientes/buscar?q=rápido')
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'Rápido Uno']);
    }

    public function test_detalle_muestra_historial_y_reporte_mejores(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $this->actingAs($encargado);
        $producto = Producto::factory()->create(['precio_venta' => '10.00']);
        $cliente = Cliente::factory()->create(['nombre' => 'Fiel Comprador']);

        app(VentaService::class)->registrar(
            [['producto_id' => $producto->id, 'cantidad' => 2]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR', 'cliente_id' => $cliente->id],
            $encargado
        );

        $this->actingAs($encargado)->get("/clientes/{$cliente->id}")
            ->assertOk()
            ->assertSee('Bs. 20,00');

        $this->actingAs($encargado)->get('/reportes/clientes')
            ->assertOk()
            ->assertSee('Fiel Comprador')
            ->assertSee('Bs. 20,00');
    }
}
