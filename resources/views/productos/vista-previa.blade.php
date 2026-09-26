@extends('layouts.app')

@section('titulo', 'Vista previa de importación')

@section('contenido')
    <h1>Vista previa (sin guardar nada)</h1>

    <p>
        Nuevos: <strong>{{ $resumen['nuevos'] }}</strong> |
        Actualizar: <strong>{{ $resumen['actualizar'] }}</strong> |
        Omitir: <strong>{{ $resumen['omitir'] }}</strong> |
        Errores: <strong>{{ $resumen['errores'] }}</strong>
    </p>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Línea</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio venta</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        <td>{{ $fila['linea'] }}</td>
                        <td>{{ $fila['datos']['codigo'] }}</td>
                        <td>{{ $fila['datos']['nombre'] }}</td>
                        <td>{{ $fila['datos']['categoria'] }}</td>
                        <td>{{ $fila['datos']['precio_venta'] }}</td>
                        <td>
                            @if ($fila['resultado'] === 'Nuevo')
                                <span class="badge bg-success">Nuevo</span>
                            @elseif ($fila['resultado'] === 'Actualizar')
                                <span class="badge bg-primary">Actualizar</span>
                            @elseif ($fila['resultado'] === 'Omitir')
                                <span class="badge bg-secondary">Omitir</span>
                            @else
                                <span class="badge bg-danger">Error: {{ $fila['error'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('productos.importar.confirmar') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button type="submit" class="btn btn-primary">Confirmar importación</button>
        <a href="{{ route('productos.importar') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection
