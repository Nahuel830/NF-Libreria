@extends('layouts.app')

@section('titulo', 'Importar productos')

@section('contenido')
    <h1>Importar productos desde Excel/CSV</h1>

    <p>
        <a href="{{ route('productos.importar.plantilla') }}" class="btn btn-outline-secondary">Descargar plantilla</a>
    </p>

    <form method="POST" action="{{ route('productos.importar.vista-previa') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="archivo" class="form-label">Archivo .csv (máximo 2 MB)</label>
            <input type="file" class="form-control" id="archivo" name="archivo" accept=".csv" required>
        </div>

        <div class="mb-3">
            <label for="si_existe" class="form-label">Si el código ya existe</label>
            <select class="form-select" id="si_existe" name="si_existe" required>
                <option value="omitir" @selected(old('si_existe') === 'omitir')>Omitir</option>
                <option value="actualizar" @selected(old('si_existe') === 'actualizar')>Actualizar datos (nunca el stock)</option>
            </select>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="crear_categorias" name="crear_categorias" value="1" @checked(old('crear_categorias', true))>
            <label class="form-check-label" for="crear_categorias">Crear categorías que no existan</label>
        </div>

        <button type="submit" class="btn btn-primary">Ver vista previa</button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
    </form>
@endsection
