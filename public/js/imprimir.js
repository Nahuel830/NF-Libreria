// NF Librería: impresión y envío automático en páginas standalone.
// Sin JavaScript inline (CSP: script-src 'self').
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-imprimir]')) {
            e.preventDefault();
            window.print();
        }
    });

    document.addEventListener('change', (e) => {
        const campo = e.target.closest('[data-envio-automatico]');

        if (campo && campo.form) {
            campo.form.submit();
        }
    });
});
