<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_y_encargado_pueden_crear_editar_y_desactivar(): void
    {
        foreach ([Rol::Admin, Rol::Encargado] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'usuario' => 'user-'.$rol->value]);

            $this->actingAs($usuario)->post('/categorias', [
                'nombre' => 'Categoria '.$rol->value,
                'descripcion' => 'Descripción.',
            ])->assertRedirect(route('categorias.index'));

            $categoria = Categoria::where('nombre', 'Categoria '.$rol->value)->first();
            $this->assertNotNull($categoria);
            $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR', 'entidad_id' => $categoria->id]);

            $this->actingAs($usuario)->put("/categorias/{$categoria->id}", [
                'nombre' => 'Categoria '.$rol->value.' editada',
                'descripcion' => 'Otra.',
            ])->assertRedirect(route('categorias.index'));
            $this->assertDatabaseHas('auditoria', ['accion' => 'EDITAR', 'entidad_id' => $categoria->id]);

            $this->actingAs($usuario)->patch("/categorias/{$categoria->id}/estado")
                ->assertRedirect(route('categorias.index'));
            $this->assertFalse($categoria->fresh()->activo);
            $this->assertDatabaseHas('auditoria', ['accion' => 'DESACTIVAR', 'entidad_id' => $categoria->id]);
        }
    }

    public function test_cajero_recibe_403(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);

        $this->actingAs($cajero)->get('/categorias')->assertForbidden();
        $this->actingAs($cajero)->post('/categorias', ['nombre' => 'X'])->assertForbidden();
    }

    public function test_no_se_permiten_nombres_duplicados_sin_distinguir_mayusculas(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Admin]);
        Categoria::factory()->create(['nombre' => 'Cuadernos']);

        $this->actingAs($admin)->post('/categorias', ['nombre' => 'cuadernos'])
            ->assertSessionHasErrors('nombre');

        $this->assertEquals(1, Categoria::whereRaw('lower(nombre) = ?', ['cuadernos'])->count());
    }
}
