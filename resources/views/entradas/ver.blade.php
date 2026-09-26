@extends('layouts.app')

@section('titulo', 'Entrada ' . $entrada->numero())

@section('contenido')
    <x-page-header :titulo="'Entrada ' . $entrada->numero()" :migas="['Inventario' => null, 'Entradas' => route('entradas.index'), $entrada->numero() => null]">
        @if ($entrada->estado === 'REGISTRADA')
            <form method="POST" action="{{ route('entradas.anular', $entrada) }}" class="d-inline"
                data-confirm="Esta acción devolverá el stock y no se puede deshacer."
                data-motivo
                data-titulo-confirm="Anular entrada {{ $entrada->numero() }}"
                data-texto-confirm="Confirmar anulación">
                @csrf
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Anular</button>
            </form>
        @endif
    </x-page-header>

    <x-card>
        <dl class="row mb-0">
            <dt class="col-sm-3">Fecha</dt>
            <dd class="col-sm-9">{{ $entrada->fecha->format('d/m/Y H:i') }}</dd>

            <dt class="col-sm-3">Proveedor</dt>
            <dd class="col-sm-9">{{ $entrada->proveedor ?? '—' }}</dd>

            <dt class="col-sm-3">Documento</dt>
            <dd class="col-sm-9">{{ $entrada->documento_referencia ?? '—' }}</dd>

            <dt class="col-sm-3">Observaciones</dt>
            <dd class="col-sm-9">{{ $entrada->observaciones ?? '—' }}</dd>

            <dt class="col-sm-3">Total</dt>
            <dd class="col-sm-9"><x-dinero :monto="$entrada->total" /></dd>

            <dt class="col-sm-3">Registrada por</dt>
            <dd class="col-sm-9">{{ $entrada->usuario->usuario }}</dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-estado :estado="$entrada->estado" /></dd>

            @if ($entrada->estado === 'ANULADA')
                <dt class="col-sm-3">Anulada por</dt>
                <dd class="col-sm-9">{{ $entrada->anuladaPor?->usuario ?? '—' }}</dd>

                <dt class="col-sm-3">Anulada en</dt>
                <dd class="col-sm-9">{{ $entrada->anulada_en?->format('d/m/Y H:i') ?? '—' }}</dd>

                <dt class="col-sm-3">Motivo</dt>
                <dd class="col-sm-9">{{ $entrada->motivo_anulacion }}</dd>
            @endif
        </dl>
    </x-card>

    <x-card titulo="Ítems">
        <div class="table-responsive">
            <table class="table table-striped tabla-nf">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th class="monto">Costo unitario</th>
                        <th class="monto">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entrada->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto->codigo }} — {{ $detalle->producto->nombre }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td class="monto"><x-dinero :monto="$detalle->costo_unitario" /></td>
                            <td class="monto"><x-dinero :monto="$detalle->subtotal" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <a href="{{ route('entradas.index') }}" class="btn btn-secondary">Volver</a>
@endsection
