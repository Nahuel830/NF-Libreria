<?php

namespace Tests\Browser;

use Database\Seeders\CategoriasSeeder;
use Database\Seeders\ConfiguracionSeeder;
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
}
