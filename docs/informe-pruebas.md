# Informe de pruebas — NF Librería (FASE 1 del prompt maestro)

Verificación del 26/09/2026. Suite: **121 tests PHPUnit en verde (476 aserciones)**
+ **21 tests Dusk en verde** (Edge headless). `stock:verificar` sin diferencias.
BD de pruebas verificada con psql tras los flujos (ver evidencias).

Leyenda: OK = pasa; CORREGIDO = fallaba y se corrigió; MANUAL = lo prueba el usuario.

## 0. Preparación

| Código | Verificación | Resultado |
|---|---|---|
| P-01 | `migrate:fresh --seed` ejecutado varias veces sin errores | OK |
| P-02 | `php artisan test` 121/121 + `php artisan dusk` 21/21 | OK |
| P-03 | `stock:verificar` sin diferencias | OK |
| P-04 | `serve` + recorridos de pantallas (capturas Dusk) | OK |
| P-05 | Dos navegadores | MANUAL |

## 1. Login y sesión

| Código | Verificación | Resultado |
|---|---|---|
| L-01 | AutenticacionTest: rutas protegidas redirigen | OK |
| L-02 | AutenticacionTest: debe_cambiar redirige | OK |
| L-03 | AutenticacionTest: actual incorrecta falla | OK |
| L-04 | AutenticacionTest: mínimo 8 y confirmación | OK |
| L-05 | AutenticacionTest: cambio correcto | OK |
| L-06 | AutenticacionTest: mensaje genérico + LOGIN_FALLIDO | OK |
| L-07 | AutenticacionTest: 429 al sexto intento | OK |
| L-08 | AutenticacionTest: login en mayúsculas funciona | OK |
| L-09 | AutenticacionTest: logout; atrás del navegador | MANUAL |
| L-10 | AutenticacionTest: cierra por inactividad | OK |
| L-11 | AutenticacionTest: desactivado es expulsado | OK |
| L-12 | VentaDuskTest: menú muestra usuario y rol | OK |
| L-13 | AutenticacionTest: 404 en español | OK |

## 2. Permisos por rol

| Código | Verificación | Resultado |
|---|---|---|
| R-01/02/03 | Feature: 403 por módulo y rol (usuarios, config, auditoría, categorías, productos, entradas, importar, reportes) | OK |
| R-04 | InterfazDuskTest: menú según rol | OK |
| R-05 | AutenticacionTest: texto 403 en español | OK |

## 3. Usuarios

| Código | Verificación | Resultado |
|---|---|---|
| U-01/02 | AdministracionTest: listado, búsqueda y filtros | OK |
| U-03/04/05 | AdministracionTest: crear, duplicado, formato | OK |
| U-06/07/08/09/10 | AdministracionTest: cambio obligado, editar, restablecer, desactivar/reactivar | OK |
| U-11/12 | AdministracionTest: autodesactivación y auto-degradación bloqueadas; último admin protegido (servicio) | OK |
| U-13 | AdministracionTest: sin texto "Eliminar" | OK |

## 4. Configuración

| Código | Verificación | Resultado |
|---|---|---|
| C-01/02 | AdministracionTest: guarda y audita; verificado en ticket (CajaTest) | OK |
| C-03 | AdministracionTest: minutos fuera de rango | OK |
| C-04 | AdministracionTest + VentaTest (negativo on/off) | OK |
| C-05 | CajaTest: script auto-print según opción | OK |

## 5. Auditoría

| Código | Verificación | Resultado |
|---|---|---|
| A-01/02/03 | AdministracionTest + consultas psql en libreria_dev | OK |
| A-04 | AdministracionTest: filtros por acción y usuario | OK |
| A-05 | AdministracionTest: detalle en tabla clave/valor | OK |
| A-06 | AutenticacionTest: ninguna contraseña en auditoría | OK |
| A-07 | AdministracionTest: sin Editar en auditoría | OK |

## 6. Categorías

| Código | Verificación | Resultado |
|---|---|---|
| K-01–K-09 | CategoriaTest (crear/editar/desactivar, 403, duplicado insensible, auditoría) + seeder con 11 | OK |

## 7. Productos

