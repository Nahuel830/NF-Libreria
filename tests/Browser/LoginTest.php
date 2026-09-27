<?php

namespace Tests\Browser;

use Database\Seeders\CategoriasSeeder;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Database\Seeders\UsuariosDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => ConfiguracionSeeder::class]);
        $this->artisan('db:seed', ['--class' => UsuarioAdminSeeder::class]);
        $this->artisan('db:seed', ['--class' => CategoriasSeeder::class]);
        $this->artisan('db:seed', ['--class' => UsuariosDemoSeeder::class]);
    }

    public function test_login_y_ver_panel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('NF Librería')
                ->type('usuario', 'encargado')
                ->type('password', 'demo12345')
                ->press('Entrar')
                ->assertPathIs('/')
                ->assertSee('Panel de inicio');
        });
    }

    public function test_admin_activa_totp_con_qr(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('usuario', 'admin')
                ->type('password', 'Admin12345')
                ->press('Entrar')
                ->waitForText('escanea este código QR', 10)
                ->assertPathIs('/totp/configurar');

            $secreto = $browser->script("return document.getElementById('totp-secreto-valor').textContent;");
            $codigo = (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp(trim((string) $secreto[0]));

            $browser->type('#codigo', $codigo)
                ->press('Activar')
                ->waitForText('Códigos de recuperación', 10)
                ->assertSee('Verificación en dos pasos');
        });

        $this->assertNotNull(
            \App\Models\User::where('usuario', 'admin')->first()->totp_confirmado_en
        );
    }
}
