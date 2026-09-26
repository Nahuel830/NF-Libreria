@extends('layouts.app')

@section('titulo', 'Restablecer contraseña')

@section('contenido')
    <h1>Restablecer contraseña: {{ $usuario->usuario }}</h1>
    <p class="text-muted">El usuario deberá cambiar esta contraseña temporal al entrar.</p>

    <form method="POST" action="{{ route('usuarios.password.actualizar', $usuario) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña temporal (mínimo 8 caracteres)</label>
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
