@extends('layouts.app')

@section('titulo', 'Ventas por categoría')

@section('contenido')
    <x-page-header titulo="Ventas por categoría" :migas="['Reportes' => route('reportes.index'), 'Por categoría' => null]" />

    @include('reportes._filtro', ['accion' => route('reportes.categorias')])

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr><th>Categoría</th><th>Cantidad</th><th>Total</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->categoria }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td>{{ bs($fila->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
