@extends('layouts.app')

@section('titulo', 'Etiquetas de código de barras')

@section('contenido')
    <x-page-header titulo="Etiquetas de código de barras" :migas="['Inventario' => null, 'Productos' => route('productos.index'), 'Etiquetas' => null]">
        <button type="button" class="btn btn-primary no-imprimir" onclick="window.print();"><i class="bi bi-printer"></i> Imprimir</button>
    </x-page-header>

    <form method="GET" action="{{ route('productos.etiquetas') }}" class="row g-2 mb-3 no-imprimir">
        <div class="col-md-4">
            <select class="form-select" name="categoria_id" aria-label="Categoría">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) request('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="filas" value="{{ $filas }}" min="1" max="20" aria-label="Filas">
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="columnas" value="{{ $columnas }}" min="1" max="5" aria-label="Columnas">
        </div>
        <div class="col-md-2">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="solo_sin_barras" value="1" id="solo_sin_barras" @checked(request()->boolean('solo_sin_barras'))>
                <label class="form-check-label" for="solo_sin_barras">Solo sin código</label>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Ver</button>
        </div>
    </form>

    <style>
        .hoja-etiquetas { display: grid; grid-template-columns: repeat({{ $columnas }}, 1fr); gap: 8px; }
        .etiqueta { border: 1px dashed #999; padding: 6px; text-align: center; overflow: hidden; }
        .etiqueta svg { max-width: 100%; height: 48px; }
        @media print {
            @page { size: A4; margin: 10mm; }
        }
    </style>

    <div class="hoja-etiquetas">
        @forelse ($etiquetas as $etiqueta)
            <div class="etiqueta">
                <div class="small">{{ $etiqueta['nombre'] }}</div>
                {!! $etiqueta['svg'] !!}
                <div class="small">{{ $etiqueta['codigo'] }}</div>
            </div>
        @empty
            <p class="text-secondary">Sin productos para mostrar.</p>
        @endforelse
    </div>
@endsection
