@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    <h1>Editar usuario: {{ $usuario->usuario }}</h1>

    <form method="POST" action="{{ route('usuarios.actualizar', $usuario) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" required maxlength="100">
        </div>

        <div class="mb-3">
            <label class="form-label">Usuario (no se puede cambiar)</label>
            <input type="text" class="form-control" value="{{ $usuario->usuario }}" disabled>
        </div>

        <div class="mb-3">
            <label for="rol" class="form-label">Rol</label>
            <select class="form-select" id="rol" name="rol" required>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->value }}" @selected(old('rol', $usuario->rol->value) === $rol->value)>{{ $rol->etiqueta() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection
