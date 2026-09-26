@extends('layouts.app')

@section('titulo', 'Ventas por método de pago')

@section('contenido')
    <x-page-header titulo="Ventas por método de pago" :migas="['Reportes' => route('reportes.index'), 'Por método' => null]" />

    @include('reportes._filtro', ['accion' => route('reportes.metodos')])

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr><th>Método</th><th>Cantidad</th><th>Total</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->metodo_pago }}</td>
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
