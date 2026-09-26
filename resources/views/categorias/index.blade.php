@extends('layouts.app')

@section('titulo', 'Categorías')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Categorías</h1>
        <a href="{{ route('categorias.crear') }}" class="btn btn-primary">Nueva categoría</a>
    </div>

    <form method="GET" action="{{ route('categorias.index') }}" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre">
        </div>
        <div class="col-md-4">
            <select class="form-select" name="estado">
                <option value="">Todos los estados</option>
                <option value="1" @selected(request('estado') === '1')>Activo</option>
                <option value="0" @selected(request('estado') === '0')>Inactivo</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Buscar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
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
                        <td>0</td>
                        <td>
                            @if ($categoria->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('categorias.editar', $categoria) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            <form method="POST" action="{{ route('categorias.estado', $categoria) }}" class="d-inline"
                                onsubmit="return confirm('¿Confirmas que quieres {{ $categoria->activo ? 'desactivar' : 'activar' }} la categoría {{ $categoria->nombre }}?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-{{ $categoria->activo ? 'danger' : 'success' }}">
                                    {{ $categoria->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No hay categorías.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $categorias->links() }}
@endsection
