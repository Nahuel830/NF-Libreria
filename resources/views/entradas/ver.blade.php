@extends('layouts.app')

@section('titulo', 'Entrada ' . $entrada->numero())

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Entrada {{ $entrada->numero() }}</h1>
        @if ($entrada->estado === 'REGISTRADA')
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-anular">
                Anular
            </button>
        @endif
    </div>

    <dl class="row">
        <dt class="col-sm-3">Fecha</dt>
        <dd class="col-sm-9">{{ $entrada->fecha->format('d/m/Y H:i') }}</dd>

        <dt class="col-sm-3">Proveedor</dt>
        <dd class="col-sm-9">{{ $entrada->proveedor ?? '—' }}</dd>

        <dt class="col-sm-3">Documento</dt>
        <dd class="col-sm-9">{{ $entrada->documento_referencia ?? '—' }}</dd>

        <dt class="col-sm-3">Observaciones</dt>
        <dd class="col-sm-9">{{ $entrada->observaciones ?? '—' }}</dd>

        <dt class="col-sm-3">Total</dt>
        <dd class="col-sm-9">{{ bs($entrada->total) }}</dd>

        <dt class="col-sm-3">Registrada por</dt>
        <dd class="col-sm-9">{{ $entrada->usuario->usuario }}</dd>

        <dt class="col-sm-3">Estado</dt>
        <dd class="col-sm-9">
            @if ($entrada->estado === 'REGISTRADA')
                <span class="badge bg-success">Registrada</span>
            @else
                <span class="badge bg-danger">Anulada</span>
            @endif
        </dd>

        @if ($entrada->estado === 'ANULADA')
            <dt class="col-sm-3">Anulada por</dt>
            <dd class="col-sm-9">{{ $entrada->anuladaPor?->usuario ?? '—' }}</dd>

            <dt class="col-sm-3">Anulada en</dt>
            <dd class="col-sm-9">{{ $entrada->anulada_en?->format('d/m/Y H:i') ?? '—' }}</dd>

            <dt class="col-sm-3">Motivo</dt>
            <dd class="col-sm-9">{{ $entrada->motivo_anulacion }}</dd>
        @endif
    </dl>

    <h2 class="h5">Ítems</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Costo unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entrada->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->producto->codigo }} — {{ $detalle->producto->nombre }}</td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>{{ bs($detalle->costo_unitario) }}</td>
                        <td>{{ bs($detalle->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('entradas.index') }}" class="btn btn-secondary">Volver</a>

    @if ($entrada->estado === 'REGISTRADA')
        <div class="modal fade" id="modal-anular" tabindex="-1" aria-labelledby="modal-anular-titulo" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('entradas.anular', $entrada) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="modal-anular-titulo">Anular entrada {{ $entrada->numero() }}</h1>
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
    @endif
@endsection
