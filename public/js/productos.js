// Sugerencia de código de producto según la categoría seleccionada.
document.addEventListener('DOMContentLoaded', () => {
    const boton = document.getElementById('btn-sugerir');
    const categoria = document.getElementById('categoria_id');
    const codigo = document.getElementById('codigo');

    if (!boton || !categoria || !codigo) {
        return;
    }

    boton.addEventListener('click', async () => {
        if (!categoria.value) {
            return;
        }

        const respuesta = await fetch(`/productos/sugerir-codigo?categoria_id=${encodeURIComponent(categoria.value)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            return;
        }

        const datos = await respuesta.json();

        if (datos.codigo) {
            codigo.value = datos.codigo;
        }
    });

    // El Enter del lector no debe enviar el formulario.
    const barras = document.getElementById('codigo_barras');

    if (barras) {
        barras.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }
});
