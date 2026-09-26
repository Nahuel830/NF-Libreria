@extends('layouts.app')

@section('titulo', 'Editar categoría')

@section('contenido')
    <x-page-header titulo="Editar categoría" :migas="['Inventario' => null, 'Categorías' => route('categorias.index'), 'Editar' => null]" />

    <x-card>
        <form method="POST" action="{{ route('categorias.actualizar', $categoria) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" required maxlength="100">
                @error('nombre')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <input type="text" class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" value="{{ old('descripcion', $categoria->descripcion) }}" maxlength="255">
                @error('descripcion')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('categorias.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
