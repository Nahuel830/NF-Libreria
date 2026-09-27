@extends('layouts.app')

@section('titulo', isset($proveedor) ? 'Editar proveedor' : 'Nuevo proveedor')

@section('contenido')
    <x-page-header :titulo="isset($proveedor) ? 'Editar proveedor' : 'Nuevo proveedor'" :migas="['Inventario' => null, 'Proveedores' => route('proveedores.index'), isset($proveedor) ? 'Editar' : 'Nuevo' => null]" />

    <x-card>
        <form method="POST" action="{{ isset($proveedor) ? route('proveedores.actualizar', $proveedor) : route('proveedores.guardar') }}">
            @csrf
            @if (isset($proveedor))
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $proveedor->nombre ?? '') }}" required maxlength="150">
                @error('nombre')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nit" class="form-label">NIT</label>
                    <input type="text" class="form-control" id="nit" name="nit" value="{{ old('nit', $proveedor->nit ?? '') }}" maxlength="20">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $proveedor->telefono ?? '') }}" maxlength="30">
                </div>
            </div>

            <div class="mb-3">
                <label for="contacto" class="form-label">Contacto</label>
                <input type="text" class="form-control" id="contacto" name="contacto" value="{{ old('contacto', $proveedor->contacto ?? '') }}" maxlength="100">
            </div>

            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion" value="{{ old('direccion', $proveedor->direccion ?? '') }}" maxlength="255">
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="2">{{ old('observaciones', $proveedor->observaciones ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
