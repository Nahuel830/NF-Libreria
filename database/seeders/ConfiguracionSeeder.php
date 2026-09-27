<?php

namespace Database\Seeders;

use App\Services\ConfiguracionService;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    protected array $valores = [
        'nombre_negocio' => 'NF Librería',
        'direccion' => '',
        'telefono' => '',
        'mensaje_ticket' => '¡Gracias por su compra!',
        'permitir_stock_negativo' => '1',
        'minutos_inactividad' => '60',
        'imprimir_automatico' => '0',
        'exigir_caja_abierta' => '1',
        'ips_cajero' => '',
        'totp_obligatorio_admin' => '1',
        'totp_obligatorio_encargado' => '0',
        'forzar_cambio_password' => '0',
    ];

    public function run(): void
    {
        $servicio = app(ConfiguracionService::class);

        foreach ($this->valores as $clave => $valor) {
            $servicio->set($clave, $valor);
        }
    }
}
