@extends('layouts.app')

@section('titulo', 'Stock bajo')

@section('contenido')
    <h1>Productos con stock bajo</h1>

    <div class="table-responsive">
        <table class="table table-striped">
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
                        <td><span class="badge bg-danger">{{ $producto->stock }}</span></td>
                        <td>{{ $producto->stock_minimo }}</td>
                        <td>{{ $producto->stock - $producto->stock_minimo }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay productos con stock bajo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $productos->links() }}
@endsection
