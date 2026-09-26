@extends('layouts.app')

@section('titulo', 'Nueva categoría')

@section('contenido')
    <h1>Nueva categoría</h1>

    <form method="POST" action="{{ route('categorias.guardar') }}">
        @csrf

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100">
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <input type="text" class="form-control" id="descripcion" name="descripcion" value="{{ old('descripcion') }}" maxlength="255">
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('categorias.index') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection
