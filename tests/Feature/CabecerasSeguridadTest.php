<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CabecerasSeguridadTest extends TestCase
{
    use RefreshDatabase;

    protected function csp(): string
    {
        return \App\Http\Middleware\CabecerasSeguridad::CSP;
    }

    public function test_cabeceras_en_respuesta_html(): void
    {
        $this->get('/login')->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy', $this->csp());
    }

    public function test_cabeceras_en_respuesta_json(): void
    {
        $this->getJson('/up')->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy', $this->csp());
    }

    public function test_csp_sin_unsafe_inline_ni_unsafe_eval_en_scripts(): void
    {
        $csp = $this->csp();

        preg_match('/script-src ([^;]+)/', $csp, $coincidencias);

        $this->assertNotEmpty($coincidencias);
        $this->assertSame("'self'", trim($coincidencias[1]));
    }

    public function test_csp_se_puede_desactivar_y_pasar_a_solo_reporte(): void
    {
        Config::set('seguridad.csp_activa', false);

        $this->get('/login')->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only')
            ->assertHeader('X-Frame-Options', 'DENY');

        Config::set('seguridad.csp_activa', true);
        Config::set('seguridad.csp_solo_reporte', true);

        $this->get('/login')->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeader('Content-Security-Policy-Report-Only', $this->csp());
    }
}
