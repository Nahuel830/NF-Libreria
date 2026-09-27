<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutasPublicasTest extends TestCase
{
    use RefreshDatabase;

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
}
