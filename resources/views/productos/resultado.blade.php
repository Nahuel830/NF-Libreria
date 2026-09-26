@extends('layouts.app')

@section('titulo', 'Importación terminada')

@section('contenido')
    <x-page-header titulo="Importación terminada" :migas="['Inventario' => null, 'Productos' => route('productos.index'), 'Importar' => null]" />

    <x-card>
        <ul class="mb-3">
            <li>Nuevos: <strong>{{ $resumen['nuevos'] }}</strong></li>
            <li>Actualizados: <strong>{{ $resumen['actualizados'] }}</strong></li>
            <li>Omitidos: <strong>{{ $resumen['omitidos'] }}</strong></li>
            <li>Con error: <strong>{{ $resumen['errores'] }}</strong></li>
        </ul>

        <a href="{{ route('productos.index') }}" class="btn btn-primary">Ver productos</a>
    </x-card>
@endsection
