@extends('layouts.app')

@section('titulo', 'Cliente ' . $cliente->nombre)

@section('contenido')
    <x-page-header :titulo="$cliente->nombre" :migas="['Ventas' => null, 'Clientes' => route('clientes.index'), $cliente->nombre => null]">
        <a href="{{ route('clientes.editar', $cliente) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
    </x-page-header>

    <x-card>
        <dl class="row mb-0">
            <dt class="col-sm-3">CI/NIT</dt>
            <dd class="col-sm-9">{{ $cliente->ci_nit ?? '—' }}</dd>

            <dt class="col-sm-3">Teléfono</dt>
            <dd class="col-sm-9">{{ $cliente->telefono ?? '—' }}</dd>

            <dt class="col-sm-3">Correo</dt>
            <dd class="col-sm-9">{{ $cliente->email ?? '—' }}</dd>

            <dt class="col-sm-3">Observaciones</dt>
            <dd class="col-sm-9">{{ $cliente->observaciones ?? '—' }}</dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-estado :estado="$cliente->activo ? 'ACTIVO' : 'INACTIVO'" /></dd>

            <dt class="col-sm-3">Total comprado</dt>
            <dd class="col-sm-9"><x-dinero :monto="$total" /></dd>

            <dt class="col-sm-3">Última compra</dt>
            <dd class="col-sm-9">{{ $ultima?->fecha->format('d/m/Y H:i') ?? '—' }}</dd>
        </dl>
    </x-card>

    <x-card titulo="Historial de compras">
        <div class="table-responsive">
            <table class="table table-striped tabla-nf">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th class="monto">Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                        <tr>
                            <td><a href="{{ route('ventas.ver', $venta) }}">{{ $venta->numero() }}</a></td>
                            <td>{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                            <td class="monto"><x-dinero :monto="$venta->total" /></td>
                            <td><x-estado :estado="$venta->estado" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><x-empty-state mensaje="Sin compras registradas." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $ventas->links() }}
    </x-card>

    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Volver</a>
@endsection
