<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\EntradaStock;
use App\Models\Producto;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class EntradaService
{
    public function __construct(
        protected StockService $stock,
        protected AuditoriaService $auditoria,
        protected ConfiguracionService $configuracion
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<int, array{producto_id: int, cantidad: int, costo_unitario: string}>  $items
     */
    public function registrar(array $datos, array $items, User $usuario): EntradaStock
    {
        $agrupados = $this->agrupar($items);

        if (empty($agrupados)) {
            throw new DomainException('La entrada debe tener al menos un ítem.');
        }

        return DB::transaction(function () use ($datos, $agrupados, $usuario) {
            $productos = $this->stock->bloquearProductos(array_keys($agrupados))->keyBy('id');

            foreach ($agrupados as $id => $item) {
                $producto = $productos->get($id);

                if (! $producto) {
                    throw new DomainException("El producto con id {$id} no existe.");
                }

                if (! $producto->activo) {
                    throw new DomainException("El producto '{$producto->codigo}' está inactivo.");
                }

                if (! $producto->controla_stock) {
                    throw new DomainException("El producto '{$producto->codigo}' no controla stock.");
                }
            }

            $total = '0.00';
            $lineas = [];

            foreach ($agrupados as $id => $item) {
                $subtotal = bcmul((string) $item['cantidad'], $item['costo_unitario'], 2);
                $total = bcadd($total, $subtotal, 2);
                $lineas[$id] = ['cantidad' => $item['cantidad'], 'costo_unitario' => $item['costo_unitario'], 'subtotal' => $subtotal];
            }

            $entrada = EntradaStock::create([
                'fecha' => now(),
                'proveedor' => $datos['proveedor'] ?? null,
                'proveedor_id' => $datos['proveedor_id'] ?? null,
                'documento_referencia' => $datos['documento_referencia'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'total' => $total,
                'estado' => 'REGISTRADA',
                'user_id' => $usuario->id,
            ]);

            $actualizarPrecio = (bool) ($datos['actualizar_precio_compra'] ?? true);

            foreach ($lineas as $id => $linea) {
                $entrada->detalles()->create([
                    'producto_id' => $id,
                    'cantidad' => $linea['cantidad'],
                    'costo_unitario' => $linea['costo_unitario'],
                    'subtotal' => $linea['subtotal'],
                ]);

                $this->stock->mover($id, $linea['cantidad'], 'ENTRADA', null, 'entrada', $entrada->id);

                if ($actualizarPrecio) {
                    $producto = $productos->get($id);

                    if (bccomp($producto->precio_compra, $linea['costo_unitario'], 2) !== 0) {
                        $antes = $producto->precio_compra;
                        $producto->forceFill(['precio_compra' => $linea['costo_unitario']])->save();

                        $this->auditoria->registrar(
                            'CAMBIO_PRECIO',
                            "Cambio de precio de compra en '{$producto->codigo}': {$antes} → {$linea['costo_unitario']} (entrada {$entrada->numero()}).",
                            $producto,
                            ['precio_compra' => $antes],
                            ['precio_compra' => $linea['costo_unitario']]
                        );
                    }
                }
            }

            $this->auditoria->registrar(
                'CREAR',
                "Se registró la entrada {$entrada->numero()} por Bs. {$total}.",
                $entrada
            );

            return $entrada;
        });
    }

    public function anular(EntradaStock $entrada, string $motivo, User $usuario): EntradaStock
    {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5) {
            throw new DomainException('El motivo de anulación debe tener al menos 5 caracteres.');
        }

        return DB::transaction(function () use ($entrada, $motivo, $usuario) {
            $entrada = EntradaStock::whereKey($entrada->id)->lockForUpdate()->firstOrFail();

            if ($entrada->estado !== 'REGISTRADA') {
                throw new DomainException('La entrada ya está anulada.');
            }

            $detalles = $entrada->detalles()->with('producto')->get();
            $productos = $this->stock->bloquearProductos($detalles->pluck('producto_id')->all())->keyBy('id');

            $impedidos = [];

            foreach ($detalles as $detalle) {
                $producto = $productos->get($detalle->producto_id);

                if ($producto->stock - $detalle->cantidad < 0
                    && $this->configuracion->get('permitir_stock_negativo', '1') !== '1') {
                    $impedidos[] = "'{$producto->codigo}' (stock {$producto->stock}, a revertir {$detalle->cantidad})";
                }
            }

            if (! empty($impedidos)) {
                throw new StockInsuficienteException(
                    'No se puede anular: revertir dejaría stock negativo en: '.implode(', ', $impedidos).'.'
                );
            }

            foreach ($detalles as $detalle) {
                $this->stock->mover($detalle->producto_id, -$detalle->cantidad, 'ANULACION_ENTRADA', $motivo, 'entrada', $entrada->id);
            }

            $entrada->forceFill([
                'estado' => 'ANULADA',
                'anulada_por' => $usuario->id,
                'anulada_en' => now(),
                'motivo_anulacion' => $motivo,
            ])->save();

            $this->auditoria->registrar(
                'ANULAR',
                "Se anuló la entrada {$entrada->numero()} por Bs. {$entrada->total}. Motivo: {$motivo}.",
                $entrada
            );

            return $entrada;
        });
    }

    /**
     * Agrupa ítems repetidos sumando cantidades (se conserva el último costo).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{producto_id: int, cantidad: int, costo_unitario: string}>
     */
    protected function agrupar(array $items): array
    {
        $agrupados = [];

        foreach ($items as $item) {
            $id = (int) ($item['producto_id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $costo = number_format((float) ($item['costo_unitario'] ?? 0), 2, '.', '');

            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            if (! isset($agrupados[$id])) {
                $agrupados[$id] = ['producto_id' => $id, 'cantidad' => 0, 'costo_unitario' => $costo];
            }

            $agrupados[$id]['cantidad'] += $cantidad;
            $agrupados[$id]['costo_unitario'] = $costo;
        }

        return $agrupados;
    }
}
