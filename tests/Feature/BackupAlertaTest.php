<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use App\Services\ConfiguracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupAlertaTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_muestra_alerta_si_backup_viejo_o_con_error(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $config = app(ConfiguracionService::class);

        $config->set('ultimo_backup_fecha', now()->subDays(2)->toDateTimeString());
        $config->set('ultimo_backup_resultado', 'ok: nf-libreria_viejo.backup');

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('más de 24 horas');

        $config->set('ultimo_backup_fecha', now()->toDateTimeString());
        $config->set('ultimo_backup_resultado', 'error: se cortó la luz');

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('último resultado fue error');
    }

    public function test_panel_no_muestra_alerta_con_backup_reciente_ok(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        $config = app(ConfiguracionService::class);

        $config->set('ultimo_backup_fecha', now()->toDateTimeString());
        $config->set('ultimo_backup_resultado', 'ok: nf-libreria_hoy.backup');

        $respuesta = $this->actingAs($admin)->get('/');
        $respuesta->assertOk()->assertSee('Último backup:');
        $this->assertStringNotContainsString('más de 24 horas', $respuesta->getContent());
    }

    public function test_comando_registra_resultado(): void
    {
        $this->artisan('backup:registrar-resultado', ['estado' => 'ok', 'mensaje' => 'prueba'])
            ->assertSuccessful();

        $this->assertDatabaseHas('configuracion', ['clave' => 'ultimo_backup_fecha']);
        $this->assertDatabaseHas('configuracion', ['clave' => 'ultimo_backup_resultado', 'valor' => 'ok: prueba']);
    }
}
