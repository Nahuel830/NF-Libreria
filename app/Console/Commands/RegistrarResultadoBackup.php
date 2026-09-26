<?php

namespace App\Console\Commands;

use App\Services\ConfiguracionService;
use Illuminate\Console\Command;

class RegistrarResultadoBackup extends Command
{
    protected $signature = 'backup:registrar-resultado {estado} {mensaje?}';

    protected $description = 'Registra en configuración la fecha y el resultado del último backup.';

    public function handle(ConfiguracionService $configuracion): int
    {
        $estado = $this->argument('estado');
        $mensaje = trim((string) $this->argument('mensaje'));

        if (! in_array($estado, ['ok', 'error'], true)) {
            $this->error("Estado inválido: {$estado}. Usa ok o error.");

            return self::FAILURE;
        }

        $configuracion->set('ultimo_backup_fecha', now()->toDateTimeString());
        $configuracion->set('ultimo_backup_resultado', $mensaje !== '' ? "{$estado}: {$mensaje}" : $estado);

        $this->info('Resultado de backup registrado.');

        return self::SUCCESS;
    }
}
