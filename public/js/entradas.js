// Entradas de mercadería: buscador de productos y tabla de ítems editable.
document.addEventListener('DOMContentLoaded', () => {
    const buscador = document.getElementById('buscador');
    const resultados = document.getElementById('resultados');
    const items = document.getElementById('items');
    const totalEl = document.getElementById('total');
    const formulario = document.getElementById('form-entrada');
    const boton = document.getElementById('btn-registrar');

    if (!buscador || !items) {
        return;
    }

    let indice = 0;
    let temporizador = null;

    const formatear = (valor) => `Bs. ${valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

    const recalcular = () => {
        let total = 0;

        items.querySelectorAll('tr[data-indice]').forEach((fila) => {
            const cantidad = parseInt(fila.querySelector('.cantidad').value, 10) || 0;
            const costo = parseFloat(fila.querySelector('.costo').value) || 0;
            const subtotal = cantidad * costo;

            fila.querySelector('.subtotal').textContent = formatear(subtotal);
            total += subtotal;
        });

        totalEl.textContent = formatear(total);
    };

    const agregar = (producto) => {
        const existente = items.querySelector(`tr[data-producto="${producto.id}"]`);

        if (existente) {
            const cantidad = existente.querySelector('.cantidad');
            cantidad.value = (parseInt(cantidad.value, 10) || 0) + 1;
            recalcular();
            return;
        }

        const fila = document.createElement('tr');
        fila.dataset.indice = String(indice);
        fila.dataset.producto = String(producto.id);
        fila.innerHTML = `
            <td>${producto.codigo} — ${producto.nombre}
                <input type="hidden" name="items[${indice}][producto_id]" value="${producto.id}">
            </td>
            <td>${producto.stock}</td>
            <td><input type="number" min="1" step="1" value="1" class="form-control cantidad" name="items[${indice}][cantidad]" required></td>
            <td><input type="number" min="0" step="0.01" value="${producto.precio_compra}" class="form-control costo" name="items[${indice}][costo_unitario]" required></td>
            <td class="subtotal"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger quitar">Quitar</button></td>
        `;

        fila.querySelector('.quitar').addEventListener('click', () => {
            fila.remove();
            recalcular();
        });
        fila.querySelector('.cantidad').addEventListener('input', recalcular);
        fila.querySelector('.costo').addEventListener('input', recalcular);

        items.appendChild(fila);
        indice += 1;
        recalcular();
    };

    buscador.addEventListener('input', () => {
        clearTimeout(temporizador);

        const texto = buscador.value.trim();

        if (texto.length < 1) {
            resultados.innerHTML = '';
            return;
        }

        temporizador = setTimeout(async () => {
            const respuesta = await fetch(`/api-interna/productos/buscar?q=${encodeURIComponent(texto)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!respuesta.ok) {
                return;
            }

            const productos = await respuesta.json();
            resultados.innerHTML = '';

            productos.forEach((producto) => {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'list-group-item list-group-item-action';
                boton.textContent = `${producto.codigo} — ${producto.nombre} (stock ${producto.stock})`;
                boton.addEventListener('click', () => {
                    agregar(producto);
                    resultados.innerHTML = '';
                    buscador.value = '';
                    buscador.focus();
                });
                resultados.appendChild(boton);
            });
        }, 250);
    });

    formulario.addEventListener('submit', () => {
        boton.disabled = true;
    });
});
