<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class StockService
{
    public function __construct(
        protected ConfiguracionService $configuracion,
        protected AuditoriaService $auditoria
    ) {}

    public function mover(
        int $productoId,
        int $cantidad,
        string $tipo,
        ?string $motivo = null,
        ?string $referenciaTipo = null,
        int|string|null $referenciaId = null,
        ?bool $permitirNegativo = null
    ): ?MovimientoStock {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('StockService::mover debe ejecutarse dentro de una transacción.');
        }

        if ($cantidad === 0) {
            throw new InvalidArgumentException('La cantidad del movimiento no puede ser 0.');
        }

        $producto = Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();

        if (! $producto->controla_stock) {
            return null;
        }

        $anterior = $producto->stock;
        $nuevo = $anterior + $cantidad;

        $permitido = $permitirNegativo ?? ($this->configuracion->get('permitir_stock_negativo', '1') === '1');

        if ($nuevo < 0 && ! $permitido) {
            throw new StockInsuficienteException(
                "Stock insuficiente para {$producto->nombre}: disponible {$anterior}, solicitado ".abs($cantidad)
            );
        }

        $producto->forceFill(['stock' => $nuevo])->save();

        return MovimientoStock::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_anterior' => $anterior,
            'stock_nuevo' => $nuevo,
            'user_id' => auth()->id(),
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
            'motivo' => $motivo,
        ]);
    }

    /**
     * Bloquea varios productos ordenados por id para evitar deadlocks.
     *
     * @param  int[]  $ids
     */
    public function bloquearProductos(array $ids): Collection
    {
        $ids = collect($ids)->unique()->sort()->values()->all();

        return Producto::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
    }

    public function ajustar(Producto $producto, int $stockReal, string $motivo): ?MovimientoStock
    {
        return DB::transaction(function () use ($producto, $stockReal, $motivo) {
            $actual = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $diferencia = $stockReal - $actual->stock;

            if ($diferencia === 0) {
                return null;
            }

            $movimiento = $this->mover(
                $actual->id,
                $diferencia,
                $diferencia > 0 ? 'AJUSTE_POSITIVO' : 'AJUSTE_NEGATIVO',
                $motivo
            );

            $this->auditoria->registrar(
                'AJUSTE_STOCK',
                "Ajuste de stock en '{$actual->codigo}': {$actual->stock} → {$stockReal}. Motivo: {$motivo}.",
                $actual,
                ['stock' => $actual->stock],
                ['stock' => $stockReal, 'motivo' => $motivo]
            );

            return $movimiento;
        });
    }

    /**
     * Devuelve los productos cuyo stock no coincide con la suma de sus movimientos.
     */
    public function verificarConsistencia(): Collection
    {
        return Producto::query()
            ->leftJoin('movimientos_stock as m', 'm.producto_id', '=', 'productos.id')
            ->groupBy('productos.id')
            ->havingRaw('productos.stock != COALESCE(SUM(m.cantidad), 0)')
            ->select('productos.id', 'productos.codigo', 'productos.nombre', 'productos.stock')
            ->selectRaw('COALESCE(SUM(m.cantidad), 0) as suma_movimientos')
            ->get();
    }
}
