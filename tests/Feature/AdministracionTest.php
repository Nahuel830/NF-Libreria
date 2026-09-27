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
        $usuario = User::factory()->create(['rol' => Rol::Cajero, 'debe_cambiar_password' => false]);

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

    public function test_password_trivial_se_rechaza_al_crear_y_restablecer(): void
    {
        foreach (['password', '123456'] as $trivial) {
            $this->actingAs($this->admin())->post('/usuarios', [
                'nombre' => 'Trivial',
                'usuario' => 'trivial.'.$trivial,
                'rol' => 'cajero',
                'password' => $trivial,
                'password_confirmation' => $trivial,
            ])->assertSessionHasErrors('password');
        }

        $this->actingAs($this->admin())->post('/usuarios', [
            'nombre' => 'Igual',
            'usuario' => 'igualito12',
            'rol' => 'cajero',
            'password' => 'igualito12',
            'password_confirmation' => 'igualito12',
        ])->assertSessionHasErrors('password');

        $encargado = User::factory()->create(['rol' => Rol::Encargado, 'usuario' => 'encargado9']);

        $this->actingAs($this->admin())->put("/usuarios/{$encargado->id}/password", [
            'password' => 'encargado9',
            'password_confirmation' => 'encargado9',
        ])->assertSessionHasErrors('password');
    }

    public function test_password_exige_letras_y_numeros_y_no_nombre_del_negocio(): void
    {
        app(\App\Services\ConfiguracionService::class)->set('nombre_negocio', 'NF Librería');

        foreach (['abcdefghij', '1234567890', 'corta123'] as $debil) {
            $this->actingAs($this->admin())->post('/usuarios', [
                'nombre' => 'Débil',
                'usuario' => 'debil.'.mb_strlen($debil),
                'rol' => 'cajero',
                'password' => $debil,
                'password_confirmation' => $debil,
            ])->assertSessionHasErrors('password');
        }

        $this->actingAs($this->admin())->post('/usuarios', [
            'nombre' => 'Negocio',
            'usuario' => 'negocio1',
            'rol' => 'cajero',
            'password' => 'xlibrería12',
            'password_confirmation' => 'xlibrería12',
        ])->assertSessionHasErrors('password');

        $this->actingAs($this->admin())->post('/usuarios', [
            'nombre' => 'Fuerte',
            'usuario' => 'fuerte1',
            'rol' => 'cajero',
            'password' => 'fuerte12345',
            'password_confirmation' => 'fuerte12345',
        ])->assertRedirect(route('usuarios.index'));
    }

    public function test_forzar_cambio_password_marca_a_todos(): void
    {
        $admin = $this->admin();
        $cajero = User::factory()->create(['rol' => Rol::Cajero, 'debe_cambiar_password' => false]);

        $valores = [
            'nombre_negocio' => 'NF Librería',
            'mensaje_ticket' => 'Gracias',
            'minutos_inactividad' => 60,
            'forzar_cambio_password' => '1',
        ];

        $this->actingAs($admin)->put('/configuracion', $valores)
            ->assertRedirect(route('configuracion.editar'));

        $this->assertTrue($cajero->fresh()->debe_cambiar_password);
        $this->assertTrue($admin->fresh()->debe_cambiar_password);
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

    public function test_subir_y_quitar_logo_del_negocio(): void
    {
        $admin = $this->admin();
        \Illuminate\Support\Facades\Storage::fake('public');

        $png = "\x89PNG\r\n\x1a\n"
            .pack('N', 13).'IHDR'.pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0)
            .hash('crc32b', 'IHDR'.pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0), true)
            .pack('N', 12).'IDAT'.($idat = gzcompress("\x00\xff\x00\x00"))
            .hash('crc32b', 'IDAT'.$idat, true)
            .pack('N', 0).'IEND'.hash('crc32b', 'IEND', true);

        $archivo = \Illuminate\Http\UploadedFile::fake()->createWithContent('logo.png', $png);

        $this->actingAs($admin)->put('/configuracion', [
            'nombre_negocio' => 'NF Librería',
            'mensaje_ticket' => '¡Gracias!',
            'minutos_inactividad' => '60',
            'logo' => $archivo,
        ])->assertRedirect(route('configuracion.editar'));

        $ruta = \App\Models\Configuracion::where('clave', 'logo_negocio')->value('valor');
        $this->assertNotEmpty($ruta);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($ruta);

        $this->actingAs($admin)->put('/configuracion', [
            'nombre_negocio' => 'NF Librería',
            'mensaje_ticket' => '¡Gracias!',
            'minutos_inactividad' => '60',
            'quitar_logo' => '1',
        ])->assertRedirect(route('configuracion.editar'));

        $this->assertSame('', \App\Models\Configuracion::where('clave', 'logo_negocio')->value('valor'));
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($ruta);
    }

    public function test_minutos_inactividad_fuera_de_rango_se_rechaza(): void
    {
        $this->actingAs($this->admin())->put('/configuracion', [
            'nombre_negocio' => 'NF Librería',
            'mensaje_ticket' => '¡Gracias!',
            'minutos_inactividad' => '1',
        ])->assertSessionHasErrors('minutos_inactividad');

        $this->actingAs($this->admin())->put('/configuracion', [
            'nombre_negocio' => 'NF Librería',
            'mensaje_ticket' => '¡Gracias!',
            'minutos_inactividad' => '1000',
        ])->assertSessionHasErrors('minutos_inactividad');
    }

    public function test_usuarios_no_tiene_boton_eliminar_y_auditoria_sin_edicion(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/usuarios')->assertOk()->assertDontSee('Eliminar');
        $this->actingAs($admin)->get('/auditoria')->assertOk()->assertDontSee('Editar');
    }

    public function test_auditoria_filtra_por_accion_y_usuario(): void
    {
        $admin = $this->admin();
        $otro = User::factory()->create();

        $this->actingAs($admin)->post('/usuarios', [
            'nombre' => 'Filtro',
            'usuario' => 'filtro.test',
            'rol' => 'cajero',
            'password' => 'temporal123',
            'password_confirmation' => 'temporal123',
        ]);

        $this->actingAs($admin)->get('/auditoria?accion=CREAR')
            ->assertOk()
            ->assertSee('filtro.test');

        $this->actingAs($admin)->get('/auditoria?usuario_id='.$otro->id)
            ->assertOk()
            ->assertSee('No hay registros de auditoría.');
    }

    public function test_actividad_reciente_muestra_logins_y_filtra_por_usuario(): void
    {
        $admin = $this->admin();
        $cajero = User::factory()->create(['usuario' => 'cajeroact', 'password' => 'secreta123']);

        $this->post('/login', ['usuario' => 'cajeroact', 'password' => 'secreta123'])->assertRedirect('/');
        $this->post('/logout');
        $this->post('/login', ['usuario' => 'cajeroact', 'password' => 'mal'])->assertSessionHasErrors('usuario');

        $this->actingAs($admin)->get('/auditoria/actividad')
            ->assertOk()
            ->assertSee('Actividad reciente de accesos')
            ->assertSee('cajeroact')
            ->assertSee('Correcto')
            ->assertSee('Fallido');

        $this->actingAs($admin)->get('/auditoria/actividad?usuario_id='.$admin->id)
            ->assertOk()
            ->assertSee('No hay accesos registrados.');
    }
}
