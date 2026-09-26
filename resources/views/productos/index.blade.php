@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Productos</h1>
        @can('gestionar-productos')
            <a href="{{ route('productos.crear') }}" class="btn btn-primary">Nuevo producto</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('productos.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por código o nombre">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="categoria_id">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) request('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="estado">
                <option value="">Todos</option>
                <option value="1" @selected(request('estado') === '1')>Activo</option>
                <option value="0" @selected(request('estado') === '0')>Inactivo</option>
            </select>
        </div>
        <div class="col-md-2">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="stock_bajo" value="1" id="stock_bajo" @checked(request()->boolean('stock_bajo'))>
                <label class="form-check-label" for="stock_bajo">Solo stock bajo</label>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Buscar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    @can('gestionar-productos')
                        <th>Precio compra</th>
                    @endcan
                    <th>Precio venta</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td>{{ $producto->codigo }}</td>
                        <td>{{ $producto->nombre }}</td>
                        <td>{{ $producto->categoria->nombre }}</td>
                        <td>{{ $producto->marca ?? '—' }}</td>
                        @can('gestionar-productos')
                            <td>{{ bs($producto->precio_compra) }}</td>
                        @endcan
                        <td>{{ bs($producto->precio_venta) }}</td>
                        <td>
                            @if (! $producto->controla_stock)
                                —
                            @elseif ($producto->stock <= $producto->stock_minimo)
                                <span class="badge bg-danger">{{ $producto->stock }}</span>
                            @else
                                {{ $producto->stock }}
                            @endif
                        </td>
                        <td>
                            @if ($producto->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('productos.ver', $producto) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                            @can('gestionar-productos')
                                <a href="{{ route('productos.editar', $producto) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No hay productos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $productos->links() }}
@endsection
