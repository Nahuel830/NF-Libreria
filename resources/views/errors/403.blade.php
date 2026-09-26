@extends('layouts.app')

@section('titulo', 'Sin permiso')

@section('contenido')
    <div class="text-center mt-5">
        <i class="bi bi-shield-lock fs-1 text-danger"></i>
        <h1>403 — Sin permiso</h1>
        <p>No tienes permiso para acceder a esta sección.</p>
        <a href="{{ auth()->check() ? route('inicio') : route('login') }}" class="btn btn-primary">Volver</a>
    </div>
@endsection
