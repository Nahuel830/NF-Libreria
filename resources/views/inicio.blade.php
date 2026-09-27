@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    @if ($esAdmin)
        <x-page-header titulo="Panel de inicio" />

        @if ($alertaBackup)
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                Atención: el último backup tiene más de 24 horas o el último resultado fue error. Revisa las copias de seguridad.
            </div>
        @endif

        @if (($nuevosDispositivos ?? 0) > 0)
            <div class="alert alert-info" role="alert">
                <i class="bi bi-phone"></i>
                {{ $nuevosDispositivos }} inicio(s) de sesión desde dispositivos nuevos en los últimos 7 días.
                <a href="{{ route('auditoria.index', ['accion' => 'LOGIN_NUEVO_DISPOSITIVO']) }}" class="alert-link">Ver en auditoría</a>
            </div>
        @endif

        <p class="text-secondary">Último backup: {{ $ultimoBackupFecha ?? 'nunca' }}{{ $ultimoBackupResultado ? ' — '.$ultimoBackupResultado : '' }}</p>

        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <x-card>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cash-stack fs-3 text-primary"></i>
                        <div>Vendido hoy<br><strong class="fs-5"><x-dinero :monto="$totalHoy" /></strong></div>
                    </div>
                </x-card>
            </div>
            <div class="col-md-3 mb-2">
                <x-card>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-receipt fs-3 text-primary"></i>
                        <div>Ventas hoy<br><strong class="fs-5">{{ $cantidadHoy }}</strong></div>
                    </div>
                </x-card>
            </div>
            <div class="col-md-3 mb-2">
                <x-card>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calculator fs-3 text-primary"></i>
                        <div>Ticket promedio<br><strong class="fs-5"><x-dinero :monto="$ticketPromedio" /></strong></div>
                    </div>
                </x-card>
            </div>
            <div class="col-md-3 mb-2">
                <x-card>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle fs-3 text-danger"></i>
                        <div>Anuladas hoy<br><strong class="fs-5">{{ $anuladasHoy }}</strong></div>
                    </div>
                </x-card>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <x-card titulo="Totales de hoy por método de pago">
                    <ul class="mb-0">
                        @forelse ($porMetodo as $fila)
                            <li>{{ $fila->metodo_pago }}: {{ $fila->cantidad }} ventas — <x-dinero :monto="$fila->total" /></li>
                        @empty
                            <li>Sin ventas hoy.</li>
                        @endforelse
                    </ul>
                </x-card>
            </div>
            <div class="col-md-6 mb-2">
                <x-card titulo="Ventas de los últimos 7 días">
                    <canvas id="grafico-ventas" height="120"></canvas>
                </x-card>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <x-card titulo="Productos con stock bajo">
                    <ul class="mb-2">
                        @forelse ($stockBajo as $producto)
                            <li>{{ $producto->codigo }} — {{ $producto->nombre }} (stock {{ $producto->stock }})</li>
                        @empty
                            <li>Sin productos con stock bajo.</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('inventario.stock-bajo') }}" class="small">ver todos</a>
                </x-card>
            </div>
            <div class="col-md-6 mb-2">
                <x-card titulo="Últimas 10 ventas">
                    <ul class="mb-0">
                        @forelse ($ultimasVentas as $venta)
                            <li><a href="{{ route('ventas.ver', $venta) }}">{{ $venta->numero() }}</a> — <x-dinero :monto="$venta->total" /> — {{ $venta->usuario->usuario }}</li>
                        @empty
                            <li>Sin ventas.</li>
                        @endforelse
                    </ul>
                </x-card>
            </div>
        </div>
    @else
        <x-page-header titulo="Bienvenido, {{ auth()->user()->nombre }}" subtitulo="Tu rol es: {{ auth()->user()->rol->etiqueta() }}" />

        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <x-card>
                    Mis ventas de hoy: <strong>{{ $misVentasHoy }}</strong> — Total: <strong><x-dinero :monto="$miTotalHoy" /></strong>
                </x-card>
            </div>
            <div class="col-md-6 mb-2">
                <a href="{{ route('ventas.nueva') }}" class="btn btn-success btn-lg w-100"><i class="bi bi-cart-plus"></i> Nueva venta</a>
            </div>
        </div>

        <x-card titulo="Mis últimas 5 ventas">
            <ul class="mb-0">
                @forelse ($misUltimas as $venta)
                    <li><a href="{{ route('ventas.ticket', $venta) }}">{{ $venta->numero() }}</a> — <x-dinero :monto="$venta->total" /></li>
                @empty
                    <li>Sin ventas hoy.</li>
                @endforelse
            </ul>
        </x-card>
    @endif
@endsection

@if ($esAdmin)
    @push('scripts')
        <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
        <script type="application/json" id="datos-grafico">@json(['etiquetas' => $graficoEtiquetas, 'valores' => $graficoValores])</script>
        <script src="{{ asset('js/inicio.js') }}"></script>
    @endpush
@endif
