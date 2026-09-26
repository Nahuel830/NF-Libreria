# Prompts finales — NF Librería

Continuación de `docs/prompts-opencode.md`. Todo lo que falta desde las pruebas hasta el uso real en la librería, más las ampliaciones posteriores.

## Orden

| # | Tarea | Cuándo |
|---|-------|--------|
| A | Corrección de fallos del plan de pruebas | Después de recorrer docs/plan-de-pruebas.md |
| B | Push de todo a GitHub | Cuando todo esté corregido |
| 15 | Revisión de seguridad, manuales y README | Antes de instalar |
| 14 | Instalación en la PC de la librería y red local | Con el código revisado |
| 17 | Preparación para el uso real (datos reales y día 1) | Antes de abrir con el sistema |
| 16 | Caja: apertura y cierre | Después de 1–2 semanas de uso |
| 18 | Proveedores | A pedido |
| 19 | Clientes | A pedido |
| 20 | Código de barras | A pedido |
| 21 | Devoluciones parciales | A pedido |

Reglas generales (las mismas de siempre): un prompt a la vez; los marcados con ⚠️ primero en modo Plan (Tab) y luego Build; probar antes de cada commit; pegar el `git diff` de lo crítico en el chat de Claude.

---

## A — Corrección de fallos

Copia la sección "Registro de fallos" de `docs/plan-de-pruebas.md` y pégala donde dice [PEGAR AQUÍ].

```text
Recorrí docs/plan-de-pruebas.md. Estos son los fallos y cambios encontrados. Lee AGENTS.md.

Para cada fallo, en orden:
1. Explica en 1–2 líneas la causa.
2. Corrige respetando AGENTS.md y docs/diseno.md.
3. Agrega o ajusta un test automático que cubra el caso (si es visual y no se puede testear, dilo).
4. Ejecuta php artisan test; debe quedar en verde.
5. Commit local con "fix: <descripción corta> (<código de prueba>)", sin push.

Para las "Mejoras o cambios que quiero": antes de implementarlas, muéstrame qué harías y espera mi confirmación en cada una.

Al terminar: php artisan migrate:fresh --seed, php artisan test y php artisan stock:verificar; muéstrame la lista de commits y una tabla con cada código de prueba y su estado (corregido / no se pudo / requiere decisión).

[PEGAR AQUÍ LOS FALLOS]
```

Después, vuelve a probar **solo** los códigos corregidos. Repite A hasta que todo pase.

---

## B — Subir todo a GitHub

```text
Todo probado. Verifica git status (limpio) y php artisan test (verde). Luego haz push de todos los commits a origin main. Después borra la rama remota de respaldo: git push origin --delete respaldo-bloque-4-13. Muéstrame git log --oneline -20.
```

Revisa en github.com/Nahuel830/NF-Libreria que el último commit aparezca en main y que NO esté el archivo `.env`.

---

## PROMPT 15 — Revisión de seguridad, manuales y README ⚠️

