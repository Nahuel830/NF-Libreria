@extends('layouts.app')

@section('titulo', 'Verificación en dos pasos')

@section('contenido')
    <x-page-header titulo="Verificación en dos pasos" :migas="['Inicio' => route('inicio'), 'Dos pasos' => null]" />

    @if (session('codigos_recuperacion'))
        <x-card titulo="Códigos de recuperación (guárdalos ahora)">
            <p class="text-danger">Se muestran una sola vez. Cada uno sirve para entrar una vez si pierdes el teléfono.</p>
            <ul>
                @foreach (session('codigos_recuperacion') as $codigo)
                    <li><code>{{ $codigo }}</code></li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-card>
        @if ($usuario->totp_activo)
            <p>Estado: <x-estado estado="ACTIVO" /></p>

            @if ($usuario->rol->value === 'encargado')
                <form method="POST" action="{{ route('totp.desactivar') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="actual" class="form-label">Contraseña actual para desactivar *</label>
                        <input type="password" class="form-control @error('actual') is-invalid @enderror" id="actual" name="actual" required autocomplete="current-password">
                        @error('actual')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-outline-danger">Desactivar</button>
                </form>
            @endif
        @else
            <p>Estado: <x-estado estado="INACTIVO" /></p>
            <a href="{{ route('totp.configurar') }}" class="btn btn-primary">Activar</a>
        @endif
    </x-card>
@endsection
