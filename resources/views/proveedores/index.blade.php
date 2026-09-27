@extends('layouts.app')

@section('titulo', 'Proveedores')

@section('contenido')
    <x-page-header titulo="Proveedores" :migas="['Inventario' => null, 'Proveedores' => null]">
        <a href="{{ route('proveedores.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo proveedor</a>
    </x-page-header>

    <x-filtros :accion="route('proveedores.index')">
        <div class="col-md-6">
            <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre" aria-label="Buscar">
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
                    <th>Teléfono</th>
                    <th>Entradas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proveedores as $proveedor)
                    <tr>
                        <td><a href="{{ route('proveedores.ver', $proveedor) }}">{{ $proveedor->nombre }}</a></td>
                        <td>{{ $proveedor->telefono ?? '—' }}</td>
                        <td>{{ $proveedor->entradas_count }}</td>
                        <td><x-estado :estado="$proveedor->activo ? 'ACTIVO' : 'INACTIVO'" /></td>
                        <td>
                            <a href="{{ route('proveedores.editar', $proveedor) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('proveedores.estado', $proveedor) }}" class="d-inline"
                                data-confirm="¿Confirmas que quieres {{ $proveedor->activo ? 'desactivar' : 'activar' }} a {{ $proveedor->nombre }}?"
                                data-texto-confirm="{{ $proveedor->activo ? 'Desactivar' : 'Activar' }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-{{ $proveedor->activo ? 'danger' : 'success' }}" title="{{ $proveedor->activo ? 'Desactivar' : 'Activar' }}">
                                    <i class="bi bi-{{ $proveedor->activo ? 'eye-slash' : 'eye' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><x-empty-state mensaje="Todavía no hay proveedores registrados." :accion-url="route('proveedores.crear')" accion-texto="Nuevo proveedor" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $proveedores->links() }}
@endsection
