@extends('layouts.app')

@section('titulo', 'Movimiento de caja')

@section('contenido')
    <x-page-header titulo="Movimiento de caja #{{ $caja->id }}" :migas="['Ventas' => null, 'Caja' => route('caja.mi-caja'), 'Movimiento' => null]" />

    <x-card>
        <form method="POST" action="{{ route('caja.movimiento.guardar', $caja) }}">
            @csrf
            @if (session()->has('warning'))
                <input type="hidden" name="confirmar_egreso" value="1">
            @endif

            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo *</label>
                <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
                    <option value="INGRESO" @selected(old('tipo') === 'INGRESO')>Ingreso</option>
                    <option value="EGRESO" @selected(old('tipo') === 'EGRESO')>Egreso</option>
                </select>
                @error('tipo')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="monto" class="form-label">Monto (Bs.) *</label>
                <input type="number" min="0.01" step="0.01" class="form-control form-control-lg @error('monto') is-invalid @enderror" id="monto" name="monto" value="{{ old('monto') }}" required>
                @error('monto')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="concepto" class="form-label">Concepto *</label>
                <input type="text" class="form-control @error('concepto') is-invalid @enderror" id="concepto" name="concepto" value="{{ old('concepto') }}" required maxlength="200" placeholder="Ej: Pago de luz, Cambio de billetes">
                @error('concepto')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('caja.mi-caja') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
