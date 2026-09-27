@extends('layouts.app')

@section('titulo', 'Compras por proveedor')

@section('contenido')
    <x-page-header titulo="Compras por proveedor" :migas="['Reportes' => route('reportes.index'), 'Compras' => null]" />

    @include('reportes._filtro', ['accion' => route('reportes.compras')])

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr><th>Proveedor</th><th>Cantidad</th><th class="monto">Total</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->proveedor }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td class="monto"><x-dinero :monto="$fila->total" /></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
