# PROMPT WEB — NF Librería en Internet (opción 2: VPS + dominio)

> **Cómo se usa:** este archivo se ejecuta por partes. Frases de invocación:
> - `Lee docs/prompt-web.md y ejecuta WEB-1.`
> - `Lee docs/prompt-web.md y ejecuta WEB-2.` (y así con WEB-3 y WEB-4)
> - Si te cortas: `Lee docs/prompt-web.md y docs/progreso.md, y continúa WEB-N desde el siguiente paso pendiente con las mismas reglas.`
>
> WEB-1, WEB-2 y WEB-3 se hacen en la PC de desarrollo (Windows + WSL2) y NO necesitan VPS ni dominio.
> WEB-4 se hace solo cuando el usuario haya comprado VPS y dominio, y tiene puntos de parada obligatorios.

---

## 0. Contexto y reglas (leer antes de cualquier WEB-N)

### 0.1 Antes de empezar
1. Lee completos: `AGENTS.md`, `docs/CONTEXTO-PROYECTO.md` (sobre todo secciones 3, 9 y 10), `docs/progreso.md`, `docs/backups.md`, `docs/restauracion.md`, `docs/usuarios-y-permisos.md` y lo que el prompt maestro dejó sobre instalación (FASE 6) y seguridad (FASE 5).
2. Verifica el estado: `git status` limpio, rama `main`, `php artisan test` y `php artisan stock:verificar` en verde. Si algo falla, detente y repórtalo antes de cambiar nada.
3. Anota en `docs/progreso.md` que empieza WEB-N, con fecha y hora (America/La_Paz).

### 0.2 Qué cambia con la versión web
- El sistema pasa a vivir en un VPS Ubuntu con dominio propio y HTTPS. La PC de caja solo usa el navegador (impresora térmica y lector USB siguen conectados a esa PC, igual que antes).
- **Riesgo aceptado por el usuario:** si se corta Internet en la librería no se puede vender. Por eso este prompt incluye un plan de contingencia (ventas en papel y carga posterior) y se recomienda un respaldo de conexión (router 4G / datos móviles).
- La instalación local en Windows de la FASE 6 del maestro **no se borra**: queda documentada como "modo local alternativo". El modo principal pasa a ser el web. Los scripts `.ps1` se mantienen para desarrollo en Windows.

### 0.3 Reglas generales (además de AGENTS.md)
- Todo en español: código de dominio, mensajes, documentación, commits.
- Nada de Node/npm/Vite ni CDN. Si agregas una dependencia de Composer, justifícala en `docs/progreso.md`, verifica que sea compatible con PHP 8.4 y Laravel 13, que esté mantenida, y fija la versión con `^`.
- Dinero con bcmath/centavos, fechas `timestampTz`, stock solo por `StockService`, auditoría con `AuditoriaService`. No romper nada de lo que ya funciona.
- **Secretos:** nunca escribas contraseñas, `APP_KEY`, llaves SSH, tokens de rclone ni secretos TOTP en archivos del repo, en `docs/progreso.md`, en commits ni en la salida de la terminal. Usa archivos `*.example` con valores de relleno. Agrega a `.gitignore` todo archivo real de configuración nuevo.
- **Scripts bash:** crea `.gitattributes` con `*.sh text eol=lf` (y `*.conf.example text eol=lf`) ANTES de crear el primer `.sh`. Un `.sh` con finales de línea CRLF no corre en Linux. Todos los scripts empiezan con `#!/usr/bin/env bash` y `set -euo pipefail`, son idempotentes cuando tenga sentido, registran lo que hacen en un log con fecha y devuelven código de salida distinto de 0 si fallan.
- Cada ítem terminado: tests nuevos + `php artisan test` en verde + commit con mensaje claro (`web-1: ...`). Push al final de cada WEB-N.
- **Autoverificación:** después de cada ítem, verifica por ti mismo (tests, consultas a la BD, `curl -I` para cabeceras, Dusk cuando toque la interfaz). El usuario solo debe probar a mano lo que sea imposible automatizar; eso va a `docs/pruebas-manuales-usuario.md` en una sección "Versión web".
- Al terminar cada WEB-N: actualiza `docs/progreso.md` y agrega una entrada en la sección 12 (Bitácora) de `docs/CONTEXTO-PROYECTO.md`, y actualiza sus secciones 7 y 8. Nunca borres entradas anteriores.
- Si una decisión no está cubierta aquí y es reversible, toma la opción más simple y segura, y anótala en `docs/progreso.md` bajo "Decisiones". Si es irreversible o toca producción, detente y pregunta.

