@extends('layouts.app')

@section('titulo', 'Importación terminada')

@section('contenido')
    <h1>Importación terminada</h1>

    <ul>
        <li>Nuevos: <strong>{{ $resumen['nuevos'] }}</strong></li>
        <li>Actualizados: <strong>{{ $resumen['actualizados'] }}</strong></li>
        <li>Omitidos: <strong>{{ $resumen['omitidos'] }}</strong></li>
        <li>Con error: <strong>{{ $resumen['errores'] }}</strong></li>
    </ul>

    <a href="{{ route('productos.index') }}" class="btn btn-primary">Ver productos</a>
@endsection
