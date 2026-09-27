# CONTEXTO DEL PROYECTO — NF Librería

> **Para cualquier IA o persona que retome este proyecto:** este documento resume TODO lo hecho, lo decidido y lo pendiente. Léelo completo antes de responder. Al final de cada sesión de trabajo, **agrega una entrada nueva en la sección 12 (Bitácora)** sin borrar las anteriores, y actualiza las secciones 7 (Estado) y 8 (Pendientes).
>
> Última actualización: 26/09/2026.

---

## 1. Qué es el proyecto

Sistema web de **gestión y ventas para una librería/papelería en Bolivia** (Tarija). Moneda Bs., zona horaria America/La_Paz. Idioma: español.

Módulos: usuarios y roles, categorías, productos, inventario (stock con kardex), entradas de mercadería, importación desde Excel/CSV, ventas (punto de venta con ticket), anulaciones, reportes, auditoría, backups, diseño propio. En construcción/pendientes: código de barras con lector USB, proveedores, clientes, caja (apertura/cierre), devoluciones parciales, puesta en marcha, instalación, y **versión online (decidida: opción 2, todo en la web en un VPS)**.

- **Dueño del proyecto:** Nahuel Martinez (usuario GitHub: Nahuel830).
- **Repositorio:** https://github.com/Nahuel830/NF-Libreria (rama `main`; existe `respaldo-bloque-4-13` como respaldo temporal).
- **Carpeta local:** `D:\Nahuel Martinez\Libreria`
- **No hay facturación electrónica (SIN)** por ahora; no diseñar nada que la impida después.

## 2. Cómo trabajamos (metodología)

- **Claude (chat)** actúa como arquitecto/revisor: diseña, escribe los prompts, revisa resultados y resuelve dudas. **No se conecta al repositorio**; el usuario le pega resultados, errores y capturas.
- **OpenCode** (agente en la terminal de **Antigravity IDE**) escribe el código, ejecuta comandos, tests y commits. Modelo actual: **Muse Spark 1.3 Free (OpenCode Zen)**. El usuario tiene Claude Pro pero NO puede usarlo en OpenCode (Anthropic no permite suscripciones en herramientas de terceros); se decidió no pagar extra.
- Los prompts para OpenCode viven en `docs/` y se invocan con frases cortas (ej: "Lee docs/prompt-maestro.md y ejecútalo desde la FASE 0").
- Reglas del proyecto para el agente: `AGENTS.md` (raíz). OpenCode lo lee siempre.
- Flujo: prompt → OpenCode implementa + tests → prueba → commit → push. Tareas grandes: modo Plan (Tab) y luego Build.
- Si OpenCode se corta: "Lee docs/prompt-maestro.md y docs/progreso.md, y continúa desde el siguiente paso pendiente con las mismas reglas."
- El usuario prefiere: respuestas en español, pasos concretos, prompts completos y detallados, avanzar en bloques grandes, que el agente se autoverifique (pruebas automáticas + revisión en BD) para minimizar pruebas manuales.

## 3. Decisiones técnicas (vigentes)

