@props([
    'id' => 'modal-confirmar',
    'titulo' => 'Confirmar',
    'mensaje' => '¿Confirmas esta acción?',
    'textoBoton' => 'Confirmar',
    'color' => 'danger',
    'conMotivo' => false,
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-titulo" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="{{ $id }}-titulo">{{ $titulo }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mensaje">{{ $mensaje }}</p>
                <div class="zona-motivo" @unless ($conMotivo) style="display: none;" @endunless>
                    <label class="form-label" for="{{ $id }}-motivo">Motivo (mínimo 5 caracteres)</label>
                    <textarea class="form-control motivo" id="{{ $id }}-motivo" rows="3"></textarea>
                    <div class="invalid-feedback error-motivo">El motivo debe tener al menos 5 caracteres.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-{{ $color }} confirmar" data-modal="{{ $id }}">{{ $textoBoton }}</button>
            </div>
        </div>
    </div>
</div>
