<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TotpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('login-ip:127.0.0.1');

        parent::tearDown();
    }

    protected function adminSinTotp(string $usuario = 'admintotp'): User
    {
        return User::factory()->create([
            'usuario' => $usuario,
            'password' => 'secreta12345',
            'rol' => Rol::Admin,
        ]);
    }

    protected function codigoDe(User $usuario): string
    {
        $secreto = $usuario->fresh()->totp_secreto ?? session('totp_secreto');

        return (new Google2FA())->getCurrentOtp($secreto);
    }

    public function test_login_admin_sin_totp_redirige_a_configurar(): void
    {
        $this->adminSinTotp();

        $this->post('/login', ['usuario' => 'admintotp', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.configurar'));

        $this->assertGuest();
    }

    public function test_activar_totp_con_codigo_valido(): void
    {
        $admin = $this->adminSinTotp('admintotp2');

        $this->post('/login', ['usuario' => 'admintotp2', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.configurar'));

        $this->get('/totp/configurar')->assertOk()->assertSee('Activar');

        $this->post('/totp/configurar', ['codigo' => $this->codigoDe($admin)])
            ->assertRedirect(route('totp.estado'));

        $admin->refresh();
        $this->assertTrue($admin->totp_activo);
        $this->assertCount(8, $admin->totp_recuperacion);
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('auditoria', ['accion' => 'TOTP', 'user_id' => $admin->id]);
    }

    public function test_login_admin_con_totp_exige_codigo(): void
    {
        $admin = $this->adminSinTotp('admintotp3');
        $admin->forceFill([
            'totp_secreto' => (new Google2FA())->generateSecretKey(),
            'totp_activo' => true,
        ])->save();

        $this->post('/login', ['usuario' => 'admintotp3', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));
        $this->assertGuest();

        $this->post('/totp/verificar', ['codigo' => '000000'])
            ->assertSessionHasErrors('codigo');
        $this->assertGuest();

        $this->post('/totp/verificar', ['codigo' => $this->codigoDe($admin)])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_codigo_de_recuperacion_se_consume(): void
    {
        $admin = $this->adminSinTotp('admintotp4');
        $planos = app(\App\Services\TotpService::class)->activar(
            $admin,
            (new Google2FA())->generateSecretKey()
        );

        $this->post('/login', ['usuario' => 'admintotp4', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));

        $this->post('/totp/verificar', ['codigo' => $planos[0]])
            ->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertCount(7, $admin->fresh()->totp_recuperacion);

        $this->post('/logout');
        $this->post('/login', ['usuario' => 'admintotp4', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));

        $this->post('/totp/verificar', ['codigo' => $planos[0]])
            ->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_bloqueo_por_ip_tras_20_fallos(): void
    {
        foreach (['blq1', 'blq2', 'blq3', 'blq4'] as $usuario) {
            User::factory()->create(['usuario' => $usuario, 'password' => 'secreta12345']);

            for ($i = 0; $i < 5; $i++) {
                $this->post('/login', ['usuario' => $usuario, 'password' => 'mal']);
            }
        }

        User::factory()->create(['usuario' => 'blq5', 'password' => 'secreta12345']);

        $this->post('/login', ['usuario' => 'blq5', 'password' => 'mal'])
            ->assertStatus(429);
    }

    public function test_login_audita_ip_nueva_solo_la_primera_vez(): void
    {
        $cajero = User::factory()->create(['usuario' => 'ipnuevo', 'password' => 'secreta12345', 'rol' => Rol::Cajero]);

        $this->post('/login', ['usuario' => 'ipnuevo', 'password' => 'secreta12345'])->assertRedirect('/');
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'LOGIN',
            'user_id' => $cajero->id,
        ]);
        $primer = \App\Models\Auditoria::where('accion', 'LOGIN')->latest('id')->first();
        $this->assertStringContainsString('Dispositivo/IP nueva', $primer->descripcion);

        $this->post('/logout');

        $this->post('/login', ['usuario' => 'ipnuevo', 'password' => 'secreta12345'])->assertRedirect('/');
        $segundo = \App\Models\Auditoria::where('accion', 'LOGIN')->latest('id')->first();
        $this->assertStringNotContainsString('Dispositivo/IP nueva', $segundo->descripcion);
    }

    public function test_restriccion_de_ip_para_cajero(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $admin = User::factory()->create(['rol' => Rol::Admin]);

        app(\App\Services\ConfiguracionService::class)->set('ips_cajero', '10.0.0.1');

        $this->actingAs($cajero)->get('/')->assertForbidden();
        $this->actingAs($admin)->get('/')->assertOk();

        app(\App\Services\ConfiguracionService::class)->set('ips_cajero', '');

        $this->actingAs($cajero)->get('/')->assertOk();
    }

    public function test_cabeceras_solo_en_produccion(): void
    {
        $this->get('/login')->assertOk()
            ->assertHeaderMissing('X-Frame-Options');

        app()->instance('env', 'production');

        try {
            $this->get('/login')->assertOk()
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'same-origin');
        } finally {
            app()->instance('env', 'testing');
        }
    }

    public function test_encargado_activa_y_desactiva_voluntariamente(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado, 'password' => 'secreta12345']);

        $this->actingAs($encargado)->get('/totp/configurar')->assertOk();
        $this->actingAs($encargado)->post('/totp/configurar', ['codigo' => $this->codigoDe($encargado)])
            ->assertRedirect(route('totp.estado'));
        $this->assertTrue($encargado->fresh()->totp_activo);

        $this->actingAs($encargado)->post('/totp/desactivar', ['actual' => 'secreta12345'])
            ->assertRedirect(route('totp.estado'));
        $this->assertFalse($encargado->fresh()->totp_activo);
    }
}
