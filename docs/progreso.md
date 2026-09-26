# Progreso del bloque 4–13 — NF Librería

> Bitácora de ejecución del bloque grande. Si la sesión se reinicia, continuar desde el siguiente prompt pendiente.

## Estado

> PROMPT MAESTRO en curso (fases 0–7). Prevalece sobre AGENTS.md en caso de contradicción.

- [x] FASE 0 — Entorno Dusk (`test: entorno de pruebas de navegador con Dusk`): `laravel/dusk` solo dev, `DuskTestCase` con Edge headless vía `msedgedriver` (winget, v154), base `libreria_dusk`, `.env.dusk.local` (gitignoreado) + ejemplo, smoke `LoginTest` en verde, docs/desarrollo.md. Tests PHPUnit: 103/103. Decisión: sin Chrome en la PC se usa Edge; endpoints viejos de msedgedriver están muertos, el que funciona es el paquete winget `Microsoft.EdgeDriver`.
- [x] FASE 1 — Verificación (`test: verificación automatizada del plan de pruebas`): 121 PHPUnit + 21 Dusk en verde; X-01 con 2 procesos reales (B esperó 4,3 s el lock y falló limpio); X-02 flujo completo verificado en BD (stock final 48); B-06 RESTAURACIÓN OK real (conteos iguales); `docs/informe-pruebas.md` con todos los códigos. Correcciones: bug `callback` en pedirConfirmacion, fallback Enter en ventas.js, scroll auto, CSV en memoria, abs() en backup, PS5.1 en scripts.

