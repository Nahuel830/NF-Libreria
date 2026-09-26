@extends('layouts.app')

@section('titulo', 'Stock bajo')

@section('contenido')
    <x-page-header titulo="Productos con stock bajo" :migas="['Inventario' => null, 'Stock bajo' => null]" />

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Stock mínimo</th>
                    <th>Diferencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td><a href="{{ route('productos.ver', $producto) }}">{{ $producto->codigo }}</a></td>
                        <td>{{ $producto->nombre }}</td>
                        <td>{{ $producto->categoria->nombre }}</td>
                        <td><x-estado estado="STOCK BAJO" /> {{ $producto->stock }}</td>
                        <td>{{ $producto->stock_minimo }}</td>
                        <td>{{ $producto->stock - $producto->stock_minimo }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"><x-empty-state mensaje="No hay productos con stock bajo." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $productos->links() }}
@endsection
