@extends('layouts.app')

@section('titulo', 'Producto ' . $producto->codigo)

@section('contenido')
    <x-page-header :titulo="$producto->codigo . ' — ' . $producto->nombre" :migas="['Inventario' => null, 'Productos' => route('productos.index'), $producto->codigo => null]">
        @can('gestionar-stock')
            <a href="{{ route('productos.ajustar', $producto) }}" class="btn btn-warning"><i class="bi bi-sliders"></i> Ajustar stock</a>
        @endcan
        @can('gestionar-productos')
            <a href="{{ route('productos.editar', $producto) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
        @endcan
    </x-page-header>

    <x-card>
        <dl class="row mb-0">
            <dt class="col-sm-3">Código</dt>
            <dd class="col-sm-9">{{ $producto->codigo }}</dd>

            <dt class="col-sm-3">Código de barras</dt>
            <dd class="col-sm-9">{{ $producto->codigo_barras ?? '—' }}</dd>

            <dt class="col-sm-3">Nombre</dt>
            <dd class="col-sm-9">{{ $producto->nombre }}</dd>

            <dt class="col-sm-3">Descripción</dt>
            <dd class="col-sm-9">{{ $producto->descripcion ?? '—' }}</dd>

            <dt class="col-sm-3">Categoría</dt>
            <dd class="col-sm-9">{{ $producto->categoria->nombre }}</dd>

            <dt class="col-sm-3">Marca o editorial</dt>
            <dd class="col-sm-9">{{ $producto->marca ?? '—' }}</dd>

            <dt class="col-sm-3">Unidad</dt>
            <dd class="col-sm-9">{{ $producto->unidad }}</dd>

            @can('gestionar-productos')
                <dt class="col-sm-3">Precio de compra</dt>
                <dd class="col-sm-9"><x-dinero :monto="$producto->precio_compra" /></dd>
            @endcan

            <dt class="col-sm-3">Precio de venta</dt>
            <dd class="col-sm-9"><x-dinero :monto="$producto->precio_venta" /></dd>

            <dt class="col-sm-3">Stock</dt>
            <dd class="col-sm-9">{{ $producto->controla_stock ? $producto->stock : '— (no controla stock)' }}</dd>

            <dt class="col-sm-3">Stock mínimo</dt>
            <dd class="col-sm-9">{{ $producto->stock_minimo }}</dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-estado :estado="$producto->activo ? 'ACTIVO' : 'INACTIVO'" /></dd>
        </dl>
    </x-card>

    <x-card titulo="Movimientos de stock">
        <div class="table-responsive">
            <table class="table table-striped table-sm tabla-nf">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Stock anterior</th>
                        <th>Stock nuevo</th>
                        <th>Usuario</th>
                        <th>Referencia</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $movimiento)
                        <tr>
                            <td>{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $movimiento->cantidad > 0 ? 'success' : 'danger' }}">{{ $movimiento->tipo }}</span>
                            </td>
                            <td>{{ $movimiento->cantidad > 0 ? '+'.$movimiento->cantidad : $movimiento->cantidad }}</td>
                            <td>{{ $movimiento->stock_anterior }}</td>
                            <td>{{ $movimiento->stock_nuevo }}</td>
                            <td>{{ $movimiento->usuario?->usuario ?? '—' }}</td>
                            <td>
                                @if ($movimiento->referencia_tipo === 'venta')
                                    @can('ver-todas-las-ventas')
                                        <a href="{{ route('ventas.ver', $movimiento->referencia_id) }}">Venta #{{ str_pad($movimiento->referencia_id, 6, '0', STR_PAD_LEFT) }}</a>
                                    @else
                                        Venta #{{ str_pad($movimiento->referencia_id, 6, '0', STR_PAD_LEFT) }}
                                    @endcan
                                @elseif ($movimiento->referencia_tipo === 'entrada')
                                    @can('registrar-entradas')
                                        <a href="{{ route('entradas.ver', $movimiento->referencia_id) }}">Entrada #{{ str_pad($movimiento->referencia_id, 6, '0', STR_PAD_LEFT) }}</a>
                                    @else
                                        Entrada #{{ str_pad($movimiento->referencia_id, 6, '0', STR_PAD_LEFT) }}
                                    @endcan
                                @else
                                    {{ $movimiento->referencia_tipo ? $movimiento->referencia_tipo.' #'.$movimiento->referencia_id : '—' }}
                                @endif
                            </td>
                            <td>{{ $movimiento->motivo ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"><x-empty-state mensaje="Sin movimientos." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $movimientos->links() }}
    </x-card>

    <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
@endsection
