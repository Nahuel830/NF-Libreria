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

    const esc = (texto) => String(texto ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    const pitar = () => {
        try {
            const contexto = new (window.AudioContext || window.webkitAudioContext)();
            const oscilador = contexto.createOscillator();
            const ganancia = contexto.createGain();
            oscilador.connect(ganancia);
            ganancia.connect(contexto.destination);
            oscilador.frequency.value = 880;
            oscilador.start();
            ganancia.gain.setTargetAtTime(0.0001, contexto.currentTime, 0.05);
            setTimeout(() => { oscilador.stop(); contexto.close(); }, 250);
        } catch (error) {
            // Sin audio disponible: solo el aviso visual.
        }
    };

    const avisoBreve = (texto) => {
        let aviso = document.getElementById('aviso-entrada');

        if (!aviso) {
            aviso = document.createElement('div');
            aviso.id = 'aviso-entrada';
            aviso.className = 'toast-venta';
            document.body.appendChild(aviso);
        }

        aviso.textContent = texto;
        aviso.classList.add('visible');
        clearTimeout(aviso.dataset.t);
        aviso.dataset.t = setTimeout(() => aviso.classList.remove('visible'), 2500).toString();
    };

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
            <td>${esc(producto.codigo)} — ${esc(producto.nombre)}
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
            buscador.focus();
        });
        fila.querySelector('.cantidad').addEventListener('input', recalcular);
        fila.querySelector('.costo').addEventListener('input', recalcular);

        items.appendChild(fila);
        indice += 1;
        recalcular();
        buscador.focus();
    };

    const coincideExacto = (productos, texto) => {
        const arriba = texto.toUpperCase();
        return productos.find((p) => p.codigo.toUpperCase() === arriba
            || (p.codigo_barras && p.codigo_barras.toUpperCase() === arriba));
    };

    buscador.addEventListener('keydown', async (e) => {
        if (e.key !== 'Enter') {
            return;
        }

        e.preventDefault();

        const texto = buscador.value.trim();

        if (texto === '') {
            return;
        }

        const respuesta = await fetch(`/api-interna/productos/buscar?para=entrada&q=${encodeURIComponent(texto)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            return;
        }

        const productos = await respuesta.json();
        const directo = coincideExacto(productos, texto);

        if (directo) {
            agregar(directo);
            resultados.innerHTML = '';
            buscador.value = '';
            buscador.focus();
            return;
        }

        if (productos.length === 0) {
            avisoBreve(`Código no encontrado: ${texto}`);
            pitar();
            buscador.value = '';
            buscador.focus();
        }
    });

    buscador.addEventListener('input', () => {
        clearTimeout(temporizador);

        const texto = buscador.value.trim();

        if (texto.length < 1) {
            resultados.innerHTML = '';
            return;
        }

        temporizador = setTimeout(async () => {
            const respuesta = await fetch(`/api-interna/productos/buscar?para=entrada&q=${encodeURIComponent(texto)}`, {
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
        setTimeout(() => { boton.disabled = false; }, 3000);
    });

    // Selector de proveedor con búsqueda + creación rápida.
    const provNombre = document.getElementById('proveedor_nombre');
    const provId = document.getElementById('proveedor_id');
    const provResultados = document.getElementById('proveedores-resultados');

    if (provNombre && provId && provResultados) {
        let temporizadorProv = null;

        provNombre.addEventListener('input', () => {
            clearTimeout(temporizadorProv);
            provId.value = '';

            const texto = provNombre.value.trim();

            if (texto.length < 1) {
                provResultados.innerHTML = '';
                return;
            }

            temporizadorProv = setTimeout(async () => {
                const respuesta = await fetch(`/proveedores/buscar?q=${encodeURIComponent(texto)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!respuesta.ok) {
                    return;
                }

                const proveedores = await respuesta.json();
                provResultados.innerHTML = '';

                proveedores.forEach((proveedor) => {
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'list-group-item list-group-item-action';
                    boton.textContent = proveedor.nombre;
                    boton.addEventListener('click', () => {
                        provId.value = proveedor.id;
                        provNombre.value = proveedor.nombre;
                        provResultados.innerHTML = '';
                    });
                    provResultados.appendChild(boton);
                });
            }, 250);
        });

        const rapidoGuardar = document.getElementById('proveedor-rapido-guardar');

        if (rapidoGuardar) {
            rapidoGuardar.addEventListener('click', async () => {
                const nombreEl = document.getElementById('proveedor-rapido-nombre');
                const errorEl = document.getElementById('proveedor-rapido-error');
                const token = document.querySelector('meta[name="csrf-token"]').content;

                const respuesta = await fetch('/proveedores/rapido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ nombre: nombreEl.value }),
                });

                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    errorEl.textContent = datos.message || 'No se pudo crear el proveedor.';
                    errorEl.classList.remove('d-none');
                    return;
                }

                provId.value = datos.id;
                provNombre.value = datos.nombre;
                errorEl.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('modal-proveedor')).hide();
            });
        }
    }
});
