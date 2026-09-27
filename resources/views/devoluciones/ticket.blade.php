<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devolución {{ $devolucion->numero() }} — {{ $nombreNegocio }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <style>
        .ticket {
            font-family: ui-monospace, monospace;
            max-width: 80mm;
            margin: 0 auto;
            color: #000;
        }
        .ticket img.logo { height: 48px; filter: grayscale(1); }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { margin: 0; }
            .no-imprimir { display: none !important; }
            .ticket { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container py-3">
        @if (session('success'))
            <div class="alert alert-success no-imprimir" role="alert">{{ session('success') }}</div>
        @endif

        <div class="ticket">
            <p class="text-center mb-1"><img class="logo" src="{{ asset('img/logo-bn.svg') }}" alt="Logo"></p>
            <p class="text-center mb-1"><strong>{{ $nombreNegocio }}</strong></p>
            <p class="text-center mb-1">DEVOLUCIÓN {{ $devolucion->numero() }}</p>
            <p class="mb-1">Venta: {{ $devolucion->venta->numero() }}</p>
            <p class="mb-1">Fecha: {{ $devolucion->fecha->format('d/m/Y H:i') }}</p>
            <p class="mb-1">Atendió: {{ $devolucion->usuario->usuario }}</p>
            <p class="mb-1">--------------------------------</p>
            @foreach ($devolucion->detalles as $detalle)
                <p class="mb-1">{{ $detalle->cantidad }} x {{ $detalle->producto->nombre }}<br>
                {{ bs($detalle->precio_unitario) }} c/u — {{ bs($detalle->subtotal) }}</p>
            @endforeach
            <p class="mb-1">--------------------------------</p>
            <p class="mb-1"><strong>TOTAL DEVUELTO: {{ bs($devolucion->total_devuelto) }}</strong></p>
            <p class="mb-1">Reembolso: {{ $devolucion->metodo_reembolso }}</p>
            <p class="mb-1">Motivo: {{ $devolucion->motivo }}</p>
            <p class="mb-1">--------------------------------</p>
            <p class="text-center mb-1">Documento sin valor fiscal</p>
        </div>

        <div class="text-center mt-3 no-imprimir">
            <button type="button" class="btn btn-primary" onclick="window.print();">Imprimir</button>
            <a href="{{ route('ventas.ver', $devolucion->venta) }}" class="btn btn-secondary">Volver a la venta</a>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
