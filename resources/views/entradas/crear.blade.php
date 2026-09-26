@extends('layouts.app')

@section('titulo', 'Nueva entrada')

@section('contenido')
    <h1>Nueva entrada de mercadería</h1>

    <form method="POST" action="{{ route('entradas.guardar') }}" id="form-entrada">
        @csrf

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

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="actualizar_precio_compra" name="actualizar_precio_compra" value="1" checked>
            <label class="form-check-label" for="actualizar_precio_compra">Actualizar precio de compra de los productos</label>
        </div>

        <div class="mb-3">
            <label for="buscador" class="form-label">Agregar producto (buscar por código o nombre)</label>
            <input type="text" class="form-control" id="buscador" autocomplete="off" placeholder="Escribe código o nombre...">
            <div id="resultados" class="list-group mt-1"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Stock actual</th>
                        <th>Cantidad</th>
                        <th>Costo unitario (Bs.)</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="items"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th id="total">Bs. 0,00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="submit" class="btn btn-primary" id="btn-registrar"
            onclick="return confirm('¿Confirmas el registro de esta entrada?');">Registrar entrada</button>
        <a href="{{ route('entradas.index') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/entradas.js') }}"></script>
@endpush
