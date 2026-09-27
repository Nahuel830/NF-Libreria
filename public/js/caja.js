// Cierre de caja: suma automática del conteo por denominación.
document.addEventListener('DOMContentLoaded', () => {
    const conteos = document.querySelectorAll('.conteo');
    const contadoEl = document.getElementById('contado');
    const diferenciaEl = document.getElementById('diferencia');
    const esperadoEl = document.getElementById('esperado');

    if (conteos.length === 0 || !contadoEl) {
        return;
    }

    const formatear = (valor) => `Bs. ${valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

    const recalcular = () => {
        let contado = 0;

        conteos.forEach((input) => {
            contado += (parseInt(input.value, 10) || 0) * parseFloat(input.dataset.valor);
        });

        const esperado = parseFloat(esperadoEl.dataset.valor || '0');

        contadoEl.textContent = formatear(contado);
        diferenciaEl.textContent = formatear(contado - esperado);
    };

    conteos.forEach((input) => input.addEventListener('input', recalcular));
    recalcular();
});
