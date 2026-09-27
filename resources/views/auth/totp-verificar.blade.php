@extends('layouts.app')

@section('titulo', 'Verificación en dos pasos')

@section('contenido')
    <div class="row justify-content-center mt-5">
        <div class="col-md-4">
            <x-card titulo="Escribe tu código">
                <p class="text-secondary">Abre tu app de autenticación o usa un código de recuperación.</p>

                <form method="POST" action="{{ route('totp.verificar.comprobar') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código *</label>
                        <input type="text" class="form-control form-control-lg text-center @error('codigo') is-invalid @enderror" id="codigo" name="codigo"
                            required autofocus autocomplete="one-time-code">
                        @error('codigo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
