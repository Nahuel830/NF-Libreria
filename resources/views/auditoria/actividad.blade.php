@extends('layouts.app')

@section('titulo', 'Actividad reciente de accesos')

@section('contenido')
    <x-page-header titulo="Actividad reciente de accesos" subtitulo="Últimos inicios de sesión correctos y fallidos." :migas="['Administración' => null, 'Auditoría' => route('auditoria.index'), 'Actividad' => null]" />

    <x-filtros :accion="route('auditoria.actividad')">
        <div class="col-md-4">
            <select class="form-select" name="usuario_id" aria-label="Usuario">
                <option value="">Todos los usuarios</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>
                        {{ $usuario->nombre }} ({{ $usuario->usuario }})
                    </option>
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
                    <th>Resultado</th>
                    <th>Descripción</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td>{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $registro->usuario?->usuario ?? '—' }}</td>
                        <td>
                            @if ($registro->accion === 'LOGIN')
                                <span class="badge bg-success">Correcto</span>
                            @else
                                <span class="badge bg-danger">Fallido</span>
                            @endif
                        </td>
                        <td>{{ $registro->descripcion }}</td>
                        <td>{{ $registro->ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><x-empty-state mensaje="No hay accesos registrados." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $registros->links() }}
@endsection
