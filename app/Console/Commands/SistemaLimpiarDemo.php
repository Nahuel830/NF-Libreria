<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SistemaLimpiarDemo extends Command
{
    protected $signature = 'sistema:limpiar-demo {--force : Confirma el borrado en producción escribiendo BORRAR DEMO}';

    protected $description = 'Elimina datos demo dejando configuración, categorías y el admin. Hace backup antes.';

    public function handle(): int
    {
        if (app()->environment('production') && $this->option('force') !== true) {
            $this->error('En producción usa --force y escribe BORRAR DEMO para confirmar.');

            return self::FAILURE;
        }

        if (app()->environment('production')) {
            $confirmacion = $this->ask('Escribe BORRAR DEMO para confirmar');

            if ($confirmacion !== 'BORRAR DEMO') {
                $this->error('Confirmación incorrecta.');

                return self::FAILURE;
            }
        }

        $this->info('Haciendo backup previo...');
        $backup = base_path('scripts/backup.ps1');

        if (is_file($backup)) {
            exec('powershell -NoProfile -ExecutionPolicy Bypass -File "'.$backup.'"', $salida, $codigo);

            if ($codigo !== 0) {
                $this->error('El backup previo falló. No se continúa.');

                return self::FAILURE;
            }
        } else {
            $this->warn('No se encontró scripts/backup.ps1; se continúa sin backup.');
        }

        DB::transaction(function (): void {
            $idsVentas = DB::table('ventas')->pluck('id');

            DB::table('detalle_devoluciones')->whereIn(
                'devolucion_id', DB::table('devoluciones')->whereIn('venta_id', $idsVentas)->pluck('id')
            )->delete();
            DB::table('devoluciones')->whereIn('venta_id', $idsVentas)->delete();
            DB::table('detalle_ventas')->whereIn('venta_id', $idsVentas)->delete();
            DB::table('ventas')->whereIn('id', $idsVentas)->delete();

            DB::table('detalle_entradas')->delete();
            DB::table('entradas_stock')->delete();
            DB::table('movimientos_stock')->delete();
            DB::table('movimientos_caja')->delete();
            DB::table('cajas')->delete();

            DB::table('productos')->whereIn('codigo', [
                'CUA-001', 'CUA-002', 'CUA-003', 'LAP-001', 'LAP-002', 'LAP-003', 'LAP-004',
                'LPI-001', 'LPI-002', 'LPI-003', 'LPI-004', 'MES-001', 'MES-002', 'MES-003',
                'MES-004', 'MOF-001', 'MOF-002', 'MOF-003', 'MOF-004', 'LIB-001', 'LIB-002',
                'CAR-001', 'CAR-002', 'HPA-001', 'HPA-002', 'ACC-001', 'ACC-002',
                'FOT-001', 'FOT-002', 'OTR-001',
            ])->delete();

            DB::table('users')->whereIn('usuario', ['encargado', 'cajero1'])->delete();
            DB::table('auditoria')->delete();
        });

        $this->info('Datos demo eliminados. Quedan configuración, categorías y el admin.');

        return self::SUCCESS;
    }
}
