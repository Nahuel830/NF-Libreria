<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cierre del día {{ $fecha }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <style>
        @media print {
            @page { size: A4; margin: 10mm; }
            .no-imprimir { display: none !important; }
        }
        .cierre { font-family: ui-monospace, monospace; max-width: 80mm; margin: 0 auto; }
        @media print and (max-width: 100mm) {
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="container py-3">
        <div class="d-none d-print-block text-center mb-3">
            <img src="{{ logo_url() }}" alt="Logo" height="48">
            <p class="mb-0"><strong>{{ app(\App\Services\ConfiguracionService::class)->get('nombre_negocio', 'NF Librería') }}</strong></p>
            <p class="mb-0">Cierre del día {{ $fecha }} — impreso el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->usuario }}</p>
        </div>

        <h1>Cierre del día {{ $fecha }}</h1>

        <form method="GET" action="{{ route('reportes.cierre') }}" class="row g-2 mb-3 no-imprimir">
            <div class="col-md-4">
                <input type="date" class="form-control" name="fecha" value="{{ $fecha }}" aria-label="Fecha">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="cajero_id">
                    <option value="">Todos los cajeros</option>
                    @foreach ($cajeros as $cajero)
                        <option value="{{ $cajero->id }}" @selected((string) $cajeroId === (string) $cajero->id)>{{ $cajero->usuario }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary">Ver</button>
                <button type="button" class="btn btn-primary" data-imprimir>Imprimir</button>
            </div>
            <div class="col-md-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="por_fecha_real" name="por_fecha_real" value="1" @checked($porFechaReal) data-envio-automatico>
                    <label class="form-check-label" for="por_fecha_real">Ver ventas por fecha real (incluye contingencias del día aunque se hayan cargado después)</label>
                </div>
            </div>
        </form>

        <div class="cierre">
            <h2 class="h6">Totales por método de pago</h2>
            <ul>
                @forelse ($porMetodo as $fila)
                    <li>{{ $fila->metodo_pago }}: {{ $fila->cantidad }} ventas — {{ bs($fila->total) }}</li>
                @empty
                    <li>Sin ventas.</li>
                @endforelse
            </ul>
            <p>Cantidad de ventas: <strong>{{ $cantidad }}</strong>@if ($porFechaReal) <span class="text-secondary">(por fecha real)</span>@endif</p>
            <p>Total: <strong>{{ bs($total) }}</strong></p>
            <p>Efectivo esperado: <strong>{{ bs($efectivo) }}</strong></p>
            <p>Devoluciones: <strong>{{ bs($devTotal) }}</strong> (ya restadas)</p>

            <h2 class="h6">Ventas anuladas ({{ $anuladas->count() }})</h2>
            <ul>
                @forelse ($anuladas as $venta)
                    <li>{{ $venta->numero() }} — {{ bs($venta->total) }} — {{ $venta->motivo_anulacion }} ({{ $venta->usuario->usuario }})@if ($venta->es_contingencia) [Contingencia: {{ $venta->fecha_contingencia->format('d/m/Y H:i') }}]@endif</li>
                @empty
                    <li>Ninguna.</li>
                @endforelse
            </ul>

            <h2 class="h6">Cajas del día ({{ $cajas->count() }})</h2>
            <ul>
                @forelse ($cajas as $caja)
                    <li>
                        Caja #{{ $caja->id }} ({{ $caja->usuario->usuario }}): {{ $caja->estado }}
                        @if ($caja->estado === 'CERRADA')
                            — esperado {{ bs($caja->efectivo_esperado) }}, contado {{ bs($caja->efectivo_contado) }}, diferencia {{ bs($caja->diferencia) }}
                        @endif
                    </li>
                @empty
                    <li>Ninguna.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <script src="{{ asset('js/imprimir.js') }}"></script>
</body>
</html>
