<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class VerificarStock extends Command
{
    protected $signature = 'stock:verificar';

    protected $description = 'Verifica que el stock de cada producto coincida con la suma de sus movimientos.';

    public function handle(StockService $stock): int
    {
        $diferencias = $stock->verificarConsistencia();

        if ($diferencias->isEmpty()) {
            $this->info('Stock consistente: sin diferencias.');

            return self::SUCCESS;
        }

        $this->error('Se encontraron diferencias de stock:');
        $this->table(
            ['ID', 'Código', 'Nombre', 'Stock', 'Suma movimientos'],
            $diferencias->map(fn ($p) => [$p->id, $p->codigo, $p->nombre, $p->stock, $p->suma_movimientos])->all()
        );

        return self::FAILURE;
    }
}
