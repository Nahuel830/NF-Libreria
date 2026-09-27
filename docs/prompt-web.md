# PROMPT WEB — NF Librería en VPS con dominio (opción 2)

> Decisión vigente (sección 9 de `docs/CONTEXTO-PROYECTO.md`): todo el sistema
> en la web, en un VPS con dominio propio. Si se corta Internet en la librería
> **no se puede vender**: recomendar al usuario un respaldo de conexión
> (datos móviles/router 4G). La instalación local Windows (FASE 6,
> `docs/instalacion.md`) sigue valiendo para desarrollo y como plan B.

## Orden de ejecución

| Paso | Tarea | Requiere |
|---|---|---|
| WEB-1 | Código: seguridad web, TOTP, cabeceras y configs de producción | Nada (se hace en la PC) |
| WEB-2 | Scripts Linux: backup/restore bash, `deploy.sh`, cron | Nada (se hace en la PC) |
| WEB-3 | Documentación + ensayo en VM/WSL2 local | Nada (sin comprar) |
| WEB-4 | VPS real: compra, instalación, HTTPS, carga inicial | VPS + dominio comprados |

Reglas generales (las mismas de siempre): un paso a la vez; probar antes de
cada commit; Conventional Commits en español; push a `main` solo cuando el
usuario confirme. Nunca datos demo ni ventas de prueba en la base real.

---

## WEB-1 — Seguridad web y configs de producción ⚠️

