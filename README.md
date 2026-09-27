# NF Librería — sistema de gestión y ventas

Sistema web para una librería en Bolivia (moneda Bs., zona America/La_Paz).
Funciona en red local sin Internet: todo el CSS, JS, fuentes e íconos están
dentro del proyecto.

## Módulos

- Usuarios y roles (admin, encargado, cajero), permisos y auditoría.
- Categorías, productos (con código de barras) e importación desde Excel/CSV.
- Inventario con kardex, entradas de mercadería, ajustes y conteo físico.
- Punto de venta con ticket 80 mm, descuentos, anulaciones y devoluciones parciales.
- Caja por vendedor (apertura, movimientos, cierre con arqueo).
- Clientes y proveedores con historial y reportes.
- Panel de inicio y reportes con exportación a CSV.
- Backups automáticos con prueba de restauración.

## Stack

Laravel 13, PHP 8.4, PostgreSQL 17, Blade + Bootstrap 5 local, JavaScript
vanilla (sin Node ni Vite). Tests: PHPUnit (`php artisan test`) y Laravel Dusk
(`php artisan dusk`, solo desarrollo).

## Requisitos (desarrollo)

PHP 8.4 con `pdo_pgsql`, Composer 2, PostgreSQL 17, Git y Microsoft Edge
(para Dusk). Ver `docs/desarrollo.md`.

## Documentación

| Documento | Contenido |
|---|---|
| docs/propuesta.md | Propuesta original y decisiones |
| docs/desarrollo.md | Levantar el proyecto + Dusk |
| docs/usuarios-y-permisos.md | Matriz de permisos |
| docs/diseno.md | Identidad visual y componentes |
| docs/importacion-productos.md | Importar desde Excel |
| docs/impresion-tickets.md | Impresora térmica |
| docs/puesta-en-marcha.md | Primeros días con datos reales |
| docs/backups.md / docs/restauracion.md | Copias y restauración |
| docs/plan-de-pruebas.md | ~200 pruebas con códigos |
| docs/informe-pruebas.md | Resultados de la verificación |
| docs/pruebas-finales.md | Lista del día de instalación |
| docs/revision-seguridad.md | Auditoría de seguridad |
| docs/manual-cajero.md | Manual simple del cajero |
| docs/manual-administrador.md | Manual del admin/encargado |
| docs/progreso.md | Bitácora técnica del desarrollo |
| docs/CONTEXTO-PROYECTO.md | Contexto general para IAs y personas |
