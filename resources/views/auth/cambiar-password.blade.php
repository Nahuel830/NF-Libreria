@extends('layouts.app')

@section('titulo', 'Cambiar contraseña')

@section('contenido')
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title h4 text-center mb-4">Cambiar contraseña</h1>

                    <form method="POST" action="{{ route('password.actualizar') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="actual" class="form-label">Contraseña actual</label>
                            <input type="password" class="form-control" id="actual" name="actual"
                                required autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label for="nueva" class="form-label">Contraseña nueva (mínimo 8 caracteres)</label>
                            <input type="password" class="form-control" id="nueva" name="nueva"
                                required autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label for="nueva_confirmation" class="form-label">Confirmar contraseña nueva</label>
                            <input type="password" class="form-control" id="nueva_confirmation" name="nueva_confirmation"
                                required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
