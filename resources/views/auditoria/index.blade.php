@extends('layouts.app')

@section('titulo', 'Auditoría')

@section('contenido')
    <x-page-header titulo="Auditoría" subtitulo="Solo lectura: los registros no se editan ni se borran." :migas="['Administración' => null, 'Auditoría' => null]" />

    <x-filtros :accion="route('auditoria.index')">
        <div class="col-md-2">
            <input type="date" class="form-control" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="usuario_id" aria-label="Usuario">
                <option value="">Todos los usuarios</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>
                        {{ $usuario->nombre }} ({{ $usuario->usuario }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="accion" aria-label="Acción">
                <option value="">Todas las acciones</option>
                @foreach ($acciones as $accion)
                    <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ $accion }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Filtrar</button>
        </div>
    </x-filtros>

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>Descripción</th>
                    <th>IP</th>
                    <th>Acciones</th>
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
                            <a href="{{ route('auditoria.ver', $registro) }}" class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-empty-state mensaje="No hay registros de auditoría." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $registros->links() }}
@endsection