- **Stack:** Laravel 13 (skeleton 13.10 / framework 13.33), PHP 8.4.25, PostgreSQL 17.11, Composer 2.10.3, Blade + Bootstrap 5.3.8 + Bootstrap Icons 1.13.1 **locales** (sin CDN), JavaScript vanilla en `public/js/`. **Sin Node/npm/Vite.** Fuente Inter local. Tests con PHPUnit sobre `libreria_test`; tests de navegador con **Laravel Dusk** (solo desarrollo) sobre `libreria_dusk`.
- **Roles:** admin, encargado, cajero (columna `rol` en users + Gates en AppServiceProvider + middleware `rol:`). Matriz en `docs/usuarios-y-permisos.md`. Login por `usuario` (no email). Contraseñas con Hash; cambio obligatorio (`debe_cambiar_password`); bloqueo tras 5 intentos; cierre por inactividad configurable.
- **Datos:** dinero `decimal(12,2)` y cálculos con bcmath/centavos (nunca float); fechas `timestampTz`; tablas y columnas en español snake_case.
- **Stock:** TODO cambio pasa por `App\Services\StockService` (dentro de transacción, `lockForUpdate`, productos bloqueados ordenados por id) y queda en `movimientos_stock` (INICIAL, ENTRADA, ANULACION_ENTRADA, VENTA, ANULACION_VENTA, AJUSTE_POSITIVO, AJUSTE_NEGATIVO; luego DEVOLUCION). Comando `php artisan stock:verificar`. Stock negativo configurable (por defecto permitido con advertencia). Productos de servicio (fotocopias) con `controla_stock = false`.
- **Ventas:** `VentaService`; precios siempre leídos de la BD; `detalle_ventas` guarda copia de código, nombre y precio; token UUID contra ventas duplicadas; descuentos solo admin/encargado; ventas nunca se borran: se anulan (motivo, usuario, fecha) devolviendo stock. Métodos de pago: Efectivo, QR, Transferencia, Tarjeta, Otro. Ticket 80 mm "Documento sin valor fiscal".
- **Auditoría:** `AuditoriaService` → tabla `auditoria` (nunca guarda contraseñas).
- **Configuración:** tabla `configuracion` + `ConfiguracionService` (nombre_negocio, direccion, telefono, mensaje_ticket, permitir_stock_negativo, minutos_inactividad, imprimir_automatico, logo_negocio, exigir_caja_abierta…).
- **Diseño:** paleta azul tinta #1F4E79 (principal), amarillo lápiz #E0A526 (acento), éxito #2E7D32, error #C62828, advertencia #E67E22, fondo #F5F6F8, texto #1F2933. Componentes Blade: x-page-header, x-card, x-estado, x-dinero, x-empty-state, x-confirmar, x-alertas, x-filtros. Guía en `docs/diseno.md` y página `/estilos` (solo admin en local).
- **Código de barras:** inicialmente fuera; luego **se decidió incluirlo** (lector USB tipo teclado) en el prompt maestro.
- **Despliegue:** originalmente 100% local (PC servidor en la librería con Apache en Windows). **Decisión nueva (26/09/2026): pasar a la opción 2 = todo en la web en un VPS con dominio propio** (ver sección 9). El usuario aún no compró dominio ni hosting.

## 4. Entorno de desarrollo (PC del usuario, Windows)

