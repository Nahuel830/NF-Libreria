@extends('layouts.app')

@section('titulo', 'Resumen de ventas por día')

@section('contenido')
    <h1>Resumen de ventas por día</h1>

    @include('reportes._filtro', ['accion' => route('reportes.resumen')])

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr><th>Fecha</th><th>Cantidad</th><th>Total</th><th>Descuentos</th><th>Anuladas</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->dia }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td>{{ bs($fila->total) }}</td>
                        <td>{{ bs($fila->descuentos) }}</td>
                        <td>{{ $fila->anuladas }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
