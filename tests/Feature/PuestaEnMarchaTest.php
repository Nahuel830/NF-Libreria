<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PuestaEnMarchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_estado_detecta_demo_y_backup_antiguo(): void
    {
        User::factory()->create(['usuario' => 'encargado', 'rol' => Rol::Encargado]);
        app(\App\Services\ConfiguracionService::class)->set('ultimo_backup_fecha', now()->subDays(2)->toDateTimeString());
        app(\App\Services\ConfiguracionService::class)->set('ultimo_backup_resultado', 'ok: viejo');

        $this->artisan('sistema:estado')
            ->expectsOutputToContain('datos demo')
            ->expectsOutputToContain('ADVERTENCIA')
            ->assertSuccessful();
    }

    public function test_limpiar_demo_no_corre_en_produccion_sin_force(): void
    {
        app()->instance('env', 'production');

        $this->artisan('sistema:limpiar-demo')
            ->expectsOutputToContain('producción')
            ->assertFailed();
    }

    public function test_conteo_fisico_ajusta_y_no_crea_si_igual(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $producto = Producto::factory()->create();
        DB::table('productos')->where('id', $producto->id)->update(['stock' => 10]);

        $this->actingAs($encargado)->post('/inventario/conteo', [
            'motivo' => 'Conteo inicial',
            'conteos' => [$producto->id => 14],
        ])->assertRedirect();

        $this->assertSame(14, $producto->fresh()->stock);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id, 'tipo' => 'AJUSTE_POSITIVO', 'cantidad' => 4,
        ]);

        $this->actingAs($encargado)->post('/inventario/conteo', [
            'conteos' => [$producto->id => 14],
        ])->assertRedirect();

        $this->assertSame(1, \App\Models\MovimientoStock::where('producto_id', $producto->id)->count());
    }

    public function test_hoja_de_conteo_descarga_csv(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);

        $this->actingAs($encargado)->get('/inventario/conteo/hoja')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('cantidad_contada');
    }
}
