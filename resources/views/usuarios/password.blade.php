@extends('layouts.app')

@section('titulo', 'Restablecer contraseña')

@section('contenido')
    <x-page-header titulo="Restablecer contraseña: {{ $usuario->usuario }}" :migas="['Administración' => null, 'Usuarios' => route('usuarios.index'), 'Contraseña' => null]" />

    <x-card>
        <p class="text-secondary">El usuario deberá cambiar esta contraseña temporal al entrar.</p>

        <form method="POST" action="{{ route('usuarios.password.actualizar', $usuario) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña temporal (mínimo 8 caracteres) *</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password">
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmar contraseña *</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
