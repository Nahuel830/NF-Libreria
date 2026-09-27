// Punto de venta: buscador, carrito, totales y cobro.
document.addEventListener('DOMContentLoaded', () => {
    const buscador = document.getElementById('buscador');
    const resultados = document.getElementById('resultados');
    const carrito = document.getElementById('carrito');
    const subtotalEl = document.getElementById('subtotal');
    const descuentoEl = document.getElementById('descuento');
    const totalEl = document.getElementById('total');
    const metodoEl = document.getElementById('metodo_pago');
    const recibidoEl = document.getElementById('recibido');
    const cambioEl = document.getElementById('cambio');
    const pagoEfectivo = document.getElementById('pago-efectivo');
    const formulario = document.getElementById('form-venta');
    const botonCobrar = document.getElementById('btn-cobrar');
    const botonCancelar = document.getElementById('btn-cancelar');
    const mensajeEl = document.getElementById('mensaje-venta');

    if (!buscador || !carrito || !formulario) {
        return;
    }

    const carritoItems = new Map();
    let temporizador = null;
    let indiceActivo = -1;
    let resultadosActuales = [];

    const formatear = (valor) => {
        const n = Number(valor) || 0;
        return `Bs. ${n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
    };

    const mostrarMensaje = (texto) => {
        if (mensajeEl) {
            mensajeEl.textContent = texto;
            mensajeEl.classList.remove('d-none');
        }
    };

    const limpiarMensaje = () => {
        if (mensajeEl) {
            mensajeEl.textContent = '';
            mensajeEl.classList.add('d-none');
        }
    };

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
        let aviso = document.getElementById('aviso-venta');

        if (!aviso) {
            aviso = document.createElement('div');
            aviso.id = 'aviso-venta';
            aviso.className = 'toast-venta';
            document.body.appendChild(aviso);
        }

        aviso.textContent = texto;
        aviso.classList.add('visible');
        clearTimeout(aviso.dataset.t);
        aviso.dataset.t = setTimeout(() => aviso.classList.remove('visible'), 2500).toString();
    };

    const codigoNoEncontrado = (texto) => {
        avisoBreve(`Código no encontrado: ${texto}`);
        pitar();
        buscador.value = '';
        buscador.focus();
    };

    const coincideExacto = (productos, texto) => {
        const arriba = texto.toUpperCase();
        return productos.find((p) => p.codigo.toUpperCase() === arriba
            || (p.codigo_barras && p.codigo_barras.toUpperCase() === arriba));
    };

    const recalcular = () => {
        let subtotal = 0;

        carritoItems.forEach((item) => {
            subtotal += item.cantidad * parseFloat(item.precio);
        });

        const descuento = descuentoEl ? parseFloat(descuentoEl.value) || 0 : 0;
        const total = Math.max(0, subtotal - descuento);

        subtotalEl.textContent = formatear(subtotal);
        totalEl.textContent = formatear(total);
        totalEl.dataset.valor = total.toFixed(2);

        if (metodoEl.value === 'EFECTIVO' && recibidoEl) {
            const recibido = parseFloat(recibidoEl.value) || 0;
            cambioEl.textContent = formatear(recibido - total);
        } else if (cambioEl) {
            cambioEl.textContent = formatear(0);
        }
    };

    const dibujar = () => {
        carrito.innerHTML = '';

        carritoItems.forEach((item) => {
            const fila = document.createElement('tr');
            const supera = item.controla && item.cantidad > item.stock;

            fila.innerHTML = `
                <td>${item.codigo}<br><small>${item.nombre}</small>
                    ${supera ? `<br><span class="badge bg-warning text-dark">Supera el stock (disponible ${item.stock})</span>` : ''}
                </td>
                <td>${formatear(item.precio)}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-lg btn-outline-secondary menos px-3" data-id="${item.id}">−</button>
                        <input type="number" min="1" step="1" value="${item.cantidad}" class="form-control form-control-lg cantidad" data-id="${item.id}" style="width: 80px;">
                        <button type="button" class="btn btn-lg btn-outline-secondary mas px-3" data-id="${item.id}">+</button>
                    </div>
                </td>
                <td class="subtotal">${formatear(item.cantidad * parseFloat(item.precio))}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger quitar" data-id="${item.id}">Quitar</button></td>
            `;

            carrito.appendChild(fila);
        });

        carrito.querySelectorAll('.mas').forEach((b) => b.addEventListener('click', () => {
            const item = carritoItems.get(Number(b.dataset.id));
            item.cantidad += 1;
            dibujar();
            recalcular();
            buscador.focus();
        }));
        carrito.querySelectorAll('.menos').forEach((b) => b.addEventListener('click', () => {
            const item = carritoItems.get(Number(b.dataset.id));
            item.cantidad = Math.max(1, item.cantidad - 1);
            dibujar();
            recalcular();
            buscador.focus();
        }));
        carrito.querySelectorAll('.cantidad').forEach((input) => input.addEventListener('change', () => {
            const item = carritoItems.get(Number(input.dataset.id));
            item.cantidad = Math.max(1, parseInt(input.value, 10) || 1);
            dibujar();
            recalcular();
            buscador.focus();
        }));
        carrito.querySelectorAll('.quitar').forEach((b) => b.addEventListener('click', () => {
            carritoItems.delete(Number(b.dataset.id));
            dibujar();
            recalcular();
            buscador.focus();
        }));

        recalcular();
    };

    const agregar = (producto) => {
        const id = Number(producto.id);
        const existente = carritoItems.get(id);

        if (existente) {
            existente.cantidad += 1;
        } else {
            carritoItems.set(id, {
                id,
                codigo: producto.codigo,
                nombre: producto.nombre,
                precio: producto.precio_venta,
                stock: Number(producto.stock),
                controla: Boolean(Number(producto.controla_stock)),
                cantidad: 1,
            });
        }

        limpiarMensaje();
        dibujar();
        buscador.value = '';
        resultados.innerHTML = '';
        resultadosActuales = [];
        buscador.focus();
    };

    const pintarResultados = () => {
        resultados.innerHTML = '';

        resultadosActuales.forEach((producto, i) => {
            const stockTexto = Number(producto.controla_stock) ? `stock ${producto.stock}` : '—';
            const enRojo = Number(producto.controla_stock) && Number(producto.stock) <= 0;

            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = `list-group-item list-group-item-action${i === indiceActivo ? ' active' : ''}`;
            boton.innerHTML = `${producto.codigo} — ${producto.nombre} — ${formatear(producto.precio_venta)} — <span class="${enRojo ? 'text-danger fw-bold' : ''}">${stockTexto}</span>`;
            boton.addEventListener('click', () => agregar(producto));
            resultados.appendChild(boton);
        });
    };

    const buscar = async (texto) => {
        const respuesta = await fetch(`/api-interna/productos/buscar?q=${encodeURIComponent(texto)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            return;
        }

        resultadosActuales = await respuesta.json();
        indiceActivo = -1;
        pintarResultados();
    };

    buscador.addEventListener('keydown', async (e) => {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();

            if (resultadosActuales.length === 0) {
                return;
            }

            indiceActivo = e.key === 'ArrowDown'
                ? Math.min(resultadosActuales.length - 1, indiceActivo + 1)
                : Math.max(0, indiceActivo - 1);
            pintarResultados();
        } else if (e.key === 'Enter') {
            e.preventDefault();

            const texto = buscador.value.trim();

            if (texto === '') {
                return;
            }

            const exacto = coincideExacto(resultadosActuales, texto);

            if (exacto) {
                agregar(exacto);
                return;
            }

            if (indiceActivo >= 0 && resultadosActuales[indiceActivo]) {
                agregar(resultadosActuales[indiceActivo]);
                return;
            }

            // Sin resultados cargados (p. ej. escaneo rápido): buscar ya.
            const respuesta = await fetch(`/api-interna/productos/buscar?q=${encodeURIComponent(texto)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!respuesta.ok) {
                return;
            }

            const productos = await respuesta.json();
            const directo = coincideExacto(productos, texto);

            if (directo) {
                agregar(directo);
                return;
            }

            if (productos.length === 0) {
                codigoNoEncontrado(texto);
                return;
            }

            resultadosActuales = productos;
            indiceActivo = -1;
            pintarResultados();
        }
    });

    buscador.addEventListener('input', () => {
        clearTimeout(temporizador);

        const texto = buscador.value.trim();

        if (texto.length < 1) {
            resultados.innerHTML = '';
            resultadosActuales = [];
            return;
        }

        temporizador = setTimeout(() => buscar(texto), 250);
    });

    // Lector de código de barras con el foco en otro campo: si llega una
    // entrada rápida (menos de 50 ms entre caracteres) terminada en Enter,
    // se redirige al carrito. La escritura manual es más lenta y no se toca.
    let secuencia = '';
    let ultimoTiempo = 0;

    const redirigirEscaneo = async (texto, objetivo) => {
        const respuesta = await fetch(`/api-interna/productos/buscar?q=${encodeURIComponent(texto)}`, {
            headers: { Accept: 'application/json' },
        });

        if (respuesta.ok) {
            const directo = coincideExacto(await respuesta.json(), texto);

            if (directo) {
                if (objetivo && (objetivo.tagName === 'INPUT' || objetivo.tagName === 'TEXTAREA')
                    && typeof objetivo.value === 'string' && objetivo.value.endsWith(texto)) {
                    objetivo.value = objetivo.value.slice(0, -texto.length);
                    objetivo.dispatchEvent(new Event('input', { bubbles: true }));
                }

                agregar(directo);
                return true;
            }
        }

        avisoBreve(`Código no encontrado: ${texto}`);
        pitar();
        buscador.focus();
        return false;
    };

    document.addEventListener('keydown', async (e) => {
        if (e.target === buscador || e.ctrlKey || e.metaKey || e.altKey) {
            return;
        }

        const ahora = performance.now();

        if (e.key === 'Enter') {
            const texto = secuencia;
            const rapida = texto.length >= 3 && (ahora - ultimoTiempo) < 50;
            secuencia = '';

            if (!rapida) {
                return;
            }

            e.preventDefault();
            await redirigirEscaneo(texto, e.target);
            return;
        }

        if (e.key.length === 1) {
            if (ahora - ultimoTiempo > 50) {
                secuencia = '';
            }

            secuencia += e.key;
            ultimoTiempo = ahora;
        } else {
            secuencia = '';
        }
    });

    document.querySelectorAll('[data-metodo]').forEach((boton) => {
        boton.addEventListener('click', () => {
            metodoEl.value = boton.dataset.metodo;
            document.querySelectorAll('[data-metodo]').forEach((b) => b.classList.remove('active'));
            boton.classList.add('active');
            pagoEfectivo.classList.toggle('d-none', metodoEl.value !== 'EFECTIVO');
            recalcular();
        });
    });

    if (recibidoEl) {
        recibidoEl.addEventListener('input', recalcular);
    }

    if (descuentoEl) {
        descuentoEl.addEventListener('input', recalcular);
    }

    document.querySelectorAll('[data-billete]').forEach((boton) => {
        boton.addEventListener('click', () => {
            recibidoEl.value = boton.dataset.billete;
            recalcular();
            recibidoEl.focus();
        });
    });

    const botonExacto = document.getElementById('btn-exacto');

    if (botonExacto) {
        botonExacto.addEventListener('click', () => {
            recibidoEl.value = totalEl.dataset.valor || recibidoEl.value;
            recalcular();
        });
    }

    botonCancelar.addEventListener('click', () => {
        if (carritoItems.size === 0) {
            return;
        }

        window.pedirConfirmacion({
            titulo: 'Cancelar venta',
            mensaje: '¿Cancelar la venta y vaciar el carrito?',
            textoBoton: 'Vaciar carrito',
            color: 'danger',
            callback: () => {
                carritoItems.clear();
                dibujar();
                buscador.focus();
            },
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            buscador.focus();
        } else if (e.key === 'F9') {
            e.preventDefault();
            formulario.requestSubmit();
        } else if (e.key === 'Escape' && document.activeElement !== buscador) {
            botonCancelar.click();
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (carritoItems.size > 0) {
            e.preventDefault();
        }
    });

    formulario.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (carritoItems.size === 0) {
            mostrarMensaje('Agrega al menos un producto.');
            return;
        }

        botonCobrar.disabled = true;
        limpiarMensaje();

        const items = [...carritoItems.values()].map((item) => ({
            producto_id: item.id,
            cantidad: item.cantidad,
        }));

        try {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const respuesta = await fetch(formulario.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    token: document.getElementById('token').value,
                    metodo_pago: metodoEl.value,
                    descuento: descuentoEl ? descuentoEl.value : null,
                    monto_recibido: recibidoEl ? recibidoEl.value : null,
                    cliente_id: document.getElementById('cliente_id').value || null,
                    cliente_nombre: document.getElementById('cliente_nombre').value,
                    observaciones: document.getElementById('observaciones').value,
                    items,
                }),
            });

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                mostrarMensaje(datos.mensaje || datos.message || 'No se pudo registrar la venta.');
                botonCobrar.disabled = false;
                return;
            }

            window.removeEventListener('beforeunload', () => {});
            window.location.href = datos.url;
        } catch (error) {
            mostrarMensaje('Error de conexión. Inténtalo de nuevo.');
            botonCobrar.disabled = false;
        }
    });

    buscador.focus();
    dibujar();

    // Cliente con búsqueda + creación rápida.
    const clienteBuscar = document.getElementById('cliente_buscar');
    const clienteId = document.getElementById('cliente_id');
    const clientesResultados = document.getElementById('clientes-resultados');
    const clienteQuitar = document.getElementById('cliente-quitar');
    const clienteNombre = document.getElementById('cliente_nombre');
    let temporizadorCliente = null;

    const fijarCliente = (id, nombre) => {
        clienteId.value = id;
        clienteBuscar.value = nombre;
        clienteNombre.value = '';
        clienteNombre.disabled = id !== '';
        clienteQuitar.classList.toggle('d-none', id === '');
    };

    if (clienteBuscar && clienteId) {
        clienteBuscar.addEventListener('input', () => {
            clearTimeout(temporizadorCliente);
            clienteId.value = '';
            clienteNombre.disabled = false;
            clienteQuitar.classList.add('d-none');
            const texto = clienteBuscar.value.trim();

            if (texto.length < 1) {
                clientesResultados.innerHTML = '';
                return;
            }

            temporizadorCliente = setTimeout(async () => {
                const respuesta = await fetch(`/clientes/buscar?q=${encodeURIComponent(texto)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!respuesta.ok) {
                    return;
                }

                const clientes = await respuesta.json();
                clientesResultados.innerHTML = '';

                clientes.forEach((cliente) => {
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'list-group-item list-group-item-action';
                    boton.textContent = cliente.ci_nit ? `${cliente.nombre} (${cliente.ci_nit})` : cliente.nombre;
                    boton.addEventListener('click', () => {
                        fijarCliente(String(cliente.id), cliente.nombre);
                        clientesResultados.innerHTML = '';
                    });
                    clientesResultados.appendChild(boton);
                });
            }, 250);
        });

        clienteQuitar.addEventListener('click', () => {
            fijarCliente('', '');
            clienteBuscar.value = '';
            clienteBuscar.focus();
        });

        const rapidoGuardar = document.getElementById('cliente-rapido-guardar');

        if (rapidoGuardar) {
            rapidoGuardar.addEventListener('click', async () => {
                const nombreEl = document.getElementById('cliente-rapido-nombre');
                const ciEl = document.getElementById('cliente-rapido-ci');
                const errorEl = document.getElementById('cliente-rapido-error');
                const token = document.querySelector('meta[name="csrf-token"]').content;

                const respuesta = await fetch('/clientes/rapido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ nombre: nombreEl.value, ci_nit: ciEl.value }),
                });

                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    errorEl.textContent = datos.message || 'No se pudo crear el cliente.';
                    errorEl.classList.remove('d-none');
                    return;
                }

                fijarCliente(String(datos.id), datos.nombre);
                errorEl.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('modal-cliente')).hide();
                buscador.focus();
            });
        }
    }
});
