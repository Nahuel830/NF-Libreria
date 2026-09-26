@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    @if ($esAdmin)
        <h1>Panel de inicio</h1>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card"><div class="card-body">Vendido hoy: <strong>{{ bs($totalHoy) }}</strong></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">Ventas hoy: <strong>{{ $cantidadHoy }}</strong></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">Ticket promedio: <strong>{{ bs($ticketPromedio) }}</strong></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">Anuladas hoy: <strong>{{ $anuladasHoy }}</strong></div></div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6">Totales de hoy por método de pago</h2>
                        <ul class="mb-0">
                            @forelse ($porMetodo as $fila)
                                <li>{{ $fila->metodo_pago }}: {{ $fila->cantidad }} ventas — {{ bs($fila->total) }}</li>
                            @empty
                                <li>Sin ventas hoy.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6">Ventas de los últimos 7 días</h2>
                        <canvas id="grafico-ventas" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <h2 class="h6">Productos con stock bajo <a href="{{ route('inventario.stock-bajo') }}" class="small">ver todos</a></h2>
                <ul>
                    @forelse ($stockBajo as $producto)
                        <li>{{ $producto->codigo }} — {{ $producto->nombre }} (stock {{ $producto->stock }})</li>
                    @empty
                        <li>Sin productos con stock bajo.</li>
                    @endforelse
                </ul>
            </div>
            <div class="col-md-6">
                <h2 class="h6">Últimas 10 ventas</h2>
                <ul>
                    @forelse ($ultimasVentas as $venta)
                        <li><a href="{{ route('ventas.ver', $venta) }}">{{ $venta->numero() }}</a> — {{ bs($venta->total) }} — {{ $venta->usuario->usuario }}</li>
                    @empty
                        <li>Sin ventas.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @else
        <h1>Bienvenido, {{ auth()->user()->nombre }}</h1>
        <p>Tu rol es: {{ auth()->user()->rol->etiqueta() }}</p>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card"><div class="card-body">Mis ventas de hoy: <strong>{{ $misVentasHoy }}</strong> — Total: <strong>{{ bs($miTotalHoy) }}</strong></div></div>
            </div>
            <div class="col-md-6">
                <a href="{{ route('ventas.nueva') }}" class="btn btn-success btn-lg w-100">Nueva venta</a>
            </div>
        </div>

        <h2 class="h6">Mis últimas 5 ventas</h2>
        <ul>
            @forelse ($misUltimas as $venta)
                <li><a href="{{ route('ventas.ticket', $venta) }}">{{ $venta->numero() }}</a> — {{ bs($venta->total) }}</li>
            @empty
                <li>Sin ventas hoy.</li>
            @endforelse
        </ul>
    @endif
@endsection

@if ($esAdmin)
    @push('scripts')
        <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new Chart(document.getElementById('grafico-ventas'), {
                    type: 'bar',
                    data: {
                        labels: @json($graficoEtiquetas),
                        datasets: [{ label: 'Ventas (Bs.)', data: @json($graficoValores) }],
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true } } },
                });
            });
        </script>
    @endpush
@endif
