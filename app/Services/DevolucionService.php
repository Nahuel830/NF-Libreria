<?php

namespace App\Services;

use App\Enums\MetodoPago;
use App\Models\Devolucion;
use App\Models\User;
use App\Models\Venta;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DevolucionService
{
    public function __construct(
        protected StockService $stock,
        protected AuditoriaService $auditoria,
        protected CajaService $caja
    ) {}

    /**
     * @param  array<int, array{detalle_venta_id: int, cantidad: int}>  $items
     */
    public function devolver(Venta $venta, array $items, string $motivo, string $metodoReembolso, User $usuario): Devolucion
    {
        Gate::forUser($usuario)->authorize('anular-ventas');

        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5) {
            throw new DomainException('El motivo de la devolución debe tener al menos 5 caracteres.');
        }

        $metodo = MetodoPago::tryFrom($metodoReembolso);

        if (! $metodo) {
            throw new DomainException('Método de reembolso inválido.');
        }

        return DB::transaction(function () use ($venta, $items, $motivo, $metodo, $usuario) {
            $venta = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();

            if ($venta->estado !== 'COMPLETADA') {
                throw new DomainException('Solo se puede devolver de una venta completada.');
            }

            $detalles = $venta->detalles()->lockForUpdate()->get()->keyBy('id');
            $lineas = [];
            $total = '0.00';

            foreach ($items as $item) {
                $detalle = $detalles->get((int) ($item['detalle_venta_id'] ?? 0));
                $cantidad = (int) ($item['cantidad'] ?? 0);

                if (! $detalle || $cantidad <= 0) {
                    continue;
                }

                $devuelto = $detalle->devoluciones()->sum('cantidad');
                $disponible = $detalle->cantidad - $devuelto;

                if ($cantidad > $disponible) {
                    throw new DomainException(
                        "No se pueden devolver {$cantidad} de '{$detalle->nombre_producto}': quedan {$disponible} por devolver."
                    );
                }

                $subtotal = bcmul((string) $cantidad, $detalle->precio_unitario, 2);
                $total = bcadd($total, $subtotal, 2);
                $lineas[] = ['detalle' => $detalle, 'cantidad' => $cantidad, 'subtotal' => $subtotal];
            }

            if (empty($lineas)) {
                throw new DomainException('La devolución debe tener al menos un ítem válido.');
            }

            $devolucion = Devolucion::create([
                'venta_id' => $venta->id,
                'user_id' => $usuario->id,
                'fecha' => now(),
                'motivo' => $motivo,
                'total_devuelto' => $total,
                'metodo_reembolso' => $metodo->value,
            ]);

            $productosIds = [];

            foreach ($lineas as $linea) {
                $devolucion->detalles()->create([
                    'detalle_venta_id' => $linea['detalle']->id,
                    'producto_id' => $linea['detalle']->producto_id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['detalle']->precio_unitario,
                    'subtotal' => $linea['subtotal'],
                ]);

                $productosIds[] = $linea['detalle']->producto_id;
            }

            $productos = $this->stock->bloquearProductos($productosIds)->keyBy('id');

            foreach ($lineas as $linea) {
                $producto = $productos->get($linea['detalle']->producto_id);

                if ($producto && $producto->controla_stock) {
                    $this->stock->mover(
                        $producto->id,
                        $linea['cantidad'],
                        'DEVOLUCION',
                        $motivo,
                        'devolucion',
                        $devolucion->id,
                        true
                    );
                }
            }

            if ($metodo === MetodoPago::Efectivo) {
                $this->egresoCaja($venta, $total, $motivo, $usuario);
            }

            $this->auditoria->registrar(
                'CREAR',
                "Devolución {$devolucion->numero()} de la venta {$venta->numero()} por Bs. {$total}. Motivo: {$motivo}.",
                $devolucion
            );

            return $devolucion;
        });
    }

    protected function egresoCaja(Venta $venta, string $total, string $motivo, User $usuario): void
    {
        $caja = $venta->caja && $venta->caja->estado === 'ABIERTA'
            ? $venta->caja
            : \App\Models\Caja::abiertaDe($usuario);

        if (! $caja) {
            return;
        }

        $this->caja->movimiento($caja, 'EGRESO', $total, "Devolución {$venta->numero()}: {$motivo}", $usuario);
    }
}
