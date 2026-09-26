@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    <x-page-header titulo="Nuevo usuario" :migas="['Administración' => null, 'Usuarios' => route('usuarios.index'), 'Nuevo' => null]" />

    <x-card>
        <form method="POST" action="{{ route('usuarios.guardar') }}">
            @csrf

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100">
                @error('nombre')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario (minúsculas, números, punto y guion bajo) *</label>
                <input type="text" class="form-control @error('usuario') is-invalid @enderror" id="usuario" name="usuario" value="{{ old('usuario') }}" required maxlength="50">
                @error('usuario')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="rol" class="form-label">Rol *</label>
                <select class="form-select @error('rol') is-invalid @enderror" id="rol" name="rol" required>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(old('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                    @endforeach
                </select>
                @error('rol')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña inicial (mínimo 8 caracteres) *</label>
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
