<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RutasPublicasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rutas públicas por diseño (todo lo demás exige auth).
     */
    protected function rutasPublicas(): array
    {
        return [
            'login',
            'login.entrar',
            'totp.configurar',
            'totp.configurar.guardar',
            'totp.verificar',
            'totp.verificar.comprobar',
            'storage.local',
            'storage.local.upload',
        ];
    }

    public function test_toda_ruta_no_listada_exige_autenticacion(): void
    {
        $sinAuth = [];

        foreach (Route::getRoutes() as $ruta) {
            $nombre = $ruta->getName();

            if (in_array($nombre, $this->rutasPublicas(), true)) {
                continue;
            }

            if ($ruta->uri() === 'up') {
                continue;
            }

            if (str_starts_with((string) $nombre, 'dusk.')) {
                continue;
            }

            if (! in_array('auth', $ruta->gatherMiddleware(), true)) {
                $sinAuth[] = implode('|', $ruta->methods()).' '.$ruta->uri();
            }
        }

        $this->assertSame([], $sinAuth);
    }

    public function test_rutas_dusk_no_se_registran_en_produccion(): void
    {
        $proceso = Process::fromShellCommandline(
            'php artisan route:list --no-ansi',
            base_path(),
            ['APP_ENV' => 'production']
        );
        $proceso->setTimeout(120);
        $proceso->run();

        $this->assertTrue($proceso->isSuccessful());
        $this->assertStringNotContainsString('_dusk', $proceso->getOutput());
    }

    public function test_rutas_publicas_responden_sin_sesion(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/up')->assertOk();
    }

    public function test_totp_sin_sesion_devuelve_error_controlado(): void
    {
        $this->get('/totp/verificar')->assertNotFound();
        $this->post('/totp/verificar', ['codigo' => '123456'])->assertNotFound();
        $this->get('/totp/configurar')->assertNotFound();
    }

    public function test_estilos_solo_admin_y_solo_local(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        app()->instance('env', 'local');

        try {
            $this->actingAs($admin)->get('/estilos')->assertOk();
            $this->actingAs($cajero)->get('/estilos')->assertForbidden();
        } finally {
            app()->instance('env', 'testing');
        }

        $this->actingAs($admin)->get('/estilos')->assertNotFound();
    }

    public function test_paginas_de_error_propias(): void
    {
        config()->set('app.debug', false);

        $this->get('/ruta-que-no-existe')->assertNotFound()
            ->assertSee('no existe o fue movida');

        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        $this->actingAs($cajero)->get('/usuarios')->assertForbidden()
            ->assertSee('No tienes permiso para acceder a esta sección');

        foreach (['403', '404', '419', '429', '500'] as $codigo) {
            $this->assertTrue(view()->exists("errors.{$codigo}"), "Falta errors/{$codigo}.");
        }
    }

    public function test_sin_telescope_ni_debugbar(): void
    {
        $this->assertFalse(class_exists(\Laravel\Telescope\Telescope::class));
        $this->assertFalse(class_exists(\Barryvdh\Debugbar\Facade::class));

        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $this->assertArrayNotHasKey('laravel/telescope', $composer['require'] ?? []);
        $this->assertArrayNotHasKey('barryvdh/laravel-debugbar', $composer['require'] ?? []);
        $this->assertArrayNotHasKey('laravel/telescope', $composer['require-dev'] ?? []);
        $this->assertArrayNotHasKey('barryvdh/laravel-debugbar', $composer['require-dev'] ?? []);
    }
}