```text
Tarea: PROMPT 15 — preparación para producción: revisión de seguridad, pruebas faltantes y manuales. Lee AGENTS.md y docs/diseno.md.

## Parte 1 — Revisión de seguridad (primero SOLO informe, sin corregir)
Revisa TODO el código y genera docs/revision-seguridad.md con una tabla (archivo, línea, problema, gravedad Alta/Media/Baja, corrección propuesta). Busca:
- rutas sin middleware auth o sin control de permisos (lista todas las rutas con php artisan route:list y marca el middleware/permiso de cada una);
- acciones de controladores que no verifiquen el Gate correspondiente;
- uso de {!! !!} con datos de usuario;
- SQL con variables concatenadas (DB::raw, whereRaw, selectRaw, orderByRaw con datos del request);
- formularios o fetch que modifican datos sin CSRF;
- modificaciones de productos.stock fuera de StockService;
- cálculos de dinero con float o round() de PHP en lugar de bcmath/centavos;
- datos sensibles (contraseñas, tokens) en logs, auditoría o respuestas JSON;
- validación faltante en Form Requests (tipos, máximos, existencia de ids);
- asignación masiva insegura ($fillable demasiado amplio, request()->all());
- subida de archivos (logo, CSV): tipo, tamaño, nombre y ubicación seguros;
- rutas de desarrollo (/estilos, seeders demo) que podrían quedar activas en producción;
- archivos que no deberían estar en git (git ls-files).
Muéstrame el informe y ESPERA mi confirmación antes de corregir.

## Parte 2 — Correcciones (después de mi confirmación)
Corrige cada punto en un commit local separado "fix(seguridad): ...", con su test cuando sea posible.

## Parte 3 — Tests faltantes
Revisa que exista al menos un test de permisos por cada ruta que modifica datos (acceso permitido y acceso denegado). Agrega los que falten. Ejecuta php artisan test y stock:verificar.

## Parte 4 — Documentación para usuarios (lenguaje simple, pasos numerados, sin términos técnicos)
- docs/manual-cajero.md: iniciar sesión, cambiar contraseña, pantalla de venta paso a paso, atajos de teclado (F2, F9, Esc), cobrar en efectivo con cambio, QR/transferencia/tarjeta, fotocopias, imprimir y reimprimir ticket, consultar precios y stock, qué hacer si me equivoqué en una venta (avisar al encargado para anular), qué hacer si el sistema no responde, cerrar sesión. Incluye una "hoja rápida" de una página al inicio con lo esencial para imprimir y pegar junto a la caja.
- docs/manual-administrador.md: usuarios (crear, restablecer contraseña, desactivar), configuración y logo, categorías, productos, importación desde Excel, entradas de mercadería, ajustes de stock y conteo físico, stock bajo, anulación de ventas, reportes, cierre del día, auditoría, backups (cómo revisar que se hacen, cómo probar restauración), qué hacer si la PC principal falla, recomendación de tener 2 usuarios admin.
- docs/pruebas-finales.md: lista de verificación del día de instalación en la librería (servicios arrancan solos tras reiniciar, acceso desde cada PC, impresión de tickets en la impresora real, backup automático ejecutado, restauración probada, usuarios reales creados, demo NO cargado).
- README.md: qué es el sistema, módulos, stack, requisitos, enlaces a toda la documentación de docs/.

## Entrega
php artisan test en verde. Commit local "docs: manuales, revisión de seguridad y README". No hagas push. Muéstrame el resumen.
```

**Cómo probar:** lee el informe de seguridad (pégamelo si tienes dudas sobre algún punto). Después de las correcciones, repite rápido las secciones 1, 2 y 11 del plan de pruebas. Lee los manuales como si fueras el cajero: si algo no se entiende, pide que lo simplifique.

**Commit/push:**
```text
Funciona. Haz push a origin main.
```

---

## PROMPT 14 — Instalación en la PC de la librería y red local ⚠️

