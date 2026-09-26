@extends('layouts.app')

@section('titulo', 'Nueva venta')

@section('contenido')
    <h1>Nueva venta</h1>

    <div id="mensaje-venta" class="alert alert-danger d-none" role="alert"></div>

    <form method="POST" action="{{ route('ventas.cobrar') }}" id="form-venta">
        @csrf
        <input type="hidden" id="token" name="token" value="{{ old('token', $token) }}">
        <input type="hidden" id="metodo_pago" name="metodo_pago" value="EFECTIVO">

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="buscador" class="form-label">Buscar producto (F2)</label>
                    <input type="text" class="form-control form-control-lg" id="buscador" autocomplete="off" placeholder="Código o nombre, Enter para agregar">
                    <div id="resultados" class="list-group mt-1"></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="carrito"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="mb-1">Subtotal: <strong id="subtotal">Bs. 0,00</strong></p>

                        @can('aplicar-descuentos')
                            <div class="mb-2">
                                <label for="descuento" class="form-label">Descuento (Bs.)</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="descuento" name="descuento" value="0">
                            </div>
                        @endcan

                        <p class="fs-3">TOTAL: <strong id="total" data-valor="0.00">Bs. 0,00</strong></p>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="form-label">Método de pago</span>
                    <div class="d-grid gap-2">
                        @foreach ($metodos as $metodo)
                            <button type="button" class="btn btn-lg {{ $metodo->value === 'EFECTIVO' ? 'btn-primary active' : 'btn-outline-primary' }}"
                                data-metodo="{{ $metodo->value }}">{{ $metodo->etiqueta() }}</button>
                        @endforeach
                    </div>
                </div>

                <div id="pago-efectivo" class="mb-3">
                    <label for="recibido" class="form-label">Recibido (Bs.)</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="recibido" name="monto_recibido">
                    <p class="mt-1">Cambio: <strong id="cambio">Bs. 0,00</strong></p>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ([10, 20, 50, 100, 200] as $billete)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-billete="{{ $billete }}">{{ $billete }}</button>
                        @endforeach
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-exacto">Exacto</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="cliente_nombre" class="form-label">Cliente (opcional)</label>
                    <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" maxlength="150">
                </div>

                <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones (opcional)</label>
                    <input type="text" class="form-control" id="observaciones" name="observaciones">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg" id="btn-cobrar">COBRAR (F9)</button>
                    <button type="button" class="btn btn-outline-danger" id="btn-cancelar">Cancelar venta (Esc)</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/ventas.js') }}"></script>
@endpush
