# Versiones

La versión vive en el archivo `VERSION` de la raíz y se muestra en el pie
de página. También se puede forzar con la variable `APP_VERSION`.
`config/app.php` la expone como `config('app.version')`.

## Cómo subir de versión

1. Prueba todo en desarrollo (`test`, `dusk`, `stock:verificar`, plan de pruebas).
2. Actualiza el archivo `VERSION` (formato `MAYOR.MENOR.PARCHE`).
3. Agrega la entrada al CHANGELOG de abajo.
4. Commit + tag: `git tag vX.Y.Z` y push del tag.

## CHANGELOG

### 1.0.0 (26/09/2026)

Primera versión para la librería:

- Usuarios y roles (admin, encargado, cajero), permisos, auditoría.
- Categorías, productos (con código de barras) e importación desde Excel/CSV.
- Inventario con kardex, entradas, ajustes y conteo físico.
- Punto de venta con ticket, descuentos, anulaciones y devoluciones parciales.
- Caja por vendedor (apertura, movimientos, cierre con arqueo).
- Clientes y proveedores con historial.
- Panel de inicio y reportes con CSV.
- Backups automáticos con prueba de restauración.
- Herramientas de puesta en marcha (`sistema:estado`, `sistema:limpiar-demo`).
