<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_nueva_responde_para_todos_los_roles(): void
    {
        foreach ([Rol::Admin, Rol::Encargado, Rol::Cajero] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'usuario' => 'u-'.$rol->value]);
            $this->actingAs($usuario)->get('/ventas/nueva')->assertOk();
        }
    }

    public function test_post_de_venta_crea_la_venta_completa(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $respuesta = $this->actingAs($cajero)->post('/ventas', [
            'token' => (string) Str::uuid(),
            'metodo_pago' => 'EFECTIVO',
            'monto_recibido' => '20.00',
            'items' => [['producto_id' => $a->id, 'cantidad' => 1]],
        ]);

        $venta = Venta::latest('id')->first();
        $respuesta->assertRedirect(route('ventas.ticket', $venta));
        $this->assertSame('10.00', $venta->total);
        $this->assertSame('10.00', $venta->cambio);
        $this->assertSame(-1, $a->fresh()->stock);
    }

    public function test_cajero_que_envia_descuento_recibe_error(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->actingAs($cajero)->post('/ventas', [
            'token' => (string) Str::uuid(),
            'metodo_pago' => 'QR',
            'descuento' => '1.00',
            'items' => [['producto_id' => $a->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('venta');

        $this->assertSame(0, Venta::count());
    }

    public function test_reenviar_mismo_token_no_duplica(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);
        $token = (string) Str::uuid();
        $datos = [
            'token' => $token,
            'metodo_pago' => 'QR',
            'items' => [['producto_id' => $a->id, 'cantidad' => 1]],
        ];

        $this->actingAs($cajero)->post('/ventas', $datos);
        $this->actingAs($cajero)->post('/ventas', $datos);

        $this->assertSame(1, Venta::where('token', $token)->count());
        $this->assertSame(-1, $a->fresh()->stock);
    }

    public function test_cajero_no_ve_ticket_de_otro_y_encargado_si(): void
    {
        $cajero1 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'cajero1']);
        $cajero2 = User::factory()->create(['rol' => Rol::Cajero, 'usuario' => 'cajero2']);
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->actingAs($cajero1);
        $venta = app(\App\Services\VentaService::class)->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $cajero1
        );

        $this->actingAs($cajero2)->get("/ventas/{$venta->id}/ticket")->assertForbidden();
        $this->actingAs($cajero1)->get("/ventas/{$venta->id}/ticket")->assertOk();
        $this->actingAs($encargado)->get("/ventas/{$venta->id}/ticket")->assertOk();
    }

    public function test_ticket_muestra_leyenda_y_anulada(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->actingAs($encargado);
        $venta = app(\App\Services\VentaService::class)->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) Str::uuid(), 'metodo_pago' => 'QR'],
            $encargado
        );

        $this->actingAs($encargado)->get("/ventas/{$venta->id}/ticket")
            ->assertOk()
            ->assertSee('Documento sin valor fiscal');

        app(\App\Services\VentaService::class)->anular($venta, 'Prueba de ticket', $encargado);

        $this->actingAs($encargado)->get("/ventas/{$venta->id}/ticket")
            ->assertOk()
            ->assertSee('ANULADA');
    }

    public function test_ticket_imprime_solo_si_esta_configurado(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->actingAs($encargado);
        $venta = app(\App\Services\VentaService::class)->registrar(
            [['producto_id' => $a->id, 'cantidad' => 1]],
            ['token' => (string) \Illuminate\Support\Str::uuid(), 'metodo_pago' => 'QR'],
            $encargado
        );

        $this->actingAs($encargado)->get("/ventas/{$venta->id}/ticket")
            ->assertOk()
            ->assertDontSee("window.addEventListener('load', () => window.print());", false);

        app(\App\Services\ConfiguracionService::class)->set('imprimir_automatico', '1');

        $this->actingAs($encargado)->get("/ventas/{$venta->id}/ticket")
            ->assertOk()
            ->assertSee("window.addEventListener('load', () => window.print());", false);
    }

    public function test_efectivo_con_recibido_menor_da_error_claro(): void
    {
        $cajero = User::factory()->create(['rol' => Rol::Cajero]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->actingAs($cajero)->post('/ventas', [
            'token' => (string) \Illuminate\Support\Str::uuid(),
            'metodo_pago' => 'EFECTIVO',
            'monto_recibido' => '5.00',
            'items' => [['producto_id' => $a->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('venta');

        $this->assertSame(0, \App\Models\Venta::count());
    }

    public function test_ticket_muestra_datos_completos(): void
    {
        $encargado = User::factory()->create(['rol' => Rol::Encargado]);
        $a = Producto::factory()->create(['precio_venta' => '10.00']);

        $this->actingAs($encargado);
        $venta = app(\App\Services\VentaService::class)->registrar(
            [['producto_id' => $a->id, 'cantidad' => 2]],
            ['token' => (string) \Illuminate\Support\Str::uuid(), 'metodo_pago' => 'EFECTIVO', 'monto_recibido' => '25.00', 'cliente_nombre' => 'Juan'],
            $encargado
        );

        $this->actingAs($encargado)->get("/ventas/{$venta->id}/ticket")
            ->assertOk()
            ->assertSee($venta->numero())
            ->assertSee('EFECTIVO')
            ->assertSee('Juan')
            ->assertSee('Bs. 20,00')
            ->assertSee('Bs. 5,00');
    }
}
