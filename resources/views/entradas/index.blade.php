@extends('layouts.app')

@section('titulo', 'Entradas de mercadería')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Entradas de mercadería</h1>
        <a href="{{ route('entradas.crear') }}" class="btn btn-primary">Nueva entrada</a>
    </div>

    <form method="GET" action="{{ route('entradas.index') }}" class="row g-2 mb-3">
        <div class="col-md-2">
            <input type="date" class="form-control" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta">
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="proveedor" value="{{ request('proveedor') }}" placeholder="Proveedor">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="estado">
                <option value="">Todos</option>
                <option value="REGISTRADA" @selected(request('estado') === 'REGISTRADA')>Registrada</option>
                <option value="ANULADA" @selected(request('estado') === 'ANULADA')>Anulada</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Documento</th>
                    <th>Ítems</th>
                    <th>Total</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entradas as $entrada)
                    <tr>
                        <td><a href="{{ route('entradas.ver', $entrada) }}">{{ $entrada->numero() }}</a></td>
                        <td>{{ $entrada->fecha->format('d/m/Y H:i') }}</td>
                        <td>{{ $entrada->proveedor ?? '—' }}</td>
                        <td>{{ $entrada->documento_referencia ?? '—' }}</td>
                        <td>{{ $entrada->detalles_count }}</td>
                        <td>{{ bs($entrada->total) }}</td>
                        <td>{{ $entrada->usuario->usuario }}</td>
                        <td>
                            @if ($entrada->estado === 'REGISTRADA')
                                <span class="badge bg-success">Registrada</span>
                            @else
                                <span class="badge bg-danger">Anulada</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No hay entradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $entradas->links() }}
@endsection
