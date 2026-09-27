<?php

namespace Tests\Feature;

use App\Support\ProxiesDeConfianza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EntornoProduccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_env_production_example_tiene_claves_sin_secretos(): void
    {
        $ruta = base_path('.env.production.example');
        $this->assertFileExists($ruta);

        $valores = [];

        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);

            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $partes = explode('=', $linea, 2) + [1 => ''];
            $valores[trim($partes[0])] = trim($partes[1], " \t\"'");
        }

        foreach ([
            'APP_ENV', 'APP_DEBUG', 'APP_URL', 'APP_TIMEZONE',
            'LOG_CHANNEL', 'LOG_DAILY_DAYS', 'LOG_LEVEL',
            'SESSION_DRIVER', 'SESSION_SECURE_COOKIE', 'SESSION_SAME_SITE',
            'SESSION_HTTP_ONLY', 'SESSION_ENCRYPT',
            'DB_HOST', 'CACHE_STORE', 'QUEUE_CONNECTION', 'MAIL_MAILER',
            'TRUSTED_PROXIES',
        ] as $clave) {
            $this->assertArrayHasKey($clave, $valores, "Falta la clave {$clave}.");
        }

        $this->assertSame('production', $valores['APP_ENV']);
        $this->assertSame('false', mb_strtolower($valores['APP_DEBUG']));
        $this->assertSame('database', $valores['SESSION_DRIVER']);
        $this->assertArrayNotHasKey('APP_KEY', $valores);
        $this->assertArrayNotHasKey('DB_PASSWORD', $valores);
    }

    public function test_tabla_sessions_existe(): void
    {
        $this->assertTrue(Schema::hasTable('sessions'));
    }

    /**
     * TRUSTED_PROXIES=127.0.0.1 viene de phpunit.xml: el cableado completo
     * (.env → ConfiarProxies → TrustProxies → IP en auditoría).
     */
    public function test_ip_reenviada_se_respeta_con_proxy_de_confianza(): void
    {
        $cajero = \App\Models\User::factory()->create([
            'usuario' => 'proxytest',
            'password' => 'secreta12345',
            'rol' => \App\Enums\Rol::Cajero,
        ]);

        $this->post('/login', ['usuario' => 'proxytest', 'password' => 'secreta12345'], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('auditoria', [
            'accion' => 'LOGIN',
            'user_id' => $cajero->id,
            'ip' => '203.0.113.7',
        ]);
    }

    public function test_proxies_de_confianza_lee_env(): void
    {
        $this->assertSame(['10.0.0.1', '192.168.0.0/16'], ProxiesDeConfianza::lista(' 10.0.0.1 , 192.168.0.0/16 '));
        $this->assertNull(ProxiesDeConfianza::lista(''));
        $this->assertNull(ProxiesDeConfianza::lista('  , '));
        $this->assertSame('*', ProxiesDeConfianza::lista('*'));
    }
}
