@extends('layouts.app')

@section('titulo', 'NF Librería')

@section('contenido')
    <h1>NF Librería — sistema en desarrollo</h1>
    <p>Fecha y hora del servidor: {{ now()->format('d/m/Y H:i') }}</p>
@endsection
