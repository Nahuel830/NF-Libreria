@extends('layouts.app')

@section('titulo', 'Inventario valorizado')

@section('contenido')
    <x-page-header titulo="Inventario valorizado" :migas="['Reportes' => route('reportes.index'), 'Inventario' => null]" />

    <form method="GET" action="{{ route('reportes.inventario') }}" class="row g-2 mb-3">
        <div class="col-md-4">
            <select class="form-select" name="categoria_id">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) $categoriaId === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
        </div>
        <div class="col-md-4">
            <a href="{{ route('reportes.inventario') }}?categoria_id={{ $categoriaId }}&formato=csv" class="btn btn-outline-success w-100">Exportar CSV</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-sm tabla-nf">
            <thead>
                <tr><th>Código</th><th>Nombre</th><th>Stock</th><th>Precio compra</th><th>Valor costo</th><th>Precio venta</th><th>Valor venta</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila['codigo'] }}</td>
                        <td>{{ $fila['nombre'] }}</td>
                        <td>{{ $fila['stock'] }}</td>
                        <td>{{ bs($fila['precio_compra']) }}</td>
                        <td>{{ bs($fila['valor_costo']) }}</td>
                        <td>{{ bs($fila['precio_venta']) }}</td>
                        <td>{{ bs($fila['valor_venta']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Totales</th>
                    <th>{{ bs($totales['costo']) }}</th>
                    <th></th>
                    <th>{{ bs($totales['venta']) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
