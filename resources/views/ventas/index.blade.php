@extends('layouts.app')

@section('titulo', 'Historial de ventas')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Historial de ventas</h1>
        <a href="{{ route('ventas.nueva') }}" class="btn btn-success">Nueva venta</a>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">Completadas: <strong>{{ $resumen['completadas'] }}</strong></div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">Total vendido: <strong>{{ bs($resumen['total']) }}</strong></div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">Anuladas: <strong>{{ $resumen['anuladas'] }}</strong></div></div>
        </div>
    </div>

    @if (! $esCajero)
        <form method="GET" action="{{ route('ventas.index') }}" class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="date" class="form-control" name="desde" value="{{ request('desde', today()->toDateString()) }}" aria-label="Desde">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="hasta" value="{{ request('hasta', today()->toDateString()) }}" aria-label="Hasta">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="cajero_id">
                    <option value="">Todos los cajeros</option>
                    @foreach ($cajeros as $cajero)
                        <option value="{{ $cajero->id }}" @selected((string) request('cajero_id') === (string) $cajero->id)>{{ $cajero->usuario }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="metodo_pago">
                    <option value="">Todos los métodos</option>
                    @foreach ($metodos as $metodo)
                        <option value="{{ $metodo->value }}" @selected(request('metodo_pago') === $metodo->value)>{{ $metodo->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="COMPLETADA" @selected(request('estado') === 'COMPLETADA')>Completada</option>
                    <option value="ANULADA" @selected(request('estado') === 'ANULADA')>Anulada</option>
                </select>
            </div>
            <div class="col-md-1">
                <input type="number" class="form-control" name="numero" value="{{ request('numero') }}" placeholder="Nº" aria-label="Número">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-secondary w-100">Ver</button>
            </div>
        </form>
    @endif

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha y hora</th>
                    <th>Cajero</th>
                    <th>Cliente</th>
                    <th>Ítems</th>
                    <th>Método</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ventas as $venta)
                    <tr>
                        <td><a href="{{ route('ventas.ver', $venta) }}">{{ $venta->numero() }}</a></td>
                        <td>{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                        <td>{{ $venta->usuario->usuario }}</td>
                        <td>{{ $venta->cliente_nombre ?? '—' }}</td>
                        <td>{{ $venta->detalles_count }}</td>
                        <td>{{ $venta->metodo_pago }}</td>
                        <td>{{ bs($venta->total) }}</td>
                        <td>
                            @if ($venta->estado === 'COMPLETADA')
                                <span class="badge bg-success">Completada</span>
                            @else
                                <span class="badge bg-danger">Anulada</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No hay ventas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $ventas->links() }}
@endsection
