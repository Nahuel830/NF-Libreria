@extends('layouts.app')

@section('titulo', 'Historial de cajas')

@section('contenido')
    <x-page-header titulo="Historial de cajas" :migas="['Ventas' => null, 'Cajas' => null]" />

    <x-filtros :accion="route('caja.historial')">
        <div class="col-md-3">
            <input type="date" class="form-control" name="desde" value="{{ request('desde') }}" aria-label="Desde">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="usuario_id" aria-label="Usuario">
                <option value="">Todos los usuarios</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>{{ $usuario->usuario }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="estado" aria-label="Estado">
                <option value="">Todos los estados</option>
                <option value="ABIERTA" @selected(request('estado') === 'ABIERTA')>Abierta</option>
                <option value="CERRADA" @selected(request('estado') === 'CERRADA')>Cerrada</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i> Filtrar</button>
        </div>
    </x-filtros>

    <div class="table-responsive">
        <table class="table table-striped tabla-nf">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Usuario</th>
                    <th>Abierta</th>
                    <th>Cerrada</th>
                    <th class="monto">Esperado</th>
                    <th class="monto">Contado</th>
                    <th class="monto">Diferencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cajas as $caja)
                    <tr>
                        <td><a href="{{ route('caja.ver', $caja) }}">#{{ $caja->id }}</a></td>
                        <td>{{ $caja->usuario->usuario }}</td>
                        <td>{{ $caja->abierta_en->format('d/m/Y H:i') }}</td>
                        <td>{{ $caja->cerrada_en?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="monto">{{ $caja->efectivo_esperado !== null ? bs($caja->efectivo_esperado) : '—' }}</td>
                        <td class="monto">{{ $caja->efectivo_contado !== null ? bs($caja->efectivo_contado) : '—' }}</td>
                        <td class="monto">{{ $caja->diferencia !== null ? bs($caja->diferencia) : '—' }}</td>
                        <td><x-estado :estado="$caja->estado" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><x-empty-state mensaje="Sin cajas registradas." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $cajas->links() }}
@endsection
