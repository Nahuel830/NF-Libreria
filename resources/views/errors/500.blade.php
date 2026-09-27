@extends('layouts.app')

@section('titulo', 'Error interno')

@section('contenido')
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <p class="display-1 text-secondary">500</p>
            <h1 class="h4">Ocurrió un error interno.</h1>
            <p class="text-secondary">Inténtalo de nuevo; si sigue pasando, avisa al administrador.</p>
            <a href="{{ route('inicio') }}" class="btn btn-primary">Ir al inicio</a>
        </div>
    </div>
@endsection
