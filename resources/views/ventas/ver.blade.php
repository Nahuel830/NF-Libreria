@extends('layouts.app')

@section('titulo', 'Venta ' . $venta->numero())

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Venta {{ $venta->numero() }}</h1>
        <div>
            <a href="{{ route('ventas.ticket', $venta) }}" class="btn btn-outline-primary">Reimprimir ticket</a>
            @if ($venta->estado === 'COMPLETADA')
                @can('anular-ventas')
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-anular">
                        Anular venta
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <dl class="row">
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
            <dd class="col-sm-9">{{ bs($venta->monto_recibido) }}</dd>

            <dt class="col-sm-3">Cambio</dt>
            <dd class="col-sm-9">{{ bs($venta->cambio) }}</dd>
        @endif

        <dt class="col-sm-3">Observaciones</dt>
        <dd class="col-sm-9">{{ $venta->observaciones ?? '—' }}</dd>

        <dt class="col-sm-3">Estado</dt>
        <dd class="col-sm-9">
            @if ($venta->estado === 'COMPLETADA')
                <span class="badge bg-success">Completada</span>
            @else
                <span class="badge bg-danger">Anulada</span>
            @endif
        </dd>

        @if ($venta->estado === 'ANULADA')
            <dt class="col-sm-3">Anulada por</dt>
            <dd class="col-sm-9">{{ $venta->anuladaPor?->usuario ?? '—' }}</dd>

            <dt class="col-sm-3">Anulada en</dt>
            <dd class="col-sm-9">{{ $venta->anulada_en?->format('d/m/Y H:i') ?? '—' }}</dd>

            <dt class="col-sm-3">Motivo</dt>
            <dd class="col-sm-9">{{ $venta->motivo_anulacion }}</dd>
        @endif
    </dl>

    <h2 class="h5">Ítems</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->codigo_producto }}</td>
                        <td>{{ $detalle->nombre_producto }}</td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>{{ bs($detalle->precio_unitario) }}</td>
                        <td>{{ bs($detalle->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Subtotal</th>
                    <th>{{ bs($venta->subtotal) }}</th>
                </tr>
                @if ((float) $venta->descuento > 0)
                    <tr>
                        <th colspan="4" class="text-end">Descuento</th>
                        <th>{{ bs($venta->descuento) }}</th>
                    </tr>
                @endif
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th>{{ bs($venta->total) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <a href="{{ route('ventas.index') }}" class="btn btn-secondary">Volver</a>

    @if ($venta->estado === 'COMPLETADA')
        @can('anular-ventas')
            <div class="modal fade" id="modal-anular" tabindex="-1" aria-labelledby="modal-anular-titulo" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('ventas.anular', $venta) }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="modal-anular-titulo">Anular venta {{ $venta->numero() }}</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p>Esta acción devolverá el stock y no se puede deshacer.</p>
                                <label for="motivo" class="form-label">Motivo (mínimo 5 caracteres)</label>
                                <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger">Confirmar anulación</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif
@endsection