```text
Tarea: PROMPT 14 — preparar la instalación de producción en la PC principal de la librería (Windows 10/11) y el acceso desde la red local. Lee AGENTS.md. NO se usa php artisan serve en producción.

Arquitectura de producción:
- Apache HTTP Server para Windows (Apache Lounge, x64, compilado con VS17) instalado como servicio de Windows con inicio automático, con PHP 8.4 Thread Safe cargado como módulo (php8apache2_4.dll), DocumentRoot en la carpeta public/ del proyecto, AllowOverride All para el .htaccess de Laravel, mod_rewrite activo.
- PostgreSQL 17 como servicio, escuchando SOLO en localhost (listen_addresses = 'localhost'); pg_hba.conf solo local con scram-sha-256.
- Base de producción libreria_prod, dueña libreria_prod, contraseña fuerte distinta a la de desarrollo.
- Para la prueba de restauración: un rol aparte libreria_restore con CREATEDB (sin permisos sobre libreria_prod), usado solo por probar-restauracion.ps1. Ajusta ese script para aceptar usuario de restauración distinto al de la base principal (en desarrollo puede seguir siendo libreria_dev).
- Proyecto en C:\NF-Libreria (clonado desde GitHub). Backups en D:\Backups\NF-Libreria si existe D:, si no C:\Backups\NF-Libreria.

1. docs/instalacion.md, paso a paso para alguien con conocimientos básicos, con cada comando listo para copiar:
   a) Requisitos de la PC: Windows actualizado, SSD, 8 GB RAM recomendado, UPS recomendado, IP fija (ver red-local.md), desactivar suspensión automática en horario de trabajo.
   b) Instalar con winget: Git, Visual C++ Redistributable, PHP 8.4 (PHP.PHP.8.4), Composer (método manual con composer-setup.php en C:\composer), PostgreSQL 17 (--interactive). Apache Lounge: enlace oficial de descarga y dónde descomprimir (C:\Apache24).
   c) php.ini de producción: partir de php.ini-production; extension_dir absoluto; extensiones pdo_pgsql, pgsql, mbstring, openssl, fileinfo, curl, zip, intl; date.timezone America/La_Paz; opcache activado (opcache.enable=1, memory_consumption=128, validate_timestamps=1 con revalidate_freq=60); upload_max_filesize 5M; post_max_size 8M; display_errors Off; log_errors On. Explica que Apache usa el php.ini de la carpeta de PHP (PHPIniDir).
   d) Crear roles y base de producción con psql (usando $env:PGPASSWORD como en desarrollo), y el rol libreria_restore.
   e) Clonar el repositorio en C:\NF-Libreria; composer install --no-dev --optimize-autoloader; crear .env de producción (APP_ENV=production, APP_DEBUG=false, APP_URL=http://IP-DE-LA-PC, LOG_LEVEL=warning, SESSION_DRIVER=database, credenciales de producción, ADMIN_PASSWORD_INICIAL); key:generate; migrate --force; db:seed --force (solo configuración, categorías y admin: verificar que los seeders demo NO corren fuera de local); storage:link; config:cache; route:cache; view:cache.
   f) Permisos de escritura en storage\ y bootstrap\cache\ para la cuenta del servicio de Apache (comandos icacls).
   g) Configurar httpd.conf (ServerName, LoadModule php, PHPIniDir, AddHandler, DirectoryIndex index.php, DocumentRoot y <Directory> apuntando a C:\NF-Libreria\public, mod_rewrite, ocultar versión con ServerTokens Prod y ServerSignature Off). Registrar el servicio: httpd.exe -k install y configurarlo en inicio automático. Probar con httpd.exe -t.
   h) Configurar backups (pgpass.conf de la cuenta que ejecuta la tarea, backup.config.ps1 apuntando a libreria_prod, instalar tarea programada, carpeta de copia externa).
   i) Verificación final: reiniciar la PC y comprobar que Apache y PostgreSQL arrancan solos y el sistema responde; login admin y cambio de contraseña; backup manual; prueba de restauración.

2. docs/red-local.md:
   - Reservar IP fija para la PC principal: opción A reserva DHCP en el router (recomendada; explicar en general cómo se hace y qué datos pedir a quien administra el router), opción B IP estática en Windows (pasos con capturas descritas).
   - Marcar la red como Privada en Windows.
   - Firewall: permitir puerto 80 entrante SOLO en perfil Privado, con el comando New-NetFirewallRule exacto. Verificar que el 5432 NO esté abierto.
   - Acceso desde otras PCs: http://IP-DE-LA-PC-PRINCIPAL; crear acceso directo en el escritorio con el logo; recomendación de navegador (Chrome o Edge) y de configurar la impresora de tickets en la PC de caja.
   - Lista de verificación si una PC no conecta (misma red, ping, IP cambió, firewall, servicio de Apache detenido).

3. scripts/instalar-produccion.ps1: automatiza lo seguro (verificar requisitos y versiones, crear carpetas, composer install --no-dev, crear .env desde .env.example si no existe pidiendo los datos por consola sin mostrarlos en pantalla para contraseñas, key:generate, migrate --force, seed de producción, storage:link, caches, permisos icacls, regla de firewall, instalar tarea de backup). Idempotente (se puede ejecutar varias veces sin romper nada), con cada paso numerado y resultado OK/ERROR, y deteniéndose ante el primer error. Sin contraseñas escritas en el script. Debe ejecutarse como administrador y verificarlo al inicio.

4. scripts/actualizar.ps1: actualización de versión en producción: verificar que no haya cambios locales; backup previo obligatorio (si falla, no continuar); php artisan down con mensaje "Actualizando el sistema, vuelve en unos minutos"; git pull; composer install --no-dev --optimize-autoloader; migrate --force; config:cache, route:cache, view:cache; php artisan up; mostrar la versión nueva. Si algo falla: dejar el sistema en modo mantenimiento y mostrar instrucciones exactas para volver atrás (git checkout del commit anterior + restaurar el backup recién hecho + up).

5. Versión del sistema: agrega APP_VERSION en config (leída de un archivo VERSION en la raíz, empezando en 1.0.0) y muéstrala en el pie de página. Documenta en docs/versiones.md cómo subir la versión y un registro de cambios (CHANGELOG) empezando por 1.0.0 con la lista de módulos.

6. docs/configuracion.md: todas las variables del .env explicadas, cuáles cambian entre desarrollo y producción.

7. Verifica en mi PC que la aplicación funcione con APP_ENV=production y APP_DEBUG=false (usa un .env temporal y restaura el original al terminar): páginas de error sin información técnica, /estilos inaccesible, seeders demo no se ejecutan.

## Entrega
php artisan test en verde. Commit local "feat: instalación de producción, red local y actualización". No hagas push. Dime qué partes puedo ensayar en mi PC antes de ir a la librería.
```

**Cómo probar:** lo ideal es ensayar la instalación completa en otra PC o en una máquina virtual con Windows, siguiendo `docs/instalacion.md` al pie de la letra como si fueras otra persona. Anota lo que no se entienda o falle y pásaselo a OpenCode. Luego prueba el acceso desde otra PC o el celular por la IP.

**Commit/push:**
```text
Funciona. Haz push a origin main.
```

---

## PROMPT 17 — Preparación para el uso real

```text
Tarea: PROMPT 17 — herramientas para arrancar con datos reales en la librería. Lee AGENTS.md.

1. Comando php artisan sistema:estado que muestre: entorno (APP_ENV), APP_DEBUG, versión, base de datos conectada, cantidad de usuarios/productos/ventas, último backup y su resultado, si hay datos demo (usuarios encargado/cajero1 o productos del seeder demo), resultado de stock:verificar y espacio libre en disco de la carpeta de backups. Salida clara con OK / ADVERTENCIA / ERROR.

2. Comando php artisan sistema:limpiar-demo (solo si APP_ENV no es production, o con --force y confirmación escribiendo "BORRAR DEMO"): elimina usuarios demo, productos demo, ventas, entradas y movimientos de prueba, dejando configuración, categorías y el admin. Hace backup antes. Sirve por si se instaló accidentalmente con datos demo.

3. Conteo físico inicial: pantalla "Inventario inicial / conteo físico" (admin, encargado) que liste productos por categoría con un campo "Cantidad contada" por producto; al guardar genera los ajustes con StockService (motivo "Conteo físico inicial" o el que se escriba), todo en una transacción, auditado. Permite guardar por categoría (no obliga a contar todo de una vez) y exportar a CSV una hoja de conteo para imprimir (código, nombre, stock sistema, casilla vacía).

4. docs/puesta-en-marcha.md con el plan para los primeros días:
   - Antes de abrir: instalar (docs/instalacion.md), cargar categorías reales, importar productos con la plantilla CSV (docs/importacion-productos.md), hacer conteo físico, crear usuarios reales (2 admins), configurar datos del negocio y logo, probar impresora de tickets, ejecutar php artisan sistema:estado.
   - Primera semana: usar el sistema EN PARALELO con el método actual; al cierre de cada día comparar el cierre del día del sistema con el efectivo real y el registro anterior; anotar diferencias.
   - Criterio para dejar el método anterior: 5 días seguidos sin diferencias inexplicadas.
   - A quién llamar y qué hacer si el sistema falla en medio del día (seguir anotando en papel y cargar después).

## Tests
- sistema:estado detecta datos demo y backup antiguo.
- sistema:limpiar-demo no se ejecuta en production sin --force.
- Conteo físico genera los ajustes correctos y no crea movimientos si la cantidad no cambió.

## Entrega
php artisan test en verde. Commit local "feat: herramientas de puesta en marcha". No hagas push.
```

**Cómo probar:** ejecuta `php artisan sistema:estado` en desarrollo (debe avisar que hay datos demo). Haz un conteo físico de una categoría y revisa los ajustes en el kardex. Prueba `sistema:limpiar-demo` en desarrollo y luego `migrate:fresh --seed` para volver.

---

## PROMPT 16 — Caja: apertura y cierre ⚠️

> Hazlo después de usar el sistema 1–2 semanas y confirmar que se necesita control de caja por turno.

