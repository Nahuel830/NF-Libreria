@extends('layouts.app')

@section('titulo', 'Abrir caja')

@section('contenido')
    <x-page-header titulo="Abrir caja" :migas="['Ventas' => null, 'Caja' => route('caja.mi-caja'), 'Abrir' => null]" />

    <x-card>
        <form method="POST" action="{{ route('caja.abrir.guardar') }}">
            @csrf

            <div class="mb-3">
                <label for="monto_inicial" class="form-label">Monto inicial en efectivo (Bs.) *</label>
                <input type="number" min="0" step="0.01" class="form-control form-control-lg @error('monto_inicial') is-invalid @enderror" id="monto_inicial" name="monto_inicial" value="{{ old('monto_inicial', '0.00') }}" required>
                @error('monto_inicial')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-success btn-lg">Abrir caja</button>
            <a href="{{ route('caja.mi-caja') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
