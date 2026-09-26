@extends('layouts.app')

@section('titulo', 'Configuración')

@section('contenido')
    <h1>Configuración</h1>

    <form method="POST" action="{{ route('configuracion.actualizar') }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nombre_negocio" class="form-label">Nombre del negocio</label>
            <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio" value="{{ old('nombre_negocio', $valores['nombre_negocio']) }}" required maxlength="100">
        </div>

        <div class="mb-3">
            <label for="direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="direccion" name="direccion" value="{{ old('direccion', $valores['direccion']) }}" maxlength="255">
        </div>

        <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $valores['telefono']) }}" maxlength="50">
        </div>

        <div class="mb-3">
            <label for="mensaje_ticket" class="form-label">Mensaje del ticket</label>
            <input type="text" class="form-control" id="mensaje_ticket" name="mensaje_ticket" value="{{ old('mensaje_ticket', $valores['mensaje_ticket']) }}" required maxlength="255">
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="permitir_stock_negativo" name="permitir_stock_negativo" value="1"
                @checked(old('permitir_stock_negativo', $valores['permitir_stock_negativo']) === '1')>
            <label class="form-check-label" for="permitir_stock_negativo">Permitir stock negativo</label>
        </div>

        <div class="mb-3">
            <label for="minutos_inactividad" class="form-label">Minutos de inactividad para cerrar sesión (5 a 480)</label>
            <input type="number" class="form-control" id="minutos_inactividad" name="minutos_inactividad" value="{{ old('minutos_inactividad', $valores['minutos_inactividad']) }}" required min="5" max="480">
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="imprimir_automatico" name="imprimir_automatico" value="1"
                @checked(old('imprimir_automatico', $valores['imprimir_automatico'] ?? '0') === '1')>
            <label class="form-check-label" for="imprimir_automatico">Imprimir ticket automáticamente al vender</label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
@endsection
