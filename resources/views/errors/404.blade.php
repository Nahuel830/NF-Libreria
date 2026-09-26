@extends('layouts.app')

@section('titulo', 'No encontrado')

@section('contenido')
    <div class="text-center mt-5">
        <h1>404 — No encontrado</h1>
        <p>La página que buscas no existe.</p>
        <a href="{{ auth()->check() ? route('inicio') : route('login') }}" class="btn btn-primary">Volver</a>
    </div>
@endsection
