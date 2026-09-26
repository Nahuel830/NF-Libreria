@extends('layouts.app')

@section('titulo', 'Ajustar stock')

@section('contenido')
    <x-page-header titulo="Ajustar stock: {{ $producto->codigo }} — {{ $producto->nombre }}" :migas="['Inventario' => null, 'Productos' => route('productos.index'), 'Ajustar' => null]" />

    <x-card>
        <form method="POST" action="{{ route('productos.ajustar.actualizar', $producto) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Stock actual (solo lectura)</label>
                <input type="text" class="form-control" value="{{ $producto->stock }}" disabled>
            </div>

            <div class="mb-3">
                <label for="stock_real" class="form-label">Stock real contado *</label>
                <input type="number" step="1" class="form-control @error('stock_real') is-invalid @enderror" id="stock_real" name="stock_real" value="{{ old('stock_real') }}" required>
                @error('stock_real')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo (mínimo 5 caracteres) *</label>
                <input type="text" class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" value="{{ old('motivo') }}" required
                    placeholder="Ej: Conteo físico, Producto dañado, Pérdida, Error de registro, Otro" list="motivos">
                <datalist id="motivos">
                    <option value="Conteo físico"></option>
                    <option value="Producto dañado"></option>
                    <option value="Pérdida"></option>
                    <option value="Error de registro"></option>
                    <option value="Otro"></option>
                </datalist>
                @error('motivo')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('productos.ver', $producto) }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
