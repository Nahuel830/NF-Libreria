@extends('layouts.app')

@section('titulo', 'Inventario inicial / conteo físico')

@section('contenido')
    <x-page-header titulo="Inventario inicial / conteo físico" subtitulo="Cuenta por categoría y guarda: solo se ajusta lo que cambió." :migas="['Inventario' => null, 'Conteo' => null]">
        <a href="{{ route('inventario.conteo.hoja', ['categoria_id' => $categoriaId]) }}" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Hoja de conteo (CSV)</a>
    </x-page-header>

    <form method="GET" action="{{ route('inventario.conteo') }}" class="row g-2 mb-3">
        <div class="col-md-6">
            <select class="form-select" name="categoria_id" aria-label="Categoría" data-envio-automatico>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) $categoriaId === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <form method="POST" action="{{ route('inventario.conteo.guardar') }}">
        @csrf

        <div class="mb-3">
            <label for="motivo" class="form-label">Motivo (opcional, por defecto "Conteo físico inicial")</label>
            <input type="text" class="form-control" id="motivo" name="motivo" value="{{ old('motivo') }}" maxlength="255">
        </div>

        <div class="table-responsive">
            <table class="table table-striped tabla-nf">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Stock sistema</th>
                        <th>Cantidad contada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr>
                            <td>{{ $producto->codigo }}</td>
                            <td>{{ $producto->nombre }}</td>
                            <td>{{ $producto->stock }}</td>
                            <td>
                                <input type="number" min="0" step="1" class="form-control" name="conteos[{{ $producto->id }}]" value="{{ old('conteos.'.$producto->id) }}">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><x-empty-state mensaje="Sin productos en esta categoría." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $productos->links() }}

        <button type="submit" class="btn btn-primary">Guardar conteo</button>
    </form>
@endsection
