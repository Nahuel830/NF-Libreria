@extends('layouts.app')

@section('titulo', 'Entradas de mercadería')

@section('contenido')
    <x-page-header titulo="Entradas de mercadería" :migas="['Inventario' => null, 'Entradas' => null]">
        <a href="{{ route('entradas.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva entrada</a>
    </x-page-header>

    <x-filtros :accion="route('entradas.index')">
        <div class="col-md-2">
            <input type="date" class="form-control" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="hasta" value="{{ request('hasta') }}" aria-label="Hasta">
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="proveedor" value="{{ request('proveedor') }}" placeholder="Proveedor" aria-label="Proveedor">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="estado" aria-label="Estado">
                <option value="">Todos</option>
                <option value="REGISTRADA" @selected(request('estado') === 'REGISTRADA')>Registrada</option>
                <option value="ANULADA" @selected(request('estado') === 'ANULADA')>Anulada</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Filtrar</button>
        </div>
    </x-filtros>

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Documento</th>
                    <th>Ítems</th>
                    <th class="monto">Total</th>
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
                        <td class="monto"><x-dinero :monto="$entrada->total" /></td>
                        <td>{{ $entrada->usuario->usuario }}</td>
                        <td><x-estado :estado="$entrada->estado" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><x-empty-state mensaje="Todavía no hay entradas registradas." :accion-url="route('entradas.crear')" accion-texto="Nueva entrada" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $entradas->links() }}
@endsection
