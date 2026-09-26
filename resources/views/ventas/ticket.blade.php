<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Venta {{ $venta->numero() }} — {{ $nombreNegocio }}</title>
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
            .ticket .btn, .ticket a { display: none !important; }
        }
    </style>
    @if ($imprimirAutomatico)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</head>
<body>
    <div class="container py-3">
        @if (session('success'))
            <div class="alert alert-success no-imprimir" role="alert">{{ session('success') }}</div>
        @endif

        @php($cambioMostrar = session('cambio', request('cambio')))
        @if ($cambioMostrar !== null)
            <div class="alert alert-info no-imprimir" role="alert">
                Cambio a entregar: <strong class="fs-4">{{ bs($cambioMostrar) }}</strong>
            </div>
        @endif

        <div class="ticket">
            <p class="text-center mb-1"><img class="logo" src="{{ asset('img/logo-bn.svg') }}" alt="Logo"></p>
            <p class="text-center mb-1"><strong>{{ $nombreNegocio }}</strong></p>
            @if ($direccion !== '')
                <p class="text-center mb-1">{{ $direccion }}</p>
            @endif
            @if ($telefono !== '')
                <p class="text-center mb-1">Tel: {{ $telefono }}</p>
            @endif
            <p class="text-center mb-1">VENTA {{ $venta->numero() }}</p>
            <p class="mb-1">Fecha: {{ $venta->fecha->format('d/m/Y H:i') }}</p>
            <p class="mb-1">Cajero: {{ $venta->usuario->usuario }}</p>
            @if ($venta->cliente_nombre)
                <p class="mb-1">Cliente: {{ $venta->cliente_nombre }}</p>
            @endif
            <p class="mb-1">--------------------------------</p>
            @foreach ($venta->detalles as $detalle)
                <p class="mb-1">{{ $detalle->cantidad }} x {{ $detalle->nombre_producto }}<br>
                {{ bs($detalle->precio_unitario) }} c/u — {{ bs($detalle->subtotal) }}</p>
            @endforeach
            <p class="mb-1">--------------------------------</p>
            <p class="mb-1">Subtotal: {{ bs($venta->subtotal) }}</p>
            @if ((float) $venta->descuento > 0)
                <p class="mb-1">Descuento: {{ bs($venta->descuento) }}</p>
            @endif
            <p class="mb-1"><strong>TOTAL: {{ bs($venta->total) }}</strong></p>
            <p class="mb-1">Pago: {{ $venta->metodo_pago }}</p>
            @if ($venta->metodo_pago === 'EFECTIVO')
                <p class="mb-1">Recibido: {{ bs($venta->monto_recibido) }}</p>
                <p class="mb-1">Cambio: {{ bs($venta->cambio) }}</p>
            @endif
            <p class="mb-1">--------------------------------</p>
            <p class="text-center mb-1">{{ $mensajeTicket }}</p>
            <p class="text-center mb-1">Documento sin valor fiscal</p>
            @if ($venta->estado === 'ANULADA')
                <p class="text-center fs-3"><strong>*** ANULADA ***</strong></p>
            @endif
        </div>

        <div class="text-center mt-3 no-imprimir">
            <button type="button" class="btn btn-primary" onclick="window.print();">Imprimir</button>
            <a href="{{ route('ventas.nueva') }}" class="btn btn-success" id="btn-nueva" autofocus>Nueva venta</a>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Enter inicia la siguiente venta.
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('btn-nueva').click();
            }
        });
    </script>
</body>
</html>