- Instalado por PowerShell/winget: Git, VC++ Redistributable, PHP 8.4 (`%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4_...`), Composer manual en `C:\composer`, PostgreSQL 17 (`C:\Program Files\PostgreSQL\17\bin` en PATH), pgAdmin 4. Python 3.14 también existe.
- php.ini creado desde php.ini-development con pdo_pgsql, pgsql, mbstring, openssl, fileinfo, curl, zip, intl y date.timezone America/La_Paz.
- **Bases:** `libreria_dev` (desarrollo, con datos demo), `libreria_test` (tests), `libreria_dusk` (tests navegador). Usuario de BD `libreria_dev` (tiene CREATEDB). Contraseñas: en el `.env` local (no se suben a git).
- `pgpass.conf` en `%APPDATA%\postgresql\` para backups sin contraseña.
- **Usuarios demo** (se recrean con `php artisan migrate:fresh --seed`): `admin` / `Admin12345` (pide cambio), `encargado` / `demo12345`, `cajero1` / `demo12345`.
- Recuperar contraseña admin sin borrar datos:
  `php artisan tinker --execute="App\Models\User::where('usuario','admin')->update(['password'=>Illuminate\Support\Facades\Hash::make('Admin12345'),'debe_cambiar_password'=>true]);"`
- Comandos: `php artisan serve`, `php artisan migrate:fresh --seed`, `php artisan test`, `php artisan dusk`, `php artisan stock:verificar`, (luego) `php artisan sistema:estado`.

## 5. Problemas ya resueltos (lecciones)

- Windows agrega "(1)" a archivos descargados repetidos → renombrar antes de usarlos.
- OpenCode encontraba el PHP de XAMPP (8.2): se resolvió reiniciando Antigravity (PATH nuevo) y verificando `where.exe php`.
- `psql` pedía contraseña a ciegas y fallaba → usar `$env:PGPASSWORD='...'` y luego `Remove-Item Env:PGPASSWORD`.
- Prueba de restauración falló por falta de CREATEDB → `ALTER ROLE libreria_dev CREATEDB;`. En producción se usará un rol aparte `libreria_restore`.
- Al mover el skeleton de Laravel se sobrescribió AGENTS.md → se restauró. Se borraron CLAUDE.md y .npmrc del skeleton.
- `migrate:fresh --seed` resetea usuarios y contraseñas de desarrollo.
- Usar pgAdmin solo para MIRAR datos; nunca editar stock/ventas a mano (rompe kardex y auditoría).

## 6. Documentos del repositorio (docs/)

| Archivo | Para qué |
|---|---|
| propuesta.md | Propuesta original + bloque "Decisiones tomadas" |
| prompts-opencode.md | Especificación de PROMPTS 0–16 (base, login, usuarios, categorías, productos, stock, entradas, importación, ventas, ticket, historial, reportes, backups, instalación local, pruebas finales, caja) |
| prompts-finales.md | Corrección de fallos (A), push (B), PROMPTS 14 (instalación), 15 (seguridad y manuales), 16 (caja), 17 (puesta en marcha), 18 (proveedores), 19 (clientes), 20 (código de barras), 21 (devoluciones) |
| prompt-maestro.md | Ejecución autónoma por fases 0–7: Dusk, verificación de todo el plan de pruebas, código de barras, módulos 18/19/16/21, puesta en marcha, seguridad/manuales, instalación, regresión final y versión 1.0.0 |
| prompt-web.md | Versión online opción 2 (VPS + dominio): WEB-1 seguridad/TOTP, WEB-2 scripts Linux, WEB-3 docs y ensayo, WEB-4 VPS real |
| prompt-carga-inicial.md | Convertir la lista real de productos (PDF/Excel) en `carga-inicial/` (no se sube a git), verificarla, ensayar y cargar en producción desde cero (ventas #000001) con `php artisan carga:inicial` |
| plan-de-pruebas.md | ~190 pruebas con códigos (L-, R-, U-, C-, A-, K-, PR-, S-, E-, I-, V-, T-, H-, D-, RP-, B-, X-, DS-) |
| progreso.md | Bitácora técnica que escribe OpenCode durante los bloques (estado, decisiones, bloqueos) |
| informe-pruebas.md | (lo genera el maestro) resultado de cada prueba |
| pruebas-manuales-usuario.md | (lo genera el maestro) lo único que el usuario debe probar a mano |
| diseno.md, desarrollo.md, usuarios-y-permisos.md, backups.md, restauracion.md, importacion-productos.md, impresion-tickets.md | Documentación técnica |
| CONTEXTO-PROYECTO.md | ESTE documento |

## 7. Estado actual (26/09/2026)

| Etapa | Estado |
|---|---|
| PROMPT 0 reglas / 1 base Laravel | ✅ Hecho y en GitHub |
| PROMPT 2 login y roles / 3 usuarios, configuración, auditoría | ✅ Hecho |
| PROMPTS 4–13 (categorías → backups) | ✅ Hecho en bloque: 12 commits, 102 tests verdes, stock:verificar OK. Subido a rama `respaldo-bloque-4-13` |
| PROMPT DISEÑO | ✅ Hecho (commit b78974b, 103 tests) |
| Plan de pruebas manual | ⏭️ Reemplazado por verificación automática del prompt maestro |
| **PROMPT MAESTRO** | ✅ **TERMINADO** en OpenCode el 27/09/2026 (~01:50). 158 PHPUnit + 26 Dusk en verde, tag `v1.0.0` en `main`. Detalle en `docs/progreso.md` |
| Carga inicial de productos reales | ⏳ Prompt listo; ejecutar DESPUÉS del maestro |
| Versión web (opción 2, VPS) | 🔄 `docs/prompt-web.md` v2 (WEB-1 a WEB-4) en el repo; **WEB-1 (1.1–1.10) terminado** el 27/09/2026 (203 PHPUnit + 28 Dusk en verde, pusheado a `main`). Pendiente: probar según guía, comprar VPS/dominio para WEB-4; WEB-2 y WEB-3 sin ejecutar |

## 8. Pendientes (en orden)

1. ✅ Prompt maestro terminado y revisado (158 + 26 en verde, tag v1.0.0).
2. ✅ CONTEXTO-PROYECTO.md en el repo y con regla de mantenimiento en AGENTS.md.
3. **Escribir y ejecutar el PROMPT WEB** (sección 9): ✅ redactado v2; ✅ WEB-1 1.1–1.10 ejecutado y verificado (203 + 28 en verde, pusheado). ⏳ Probar con la guía del usuario; luego WEB-2 (scripts Linux + deploy) y WEB-3 (guías + ensayo VM/WSL2). Ajustar lo del maestro que asumía instalación local Windows (FASE 6) para que conviva o se reemplace por el despliegue en VPS.
4. Comprar dominio + VPS (el usuario pagará anual).
5. Ejecutar **carga inicial** (PARTE 1–2 en la PC; PARTE 3 en el servidor web, adaptada a Linux/SSH).
6. Conteo físico, usuarios reales (2 admins), logo y datos del negocio, impresora térmica y lector de barras en la PC de caja.
7. Primera semana en paralelo con el método actual; comparar cierres diarios.
8. Mejora pendiente: `sistema:limpiar-demo` debe reiniciar secuencias (ventas #000001) — incluida en prompt-carga-inicial.md.

## 9. Plan de la versión web (opción 2 elegida) — base para escribir el PROMPT WEB

Contexto de la decisión: se explicó al usuario que con todo en la web, **si se corta Internet en la librería no se puede vender** (alternativas: híbrido local + acceso remoto, o sincronización). Aun así eligió la opción 2. Recomendar al usuario un respaldo de conexión (datos móviles/router 4G) en la librería.

Requisitos y contenido del PROMPT WEB (a redactar en `docs/prompt-web.md`):
- **Proveedor:** VPS (no hosting compartido: suele no tener PostgreSQL ni SSH). Ubuntu LTS, 1–2 vCPU, 2 GB RAM, 25+ GB SSD. Costos aproximados: VPS ~5–10 USD/mes, dominio ~10–15 USD/año (verificar al comprar). Ayudar al usuario a elegir proveedor y comprar dominio.
- **Servidor:** Nginx + PHP 8.4-FPM (+ opcache) + PostgreSQL 17 (solo localhost) + Composer + Git; usuario de despliegue sin root; SSH con llaves y sin login de root ni contraseña; UFW (solo 22, 80, 443); fail2ban; actualizaciones automáticas de seguridad (unattended-upgrades); zona horaria America/La_Paz.
- **Dominio y HTTPS:** DNS A/AAAA al VPS; Let's Encrypt (certbot) con renovación automática; redirección HTTP→HTTPS; HSTS.
- **Laravel en producción:** APP_ENV=production, APP_DEBUG=false, APP_URL=https://dominio, SESSION_SECURE_COOKIE=true, SESSION_SAME_SITE=lax, TRUSTED_PROXIES si hay proxy/Cloudflare, cabeceras de seguridad (CSP compatible con los JS locales, X-Frame-Options, X-Content-Type-Options, Referrer-Policy), caches, colas no necesarias, `storage:link`, permisos www-data.
- **Seguridad extra por estar en Internet:** verificación en dos pasos (TOTP) obligatoria para admin y opcional para encargado; política de contraseñas más fuerte; limitar intentos de login por IP también; aviso de inicio de sesión desde nuevo dispositivo en auditoría; opcional restringir rol cajero a la IP de la librería (configurable); `/estilos` y Dusk inaccesibles; revisión de rutas públicas.
- **Backups en Linux:** reescribir los scripts PowerShell como bash (pg_dump -Fc diario por cron, retención, verificación con pg_restore --list, **copia fuera del VPS** cifrada — p. ej. rclone a Google Drive o almacenamiento S3 compatible —, prueba de restauración semanal automática con rol `libreria_restore`, registro del resultado en la tabla configuracion para la alerta del panel). Mantener los .ps1 para desarrollo en Windows.
- **Despliegue/actualización:** script `deploy.sh` (backup previo, `php artisan down`, git pull, composer install --no-dev, migrate --force, caches, `up`, rollback documentado). Opcional: GitHub Actions que ejecute tests en cada push.
- **Monitoreo:** chequeo de disponibilidad (UptimeRobot u otro gratuito), logs de Laravel/Nginx con rotación, alerta de espacio en disco, `php artisan sistema:estado` adaptado.
- **Impresión y lector en la PC de caja:** siguen igual (navegador + impresora térmica local + lector USB tipo teclado). Documentar configuración del navegador en la PC de caja con el dominio (modo quiosco con --kiosk-printing).
- **Carga inicial en el VPS:** subir `productos_normalizados.csv` por SCP/SFTP (fuera de la carpeta pública) y ejecutar `php artisan carga:inicial` por SSH; adaptar la PARTE 3 de prompt-carga-inicial.md (backups vía script bash).
- **Documentación:** docs/despliegue-web.md (paso a paso para comprar VPS/dominio, configurar DNS, instalar todo), docs/operacion-web.md (actualizar, backups, restaurar, qué hacer si el sitio cae o si se corta Internet en la librería — plan de contingencia: anotar ventas en papel y cargarlas después), actualizar manuales.
- **Ensayo antes de comprar:** probar el despliegue completo en una VM local con Ubuntu (o WSL2) antes de ir al VPS real.

## 10. Datos a no olvidar

- Todo lo que el agente haga en producción: nunca datos demo, nunca ventas de prueba en la base real (el contador de ventas no vuelve a 0).
- Tener 2 usuarios admin reales para poder restablecerse contraseñas mutuamente.
- `carga-inicial/`, `.env`, backups y `backup.config.ps1` nunca se suben a git.

## 11. Cómo mantener este documento

- Vive en `docs/CONTEXTO-PROYECTO.md` en el repositorio.
- Regla para OpenCode (agregar a AGENTS.md): "Al terminar cada tarea o fase, agrega una entrada en la sección 12 (Bitácora) de docs/CONTEXTO-PROYECTO.md con fecha, qué se hizo, commits y pendientes, y actualiza las secciones 7 y 8. Nunca borres entradas anteriores."
- Para retomar con una IA nueva: darle este archivo (o el enlace del repo) y decir "Lee docs/CONTEXTO-PROYECTO.md completo y continúa desde los Pendientes".

## 12. Bitácora (agregar entradas nuevas al final)

### 26/09/2026 — Sesión inicial con Claude (≈05:00–16:10)
- Revisión de la propuesta; se decidió: sin facturación, MVP primero, Laravel + PostgreSQL local, sin Node, sin código de barras (luego se reincorporó).
- Instalación del entorno de desarrollo en Windows (PHP 8.4, Composer, PostgreSQL 17, bases dev/test).
- Repo conectado a GitHub; PROMPT 0 (AGENTS.md) y PROMPT 1 (Laravel base) hechos y subidos.
- Elección de modelo: Muse Spark Free en OpenCode (Claude Pro no usable en OpenCode; Gemini free descartado).
- Bloque de PROMPTS 4–13 ejecutado de una vez (102 tests). Diseño visual aplicado (103 tests). Respaldo en rama `respaldo-bloque-4-13`.
- Creados: plan-de-pruebas.md, prompts-finales.md, prompt-maestro.md (con código de barras y autoverificación con Dusk + BD), prompt-carga-inicial.md.
- Prompt maestro lanzado (~15:45).
- Decisión: versión online opción 2 (VPS + dominio). Pendiente redactar prompt-web.md (sección 9).
- Creado este documento de contexto.

### 27/09/2026 — Prompt maestro terminado + PROMPT WEB (~01:50)
- FASES 0–7 completadas: Dusk con Edge, verificación total del plan (informe-pruebas.md), código de barras, proveedores, clientes, caja, devoluciones, puesta en marcha, seguridad (3 fixes), manuales, instalación Windows, día completo en Dusk.
- 158 tests PHPUnit + 26 Dusk en verde, stock limpio, tag `v1.0.0` en `main`.
- CONTEXTO-PROYECTO.md ya estaba en el repo; se agregó la regla de mantenimiento a AGENTS.md.
- En curso: redacción de `docs/prompt-web.md` (VPS + dominio, opción 2).

### 27/09/2026 — PROMPT WEB redactado
- Creado `docs/prompt-web.md` desde la sección 9: WEB-1 (seguridad web, TOTP, cabeceras, límites), WEB-2 (scripts Linux backup/restore/deploy + Actions opcional), WEB-3 (despliegue-web.md, operacion-web.md, manuales, ensayo en VM/WSL2), WEB-4 (VPS real, carga inicial por SSH).
- Actualizadas secciones 6, 7 y 8 de este documento.
- Pendiente: confirmación para ejecutar WEB-1; compra de VPS + dominio para WEB-4.

### 27/09/2026 — WEB-1 ejecutado (opción 2: mantener base y completar faltantes)
- Reemplazado `docs/prompt-web.md` por la v2 (WEB-1 a WEB-4 detallado).
- TOTP según spec 1.1: secreto cifrado, confirmado_en, anti-reúso por paso, ventana ±1, 10 códigos hash de un solo uso, 5 intentos por sesión, sesión intermedia sin acceso, flujo forzado (`ExigirTotp`), Mi seguridad (activar/regenerar/desactivar con password + código), reset por admin y por consola (`totp:restablecer --motivo`), config `totp_obligatorio_admin/encargado`.
- Contraseñas (1.4): mín. 10/8, regla `PasswordNoTrivial`, cajero con clave fija (403 en /cambiar-password), cambio propio con actual, debe_cambiar solo admin/encargado.
- Límites (1.5/1.6/1.7): login 5 intentos + 20 por IP, nota de dispositivo nuevo, IP cajero configurable, actividad reciente `/auditoria/actividad`, cookies documentadas en `.env.example`.
- Rutas públicas (1.8): `RutasPublicasTest`; contingencia (1.9): comando + sección en manual-administrador.md.
- `actingAs`/`loginAs` equivalen a sesión completa (flujo real cubierto en `LoginTest`); APP_KEY de testing en `phpunit.xml`.
- Commits: `d71cb23`, `5808d0f`, `4d9b510`, `1321dc4`, `c6e5b32`, `3038c1f`, `b27a35b`, `f565066` — pusheados a `main`.
- Verificación: 184 PHPUnit + 27 Dusk en verde, `stock:verificar` sin diferencias. (Un Dusk falló una vez por flake de timing y pasó al re-ejecutar.)
- Pendiente: probar según guía del usuario; luego WEB-2/WEB-3; compra de VPS/dominio para WEB-4.

### 27/09/2026 — WEB-1 v2 1.1–1.10 ejecutado (PHP volvió a funcionar)
- 1.1: `.env.production.example` + excepción en `.gitignore` (la tabla `sessions` ya la cubre la migración base; se eliminó un duplicado que rompía `migrate:fresh`).
- 1.2: `trustProxies` con `TRUSTED_PROXIES` vía middleware `ConfiarProxies` (hallazgo: el closure de bootstrap corre antes de cargar `.env`; verificado en vivo con `X-Forwarded-For`).
- 1.3: CSP estricta global + 11 fragmentos JS movidos a `public/js` + `CspTest` Dusk (control negativo hecho y revertido).
- 1.4: contraseñas unificadas (mín. 10 + letras + números, cajero incluido), `uncompromised()` configurable (fail-open), forzar cambio a todos, cierre de demás sesiones.
- 1.5: IP 20/15 min + throttles (anular 30/min, importar 20/10 por min).
- 1.6: gaps TOTP (imprimir códigos, auditoría por intento, test fuera de ventana, manual, justificación de dependencias).
- 1.7: cookie `nf_dispositivo` + tabla + aviso en panel + Mi seguridad; restricción cajero por IP rehecha (switch + CIDR, rechazo en login; eliminado middleware viejo).
- 1.8: whitelist automática de rutas, `_dusk` ausente en producción, páginas de error propias.
- 1.9: ventas de contingencia + `docs/planilla-contingencia.md`.
- 1.10: manuales, `usuarios-y-permisos.md`, pruebas-manuales "Versión web", bitácora.
- Commits: `88af595`, `898c3c0`, `94f57a8`, `da21019`, `23840b8`, `d1ee4bd`, `3995b09`, `ef988b3`, `b6ab6d3`, `036bec5` — pusheados a `main`.
- Verificación: 203 PHPUnit + 28 Dusk en verde (Dusk pasó a la primera, sin flakes), `stock:verificar` sin diferencias.
- Pendiente: tu prueba manual (guía en el resumen); luego WEB-2/WEB-3; compra de VPS/dominio para WEB-4.
