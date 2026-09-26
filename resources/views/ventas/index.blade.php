@extends('layouts.app')

@section('titulo', 'Historial de ventas')

@section('contenido')
    <x-page-header titulo="Historial de ventas" :migas="['Ventas' => null, 'Historial' => null]">
        <a href="{{ route('ventas.nueva') }}" class="btn btn-success"><i class="bi bi-cart-plus"></i> Nueva venta</a>
    </x-page-header>

    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <x-card>Completadas: <strong>{{ $resumen['completadas'] }}</strong></x-card>
        </div>
        <div class="col-md-4 mb-2">
            <x-card>Total vendido: <strong><x-dinero :monto="$resumen['total']" /></strong></x-card>
        </div>
        <div class="col-md-4 mb-2">
            <x-card>Anuladas: <strong>{{ $resumen['anuladas'] }}</strong></x-card>
        </div>
    </div>

    @if (! $esCajero)
        <x-filtros :accion="route('ventas.index')">
            <div class="col-md-2">
                <input type="date" class="form-control" name="desde" value="{{ request('desde', today()->toDateString()) }}" aria-label="Desde">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="hasta" value="{{ request('hasta', today()->toDateString()) }}" aria-label="Hasta">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="cajero_id" aria-label="Cajero">
                    <option value="">Todos los cajeros</option>
                    @foreach ($cajeros as $cajero)
                        <option value="{{ $cajero->id }}" @selected((string) request('cajero_id') === (string) $cajero->id)>{{ $cajero->usuario }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="metodo_pago" aria-label="Método">
                    <option value="">Todos los métodos</option>
                    @foreach ($metodos as $metodo)
                        <option value="{{ $metodo->value }}" @selected(request('metodo_pago') === $metodo->value)>{{ $metodo->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="estado" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    <option value="COMPLETADA" @selected(request('estado') === 'COMPLETADA')>Completada</option>
                    <option value="ANULADA" @selected(request('estado') === 'ANULADA')>Anulada</option>
                </select>
            </div>
            <div class="col-md-1">
                <input type="number" class="form-control" name="numero" value="{{ request('numero') }}" placeholder="Nº" aria-label="Número">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i></button>
            </div>
        </x-filtros>
    @endif

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha y hora</th>
                    <th>Cajero</th>
                    <th>Cliente</th>
                    <th>Ítems</th>
                    <th>Método</th>
                    <th class="monto">Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ventas as $venta)
                    <tr>
                        <td><a href="{{ route('ventas.ver', $venta) }}">{{ $venta->numero() }}</a></td>
                        <td>{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                        <td>{{ $venta->usuario->usuario }}</td>
                        <td>{{ $venta->cliente_nombre ?? '—' }}</td>
                        <td>{{ $venta->detalles_count }}</td>
                        <td>{{ $venta->metodo_pago }}</td>
                        <td class="monto"><x-dinero :monto="$venta->total" /></td>
                        <td><x-estado :estado="$venta->estado" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><x-empty-state mensaje="No hay ventas para mostrar." :accion-url="route('ventas.nueva')" accion-texto="Nueva venta" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $ventas->links() }}
@endsection
