@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    <h1>Nuevo usuario</h1>

    <form method="POST" action="{{ route('usuarios.guardar') }}">
        @csrf

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100">
        </div>

        <div class="mb-3">
            <label for="usuario" class="form-label">Usuario (minúsculas, números, punto y guion bajo)</label>
            <input type="text" class="form-control" id="usuario" name="usuario" value="{{ old('usuario') }}" required maxlength="50">
        </div>

        <div class="mb-3">
            <label for="rol" class="form-label">Rol</label>
            <select class="form-select" id="rol" name="rol" required>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->value }}" @selected(old('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña inicial (mínimo 8 caracteres)</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection
