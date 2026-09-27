# Plan de pruebas — NF Librería (PROMPTS 2 al 13)

Lista de todo lo que el sistema debe hacer. Recórrela en orden, marca cada casilla `[x]` cuando funcione y anota lo que falle en la sección **Registro de fallos** al final. Luego se le pasa esa sección a OpenCode para corregir todo de una vez.

Cada prueba tiene un código (por ejemplo **V-05**) para identificarla al reportar fallos.

---

## 0. Preparación

- [ ] **P-01** En la terminal: `php artisan migrate:fresh --seed` termina sin errores.
- [ ] **P-02** `php artisan test` → todo en verde. Anota cuántos tests pasaron: ______
- [ ] **P-03** `php artisan stock:verificar` → sin diferencias.
- [ ] **P-04** `php artisan serve` y abrir http://127.0.0.1:8000.
- [ ] **P-05** Tener dos navegadores distintos (ej: Chrome y Edge) para probar dos usuarios a la vez.

**Usuarios de prueba** (solo en desarrollo):

| Usuario | Contraseña | Rol |
|---|---|---|
| admin | Admin12345 (pedirá cambiarla) | Administrador |
| encargado | demo12345 | Encargado |
| cajero1 | demo12345 | Cajero |

---

## 1. Login y sesión

- [ ] **L-01** Entrar a http://127.0.0.1:8000 sin sesión → redirige al login.
- [ ] **L-02** Login con `admin` / `Admin12345` → obliga a ir a "Cambiar contraseña" y no deja abrir otras pantallas (probar escribiendo /productos en la barra) hasta cambiarla.
- [ ] **L-03** Cambiar contraseña con la contraseña actual incorrecta → error.
- [ ] **L-04** Cambiar contraseña con menos de 8 caracteres o sin coincidir la confirmación → error en español.
- [ ] **L-05** Cambiar contraseña correctamente → entra al inicio. Anota la nueva: ______
- [ ] **L-06** Login con contraseña incorrecta → "Usuario o contraseña incorrectos" (no dice cuál falló).
- [ ] **L-07** 6 intentos fallidos seguidos → bloqueo temporal (~1 minuto) con mensaje.
- [ ] **L-08** El usuario se puede escribir en mayúsculas (`ADMIN`) y funciona igual (o muestra el mismo error genérico; anotar comportamiento).
- [ ] **L-09** Botón "Salir" → vuelve al login; el botón Atrás del navegador no muestra datos protegidos.
- [ ] **L-10** Inactividad: en Configuración poner minutos_inactividad = 5, esperar 6 minutos sin tocar y hacer clic → sesión cerrada con el mensaje "Tu sesión se cerró por inactividad". (Volver a dejarlo en 60.)
- [ ] **L-11** Con cajero1 conectado en otro navegador, el admin lo desactiva → en su siguiente clic, cajero1 es expulsado.
- [ ] **L-12** Barra superior muestra nombre del negocio, nombre del usuario, rol, "Cambiar contraseña" y "Salir".
- [ ] **L-13** Páginas de error en español: /no-existe → 404 propio.

---

## 2. Permisos por rol

Probar entrando con cada usuario y escribiendo la dirección directamente en la barra del navegador (no solo mirar el menú).

| Dirección | Admin | Encargado | Cajero |
|---|---|---|---|
| /usuarios | ✅ | ❌ 403 | ❌ 403 |
| /configuracion | ✅ | ❌ 403 | ❌ 403 |
| /auditoria | ✅ | ❌ 403 | ❌ 403 |
| /categorias | ✅ | ✅ | ❌ 403 |
| /productos | ✅ | ✅ | ✅ solo consulta |
| /productos/crear | ✅ | ✅ | ❌ 403 |
| /inventario/stock-bajo | ✅ | ✅ | ❌ 403 |
| /entradas | ✅ | ✅ | ❌ 403 |
| /productos/importar | ✅ | ✅ | ❌ 403 |
| /ventas/nueva | ✅ | ✅ | ✅ |
| /ventas | ✅ todas | ✅ todas | ✅ solo las suyas de hoy |
| /reportes | ✅ | ✅ | ❌ 403 |

- [ ] **R-01** Todas las filas de la tabla con admin.
- [ ] **R-02** Todas las filas con encargado.
- [ ] **R-03** Todas las filas con cajero1.
- [ ] **R-04** El menú de cada rol muestra solo lo que puede usar.
- [ ] **R-05** La página 403 dice "No tienes permiso para acceder a esta sección" (sin datos técnicos).

---

## 3. Usuarios (admin)

- [ ] **U-01** Listado con nombre, usuario, rol, estado y último acceso.
- [ ] **U-02** Búsqueda por nombre y por usuario; filtros por rol y estado.
- [ ] **U-03** Crear usuario `cajero2` (rol Cajero) → aparece en la lista.
- [ ] **U-04** Crear otro usuario `cajero2` → error "ya existe".
- [ ] **U-05** Crear usuario con espacios o tildes en el nombre de usuario (`juan perez`) → error de formato.
- [ ] **U-06** Entrar como cajero2 → obliga a cambiar contraseña.
- [ ] **U-07** Editar nombre y rol de cajero2 → se guarda.
- [ ] **U-08** Restablecer contraseña de cajero2 → al entrar vuelve a pedir cambio.
- [ ] **U-09** Desactivar cajero2 → no puede iniciar sesión.
- [ ] **U-10** Reactivar cajero2 → puede entrar.
- [ ] **U-11** El admin intenta desactivarse a sí mismo → no lo permite.
- [ ] **U-12** El admin intenta quitarse el rol admin (siendo el único admin) → no lo permite.
- [ ] **U-13** No existe ningún botón de "Eliminar" usuario.

---

## 4. Configuración (admin)

- [ ] **C-01** Cambiar nombre del negocio → cambia en la barra superior y en el ticket.
- [ ] **C-02** Cambiar dirección, teléfono y mensaje del ticket → aparecen en el ticket.
- [ ] **C-03** minutos_inactividad fuera de rango (ej: 1 o 1000) → error.
- [ ] **C-04** Switch "permitir stock negativo" se guarda (se prueba en ventas, V-20/V-21).
- [ ] **C-05** Switch "imprimir automático" se guarda (se prueba en T-06).

---

## 5. Auditoría (admin)

- [ ] **A-01** Aparecen LOGIN, LOGIN_FALLIDO y LOGOUT de las pruebas anteriores con usuario, fecha, hora e IP.
- [ ] **A-02** Aparecen CREAR/EDITAR/DESACTIVAR/ACTIVAR/CAMBIO_PASSWORD de usuarios.
- [ ] **A-03** Aparece CONFIGURACION con valores antes y después.
- [ ] **A-04** Filtros por fecha, usuario y acción funcionan.
- [ ] **A-05** "Ver detalle" muestra los datos antes/después en tabla legible (no JSON crudo).
- [ ] **A-06** En ningún registro aparece una contraseña.
- [ ] **A-07** No hay botones para editar ni borrar registros de auditoría.

---

## 6. Categorías (admin / encargado)

- [ ] **K-01** Existen las 11 categorías iniciales (Cuadernos, Lapiceros, … Fotocopias e impresiones, Otros).
- [ ] **K-02** Listado muestra cantidad de productos por categoría.
- [ ] **K-03** Crear "Mochilas" → aparece.
- [ ] **K-04** Crear "mochilas" (minúscula) → error de duplicado.
- [ ] **K-05** Editar descripción → se guarda.
- [ ] **K-06** Desactivar "Mochilas" → no aparece para elegir al crear un producto.
- [ ] **K-07** Reactivar → vuelve a aparecer.
- [ ] **K-08** No existe botón "Eliminar".
- [ ] **K-09** Acciones registradas en auditoría.

---

## 7. Productos

- [ ] **PR-01** Existen ~30 productos demo con stock inicial.
- [ ] **PR-02** Listado con código, nombre, categoría, marca, precio (formato "Bs. 12,50"), stock y estado.
- [ ] **PR-03** Búsqueda "cuad" encuentra cuadernos; "CUAD" también (sin importar mayúsculas).
- [ ] **PR-04** Búsqueda por código exacto.
- [ ] **PR-05** Filtros por categoría, estado y "solo stock bajo".
- [ ] **PR-06** Productos con stock ≤ stock mínimo se ven con badge rojo.
- [ ] **PR-07** Crear producto: botón "Sugerir código" propone el siguiente (ej: CUA-031).
- [ ] **PR-08** El código se guarda en MAYÚSCULAS aunque se escriba en minúsculas.
- [ ] **PR-09** Código repetido → error.
- [ ] **PR-10** Precio de venta menor al de compra → advertencia que pide confirmar (no bloquea).
- [ ] **PR-11** Crear con stock inicial 20 → stock 20 y un movimiento INICIAL en el kardex.
- [ ] **PR-12** Crear un producto de servicio "Impresión color" con "controla stock" desactivado → no pide stock y muestra "—".
- [ ] **PR-13** Editar producto: el stock NO se puede cambiar desde el formulario.
- [ ] **PR-14** Cambiar precio de venta → auditoría registra CAMBIO_PRECIO con precio anterior y nuevo.
- [ ] **PR-15** Desactivar producto → no aparece en la búsqueda de ventas ni de entradas.
- [ ] **PR-16** Como cajero: ve el listado sin precio de compra y sin botones de crear/editar.
- [ ] **PR-17** Como cajero: el detalle del producto no muestra precio de compra.
- [ ] **PR-18** Nombre con caracteres especiales `<b>Prueba</b>` se muestra como texto literal (no en negrita).

---

## 8. Stock, ajustes y kardex

- [ ] **S-01** Detalle de producto muestra el kardex: fecha, tipo (con color), cantidad, stock anterior, stock nuevo, usuario, referencia, motivo.
- [ ] **S-02** Ajustar stock: stock actual 20, stock real 17, motivo "Conteo físico" → AJUSTE_NEGATIVO −3, stock 17.
- [ ] **S-03** Ajustar a un número mayor → AJUSTE_POSITIVO.
- [ ] **S-04** Ajustar al mismo stock → no crea movimiento (mensaje informativo).
- [ ] **S-05** Ajuste sin motivo o con motivo de menos de 5 letras → error.
- [ ] **S-06** Ajuste registrado en auditoría como AJUSTE_STOCK.
- [ ] **S-07** Cajero no ve el botón "Ajustar stock" y no puede entrar por URL (403).
- [ ] **S-08** Pantalla "Stock bajo" lista los productos con stock ≤ mínimo, ordenados por urgencia.
- [ ] **S-09** `php artisan stock:verificar` → sin diferencias.

---

## 9. Entradas de mercadería

- [ ] **E-01** Nueva entrada: proveedor "Distribuidora ABC", documento "N-4521".
- [ ] **E-02** Buscador encuentra productos por código o nombre; no muestra productos inactivos ni de servicio.
- [ ] **E-03** Agregar 3 productos con cantidades y costos → el total se calcula en vivo.
- [ ] **E-04** Agregar el mismo producto dos veces → queda en una línea con la cantidad sumada.
- [ ] **E-05** Quitar un producto de la lista → el total se actualiza.
- [ ] **E-06** Registrar sin productos → error.
- [ ] **E-07** Cantidad 0 o negativa → error.
- [ ] **E-08** Registrar → el stock de cada producto aumenta y el kardex muestra ENTRADA con "Entrada #000001" enlazado.
- [ ] **E-09** Con "Actualizar precio de compra" marcado → cambia precio_compra del producto (y queda CAMBIO_PRECIO en auditoría).
- [ ] **E-10** Otra entrada con la opción desmarcada → no cambia precio_compra.
- [ ] **E-11** Doble clic rápido en "Registrar" → solo se crea una entrada.
- [ ] **E-12** Listado de entradas con filtros por fecha, proveedor y estado.
- [ ] **E-13** Detalle muestra ítems, total y usuario.
- [ ] **E-14** Anular entrada con motivo → el stock vuelve al valor anterior (movimiento ANULACION_ENTRADA) y el detalle muestra quién, cuándo y por qué.
- [ ] **E-15** Intentar anular la misma entrada otra vez → no se puede.
- [ ] **E-16** `php artisan stock:verificar` → sin diferencias.

---

## 10. Importación desde Excel/CSV

- [ ] **I-01** Descargar plantilla → se abre en Excel con columnas separadas y tildes correctas.
- [ ] **I-02** Llenar 10 productos nuevos (con una categoría nueva "Arte"), guardar como CSV UTF-8 e importar → vista previa muestra "Nuevo" en cada fila.
- [ ] **I-03** Incluir a propósito: una fila sin nombre, una con precio "abc" y un código repetido dentro del archivo → aparecen como Error con motivo claro.
- [ ] **I-04** Precios con coma (12,50) se interpretan bien.
- [ ] **I-05** Confirmar → se crean solo los válidos; la categoría "Arte" se crea sola.
- [ ] **I-06** Productos con stock_inicial tienen movimiento INICIAL.
- [ ] **I-07** Importar el mismo archivo con opción "Omitir" → no cambia nada.
- [ ] **I-08** Importar con opción "Actualizar" cambiando precios → cambian los precios pero NO el stock.
- [ ] **I-09** Importar un archivo guardado como "CSV (delimitado por comas)" normal de Excel (no UTF-8) → las tildes se ven bien igual.
- [ ] **I-10** Subir un archivo que no es CSV (ej: una imagen) → error.
- [ ] **I-11** La importación aparece una vez en auditoría con el resumen.

---

## 11. Ventas — pantalla de caja

Hacer estas pruebas como **cajero1** salvo que se indique otra cosa.

- [ ] **V-01** Menú "Ventas" visible en la barra superior, con "Nueva venta" e "Historial de ventas".
- [ ] **V-02** Al abrir, el cursor ya está en el buscador.
- [ ] **V-03** Escribir parte del nombre → aparecen resultados con código, nombre, precio y stock.
- [ ] **V-04** Navegar resultados con flechas ↑ ↓ y agregar con Enter.
- [ ] **V-05** Escribir un código exacto y Enter → se agrega directo.
- [ ] **V-06** Después de agregar, el cursor vuelve al buscador.
- [ ] **V-07** Agregar el mismo producto otra vez → suma 1 a la cantidad.
- [ ] **V-08** Botones + y − y edición manual de cantidad funcionan; subtotales y total se actualizan.
- [ ] **V-09** Quitar un producto del carrito.
- [ ] **V-10** F2 enfoca el buscador; F9 cobra; Esc pide confirmación y vacía el carrito.
- [ ] **V-11** El cajero NO ve el campo de descuento.
- [ ] **V-12** Efectivo: total 37,50, recibido 50 → cambio 12,50 en vivo.
- [ ] **V-13** Botones rápidos de billetes (10, 20, 50, 100, 200) y "Exacto".
- [ ] **V-14** Recibido menor al total → no deja cobrar / error claro.
- [ ] **V-15** Cobrar venta en efectivo de 3 productos → ticket con cambio en grande.
- [ ] **V-16** Venta con QR → no pide monto recibido.
- [ ] **V-17** Venta con Transferencia y con Tarjeta.
- [ ] **V-18** Venta con fotocopias (producto sin control de stock) cantidad 25 → precio 25 × 0,30 = Bs. 7,50 exacto; su stock no cambia.
- [ ] **V-19** Después de vender, el stock de los productos bajó y su kardex muestra VENTA con "Venta #000xxx".
- [ ] **V-20** Con "permitir stock negativo" ACTIVADO: vender más de lo que hay → advertencia visible, pero la venta se realiza.
- [ ] **V-21** Con "permitir stock negativo" DESACTIVADO: vender más de lo que hay → error claro y NO se guarda nada. (Volver a activarlo.)
- [ ] **V-22** Como encargado: aparece el campo descuento; descuento de Bs. 5 → total baja 5 y queda en auditoría.
- [ ] **V-23** Descuento mayor al subtotal → error.
- [ ] **V-24** Doble clic muy rápido en COBRAR → se registra UNA sola venta.
- [ ] **V-25** En el ticket, presionar F5 (recargar) → no se duplica la venta.
- [ ] **V-26** Con productos en el carrito, intentar cerrar la pestaña → el navegador advierte.
- [ ] **V-27** Si ocurre un error al cobrar, el carrito se conserva y el botón se reactiva.
- [ ] **V-28** Un producto desactivado no aparece en la búsqueda.
- [ ] **V-29** La pantalla se ve bien en 1366×768 (sin tener que hacer scroll horizontal).
- [ ] **V-30** Cambiar el precio de un producto después de venderlo → la venta anterior mantiene el precio viejo en su detalle y ticket.

---

## 12. Ticket

- [ ] **T-01** Muestra: nombre del negocio, dirección, teléfono, "VENTA #000xxx", fecha y hora, cajero, líneas, subtotal, descuento (si hay), TOTAL, método de pago, recibido y cambio (si es efectivo), mensaje final y "Documento sin valor fiscal".
- [ ] **T-02** Imprimir con "Microsoft Print to PDF" (o impresora térmica): sale sin menú ni botones, en formato angosto.
- [ ] **T-03** Botón "Nueva venta" tiene el foco: Enter abre una venta nueva.
- [ ] **T-04** cajero1 intenta abrir el ticket de una venta de otro cajero (cambiando el número en la URL) → 403.
- [ ] **T-05** Encargado puede ver cualquier ticket.
- [ ] **T-06** Con "imprimir automático" activado, el diálogo de impresión se abre solo.
- [ ] **T-07** Ticket de una venta anulada muestra "*** ANULADA ***".

---

## 13. Historial y anulación de ventas

- [ ] **H-01** Como cajero1: /ventas muestra solo sus ventas de hoy.
- [ ] **H-02** Como cajero1: cambiar fechas o cajero en la URL no muestra ventas de otros ni de otros días.
- [ ] **H-03** Como encargado: ve todas las ventas; filtros por fechas, cajero, método, estado y número.
- [ ] **H-04** El resumen (cantidad y total) suma solo ventas COMPLETADA.
- [ ] **H-05** Detalle de venta: ítems con precios del momento, pago y estado; botón "Reimprimir ticket".
- [ ] **H-06** Cajero no ve el botón "Anular".
- [ ] **H-07** Encargado anula sin motivo → error; con motivo → pide confirmación y anula.
- [ ] **H-08** Tras anular: estado ANULADA, se ve quién, cuándo y motivo; el stock de los productos volvió (kardex ANULACION_VENTA).
- [ ] **H-09** Anulación registrada en auditoría con los datos de la venta.
- [ ] **H-10** Una venta anulada ya no muestra botón "Anular".
- [ ] **H-11** Venta anulada con fotocopias → no intenta devolver stock de fotocopias.
- [ ] **H-12** `php artisan stock:verificar` → sin diferencias.

---

## 14. Panel de inicio y reportes

- [ ] **D-01** Panel (encargado/admin): total vendido hoy, cantidad de ventas, ticket promedio, anuladas hoy.
- [ ] **D-02** Totales de hoy por método de pago.
- [ ] **D-03** Gráfico de los últimos 7 días (funciona sin Internet).
- [ ] **D-04** Lista de stock bajo y últimas 10 ventas.
- [ ] **D-05** Panel del cajero: sus ventas de hoy y botón grande "Nueva venta".
- [ ] **D-06** Verificación manual: sumar a mano las ventas de hoy en el historial y comparar con el panel → coinciden.
- [ ] **RP-01** Ventas por día en un rango → cuadra con el historial.
- [ ] **RP-02** Ventas por cajero.
- [ ] **RP-03** Ventas por método de pago.
- [ ] **RP-04** Productos más vendidos (ordenar por cantidad y por total); no cuenta ventas anuladas.
- [ ] **RP-05** Ventas por categoría.
- [ ] **RP-06** Cierre del día (fecha y cajero): totales por método, anuladas con motivo, efectivo esperado; imprimible en A4 y en 80 mm.
- [ ] **RP-07** Inventario valorizado: stock × precio de compra y × precio de venta, con totales; filtro por categoría.
- [ ] **RP-08** Movimientos de stock por fechas, producto, tipo y usuario.
- [ ] **RP-09** Exportar cada reporte a CSV y abrirlo en Excel: columnas separadas, tildes correctas, números correctos.
- [ ] **RP-10** Cajero no puede entrar a /reportes (403).

---

## 15. Backups

- [ ] **B-01** Seguir docs/backups.md: crear pgpass.conf y scripts/backup.config.ps1.
- [ ] **B-02** `.\scripts\backup.ps1` crea el archivo `nf-libreria_AAAA-MM-DD_HHMM.backup` en la carpeta configurada.
- [ ] **B-03** Se escribe una línea en backup.log con fecha, resultado y tamaño.
- [ ] **B-04** Con carpeta de copia externa configurada (ej: una carpeta de Google Drive o USB) → el archivo también se copia ahí.
- [ ] **B-05** Con la carpeta externa inexistente → advertencia en el log, pero el backup local se hace igual.
- [ ] **B-06** `.\scripts\probar-restauracion.ps1` → muestra conteos iguales y "RESTAURACIÓN OK"; la base temporal se borra al final.
- [ ] **B-07** `.\scripts\instalar-tarea-backup.ps1` (como administrador) → la tarea aparece en el Programador de tareas de Windows. Ejecutarla manualmente desde ahí → crea un backup.
- [ ] **B-08** El panel del admin muestra "Último backup" con fecha y resultado.
- [ ] **B-09** Cambiar la fecha del último backup a hace 2 días (o esperar) → alerta amarilla en el panel.
- [ ] **B-10** `git status` no muestra backup.config.ps1 ni archivos .backup.
- [ ] **B-11** (Opcional, con cuidado) `.\scripts\restaurar.ps1` sobre libreria_dev → pide escribir el nombre de la base, hace backup de seguridad antes, y restaura.

---

## 16. Pruebas combinadas y de integridad

- [ ] **X-01** Dos navegadores (cajero1 y encargado) venden a la vez el último ítem de un producto con stock negativo DESACTIVADO → uno vende, el otro recibe error de stock; nunca queda negativo.
- [ ] **X-02** Flujo completo: crear producto → entrada de 50 → vender 10 → anular esa venta → ajuste a 48 → el kardex cuenta toda la historia y el stock final es 48.
- [ ] **X-03** `php artisan stock:verificar` al final de todo → sin diferencias.
- [ ] **X-04** `php artisan test` al final → todo en verde.
- [ ] **X-05** Desconectar Internet y usar el sistema 5 minutos (vender, buscar, reportes) → todo funciona.
- [ ] **X-06** Dejar el formulario de venta abierto 2+ horas y luego cobrar → mensaje "La sesión expiró" (419) en español, sin perder datos guardados.
- [ ] **X-07** Desde el celular conectado al mismo WiFi: ejecutar `php artisan serve --host=0.0.0.0` y entrar a http://IP-DE-TU-PC:8000 → carga el login (prueba previa de red local; si el firewall pregunta, permitir solo red privada).

---

## 17. Diseño y apariencia

