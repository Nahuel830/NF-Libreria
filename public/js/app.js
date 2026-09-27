// NF Librería: toasts de alertas y modal de confirmación reutilizable.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toasts-nf .toast').forEach((elemento) => {
        bootstrap.Toast.getOrCreateInstance(elemento).show();
    });

    // Botones con data-imprimir (CSP: sin onclick inline).
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-imprimir]')) {
            e.preventDefault();
            window.print();
        }
    });

    // Campos con data-envio-automatico (CSP: sin onchange inline).
    document.addEventListener('change', (e) => {
        const campo = e.target.closest('[data-envio-automatico]');

        if (campo && campo.form) {
            campo.form.submit();
        }
    });

    const modalEl = document.getElementById('modal-confirmar');

    if (!modalEl) {
        return;
    }

    const modal = new bootstrap.Modal(modalEl);
    const tituloEl = modalEl.querySelector('.modal-title');
    const mensajeEl = modalEl.querySelector('.mensaje');
    const zonaMotivo = modalEl.querySelector('.zona-motivo');
    const motivoEl = modalEl.querySelector('.motivo');
    const botonOk = modalEl.querySelector('.confirmar');
    let alConfirmar = null;
    let pideMotivo = false;

    const abrir = ({ titulo, mensaje, textoBoton, color, conMotivo, callback }) => {
        tituloEl.textContent = titulo;
        mensajeEl.textContent = mensaje;
        botonOk.textContent = textoBoton;
        botonOk.className = `btn btn-${color} confirmar`;
        pideMotivo = conMotivo;

        if (zonaMotivo) {
            zonaMotivo.style.display = conMotivo ? '' : 'none';
        }

        if (motivoEl) {
            motivoEl.value = '';
            motivoEl.classList.remove('is-invalid');
        }

        botonOk.disabled = false;
        alConfirmar = callback;
        modal.show();
    };

    window.pedirConfirmacion = (opciones) => abrir({
        titulo: 'Confirmar',
        textoBoton: 'Confirmar',
        color: 'danger',
        conMotivo: false,
        ...opciones,
    });

    botonOk.addEventListener('click', () => {
        let motivo = '';

        if (pideMotivo && motivoEl) {
            motivo = motivoEl.value.trim();

            if (motivo.length < 5) {
                motivoEl.classList.add('is-invalid');
                return;
            }
        }

        botonOk.disabled = true;
        modal.hide();

        if (alConfirmar) {
            const callback = alConfirmar;
            alConfirmar = null;
            callback(motivo);
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        botonOk.disabled = false;
    });

    // Formularios con data-confirm="mensaje" (y data-motivo para pedir motivo).
    document.addEventListener('submit', (e) => {
        const formulario = e.target;

        if (!(formulario instanceof HTMLFormElement)) {
            return;
        }

        if (formulario.dataset.confirmado === '1' || !formulario.dataset.confirm) {
            return;
        }

        e.preventDefault();

        abrir({
            titulo: formulario.dataset.tituloConfirm || 'Confirmar',
            mensaje: formulario.dataset.confirm,
            textoBoton: formulario.dataset.textoConfirm || 'Confirmar',
            color: formulario.dataset.colorConfirm || 'danger',
            conMotivo: formulario.hasAttribute('data-motivo'),
            callback: (motivo) => {
                if (formulario.hasAttribute('data-motivo')) {
                    let oculto = formulario.querySelector('input[name="motivo"]');

                    if (!oculto) {
                        oculto = document.createElement('input');
                        oculto.type = 'hidden';
                        oculto.name = 'motivo';
                        formulario.appendChild(oculto);
                    }

                    oculto.value = motivo;
                }

                formulario.dataset.confirmado = '1';
                formulario.submit();
            },
        });
    });
});