```text
Tarea: WEB-1 — preparar la aplicación para vivir en Internet. Lee AGENTS.md y docs/diseno.md.

1. Contraseñas: mínimo 10 caracteres para admin/encargado (cajero sigue en 8).
   Cambia las validaciones de CrearUsuario, RestablecerPassword y CambiarPassword
   (mensajes en español) y ajusta los tests/factories que usen claves cortas.
   Nada de contraseñas en logs ni respuestas.

2. Verificación en dos pasos (TOTP) con librería PHP por Composer (sin CDN):
   - Columnas nuevas en users por migración: totp_secreto (nullable), totp_activo (bool default false).
   - Obligatorio para admin: al entrar, si no lo tiene configurado, obligar a
     activarlo (QR + código de confirmación); si lo tiene, pedir el código
     después de usuario/contraseña. Opcional para encargado (pantalla para
     activarlo/desactivarlo). Cajero sin TOTP.
   - Códigos de recuperación de un solo uso (mínimo 8, hasheados) para no
     quedar bloqueado. Auditar activación y uso de recuperación.
   - Tests: login admin exige TOTP, código válido entra, inválido no entra,
     recuperación consume el código.

3. Login: además del límite actual por usuario+IP, limita por IP sola
   (máximo 20 intentos fallidos por IP cada 10 minutos, RateLimiter).
   Audita LOGIN con IP y, si la IP es nueva para ese usuario, agrega a la
   descripción "dispositivo/IP nueva". Tests incluidos.

4. Opcional configurable: restringir rol cajero a IPs autorizadas.
   Nueva clave `ips_cajero` (texto, IPs separadas por coma; vacío = sin
   restricción) en Configuración (admin) + middleware que devuelve 403 al
   cajero fuera de esas IPs. Tests incluidos.

5. Cabeceras de seguridad (middleware, solo cuando APP_ENV=production):
   X-Frame-Options SAMEORIGIN, X-Content-Type-Options nosniff,
   Referrer-Policy same-origin y CSP que permita solo recursos propios
   (`default-src 'self'`, sin CDN). Verifica que no rompa los JS locales.
   Tests: las cabeceras están presentes en producción.

6. Producción en Laravel: `SESSION_SECURE_COOKIE=true` y
   `SESSION_SAME_SITE=lax` en `.env.example` (comentados para local);
   `TRUSTED_PROXIES=*` configurable por env (para proxy/Cloudflare);
   `/estilos` y rutas `_dusk/*` inaccesibles fuera de `local`
   (ya lo están: verificar con test). `storage:link` documentado.

7. Revisión de rutas públicas: lista con `php artisan route:list` cuáles
   responden sin login y confirma que solo sean login, ticket nada
   (el ticket exige login), `up` (health) y assets. Documenta el resultado
   en docs/revision-seguridad.md (anexo web).

## Tests
- Todo lo de los puntos 1–4 con Feature.
- Cabeceras presentes con APP_ENV=production (test con `RefreshDatabase` y
  entorno forzado, o petición con cabecera).
- `php artisan test` y `stock:verificar` en verde.

## Entrega
Commit local "feat: seguridad para versión web (TOTP, cabeceras, límites)". No hagas push.
```

**Cómo probar:** crea un admin nuevo, entra y activa el TOTP con Google Authenticator; sal y entra con código. Prueba un código malo y un código de recuperación. Intenta entrar 21 veces mal desde la misma IP.

**Commit/push:**
```text
Funciona. Haz push a origin main.
```

---

## WEB-2 — Scripts Linux y despliegue ⚠️

```text
Tarea: WEB-2 — backups, restauración y despliegue en Linux. Lee AGENTS.md.
Los .ps1 de Windows se mantienen para desarrollo. No guardes contraseñas.

1. scripts/backup.sh (bash):
   - Variables arriba del todo (PGHOST, PGPORT, PGDATABASE, PGUSER, carpeta,
     retención en días, destino externo). La contraseña por `~/.pgpass`
     (documentado) o variable `PGPASSWORD` del cron.
   - `pg_dump -Fc` a `nf-libreria_AAAA-MM-DD_HHMM.backup`; verifica que pese
     > 0 y `pg_restore --list` legible; borra los de más de N días;
     log en `backup.log`; registra en la tabla configuracion con
     `php artisan backup:registrar-resultado ok|error mensaje` (ese comando
     ya existe y funciona igual en Linux).
   - Copia fuera del VPS cifrada con rclone a Google Drive o S3 compatible
     (sección configurable; si no hay remoto, advierte y sigue).
   - Exit 0/1. `chmod +x`.
2. scripts/probar-restauracion.sh: mismo comportamiento que el .ps1
   (base temporal, conteos, RESTAURACIÓN OK/ERROR, la borra), con usuario
   `libreria_restore` (CREATEDB, sin acceso a la prod).
3. scripts/deploy.sh: backup previo (falla → no continúa), `php artisan down`,
   `git pull --ff-only`, `composer install --no-dev --optimize-autoloader`,
   `migrate --force`, caches, `up`, muestra VERSION. Rollback documentado
   en comentarios (checkout + restaurar backup + up).
4. Opcional: workflow de GitHub Actions que en cada push a main ejecuta
   `php artisan test` contra PostgreSQL de servicio.
5. `php artisan sistema:estado` adaptado: detecta Linux (rutas de backups,
   servicio web) sin romperse en Windows.

## Tests
- Sintaxis: `bash -n` en los tres scripts.
- `backup:registrar-resultado` ya tiene test; agrega uno de `sistema:estado`
  en entorno simulado Linux si es simple, si no documenta prueba manual.
- `php artisan test` en verde.

## Entrega
Commit local "feat: scripts Linux de backup, restauración y despliegue". No hagas push.
```

**Cómo probar:** en WSL2/VM (ver WEB-3) ejecuta `backup.sh` y `probar-restauracion.sh`, revisa archivo, log y panel.

**Commit/push:**
```text
Funciona. Haz push a origin main.
```

---

## WEB-3 — Documentación y ensayo sin comprar nada

```text
Tarea: WEB-3 — docs y ensayo local. Lee AGENTS.md.

1. docs/despliegue-web.md, paso a paso con comandos listos para copiar:
   a) Elegir proveedor VPS (no hosting compartido: sin PostgreSQL ni SSH):
      1–2 vCPU, 2 GB RAM, 25+ GB SSD, Ubuntu LTS. Precios orientativos a
      verificar al comprar: VPS ~5–10 USD/mes, dominio ~10–15 USD/año.
      Ayuda al usuario a comparar 2–3 opciones cuando lo pida (no elijas
      por tu cuenta el proveedor final).
   b) Comprar dominio y apuntar DNS A/AAAA al VPS.
   c) Servidor desde cero por SSH: usuario deploy con sudo, llaves SSH,
      sin root ni contraseña (`PermitRootLogin no`, `PasswordAuthentication no`),
      zona America/La_Paz, UFW (solo 22/80/443), fail2ban, unattended-upgrades.
   d) Nginx + PHP 8.4-FPM (+opcache) + PostgreSQL 17 (solo localhost) +
      Composer + Git. Bloque server con root en `public/`, PHP-FPM,
      redirección HTTP→HTTPS.
   e) HTTPS con certbot y renovación automática; HSTS.
   f) Clonar, `composer install --no-dev`, `.env` de producción
      (APP_URL=https://dominio, credenciales `libreria_prod`,
      ADMIN_PASSWORD_INICIAL), `key:generate`, `migrate --force`,
      `db:seed --force` (verifica que NO corra demo fuera de local),
      `storage:link`, caches, permisos www-data.
   g) Tarea cron diaria del backup + rclone; prueba de restauración.
   h) Verificación final (igual que instalación local + HTTPS válido).
2. docs/operacion-web.md: actualizar (`deploy.sh`), backups, restaurar,
   qué hacer si el sitio cae, qué hacer si se corta Internet en la librería
   (plan de contingencia: anotar en papel y cargar después con la fecha real
   en Observaciones), monitoreo (UptimeRobot gratuito, logs con rotación,
   alerta de disco).
3. Actualiza docs/manual-cajero.md y docs/manual-administrador.md donde
   cambie algo (dominio en vez de IP, TOTP, impresora en la PC de caja).
4. Ensayo: instala VirtualBox + Ubuntu LTS (o WSL2) y sigue
   despliegue-web.md al pie de la letra hasta tener el sistema con HTTPS
   autofirmado o local. Anota lo que falle y corrige el doc.

## Entrega
Commit local "docs: despliegue y operación web". No hagas push. Dime qué
partes del ensayo hiciste en VM/WSL2 y qué quedó pendiente.
```

**Cómo probar:** lee los docs como si fueras otra persona; sigue el ensayo en VM.

**Commit/push:**
```text
Funciona. Haz push a origin main.
```

---

## WEB-4 — VPS real (solo con VPS y dominio comprados)

```text
Tarea: WEB-4 — puesta en producción web. Lee AGENTS.md y docs/despliegue-web.md.
Solo cuando el usuario confirme que compró VPS y dominio.

1. Ejecuta despliegue-web.md paso a paso en el VPS real (pégale al usuario
   cada comando que deba correr él si no tienes SSH, o ejecútalo tú si te da
   acceso; nunca pidas ni guardes contraseñas: que las escriba él).
2. Carga inicial: sube `productos_normalizados.csv` por SCP/SFTP FUERA de la
   carpeta pública y ejecuta `php artisan carga:inicial` por SSH
   (ver docs/prompt-carga-inicial.md PARTE 3 adaptada a Linux; backups vía
   script bash, no .ps1).
3. Verificación: HTTPS válido, login admin + TOTP, backup + restauración de
   prueba en el VPS, `sistema:estado` OK, conteo físico y usuarios reales
   (2 admins), sin datos demo (verifica con `sistema:estado`).
4. Configura UptimeRobot y la copia externa de backups.

## Entrega
Commit "chore: producción web activa" solo si cambiaste algo del repo
(normalmente nada). Dime la URL final y el estado de cada verificación.
```

---

## Después del WEB-4

- Primera semana en paralelo (papel + sistema) como dice puesta-en-marcha.md.
- Criterio: 5 días sin diferencias → registro oficial.
- Plan de contingencia siempre a mano en la caja.
