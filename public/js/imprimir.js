// NF Librería: impresión en páginas standalone (tickets, cierre).
// Sin onclick inline (CSP: script-src 'self').
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-imprimir]')) {
            e.preventDefault();
            window.print();
        }
    });
});
