@extends('layouts.app')

@section('titulo', 'Demasiados intentos')

@section('contenido')
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <p class="display-1 text-secondary">429</p>
            <h1 class="h4">Demasiados intentos.</h1>
            <p class="text-secondary">Espera un momento e inténtalo de nuevo.</p>
            <a href="{{ route('inicio') }}" class="btn btn-primary">Ir al inicio</a>
        </div>
    </div>
@endsection
