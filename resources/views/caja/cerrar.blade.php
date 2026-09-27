@extends('layouts.app')

@section('titulo', 'Cerrar caja')

@section('contenido')
    <x-page-header titulo="Cerrar caja #{{ $caja->id }}" :migas="['Ventas' => null, 'Caja' => route('caja.mi-caja'), 'Cerrar' => null]" />

    <x-card>
        <form method="POST" action="{{ route('caja.cerrar.guardar', $caja) }}">
            @csrf

            <p>Efectivo esperado: <strong id="esperado" data-valor="{{ $esperado }}">{{ bs($esperado) }}</strong></p>

            <h2 class="h6">Conteo por denominación</h2>
            <div class="row">
                @foreach ($denominaciones as $denominacion)
                    <div class="col-6 col-md-4 mb-2">
                        <label class="form-label" for="conteo-{{ str_replace('.', '-', $denominacion) }}">Bs. {{ $denominacion }}</label>
                        <input type="number" min="0" max="10000" step="1" class="form-control conteo" id="conteo-{{ str_replace('.', '-', $denominacion) }}"
                            name="conteo[{{ $denominacion }}]" value="{{ old('conteo.'.$denominacion, 0) }}" data-valor="{{ $denominacion }}">
                    </div>
                @endforeach
            </div>

            <p class="fs-4">Efectivo contado: <strong id="contado">Bs. 0,00</strong></p>
            <p class="fs-4">Diferencia: <strong id="diferencia">Bs. 0,00</strong></p>

            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones (obligatorias si hay diferencia)</label>
                <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones" rows="2">{{ old('observaciones') }}</textarea>
                @error('observaciones')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-warning btn-lg">Cerrar caja</button>
            <a href="{{ route('caja.mi-caja') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection

@push('scripts')
    <script src="{{ asset('js/caja.js') }}"></script>
@endpush