- [x] PROMPT 0–2: commiteados (`e790a5f`, `b987fc5`, `cc365e8`, `87b27b1`).
- [x] PROMPT 3: commiteado local (`ede4fcf`, sin push).
- [x] PROMPT 4 — Categorías (`4b083ae`): tabla `categorias` (unique + índice único en `lower(nombre)`), CRUD sin eliminar, auditoría CREAR/EDITAR/ACTIVAR/DESACTIVAR, menú Inventario, `CategoriasSeeder` (11 categorías, todos los entornos), `CategoriaFactory`. Tests: 34/34 en verde (145 aserciones). Decisión: unicidad insensible a mayúsculas validada en Form Request + índice DB; conteo de productos fijo en 0 hasta el PROMPT 5 (relación `productos()` preparada).
- [x] PROMPT 5 — Productos (`feat: módulo de productos`): tabla `productos` (checks, índices, `lower(nombre)`), CRUD con Gates ver/gestionar, sugerir-código `CUA-001`, advertencia venta<compra con confirmación, CAMBIO_PRECIO auditado, detalle con placeholder de kardex, `ProductosDemoSeeder` (30 productos, solo local). Tests: 43/43 en verde (187 aserciones). Decisión: `stock` fuera de `$fillable` para que ningún formulario lo toque.
- [x] PROMPT 6 — Stock, ajustes y kardex (`feat: servicio de stock, ajustes y kardex`): `movimientos_stock` (checks cantidad≠0 y consistencia), `StockService` (mover con LogicException fuera de transacción, lockForUpdate, respeta controla_stock y permitir_stock_negativo, `StockInsuficienteException`; bloquearProductos ordenados; ajustar con auditoría AJUSTE_STOCK; verificarConsistencia), `stock:verificar` (tabla + exit 1), stock inicial en crear producto (misma transacción), ajuste con motivo mín. 5 + sugerencias, kardex paginado con badges y referencias "Venta #000123", `/inventario/stock-bajo` ordenado por diferencia, seeder con stock 0–80 vía StockService. Tests: 55/55 en verde (217 aserciones). Decisión: test de LogicException sale/restaura la transacción de RefreshDatabase.
- [x] PROMPT 7 — Entradas (`feat: entradas de mercadería`): `entradas_stock` + `detalle_entradas` (checks), `EntradaService` (agrupa repetidos, transacción con bloqueo ordenado, totales con bcmath, actualiza precio_compra + CAMBIO_PRECIO, anular con pre-chequeo de negativos que lista productos, sin revertir precio), buscador `/api-interna/productos/buscar` (ver-productos, reutilizable en ventas), `public/js/entradas.js`, vistas + modal de anulación, kardex enlaza entradas. Tests: 63/63 en verde (252 aserciones). Decisión: `mover()` ahora recibe `referenciaTipo`/`referenciaId` explícitos (`'entrada'`/`'venta'`).
- [x] PROMPT 8 — Importación CSV (`feat: importación de productos desde CSV`): plantilla con BOM, detección de separador/encoding, precios con coma o punto, vista previa con token temporal en storage, confirmación en UNA transacción, INICIAL para stock>0, auditoría única CREAR con entidad `importacion` (registrar acepta string), `docs/importacion-productos.md`. Tests: 68/68 en verde (289 aserciones).
- [x] PROMPT 9 — Ventas lógica (`feat: lógica de ventas y anulaciones con tests`): `ventas` + `detalle_ventas` (checks), enum `MetodoPago`, `VentaService` (idempotencia por token, precios siempre de BD, bcmath, descuento solo con `aplicar-descuentos`, efectivo con cambio, anular con `anular-ventas` y motivo, auditoría solo con descuento o anulación, `calcularTotales`). Tests primero: 83/83 en verde (339 aserciones), stock consistente.
- [x] PROMPT 10 — Caja y ticket (`feat: pantalla de venta y ticket`): buscador ampliado (incluye sin control de stock, devuelve precio_venta), `/ventas/nueva` (token UUID servidor, carrito con teclado F2/F9/Esc, descuento solo con permiso, efectivo con cambio y billetes, POST por fetch+JSON con fallback a formulario), ticket 80mm sin menú con "Documento sin valor fiscal" y "*** ANULADA ***", `imprimir_automatico` en configuración, botón Vender, `docs/impresion-tickets.md`. Tests: 89/89 en verde (359 aserciones).
- [x] PROMPT 11 — Historial (`feat: historial y anulación de ventas`): `/ventas` (cajero solo propias del día forzado en servidor; filtros y resumen solo-COMPLETADA para admin/encargado; 30 por página), detalle con reimprimir y anular por modal, anulación vía `VentaService` con `can:anular-ventas`, kardex enlaza ventas, menú Ventas con Nueva + Historial. Tests: 94/94 en verde (381 aserciones).
- [x] PROMPT 12 — Panel y reportes (`feat: panel de inicio y reportes`): panel admin (tarjetas, métodos hoy, gráfico 7 días con Chart.js 4.4.7 local, stock bajo top 10, últimas 10) y cajero (sus ventas + últimas 5); 8 reportes con agregaciones SQL solo-COMPLETADA y CSV con BOM; cierre imprimible A4/80mm; menú Reportes. `VentasDemoSeeder` (60 ventas 10 días, 3 anuladas, vía VentaService). Tests: 99/99 en verde (395 aserciones). Decisión: CSV como respuesta en memoria (testeable) en vez de stream.
- [x] PROMPT 13 — Backups (`feat: backups automáticos y restauración`): `backup.ps1` (pg_dump -Fc, valida con pg_restore --list, copia externa opcional, retención 30 días, log + `backup:registrar-resultado`), `instalar-tarea-backup.ps1` (diaria 13:00/20:30), `probar-restauracion.ps1` (temporal + conteos + RESTAURACIÓN OK/ERROR), `restaurar.ps1` (confirmación + backup previo), alerta en panel (>24h o error), `docs/backups.md` + `docs/restauracion.md`. Ejecutado: backup.ps1 OK real (0.06 MB, validado, registrado). Tests: 102/102 en verde (405 aserciones). Decisión: sin pgpass.conf en esta PC, las pruebas usaron `PGPASSWORD` de entorno; no se guardó ninguna contraseña.

## Decisiones para revisar

(nada por ahora)

## Pruebas manuales para el usuario

1. **Dar CREATEDB a `libreria_dev`** (lo necesitan `probar-restauracion.ps1` y futuros tests de concurrencia): como superusuario postgres: `ALTER USER libreria_dev CREATEDB;`. Verificado: el usuario NO lo tiene.
2. **Crear `%APPDATA%\postgresql\pgpass.conf`** con `localhost:5432:libreria_dev:libreria_dev:TU_CONTRASEÑA` (ver `docs/backups.md`). Verificado: no existe.
3. **Instalar la tarea programada de backup** (PowerShell como administrador). Ver `docs/backups.md`.

## Acciones manuales pendientes

1. **Dar permiso CREATEDB a `libreria_dev`** (para `probar-restauracion.ps1`): como superusuario postgres en pgAdmin/psql: `ALTER USER libreria_dev CREATEDB;`. Luego ejecutar `.\scripts\probar-restauracion.ps1` y esperar RESTAURACIÓN OK.
2. **Crear `pgpass.conf`** según `docs/backups.md` para no depender de `PGPASSWORD`.
3. **Instalar la tarea programada** con PowerShell como administrador: `.\scripts\instalar-tarea-backup.ps1`.
