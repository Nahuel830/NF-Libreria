<?php

namespace Tests\Browser;

use App\Models\Producto;
use App\Models\User;
use Database\Seeders\CategoriasSeeder;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Database\Seeders\UsuariosDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DiaCompletoTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => ConfiguracionSeeder::class]);
        $this->artisan('db:seed', ['--class' => UsuarioAdminSeeder::class]);
        $this->artisan('db:seed', ['--class' => CategoriasSeeder::class]);
        $this->artisan('db:seed', ['--class' => UsuariosDemoSeeder::class]);

        $cat = \App\Models\Categoria::firstOrFail();
        $datos = [
            ['DIA-A', 'Producto A', '10.00', 'SCAN-001'],
            ['DIA-B', 'Producto B', '5.00', 'SCAN-002'],
            ['DIA-C', 'Producto C', '2.50', 'SCAN-003'],
            ['DIA-D', 'Producto D', '20.00', 'SCAN-004'],
            ['DIA-E', 'Producto E', '7.00', 'SCAN-005'],
            ['DIA-FOT', 'Fotocopia', '0.30', 'SCAN-FOT'],
        ];

        foreach ($datos as [$codigo, $nombre, $precio, $barras]) {
            Producto::factory()->create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'categoria_id' => $cat->id,
                'precio_compra' => '1.00',
                'precio_venta' => $precio,
                'controla_stock' => $codigo !== 'DIA-FOT',
            ])->forceFill(['codigo_barras' => $barras])->save();
        }

        $prov = \App\Models\Proveedor::create(['nombre' => 'Proveedor Día']);

        $admin = User::where('usuario', 'admin')->firstOrFail();
        $admin->forceFill(['debe_cambiar_password' => false])->save();
    }

    public function test_dia_completo_en_la_libreria(): void
    {
        $stocksAntes = Producto::whereIn('codigo', ['DIA-A', 'DIA-B', 'DIA-C', 'DIA-D', 'DIA-E'])
            ->pluck('stock', 'codigo')->all();

        $this->browse(function (Browser $browser) {
            $admin = User::where('usuario', 'admin')->firstOrFail();

            // 1. Admin crea un cajero.
            $browser->loginAs($this->totpConfirmado($admin))->visit('/usuarios/crear')
                ->type('#nombre', 'Cajero Día')
                ->type('#usuario', 'cajerodia')
                ->select('#rol', 'cajero')
                ->type('#password', 'temporal123')
                ->type('#password_confirmation', 'temporal123')
                ->press('Guardar')
                ->waitForText('creado', 10);

            // 2. El cajero cambia su contraseña.
            $browser->loginAs(User::where('usuario', 'cajerodia')->firstOrFail())
                ->visit('/')
                ->assertPathIs('/cambiar-password')
                ->type('#actual', 'temporal123')
                ->type('#nueva', 'cajero12345')
                ->type('#nueva_confirmation', 'cajero12345')
                ->press('Guardar')
                ->waitForText('Bienvenido', 10);

            // 3. Abre caja con 50.
            $browser->visit('/caja/abrir')
                ->type('#monto_inicial', '50')
                ->press('Abrir caja')
                ->waitForText('Nueva venta', 10);

            // 4. Vende escaneando 5 productos, efectivo 50, cambio 5.50.
            foreach (['SCAN-001', 'SCAN-002', 'SCAN-003', 'SCAN-004', 'SCAN-005'] as $codigo) {
                $browser->type('#buscador', $codigo)
                    ->keys('#buscador', '{enter}');
            }

            $browser->waitForTextIn('#total', 'Bs. 44,50', 15)
                ->type('#recibido', '50')
                ->press('COBRAR (F9)')
                ->waitForText('VENTA #', 15)
                ->assertSee('Bs. 5,50');

            // 5. Vende 2 fotocopias con QR.
            $browser->visit('/ventas/nueva')
                ->type('#buscador', 'SCAN-FOT')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->type('#buscador', 'SCAN-FOT')
                ->keys('#buscador', '{enter}')
                ->waitForTextIn('#total', 'Bs. 0,60', 10)
                ->press('QR')
                ->press('COBRAR (F9)')
                ->waitForText('VENTA #', 15);

            $encargado = User::where('usuario', 'encargado')->firstOrFail();

            // 6. Encargado abre su caja y registra entrada del proveedor.
            $browser->loginAs($encargado)->visit('/caja/abrir')
                ->type('#monto_inicial', '0')
                ->press('Abrir caja')
                ->waitForText('Nueva venta', 10);

            $browser->visit('/entradas/crear')
                ->type('#proveedor_nombre', 'Proveedor Día')
                ->waitForText('Proveedor Día', 10)
                ->click('.list-group-item')
                ->type('#buscador', 'DIA-A')
                ->waitForText('Producto A', 10)
                ->click('#resultados .list-group-item')
                ->waitFor('#items tr', 10)
                ->type('.cantidad', '10')
                ->type('.costo', '5')
                ->press('Registrar entrada')
                ->waitFor('#modal-confirmar.show', 10)
                ->pause(500)
                ->click('.modal-footer .confirmar')
                ->waitForText('registrada', 15);

            // 7. Encargado vende con descuento 5.
            $browser->visit('/ventas/nueva')
                ->type('#buscador', 'DIA-B')
                ->keys('#buscador', '{enter}')
                ->waitFor('#carrito tr', 10)
                ->type('#descuento', '2')
                ->press('QR')
                ->press('COBRAR (F9)')
                ->waitForText('VENTA #', 15);

            $url = $browser->script('return window.location.href;');
            preg_match('#/ventas/(\d+)/ticket#', (string) $url[0], $m);
            $ventaDescuento = $m[1];

            // 8. Encargado anula esa venta con motivo.
            $browser->visit("/ventas/{$ventaDescuento}")
                ->press('Anular venta')
                ->waitFor('#modal-confirmar.show', 10)
                ->pause(500)
                ->type('#modal-confirmar-motivo', 'Se arrepintió el cliente')
                ->pause(500)
                ->click('.modal-footer .confirmar')
                ->waitForText('ANULADA', 15);

            // 9. Cliente devuelve 1 unidad de la primera venta.
            $primera = \App\Models\Venta::orderBy('id')->first()->id;
            $browser->visit("/ventas/{$primera}/devoluciones/crear")
                ->type('input[name="items[0][cantidad]"]', '1')
                ->type('#motivo', 'Producto con falla de prueba')
                ->select('#metodo_reembolso', 'EFECTIVO')
                ->press('Guardar devolución')
                ->waitForText('DEVOLUCIÓN', 15);

            $cajero = User::where('usuario', 'cajerodia')->firstOrFail();

            // 10. Cajero registra un egreso.
            $browser->loginAs($cajero)->visit('/caja')
                ->clickLink('Movimiento')
                ->select('#tipo', 'EGRESO')
                ->type('#monto', '5')
                ->type('#concepto', 'Pago autorizado de prueba')
                ->press('Guardar')
                ->waitForText('registrado', 10);

            // 11. Cajero cierra caja con arqueo (esperado 79.50).
            $cajaId = \App\Models\Caja::abiertaDe($cajero)->id;
            $browser->visit("/caja/{$cajaId}/cerrar")
                ->type('#conteo-50', '1')
                ->type('#conteo-20', '1')
                ->type('#conteo-5', '1')
                ->type('#conteo-2', '2')
                ->type('#conteo-0-50', '1')
                ->waitForText('Bs. 79,50', 10)
                ->assertSeeIn('#diferencia', 'Bs. 0,00')
                ->press('Cerrar caja')
                ->waitForText('Caja #', 15);

            // 12. Encargado revisa cierre y reportes (neto: 45.10 − 10.00 devolución).
            $browser->loginAs($encargado)->visit('/reportes/cierre')
                ->assertSee('Bs. 35,10');
            $browser->visit('/reportes/resumen')
                ->assertSee('Bs. 35,10');
        });

        // Verificación en BD: stock = antes + entradas − ventas + anulaciones + devoluciones.
        $esperados = [
            'DIA-A' => $stocksAntes['DIA-A'] + 10 - 1 + 1,
            'DIA-B' => $stocksAntes['DIA-B'] - 1 - 1 + 1,
            'DIA-C' => $stocksAntes['DIA-C'] - 1,
            'DIA-D' => $stocksAntes['DIA-D'] - 1,
            'DIA-E' => $stocksAntes['DIA-E'] - 1,
        ];

        foreach ($esperados as $codigo => $stock) {
            $this->assertSame($stock, Producto::where('codigo', $codigo)->first()->stock, "Stock de {$codigo}");
        }

        // Neto: 44.50 + 0.60 + 3.00 (anulada, excluida) − 10.00 devolución = 35.10.
        $this->assertSame(2, \App\Models\Venta::where('estado', 'COMPLETADA')->count());
        $this->assertSame(3, \App\Models\Venta::count());
        $this->assertDatabaseHas('auditoria', ['accion' => 'ANULAR']);
        $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR', 'entidad' => 'devoluciones']);

        $this->artisan('stock:verificar')->assertSuccessful();
    }
}
