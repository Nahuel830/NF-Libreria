<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use App\Services\ConfiguracionService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        return (new Google2FA())->getCurrentOtp($usuario->fresh()->totp_secreto);
    }

    /**
     * Código del paso siguiente: válido por ventana ±1 aunque el paso actual ya se haya usado.
     */
    protected function codigoFuturo(User $usuario): string
    {
        $google = new Google2FA();

        return $google->oathTotp($usuario->fresh()->totp_secreto, $google->getTimestamp() + 1);
    }

    protected function activarTotp(User $usuario): array
    {
        $totp = app(TotpService::class);
        $secreto = $totp->generarSecretoPara($usuario->fresh());
        $paso = $totp->verificar($usuario->fresh(), (new Google2FA())->getCurrentOtp($secreto));

        return $totp->confirmar($usuario->fresh(), $paso);
    }

    public function test_login_admin_sin_totp_exige_activacion_y_restringe_rutas(): void
    {
        $admin = $this->adminSinTotp();

        $this->post('/login', ['usuario' => 'admintotp', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.configurar'));
        $this->assertAuthenticatedAs($admin);

        $this->get('/')->assertRedirect(route('totp.configurar'));
        $this->get('/totp/configurar')->assertOk()->assertSee('Activar');

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_activar_totp_con_codigo_valido(): void
    {
        $admin = $this->adminSinTotp('admintotp2');

        $this->post('/login', ['usuario' => 'admintotp2', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.configurar'));

        $this->get('/totp/configurar')->assertOk()->assertSee('Activar');

        $secreto = $admin->fresh()->totp_secreto;

        $this->post('/totp/configurar', ['codigo' => $this->codigoDe($admin)])
            ->assertRedirect(route('totp.estado'))
            ->assertSessionHas('codigos_recuperacion');

        $admin->refresh();
        $this->assertNotNull($admin->totp_confirmado_en);
        $this->assertNotNull($admin->totp_ultimo_paso);
        $this->assertCount(10, $admin->codigos_recuperacion);
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('auditoria', ['accion' => 'TOTP', 'user_id' => $admin->id]);

        $crudo = DB::table('users')->where('id', $admin->id)->value('totp_secreto');
        $this->assertNotSame($secreto, $crudo);
    }

    public function test_ventana_mas_menos_un_paso_y_codigo_no_se_reutiliza(): void
    {
        $admin = $this->adminSinTotp('admintotp3');

        $this->post('/login', ['usuario' => 'admintotp3', 'password' => 'secreta12345']);
        $this->get('/totp/configurar')->assertOk();

        $google = new Google2FA();
        $secreto = $admin->fresh()->totp_secreto;
        $codigoPrevio = $google->oathTotp($secreto, $google->getTimestamp() - 1);

        $this->post('/totp/configurar', ['codigo' => $codigoPrevio])
            ->assertRedirect(route('totp.estado'));
        $this->assertNotNull($admin->fresh()->totp_confirmado_en);

        $this->post('/logout');

        $this->post('/login', ['usuario' => 'admintotp3', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));
        $this->assertGuest();

        $this->post('/totp/verificar', ['codigo' => $codigoPrevio])
            ->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_login_con_totp_exige_codigo_y_luego_entra(): void
    {
        $admin = $this->adminSinTotp('admintotp4');
        $this->activarTotp($admin);

        $this->post('/login', ['usuario' => 'admintotp4', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));
        $this->assertGuest();

        $this->post('/totp/verificar', ['codigo' => '000000'])
            ->assertSessionHasErrors('codigo');
        $this->assertGuest();

        $this->post('/totp/verificar', ['codigo' => $this->codigoFuturo($admin)])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_cinco_intentos_fallidos_cierran_la_sesion(): void
    {
        $admin = $this->adminSinTotp('admintotp5');
        $this->activarTotp($admin);

        $this->post('/login', ['usuario' => 'admintotp5', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));

        for ($i = 0; $i < 4; $i++) {
            $this->post('/totp/verificar', ['codigo' => '000000'])
                ->assertSessionHasErrors('codigo');
            $this->assertGuest();
        }

        $this->post('/totp/verificar', ['codigo' => '000000'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sesion_intermedia_no_accede_a_rutas_protegidas(): void
    {
        $admin = $this->adminSinTotp('admintotp6');
        $this->activarTotp($admin);

        $this->post('/login', ['usuario' => 'admintotp6', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));
        $this->assertGuest();

        $this->get('/')->assertRedirect(route('login'));
        $this->get('/totp')->assertRedirect(route('login'));
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_codigo_de_recuperacion_se_consume(): void
    {
        $admin = $this->adminSinTotp('admintotp7');
        $planos = $this->activarTotp($admin);

        $this->post('/login', ['usuario' => 'admintotp7', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));

        $this->post('/totp/verificar', ['codigo' => $planos[0]])
            ->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertCount(9, $admin->fresh()->codigos_recuperacion);

        $this->post('/logout');
        $this->post('/login', ['usuario' => 'admintotp7', 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));

        $this->post('/totp/verificar', ['codigo' => $planos[0]])
            ->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_encargado_no_obligado_entra_directo_y_puede_activar_voluntariamente(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado, 'password' => 'secreta12345']);

        $this->post('/login', ['usuario' => $encargado->usuario, 'password' => 'secreta12345'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($encargado);

        $this->get('/totp/configurar')->assertOk();
        $this->post('/totp/configurar', ['codigo' => $this->codigoDe($encargado)])
            ->assertRedirect(route('totp.estado'));
        $this->assertNotNull($encargado->fresh()->totp_confirmado_en);

        $this->post('/logout');

        $this->post('/login', ['usuario' => $encargado->usuario, 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.verificar'));
        $this->assertGuest();

        $this->post('/totp/verificar', ['codigo' => $this->codigoFuturo($encargado)])
            ->assertRedirect('/');
    }

    public function test_encargado_obligado_exige_activacion(): void
    {
        app(ConfiguracionService::class)->set('totp_obligatorio_encargado', '1');

        $encargado = User::factory()->create(['rol' => Rol::Encargado, 'password' => 'secreta12345']);

        $this->post('/login', ['usuario' => $encargado->usuario, 'password' => 'secreta12345'])
            ->assertRedirect(route('totp.configurar'));
        $this->assertAuthenticatedAs($encargado);

        $this->get('/')->assertRedirect(route('totp.configurar'));
    }

    public function test_desactivar_requiere_password_y_codigo_y_admin_no_puede(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado, 'password' => 'secreta12345']);
        $this->actingAs($encargado);
        $this->get('/totp/configurar')->assertOk();
        $this->post('/totp/configurar', ['codigo' => $this->codigoDe($encargado)])
            ->assertRedirect(route('totp.estado'));

        $this->post('/totp/desactivar', ['actual' => 'otraclave12', 'codigo' => $this->codigoFuturo($encargado)])
            ->assertSessionHasErrors('actual');
        $this->assertNotNull($encargado->fresh()->totp_confirmado_en);

        $this->post('/totp/desactivar', ['actual' => 'secreta12345', 'codigo' => $this->codigoFuturo($encargado)])
            ->assertRedirect(route('totp.estado'));
        $this->assertNull($encargado->fresh()->totp_confirmado_en);

        $admin = $this->adminSinTotp('admintotp8');
        $this->activarTotp($admin);
        $this->actingAs($admin);

        $this->post('/totp/desactivar', ['actual' => 'secreta12345', 'codigo' => $this->codigoDe($admin)])
            ->assertForbidden();
    }

    public function test_regenerar_codigos_requiere_password_y_codigo(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado, 'password' => 'secreta12345']);
        $planos = $this->activarTotp($encargado);
        $encargado->refresh();
        $this->actingAs($encargado);

        $this->post('/totp/regenerar', ['actual' => 'secreta12345', 'codigo' => '000000'])
            ->assertForbidden();

        $this->post('/totp/regenerar', ['actual' => 'secreta12345', 'codigo' => $this->codigoFuturo($encargado)])
            ->assertRedirect(route('totp.estado'))
            ->assertSessionHas('codigos_recuperacion');

        $this->assertFalse(app(TotpService::class)->usarCodigoRecuperacion($encargado->fresh(), $planos[0]));
    }

    public function test_admin_restablece_totp_de_otro_usuario(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin, 'password' => 'secreta12345']);
        $otro = User::factory()->create(['rol' => Rol::Encargado, 'password' => 'secreta12345']);
        $this->activarTotp($admin);
        $this->activarTotp($otro);
        $admin->refresh();

        $this->actingAs($admin)
            ->get(route('usuarios.editar', $otro))
            ->assertOk()
            ->assertSee('Restablecer verificación');

        $this->actingAs($admin)
            ->post(route('usuarios.totp.restablecer', $otro))
            ->assertRedirect(route('usuarios.editar', $otro));

        $this->assertNull($otro->fresh()->totp_confirmado_en);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'TOTP',
            'user_id' => $admin->id,
            'entidad' => 'users',
            'entidad_id' => $otro->id,
        ]);
    }

    public function test_comando_restablece_totp_con_motivo_y_audita(): void
    {
        $admin = $this->adminSinTotp('admintotp9');
        $this->activarTotp($admin);

        $this->artisan('totp:restablecer', ['usuario' => 'admintotp9'])
            ->assertFailed();

        $this->artisan('totp:restablecer', ['usuario' => 'admintotp9', '--motivo' => 'perdió el teléfono'])
            ->assertSuccessful();

        $this->assertNull($admin->fresh()->totp_confirmado_en);
        $this->assertDatabaseHas('auditoria', ['accion' => 'TOTP', 'entidad_id' => $admin->id]);
        $registro = \App\Models\Auditoria::where('accion', 'TOTP')->latest('id')->first();
        $this->assertStringContainsString('perdió el teléfono', $registro->descripcion);
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
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);

        app(\App\Services\ConfiguracionService::class)->set('ips_cajero', '10.0.0.1');

        $this->actingAs($cajero)->get('/')->assertForbidden();
        $this->actingAs($encargado)->get('/')->assertOk();

        app(\App\Services\ConfiguracionService::class)->set('ips_cajero', '');

        $this->actingAs($cajero)->get('/')->assertOk();
    }

}
