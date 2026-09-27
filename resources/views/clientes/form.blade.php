@extends('layouts.app')

@section('titulo', isset($cliente) ? 'Editar cliente' : 'Nuevo cliente')

@section('contenido')
    <x-page-header :titulo="isset($cliente) ? 'Editar cliente' : 'Nuevo cliente'" :migas="['Ventas' => null, 'Clientes' => route('clientes.index'), isset($cliente) ? 'Editar' : 'Nuevo' => null]" />

    <x-card>
        <form method="POST" action="{{ isset($cliente) ? route('clientes.actualizar', $cliente) : route('clientes.guardar') }}">
            @csrf
            @if (isset($cliente))
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $cliente->nombre ?? '') }}" required maxlength="150">
                @error('nombre')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ci_nit" class="form-label">CI/NIT</label>
                    <input type="text" class="form-control @error('ci_nit') is-invalid @enderror" id="ci_nit" name="ci_nit" value="{{ old('ci_nit', $cliente->ci_nit ?? '') }}" maxlength="20">
                    @error('ci_nit')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono', $cliente->telefono ?? '') }}" maxlength="30">
                    @error('telefono')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $cliente->email ?? '') }}" maxlength="255">
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="2">{{ old('observaciones', $cliente->observaciones ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </x-card>
@endsection
