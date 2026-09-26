@extends('layouts.app')

@section('titulo', 'Detalle de auditoría')

@section('contenido')
    <x-page-header titulo="Detalle de auditoría #{{ $registro->id }}" :migas="['Administración' => null, 'Auditoría' => route('auditoria.index'), 'Detalle' => null]">
        <a href="{{ route('auditoria.index') }}" class="btn btn-secondary">Volver</a>
    </x-page-header>

    <x-card>
        <dl class="row mb-0">
            <dt class="col-sm-3">Fecha y hora</dt>
            <dd class="col-sm-9">{{ $registro->created_at->format('d/m/Y H:i') }}</dd>

            <dt class="col-sm-3">Usuario</dt>
            <dd class="col-sm-9">{{ $registro->usuario ? $registro->usuario->nombre.' ('.$registro->usuario->usuario.')' : '—' }}</dd>

            <dt class="col-sm-3">Acción</dt>
            <dd class="col-sm-9">{{ $registro->accion }}</dd>

            <dt class="col-sm-3">Entidad</dt>
            <dd class="col-sm-9">{{ $registro->entidad }}{{ $registro->entidad_id ? ' #'.$registro->entidad_id : '' }}</dd>

            <dt class="col-sm-3">Descripción</dt>
            <dd class="col-sm-9">{{ $registro->descripcion }}</dd>

            <dt class="col-sm-3">IP</dt>
            <dd class="col-sm-9">{{ $registro->ip ?? '—' }}</dd>
        </dl>
    </x-card>

    <x-card titulo="Datos anteriores">
        @include('auditoria._datos', ['datos' => $registro->datos_anteriores])
    </x-card>

    <x-card titulo="Datos nuevos">
        @include('auditoria._datos', ['datos' => $registro->datos_nuevos])
    </x-card>
@endsection
