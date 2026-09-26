@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <h1>Bienvenido, {{ auth()->user()->nombre }}</h1>
    <p>Tu rol es: {{ auth()->user()->rol->etiqueta() }}</p>
@endsection
