@extends('layouts.app')

@section('titulo', 'Sesión expirada')

@section('contenido')
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <p class="display-1 text-secondary">419</p>
            <h1 class="h4">Tu sesión expiró.</h1>
            <p class="text-secondary">Vuelve a cargar la página e inténtalo de nuevo.</p>
            <a href="{{ route('login') }}" class="btn btn-primary">Ir al login</a>
        </div>
    </div>
@endsection