---

## WEB-1 — Seguridad de la aplicación para Internet

Objetivo: que la aplicación sea segura expuesta a Internet, sin romper el uso diario en la caja. Todo se prueba en local.

### 1.1 Configuración de producción en Laravel
- Crea `.env.production.example` (sin secretos) con: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://DOMINIO`, `APP_TIMEZONE=America/La_Paz`, `LOG_CHANNEL=daily`, `LOG_DAILY_DAYS=30`, `LOG_LEVEL=warning`, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_HTTP_ONLY=true`, `SESSION_ENCRYPT=true`, `DB_HOST=127.0.0.1`, `CACHE_STORE=database` (o `file`), `QUEUE_CONNECTION=sync`, `MAIL_MAILER=log`, y `TRUSTED_PROXIES=` (vacío por defecto; ver 1.2).
- Si `SESSION_DRIVER=database` requiere la tabla `sessions` y no existe, crea la migración.
- Revisa que ningún lugar dependa de `APP_DEBUG=true` o de `APP_ENV=local` para funcionar.

### 1.2 Proxies de confianza
- En `bootstrap/app.php` configura `trustProxies` leyendo `TRUSTED_PROXIES` del `.env` (lista separada por comas; vacío = ninguno). Documenta: vacío si Nginx está directo; rangos de Cloudflare solo si se usa Cloudflare con proxy activado.
- Motivo: la IP real del cliente se usa para límites de login y para la restricción de IP del cajero (1.7). Si esto está mal, todos parecen venir de la misma IP.

