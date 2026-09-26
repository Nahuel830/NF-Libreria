@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Usuarios</h1>
        <a href="{{ route('usuarios.crear') }}" class="btn btn-primary">Nuevo usuario</a>
    </div>

    <form method="GET" action="{{ route('usuarios.index') }}" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o usuario">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="rol">
                <option value="">Todos los roles</option>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->value }}" @selected(request('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
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
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Último acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->nombre }}</td>
                        <td>{{ $usuario->usuario }}</td>
                        <td>{{ $usuario->rol->etiqueta() }}</td>
                        <td>
                            @if ($usuario->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>{{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('usuarios.editar', $usuario) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            <a href="{{ route('usuarios.password', $usuario) }}" class="btn btn-sm btn-outline-secondary">Contraseña</a>
                            <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="d-inline"
                                onsubmit="return confirm('¿Confirmas que quieres {{ $usuario->activo ? 'desactivar' : 'activar' }} a {{ $usuario->usuario }}?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-{{ $usuario->activo ? 'danger' : 'success' }}">
                                    {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $usuarios->links() }}
@endsection
