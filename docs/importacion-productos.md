# Importar productos desde Excel

## Preparar el archivo en Excel

1. Entra a Productos → "Importar desde Excel/CSV" y descarga la **plantilla**.
2. Ábrela en Excel. Tiene estos encabezados (no los cambies):
   `codigo;nombre;categoria;marca;unidad;precio_compra;precio_venta;stock_inicial;stock_minimo;controla_stock`
3. Agrega una fila por producto:
   - `codigo`: código interno, único (ej: `CUA-001`).
   - `categoria`: debe existir o se crea si marcaste la opción.
   - `unidad`: `unidad`, `paquete`, `caja`, `resma`, `hoja` o `docena`.
   - Precios: con coma o punto decimal (`12,50` o `12.50`).
   - `controla_stock`: `si`/`no` (vacío = sí). Usa `no` para servicios como fotocopias.
4. Guárdalo como **"CSV UTF-8 (delimitado por comas)"**: Archivo → Guardar como → tipo `CSV UTF-8 (delimitado por comas) (*.csv)`.
   - Si tu Excel solo ofrece "CSV (delimitado por comas)", igual sirve: el sistema detecta `;` o `,` y convierte tildes automáticamente.

## Importar en el sistema

1. Sube el archivo, elige qué hacer si el código ya existe (**Omitir** o **Actualizar datos**, que nunca toca el stock) y revisa la **vista previa**.
2. Confirma: todo se guarda en una sola transacción y muestra el resumen.

El `stock_inicial` de productos nuevos se registra como movimiento INICIAL en el kardex.
