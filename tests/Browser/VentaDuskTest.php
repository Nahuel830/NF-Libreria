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

class VentaDuskTest extends DuskTestCase
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

        foreach (['cajero1', 'encargado'] as $usuario) {
            $u = User::where('usuario', $usuario)->firstOrFail();

            if (! \App\Models\Caja::abiertaDe($u)) {
                app(\App\Services\CajaService::class)->abrir($u, '0.00');
            }
        }
    }

    protected function cajero(): User
    {
        return User::where('usuario', 'cajero1')->firstOrFail();
    }

    protected function abrirCaja(Browser $browser): void
    {
        $browser->loginAs($this->cajero())->visit('/ventas/nueva')->assertSee('Nueva venta');
    }

    public function test_buscar_navegar_con_flechas_y_agregar_con_enter(): void
    {
        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'lap')
                ->waitForText('LAP-001', 10)
                ->keys('#buscador', '{arrow_down}', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->assertSeeIn('#total', 'Bs. 2,50');
        });
    }

    public function test_codigo_exacto_agrega_directo(): void
    {
        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->assertSeeIn('#total', 'Bs. 2,50');
        });
    }

    public function test_repetido_suma_y_botones_mas_menos(): void
    {
        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->assertSeeIn('#total', 'Bs. 2,50')
                ->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitForTextIn('#total', 'Bs. 5,00', 10)
                ->press('+')
                ->waitForTextIn('#total', 'Bs. 7,50', 10)
                ->press('−')
                ->waitForTextIn('#total', 'Bs. 5,00', 10);
        });
    }

    public function test_f2_enfoca_y_escape_vacia_con_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->keys('#buscador', '{f2}')
                ->click('#btn-cancelar')
                ->waitFor('#modal-confirmar.show', 10)
                ->assertSee('¿Cancelar la venta y vaciar el carrito?')
                ->pause(500)
                ->press('Vaciar carrito')
                ->waitForTextIn('#total', 'Bs. 0,00', 10);
        });
    }

    public function test_efectivo_cambio_billetes_y_exacto(): void
    {
        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->type('#recibido', '10')
                ->assertSeeIn('#cambio', 'Bs. 7,50')
                ->press('Exacto')
                ->assertSeeIn('#cambio', 'Bs. 0,00');
        });
    }

    public function test_cobrar_flujo_completo_y_ticket(): void
    {
        $producto = Producto::where('codigo', 'LAP-001')->firstOrFail();
        $stockAntes = $producto->stock;

        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->type('#recibido', '10')
                ->press('COBRAR (F9)')
                ->waitForText('VENTA #', 15)
                ->assertSee('Documento sin valor fiscal')
                ->assertSee('Bs. 7,50');
        });

        $this->assertSame($stockAntes - 1, $producto->fresh()->stock);
        $this->assertDatabaseHas('ventas', ['user_id' => $this->cajero()->id]);
    }

    public function test_error_conserva_carrito_y_reactiva_boton(): void
    {
        \App\Models\Configuracion::updateOrCreate(['clave' => 'permitir_stock_negativo'], ['valor' => '0']);

        $producto = Producto::where('codigo', 'LAP-001')->firstOrFail();
        $producto->forceFill(['stock' => 1])->save();

        $this->browse(function (Browser $browser) use ($producto) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'LAP-001')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->press('+')
                ->waitForTextIn('#total', 'Bs. 5,00', 10)
                ->type('#recibido', '10')
                ->press('COBRAR (F9)')
                ->waitFor('#mensaje-venta:not(.d-none)', 15)
                ->assertSeeIn('#carrito', 'LAP-001');

            $deshabilitado = $browser->script("return document.getElementById('btn-cobrar').disabled;");
            $this->assertFalse((bool) $deshabilitado[0]);
        });
    }

    public function test_cajero_no_ve_descuento(): void
    {
        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->assertMissing('#descuento');
        });
    }

    public function test_menu_muestra_usuario_y_titulo_con_negocio(): void
    {
        $this->browse(function (Browser $browser) {
            $cajero = $this->cajero();
            $browser->loginAs($cajero)->visit('/')
                ->assertSee($cajero->nombre)
                ->assertSee('Cajero');

            $titulo = $browser->script('return document.title;');
            $this->assertStringContainsString('NF Librería', (string) $titulo[0]);

            $favicon = $browser->script("return document.querySelector('link[rel=icon]') !== null;");
            $this->assertTrue((bool) $favicon[0]);
        });
    }

    public function test_menu_colapsable_en_pantalla_chica(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->cajero())->visit('/')->resize(390, 844)
                ->assertVisible('.navbar-toggler')
                ->click('.navbar-toggler')
                ->waitFor('#menuPrincipal.show', 10)
                ->assertSee('Nueva venta');
        });
    }

    public function test_venta_sin_scroll_horizontal_en_1366(): void
    {
        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->resize(1366, 768);

            $ancho = $browser->script('return document.documentElement.scrollWidth <= window.innerWidth;');
            $this->assertTrue((bool) $ancho[0]);
        });
    }

    public function test_escaneo_consecutivo_por_codigo_de_barras(): void
    {
        $producto = Producto::where('codigo', 'LAP-001')->firstOrFail();
        $producto->forceFill(['codigo_barras' => '7501000100018'])->save();
        $stockAntes = $producto->stock;

        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            // Simula el lector: escribe rápido y termina con Enter, 3 veces.
            for ($i = 0; $i < 3; $i++) {
                $browser->type('#buscador', '7501000100018')
                    ->keys('#buscador', '{enter}');
            }

            $browser->waitForTextIn('#total', 'Bs. 7,50', 10)
                ->type('#recibido', '10')
                ->press('COBRAR (F9)')
                ->waitForText('VENTA #', 15);
        });

        $this->assertSame($stockAntes - 3, $producto->fresh()->stock);
        $venta = \App\Models\Venta::where('user_id', $this->cajero()->id)->latest('id')->first();
        $this->assertSame(3, $venta->detalles->first()->cantidad);
    }

    public function test_nombre_con_html_no_crea_elementos(): void
    {
        Producto::factory()->create(['codigo' => 'XSS-999', 'nombre' => '<b>Malicioso</b>']);

        $this->browse(function (Browser $browser) {
            $this->abrirCaja($browser);

            $browser->type('#buscador', 'XSS-999')
                ->waitForText('XSS-999', 10);

            $hayB = $browser->script("return document.querySelectorAll('#resultados b').length;");
            $this->assertSame(0, (int) $hayB[0]);
            $browser->assertSee('Malicioso');
        });
    }
}
