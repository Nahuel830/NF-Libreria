@extends('layouts.app')

@section('titulo', 'Caja #' . $caja->id)

@section('contenido')
    <x-page-header :titulo="'Caja #' . $caja->id . ' — ' . $caja->usuario->usuario" :migas="['Ventas' => null, 'Caja' => route('caja.mi-caja'), '#' . $caja->id => null]">
        <button type="button" class="btn btn-outline-primary no-imprimir" data-imprimir><i class="bi bi-printer"></i> Imprimir</button>
    </x-page-header>

    <x-card>
        <dl class="row mb-0">
            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8"><x-estado :estado="$caja->estado" /></dd>

            <dt class="col-sm-4">Abierta en</dt>
            <dd class="col-sm-8">{{ $caja->abierta_en->format('d/m/Y H:i') }}</dd>

            <dt class="col-sm-4">Monto inicial</dt>
            <dd class="col-sm-8"><x-dinero :monto="$caja->monto_inicial" /></dd>

            @if ($caja->estado === 'CERRADA')
                <dt class="col-sm-4">Cerrada en</dt>
                <dd class="col-sm-8">{{ $caja->cerrada_en->format('d/m/Y H:i') }} por {{ $caja->cerradaPor?->usuario ?? '—' }}</dd>

                <dt class="col-sm-4">Efectivo esperado</dt>
                <dd class="col-sm-8"><x-dinero :monto="$caja->efectivo_esperado" /></dd>

                <dt class="col-sm-4">Efectivo contado</dt>
                <dd class="col-sm-8"><x-dinero :monto="$caja->efectivo_contado" /></dd>

                <dt class="col-sm-4">Diferencia</dt>
                <dd class="col-sm-8"><x-dinero :monto="$caja->diferencia" /></dd>

                @if ($caja->detalle_conteo)
                    <dt class="col-sm-4">Conteo</dt>
                    <dd class="col-sm-8">
                        @foreach ($caja->detalle_conteo as $denominacion => $cantidad)
                            Bs. {{ $denominacion }} × {{ $cantidad }}@if (! $loop->last), @endif
                        @endforeach
                    </dd>
                @endif

                <dt class="col-sm-4">Observaciones</dt>
                <dd class="col-sm-9">{{ $caja->observaciones_cierre ?? '—' }}</dd>
            @else
                <dt class="col-sm-4">Efectivo esperado (en vivo)</dt>
                <dd class="col-sm-8"><x-dinero :monto="$resumen['esperado']" /></dd>
            @endif
        </dl>
    </x-card>

    @if ($caja->estado === 'ABIERTA')
        <x-card titulo="Ventas por método (informativo)">
            <ul class="mb-0">
                @foreach ($resumen['por_metodo'] as $metodo => $total)
                    <li>{{ $metodo }}: <x-dinero :monto="$total" /></li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-card titulo="Ventas de esta caja">
        <ul class="mb-0">
            @forelse ($caja->ventas as $venta)
                <li>
                    <a href="{{ route('ventas.ver', $venta) }}">{{ $venta->numero() }}</a>
                    — <x-dinero :monto="$venta->total" /> — {{ $venta->metodo_pago }} — {{ $venta->estado }}
                    @if ($venta->estado === 'ANULADA' && $caja->estado === 'CERRADA')
                        <span class="badge bg-warning text-dark">anulada después del cierre</span>
                    @endif
                </li>
            @empty
                <li>Sin ventas.</li>
            @endforelse
        </ul>
    </x-card>

    <a href="{{ route('caja.mi-caja') }}" class="btn btn-secondary no-imprimir">Volver</a>
@endsection
