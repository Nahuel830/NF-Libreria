@extends('layouts.app')

@section('titulo', 'Ventas por cajero')

@section('contenido')
    <x-page-header titulo="Ventas por cajero" :migas="['Reportes' => route('reportes.index'), 'Por cajero' => null]" />

    @include('reportes._filtro', ['accion' => route('reportes.cajeros')])

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr><th>Cajero</th><th>Usuario</th><th>Cantidad</th><th>Total</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->nombre }}</td>
                        <td>{{ $fila->usuario }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td>{{ bs($fila->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
