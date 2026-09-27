@extends('layouts.app')

@section('titulo', 'Activar verificación en dos pasos')

@section('contenido')
    <div class="row justify-content-center mt-4">
        <div class="col-md-6">
            <x-card titulo="Activar verificación en dos pasos">
                <ol>
                    <li>Abre Google Authenticator (u otra app) y escanea este código QR.</li>
                    <li>Escribe abajo el código de 6 dígitos que muestra la app.</li>
                </ol>

                <div class="text-center mb-3">
                    {!! $qr !!}
                    <p class="mt-2 text-secondary small">Si no puedes escanear, escribe esta clave: <code id="totp-secreto-valor">{{ $secreto }}</code></p>
                </div>

                <form method="POST" action="{{ route('totp.configurar.guardar') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código de 6 dígitos *</label>
                        <input type="text" class="form-control form-control-lg text-center @error('codigo') is-invalid @enderror" id="codigo" name="codigo"
                            required autocomplete="one-time-code" inputmode="numeric" maxlength="8">
                        @error('codigo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Activar</button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
