# Revisión de seguridad — NF Librería

Revisión del 26/09/2026 sobre todo el código (rutas, controladores, servicios, vistas, JS, git). Criterio: Alta = explota datos o accesos; Media = endurecimiento necesario; Baja = recomendación.

## Corregidos (Alta/Media)

| Archivo | Problema | Gravedad | Corrección |
|---|---|---|---|
| public/js/ventas.js (carrito y resultados) | `innerHTML` con nombre/código de producto sin escapar: XSS almacenado si el nombre trae HTML | Alta | `esc()` en todas las interpolaciones |
| public/js/entradas.js (filas) | Igual que arriba | Alta | `esc()` en la interpolación |
| DevolucionController@ticket | Sin control de acceso: cualquier cajero veía tickets de devolución ajenos | Media | Mismo control que ticket de venta (propias o ver-todas-las-ventas) |
| VentaRequest / EntradaRequest | `items` sin tope (posible abuso con miles de líneas) | Media | `max:200` |
| routes/web.php (clientes/buscar) | Registrada después de `/{cliente}`: caía en el binding y daba 500 | Media | Grupo buscar/rápido ANTES (ya corregido en FASE 3.2) |

## Verificados como seguros (sin cambios)

| Punto | Resultado |
|---|---|
| Rutas (103 con `route:list`) | Todas con `auth` salvo login, `_dusk/*` (solo existe si el paquete dev está instalado), `up` (health sin datos) y `storage/*` (sirve archivos públicos; el PUT exige URL firmada) |
| Gates en acciones | Cada controlador verifica su Gate (middleware `can:`, FormRequest `authorize()` o `Gate::forUser` en servicios) |
| `{!! !!}` | Solo `productos/etiquetas.blade.php` con SVG generado por picqer (gráficos, no HTML de usuarios) |
| SQL crudo | Todo con bindings o cadenas fijas internas (`devolucionesAgrupadas` solo recibe literales del código) |
| CSRF | `@csrf` en formularios; fetch con `X-CSRF-TOKEN` |
| `productos.stock` | Solo `StockService` lo modifica (verificado por búsqueda) |
| Dinero | bcmath/decimal en servicios; `float` solo para mostrar en `bs()` y CSV |
| Auditoría | Nunca guarda contraseñas (test automático) ni tokens (limpieza en servicio) |
| Form Requests | Todas las pantallas validan tipos, máximos y existencia; errores en español |
| Asignación masiva | `$fillable` explícitos; los controladores pasan solo claves elegidas (nunca `request()->all()`) |
| Subidas | Logo: `image+mimes:png,jpg,jpeg+max:1024`, nombre aleatorio en `storage/app/public/logo`, SVG bloqueado. CSV: `mimes:csv,txt+max:2048`, se procesa en storage privado |
| `/estilos` y seeders demo | Solo entorno `local` (test: 404 fuera de local) |
| Contraseñas | Hash de Laravel; `ADMIN_PASSWORD_INICIAL` solo en `.env` (gitignoreado) |
| `git ls-files` | Sin `.env`, backups, dumps, `backup.config.ps1` ni `vendor/` |

## Baja (recomendaciones, no corregidas)

- Archivos temporales de importación huérfanos (si se abandona la vista previa): purgarlos con una tarea o al login.
- `importar` acepta `.txt`: documentado como alternativa válida, no un riesgo.
- `detalle_conteo` de caja y `datos_*` de auditoría confían en validación de entrada (tipos validados).
- Cabeceras de seguridad HTTP (CSP, HSTS) pendientes para el despliegue web (ver prompt futuro).
- Rate limiting solo en login; considerar throttle global si se expone a Internet.
