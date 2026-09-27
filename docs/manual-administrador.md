# Manual del administrador / encargado — NF Librería

## Usuarios

1. Administración → Usuarios → Nuevo usuario: nombre, usuario (minúsculas, sin espacios), rol y contraseña inicial. El usuario deberá cambiarla al entrar.
2. Si alguien olvida su contraseña: abre el usuario → "Contraseña" (ícono llave) → escribe una temporal → el usuario la cambia al entrar.
3. Para dar de baja: "Desactivar" (nunca se eliminan). Para dar de alta: "Activar".
4. Reglas: nadie puede desactivarse a sí mismo; siempre debe quedar al menos un administrador activo. **Recomendación: tener 2 usuarios admin** para poder restablecerse contraseñas mutuamente.

## Configuración y logo

Administración → Configuración: nombre del negocio (sale en la barra y tickets), dirección, teléfono, mensaje del ticket, permitir stock negativo, minutos de inactividad, impresión automática y exigir caja abierta. Para el logo: sube un PNG o JPG (máx. 1 MB); "Quitar logo" vuelve al de por defecto. Todo cambio queda en auditoría.

## Categorías

Inventario → Categorías: crear, editar, desactivar (no se eliminan). Los nombres no se repiten aunque cambien mayúsculas.

## Productos

1. Inventario → Productos → Nuevo: código (usa "Sugerir código"), nombre, categoría, precios, stock mínimo. Si el precio de venta es menor al de compra, te pedirá confirmar.
2. El stock NO se escribe a mano: se carga con stock inicial al crear, con entradas o con ajustes.
3. Para servicios (fotocopias): desactiva "Controla stock".
4. Código de barras: escríbelo o escanéalo en el campo del producto. Para imprimir etiquetas: Productos → Etiquetas.

## Importación desde Excel

1. Productos → Importar → Descargar plantilla.
2. Llena en Excel y guarda como "CSV UTF-8 (delimitado por comas)".
3. Sube, revisa la vista previa (Nuevo/Actualizar/Omitir/Error) y confirma.
4. "Omitir" no toca lo existente; "Actualizar" cambia datos pero nunca el stock.

## Entradas de mercadería

1. Inventario → Entradas → Nueva entrada: elige el proveedor (o créalo rápido), documento y observaciones.
2. Busca productos y agrega cantidades y costos. El total se calcula solo.
3. Con "Actualizar precio de compra" marcado, se actualiza el costo de cada producto.
4. Para corregir una entrada: ábrela y "Anular" con motivo (devuelve el stock). No se puede anular dos veces.

## Proveedores

Inventario → Proveedores: datos (nombre, NIT, contacto), historial de entradas, total comprado y productos que suele proveer con su último costo.

## Ajustes de stock y conteo físico

- Corrección puntual: abre el producto → "Ajustar stock": escribe lo contado y el motivo.
- Conteo general: Inventario → Conteo físico: filtra por categoría (o descarga la hoja CSV para contar en papel), escribe las cantidades y guarda. Solo se mueve lo que cambió. Todo queda en el kardex y en auditoría.

## Stock bajo

Inventario → Stock bajo: lo que está en o bajo el mínimo, ordenado por urgencia.

## Anulaciones y devoluciones

- Anular (toda la venta): desde el detalle → "Anular" con motivo. Devuelve el stock y queda auditada. No se puede anular dos veces.
- Devolución parcial: desde el detalle → "Devolver": elige cantidades, motivo y reembolso. Si es en efectivo sale un egreso de la caja. La venta muestra sus devoluciones.

## Caja (apertura y cierre)

1. Cada vendedor abre su caja con el monto inicial (Ventas → Caja → Abrir caja).
2. Las ventas en efectivo alimentan la caja; QR/transferencia/tarjeta solo se informan.
3. Ingresos/egresos manuales con concepto (ej: pago de luz).
4. Cierre: cuenta por denominación (suma sola), compara con lo esperado y guarda la diferencia con observaciones si la hay.
5. Puedes cerrar la caja de otro usuario (queda registrado quién la cerró).
6. Historial de cajas: todas, con filtros.

## Clientes

Ventas → Clientes: crear, editar, desactivar. En la venta se busca por nombre o CI/NIT. Detalle con historial, total y última compra. Reporte "Mejores clientes".

## Reportes y cierre del día

Reportes: resumen por día, por cajero, por método, productos y categorías más vendidas, cierre del día, inventario valorizado, movimientos y compras. Todos con rango de fechas y exportación a CSV (se abre en Excel). Los totales restan las devoluciones. El cierre del día muestra métodos, anuladas con motivo y las cajas del día; se puede imprimir.

## Auditoría

Administración → Auditoría: quién hizo qué, cuándo y desde qué IP. Filtra por fecha, usuario y acción. "Ver detalle" muestra valores antes/después. Es solo lectura.

## Backups

1. Revisa cada semana que el panel diga un backup reciente (alerta amarilla si tiene más de 24 h o falló).
2. Una vez al mes ejecuta `probar-restauracion.ps1`: debe decir RESTAURACIÓN OK.
3. Mantén copia fuera de la PC (nube o disco externo).
4. Para restaurar en emergencia: `docs/restauracion.md`.

## Verificación en dos pasos (TOTP)

1. Los administradores están obligados a activarla (los encargados pueden, desde "Mi seguridad"). Sirven Google Authenticator, Microsoft Authenticator, Aegis y 2FAS.
2. Al activar se muestran 10 códigos de recuperación: se ven una sola vez (imprímelos con el botón "Imprimir" y guárdalos en papel). Cada uno sirve para entrar una sola vez si pierdes el celular.
3. El cajero no usa TOTP por agilidad en caja (escribir un código en cada venta frenaría la fila); se compensa con contraseña fuerte, límites de intentos y restricción opcional por IP.
4. Si un admin pierde el teléfono:
    - Otro administrador: Usuarios → Editar al usuario → "Restablecer verificación en dos pasos". Al entrar, deberá configurarla de nuevo (queda en auditoría).
    - Si no hay otro admin disponible, en el servidor: `php artisan totp:restablecer <usuario> --motivo="<motivo>"`. El motivo es obligatorio y queda en auditoría.
    - El usuario entra con su contraseña y activa de nuevo con su teléfono nuevo (QR + código + guardar los 10 códigos de recuperación en papel).

## Qué hacer si la PC principal falla

1. No entres en pánico: los datos están en los backups (local + copia externa).
2. Prepara otra PC con el instalador (`docs/instalacion.md`), restaura el backup más reciente (`docs/restauracion.md`).
3. Mientras tanto, vende en papel y carga después.
4. Revisa stock y auditoría al volver.