| Código | Verificación | Resultado |
|---|---|---|
| PR-01 | ProductosDemoSeeder (30, stock vía StockService) | OK |
| PR-02/03/04/05/06 | ProductoTest: listado, búsqueda ILIKE, filtros, badge | OK |
| PR-07 | ProductoTest + InterfazDuskTest (botón llena código) | OK |
| PR-08/09 | ProductoTest: mayúsculas, duplicado | OK |
| PR-10 | InterfazDuskTest: advertencia y confirmación | OK |
| PR-11 | StockTest: stock inicial + INICIAL | OK |
| PR-12 | ProductoTest: servicio sin control de stock | OK |
| PR-13 | ProductoTest/StockTest: edición no toca stock | OK |
| PR-14 | ProductoTest: CAMBIO_PRECIO auditado | OK |
| PR-15 | ProductoTest: inactivos fuera de buscadores | OK |
| PR-16/17 | ProductoTest: cajero sin precio_compra | OK |
| PR-18 | ProductoTest: HTML escapado | OK |

## 8. Stock, ajustes y kardex

| Código | Verificación | Resultado |
|---|---|---|
| S-01–S-09 | StockTest (kardex, ajustes ±, sin movimiento si igual, motivo, AJUSTE_STOCK, 403, stock bajo, consistencia) | OK |

## 9. Entradas

| Código | Verificación | Resultado |
|---|---|---|
| E-01–E-10 | EntradaTest (servicio + HTTP) e InterfazDuskTest (buscador, total en vivo) | OK |
| E-11 | Doble clic: el botón se desactiva + modal confirma una vez | MANUAL |
| E-12/13/14/15 | EntradaTest: listado, detalle, anulación, sin doble anulación | OK |
| E-16 | stock:verificar | OK |

## 10. Importación

| Código | Verificación | Resultado |
|---|---|---|
| I-02–I-08 | ImportacionTest + InterfazDuskTest (vista previa y confirmación) | OK |
| I-01/I-09 | Plantilla BOM; I-09 Windows-1252 con tildes (test); abrir en Excel | MANUAL |
| I-10 | ImportacionTest: no CSV rechazado | OK |
| I-11 | ImportacionTest: auditoría única con resumen | OK |

## 11. Caja

| Código | Verificación | Resultado |
|---|---|---|
| V-01 | InterfazDuskTest: menú Ventas | OK |
| V-02 | InterfazDuskTest: foco inicial en buscador | OK |
| V-03/04/05/06 | VentaDuskTest: buscar, flechas+Enter, código exacto, foco vuelve | OK |
| V-07/08/09 | VentaDuskTest + InterfazDuskTest (Quitar) | OK |
| V-10 | VentaDuskTest (F2/Esc+modal) | OK |
| V-11 | VentaDuskTest: sin campo descuento | OK |
| V-12/13 | VentaDuskTest: cambio, billetes, Exacto | OK |
| V-14 | CajaTest: recibido menor → error, nada guardado | OK |
| V-15/16/17 | CajaTest + VentaDuskTest (efectivo, QR; transferencia/tarjeta aceptados por validación) | OK |
| V-18 | VentaTest: 25 × 0,30 = 7,50 sin mover stock | OK |
| V-19 | CajaTest: stock y movimiento VENTA | OK |
| V-20/21 | VentaTest: negativo permitido/bloqueado | OK |
| V-22/23 | VentaTest: descuento encargado + auditoría; mayor al subtotal rechazado | OK |
| V-24/25 | CajaTest: mismo token no duplica (doble clic y F5 cubiertos por idempotencia) | OK |
| V-26 | beforeunload implementado | MANUAL |
| V-27 | VentaDuskTest: error conserva carrito y reactiva botón | OK |
| V-28 | ProductoTest: inactivos fuera de buscadores | OK |
| V-29 | VentaDuskTest: sin scroll horizontal en 1366×768 | OK |
| V-30 | VentaTest: detalle conserva precio viejo | OK |

## 12. Ticket

| Código | Verificación | Resultado |
|---|---|---|
| T-01 | CajaTest: datos completos (número, método, cliente, totales, cambio) | OK |
| T-02 | Impresión sin menú en 80 mm | MANUAL |
| T-03 | InterfazDuskTest: foco en Nueva venta | OK |
| T-04/05 | CajaTest: 403 otro cajero, encargado sí | OK |
| T-06 | CajaTest: script auto-print según configuración | OK |
| T-07 | CajaTest: ANULADA en ticket | OK |

