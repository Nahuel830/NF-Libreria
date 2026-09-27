// NF Librería: gráfico de ventas del panel (datos por JSON, sin inline).
document.addEventListener('DOMContentLoaded', () => {
    const datosEl = document.getElementById('datos-grafico');
    const lienzo = document.getElementById('grafico-ventas');

    if (!datosEl || !lienzo || typeof Chart === 'undefined') {
        return;
    }

    const datos = JSON.parse(datosEl.textContent);

    new Chart(lienzo, {
        type: 'bar',
        data: {
            labels: datos.etiquetas,
            datasets: [{
                label: 'Ventas (Bs.)',
                data: datos.valores,
                backgroundColor: '#1F4E79',
                borderColor: '#163A5C',
            }],
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } },
    });
});
