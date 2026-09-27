<?php

namespace App\Console\Commands;

use App\Services\ConfiguracionService;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SistemaEstado extends Command
{
    protected $signature = 'sistema:estado';

    protected $description = 'Muestra el estado del sistema: entorno, datos, backup y consistencia.';

    public function handle(ConfiguracionService $configuracion, StockService $stock): int
    {
        $this->info('Entorno: '.app()->environment());
        $this->info('APP_DEBUG: '.(config('app.debug') ? 'true' : 'false'));
        $this->info('Versión: '.config('app.version', '1.0.0-dev'));
        $this->info('BD: '.config('database.default').' / '.config('database.connections.pgsql.database'));

        $tablas = ['users' => 'Usuarios', 'productos' => 'Productos', 'ventas' => 'Ventas'];

        foreach ($tablas as $tabla => $etiqueta) {
            $cantidad = Schema::hasTable($tabla) ? DB::table($tabla)->count() : 0;
            $this->info("{$etiqueta}: {$cantidad}");
        }

        $demo = $this->hayDemo();
        $this->line($demo ? 'ADVERTENCIA: hay datos demo (encargado/cajero1 o productos demo).' : 'OK: sin datos demo.');

        $fecha = $configuracion->get('ultimo_backup_fecha');
        $resultado = $configuracion->get('ultimo_backup_resultado');

        if ($fecha === null) {
            $this->line('ADVERTENCIA: nunca se registró un backup.');
        } else {
            $horas = abs(now()->diffInHours($fecha));
            $this->line("Último backup: {$fecha} ({$resultado}). Hace {$horas} h."
                .($horas > 24 || str_starts_with((string) $resultado, 'error') ? ' ADVERTENCIA: revisar backups.' : ' OK.'));
        }

        $diferencias = $stock->verificarConsistencia();

        if ($diferencias->isEmpty()) {
            $this->info('stock:verificar: OK, sin diferencias.');
        } else {
            $this->error("stock:verificar: ERROR, {$diferencias->count()} productos con diferencias.");
        }

        $libre = disk_free_space(base_path());
        $this->line('Espacio libre en disco: '.$this->humano($libre));

        $carpeta = getenv('CARPETA_BACKUPS') ?: 'D:\Backups\NF-Libreria';

        if (is_dir($carpeta)) {
            $this->line('Espacio libre en backups: '.$this->humano(disk_free_space($carpeta)));
        }

        return self::SUCCESS;
    }

    protected function hayDemo(): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('productos')) {
            return false;
        }

        if (DB::table('users')->whereIn('usuario', ['encargado', 'cajero1'])->exists()) {
            return true;
        }

        return DB::table('productos')->whereIn('codigo', [
            'CUA-001', 'LAP-001', 'LPI-001', 'MES-001', 'MOF-001', 'LIB-001',
            'CAR-001', 'HPA-001', 'ACC-001', 'FOT-001', 'OTR-001',
        ])->exists();
    }

    protected function humano(int|float $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$unidades[$i];
    }
}
