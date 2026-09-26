@extends('layouts.app')

@section('titulo', isset($producto) ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
    <x-page-header :titulo="isset($producto) ? 'Editar producto' : 'Nuevo producto'" :migas="['Inventario' => null, 'Productos' => route('productos.index'), isset($producto) ? 'Editar' : 'Nuevo' => null]" />

    <x-card>
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
                    <label for="codigo" class="form-label">Código *</label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('codigo') is-invalid @enderror" id="codigo" name="codigo" value="{{ old('codigo', $producto->codigo ?? '') }}" required maxlength="30">
                        <button type="button" class="btn btn-outline-secondary" id="btn-sugerir">Sugerir código</button>
                    </div>
                    @error('codigo')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8 mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required maxlength="150">
                    @error('nombre')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="2">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
                @error('descripcion')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="categoria_id" class="form-label">Categoría *</label>
                    <select class="form-select @error('categoria_id') is-invalid @enderror" id="categoria_id" name="categoria_id" required>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((string) old('categoria_id', $producto->categoria_id ?? '') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="marca" class="form-label">Marca o editorial</label>
                    <input type="text" class="form-control @error('marca') is-invalid @enderror" id="marca" name="marca" value="{{ old('marca', $producto->marca ?? '') }}" maxlength="100">
                    @error('marca')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="unidad" class="form-label">Unidad *</label>
                    <select class="form-select @error('unidad') is-invalid @enderror" id="unidad" name="unidad" required>
                        @foreach ($unidades as $unidad)
                            <option value="{{ $unidad }}" @selected(old('unidad', $producto->unidad ?? 'unidad') === $unidad)>{{ ucfirst($unidad) }}</option>
                        @endforeach
                    </select>
                    @error('unidad')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="precio_compra" class="form-label">Precio de compra (Bs.) *</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('precio_compra') is-invalid @enderror" id="precio_compra" name="precio_compra" value="{{ old('precio_compra', $producto->precio_compra ?? '0.00') }}" required>
                    @error('precio_compra')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="precio_venta" class="form-label">Precio de venta (Bs.) *</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('precio_venta') is-invalid @enderror" id="precio_venta" name="precio_venta" value="{{ old('precio_venta', $producto->precio_venta ?? '') }}" required>
                    @error('precio_venta')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="stock_minimo" class="form-label">Stock mínimo</label>
                    <input type="number" step="1" min="0" class="form-control @error('stock_minimo') is-invalid @enderror" id="stock_minimo" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}">
                    @error('stock_minimo')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="controla_stock" name="controla_stock" value="1"
                    @checked(old('controla_stock', isset($producto) ? $producto->controla_stock : true))>
                <label class="form-check-label" for="controla_stock">Controla stock (desactivar para servicios como fotocopias)</label>
            </div>

            @if (! isset($producto))
                <div class="mb-3">
                    <label for="stock_inicial" class="form-label">Stock inicial (opcional, solo si controla stock)</label>
                    <input type="number" step="1" min="0" class="form-control @error('stock_inicial') is-invalid @enderror" id="stock_inicial" name="stock_inicial" value="{{ old('stock_inicial', 0) }}">
                    @error('stock_inicial')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection

@push('scripts')
    <script src="{{ asset('js/productos.js') }}"></script>
@endpush
