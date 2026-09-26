@extends('layouts.app')

@section('titulo', 'Productos más vendidos')

@section('contenido')
    <h1>Productos más vendidos (top 50)</h1>

    <form method="GET" action="{{ route('reportes.productos') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="date" class="form-control" name="desde" value="{{ $desde }}" aria-label="Desde">
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="hasta" value="{{ $hasta }}" aria-label="Hasta">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="orden">
                <option value="cantidad" @selected($orden === 'cantidad')>Por cantidad</option>
                <option value="total" @selected($orden === 'total')>Por total</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
        </div>
        <div class="col-md-2">
            <a href="{{ route('reportes.productos') }}?desde={{ $desde }}&hasta={{ $hasta }}&orden={{ $orden }}&formato=csv" class="btn btn-outline-success w-100">Exportar CSV</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Cantidad</th><th>Total</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->codigo }}</td>
                        <td>{{ $fila->nombre }}</td>
                        <td>{{ $fila->categoria }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td>{{ bs($fila->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