```text
Tarea: PROMPT 16 — módulo de caja (apertura, movimientos de efectivo y cierre con arqueo). Lee AGENTS.md y docs/diseno.md (usa los componentes existentes).

## Base de datos
Tabla cajas (sesiones de caja):
- id, user_id (quien abre), abierta_en timestampTz, monto_inicial decimal(12,2) >= 0,
- cerrada_en timestampTz nullable, cerrada_por foreignId nullable (users),
- efectivo_esperado decimal(12,2) nullable, efectivo_contado decimal(12,2) nullable, diferencia decimal(12,2) nullable,
- detalle_conteo jsonb nullable (cantidad por denominación),
- observaciones_cierre text nullable, estado string(20): ABIERTA, CERRADA, timestampsTz.
- Un usuario solo puede tener una caja ABIERTA: índice único parcial en user_id WHERE estado = 'ABIERTA'.

Tabla movimientos_caja:
- id, caja_id foreignId, tipo string(20): INGRESO, EGRESO, user_id, monto decimal(12,2) > 0, concepto string(200), created_at timestampTz.

Nueva migración: agregar a ventas caja_id foreignId nullable.

## Reglas
1. Configuración nueva: exigir_caja_abierta (por defecto "1"). Si está activa, la pantalla de venta redirige a "Abrir caja" cuando el usuario no tiene caja abierta.
2. Abrir caja: monto inicial en efectivo. Auditar.
3. Cada venta se asocia a la caja abierta de quien vende (en VentaService, dentro de la misma transacción).
4. Ingresos y egresos manuales de efectivo con concepto obligatorio (ej: "Pago de luz", "Cambio de billetes"). Egresos solo hasta el efectivo disponible (advertencia si lo supera). Auditar.
5. Cierre: efectivo_esperado = monto_inicial + ventas EFECTIVO COMPLETADA de esa caja + ingresos − egresos (ventas anuladas no suman). El usuario ingresa el efectivo contado, con ayuda por denominación (billetes 200, 100, 50, 20, 10; monedas 5, 2, 1, 0,50, 0,20, 0,10) que suma automáticamente. Se guarda la diferencia (sobrante/faltante) y observaciones (obligatorias si hay diferencia). Muestra también totales de QR, transferencia y tarjeta (informativo). Auditar.
6. Caja cerrada no acepta ventas ni movimientos. Anular una venta de una caja ya cerrada: permitido a admin/encargado, pero se marca en el reporte de esa caja como "anulada después del cierre".
7. Admin/encargado pueden cerrar la caja de otro usuario (ej: si se fue sin cerrar), quedando registrado quién la cerró.
8. Pantallas: "Mi caja" (resumen en vivo: inicial, ventas por método, ingresos, egresos, esperado), abrir, registrar movimiento, cerrar, historial de cajas (admin/encargado ven todas, con filtros), reporte imprimible de cierre (A4 y 80 mm). Agregar "Caja" al menú y el estado (Caja abierta desde HH:MM) en la barra superior.
9. El reporte "Cierre del día" existente debe mostrar también las cajas del día.

## Tests
- No se puede vender sin caja abierta con la opción activa; sí con la opción desactivada.
- Un usuario no puede abrir dos cajas.
- Efectivo esperado correcto con: ventas en efectivo, ventas QR (no suman), anuladas (no suman), ingresos y egresos.
- Diferencia correcta (sobrante y faltante).
- Caja cerrada no acepta movimientos ni ventas.
- Encargado puede cerrar la caja de un cajero; cajero no puede cerrar la de otro.

## Entrega
php artisan test en verde. Commit local "feat: módulo de caja con apertura y cierre". Agrega al plan de pruebas una sección "18. Caja" con las pruebas manuales. No hagas push.
```

---

## PROMPT 18 — Proveedores

```text
Tarea: PROMPT 18 — módulo de proveedores. Lee AGENTS.md y docs/diseno.md.

1. Tabla proveedores: id, nombre string(150) unique, nit string(20) nullable, contacto string(100) nullable, telefono string(30) nullable, direccion string(255) nullable, observaciones text nullable, activo boolean default true, timestampsTz.
2. CRUD (admin, encargado): listado con búsqueda, crear, editar, activar/desactivar (sin eliminar). Auditado.
3. Nueva migración: entradas_stock.proveedor_id foreignId nullable. Migrar los datos existentes: por cada texto distinto en entradas_stock.proveedor, crear el proveedor y vincular (mantener la columna de texto por compatibilidad).
4. En "Nueva entrada": selector de proveedor con búsqueda y opción "Crear proveedor rápido" (modal).
5. Detalle del proveedor: datos, historial de entradas y total comprado por período; productos que suele proveer (últimos costos).
6. Reporte "Compras por proveedor" con exportación CSV.
7. Tests: CRUD, permisos, migración de datos, entrada con proveedor.
Commit local "feat: módulo de proveedores". Agrega pruebas manuales al plan de pruebas (sección "Proveedores"). No hagas push.
```

