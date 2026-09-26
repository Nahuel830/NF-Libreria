@extends('layouts.app')

@section('titulo', 'Error del servidor')

@section('contenido')
    <div class="text-center mt-5">
        <i class="bi bi-exclamation-octagon fs-1 text-danger"></i>
        <h1>500 — Error del servidor</h1>
        <p>Ocurrió un problema inesperado. Inténtalo de nuevo y avisa al administrador si continúa.</p>
        <a href="{{ auth()->check() ? route('inicio') : route('login') }}" class="btn btn-primary">Volver</a>
    </div>
@endsection
