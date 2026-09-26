@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="fondo-login rounded-3 py-5">
        <div class="row justify-content-center m-0">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-3">
                            <img src="{{ logo_url() }}" alt="Logo" height="72">
                            <h1 class="h4 mt-2">{{ $nombreNegocio ?? 'NF Librería' }}</h1>
                        </div>

                        <form method="POST" action="{{ route('login.entrar') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario *</label>
                                <input type="text" class="form-control form-control-lg @error('usuario') is-invalid @enderror" id="usuario" name="usuario"
                                    value="{{ old('usuario') }}" required autofocus autocomplete="username">
                                @error('usuario')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña *</label>
                                <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" name="password"
                                    required autocomplete="current-password">
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
