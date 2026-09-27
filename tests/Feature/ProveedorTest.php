<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_y_encargado_gestionan_cajero_403(): void
    {
        foreach ([Rol::Admin, Rol::Encargado] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'usuario' => 'u-'.$rol->value]);

            $this->actingAs($usuario)->get('/proveedores')->assertOk();
            $this->actingAs($usuario)->post('/proveedores', ['nombre' => 'Prov '.$rol->value])
                ->assertRedirect(route('proveedores.index'));
            $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR']);
        }

        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $this->actingAs($cajero)->get('/proveedores')->assertForbidden();
        $this->actingAs($cajero)->post('/proveedores', ['nombre' => 'X'])->assertForbidden();
    }

    public function test_editar_y_desactivar_con_auditoria(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $proveedor = Proveedor::factory()->create(['nombre' => 'Viejo']);

        $this->actingAs($admin)->put("/proveedores/{$proveedor->id}", ['nombre' => 'Nuevo'])
            ->assertRedirect(route('proveedores.index'));
        $this->assertDatabaseHas('auditoria', ['accion' => 'EDITAR', 'entidad_id' => $proveedor->id]);

        $this->actingAs($admin)->patch("/proveedores/{$proveedor->id}/estado")
            ->assertRedirect(route('proveedores.index'));
        $this->assertFalse($proveedor->fresh()->activo);
        $this->assertDatabaseHas('auditoria', ['accion' => 'DESACTIVAR', 'entidad_id' => $proveedor->id]);
    }

    public function test_entrada_con_proveedor_vinculado(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $proveedor = Proveedor::factory()->create();
        $producto = Producto::factory()->create(['precio_compra' => '5.00']);

        $this->actingAs($admin)->post('/entradas', [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'costo_unitario' => '5.00']],
        ])->assertRedirect();

        $entrada = \App\Models\EntradaStock::latest('id')->first();
        $this->assertSame($proveedor->id, $entrada->proveedor_id);
        $this->assertSame($proveedor->nombre, $entrada->proveedorVinculado->nombre);
    }

    public function test_buscador_y_creacion_rapida(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        Proveedor::factory()->create(['nombre' => 'Distribuidora ABC']);

        $this->actingAs($admin)->getJson('/proveedores/buscar?q=distribuidora')
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'Distribuidora ABC']);

        $respuesta = $this->actingAs($admin)->postJson('/proveedores/rapido', ['nombre' => 'Nuevo Rápido']);
        $respuesta->assertOk()->assertJson(['nombre' => 'Nuevo Rápido']);
        $this->assertDatabaseHas('proveedores', ['nombre' => 'Nuevo Rápido']);
    }

    public function test_reporte_compras_por_proveedor(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $this->actingAs($admin);
        $proveedor = Proveedor::factory()->create(['nombre' => 'Compras SA']);
        $producto = Producto::factory()->create(['precio_compra' => '5.00']);

        app(\App\Services\EntradaService::class)->registrar(
            ['proveedor_id' => $proveedor->id, 'actualizar_precio_compra' => false],
            [['producto_id' => $producto->id, 'cantidad' => 4, 'costo_unitario' => '5.00']],
            $admin
        );

        $this->actingAs($admin)->get('/reportes/compras')
            ->assertOk()
            ->assertSee('Compras SA')
            ->assertSee('Bs. 20,00');
    }
}