### 1.3 Cabeceras de seguridad
- Middleware `CabecerasSeguridad` (global, en respuestas HTML y JSON):
  - `Content-Security-Policy`: `default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'`.
  - `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
  - **HSTS NO va en Laravel** (lo pone Nginx en WEB-2) para no afectar el desarrollo local.
  - Configurable por `.env` (`CSP_ACTIVA=true`, `CSP_SOLO_REPORTE=false`) para poder desactivar en emergencia.
- `script-src 'self'` prohíbe JavaScript inline. Busca en TODAS las vistas Blade: `<script>` con código dentro, atributos `onclick=`, `onchange=`, `onsubmit=`, `href="javascript:..."`, etc. Muévelos a archivos en `public/js/` con `addEventListener` y pasa datos por atributos `data-*` o por un `<script type="application/json" id="...">` (este tipo sí está permitido porque no se ejecuta). No uses `'unsafe-inline'` ni `'unsafe-eval'` en `script-src`.
- Verificación: test PHPUnit que comprueba las cabeceras; **ejecuta toda la suite Dusk con la CSP activa** (punto de venta, lector de barras, impresión de ticket, modales de confirmar, caja, devoluciones). Cualquier error de CSP en la consola del navegador es un fallo. Agrega un test Dusk que falle si la consola registra violaciones de CSP.

### 1.4 Contraseñas más fuertes
- Regla central (una sola clase o `Password::defaults()` en `AppServiceProvider`): mínimo 10 caracteres, con letras y números; no puede ser igual al usuario ni contener el nombre del negocio.
- `uncompromised()` (consulta a Have I Been Pwned): actívalo solo si `PASSWORD_VERIFICAR_FILTRADAS=true` en el `.env` (por defecto `true` en `.env.production.example`, `false` en local y tests). Verifica cómo se comporta Laravel si la API no responde y documenta el resultado; el login nunca debe depender de esa API (solo aplica al crear/cambiar contraseña).
- Las contraseñas existentes siguen funcionando; la regla se aplica al crear o cambiar. Opción en configuración para forzar cambio a todos (`debe_cambiar_password = true`) — útil al pasar a producción.
- Al cambiar contraseña: cerrar las demás sesiones del usuario (`Auth::logoutOtherDevices` o borrar sus filas en `sessions`) y auditar.

### 1.5 Límites de intentos
- Se mantiene el bloqueo por usuario tras 5 intentos. Además:
  - Límite por IP: máximo 20 intentos de login fallidos por IP cada 15 minutos (RateLimiter, clave por IP), sin importar el usuario.
  - Límite del paso TOTP (1.6): 5 intentos por sesión de login; luego se anula el login y hay que empezar de nuevo.
  - Límite general en rutas de escritura sensibles (anular venta, importar, backups manuales): valores razonables, anotados en progreso.md.
- Mensajes de error genéricos: no revelar si el usuario existe.
- Auditar los bloqueos por IP.

### 1.6 Verificación en dos pasos (TOTP)
- Implementación: usa `pragmarx/google2fa` para TOTP y `bacon/bacon-qr-code` para generar el QR en **SVG en el servidor** (sin JS externo). Si alguna no es compatible con PHP 8.4 / Laravel 13, implementa TOTP según RFC 6238 (HMAC-SHA1, 6 dígitos, 30 s) con tests contra los vectores oficiales del RFC, y anótalo.
- No uses Laravel Fortify (el login es propio, por `usuario`).
- Migración en `users`: `totp_secreto` (text, nullable, cast `encrypted`), `totp_confirmado_en` (timestampTz nullable), `totp_ultimo_paso` (bigint nullable, para impedir reutilizar el mismo código), `codigos_recuperacion` (text nullable, cast `encrypted:array`, cada código guardado **hasheado**).
- Configuración: `totp_obligatorio_admin` (true), `totp_obligatorio_encargado` (false; el encargado puede activarlo por su cuenta), cajero: no disponible por ahora (documentar por qué: agilidad en caja; se compensa con 1.7).
- Flujo de login: usuario + contraseña correctos → si tiene TOTP activo, pantalla "Código de verificación" (sesión intermedia que NO da acceso a nada más) → código correcto (ventana ±1 paso, sin reutilizar) → acceso. También se acepta un código de recuperación (se consume).
- Admin sin TOTP configurado y obligatorio: después del login solo puede acceder a la pantalla de activación (y a cerrar sesión) hasta completarla. Esto va después del cambio obligatorio de contraseña si también aplica.
- Pantalla "Mi seguridad": activar (muestra QR + clave en texto para cargar a mano, pide un código para confirmar), ver estado, regenerar códigos de recuperación (pide contraseña actual), desactivar (solo si no es obligatorio para su rol; pide contraseña y código).
- 10 códigos de recuperación, mostrados una sola vez, con botón "Imprimir" (vista imprimible simple).
- Un admin puede **restablecer** el TOTP de otro usuario (quedará obligado a configurarlo de nuevo), con confirmación y auditoría. Por eso se necesitan 2 admins reales.
- Auditar: activación, desactivación, restablecimiento, uso de código de recuperación, código incorrecto.
- Documentar en el manual qué apps sirven (Google Authenticator, Microsoft Authenticator, Aegis, 2FAS) y qué hacer si se pierde el celular.
- **Importante:** los secretos TOTP se cifran con `APP_KEY`. Si se pierde `APP_KEY`, se pierden los TOTP. WEB-2 debe respaldar `APP_KEY` fuera del VPS de forma segura (ver 2.3).
- Tests: activar, login con código correcto, incorrecto, reutilizado, fuera de ventana, código de recuperación (y que no sirva dos veces), admin forzado a configurar, restablecimiento por otro admin, que la sesión intermedia no accede a rutas protegidas. Dusk: flujo completo de activación y login (generando el código en el test con el secreto).

### 1.7 Aviso de nuevo dispositivo y restricción del cajero por IP
- **Nuevo dispositivo:** al iniciar sesión, cookie de larga duración (`nf_dispositivo`, httpOnly, secure en producción) con un identificador aleatorio; en BD se guarda su hash por usuario (tabla `dispositivos_usuario`: user_id, hash, user_agent resumido, ip, primer_uso, ultimo_uso). Si el dispositivo es nuevo para ese usuario: registro en auditoría `LOGIN_NUEVO_DISPOSITIVO` y aviso visible para los admin en el panel (últimos 7 días). El usuario ve en "Mi seguridad" sus dispositivos y puede cerrar la sesión en todos los demás.
- **Restricción del cajero por IP (opcional):** configuración `restringir_cajero_por_ip` (false por defecto) e `ips_permitidas_cajero` (lista de IPs o rangos CIDR). Si está activa y el cajero entra desde otra IP: login rechazado con mensaje claro y auditoría. Advertir en la pantalla de configuración que muchas conexiones en Bolivia tienen IP dinámica y que activarlo puede dejar al cajero afuera; mostrar "Tu IP actual es X" para facilitar la carga. Admin y encargado nunca se restringen por IP.
- Tests de ambos.

### 1.8 Rutas y herramientas de desarrollo
- Lista todas las rutas (`php artisan route:list`) y verifica que toda ruta que no sea login, logout, `/up` y assets requiera autenticación. Deja esa verificación como test automático (recorre las rutas y falla si alguna nueva queda pública sin estar en una lista blanca explícita).
- `/estilos`: solo si `APP_ENV=local`. Test que confirme 404 en producción.
- Dusk: solo `require-dev`; confirma que sus rutas no se registran en producción. Telescope/Debugbar: no deben existir o solo en dev.
- Páginas de error 403/404/419/429/500 propias, con el diseño del sistema y sin detalles técnicos.
- `/up` (health check de Laravel): público, sin datos sensibles; se usará para el monitoreo.

### 1.9 Contingencia por corte de Internet (parte de la aplicación)
- Migración en `ventas`: `es_contingencia` (boolean, default false) y `fecha_contingencia` (timestampTz nullable).
- En el punto de venta, solo admin/encargado: casilla "Venta registrada en papel durante un corte" + fecha y hora real de la venta (no puede ser futura ni de más de 7 días atrás). La venta se registra normalmente (número correlativo nuevo, stock, caja abierta del momento), guardando la fecha real aparte. Auditoría.
- Reportes y listado de ventas: mostrar la marca "Contingencia" y la fecha real; en el reporte diario, opción de ver ventas por fecha real.
- Crear `docs/planilla-contingencia.md` con una planilla imprimible (fecha, hora, código/producto, cantidad, precio, método de pago, total, iniciales).
- Tests.

### 1.10 Cierre de WEB-1
- `php artisan test` y `php artisan dusk` completos en verde, con la CSP activa.
- `php artisan stock:verificar` OK.
- Actualiza `docs/usuarios-y-permisos.md` (TOTP, IP cajero, contingencia) y `docs/pruebas-manuales-usuario.md` (sección "Versión web": escanear el QR con un celular real).
- Commits, push, bitácora en CONTEXTO-PROYECTO.md. Detente y resume: qué se hizo, tests, dependencias agregadas, decisiones.

---

## WEB-2 — Servidor Linux: aprovisionamiento, backups y despliegue

Objetivo: dejar en el repo todo lo necesario para montar el servidor con scripts, sin tocar todavía ningún servidor real. Los scripts se prueban en WEB-3.

Estructura:
```
scripts/linux/
  provisionar.sh              # instala y asegura el servidor (idempotente)
  endurecer-ssh.sh            # separado a propósito (ver 2.1)
  backup.sh                   # backup diario + copia fuera del VPS
  probar-restauracion.sh      # prueba semanal automática
  restaurar.sh                # restauración real (manual, con confirmaciones)
  desplegar.sh                # actualización de la aplicación
  revertir.sh                 # vuelta atrás
  revisar-disco.sh            # alerta de espacio
  nf-libreria.conf.example    # configuración (se copia a /etc/nf-libreria/nf-libreria.conf)
  nginx/nf-libreria.conf.example
  cron/nf-libreria.example
  logrotate/nf-libreria.example
```

### 2.1 Aprovisionamiento (`provisionar.sh`)
Parámetros: dominio, correo para Let's Encrypt, usuario de despliegue (por defecto `nflib`). Se ejecuta como root (o sudo) una vez; repetirlo no debe romper nada.
- Sistema: Ubuntu LTS (24.04; aceptar 26.04 si el proveedor la ofrece — detectar versión y abortar con mensaje claro si no es una LTS soportada). Zona horaria `America/La_Paz`, locale `es_BO.UTF-8` o `es_ES.UTF-8`, `apt update && upgrade`.
- Usuario de despliegue sin contraseña de login, con sudo limitado solo a lo necesario (recargar php-fpm y nginx), en el grupo `www-data`.
- PHP 8.4 (+FPM) desde el PPA `ondrej/php` si la versión de Ubuntu no la trae: extensiones pgsql, pdo_pgsql, mbstring, xml, curl, zip, intl, bcmath, gd, opcache. `php.ini` de producción: `expose_php=Off`, `display_errors=Off`, `date.timezone=America/La_Paz`, opcache activado con valores documentados, `upload_max_filesize`/`post_max_size` suficientes para la importación de Excel.
- Pool FPM propio (`nf-libreria`) corriendo como el usuario de despliegue con grupo `www-data`, socket unix.
- PostgreSQL 17 desde el repositorio oficial PGDG (misma versión mayor que desarrollo). Escuchando solo en localhost, autenticación `scram-sha-256`. Roles:
  - `libreria_app`: dueño de la base `libreria` (necesario para migraciones), sin CREATEDB ni superuser.
  - `libreria_restore`: con CREATEDB, solo para la prueba de restauración.
  - Las contraseñas se generan aleatorias y se escriben en `/etc/nf-libreria/` (permisos 600) y en `~nflib/.pgpass`; nunca se imprimen.
- Composer 2, Git, unzip, rclone, fail2ban, ufw, unattended-upgrades, certbot con plugin nginx.
- UFW: denegar todo entrante excepto 22, 80, 443.
- fail2ban: jail `sshd` y jail para Nginx (`nginx-limit-req` o equivalente); valores documentados.
- unattended-upgrades: solo actualizaciones de seguridad, reinicio automático desactivado (se avisa en `sistema:estado` si hace falta reiniciar).
- Swap de 1–2 GB si el VPS tiene 2 GB de RAM o menos.
- Nginx: server block desde la plantilla, raíz en `/var/www/nf-libreria/public`, `server_tokens off`, gzip para css/js/svg, cache larga para `/css`, `/js`, `/fonts`, `/img`, bloqueo de archivos ocultos (`/\.`) excepto `/.well-known/acme-challenge`, `client_max_body_size` acorde a la importación, `limit_req` suave en `/login`.
- Certbot: certificado Let's Encrypt, redirección HTTP→HTTPS, renovación automática (verificar el timer con `certbot renew --dry-run`). Luego agregar HSTS: `max-age=86400` al principio (documentar subirlo a `31536000` tras una semana sin problemas; sin `preload`).
- Aplicación en `/var/www/nf-libreria` (dueño `nflib:www-data`; `storage` y `bootstrap/cache` escribibles por el grupo).
- Modo "sin dominio todavía" (`--sin-certificado`): para el ensayo de WEB-3, usa un certificado autofirmado.
- **`endurecer-ssh.sh` (separado a propósito):** desactiva login de root y autenticación por contraseña. Antes de aplicar, verifica que el usuario de despliegue tenga `authorized_keys` no vacío; si no, aborta. Valida la configuración con `sshd -t` antes de recargar. Documenta en letras grandes: **no cerrar la sesión SSH actual hasta confirmar desde otra terminal que se puede entrar con la llave** (si no, se pierde el acceso al VPS).

### 2.2 Laravel en el servidor
- Primera instalación (sección dentro de `desplegar.sh --primera-vez` o script aparte): clonar el repo (HTTPS público o deploy key de solo lectura si el repo es privado — documentar ambas), `composer install --no-dev --optimize-autoloader`, crear `.env` desde `.env.production.example` con los valores generados, `php artisan key:generate`, `migrate --force`, `storage:link`, `config:cache`, `route:cache`, `view:cache`, `event:cache`.
- **Nunca** `db:seed` con datos demo en producción. Crear un comando o seeder de producción que solo cree lo mínimo (configuración base, métodos de pago) y un comando interactivo `php artisan usuario:crear-admin` si no existe (pide nombre y usuario, genera contraseña temporal y la muestra UNA vez, marca `debe_cambiar_password`).
- Scheduler de Laravel: cron del usuario `nflib` con `* * * * * php artisan schedule:run`.
- Verifica que `sistema:limpiar-demo` y cualquier comando destructivo se nieguen a correr en `production` sin una bandera explícita y confirmación.

### 2.3 Backups (`backup.sh`)
Configuración en `/etc/nf-libreria/nf-libreria.conf` (ejemplo en el repo): carpeta local, retenciones, remoto de rclone, ruta de la app, correo opcional.
- Diario por cron (p. ej. 03:15): `pg_dump -Fc` a `/var/backups/nf-libreria/diarios/libreria_AAAA-MM-DD_HHMM.dump`.
- Verificación inmediata con `pg_restore --list` (si falla, el backup cuenta como fallido).
- Además del dump: `tar.gz` de `storage/app` (logo y archivos subidos).
- Retención local: 14 diarios + 8 semanales (domingo) + 6 mensuales (día 1). Borrar lo vencido.
- **Copia fuera del VPS cifrada** con rclone usando un remoto `crypt` sobre Google Drive o un almacenamiento compatible S3 (documentar ambos; el usuario elige). Retención remota: 30 diarios + 12 mensuales.
- **Respaldo de `APP_KEY` y del `.env`:** una copia cifrada del `.env` va al remoto `crypt` (no al lado de los dumps sin cifrar). Documentar además que el usuario guarde `APP_KEY` y la contraseña del remoto crypt en un gestor de contraseñas o en papel en lugar seguro: sin ellas, los backups remotos y los TOTP son irrecuperables.
- Registrar el resultado para la alerta del panel: reutiliza el mecanismo que ya existe del PROMPT 13 (claves en `configuracion` como fecha del último backup correcto); agrega si falta `ultimo_backup_remoto_ok` y `ultimo_error_backup`. Hacerlo mediante un comando artisan (p. ej. `php artisan backup:registrar --tipo=local|remoto|restauracion --estado=ok|error --detalle=...`) para no escribir SQL a mano en bash.
- Log en `/var/log/nf-libreria/backup.log` (con rotación).

### 2.4 Prueba de restauración semanal (`probar-restauracion.sh`)
- Cron semanal (domingo 04:00). Toma el último dump, crea con el rol `libreria_restore` la base temporal `libreria_restore_prueba`, restaura, ejecuta comprobaciones (conteo de productos, ventas, usuarios; que existan las tablas clave; y `php artisan stock:verificar` apuntando a esa base mediante variables de entorno temporales), borra la base temporal SIEMPRE (también si falla, con `trap`), y registra el resultado con `backup:registrar --tipo=restauracion`.
- El panel ya alerta si no hay backup reciente; agregar alerta si la última prueba de restauración falló o tiene más de 10 días.

### 2.5 Restauración real (`restaurar.sh`)
- Uso manual y guiado: lista los dumps disponibles (locales y remotos), pide confirmación escribiendo el nombre de la base, pone la app en `php artisan down`, hace un backup de seguridad del estado actual antes de pisarlo, restaura, corre `stock:verificar`, `php artisan up`. Registrar todo en el log y en auditoría.
- Documentado paso a paso en `docs/operacion-web.md`.

### 2.6 Despliegue (`desplegar.sh`) y vuelta atrás (`revertir.sh`)
- Uso: `desplegar.sh [ref]` (por defecto el último tag `v*`; recomendar desplegar tags, no `main` a secas).
- Pasos: comprobar que no hay otro despliegue en curso (lock), guardar el commit actual en `/var/www/nf-libreria/.ultimo-despliegue`, `backup.sh` (si falla, abortar), `php artisan down --retry=60` con vista de mantenimiento propia, `git fetch --tags && git checkout ref`, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, limpiar y regenerar caches, recargar php-fpm (opcache), `php artisan up`, chequeo de salud (`curl` a `/up` con código 200). Si algo falla después de `down`, dejar un mensaje claro de cómo revertir y NO dejar el sitio en un estado a medias sin avisar.
- `revertir.sh`: vuelve al commit guardado, composer install, caches, up. Si el despliegue fallido corrió migraciones, NO intentar `migrate:rollback` automático: indicar restaurar el backup previo con `restaurar.sh` (el que hizo `desplegar.sh`). Documentar el criterio.

### 2.7 Monitoreo
- `revisar-disco.sh` cada hora por cron: si el disco supera 80% (configurable), registra alerta en `configuracion` (se muestra en el panel del admin) y en el log.
- Adaptar `php artisan sistema:estado` para producción: versión desplegada (tag/commit), `APP_DEBUG` apagado, conexión a BD, espacio en disco, último backup local/remoto, última prueba de restauración, días hasta el vencimiento del certificado (leer con `openssl` sobre el dominio o los archivos de certbot; si no puede leerlos, decirlo sin fallar), si el sistema pide reinicio (`/var/run/reboot-required`), y estado de `stock:verificar`. Salida clara con ✅/⚠️/❌.
- Logs: `LOG_CHANNEL=daily` de Laravel; logrotate para `/var/log/nf-libreria/*.log` y verificar el de Nginx.
- Documentar UptimeRobot (gratis) apuntando a `https://DOMINIO/up` con aviso por correo.

### 2.8 GitHub Actions (opcional pero recomendado)
- `.github/workflows/tests.yml`: en cada push y PR a `main`, PHP 8.4 + servicio PostgreSQL 17, `composer install`, `php artisan test`. Sin Dusk en CI (documentar por qué). Sin secretos reales. Sin despliegue automático.

### 2.9 Cierre de WEB-2
- Todos los scripts con `bash -n` sin errores. Si hay WSL2 disponible (`wsl -l -v`), pasa `shellcheck` a todos dentro de WSL y corrige lo que marque.
- Comprueba con `git ls-files --eol` que los `.sh` están en LF.
- Tests PHPUnit para los comandos artisan nuevos (`backup:registrar`, `usuario:crear-admin`, protección de comandos destructivos en producción, `sistema:estado`).
- Commits, push, bitácora. Detente y resume.

---

## WEB-3 — Documentación y ensayo completo en WSL2

Objetivo: documentar todo para el usuario y **ensayar el despliegue completo en un Linux local** antes de pagar el VPS.

### 3.1 Documentación
- `docs/despliegue-web.md` — para el usuario, paso a paso y sin suponer conocimientos:
  1. Qué comprar: VPS con Ubuntu LTS, 1–2 vCPU, 2 GB RAM, 25+ GB SSD, con IPv4, ubicación cercana (EE. UU. o Brasil suelen dar mejor latencia desde Bolivia — indicar que se verifique con el proveedor). Dominio (.com u otro). No usar hosting compartido. Costos a verificar al momento de comprar.
  2. Crear llaves SSH en Windows (`ssh-keygen -t ed25519`), cargar la pública al crear el VPS.
  3. Configurar DNS: registro A (y AAAA si hay IPv6) para `@` y `www`; cómo comprobar la propagación (`nslookup`).
  4. Ejecutar `provisionar.sh`, luego `endurecer-ssh.sh` con la advertencia de la segunda terminal.
  5. Primera instalación de la aplicación, creación de los 2 admins reales, activación de TOTP.
  6. Configurar rclone + remoto crypt, correr un backup a mano y una prueba de restauración a mano.
  7. UptimeRobot.
  8. Lista de verificación final (ver 3.3).
- `docs/operacion-web.md` — día a día: actualizar (`desplegar.sh`), revertir, backups y dónde están, restaurar, renovar certificado (automático; qué hacer si falla), qué hacer si el sitio no responde (diagnóstico en orden: ¿Internet de la librería? ¿UptimeRobot dice caído? SSH → `systemctl status nginx php8.4-fpm postgresql` → logs → espacio en disco), **plan de contingencia por corte de Internet** (planilla en papel → cargar después con la opción de contingencia de 1.9; cómo cuadrar la caja), cambiar de VPS o de proveedor (restaurar desde el remoto), qué pasa si se pierde `APP_KEY`.
- `docs/pc-caja.md` (o actualizar `docs/impresion-tickets.md`): Chrome o Edge en modo quiosco con impresión silenciosa apuntando al dominio, por ejemplo
  `msedge.exe --kiosk https://DOMINIO --kiosk-printing --edge-kiosk-type=fullscreen` (verificar las banderas vigentes del navegador instalado), acceso directo en el escritorio, impresora térmica como predeterminada, márgenes, lector USB; y la recomendación del router 4G / datos móviles de respaldo.
- Actualizar los manuales de usuario (admin, encargado, cajero) con: inicio de sesión con código, "Mi seguridad", códigos de recuperación, venta de contingencia, qué hacer si no hay Internet.
- Actualizar `docs/backups.md` y `docs/restauracion.md`: sección Linux (principal) y sección Windows (desarrollo / modo local alternativo).
- Marcar en la documentación de la FASE 6 (instalación Windows) que es el modo alternativo y enlazar a `despliegue-web.md`.
- Adaptar `docs/prompt-carga-inicial.md` PARTE 3 para el VPS: subir `productos_normalizados.csv` por `scp` a una carpeta fuera de `public` (p. ej. `/home/nflib/carga-inicial/`), backup con `backup.sh`, ejecutar `php artisan carga:inicial` por SSH, verificar, borrar el CSV del servidor al terminar.

### 3.2 Ensayo en WSL2 (autoverificado)
- Comprueba si hay WSL2 con Ubuntu (`wsl -l -v`). Si no hay, **detente** y dale al usuario los comandos exactos para instalarlo (`wsl --install -d Ubuntu-24.04`, reiniciar, crear usuario) y espera. No intentes instalar WSL sin avisar.
- Habilita systemd en WSL (`/etc/wsl.conf` con `[boot] systemd=true`, luego `wsl --shutdown`) si no está.
- Clona el repo dentro del sistema de archivos de Linux (no en `/mnt/d/...`, por permisos y velocidad) y ejecuta desde PowerShell con `wsl -d <distro> -- bash -lc "..."`:
  1. `provisionar.sh --sin-certificado` con dominio de prueba `nf-libreria.test` (agregar a `/etc/hosts` de WSL; opcionalmente también al `hosts` de Windows — pedir permiso al usuario porque requiere administrador).
  2. Primera instalación, `usuario:crear-admin`, verificación de que no hay datos demo.
  3. Verificar: `curl -kI https://nf-libreria.test` (cabeceras de seguridad, redirección HTTP→HTTPS, HSTS), `/up` 200, `/estilos` 404, página de login carga CSS/JS/fuentes locales sin errores.
  4. Crear una venta de prueba **solo en este ensayo** (esta base es desechable), correr `backup.sh` con un remoto rclone de tipo `local` (carpeta) configurado como `crypt` para simular el remoto, `probar-restauracion.sh`, y confirmar el registro en el panel.
  5. `desplegar.sh` con un tag de prueba (crea un tag local `v1.1.0-ensayo` que NO se sube a GitHub), luego `revertir.sh`.
  6. `restaurar.sh` sobre el backup.
  7. `php artisan sistema:estado` y `stock:verificar`.
  8. Correr los tests PHPUnit dentro de WSL contra una base de test local de Linux (confirma que no hay dependencias de Windows).
- Qué NO se puede probar bien en WSL (anotar como "a verificar en WEB-4"): UFW, fail2ban real, certificado real de Let's Encrypt, DNS, endurecimiento SSH, reinicios.
- Opcional: si el usuario prefiere una VM (Hyper-V o VirtualBox) para probar también UFW y SSH, documenta cómo, pero no la crees tú.
- Deja el resultado en `docs/informe-ensayo-web.md` (qué se probó, resultado, problemas encontrados y corregidos).

### 3.3 Lista de verificación de salida a producción
Crear `docs/checklist-produccion.md` con casillas: APP_DEBUG=false, APP_KEY respaldada, 2 admins reales con TOTP, contraseñas demo inexistentes, sin datos demo, backup local OK, backup remoto OK, prueba de restauración OK, certificado válido y renovación probada, UFW activo, SSH sin root ni contraseña, fail2ban activo, UptimeRobot configurado, PC de caja en modo quiosco imprimiendo, lector de barras probado, plan de contingencia impreso, router 4G de respaldo (recomendado).

### 3.4 Cierre de WEB-3
- Commits, push, bitácora. Detente, resume el ensayo y lista lo que el usuario necesita comprar/tener para WEB-4.

---

## WEB-4 — Puesta en el VPS real (con el usuario presente)

**Solo empieza cuando el usuario diga que ya compró VPS y dominio y te dé: IP del VPS, dominio, usuario SSH inicial y correo para Let's Encrypt.** La conexión se hace con la llave SSH del usuario; nunca le pidas que pegue contraseñas en el chat.

Reglas especiales de producción:
- Antes de cada paso que cambie el servidor, di en una línea qué vas a hacer y espera "sí" del usuario en los puntos marcados con 🛑.
- Nada de datos demo, nada de ventas de prueba, nada de `migrate:fresh`, nada de `db:seed` con demo, nunca `sistema:limpiar-demo` en producción. La numeración de ventas empieza en #000001 con la carga inicial.
- No muestres secretos en la salida (usa `--quiet`, redirige a archivos con permisos 600).

Pasos:
1. Comprobar DNS (`nslookup DOMINIO` devuelve la IP del VPS). Si no, esperar propagación.
2. 🛑 Subir y ejecutar `provisionar.sh` con el dominio real (esta vez con certificado real).
3. 🛑 `endurecer-ssh.sh`. **Mantener la sesión abierta**, pedir al usuario que pruebe entrar desde otra terminal con `ssh nflib@DOMINIO`, y solo cuando confirme, seguir.
4. Verificar lo que no se pudo en WSL: `ufw status`, `fail2ban-client status`, `certbot renew --dry-run`, cabeceras con `curl -I`, que el puerto 5432 no responde desde fuera.
5. 🛑 Primera instalación de la aplicación (tag `v1.x` vigente). Crear los 2 admins reales con `usuario:crear-admin` (el usuario anota las contraseñas temporales). Configurar datos del negocio y logo.
6. El usuario inicia sesión, cambia contraseña y activa TOTP en los 2 admins (manual, con celular).
7. Configurar rclone + crypt (el usuario hace la parte de autorizar Google Drive o carga las credenciales S3), correr `backup.sh` y `probar-restauracion.sh` a mano, verificar el panel.
8. 🛑 Carga inicial según `docs/prompt-carga-inicial.md` PARTE 3 (versión VPS). Backup antes y después.
9. Configurar la PC de caja (guía en `docs/pc-caja.md`): el usuario sigue los pasos; tú verificas desde el servidor que llegan las sesiones.
10. Recorrer `docs/checklist-produccion.md` y marcar cada casilla con evidencia.
11. Registrar en UptimeRobot (lo hace el usuario; tú le das la URL).
12. Cierre: `sistema:estado` todo en ✅, bitácora, actualizar secciones 7 y 8 de CONTEXTO-PROYECTO.md, y recordar al usuario: primera semana en paralelo con el método actual, comparar cierres diarios, y subir HSTS a un año pasada una semana sin problemas.
