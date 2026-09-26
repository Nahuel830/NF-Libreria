@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    <x-page-header titulo="Editar usuario: {{ $usuario->usuario }}" :migas="['Administración' => null, 'Usuarios' => route('usuarios.index'), 'Editar' => null]" />

    <x-card>
        <form method="POST" action="{{ route('usuarios.actualizar', $usuario) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" required maxlength="100">
                @error('nombre')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Usuario (no se puede cambiar)</label>
                <input type="text" class="form-control" value="{{ $usuario->usuario }}" disabled>
            </div>

            <div class="mb-3">
                <label for="rol" class="form-label">Rol *</label>
                <select class="form-select @error('rol') is-invalid @enderror" id="rol" name="rol" required>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(old('rol', $usuario->rol->value) === $rol->value)>{{ $rol->etiqueta() }}</option>
                    @endforeach
                </select>
                @error('rol')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
