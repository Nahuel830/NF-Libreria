<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    protected ?Categoria $categoriaCache = null;

    protected function categoria(): Categoria
    {
        if (! $this->categoriaCache) {
            $this->categoriaCache = Categoria::factory()->create(['nombre' => 'Cuadernos']);
        }

        return $this->categoriaCache;
    }

    /**
     * @return array<string, mixed>
     */
    protected function datosValidos(?Categoria $categoria = null): array
    {
        return [
            'codigo' => 'CUA-001',
            'nombre' => 'Cuaderno prueba',
            'descripcion' => null,
            'categoria_id' => ($categoria ?? $this->categoria())->id,
            'marca' => 'Loro',
            'unidad' => 'unidad',
            'precio_compra' => '8.00',
            'precio_venta' => '12.50',
            'stock_minimo' => 5,
            'controla_stock' => '1',
        ];
    }

    public function test_admin_y_encargado_pueden_crear_y_editar(): void
    {
        foreach ([Rol::Admin, Rol::Encargado] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'usuario' => 'u-'.$rol->value]);
            $codigo = 'CUA-'.($rol === Rol::Admin ? '101' : '102');

            $this->actingAs($usuario)->post('/productos', array_merge($this->datosValidos(), ['codigo' => $codigo]))
                ->assertRedirect(route('productos.index'));

            $producto = Producto::where('codigo', $codigo)->first();
            $this->assertNotNull($producto);
            $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR', 'entidad_id' => $producto->id]);

            $this->actingAs($usuario)->put("/productos/{$producto->id}", array_merge(
                $this->datosValidos($producto->categoria),
                ['codigo' => $codigo, 'nombre' => 'Cuaderno editado', 'precio_venta' => '13.00']
            ))->assertRedirect(route('productos.index'));

            $this->assertSame('Cuaderno editado', $producto->fresh()->nombre);
        }
    }

    public function test_cajero_puede_ver_listado_pero_no_crear_ni_editar(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $producto = Producto::factory()->create();

        $this->actingAs($cajero)->get('/productos')->assertOk();
        $this->actingAs($cajero)->post('/productos', $this->datosValidos())->assertForbidden();
        $this->actingAs($cajero)->put("/productos/{$producto->id}", $this->datosValidos())->assertForbidden();
    }

    public function test_cajero_no_ve_precio_compra(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $producto = Producto::factory()->create(['precio_compra' => '7.25']);

        $this->actingAs($cajero)->get('/productos')->assertOk()->assertDontSee('7,25');
        $this->actingAs($cajero)->get("/productos/{$producto->id}")->assertOk()->assertDontSee('7,25');
    }

    public function test_codigo_duplicado_se_rechaza_y_se_guarda_en_mayusculas(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        Producto::factory()->create(['codigo' => 'CUA-001']);

        $this->actingAs($admin)->post('/productos', array_merge($this->datosValidos(), ['codigo' => 'cua-001']))
            ->assertSessionHasErrors('codigo');

        $this->actingAs($admin)->post('/productos', array_merge($this->datosValidos(), ['codigo' => '  cua-002  ']))
            ->assertRedirect(route('productos.index'));

        $this->assertNotNull(Producto::where('codigo', 'CUA-002')->first());
    }

    public function test_cambio_de_precio_queda_auditado(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $producto = Producto::factory()->create(['precio_compra' => '8.00', 'precio_venta' => '12.00']);

        $this->actingAs($admin)->put("/productos/{$producto->id}", array_merge(
            $this->datosValidos($producto->categoria),
            ['codigo' => $producto->codigo, 'precio_compra' => '9.00', 'precio_venta' => '14.00']
        ))->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('auditoria', [
            'accion' => 'CAMBIO_PRECIO',
            'entidad_id' => $producto->id,
        ]);

        $registro = \App\Models\Auditoria::where('accion', 'CAMBIO_PRECIO')->latest('id')->first();
        $this->assertSame('9.00', $registro->datos_nuevos['precio_compra']);
        $this->assertSame('14.00', $registro->datos_nuevos['precio_venta']);
    }

    public function test_edicion_no_modifica_stock_aunque_se_envie_manipulado(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $producto = Producto::factory()->create();

        $this->actingAs($admin)->put("/productos/{$producto->id}", array_merge(
            $this->datosValidos($producto->categoria),
            ['codigo' => $producto->codigo, 'stock' => 9999]
        ))->assertRedirect(route('productos.index'));

        $this->assertSame(0, $producto->fresh()->stock);
    }

    public function test_busqueda_encuentra_por_parte_del_nombre_sin_importar_mayusculas(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        Producto::factory()->create(['nombre' => 'Cuaderno Universitario']);

        $this->actingAs($cajero)->get('/productos?q=cuad')
            ->assertOk()
            ->assertSee('Cuaderno Universitario');
    }

    public function test_precio_se_guarda_con_2_decimales_exactos(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);

        $this->actingAs($admin)->post('/productos', array_merge($this->datosValidos(), ['precio_venta' => '12.5']))
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', ['codigo' => 'CUA-001', 'precio_venta' => '12.50']);
        $this->assertSame('12.50', Producto::where('codigo', 'CUA-001')->first()->precio_venta);
    }

    public function test_sugerir_codigo_propone_el_siguiente_libre(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $categoria = $this->categoria();
        Producto::factory()->create(['codigo' => 'CUA-001', 'categoria_id' => $categoria->id]);
        Producto::factory()->create(['codigo' => 'CUA-002', 'categoria_id' => $categoria->id]);

        $respuesta = $this->actingAs($admin)->getJson("/productos/sugerir-codigo?categoria_id={$categoria->id}");

        $respuesta->assertOk()->assertJson(['codigo' => 'CUA-003']);
    }

    public function test_nombre_con_html_se_muestra_como_texto_literal(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        Producto::factory()->create(['nombre' => '<b>Prueba</b>', 'codigo' => 'XSS-001']);

        $respuesta = $this->actingAs($cajero)->get('/productos?q=xss');

        $respuesta->assertOk()->assertSee('&lt;b&gt;Prueba&lt;/b&gt;', false);
    }

    public function test_crear_servicio_sin_control_de_stock(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);

        $this->actingAs($admin)->post('/productos', array_merge($this->datosValidos(), [
            'codigo' => 'SRV-001',
            'nombre' => 'Servicio de prueba',
        ], ['controla_stock' => '0']))
            ->assertRedirect(route('productos.index'));

        $producto = Producto::where('codigo', 'SRV-001')->first();
        $this->assertFalse($producto->controla_stock);
    }

    public function test_buscadores_no_muestran_productos_inactivos(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        Producto::factory()->create(['codigo' => 'INA-001', 'nombre' => 'Inactivo Unico', 'activo' => false]);

        // El listado sí los muestra (con badge INACTIVO); los buscadores no.
        $this->actingAs($admin)->get('/productos?q=Inactivo+Unico')
            ->assertOk()
            ->assertSee('INACTIVO');

        $this->actingAs($admin)->getJson('/api-interna/productos/buscar?q=Inactivo+Unico')
            ->assertOk()
            ->assertJsonMissing(['codigo' => 'INA-001']);
    }
}
