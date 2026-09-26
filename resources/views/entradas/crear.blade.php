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
                    <label for="proveedor" class="form-label">Proveedor</label>
                    <input type="text" class="form-control" id="proveedor" name="proveedor" value="{{ old('proveedor') }}" maxlength="150">
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

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="actualizar_precio_compra" name="actualizar_precio_compra" value="1" checked>
                <label class="form-check-label" for="actualizar_precio_compra">Actualizar precio de compra de los productos</label>
            </div>
        </x-card>

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
