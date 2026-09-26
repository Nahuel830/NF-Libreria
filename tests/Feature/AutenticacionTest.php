<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correcto_redirige_a_inicio_y_registra_login(): void
    {
        $user = User::factory()->create([
            'usuario' => 'cajero-login',
            'password' => 'secreta123',
            'rol' => Rol::Cajero,
        ]);

        $respuesta = $this->post('/login', [
            'usuario' => 'cajero-login',
            'password' => 'secreta123',
        ]);

        $respuesta->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->ultimo_acceso);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'LOGIN',
            'user_id' => $user->id,
        ]);
    }

    public function test_login_con_password_incorrecta_falla_y_registra_fallido(): void
    {
        $user = User::factory()->create([
            'usuario' => 'cajero-fallo',
            'password' => 'secreta123',
        ]);

        $respuesta = $this->post('/login', [
            'usuario' => 'cajero-fallo',
            'password' => 'equivocada',
        ]);

        $respuesta->assertSessionHasErrors('usuario');
        $this->assertGuest();
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'LOGIN_FALLIDO',
            'entidad' => 'users',
            'entidad_id' => $user->id,
        ]);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        User::factory()->create([
            'usuario' => 'cajero-inactivo',
            'password' => 'secreta123',
            'activo' => false,
        ]);

        $respuesta = $this->post('/login', [
            'usuario' => 'cajero-inactivo',
            'password' => 'secreta123',
        ]);

        $respuesta->assertSessionHasErrors('usuario', 'Usuario o contraseña incorrectos.');
        $this->assertGuest();
    }

    public function test_despues_de_5_intentos_fallidos_el_sexto_se_bloquea(): void
    {
        User::factory()->create([
            'usuario' => 'cajero-bloqueo',
            'password' => 'secreta123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'usuario' => 'cajero-bloqueo',
                'password' => 'equivocada',
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'usuario' => 'cajero-bloqueo',
            'password' => 'equivocada',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_usuario_con_debe_cambiar_password_es_redirigido(): void
    {
        $user = User::factory()->create([
            'usuario' => 'cajero-cambio',
            'password' => 'secreta123',
            'debe_cambiar_password' => true,
        ]);

        $this->post('/login', [
            'usuario' => 'cajero-cambio',
            'password' => 'secreta123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertRedirect('/cambiar-password');
        $this->get('/cambiar-password')->assertOk();
    }

    public function test_cambiar_password_funciona_y_exige_la_actual(): void
    {
        $user = User::factory()->create([
            'usuario' => 'cajero-pass',
            'password' => 'actual123',
            'debe_cambiar_password' => true,
        ]);

        $this->actingAs($user)->put('/cambiar-password', [
            'actual' => 'otra-clave',
            'nueva' => 'nueva12345',
            'nueva_confirmation' => 'nueva12345',
        ])->assertSessionHasErrors('actual');

        $this->actingAs($user)->put('/cambiar-password', [
            'actual' => 'actual123',
            'nueva' => 'nueva12345',
            'nueva_confirmation' => 'nueva12345',
        ])->assertRedirect('/');

        $user->refresh();
        $this->assertTrue(Hash::check('nueva12345', $user->password));
        $this->assertFalse($user->debe_cambiar_password);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'CAMBIO_PASSWORD',
            'user_id' => $user->id,
        ]);
    }

    public function test_rutas_protegidas_redirigen_a_login_sin_sesion(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/cambiar-password')->assertRedirect('/login');
    }

    public function test_cajero_recibe_403_en_ruta_solo_admin(): void
    {
        Route::get('/_solo-admin', fn () => 'ok')->middleware(['auth', 'rol:admin']);

        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        $this->actingAs($cajero)->get('/_solo-admin')
            ->assertForbidden()
            ->assertSee('No tienes permiso para acceder a esta sección');
    }

    public function test_logout_cierra_la_sesion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'LOGOUT',
            'user_id' => $user->id,
        ]);
    }

    public function test_usuario_desactivado_con_sesion_abierta_se_cierra(): void
    {
        $user = User::factory()->create(['activo' => true]);

        $this->actingAs($user)->get('/')->assertOk();

        $user->forceFill(['activo' => false])->save();

        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_auditoria_nunca_guarda_la_password(): void
    {
        User::factory()->create([
            'usuario' => 'cajero-limpio',
            'password' => 'clave-secreta-123',
        ]);

        $this->post('/login', [
            'usuario' => 'cajero-limpio',
            'password' => 'otra-clave-456',
        ]);

        $user = User::where('usuario', 'cajero-limpio')->first();
        $this->actingAs($user)->put('/cambiar-password', [
            'actual' => 'clave-secreta-123',
            'nueva' => 'nueva-clave-789',
            'nueva_confirmation' => 'nueva-clave-789',
        ]);

        foreach (Auditoria::all() as $registro) {
            $texto = $registro->descripcion
                .' '.json_encode($registro->datos_anteriores)
                .' '.json_encode($registro->datos_nuevos);

            $this->assertStringNotContainsString('clave-secreta-123', $texto);
            $this->assertStringNotContainsString('otra-clave-456', $texto);
            $this->assertStringNotContainsString('nueva-clave-789', $texto);
        }
    }
}
