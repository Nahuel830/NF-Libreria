@extends('layouts.app')

@section('titulo', 'Categorías')

@section('contenido')
    <x-page-header titulo="Categorías" :migas="['Inventario' => null, 'Categorías' => null]">
        <a href="{{ route('categorias.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva categoría</a>
    </x-page-header>

    <x-filtros :accion="route('categorias.index')">
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
                    <th>Descripción</th>
                    <th>Productos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categorias as $categoria)
                    <tr>
                        <td>{{ $categoria->nombre }}</td>
                        <td>{{ $categoria->descripcion ?? '—' }}</td>
                        <td>{{ $categoria->productos_count }}</td>
                        <td><x-estado :estado="$categoria->activo ? 'ACTIVO' : 'INACTIVO'" /></td>
                        <td>
                            <a href="{{ route('categorias.editar', $categoria) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('categorias.estado', $categoria) }}" class="d-inline"
                                data-confirm="¿Confirmas que quieres {{ $categoria->activo ? 'desactivar' : 'activar' }} la categoría {{ $categoria->nombre }}?"
                                data-texto-confirm="{{ $categoria->activo ? 'Desactivar' : 'Activar' }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-{{ $categoria->activo ? 'danger' : 'success' }}" title="{{ $categoria->activo ? 'Desactivar' : 'Activar' }}">
                                    <i class="bi bi-{{ $categoria->activo ? 'eye-slash' : 'eye' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><x-empty-state mensaje="Todavía no hay categorías registradas." :accion-url="route('categorias.crear')" accion-texto="Nueva categoría" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $categorias->links() }}
@endsection
