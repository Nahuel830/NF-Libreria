@extends('layouts.app')

@section('titulo', 'Mejores clientes')

@section('contenido')
    <x-page-header titulo="Mejores clientes" :migas="['Reportes' => route('reportes.index'), 'Clientes' => null]" />

    @include('reportes._filtro', ['accion' => route('reportes.clientes')])

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr><th>Cliente</th><th>CI/NIT</th><th>Cantidad</th><th class="monto">Total</th></tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila->nombre }}</td>
                        <td>{{ $fila->ci_nit ?? '—' }}</td>
                        <td>{{ $fila->cantidad }}</td>
                        <td class="monto"><x-dinero :monto="$fila->total" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