- [ ] **DS-01** Login con el logo, colores de la marca y diseño centrado y limpio.
- [ ] **DS-02** Barra superior en el color principal, con el logo, y la opción del menú actual resaltada.
- [ ] **DS-03** Todas las pantallas tienen el mismo encabezado (título + botones de acción a la derecha) y ruta de navegación (ej: Inventario › Productos › Editar).
- [ ] **DS-04** Botones, tablas, formularios y badges con el mismo estilo en todas las pantallas (recorrer al menos: usuarios, categorías, productos, entradas, ventas, reportes).
- [ ] **DS-05** Estados con colores consistentes: Activo/COMPLETADA verde, Inactivo gris, ANULADA rojo, stock bajo naranja/rojo.
- [ ] **DS-06** Montos alineados a la derecha y siempre con formato "Bs. 1.234,50".
- [ ] **DS-07** Listas vacías muestran un mensaje amable con ícono (ej: "Todavía no hay entradas registradas" + botón para crear).
- [ ] **DS-08** Las confirmaciones (desactivar, anular, cancelar venta) usan una ventana del sistema con el mismo estilo, no el cuadro gris del navegador.
- [ ] **DS-09** Los mensajes de éxito/error aparecen con el mismo estilo y se cierran solos (los de error no).
- [ ] **DS-10** Pantalla de venta: total muy grande y legible, botones de pago grandes, alto contraste; usable sin mirar el mouse.
- [ ] **DS-11** Ticket y reportes impresos en blanco y negro, legibles, sin colores de fondo.
- [ ] **DS-12** Pantalla 1366×768: nada cortado ni con scroll horizontal. Celular: el menú se colapsa en botón ☰ y las tablas se desplazan dentro de su caja.
- [ ] **DS-13** Pestaña del navegador con ícono (favicon) y título "Página — NF Librería".
- [ ] **DS-14** En Configuración el admin puede subir el logo del negocio (PNG/JPG) y se ve en el login, la barra y el ticket; sin logo se usa el logo por defecto.
- [ ] **DS-15** Sin Internet la fuente y los íconos se ven igual (todo local).
- [ ] **DS-16** Navegando con la tecla Tab se ve claramente qué elemento tiene el foco.
- [ ] **DS-17** Página /estilos (solo admin, solo en desarrollo) muestra todos los componentes para revisarlos juntos.

---

## Registro de fallos

Anota aquí cada prueba que falle. Después copia toda esta sección y pégala en OpenCode con el mensaje que está abajo.

```text
Código: 
Qué hice: 
Qué esperaba: 
Qué pasó (mensaje de error exacto o captura): 
```

```text
Código: 
Qué hice: 
Qué esperaba: 
Qué pasó: 
```

**Mensaje para OpenCode:**

```text
Recorrí docs/plan-de-pruebas.md. Estos son los fallos encontrados. Corrígelos uno por uno, en orden. Para cada uno: explica la causa, corrige, agrega o ajusta un test automático que lo cubra, y ejecuta php artisan test. Haz un commit local por cada corrección con mensaje "fix: ..." (sin push). Al final muéstrame la lista de commits y el resultado de php artisan test y php artisan stock:verificar.

[PEGAR AQUÍ LOS FALLOS]
```

## Mejoras o cambios que quiero

Anota aquí lo que no es un error pero quieres distinto (textos, orden de columnas, nuevas funciones). Se lo pasamos a Claude para convertirlo en prompts.

- 
- 

---

## 19. Código de barras (lector USB tipo teclado)

- [ ] **CB-01** En el producto: campo "Código de barras", se puede llenar escaneando (el Enter del lector no envía el formulario).
- [ ] **CB-02** Código de barras repetido en otro producto → error.
- [ ] **CB-03** En ventas: escanear un código existente agrega 1 (o suma 1 si ya está), limpia el buscador y lo deja enfocado; 3 escaneos seguidos → cantidad 3.
- [ ] **CB-04** Escanear un código que no existe → aviso breve + sonido corto, sin bloquear.
- [ ] **CB-05** Escanear mientras el foco está en otro campo: documentado (el lector escribe donde esté el foco; para agregar al carrito el foco debe estar en el buscador o F2).
- [ ] **CB-06** En entradas: escanear agrega el producto igual que en ventas.
- [ ] **CB-07** Importar CSV con columna codigo_barras → se guarda; duplicado → error claro.
- [ ] **CB-08** Pantalla Etiquetas: imprime hojas A4 con códigos Code128 (nombre + código); filtro por categoría y "solo sin código".
- [ ] **CB-09** Después de cambiar cantidad, quitar ítem o cancelar con el modal, el foco vuelve al buscador.

---

## 20. Proveedores

- [ ] **PV-01** Listado con búsqueda, cantidad de entradas y estado.
- [ ] **PV-02** Crear proveedor (nombre obligatorio y único); editar; desactivar/reactivar; todo auditado.
- [ ] **PV-03** Cajero recibe 403.
- [ ] **PV-04** Detalle: datos, historial de entradas con filtro de fechas, total comprado y productos que suele proveer con último costo.
- [ ] **PV-05** Nueva entrada: selector de proveedor con búsqueda + "Crear proveedor rápido".
- [ ] **PV-06** Reporte Compras por proveedor con CSV.
