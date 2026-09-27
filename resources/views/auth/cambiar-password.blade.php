@extends('layouts.app')

@section('titulo', 'Cambiar contraseña')

@section('contenido')
    <x-page-header titulo="Cambiar contraseña" />

    <div class="row justify-content-center">
        <div class="col-md-5">
            <x-card>
                <form method="POST" action="{{ route('password.actualizar') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="actual" class="form-label">Contraseña actual *</label>
                        <input type="password" class="form-control @error('actual') is-invalid @enderror" id="actual" name="actual"
                            required autocomplete="current-password">
                        @error('actual')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="nueva" class="form-label">Contraseña nueva (mínimo 8 caracteres) *</label>
                        <input type="password" class="form-control @error('nueva') is-invalid @enderror" id="nueva" name="nueva"
                            required autocomplete="new-password">
                        @error('nueva')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="nueva_confirmation" class="form-label">Confirmar contraseña nueva *</label>
                        <input type="password" class="form-control" id="nueva_confirmation" name="nueva_confirmation"
                            required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                </form>

                @if (auth()->user()->rol->value === 'encargado')
                    <hr>
                    <a href="{{ route('totp.estado') }}" class="btn btn-outline-secondary w-100">Verificación en dos pasos</a>
                @endif
            </x-card>
        </div>
    </div>
@endsection
