@extends('layouts.app')

@section('titulo', 'Configuración')

@section('contenido')
    <x-page-header titulo="Configuración" :migas="['Administración' => null, 'Configuración' => null]" />

    <x-card>
        <form method="POST" action="{{ route('configuracion.actualizar') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="nombre_negocio" class="form-label">Nombre del negocio *</label>
                <input type="text" class="form-control @error('nombre_negocio') is-invalid @enderror" id="nombre_negocio" name="nombre_negocio" value="{{ old('nombre_negocio', $valores['nombre_negocio']) }}" required maxlength="100">
                @error('nombre_negocio')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="logo" class="form-label">Logo del negocio (PNG o JPG, máximo 1 MB)</label>
                <div class="mb-2"><img src="{{ logo_url() }}" alt="Logo actual" height="64"></div>
                <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept=".png,.jpg,.jpeg">
                @error('logo')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="quitar_logo" name="quitar_logo" value="1">
                    <label class="form-check-label" for="quitar_logo">Quitar logo (volver al de por defecto)</label>
                </div>
            </div>

            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control @error('direccion') is-invalid @enderror" id="direccion" name="direccion" value="{{ old('direccion', $valores['direccion']) }}" maxlength="255">
                @error('direccion')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono', $valores['telefono']) }}" maxlength="50">
                @error('telefono')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="mensaje_ticket" class="form-label">Mensaje del ticket *</label>
                <input type="text" class="form-control @error('mensaje_ticket') is-invalid @enderror" id="mensaje_ticket" name="mensaje_ticket" value="{{ old('mensaje_ticket', $valores['mensaje_ticket']) }}" required maxlength="255">
                @error('mensaje_ticket')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="permitir_stock_negativo" name="permitir_stock_negativo" value="1"
                    @checked(old('permitir_stock_negativo', $valores['permitir_stock_negativo']) === '1')>
                <label class="form-check-label" for="permitir_stock_negativo">Permitir stock negativo</label>
            </div>

            <div class="mb-3">
                <label for="minutos_inactividad" class="form-label">Minutos de inactividad para cerrar sesión (5 a 480) *</label>
                <input type="number" class="form-control @error('minutos_inactividad') is-invalid @enderror" id="minutos_inactividad" name="minutos_inactividad" value="{{ old('minutos_inactividad', $valores['minutos_inactividad']) }}" required min="5" max="480">
                @error('minutos_inactividad')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="imprimir_automatico" name="imprimir_automatico" value="1"
                    @checked(old('imprimir_automatico', $valores['imprimir_automatico'] ?? '0') === '1')>
                <label class="form-check-label" for="imprimir_automatico">Imprimir ticket automáticamente al vender</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="exigir_caja_abierta" name="exigir_caja_abierta" value="1"
                    @checked(old('exigir_caja_abierta', $valores['exigir_caja_abierta'] ?? '1') === '1')>
                <label class="form-check-label" for="exigir_caja_abierta">Exigir caja abierta para vender</label>
            </div>

            <div class="mb-3">
                <label for="ips_cajero" class="form-label">IPs autorizadas para cajeros (separadas por coma; vacío = sin restricción)</label>
                <input type="text" class="form-control @error('ips_cajero') is-invalid @enderror" id="ips_cajero" name="ips_cajero" value="{{ old('ips_cajero', $valores['ips_cajero'] ?? '') }}" maxlength="255" placeholder="Ej: 192.168.1.50, 192.168.1.51">
                @error('ips_cajero')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </x-card>
@endsection
