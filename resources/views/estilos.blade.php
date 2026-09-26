@extends('layouts.app')

@section('titulo', 'Guía de estilos')

@section('contenido')
    <x-page-header titulo="Guía de estilos" subtitulo="Solo visible en desarrollo. Muestra todos los componentes juntos." />

    <x-card titulo="Colores">
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-primary">Principal</span>
            <span class="badge bg-success">Éxito</span>
            <span class="badge bg-danger">Error</span>
            <span class="badge bg-warning text-dark">Advertencia</span>
            <span class="badge bg-secondary">Secundario</span>
            <span class="badge bg-info text-dark">Info</span>
        </div>
    </x-card>

    <x-card titulo="Botones">
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary">Guardar</button>
            <button class="btn btn-secondary">Cancelar</button>
            <button class="btn btn-success">Cobrar</button>
            <button class="btn btn-danger">Anular</button>
            <button class="btn btn-warning">Ajustar</button>
            <button class="btn btn-outline-primary">Secundario</button>
        </div>
    </x-card>

    <x-card titulo="Badges de estado">
        <div class="d-flex flex-wrap gap-2">
            <x-estado estado="ACTIVO" />
            <x-estado estado="COMPLETADA" />
            <x-estado estado="REGISTRADA" />
            <x-estado estado="INACTIVO" />
            <x-estado estado="ANULADA" />
            <x-estado estado="STOCK BAJO" />
            <x-estado estado="SIN STOCK" />
        </div>
    </x-card>

    <x-card titulo="Montos">
        <p class="mb-1"><x-dinero :monto="'12.50'" /></p>
        <p class="mb-0"><x-dinero :monto="'1234.50'" /></p>
    </x-card>

    <x-card titulo="Formulario">
        <div class="mb-3">
            <label class="form-label" for="ejemplo">Campo obligatorio *</label>
            <input type="text" class="form-control" id="ejemplo" placeholder="Ejemplo">
        </div>
        <div class="mb-3">
            <label class="form-label" for="ejemplo-error">Campo con error *</label>
            <input type="text" class="form-control is-invalid" id="ejemplo-error" value="mal">
            <div class="text-danger small mt-1">Mensaje de error en rojo bajo el campo.</div>
        </div>
        <button class="btn btn-primary">Guardar</button>
        <button class="btn btn-secondary">Cancelar</button>
    </x-card>

    <x-card titulo="Tabla">
        <div class="table-responsive">
            <table class="table table-striped tabla-nf">
                <thead>
                    <tr><th>Código</th><th>Nombre</th><th class="monto">Precio</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>CUA-001</td>
                        <td>Cuaderno</td>
                        <td class="monto"><x-dinero :monto="'12.50'" /></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger" title="Desactivar"><i class="bi bi-eye-slash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card titulo="Estado vacío">
        <x-empty-state mensaje="Todavía no hay registros." accion-url="#" accion-texto="Crear uno" />
    </x-card>

    <x-card titulo="Modal de confirmación">
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-confirmar">Abrir confirmación</button>
    </x-card>
@endsection
