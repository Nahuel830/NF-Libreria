@extends('layouts.app')

@section('titulo', 'Clientes')

@section('contenido')
    <x-page-header titulo="Clientes" :migas="['Ventas' => null, 'Clientes' => null]">
        <a href="{{ route('clientes.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo cliente</a>
    </x-page-header>

    <x-filtros :accion="route('clientes.index')">
        <div class="col-md-6">
            <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o CI/NIT" aria-label="Buscar">
        </div>
        <div class="col-md-4">
            <select class="form-select" name="estado" aria-label="Estado">
                <option value="">Todos los estados</option>
                <option value="1" @selected(request('estado') === '1')>Activo</option>
                <option value="0" @selected(request('estado') === '0')>Inactivo</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Buscar</button>
        </div>
    </x-filtros>

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>CI/NIT</th>
                    <th>Teléfono</th>
                    <th>Compras</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clientes as $cliente)
                    <tr>
                        <td><a href="{{ route('clientes.ver', $cliente) }}">{{ $cliente->nombre }}</a></td>
                        <td>{{ $cliente->ci_nit ?? '—' }}</td>
                        <td>{{ $cliente->telefono ?? '—' }}</td>
                        <td>{{ $cliente->ventas_count }}</td>
                        <td><x-estado :estado="$cliente->activo ? 'ACTIVO' : 'INACTIVO'" /></td>
                        <td>
                            <a href="{{ route('clientes.editar', $cliente) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('clientes.estado', $cliente) }}" class="d-inline"
                                data-confirm="¿Confirmas que quieres {{ $cliente->activo ? 'desactivar' : 'activar' }} a {{ $cliente->nombre }}?"
                                data-texto-confirm="{{ $cliente->activo ? 'Desactivar' : 'Activar' }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-{{ $cliente->activo ? 'danger' : 'success' }}" title="{{ $cliente->activo ? 'Desactivar' : 'Activar' }}">
                                    <i class="bi bi-{{ $cliente->activo ? 'eye-slash' : 'eye' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"><x-empty-state mensaje="Todavía no hay clientes registrados." :accion-url="route('clientes.crear')" accion-texto="Nuevo cliente" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $clientes->links() }}
@endsection
