@extends('layouts.app')

@section('titulo', 'Detalle de auditoría')

@section('contenido')
    <h1>Detalle de auditoría #{{ $registro->id }}</h1>

    <dl class="row">
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

    <h2 class="h5">Datos anteriores</h2>
    @include('auditoria._datos', ['datos' => $registro->datos_anteriores])

    <h2 class="h5 mt-3">Datos nuevos</h2>
    @include('auditoria._datos', ['datos' => $registro->datos_nuevos])

    <a href="{{ route('auditoria.index') }}" class="btn btn-secondary mt-3">Volver</a>
@endsection
