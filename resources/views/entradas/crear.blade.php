@extends('layouts.app')

@section('titulo', 'Nueva entrada')

@section('contenido')
    <x-page-header titulo="Nueva entrada de mercadería" :migas="['Inventario' => null, 'Entradas' => route('entradas.index'), 'Nueva' => null]" />

    <form method="POST" action="{{ route('entradas.guardar') }}" id="form-entrada"
        data-confirm="¿Confirmas el registro de esta entrada?" data-texto-confirm="Registrar entrada" data-color-confirm="primary">
        @csrf

        <x-card titulo="Datos de la entrada">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="proveedor_nombre" class="form-label">Proveedor</label>
                    <input type="text" class="form-control" id="proveedor_nombre" autocomplete="off" placeholder="Buscar proveedor...">
                    <input type="hidden" id="proveedor_id" name="proveedor_id" value="{{ old('proveedor_id') }}">
                    <div id="proveedores-resultados" class="list-group mt-1"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-bs-toggle="modal" data-bs-target="#modal-proveedor">
                        Crear proveedor rápido
                    </button>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="documento_referencia" class="form-label">Documento de referencia</label>
                    <input type="text" class="form-control" id="documento_referencia" name="documento_referencia" value="{{ old('documento_referencia') }}" maxlength="50">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <input type="text" class="form-control" id="observaciones" name="observaciones" value="{{ old('observaciones') }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="proveedor" class="form-label">Proveedor (texto libre, opcional si eliges arriba)</label>
                <input type="text" class="form-control" id="proveedor" name="proveedor" value="{{ old('proveedor') }}" maxlength="150">
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="actualizar_precio_compra" name="actualizar_precio_compra" value="1" checked>
                <label class="form-check-label" for="actualizar_precio_compra">Actualizar precio de compra de los productos</label>
            </div>
        </x-card>

        <div class="modal fade" id="modal-proveedor" tabindex="-1" aria-labelledby="modal-proveedor-titulo" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="modal-proveedor-titulo">Crear proveedor rápido</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <label for="proveedor-rapido-nombre" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="proveedor-rapido-nombre" maxlength="150">
                        <div class="text-danger small mt-1 d-none" id="proveedor-rapido-error"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="proveedor-rapido-guardar">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <x-card titulo="Productos">
            <div class="mb-3">
                <label for="buscador" class="form-label">Agregar producto (buscar por código o nombre)</label>
                <input type="text" class="form-control" id="buscador" autocomplete="off" placeholder="Escribe código o nombre...">
                <div id="resultados" class="list-group mt-1"></div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped tabla-nf">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Stock actual</th>
                            <th>Cantidad</th>
                            <th class="monto">Costo unitario (Bs.)</th>
                            <th class="monto">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="items"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total</th>
                            <th class="monto" id="total">Bs. 0,00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>

        <button type="submit" class="btn btn-primary" id="btn-registrar">Registrar entrada</button>
        <a href="{{ route('entradas.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/entradas.js') }}"></script>
@endpush
