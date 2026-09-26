@extends('layouts.app')

@section('titulo', 'Producto ' . $producto->codigo)

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $producto->codigo }} — {{ $producto->nombre }}</h1>
        @can('gestionar-productos')
            <a href="{{ route('productos.editar', $producto) }}" class="btn btn-primary">Editar</a>
        @endcan
    </div>

    <dl class="row">
        <dt class="col-sm-3">Código</dt>
        <dd class="col-sm-9">{{ $producto->codigo }}</dd>

        <dt class="col-sm-3">Nombre</dt>
        <dd class="col-sm-9">{{ $producto->nombre }}</dd>

        <dt class="col-sm-3">Descripción</dt>
        <dd class="col-sm-9">{{ $producto->descripcion ?? '—' }}</dd>

        <dt class="col-sm-3">Categoría</dt>
        <dd class="col-sm-9">{{ $producto->categoria->nombre }}</dd>

        <dt class="col-sm-3">Marca o editorial</dt>
        <dd class="col-sm-9">{{ $producto->marca ?? '—' }}</dd>

        <dt class="col-sm-3">Unidad</dt>
        <dd class="col-sm-9">{{ $producto->unidad }}</dd>

        @can('gestionar-productos')
            <dt class="col-sm-3">Precio de compra</dt>
            <dd class="col-sm-9">{{ bs($producto->precio_compra) }}</dd>
        @endcan

        <dt class="col-sm-3">Precio de venta</dt>
        <dd class="col-sm-9">{{ bs($producto->precio_venta) }}</dd>

        <dt class="col-sm-3">Stock</dt>
        <dd class="col-sm-9">{{ $producto->controla_stock ? $producto->stock : '— (no controla stock)' }}</dd>

        <dt class="col-sm-3">Stock mínimo</dt>
        <dd class="col-sm-9">{{ $producto->stock_minimo }}</dd>

        <dt class="col-sm-3">Estado</dt>
        <dd class="col-sm-9">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</dd>
    </dl>

    <h2 class="h5 mt-4">Movimientos de stock</h2>
    <p class="text-muted">El kardex de este producto estará disponible próximamente.</p>

    <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
@endsection
