@extends('layouts.app')

@section('titulo', 'Devolución de venta ' . $venta->numero())

@section('contenido')
    <x-page-header :titulo="'Devolución de venta ' . $venta->numero()" :migas="['Ventas' => null, 'Historial' => route('ventas.index'), $venta->numero() => route('ventas.ver', $venta), 'Devolución' => null]" />

    <form method="POST" action="{{ route('devoluciones.guardar', $venta) }}">
        @csrf

        <x-card titulo="Productos a devolver">
            <div class="table-responsive">
                <table class="table table-striped tabla-nf">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Vendido</th>
                            <th>Devuelto</th>
                            <th>Disponible</th>
                            <th>Devolver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detalles as $i => $detalle)
                            <tr>
                                <td>{{ $detalle->codigo_producto }} — {{ $detalle->nombre_producto }} ({{ bs($detalle->precio_unitario) }})</td>
                                <td>{{ $detalle->cantidad }}</td>
                                <td>{{ $detalle->devuelto }}</td>
                                <td>{{ $detalle->disponible }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $i }}][detalle_venta_id]" value="{{ $detalle->id }}">
                                    <input type="number" min="0" max="{{ $detalle->disponible }}" step="1" class="form-control" name="items[{{ $i }}][cantidad]" value="0">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card>
            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo *</label>
                <textarea class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" rows="2" required>{{ old('motivo') }}</textarea>
                @error('motivo')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="metodo_reembolso" class="form-label">Método de reembolso *</label>
                <select class="form-select" id="metodo_reembolso" name="metodo_reembolso" required>
                    @foreach ($metodos as $metodo)
                        <option value="{{ $metodo->value }}" @selected(old('metodo_reembolso') === $metodo->value)>{{ $metodo->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Guardar devolución</button>
            <a href="{{ route('ventas.ver', $venta) }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
