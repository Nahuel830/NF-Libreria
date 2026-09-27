@extends('layouts.app')

@section('titulo', 'Mi caja')

@section('contenido')
    <x-page-header titulo="Mi caja" :migas="['Ventas' => null, 'Caja' => null]">
        @if ($caja)
            <a href="{{ route('caja.movimiento', $caja) }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left-right"></i> Movimiento</a>
            <a href="{{ route('caja.cerrar', $caja) }}" class="btn btn-warning"><i class="bi bi-lock"></i> Cerrar caja</a>
        @else
            <a href="{{ route('caja.abrir') }}" class="btn btn-success"><i class="bi bi-unlock"></i> Abrir caja</a>
        @endif
    </x-page-header>

    @if (! $caja)
        <x-empty-state icono="bi-safe" mensaje="No tienes caja abierta." :accion-url="route('caja.abrir')" accion-texto="Abrir caja" />
    @else
        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <x-card titulo="Abierta desde">
                    <strong>{{ $caja->abierta_en->format('d/m/Y H:i') }}</strong><br>
                    Monto inicial: <x-dinero :monto="$caja->monto_inicial" />
                </x-card>
            </div>
            <div class="col-md-4 mb-2">
                <x-card titulo="Ventas (completadas)">
                    {{ $resumen['ventas'] }} ventas
                    <ul class="mb-0">
                        @foreach ($resumen['por_metodo'] as $metodo => $total)
                            <li>{{ $metodo }}: <x-dinero :monto="$total" /></li>
                        @endforeach
                    </ul>
                </x-card>
            </div>
            <div class="col-md-4 mb-2">
                <x-card titulo="Efectivo">
                    Ingresos: <x-dinero :monto="$resumen['ingresos']" /><br>
                    Egresos: <x-dinero :monto="$resumen['egresos']" /><br>
                    Esperado: <strong><x-dinero :monto="$resumen['esperado']" /></strong>
                </x-card>
            </div>
        </div>

        <x-card titulo="Movimientos de efectivo">
            <ul class="mb-0">
                @forelse ($caja->movimientos as $movimiento)
                    <li>{{ $movimiento->created_at->format('d/m/Y H:i') }} — {{ $movimiento->tipo }} <x-dinero :monto="$movimiento->monto" /> ({{ $movimiento->concepto }})</li>
                @empty
                    <li>Sin movimientos.</li>
                @endforelse
            </ul>
        </x-card>
    @endif
@endsection
