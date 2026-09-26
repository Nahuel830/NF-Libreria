# Progreso del bloque 4–13 — NF Librería

> Bitácora de ejecución del bloque grande. Si la sesión se reinicia, continuar desde el siguiente prompt pendiente.

## Estado

- [x] PROMPT 0–2: commiteados (`e790a5f`, `b987fc5`, `cc365e8`, `87b27b1`).
- [x] PROMPT 3: commiteado local (`ede4fcf`, sin push).
- [x] PROMPT 4 — Categorías (`4b083ae`): tabla `categorias` (unique + índice único en `lower(nombre)`), CRUD sin eliminar, auditoría CREAR/EDITAR/ACTIVAR/DESACTIVAR, menú Inventario, `CategoriasSeeder` (11 categorías, todos los entornos), `CategoriaFactory`. Tests: 34/34 en verde (145 aserciones). Decisión: unicidad insensible a mayúsculas validada en Form Request + índice DB; conteo de productos fijo en 0 hasta el PROMPT 5 (relación `productos()` preparada).
- [x] PROMPT 5 — Productos (`feat: módulo de productos`): tabla `productos` (checks, índices, `lower(nombre)`), CRUD con Gates ver/gestionar, sugerir-código `CUA-001`, advertencia venta<compra con confirmación, CAMBIO_PRECIO auditado, detalle con placeholder de kardex, `ProductosDemoSeeder` (30 productos, solo local). Tests: 43/43 en verde (187 aserciones). Decisión: `stock` fuera de `$fillable` para que ningún formulario lo toque.
- [x] PROMPT 6 — Stock, ajustes y kardex (`feat: servicio de stock, ajustes y kardex`): `movimientos_stock` (checks cantidad≠0 y consistencia), `StockService` (mover con LogicException fuera de transacción, lockForUpdate, respeta controla_stock y permitir_stock_negativo, `StockInsuficienteException`; bloquearProductos ordenados; ajustar con auditoría AJUSTE_STOCK; verificarConsistencia), `stock:verificar` (tabla + exit 1), stock inicial en crear producto (misma transacción), ajuste con motivo mín. 5 + sugerencias, kardex paginado con badges y referencias "Venta #000123", `/inventario/stock-bajo` ordenado por diferencia, seeder con stock 0–80 vía StockService. Tests: 55/55 en verde (217 aserciones). Decisión: test de LogicException sale/restaura la transacción de RefreshDatabase.
- [x] PROMPT 7 — Entradas (`feat: entradas de mercadería`): `entradas_stock` + `detalle_entradas` (checks), `EntradaService` (agrupa repetidos, transacción con bloqueo ordenado, totales con bcmath, actualiza precio_compra + CAMBIO_PRECIO, anular con pre-chequeo de negativos que lista productos, sin revertir precio), buscador `/api-interna/productos/buscar` (ver-productos, reutilizable en ventas), `public/js/entradas.js`, vistas + modal de anulación, kardex enlaza entradas. Tests: 63/63 en verde (252 aserciones). Decisión: `mover()` ahora recibe `referenciaTipo`/`referenciaId` explícitos (`'entrada'`/`'venta'`).
- [x] PROMPT 8 — Importación CSV (`feat: importación de productos desde CSV`): plantilla con BOM, detección de separador/encoding, precios con coma o punto, vista previa con token temporal en storage, confirmación en UNA transacción, INICIAL para stock>0, auditoría única CREAR con entidad `importacion` (registrar acepta string), `docs/importacion-productos.md`. Tests: 68/68 en verde (289 aserciones).
- [x] PROMPT 9 — Ventas lógica (`feat: lógica de ventas y anulaciones con tests`): `ventas` + `detalle_ventas` (checks), enum `MetodoPago`, `VentaService` (idempotencia por token, precios siempre de BD, bcmath, descuento solo con `aplicar-descuentos`, efectivo con cambio, anular con `anular-ventas` y motivo, auditoría solo con descuento o anulación, `calcularTotales`). Tests primero: 83/83 en verde (339 aserciones), stock consistente.
- [ ] PROMPT 10 — Ventas: caja y ticket.
- [ ] PROMPT 11 — Historial y anulación.
- [ ] PROMPT 12 — Panel y reportes (+ VentasDemoSeeder local).
- [ ] PROMPT 13 — Backups y restauración.

## Decisiones para revisar

(nada por ahora)

## Acciones manuales pendientes

(nada por ahora)
