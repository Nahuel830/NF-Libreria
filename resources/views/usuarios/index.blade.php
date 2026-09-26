@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <x-page-header titulo="Usuarios" :migas="['Administración' => null, 'Usuarios' => null]">
        <a href="{{ route('usuarios.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo usuario</a>
    </x-page-header>

    <x-filtros :accion="route('usuarios.index')">
        <div class="col-md-4">
            <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o usuario" aria-label="Buscar">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="rol" aria-label="Rol">
                <option value="">Todos los roles</option>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->value }}" @selected(request('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
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
                        <td><x-estado :estado="$usuario->activo ? 'ACTIVO' : 'INACTIVO'" /></td>
                        <td>{{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('usuarios.editar', $usuario) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('usuarios.password', $usuario) }}" class="btn btn-sm btn-outline-secondary" title="Restablecer contraseña"><i class="bi bi-key"></i></a>
                            <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="d-inline"
                                data-confirm="¿Confirmas que quieres {{ $usuario->activo ? 'desactivar' : 'activar' }} a {{ $usuario->usuario }}?"
                                data-texto-confirm="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-{{ $usuario->activo ? 'danger' : 'success' }}" title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}">
                                    <i class="bi bi-{{ $usuario->activo ? 'person-dash' : 'person-check' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"><x-empty-state mensaje="Todavía no hay usuarios registrados." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $usuarios->links() }}
@endsection
