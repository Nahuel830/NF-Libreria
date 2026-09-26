<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use App\Services\UsuarioService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Admin]);
    }

    public function test_admin_puede_listar_usuarios(): void
    {
        $this->actingAs($this->admin())->get('/usuarios')
            ->assertOk()
            ->assertSee('Usuarios');
    }

    public function test_admin_puede_crear_usuario_y_queda_auditado(): void
    {
        $admin = $this->admin();

        $respuesta = $this->actingAs($admin)->post('/usuarios', [
            'nombre' => 'Nuevo Cajero',
            'usuario' => 'cajero.nuevo',
            'rol' => 'cajero',
            'password' => 'temporal123',
            'password_confirmation' => 'temporal123',
        ]);

        $respuesta->assertRedirect(route('usuarios.index'));

        $creado = User::where('usuario', 'cajero.nuevo')->first();
        $this->assertNotNull($creado);
        $this->assertTrue($creado->debe_cambiar_password);
        $this->assertTrue(Hash::check('temporal123', $creado->password));

        $this->assertDatabaseHas('auditoria', [
            'accion' => 'CREAR',
            'user_id' => $admin->id,
            'entidad' => 'users',
            'entidad_id' => $creado->id,
        ]);
    }

    public function test_no_se_puede_crear_usuario_con_nombre_repetido(): void
    {
        User::factory()->create(['usuario' => 'repetido']);

        $this->actingAs($this->admin())->post('/usuarios', [
            'nombre' => 'Otro',
            'usuario' => 'repetido',
            'rol' => 'cajero',
            'password' => 'temporal123',
            'password_confirmation' => 'temporal123',
        ])->assertSessionHasErrors('usuario');

        $this->assertEquals(1, User::where('usuario', 'repetido')->count());
    }

    public function test_no_se_puede_crear_usuario_con_mayusculas_o_caracteres_invalidos(): void
    {
        $this->actingAs($this->admin())->post('/usuarios', [
            'nombre' => 'Otro',
            'usuario' => 'Con-Mayusculas!',
            'rol' => 'cajero',
            'password' => 'temporal123',
            'password_confirmation' => 'temporal123',
        ])->assertSessionHasErrors('usuario');
    }

    public function test_admin_puede_editar_usuario_y_queda_auditado(): void
    {
        $admin = $this->admin();
        $usuario = User::factory()->create(['nombre' => 'Antes', 'rol' => Rol::Cajero]);

        $this->actingAs($admin)->put("/usuarios/{$usuario->id}", [
            'nombre' => 'Después',
            'rol' => 'encargado',
        ])->assertRedirect(route('usuarios.index'));

        $usuario->refresh();
        $this->assertSame('Después', $usuario->nombre);
        $this->assertSame(Rol::Encargado, $usuario->rol);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'EDITAR',
            'user_id' => $admin->id,
            'entidad_id' => $usuario->id,
        ]);
    }

    public function test_admin_puede_desactivar_y_reactivar_con_auditoria(): void
    {
        $admin = $this->admin();
        $usuario = User::factory()->create(['activo' => true]);

        $this->actingAs($admin)->patch("/usuarios/{$usuario->id}/estado")
            ->assertRedirect(route('usuarios.index'));
        $this->assertFalse($usuario->fresh()->activo);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'DESACTIVAR',
            'entidad_id' => $usuario->id,
        ]);

        $this->actingAs($admin)->patch("/usuarios/{$usuario->id}/estado")
            ->assertRedirect(route('usuarios.index'));
        $this->assertTrue($usuario->fresh()->activo);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'ACTIVAR',
            'entidad_id' => $usuario->id,
        ]);
    }

    public function test_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/usuarios/{$admin->id}/estado")
            ->assertSessionHasErrors('usuario');

        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_admin_no_puede_quitarse_el_rol_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put("/usuarios/{$admin->id}", [
            'nombre' => $admin->nombre,
            'rol' => 'cajero',
        ])->assertSessionHasErrors('rol');

        $this->assertSame(Rol::Admin, $admin->fresh()->rol);
    }

    public function test_no_se_puede_dejar_al_sistema_sin_admins(): void
    {
        $unicoAdmin = User::factory()->create(['rol' => Rol::Admin, 'activo' => true]);
        $otro = User::factory()->create(['rol' => Rol::Cajero]);
        $servicio = app(UsuarioService::class);

        $this->expectException(DomainException::class);
        $servicio->cambiarActivo($unicoAdmin, false, $otro);
    }

    public function test_no_se_puede_quitar_el_rol_al_ultimo_admin(): void
    {
        $unicoAdmin = User::factory()->create(['rol' => Rol::Admin, 'activo' => true]);
        $otro = User::factory()->create(['rol' => Rol::Cajero]);
        $servicio = app(UsuarioService::class);

        $this->expectException(DomainException::class);
        $servicio->actualizar($unicoAdmin, ['nombre' => $unicoAdmin->nombre, 'rol' => 'cajero'], $otro);
    }

    public function test_restablecer_password_deja_debe_cambiar_en_true(): void
    {
        $admin = $this->admin();
        $usuario = User::factory()->create(['debe_cambiar_password' => false]);

        $this->actingAs($admin)->put("/usuarios/{$usuario->id}/password", [
            'password' => 'temporal999',
            'password_confirmation' => 'temporal999',
        ])->assertRedirect(route('usuarios.index'));

        $usuario->refresh();
        $this->assertTrue($usuario->debe_cambiar_password);
        $this->assertTrue(Hash::check('temporal999', $usuario->password));
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'CAMBIO_PASSWORD',
            'user_id' => $admin->id,
            'entidad_id' => $usuario->id,
        ]);
    }

    public function test_encargado_y_cajero_reciben_403(): void
    {
        foreach ([Rol::Encargado, Rol::Cajero] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol]);

            $this->actingAs($usuario)->get('/usuarios')->assertForbidden();
            $this->actingAs($usuario)->get('/configuracion')->assertForbidden();
            $this->actingAs($usuario)->get('/auditoria')->assertForbidden();
        }
    }

    public function test_guardar_configuracion_cambia_valores_y_audita(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/configuracion', [
            'nombre_negocio' => 'Librería Central',
            'direccion' => 'Av. Siempre Viva 123',
            'telefono' => '77712345',
            'mensaje_ticket' => '¡Vuelva pronto!',
            'permitir_stock_negativo' => '0',
            'minutos_inactividad' => '30',
        ])->assertRedirect(route('configuracion.editar'));

        $this->assertDatabaseHas('configuracion', ['clave' => 'nombre_negocio', 'valor' => 'Librería Central']);
        $this->assertDatabaseHas('configuracion', ['clave' => 'minutos_inactividad', 'valor' => '30']);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'CONFIGURACION',
            'user_id' => $admin->id,
        ]);
    }

    public function test_auditoria_es_solo_lectura_y_muestra_detalle(): void
    {
        $admin = $this->admin();

        $registro = \App\Models\Auditoria::create([
            'user_id' => $admin->id,
            'accion' => 'CREAR',
            'entidad' => 'users',
            'entidad_id' => $admin->id,
            'descripcion' => 'Registro de prueba.',
            'datos_anteriores' => ['rol' => 'cajero'],
            'datos_nuevos' => ['rol' => 'encargado'],
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($admin)->get('/auditoria')
            ->assertOk()
            ->assertSee('Registro de prueba.');

        $this->actingAs($admin)->get("/auditoria/{$registro->id}")
            ->assertOk()
            ->assertSee('Datos anteriores')
            ->assertSee('encargado');
    }
}
