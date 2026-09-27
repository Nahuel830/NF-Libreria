@extends('layouts.app')

@section('titulo', 'Mi seguridad')

@section('contenido')
    <x-page-header titulo="Mi seguridad" :migas="['Inicio' => route('inicio'), 'Mi seguridad' => null]" />

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

    <x-card titulo="Verificación en dos pasos">
        @if ($usuario->tieneTotpActivo())
            <p>Estado: <x-estado estado="ACTIVO" /></p>

            <form method="POST" action="{{ route('totp.regenerar') }}" class="mb-4">
                @csrf

                <p class="fw-bold">Regenerar códigos de recuperación</p>
                <p class="text-secondary">Los códigos anteriores dejarán de servir.</p>

                <div class="mb-3">
                    <label for="actual-reg" class="form-label">Contraseña actual *</label>
                    <input type="password" class="form-control @error('actual') is-invalid @enderror" id="actual-reg" name="actual" required autocomplete="current-password">
                    @error('actual')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="codigo-reg" class="form-label">Código actual de la app *</label>
                    <input type="text" class="form-control @error('codigo') is-invalid @enderror" id="codigo-reg" name="codigo"
                        required autocomplete="one-time-code" inputmode="numeric" maxlength="8">
                    @error('codigo')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-outline-primary">Regenerar códigos</button>
            </form>

            @if (! $obligatorio)
                <form method="POST" action="{{ route('totp.desactivar') }}"
                    data-confirm="¿Desactivar la verificación en dos pasos?">
                    @csrf

                    <p class="fw-bold">Desactivar</p>

                    <div class="mb-3">
                        <label for="actual-des" class="form-label">Contraseña actual *</label>
                        <input type="password" class="form-control @error('actual') is-invalid @enderror" id="actual-des" name="actual" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label for="codigo-des" class="form-label">Código actual de la app *</label>
                        <input type="text" class="form-control @error('codigo') is-invalid @enderror" id="codigo-des" name="codigo"
                            required autocomplete="one-time-code" inputmode="numeric" maxlength="8">
                    </div>

                    <button type="submit" class="btn btn-outline-danger">Desactivar</button>
                </form>
            @else
                <p class="text-secondary">La verificación en dos pasos es obligatoria para tu rol y no se puede desactivar.</p>
            @endif
        @else
            <p>Estado: <x-estado estado="INACTIVO" /></p>
            @if ($obligatorio)
                <p class="text-danger">Es obligatoria para tu rol. Actívala para usar el sistema.</p>
            @endif
            <a href="{{ route('totp.configurar') }}" class="btn btn-primary">Activar</a>
        @endif
    </x-card>
@endsection
