@extends('layouts.app')

@section('titulo', 'Venta ' . $venta->numero())

@section('contenido')
    <x-page-header :titulo="'Venta ' . $venta->numero()" :migas="['Ventas' => null, 'Historial' => route('ventas.index'), $venta->numero() => null]">
        <a href="{{ route('ventas.ticket', $venta) }}" class="btn btn-outline-primary"><i class="bi bi-printer"></i> Reimprimir ticket</a>
        @if ($venta->estado === 'COMPLETADA')
            @can('anular-ventas')
                <a href="{{ route('devoluciones.crear', $venta) }}" class="btn btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i> Devolver</a>
                <form method="POST" action="{{ route('ventas.anular', $venta) }}" class="d-inline"
                    data-confirm="Esta acción devolverá el stock y no se puede deshacer."
                    data-motivo
                    data-titulo-confirm="Anular venta {{ $venta->numero() }}"
                    data-texto-confirm="Confirmar anulación">
                    @csrf
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Anular venta</button>
                </form>
            @endcan
        @endif
    </x-page-header>

    <x-card>
        <dl class="row mb-0">
            <dt class="col-sm-3">Fecha y hora</dt>
            <dd class="col-sm-9">{{ $venta->fecha->format('d/m/Y H:i') }}</dd>

            <dt class="col-sm-3">Cajero</dt>
            <dd class="col-sm-9">{{ $venta->usuario->nombre }} ({{ $venta->usuario->usuario }})</dd>

            <dt class="col-sm-3">Cliente</dt>
            <dd class="col-sm-9">{{ $venta->cliente_nombre ?? '—' }}</dd>

            <dt class="col-sm-3">Método de pago</dt>
            <dd class="col-sm-9">{{ $venta->metodo_pago }}</dd>

            @if ($venta->metodo_pago === 'EFECTIVO')
                <dt class="col-sm-3">Recibido</dt>
                <dd class="col-sm-9"><x-dinero :monto="$venta->monto_recibido" /></dd>

                <dt class="col-sm-3">Cambio</dt>
                <dd class="col-sm-9"><x-dinero :monto="$venta->cambio" /></dd>
            @endif

            <dt class="col-sm-3">Observaciones</dt>
            <dd class="col-sm-9">{{ $venta->observaciones ?? '—' }}</dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-estado :estado="$venta->estado" />
                @if ($venta->es_contingencia)
                    <span class="badge bg-warning text-dark">Contingencia</span>
                @endif
            </dd>

            @if ($venta->es_contingencia)
                <dt class="col-sm-3">Fecha real</dt>
                <dd class="col-sm-9">{{ $venta->fecha_contingencia->format('d/m/Y H:i') }} (registrada en papel durante un corte)</dd>
            @endif

            @if ($venta->estado === 'ANULADA')
                <dt class="col-sm-3">Anulada por</dt>
                <dd class="col-sm-9">{{ $venta->anuladaPor?->usuario ?? '—' }}</dd>

                <dt class="col-sm-3">Anulada en</dt>
                <dd class="col-sm-9">{{ $venta->anulada_en?->format('d/m/Y H:i') ?? '—' }}</dd>

                <dt class="col-sm-3">Motivo</dt>
                <dd class="col-sm-9">{{ $venta->motivo_anulacion }}</dd>
            @endif
        </dl>
    </x-card>

    <x-card titulo="Ítems">
        <div class="table-responsive">
            <table class="table table-striped tabla-nf">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th class="monto">Precio unitario</th>
                        <th class="monto">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($venta->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->codigo_producto }}</td>
                            <td>{{ $detalle->nombre_producto }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td class="monto"><x-dinero :monto="$detalle->precio_unitario" /></td>
                            <td class="monto"><x-dinero :monto="$detalle->subtotal" /></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Subtotal</th>
                        <th class="monto"><x-dinero :monto="$venta->subtotal" /></th>
                    </tr>
                    @if ((float) $venta->descuento > 0)
                        <tr>
                            <th colspan="4" class="text-end">Descuento</th>
                            <th class="monto"><x-dinero :monto="$venta->descuento" /></th>
                        </tr>
                    @endif
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="monto"><x-dinero :monto="$venta->total" /></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>

    <a href="{{ route('ventas.index') }}" class="btn btn-secondary">Volver</a>

    @if ($venta->devoluciones->isNotEmpty())
        <x-card titulo="Devoluciones">
            <ul class="mb-0">
                @foreach ($venta->devoluciones as $devolucion)
                    <li>
                        <a href="{{ route('devoluciones.ticket', $devolucion) }}">{{ $devolucion->numero() }}</a>
                        — <x-dinero :monto="$devolucion->total_devuelto" /> — {{ $devolucion->motivo }}
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
@endsection
