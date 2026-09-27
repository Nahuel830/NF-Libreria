@extends('layouts.app')

@section('titulo', 'Proveedor ' . $proveedor->nombre)

@section('contenido')
    <x-page-header :titulo="$proveedor->nombre" :migas="['Inventario' => null, 'Proveedores' => route('proveedores.index'), $proveedor->nombre => null]">
        <a href="{{ route('proveedores.editar', $proveedor) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
    </x-page-header>

    <x-card>
        <dl class="row mb-0">
            <dt class="col-sm-3">NIT</dt>
            <dd class="col-sm-9">{{ $proveedor->nit ?? '—' }}</dd>

            <dt class="col-sm-3">Contacto</dt>
            <dd class="col-sm-9">{{ $proveedor->contacto ?? '—' }}</dd>

            <dt class="col-sm-3">Teléfono</dt>
            <dd class="col-sm-9">{{ $proveedor->telefono ?? '—' }}</dd>

            <dt class="col-sm-3">Dirección</dt>
            <dd class="col-sm-9">{{ $proveedor->direccion ?? '—' }}</dd>

            <dt class="col-sm-3">Observaciones</dt>
            <dd class="col-sm-9">{{ $proveedor->observaciones ?? '—' }}</dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><x-estado :estado="$proveedor->activo ? 'ACTIVO' : 'INACTIVO'" /></dd>

            <dt class="col-sm-3">Total comprado</dt>
            <dd class="col-sm-9"><x-dinero :monto="$total" /></dd>
        </dl>
    </x-card>

    <x-card titulo="Productos que suele proveer">
        <ul class="mb-0">
            @forelse ($productos as $producto)
                <li>{{ $producto->codigo }} — {{ $producto->nombre }} (último costo: <x-dinero :monto="$producto->ultimo_costo" />)</li>
            @empty
                <li>Sin compras registradas.</li>
            @endforelse
        </ul>
    </x-card>

    <x-card titulo="Historial de entradas">
        <div class="table-responsive">
            <table class="table table-striped tabla-nf">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Ítems</th>
                        <th class="monto">Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entradas as $entrada)
                        <tr>
                            <td><a href="{{ route('entradas.ver', $entrada) }}">{{ $entrada->numero() }}</a></td>
                            <td>{{ $entrada->fecha->format('d/m/Y H:i') }}</td>
                            <td>{{ $entrada->detalles_count }}</td>
                            <td class="monto"><x-dinero :monto="$entrada->total" /></td>
                            <td><x-estado :estado="$entrada->estado" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><x-empty-state mensaje="Sin entradas de este proveedor." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $entradas->links() }}
    </x-card>

    <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Volver</a>
@endsection
