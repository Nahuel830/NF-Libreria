@extends('layouts.app')

@section('titulo', 'Ajustar stock')

@section('contenido')
    <h1>Ajustar stock: {{ $producto->codigo }} — {{ $producto->nombre }}</h1>

    <form method="POST" action="{{ route('productos.ajustar.actualizar', $producto) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Stock actual (solo lectura)</label>
            <input type="text" class="form-control" value="{{ $producto->stock }}" disabled>
        </div>

        <div class="mb-3">
            <label for="stock_real" class="form-label">Stock real contado</label>
            <input type="number" step="1" class="form-control" id="stock_real" name="stock_real" value="{{ old('stock_real') }}" required>
        </div>

        <div class="mb-3">
            <label for="motivo" class="form-label">Motivo (mínimo 5 caracteres)</label>
            <input type="text" class="form-control" id="motivo" name="motivo" value="{{ old('motivo') }}" required
                placeholder="Ej: Conteo físico, Producto dañado, Pérdida, Error de registro, Otro" list="motivos">
            <datalist id="motivos">
                <option value="Conteo físico"></option>
                <option value="Producto dañado"></option>
                <option value="Pérdida"></option>
                <option value="Error de registro"></option>
                <option value="Otro"></option>
            </datalist>
        </div>

        <button type="submit" class="btn btn-primary">Guardar ajuste</button>
        <a href="{{ route('productos.ver', $producto) }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection
