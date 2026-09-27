<?php

namespace App\Services;

use App\Enums\MetodoPago;
use App\Models\User;
use App\Models\Venta;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VentaService
{
    public function __construct(
        protected StockService $stock,
        protected AuditoriaService $auditoria,
        protected CajaService $caja,
        protected ConfiguracionService $configuracion
    ) {}

    /**
     * @param  array<int, array{producto_id: int, cantidad: int}>  $items
     * @param  array<string, mixed>  $datos
     */
    public function registrar(array $items, array $datos, User $usuario): Venta
    {
        $token = trim((string) ($datos['token'] ?? ''));

        if ($token === '') {
            throw new DomainException('La venta requiere un token.');
        }

        $existente = Venta::where('token', $token)->first();

        if ($existente) {
            return $existente;
        }

        $metodo = MetodoPago::tryFrom((string) ($datos['metodo_pago'] ?? ''));

        if (! $metodo) {
            throw new DomainException('Método de pago inválido.');
        }

        $agrupados = $this->agrupar($items);

        if (empty($agrupados)) {
            throw new DomainException('La venta debe tener al menos un ítem.');
        }

        $clienteId = isset($datos['cliente_id']) && trim((string) $datos['cliente_id']) !== ''
            ? (int) $datos['cliente_id']
            : null;
        $clienteNombre = isset($datos['cliente_nombre']) ? trim((string) $datos['cliente_nombre']) : '';
        $clienteNombre = $clienteNombre === '' ? null : mb_substr($clienteNombre, 0, 150);

        if ($clienteId !== null) {
            $cliente = \App\Models\Cliente::whereKey($clienteId)->first();

            if (! $cliente) {
                throw new DomainException('El cliente indicado no existe.');
            }

            if (! $cliente->activo) {
                throw new DomainException('El cliente indicado está inactivo.');
            }

            $clienteNombre = $cliente->nombre;
        }

        $descuento = $this->normalizarDecimal($datos['descuento'] ?? '0');

        if (bccomp($descuento, '0', 2) > 0) {
            Gate::forUser($usuario)->authorize('aplicar-descuentos');
        }

        $cajaId = null;

        if ($this->configuracion->get('exigir_caja_abierta', '1') === '1') {
            $caja = \App\Models\Caja::abiertaDe($usuario);

            if (! $caja) {
                throw new DomainException('Debes abrir tu caja antes de vender.');
            }

            $cajaId = $caja->id;
        } else {
            $cajaId = \App\Models\Caja::abiertaDe($usuario)?->id;
        }

        return DB::transaction(function () use ($agrupados, $datos, $descuento, $metodo, $token, $usuario, $clienteId, $clienteNombre, $cajaId) {
            $productos = $this->stock->bloquearProductos(array_keys($agrupados))->keyBy('id');

            $lineas = [];
            $subtotal = '0.00';

            foreach ($agrupados as $id => $cantidad) {
                $producto = $productos->get($id);

                if (! $producto) {
                    throw new DomainException("El producto con id {$id} no existe.");
                }

                if (! $producto->activo) {
                    throw new DomainException("El producto '{$producto->codigo}' está inactivo.");
                }

                $precio = number_format((float) $producto->precio_venta, 2, '.', '');
                $subLinea = bcmul((string) $cantidad, $precio, 2);
                $subtotal = bcadd($subtotal, $subLinea, 2);

                $lineas[$id] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subLinea,
                ];
            }

            if (bccomp($descuento, $subtotal, 2) > 0) {
                throw new DomainException('El descuento no puede superar el subtotal.');
            }

            $total = bcsub($subtotal, $descuento, 2);

            $recibido = null;
            $cambio = null;

            if ($metodo === MetodoPago::Efectivo && isset($datos['monto_recibido']) && trim((string) $datos['monto_recibido']) !== '') {
                $recibido = $this->normalizarDecimal($datos['monto_recibido']);

                if (bccomp($recibido, $total, 2) < 0) {
                    throw new DomainException('El monto recibido es menor al total.');
                }

                $cambio = bcsub($recibido, $total, 2);
            }

            $venta = Venta::create([
                'token' => $token,
                'fecha' => now(),
                'user_id' => $usuario->id,
                'caja_id' => $cajaId,
                'cliente_id' => $clienteId,
                'cliente_nombre' => $clienteNombre,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'metodo_pago' => $metodo->value,
                'monto_recibido' => $recibido,
                'cambio' => $cambio,
                'estado' => 'COMPLETADA',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            foreach ($lineas as $id => $linea) {
                $venta->detalles()->create([
                    'producto_id' => $id,
                    'codigo_producto' => $linea['producto']->codigo,
                    'nombre_producto' => $linea['producto']->nombre,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio'],
                    'subtotal' => $linea['subtotal'],
                ]);

                $this->stock->mover($id, -$linea['cantidad'], 'VENTA', null, 'venta', $venta->id);
            }

            if (bccomp($descuento, '0', 2) > 0) {
                $this->auditoria->registrar(
                    'CREAR',
                    "Venta {$venta->numero()} con descuento de Bs. {$descuento}.",
                    $venta,
                    null,
                    ['descuento' => $descuento, 'total' => $total]
                );
            }

            return $venta;
        });
    }

    public function anular(Venta $venta, string $motivo, User $usuario): Venta
    {
        Gate::forUser($usuario)->authorize('anular-ventas');

        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5) {
            throw new DomainException('El motivo de anulación debe tener al menos 5 caracteres.');
        }

        return DB::transaction(function () use ($venta, $motivo, $usuario) {
            $venta = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();

            if ($venta->estado !== 'COMPLETADA') {
                throw new DomainException('La venta ya está anulada.');
            }

            $detalles = $venta->detalles()->get();
            $this->stock->bloquearProductos($detalles->pluck('producto_id')->all());

            foreach ($detalles as $detalle) {
                $this->stock->mover(
                    $detalle->producto_id,
                    $detalle->cantidad,
                    'ANULACION_VENTA',
                    $motivo,
                    'venta',
                    $venta->id,
                    true
                );
            }

            $venta->forceFill([
                'estado' => 'ANULADA',
                'anulada_por' => $usuario->id,
                'anulada_en' => now(),
                'motivo_anulacion' => $motivo,
            ])->save();

            $this->auditoria->registrar(
                'ANULAR',
                "Se anuló la venta {$venta->numero()} por Bs. {$venta->total} ({$detalles->count()} ítems). Motivo: {$motivo}.",
                $venta,
                ['estado' => 'COMPLETADA'],
                ['estado' => 'ANULADA', 'motivo' => $motivo]
            );

            return $venta;
        });
    }

    /**
     * Calcula subtotal, descuento y total para la vista previa (mismos cálculos).
     *
     * @param  array<int, array{producto_id: int, cantidad: int}>  $items
     * @return array{subtotal: string, descuento: string, total: string}
     */
    public function calcularTotales(array $items, mixed $descuento = '0'): array
    {
        $subtotal = '0.00';

        foreach ($this->agrupar($items) as $id => $cantidad) {
            $precio = \App\Models\Producto::whereKey($id)->value('precio_venta');

            if ($precio === null) {
                continue;
            }

            $subtotal = bcadd($subtotal, bcmul((string) $cantidad, number_format((float) $precio, 2, '.', ''), 2), 2);
        }

        $descuento = $this->normalizarDecimal($descuento);

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => bcsub($subtotal, $descuento, 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, int>
     */
    protected function agrupar(array $items): array
    {
        $agrupados = [];

        foreach ($items as $item) {
            $id = (int) ($item['producto_id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            $agrupados[$id] = ($agrupados[$id] ?? 0) + $cantidad;
        }

        return $agrupados;
    }

    protected function normalizarDecimal(mixed $valor): string
    {
        $valor = trim((string) $valor);

        if ($valor === '' || ! is_numeric($valor) || (float) $valor < 0) {
            throw new DomainException('Monto inválido.');
        }

        return number_format((float) $valor, 2, '.', '');
    }
}
