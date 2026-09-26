# PROMPT MAESTRO — Terminar, verificar y dejar listo NF Librería

Instrucción para OpenCode. Se ejecuta de corrido, por fases, verificándose a sí mismo.

Para iniciarlo, escribe en OpenCode:

    Lee docs/prompt-maestro.md completo y ejecútalo desde la FASE 0. Sigue sus reglas al pie de la letra.

Para retomarlo si se corta:

    Lee docs/prompt-maestro.md y docs/progreso.md, y continúa desde el siguiente paso pendiente con las mismas reglas.

---

## Objetivo

Dejar el sistema **terminado, verificado y listo para instalar** en la librería, sin que el usuario tenga que probar pantalla por pantalla. Tú (OpenCode) implementas lo que falta Y compruebas por ti mismo que todo funciona como se pidió: usando la aplicación en un navegador real automatizado, revisando la base de datos y ejecutando los scripts. Si algo falla, lo corriges y vuelves a probar hasta que pase (prueba y error).

Documentos de referencia (léelos antes de empezar):
- AGENTS.md — reglas del proyecto (obligatorias).
- docs/diseno.md — componentes y estilo visual (toda pantalla nueva los usa).
- docs/propuesta.md — propuesta original.
- docs/prompts-opencode.md — especificación de los PROMPTS 0–16.
- docs/prompts-finales.md — especificación de los PROMPTS 14–21.
- docs/plan-de-pruebas.md — lo que el sistema debe hacer, prueba por prueba.

Si hay contradicción, prevalece este documento; después AGENTS.md; después los prompts.

---

## Reglas de este trabajo

1. **Autonomía.** No te detengas a preguntar. Si hay que decidir algo, elige la opción más segura y coherente con AGENTS.md, anótala en docs/progreso.md bajo "Decisiones para revisar" y continúa. Si algo requiere una acción física del usuario (hardware, otra PC, router, permisos de administrador de Windows que no tengas), anótalo en "Pruebas manuales para el usuario" y sigue.

2. **Commits y push.** Commit local al terminar cada paso con el mensaje indicado. Al final de cada FASE, si `php artisan test` está 100% en verde y `php artisan stock:verificar` no reporta diferencias, haz `git push origin main`. Nunca hagas push con tests en rojo.

3. **Tests sagrados.** Prohibido borrar, saltar (skip/markTestIncomplete) o debilitar tests para que pasen. Si un test estaba mal especificado, corrígelo y explica por qué en progreso.md.

4. **Prueba y error.** Cada funcionalidad se da por terminada solo cuando:
   a) tiene tests automáticos (Feature/Unit y, si usa JavaScript o teclado, tests de navegador con Dusk),
   b) los tests pasan,
   c) verificaste el estado real en la base de datos (consultas con psql o php artisan tinker) después de ejecutar el flujo,
   d) el diseño cumple docs/diseno.md.
   Si algo falla: diagnostica, corrige, vuelve a ejecutar. Máximo 5 intentos por problema; si sigue fallando, anótalo como BLOQUEADO en progreso.md con el error completo y continúa con lo que no dependa de eso.

5. **Registro.** Actualiza docs/progreso.md al terminar cada paso: qué se hizo, tests totales, verificaciones en BD realizadas, decisiones, bloqueos.

6. **Base de datos.** Nunca uses libreria_dev para los tests automáticos (usa libreria_test y libreria_dusk). Al final de cada fase deja libreria_dev en estado limpio con `php artisan migrate:fresh --seed`.

7. **Sin Internet en producción.** Dusk y ChromeDriver son solo dependencias de desarrollo (`composer require --dev`); nunca deben ser necesarios en producción (`composer install --no-dev`).

---

## FASE 0 — Preparación del entorno de pruebas

0.1. Verifica: git status limpio, php artisan test en verde, stock:verificar sin diferencias. Si algo falla, arréglalo primero.

0.2. Verifica que libreria_dev tenga CREATEDB y que exista %APPDATA%\postgresql\pgpass.conf. Si falta algo, anótalo en "Pruebas manuales para el usuario" con el comando exacto, y continúa (las fases que lo necesiten lo marcarán como pendiente).

