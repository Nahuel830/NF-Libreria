<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportacionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('importaciones');

        parent::tearDown();
    }

    protected function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Admin]);
    }

    protected function archivo(string $contenido): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('productos.csv', $contenido);
    }

    protected function tokenDesdeVistaPrevia(string $contenido, string $siExiste = 'omitir'): string
    {
        $respuesta = $this->actingAs($this->admin())->post('/productos/importar/vista-previa', [
            'archivo' => $this->archivo($contenido),
            'si_existe' => $siExiste,
            'crear_categorias' => '1',
        ]);

        $respuesta->assertOk();
        preg_match('/name="token" value="([^"]+)"/', $respuesta->getContent(), $m);

        $this->assertNotEmpty($m[1] ?? null);

        return $m[1];
    }

    public function test_importa_csv_con_punto_y_coma_y_plantilla_descargable(): void
    {
        $this->actingAs($this->admin())->get('/productos/importar/plantilla')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = "codigo;nombre;categoria;marca;unidad;precio_compra;precio_venta;stock_inicial;stock_minimo;controla_stock\n"
            ."IMP-001;Regla 30 cm;Accesorios;;unidad;1,50;3,00;10;2;si\n";

        $token = $this->tokenDesdeVistaPrevia($csv);

        $this->actingAs($this->admin())->post('/productos/importar/confirmar', ['token' => $token])
            ->assertOk()
            ->assertSee('Importación terminada');

        $producto = Producto::where('codigo', 'IMP-001')->first();
        $this->assertNotNull($producto);
        $this->assertSame('3.00', $producto->precio_venta);
        $this->assertSame(10, $producto->stock);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id, 'tipo' => 'INICIAL', 'cantidad' => 10,
        ]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR', 'entidad' => 'importacion']);
    }

    public function test_importa_csv_con_coma_y_precios_con_punto(): void
    {
        $csv = "codigo,nombre,categoria,marca,unidad,precio_compra,precio_venta,stock_inicial,stock_minimo,controla_stock\n"
            ."IMP-002,Borrador,Accesorios,,unidad,0.80,1.50,5,1,1\n";

        $token = $this->tokenDesdeVistaPrevia($csv);

        $this->actingAs($this->admin())->post('/productos/importar/confirmar', ['token' => $token])->assertOk();

        $this->assertNotNull(Producto::where('codigo', 'IMP-002')->first());
    }

    public function test_filas_con_error_se_reportan_y_se_omiten_al_confirmar(): void
    {
        $csv = "codigo;nombre;categoria;marca;unidad;precio_compra;precio_venta;stock_inicial;stock_minimo;controla_stock\n"
            ."IMP-010;Bueno;Accesorios;;unidad;1;2;0;0;si\n"
            ."IMP-011;;Accesorios;;unidad;1;2;0;0;si\n"
            ."IMP-010;Duplicado;Accesorios;;unidad;1;2;0;0;si\n";

        $respuesta = $this->actingAs($this->admin())->post('/productos/importar/vista-previa', [
            'archivo' => $this->archivo($csv),
            'si_existe' => 'omitir',
            'crear_categorias' => '1',
        ]);

        $respuesta->assertOk()
            ->assertSee('Falta el nombre.')
            ->assertSee('Código duplicado dentro del archivo.');

        preg_match('/name="token" value="([^"]+)"/', $respuesta->getContent(), $m);

        $this->actingAs($this->admin())->post('/productos/importar/confirmar', ['token' => $m[1]])->assertOk();

        $this->assertNotNull(Producto::where('codigo', 'IMP-010')->first());
        $this->assertNull(Producto::where('codigo', 'IMP-011')->first());
        $this->assertSame(1, Producto::where('codigo', 'IMP-010')->count());
    }

    public function test_codigo_existente_omitir_no_cambia_y_actualizar_cambia_sin_tocar_stock(): void
    {
        $admin = $this->admin();
        $existente = Producto::factory()->create([
            'codigo' => 'IMP-020', 'nombre' => 'Viejo', 'precio_venta' => '10.00',
        ]);
        \Illuminate\Support\Facades\DB::table('productos')->where('id', $existente->id)->update(['stock' => 7]);

        $csv = "codigo;nombre;categoria;marca;unidad;precio_compra;precio_venta;stock_inicial;stock_minimo;controla_stock\n"
            ."IMP-020;Nuevo;Accesorios;Marca;unidad;5;20;99;1;si\n";

        $token = $this->tokenDesdeVistaPrevia($csv, 'omitir');
        $this->actingAs($admin)->post('/productos/importar/confirmar', ['token' => $token])->assertOk();

        $existente->refresh();
        $this->assertSame('Viejo', $existente->nombre);
        $this->assertSame(7, $existente->stock);

        $token = $this->tokenDesdeVistaPrevia($csv, 'actualizar');
        $this->actingAs($admin)->post('/productos/importar/confirmar', ['token' => $token])->assertOk();

        $existente->refresh();
        $this->assertSame('Nuevo', $existente->nombre);
        $this->assertSame('Marca', $existente->marca);
        $this->assertSame(7, $existente->stock);
    }

    public function test_cajero_recibe_403(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        $this->actingAs($cajero)->get('/productos/importar')->assertForbidden();
        $this->actingAs($cajero)->get('/productos/importar/plantilla')->assertForbidden();
        $this->actingAs($cajero)->post('/productos/importar/vista-previa', [])->assertForbidden();
    }

    public function test_archivo_que_no_es_csv_se_rechaza(): void
    {
        $this->actingAs($this->admin())->post('/productos/importar/vista-previa', [
            'archivo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('foto.png', "\x89PNGcontenido"),
            'si_existe' => 'omitir',
        ])->assertSessionHasErrors('archivo');
    }

    public function test_csv_no_utf8_convierte_tildes(): void
    {
        $contenido = mb_convert_encoding(
            "codigo;nombre;categoria;marca;unidad;precio_compra;precio_venta;stock_inicial;stock_minimo;controla_stock\n"
            ."TIL-001;Lápiz ótimo;Accesorios;;unidad;1;2;0;0;si\n",
            'Windows-1252', 'UTF-8'
        );

        $token = $this->tokenDesdeVistaPrevia($contenido);

        $this->actingAs($this->admin())->post('/productos/importar/confirmar', ['token' => $token])->assertOk();

        $this->assertNotNull(Producto::where('codigo', 'TIL-001')->first());
        $this->assertSame('Lápiz ótimo', Producto::where('codigo', 'TIL-001')->first()->nombre);
    }
}
