@extends('layouts.app')

@section('titulo', isset($producto) ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
    <h1>{{ isset($producto) ? 'Editar producto' : 'Nuevo producto' }}</h1>

    <form method="POST" action="{{ isset($producto) ? route('productos.actualizar', $producto) : route('productos.guardar') }}">
        @csrf
        @if (isset($producto))
            @method('PUT')
        @endif
        @if (session()->has('warning'))
            <input type="hidden" name="confirmar_precio" value="1">
        @endif

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="codigo" class="form-label">Código</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo" name="codigo" value="{{ old('codigo', $producto->codigo ?? '') }}" required maxlength="30">
                    <button type="button" class="btn btn-outline-secondary" id="btn-sugerir">Sugerir código</button>
                </div>
            </div>

            <div class="col-md-8 mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required maxlength="150">
            </div>
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="2">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="categoria_id" class="form-label">Categoría</label>
                <select class="form-select" id="categoria_id" name="categoria_id" required>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((string) old('categoria_id', $producto->categoria_id ?? '') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="marca" class="form-label">Marca o editorial</label>
                <input type="text" class="form-control" id="marca" name="marca" value="{{ old('marca', $producto->marca ?? '') }}" maxlength="100">
            </div>

            <div class="col-md-4 mb-3">
                <label for="unidad" class="form-label">Unidad</label>
                <select class="form-select" id="unidad" name="unidad" required>
                    @foreach ($unidades as $unidad)
                        <option value="{{ $unidad }}" @selected(old('unidad', $producto->unidad ?? 'unidad') === $unidad)>{{ ucfirst($unidad) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="precio_compra" class="form-label">Precio de compra (Bs.)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="precio_compra" name="precio_compra" value="{{ old('precio_compra', $producto->precio_compra ?? '0.00') }}" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="precio_venta" class="form-label">Precio de venta (Bs.)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="precio_venta" name="precio_venta" value="{{ old('precio_venta', $producto->precio_venta ?? '') }}" required>
            </div>

            <div class="col-md-4 mb-3">
                <label for="stock_minimo" class="form-label">Stock mínimo</label>
                <input type="number" step="1" min="0" class="form-control" id="stock_minimo" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}">
            </div>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="controla_stock" name="controla_stock" value="1"
                @checked(old('controla_stock', isset($producto) ? $producto->controla_stock : true))>
            <label class="form-check-label" for="controla_stock">Controla stock (desactivar para servicios como fotocopias)</label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/productos.js') }}"></script>
@endpush
