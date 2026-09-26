# Progreso del bloque 4–13 — NF Librería

> Bitácora de ejecución del bloque grande. Si la sesión se reinicia, continuar desde el siguiente prompt pendiente.

## Estado

- [x] PROMPT 0–2: commiteados (`e790a5f`, `b987fc5`, `cc365e8`, `87b27b1`).
- [x] PROMPT 3: commiteado local (`ede4fcf`, sin push).
- [x] PROMPT 4 — Categorías (`4b083ae`): tabla `categorias` (unique + índice único en `lower(nombre)`), CRUD sin eliminar, auditoría CREAR/EDITAR/ACTIVAR/DESACTIVAR, menú Inventario, `CategoriasSeeder` (11 categorías, todos los entornos), `CategoriaFactory`. Tests: 34/34 en verde (145 aserciones). Decisión: unicidad insensible a mayúsculas validada en Form Request + índice DB; conteo de productos fijo en 0 hasta el PROMPT 5 (relación `productos()` preparada).
- [x] PROMPT 5 — Productos (`feat: módulo de productos`): tabla `productos` (checks, índices, `lower(nombre)`), CRUD con Gates ver/gestionar, sugerir-código `CUA-001`, advertencia venta<compra con confirmación, CAMBIO_PRECIO auditado, detalle con placeholder de kardex, `ProductosDemoSeeder` (30 productos, solo local). Tests: 43/43 en verde (187 aserciones). Decisión: `stock` fuera de `$fillable` para que ningún formulario lo toque.
- [ ] PROMPT 6 — Stock, ajustes y kardex.
- [ ] PROMPT 7 — Entradas de mercadería.
- [ ] PROMPT 8 — Importación CSV.
- [ ] PROMPT 9 — Ventas: lógica y tests.
- [ ] PROMPT 10 — Ventas: caja y ticket.
- [ ] PROMPT 11 — Historial y anulación.
- [ ] PROMPT 12 — Panel y reportes (+ VentasDemoSeeder local).
- [ ] PROMPT 13 — Backups y restauración.

## Decisiones para revisar

(nada por ahora)

## Acciones manuales pendientes

(nada por ahora)