0.3. Instala Laravel Dusk como dependencia de desarrollo:
- composer require --dev laravel/dusk; php artisan dusk:install.
- Instala el ChromeDriver que coincida con el Chrome o Edge instalado (php artisan dusk:chrome-driver --detect). Si no hay Chrome, usa Edge con msedgedriver.
- Crea la base libreria_dusk (dueño libreria_dev) y un .env.dusk.local que apunte a ella (APP_ENV=local, APP_URL=http://127.0.0.1:8001). Agrégalo a .gitignore.
- Los tests de Dusk usan DatabaseMigrations + seeders necesarios y se ejecutan con php artisan serve --port=8001 en segundo plano (documenta en docs/desarrollo.md cómo ejecutarlos: php artisan dusk).
- Crea un test de humo de Dusk (login y ver el panel) y confirma que pasa, en modo headless.

0.4. Commit "test: entorno de pruebas de navegador con Dusk".

---

## FASE 1 — Verificación completa de lo que ya existe (plan de pruebas)

1.1. Recorre docs/plan-de-pruebas.md sección por sección (0 a 17). Para CADA código de prueba (L-01, R-01, U-01, … DS-17):
- Si ya existe un test automático que lo cubre, anótalo.
- Si no existe, créalo:
  - Tests Feature (HTTP) para permisos, validaciones, cálculos, estados y auditoría.
  - Tests Dusk para todo lo que depende de JavaScript, teclado o interfaz: búsqueda con flechas y Enter, F2/F9/Esc, carrito, cambio en vivo, botones de billetes, doble clic en COBRAR, modales de confirmación (x-confirmar), toasts, sugerir código, advertencia de precio, entradas dinámicas, importación con vista previa, foco en "Nueva venta" del ticket, menú colapsable en pantalla chica (ventana 390×844), pantalla de venta sin scroll en 1366×768, título de pestaña y favicon.
  - Para concurrencia (X-01), prueba con dos procesos o conexiones reales a la base (por ejemplo, un comando artisan de prueba que lance dos ventas simultáneas del último ítem con stock negativo desactivado) y verifica en BD que el stock nunca quede negativo y que solo una venta se registró.
- Después de cada flujo importante (venta, anulación, entrada, anulación de entrada, ajuste, importación, restablecer contraseña), verifica en la base con consultas: filas creadas, stock final, movimientos_stock con stock_anterior/stock_nuevo correctos, registros en auditoria sin contraseñas.
- Ejecuta tú mismo los scripts de backup y probar-restauracion.ps1 y verifica los archivos y el log.

1.2. Cada fallo que encuentres: corrígelo (commit "fix: … (código)") y vuelve a probar.

1.3. Genera docs/informe-pruebas.md: tabla con TODOS los códigos del plan → cómo se verificó (nombre del test o comando), resultado (OK / CORREGIDO / MANUAL / BLOQUEADO) y evidencia breve (por ejemplo, resultado de la consulta en BD).

1.4. Commit "test: verificación automatizada del plan de pruebas". Push si todo está en verde.

---

## FASE 2 — Código de barras con lector USB (PROMPT 20 de docs/prompts-finales.md)

Implementa el PROMPT 20 completo, con estas precisiones:
- El lector USB funciona como teclado: escribe el código muy rápido y termina con Enter. La pantalla de venta y la de entradas deben aceptar escaneos consecutivos sin tocar el mouse: cada escaneo agrega el producto (o suma 1), limpia el buscador y lo deja enfocado.
- El foco debe volver SIEMPRE al buscador después de: agregar producto, cambiar cantidad, quitar ítem, cerrar un modal y terminar una venta (en "Nueva venta").
- Escanear un código que no existe: aviso breve (toast + sonido corto generado con Web Audio API, sin archivos externos) sin bloquear.
- Escanear mientras el foco está en otro campo (por ejemplo, "Recibido"): detectar entrada rápida terminada en Enter (caracteres con menos de 50 ms entre sí) y redirigirla al carrito; documentar el comportamiento.
- En el formulario de producto, el campo código de barras acepta escaneo y NO envía el formulario al recibir el Enter del lector.
- Etiquetas Code128 imprimibles para productos sin código de fábrica (librería PHP por Composer, sin CDN).
- Agrega una sección "19. Código de barras" al plan de pruebas.
- Tests Dusk que simulan el lector: escribir el código con type() + Enter rápido, varias veces seguidas, y verificar cantidades en el carrito y en la BD tras cobrar.
Commit "feat: código de barras con lector USB".

---

## FASE 3 — Módulos adicionales (docs/prompts-finales.md)

Implementa en este orden, cada uno completo, con sus tests (Feature + Dusk donde haya interfaz), verificación en BD, diseño según docs/diseno.md y su sección nueva en el plan de pruebas:
3.1. PROMPT 18 — Proveedores. Commit "feat: módulo de proveedores".
3.2. PROMPT 19 — Clientes. Commit "feat: módulo de clientes".
3.3. PROMPT 16 — Caja (docs/prompts-finales.md). La opción exigir_caja_abierta se crea con valor por defecto "1". Actualiza los tests de ventas existentes para que abran caja cuando corresponda (sin debilitarlos). Commit "feat: módulo de caja con apertura y cierre".
3.4. PROMPT 21 — Devoluciones parciales. Commit "feat: devoluciones parciales".

Después de 3.4: vuelve a ejecutar TODA la suite (php artisan test y php artisan dusk) y stock:verificar. Actualiza informe-pruebas.md con las secciones nuevas. Push si todo está en verde.

---

## FASE 4 — Puesta en marcha (PROMPT 17 de docs/prompts-finales.md)

Implementa el PROMPT 17 completo (sistema:estado, sistema:limpiar-demo, conteo físico inicial, docs/puesta-en-marcha.md). La hoja de conteo debe incluir el código de barras si existe. Verifica los comandos ejecutándolos. Commit "feat: herramientas de puesta en marcha". Push si todo está en verde.

---

## FASE 5 — Seguridad, manuales y README (PROMPT 15 de docs/prompts-finales.md)

Implementa el PROMPT 15 con este cambio: NO esperes confirmación. Genera docs/revision-seguridad.md, corrige tú mismo todo lo de gravedad Alta y Media (commits "fix(seguridad): …" con test), y deja lo de gravedad Baja listado como recomendación. La revisión debe cubrir TODOS los módulos, incluidos código de barras, proveedores, clientes, caja y devoluciones.
Los manuales deben incluir: uso del lector de código de barras, caja (abrir, movimientos, cerrar y arqueo), clientes, proveedores y devoluciones. La "hoja rápida" del cajero debe caber en una página A4.
Commit "docs: manuales, revisión de seguridad y README". Push si todo está en verde.

---

## FASE 6 — Instalación en la librería (PROMPT 14 de docs/prompts-finales.md)

Implementa el PROMPT 14 completo (docs/instalacion.md, docs/red-local.md, docs/configuracion.md, docs/versiones.md, scripts/instalar-produccion.ps1, scripts/actualizar.ps1, archivo VERSION = 1.0.0, rol libreria_restore).
Verificación en esta PC, sin romper el entorno de desarrollo:
- Ejecuta la aplicación con APP_ENV=production y APP_DEBUG=false usando un .env temporal (restaura el original al terminar): páginas de error sin datos técnicos, /estilos inaccesible, seeders demo no se ejecutan, caches generadas sin errores.
- Ejecuta scripts/instalar-produccion.ps1 en modo simulación (agrega un parámetro -Simulacion que muestre lo que haría sin cambiar nada) y, si es seguro, contra una base de ensayo libreria_ensayo, para comprobar que es idempotente (ejecutarlo dos veces).
- Ejecuta scripts/actualizar.ps1 contra la base de ensayo.
- Borra la base de ensayo al terminar.
Commit "feat: instalación de producción, red local y actualización". Push si todo está en verde.

---

## FASE 7 — Regresión final y entrega

7.1. php artisan migrate:fresh --seed; php artisan test; php artisan dusk; php artisan stock:verificar; php artisan sistema:estado. Todo debe estar OK.

7.2. Recorrido final con Dusk de un "día completo" en la librería (un test largo, documentado paso a paso):
admin crea un cajero → cajero cambia su contraseña → cajero abre caja → vende escaneando 5 productos por código de barras, con efectivo y cambio → vende fotocopias con QR → encargado registra entrada de mercadería de un proveedor → encargado aplica un descuento en una venta → encargado anula una venta con motivo → cliente devuelve 1 producto de otra venta → cajero registra un egreso de caja → cajero cierra caja con arqueo → encargado revisa el cierre del día y los reportes.
Al final, verifica en la BD: stock de cada producto involucrado = stock inicial + entradas − ventas + anulaciones + devoluciones; totales del cierre del día = suma de ventas COMPLETADA menos devoluciones; auditoría contiene todas las acciones sensibles; stock:verificar sin diferencias.

7.3. Actualiza docs/informe-pruebas.md completo (todas las secciones del plan de pruebas, incluidas las nuevas) y docs/plan-de-pruebas.md (con las secciones nuevas).

7.4. Crea docs/pruebas-manuales-usuario.md: SOLO lo que no se puede automatizar, lo más corto posible (idealmente menos de 20 puntos), por ejemplo: imprimir en la impresora térmica real, escanear con el lector físico, abrir desde otra PC y desde el celular por la IP, reiniciar la PC y ver que los servicios arrancan solos, tarea programada de backup en el Programador de tareas de Windows, y una revisión visual general. Cada punto con pasos y resultado esperado.

7.5. Actualiza VERSION a 1.0.0 y el CHANGELOG. Commit "chore: versión 1.0.0". Push. Crea el tag v1.0.0 y haz push del tag.

7.6. Mensaje final para el usuario con:
- Resumen de cada fase (qué se hizo, cantidad de tests Feature/Unit y Dusk).
- Tabla de docs/informe-pruebas.md resumida: cuántas pruebas OK, corregidas, manuales, bloqueadas.
- Lista de "Decisiones para revisar".
- Lista de BLOQUEADOS (si hay).
- Qué debe probar él (docs/pruebas-manuales-usuario.md).
- Próximo paso: instalar en la librería siguiendo docs/instalacion.md.
