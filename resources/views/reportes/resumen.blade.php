@extends('layouts.app')

@section('titulo', 'Resumen de ventas por día')

@section('contenido')
    <x-page-header titulo="Resumen de ventas por día" :migas="['Reportes' => route('reportes.index'), 'Resumen' => null]" />

    @include('reportes._filtro', ['accion' => route('reportes.resumen')])

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr><th>Fecha</th><th>Cantidad</th><th>Total</th><th>Descuentos</th><th>Devoluciones</th><th>Anuladas</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->dia }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td>{{ bs($fila->total) }}</td>
                        <td>{{ bs($fila->descuentos) }}</td>
                        <td>{{ bs($fila->devoluciones) }}</td>
                        <td>{{ $fila->anuladas }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
