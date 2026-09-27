// NF Librería: ticket de venta (auto-impresión opcional + Enter = nueva venta).
// Sin JavaScript inline (CSP: script-src 'self').
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.dataset.impresionAutomatica === '1') {
        window.addEventListener('load', () => window.print());
    }

    const btnNueva = document.getElementById('btn-nueva');

    if (btnNueva) {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                btnNueva.click();
            }
        });
    }
});
