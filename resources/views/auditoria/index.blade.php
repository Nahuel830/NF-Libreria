@extends('layouts.app')

@section('titulo', 'Auditoría')

@section('contenido')
    <h1>Auditoría</h1>

    <form method="GET" action="{{ route('auditoria.index') }}" class="row g-2 mb-3">
        <div class="col-md-2">
            <input type="date" class="form-control" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="usuario_id">
                <option value="">Todos los usuarios</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>
                        {{ $usuario->nombre }} ({{ $usuario->usuario }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="accion">
                <option value="">Todas las acciones</option>
                @foreach ($acciones as $accion)
                    <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ $accion }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>Descripción</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td>{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $registro->usuario?->usuario ?? '—' }}</td>
                        <td><span class="badge bg-info text-dark">{{ $registro->accion }}</span></td>
                        <td>{{ $registro->entidad }}{{ $registro->entidad_id ? ' #'.$registro->entidad_id : '' }}</td>
                        <td>{{ $registro->descripcion }}</td>
                        <td>{{ $registro->ip ?? '—' }}</td>
                        <td>
                            <a href="{{ route('auditoria.ver', $registro) }}" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No hay registros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $registros->links() }}
@endsection
