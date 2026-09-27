<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\CategoriasSeeder;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\ProductosDemoSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Database\Seeders\UsuariosDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CspTest extends DuskTestCase
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

        $admin = User::where('usuario', 'admin')->firstOrFail();

        if (! \App\Models\Caja::abiertaDe($admin)) {
            app(\App\Services\CajaService::class)->abrir($admin, '0.00');
        }
    }

    /**
     * @return string[]
     */
    protected function violacionesCsp(Browser $browser): array
    {
        $violaciones = [];

        foreach ($browser->driver->manage()->getLog('browser') as $entrada) {
            $mensaje = (string) ($entrada['message'] ?? '');

            if (stripos($mensaje, 'Content Security Policy') !== false) {
                $violaciones[] = $mensaje;
            }
        }

        return $violaciones;
    }

    public function test_consola_sin_violaciones_csp(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = $this->totpConfirmado(User::where('usuario', 'admin')->firstOrFail());
            $admin->forceFill(['debe_cambiar_password' => false])->save();

            $browser->loginAs($admin)->visit('/')
                ->assertSee('Panel de inicio');

            $this->assertSame([], $this->violacionesCsp($browser));

            $browser->visit('/ventas/nueva')
                ->assertSee('Nueva venta');

            $this->assertSame([], $this->violacionesCsp($browser));

            $browser->visit('/totp')
                ->assertSee('Mi seguridad');

            $this->assertSame([], $this->violacionesCsp($browser));

            $browser->visit('/reportes/cierre')
                ->assertSee('Cierre del día');

            $this->assertSame([], $this->violacionesCsp($browser));
        });
    }
}