## 13. Historial

| Código | Verificación | Resultado |
|---|---|---|
| H-01/02 | HistorialTest: cajero solo lo suyo aunque manipule URL | OK |
| H-03/04 | HistorialTest: encargado filtra; resumen solo COMPLETADA | OK |
| H-05/06 | HistorialTest: detalle + cajero sin botón Anular | OK |
| H-07 | InterfazDuskTest: anulación con modal y motivo | OK |
| H-08/09/10 | HistorialTest: estado, datos, stock, auditoría, sin doble | OK |
| H-11 | VentaService: controla_stock=false no mueve (VentaTest) | OK |
| H-12 | stock:verificar | OK |

## 14. Panel y reportes

| Código | Verificación | Resultado |
|---|---|---|
| D-01–D-05 | ReporteTest + InterfazDuskTest (panel visible) | OK |
| D-06 | Comparación manual contra historial | MANUAL |
| RP-01–RP-08 | ReporteTest (totales, top, CSV, 403, valorizado, cierre) | OK |
| RP-09 | CSV con BOM verificado en tests; abrir en Excel | MANUAL |
| RP-10 | ReporteTest: 403 cajero | OK |

## 15. Backups

| Código | Verificación | Resultado |
|---|---|---|
| B-01 | pgpass.conf + backup.config.ps1 existen (verificado en esta PC) | OK |
| B-02/03 | backup.ps1 ejecutado real: archivo + log + registro en BD | OK |
| B-04/05 | Copia externa configurada / ausente | MANUAL |
| B-06 | probar-restauracion.ps1 real: **RESTAURACIÓN OK** (30/60/151/174/3 iguales) | OK |
| B-07 | Tarea programada (requiere administrador) | MANUAL |
| B-08/09 | BackupAlertaTest: dato + alerta | OK |
| B-10 | `git status` limpio de backups y `git check-ignore` | OK |
| B-11 | restaurar.ps1 (cuidado: toca datos) | MANUAL |

## 16. Combinadas

| Código | Verificación | Resultado |
|---|---|---|
| X-01 | Dos procesos reales paralelos (uno bloquea 6 s con lockForUpdate): A vende en 6,1 s, B espera 4,3 s y falla con StockInsuficienteException; 1 venta, stock 0, sin negativos. Evidencia en progreso.md | OK |
| X-02 | Flujo en BD de pruebas: 0 → +50 → −10 → +10 → −2 = 48; kardex completo; auditoría ANULAR/AJUSTE_STOCK; stock:verificar limpio | OK |
| X-03/X-04 | stock:verificar + suites en verde | OK |
| X-05/X-06/X-07 | Sin internet / 419 tras 2 h / red local | MANUAL |

## 17. Diseño

| Código | Verificación | Resultado |
|---|---|---|
| DS-01 | Login muestra logo (captura Dusk) | OK |
| DS-02–DS-12, DS-14–DS-16 | Revisión visual general | MANUAL |
| DS-13 | InterfazDuskTest: título con negocio + favicon presente | OK |
| DS-17 | /estilos 200 como admin en local (serve); 404 fuera de local (ReporteTest) | OK |

## Correcciones aplicadas durante la fase (con prueba y error real)

1. `pedirConfirmacion` ignoraba el callback (`alConfirmar` vs `callback`): el modal de cancelar venta no vaciaba el carrito. CORREGIDO (ventas.js + doc).
2. Enter con resultados aún no cargados no agregaba (tipeo rápido/lector): fallback con búsqueda inmediata en ventas.js. CORREGIDO.
3. `scroll-behavior: smooth` de Bootstrap rompía los clics de WebDriver: `:root { scroll-behavior: auto }` en app.css. CORREGIDO.
4. Tests con `keys()` en arreglo anidado solo convertían la primera tecla: separar llamadas. CORREGIDO (solo tests).
5. `AuditoriaService::registrar` ahora acepta entidad como string (`importacion`).
6. CSV de reportes como respuesta en memoria (testeable) en vez de stream.
7. Carbon 3 `diffInHours` con signo en alerta de backup: `abs()`.
8. `probar-restauracion.ps1`: sintaxis compatible con PowerShell 5.1.
