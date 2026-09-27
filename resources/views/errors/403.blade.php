@extends('layouts.app')

@section('titulo', 'Sin permiso')

@section('contenido')
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <p class="display-1 text-secondary">403</p>
            <h1 class="h4">No tienes permiso para acceder a esta sección.</h1>
            <p class="text-secondary">Si crees que deberías poder entrar, pide al administrador que revise tu usuario.</p>
            <a href="{{ route('inicio') }}" class="btn btn-primary">Ir al inicio</a>
        </div>
    </div>
@endsection
