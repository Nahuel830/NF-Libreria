@extends('layouts.app')

@section('titulo', 'Movimientos de stock')

@section('contenido')
    <x-page-header titulo="Movimientos de stock" :migas="['Reportes' => route('reportes.index'), 'Movimientos' => null]" />

    <form method="GET" action="{{ route('reportes.movimientos') }}" class="row g-2 mb-3">
        <div class="col-md-2">
            <input type="date" class="form-control" name="desde" value="{{ $desde }}" aria-label="Desde">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="hasta" value="{{ $hasta }}" aria-label="Hasta">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="producto_id">
                <option value="">Todos los productos</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}" @selected((string) request('producto_id') === (string) $producto->id)>{{ $producto->codigo }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="tipo">
                <option value="">Todos los tipos</option>
                @foreach ($tipos as $tipo)
                    <option value="{{ $tipo }}" @selected(request('tipo') === $tipo)>{{ $tipo }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="usuario_id">
                <option value="">Todos los usuarios</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>{{ $usuario->usuario }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-secondary w-100">Ver</button>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-outline-success w-100" name="formato" value="csv">CSV</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-sm tabla-nf">
            <thead>
                <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Anterior</th><th>Nuevo</th><th>Usuario</th><th>Motivo</th></tr>
            </thead>
            <tbody>
                @forelse ($movimientos as $movimiento)
                    <tr>
                        <td>{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $movimiento->producto->codigo }} — {{ $movimiento->producto->nombre }}</td>
                        <td>{{ $movimiento->tipo }}</td>
                        <td>{{ $movimiento->cantidad }}</td>
                        <td>{{ $movimiento->stock_anterior }}</td>
                        <td>{{ $movimiento->stock_nuevo }}</td>
                        <td>{{ $movimiento->usuario?->usuario ?? '—' }}</td>
                        <td>{{ $movimiento->motivo ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $movimientos->links() }}
@endsection
