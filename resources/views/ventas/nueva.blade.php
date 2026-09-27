@extends('layouts.app')

@section('titulo', 'Nueva venta')

@section('contenido')
    <x-page-header titulo="Nueva venta" :migas="['Ventas' => null, 'Nueva' => null]" />

    <div id="mensaje-venta" class="alert alert-danger d-none" role="alert"></div>

    <form method="POST" action="{{ route('ventas.cobrar') }}" id="form-venta">
        @csrf
        <input type="hidden" id="token" name="token" value="{{ old('token', $token) }}">
        <input type="hidden" id="metodo_pago" name="metodo_pago" value="EFECTIVO">

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="buscador" class="form-label">Buscar producto <span class="badge bg-secondary">F2</span></label>
                    <input type="text" class="form-control buscador-venta" id="buscador" autocomplete="off" placeholder="Código o nombre, Enter para agregar">
                    <div id="resultados" class="list-group mt-1"></div>
                </div>

                <div class="table-responsive" style="max-height: 46vh; overflow-y: auto;">
                    <table class="table table-striped tabla-nf align-middle">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="monto">Precio</th>
                                <th>Cantidad</th>
                                <th class="monto">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="carrito"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <x-card>
                    <p class="mb-1">Subtotal: <strong id="subtotal">Bs. 0,00</strong></p>

                    @can('aplicar-descuentos')
                        <div class="mb-2">
                            <label for="descuento" class="form-label">Descuento (Bs.)</label>
                            <input type="number" min="0" step="0.01" class="form-control form-control-lg" id="descuento" name="descuento" value="0">
                        </div>
                    @endcan

                    <p class="mb-0">TOTAL:</p>
                    <p class="total-venta text-center"><span id="total" data-valor="0.00">Bs. 0,00</span></p>
                </x-card>

                <x-card titulo="Método de pago">
                    <div class="d-grid gap-2">
                        @foreach ($metodos as $metodo)
                            <button type="button" class="btn btn-lg {{ $metodo->value === 'EFECTIVO' ? 'btn-primary active' : 'btn-outline-primary' }}"
                                data-metodo="{{ $metodo->value }}">
                                <i class="bi bi-{{ $metodo->value === 'EFECTIVO' ? 'cash' : ($metodo->value === 'QR' ? 'qr-code' : ($metodo->value === 'TRANSFERENCIA' ? 'arrow-left-right' : ($metodo->value === 'TARJETA' ? 'credit-card' : 'wallet'))) }}"></i>
                                {{ $metodo->etiqueta() }}
                            </button>
                        @endforeach
                    </div>
                </x-card>

                <x-card titulo="Efectivo" id="pago-efectivo">
                    <label for="recibido" class="form-label">Recibido (Bs.)</label>
                    <input type="number" min="0" step="0.01" class="form-control form-control-lg" id="recibido" name="monto_recibido">
                    <p class="mt-2 fs-5">Cambio: <strong id="cambio">Bs. 0,00</strong></p>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ([10, 20, 50, 100, 200] as $billete)
                            <button type="button" class="btn btn-outline-secondary" data-billete="{{ $billete }}">{{ $billete }}</button>
                        @endforeach
                        <button type="button" class="btn btn-outline-secondary" id="btn-exacto">Exacto</button>
                    </div>
                </x-card>

                <x-card>
                    <div class="mb-3">
                        <label for="cliente_buscar" class="form-label">Cliente (opcional)</label>
                        <input type="text" class="form-control" id="cliente_buscar" autocomplete="off" placeholder="Buscar por nombre o CI/NIT...">
                        <input type="hidden" id="cliente_id" name="cliente_id">
                        <div id="clientes-resultados" class="list-group mt-1"></div>
                        <div class="d-flex gap-2 mt-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-cliente">Nuevo cliente</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="cliente-quitar">Quitar</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cliente_nombre" class="form-label">Cliente sin registro (opcional)</label>
                        <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" maxlength="150">
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones (opcional)</label>
                        <input type="text" class="form-control" id="observaciones" name="observaciones">
                    </div>

                    @can('ver-todas-las-ventas')
                        <div class="mb-3 border rounded p-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="es_contingencia" name="es_contingencia" value="1">
                                <label class="form-check-label" for="es_contingencia">Venta registrada en papel durante un corte</label>
                            </div>
                            <label for="fecha_contingencia" class="form-label mt-2">Fecha y hora real de la venta (máx. 7 días atrás)</label>
                            <input type="datetime-local" class="form-control" id="fecha_contingencia" name="fecha_contingencia" max="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    @endcan

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="btn-cobrar"><i class="bi bi-check-circle"></i> COBRAR (F9)</button>
                        <button type="button" class="btn btn-outline-danger" id="btn-cancelar">Cancelar venta (Esc)</button>
                    </div>
                    <p class="text-secondary small mt-2 mb-0">Atajos: <span class="badge bg-secondary">F2</span> buscar · <span class="badge bg-secondary">F9</span> cobrar · <span class="badge bg-secondary">Esc</span> cancelar</p>
                </x-card>
            </div>
        </div>
    </form>

    <div class="modal fade" id="modal-cliente" tabindex="-1" aria-labelledby="modal-cliente-titulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-cliente-titulo">Nuevo cliente</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="cliente-rapido-nombre" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="cliente-rapido-nombre" maxlength="150">
                    <label for="cliente-rapido-ci" class="form-label mt-2">CI/NIT</label>
                    <input type="text" class="form-control" id="cliente-rapido-ci" maxlength="20">
                    <div class="text-danger small mt-1 d-none" id="cliente-rapido-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="cliente-rapido-guardar">Guardar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ventas.js') }}"></script>
@endpush
