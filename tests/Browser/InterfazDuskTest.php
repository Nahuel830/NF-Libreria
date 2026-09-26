<?php

namespace Tests\Browser;

use App\Models\Producto;
use App\Models\User;
use Database\Seeders\CategoriasSeeder;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\ProductosDemoSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Database\Seeders\UsuariosDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InterfazDuskTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => ConfiguracionSeeder::class]);
        $this->artisan('db:seed', ['--class' => UsuarioAdminSeeder::class]);
        $this->artisan('db:seed', ['--class' => CategoriasSeeder::class]);
        $this->artisan('db:seed', ['--class' => UsuariosDemoSeeder::class]);
        $this->artisan('db:seed', ['--class' => ProductosDemoSeeder::class]);
    }

    public function test_anular_venta_con_modal_y_motivo(): void
    {
        $encargado = User::where('usuario', 'encargado')->firstOrFail();
        $producto = Producto::where('codigo', 'LAP-001')->firstOrFail();

        $this->browse(function (Browser $browser) use ($encargado, $producto) {
            $browser->loginAs($encargado)->visit('/ventas/nueva')
                ->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->type('#recibido', '10')
                ->press('COBRAR (F9)')
                ->waitForText('VENTA #', 15);

            $url = $browser->script('return window.location.href;');
            preg_match('#/ventas/(\d+)/ticket#', (string) $url[0], $m);
            $id = $m[1];

            $browser->visit("/ventas/{$id}")
                ->assertSee('Reimprimir ticket')
                ->press('Anular venta')
                ->waitFor('#modal-confirmar.show', 10)
                ->type('#modal-confirmar-motivo', 'Me equivoqué de producto')
                ->pause(500)
                ->press('Confirmar anulación')
                ->waitForText('ANULADA', 15)
                ->assertSee('Me equivoqué de producto');
        });

        $this->assertSame('ANULADA', \App\Models\Venta::latest('id')->first()->estado);
    }

    public function test_sugerir_codigo_llena_el_campo(): void
    {
        $encargado = User::where('usuario', 'encargado')->firstOrFail();

        $this->browse(function (Browser $browser) use ($encargado) {
            $browser->loginAs($encargado)->visit('/productos/crear')
                ->press('Sugerir código')
                ->waitUntil("return document.getElementById('codigo').value !== '';", 10);

            $codigo = $browser->script("return document.getElementById('codigo').value;");
            $this->assertMatchesRegularExpression('/^[A-Z]+-\d+$/', (string) $codigo[0]);
        });
    }

    public function test_advertencia_precio_pide_confirmar(): void
    {
        $encargado = User::where('usuario', 'encargado')->firstOrFail();

        $this->browse(function (Browser $browser) use ($encargado) {
            $browser->loginAs($encargado)->visit('/productos/crear')
                ->type('#codigo', 'ADV-001')
                ->type('#nombre', 'Producto con advertencia')
                ->type('#precio_compra', '10')
                ->type('#precio_venta', '8')
                ->press('Guardar')
                ->waitForText('menor al precio de compra', 10)
                ->press('Guardar')
                ->waitForText('creado', 10);

            $this->assertDatabaseHas('productos', ['codigo' => 'ADV-001']);
        });
    }

    public function test_entradas_dinamicas_y_total_en_vivo(): void
    {
        $encargado = User::where('usuario', 'encargado')->firstOrFail();

        $this->browse(function (Browser $browser) use ($encargado) {
            $browser->loginAs($encargado)->visit('/entradas/crear')
                ->type('#buscador', 'LAP-001')
                ->waitForText('Lapicero azul', 10)
                ->click('.list-group-item')
                ->waitFor('#items tr', 10)
                ->assertSee('Bs. 1,50');
        });
    }

    public function test_importacion_con_vista_previa(): void
    {
        $encargado = User::where('usuario', 'encargado')->firstOrFail();
        $ruta = storage_path('app/prueba-dusk.csv');
        file_put_contents($ruta, "codigo;nombre;categoria;marca;unidad;precio_compra;precio_venta;stock_inicial;stock_minimo;controla_stock\nDUSK-001;Prod Dusk;Accesorios;;unidad;1;2;5;1;si\n");

        try {
            $this->browse(function (Browser $browser) use ($encargado, $ruta) {
                $browser->loginAs($encargado)->visit('/productos/importar')
                    ->attach('#archivo', $ruta)
                    ->press('Ver vista previa')
                    ->waitForText('Nuevo', 15)
                    ->press('Confirmar importación')
                    ->waitForText('Importación terminada', 15);
            });

            $this->assertDatabaseHas('productos', ['codigo' => 'DUSK-001']);
        } finally {
            @unlink($ruta);
        }
    }

    public function test_ticket_foco_en_nueva_venta(): void
    {
        $cajero = User::where('usuario', 'cajero1')->firstOrFail();

        $this->browse(function (Browser $browser) use ($cajero) {
            $browser->loginAs($cajero)->visit('/ventas/nueva')
                ->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->type('#recibido', '10')
                ->press('COBRAR (F9)')
                ->waitForText('VENTA #', 15);

            $foco = $browser->script('return document.activeElement.id;');
            $this->assertSame('btn-nueva', (string) $foco[0]);
        });
    }

    public function test_toast_visible_tras_accion(): void
    {
        $encargado = User::where('usuario', 'encargado')->firstOrFail();

        $this->browse(function (Browser $browser) use ($encargado) {
            $browser->loginAs($encargado)->visit('/categorias/crear')
                ->type('#nombre', 'Categoria Toast')
                ->press('Guardar')
                ->waitFor('.toasts-nf .toast.show', 10)
                ->assertSee('creada');
        });
    }

    public function test_menu_segun_rol(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('usuario', 'admin')->firstOrFail();
            $admin->forceFill(['debe_cambiar_password' => false])->save();
            $browser->loginAs($admin)->visit('/')
                ->assertSee('Administración')
                ->assertSee('Reportes');

            $encargado = User::where('usuario', 'encargado')->firstOrFail();
            $browser->loginAs($encargado)->visit('/')
                ->assertSee('Inventario')
                ->assertDontSee('Administración');

            $cajero = User::where('usuario', 'cajero1')->firstOrFail();
            $browser->loginAs($cajero)->visit('/')
                ->assertSee('Nueva venta')
                ->assertDontSee('Administración')
                ->assertDontSee('Reportes');
        });
    }

    public function test_caja_foco_quitar_y_f2(): void
    {
        $this->browse(function (Browser $browser) {
            $cajero = User::where('usuario', 'cajero1')->firstOrFail();
            $browser->loginAs($cajero)->visit('/ventas/nueva');

            $foco = $browser->script('return document.activeElement.id;');
            $this->assertSame('buscador', (string) $foco[0]);

            $browser->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->press('Quitar')
                ->waitForTextIn('#total', 'Bs. 0,00', 10)
                ->keys('#recibido', '{f2}');

            $foco = $browser->script('return document.activeElement.id;');
            $this->assertSame('buscador', (string) $foco[0]);
        });
    }
}
