@extends('layouts.app')

@section('titulo', 'Mejores clientes')

@section('contenido')
    <x-page-header titulo="Mejores clientes" :migas="['Reportes' => route('reportes.index'), 'Clientes' => null]" />

    @include('reportes._filtro', ['accion' => route('reportes.clientes')])

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr><th>Cliente</th><th>CI/NIT</th><th>Cantidad</th><th>Total</th><th>Devoluciones</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->nombre }}</td>
                        <td>{{ $fila->ci_nit ?? '—' }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td>{{ bs($fila->total) }}</td>
                        <td>{{ bs($fila->devoluciones) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
