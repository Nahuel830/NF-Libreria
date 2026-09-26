@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
    <x-page-header titulo="Productos" :migas="['Inventario' => null, 'Productos' => null]">
        @can('gestionar-productos')
            <a href="{{ route('productos.importar') }}" class="btn btn-outline-secondary"><i class="bi bi-upload"></i> Importar desde Excel/CSV</a>
            <a href="{{ route('productos.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo producto</a>
        @endcan
    </x-page-header>

    <x-filtros :accion="route('productos.index')">
        <div class="col-md-3">
            <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por código o nombre" aria-label="Buscar">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="categoria_id" aria-label="Categoría">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) request('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="estado" aria-label="Estado">
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
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Buscar</button>
        </div>
    </x-filtros>

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    @can('gestionar-productos')
                        <th class="monto">Precio compra</th>
                    @endcan
                    <th class="monto">Precio venta</th>
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
                            <td class="monto"><x-dinero :monto="$producto->precio_compra" /></td>
                        @endcan
                        <td class="monto"><x-dinero :monto="$producto->precio_venta" /></td>
                        <td>
                            @if (! $producto->controla_stock)
                                —
                            @else
                                @if ($producto->stock <= 0)
                                    <x-estado estado="SIN STOCK" />
                                @elseif ($producto->stock <= $producto->stock_minimo)
                                    <x-estado estado="STOCK BAJO" />
                                @endif
                                {{ $producto->stock }}
                            @endif
                        </td>
                        <td><x-estado :estado="$producto->activo ? 'ACTIVO' : 'INACTIVO'" /></td>
                        <td>
                            <a href="{{ route('productos.ver', $producto) }}" class="btn btn-sm btn-outline-secondary" title="Ver"><i class="bi bi-eye"></i></a>
                            @can('gestionar-productos')
                                <a href="{{ route('productos.editar', $producto) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9"><x-empty-state mensaje="Todavía no hay productos registrados." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $productos->links() }}
@endsection