---

## PROMPT 19 — Clientes

```text
Tarea: PROMPT 19 — módulo de clientes (opcional en cada venta). Lee AGENTS.md y docs/diseno.md.

1. Tabla clientes: id, nombre string(150), ci_nit string(20) nullable unique, telefono string(30) nullable, email string nullable, observaciones text nullable, activo boolean default true, timestampsTz.
2. CRUD (admin, encargado; cajero puede crear desde la venta). Sin eliminar.
3. Nueva migración: ventas.cliente_id foreignId nullable (mantener cliente_nombre como copia del momento).
4. En la pantalla de venta: campo cliente con búsqueda por nombre o CI/NIT y botón "Nuevo cliente" (modal rápido); sigue siendo opcional.
5. Detalle del cliente: historial de compras, total comprado, última compra.
6. Reporte "Mejores clientes" por período con exportación CSV.
7. Tests: venta con y sin cliente, búsqueda, permisos.
Commit local "feat: módulo de clientes". Agrega pruebas manuales al plan de pruebas. No hagas push.
```

---

## PROMPT 20 — Código de barras

```text
Tarea: PROMPT 20 — soporte para código de barras con lector USB (el lector funciona como un teclado que escribe el código y presiona Enter). Lee AGENTS.md.

1. Nueva migración: productos.codigo_barras string(50) nullable unique (índice).
2. Formulario de producto: campo "Código de barras" (se puede llenar escaneando). Validar único.
3. Importación CSV: nueva columna opcional codigo_barras en la plantilla.
4. Pantalla de venta y de entradas: si lo escrito en el buscador coincide exactamente con un codigo_barras (o codigo), al presionar Enter se agrega directo con cantidad 1 (si ya está, suma 1) y el buscador se limpia y queda enfocado, listo para el siguiente escaneo. Si no existe, sonido/aviso breve "Código no encontrado" sin bloquear.
5. Distinguir escaneo rápido de escritura manual no es necesario; basta la coincidencia exacta + Enter.
6. Etiquetas: pantalla para imprimir etiquetas con código de barras (Code128) para productos sin código de fábrica, generando el código de barras en SVG en el servidor con una librería PHP instalada por Composer (sin CDN), en hojas A4 de etiquetas (configurable filas × columnas) o en impresora de etiquetas.
7. Tests: búsqueda por código de barras, unicidad, importación.
Commit local "feat: código de barras". Agrega pruebas manuales al plan de pruebas. No hagas push.
```

---

## PROMPT 21 — Devoluciones parciales

```text
Tarea: PROMPT 21 — devoluciones parciales (el cliente devuelve algunos productos de una venta, sin anular toda la venta). Lee AGENTS.md.

1. Tablas devoluciones (id, venta_id, user_id, fecha, motivo text, total_devuelto decimal(12,2), metodo_reembolso string(20), timestampsTz) y detalle_devoluciones (devolucion_id, detalle_venta_id, producto_id, cantidad integer > 0, precio_unitario decimal(12,2), subtotal decimal(12,2)).
2. Solo admin/encargado. Desde el detalle de una venta COMPLETADA: elegir productos y cantidades a devolver (no más de lo vendido menos lo ya devuelto), motivo obligatorio, método de reembolso.
3. En transacción: devolver stock con StockService (nuevo tipo DEVOLUCION) solo para productos que controlan stock; registrar la devolución; auditar. Si hay caja (PROMPT 16), registrar un EGRESO de caja cuando el reembolso es en efectivo.
4. La venta muestra sus devoluciones; los reportes restan las devoluciones de los totales vendidos (documentar el criterio).
5. Ticket de devolución imprimible.
6. Tests: devolución parcial, no devolver más de lo vendido, stock, reportes, permisos.
Commit local "feat: devoluciones parciales". Agrega pruebas manuales al plan de pruebas. No hagas push.
```
