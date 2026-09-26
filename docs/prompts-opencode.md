# Prompts para OpenCode — NF Librería

Guía de trabajo paso a paso. Cada PROMPT es una tarea completa para OpenCode.

## Cómo usar este archivo

1. Guarda este archivo en `docs/prompts-opencode.md` dentro del proyecto.
2. Ejecuta **un prompt a la vez, en orden**. No pases al siguiente hasta que el anterior funcione y tenga su commit.
3. Hay dos formas de enviar cada prompt:
   - **Copiar y pegar** el bloque del prompt en OpenCode, o
   - escribir en OpenCode: `Lee docs/prompts-opencode.md y ejecuta SOLO el PROMPT N. No hagas nada de los demás prompts.`
4. En los prompts grandes (marcados con ⚠️), primero usa el modo **Plan** (tecla Tab): pega el prompt, revisa el plan que propone, y luego cambia a **Build** y escribe "Hazlo".
5. Después de cada prompt, sigue la sección **"Cómo probar"**. Si todo funciona, envía el mensaje de **commit** indicado.
6. Si algo falla: copia el error completo y pégaselo a OpenCode diciendo "Esto falla: ...". Si OpenCode hace algo que no pediste, descarta con `git restore .` y `git clean -fd` (borra archivos nuevos no guardados en git) y repite el prompt.
7. Antes de los commits importantes (ventas, stock, permisos), pega el resultado de `git diff` en el chat de Claude para una revisión.

## Orden

| # | Tarea | MVP |
|---|-------|-----|
| 0 | Reglas del proyecto (AGENTS.md) | ✅ |
| 1 | Estructura base Laravel + PostgreSQL | ✅ |
| 2 | Login, roles, layout y auditoría base | ✅ |
| 3 | Administración de usuarios y configuración | ✅ |
| 4 | Categorías | ✅ |
| 5 | Productos | ✅ |
| 6 | Servicio de stock, ajustes y kardex | ✅ |
| 7 | Entradas de mercadería | ✅ |
| 8 | Importación de productos desde CSV | ✅ |
| 9 | Ventas — lógica y tests | ✅ |
| 10 | Ventas — pantalla de caja y ticket | ✅ |
| 11 | Historial y anulación de ventas | ✅ |
| 12 | Panel de inicio y reportes | ✅ |
| 13 | Backups y restauración | ✅ |
| 14 | Instalación en la PC de la librería y red local | ✅ |
| 15 | Pruebas finales y manuales | ✅ |
| 16 | Caja (apertura y cierre) | Después del MVP |

---

## PROMPT 0 — Reglas del proyecto

```text
Vamos a desarrollar un sistema de gestión y ventas para una librería. La propuesta completa está en docs/propuesta.md: léela, especialmente el bloque "Decisiones tomadas" del inicio, que prevalece sobre el resto.

En esta tarea NO escribas código de la aplicación. Solo:

1. Confirma que existe docs/propuesta.md y dame un resumen de 5 líneas de lo que entendiste.

2. Reemplaza por completo el AGENTS.md actual con este contenido exacto:

# Reglas del proyecto NF-Libreria

## Contexto
- Sistema de ventas e inventario para una librería en Bolivia. Moneda: Bs. Zona horaria: America/La_Paz.
- Funciona 100% local: una PC servidor (PHP + PostgreSQL) y otras PCs acceden por navegador en la red local.
- NO depender de Internet: nada de CDNs; todas las librerías CSS/JS se guardan localmente en el proyecto.
- Sin facturación electrónica por ahora, pero no diseñar nada que impida agregarla después.
- Sin código de barras por ahora: los productos se identifican por código interno y nombre.
- Interfaz y documentación en español.
- Referencias: docs/propuesta.md (propuesta) y docs/prompts-opencode.md (tareas).

## Stack
- Laravel (última versión estable), PHP 8.4, PostgreSQL 17.
- Vistas Blade + Bootstrap 5 guardado en public/vendor/. JavaScript simple (vanilla) en public/js/. Sin Node, npm ni Vite.
- Tests con PHPUnit sobre la base libreria_test.
- Desarrollo en Windows (PowerShell).

## Forma de trabajo
- Haz SOLO la tarea pedida. No adelantes trabajo de otras tareas ni modifiques archivos fuera del alcance.
- Antes de modificar, explica brevemente qué archivos vas a crear o tocar.
- NO hagas commit ni push hasta que yo confirme que probé y funciona. Cuando confirme, usa Conventional Commits en español (feat:, fix:, docs:, refactor:, test:, chore:).
- Al terminar cada tarea: ejecuta php artisan test, y dime qué cambiaste, cómo probarlo paso a paso y qué quedó pendiente.
- Si algo no está claro o hay que tomar una decisión de negocio, pregúntame antes de inventar.
- Usa las herramientas de Laravel (migraciones, Form Requests, Gates/Policies, middleware, Eloquent, DB::transaction, factories) en lugar de reinventarlas.
- La lógica de negocio va en clases de servicio (app/Services), no en los controladores ni en las vistas.

## Seguridad
- Nunca subir a git: .env, contraseñas, backups, dumps de base de datos, datos reales. Usar .env.example con valores de ejemplo.
- Nunca concatenar variables en SQL; usar Eloquent, Query Builder o consultas con parámetros.
- Contraseñas siempre con Hash de Laravel.
- Protección CSRF en todos los formularios (@csrf) y en las peticiones fetch que modifican datos.
- Escapar toda salida en Blade con {{ }}; no usar {!! !!} con datos ingresados por usuarios.
- Verificar permisos en el servidor en cada ruta y acción (middleware/Gates), no solo ocultar botones.
- Nunca confiar en precios o totales enviados por el navegador: el servidor los recalcula desde la base de datos.

## Base de datos
- Cambios de esquema SOLO mediante migraciones. Nunca editar una migración ya subida a GitHub: crear una nueva.
- Nombres de tablas y columnas en español, en snake_case (productos, precio_venta, etc.).
- Dinero siempre decimal(12,2) (NUMERIC), nunca float.
- Fechas con timestampTz.
- Las ventas NUNCA se eliminan: se anulan (estado ANULADA, usuario, fecha y motivo), devolviendo el stock.
- detalle_ventas guarda una copia del código, nombre y precio unitario del producto al momento de la venta.
- Todo cambio de stock pasa por App\Services\StockService y se registra en movimientos_stock (tipo, cantidad, stock anterior, stock nuevo, usuario, fecha, referencia). Nunca modificar productos.stock directamente en otro lugar.
- Ventas, entradas, ajustes y anulaciones van dentro de DB::transaction, bloqueando los productos con lockForUpdate() (ordenados por id para evitar bloqueos cruzados).
- Stock negativo: configurable; por defecto permitido con advertencia visible.
- Las acciones importantes se registran con App\Services\AuditoriaService en la tabla auditoria.
- Productos, categorías y usuarios no se borran: se desactivan.

## Pruebas
- Toda lógica crítica debe tener tests automáticos: stock, ventas, anulaciones, totales, permisos por rol.
- Los tests usan la base libreria_test, nunca libreria_dev ni producción.
- No se hace commit con tests fallando.

3. Crea un .gitignore básico (.env, .env.*, excepto .env.example; backups/; *.sql; *.backup; *.dump; *.log; Thumbs.db; desktop.ini; .vscode/; .idea/). Laravel agregará sus reglas después.

4. Muéstrame los archivos creados. No hagas commit todavía.
```

**Cómo probar:** abre `AGENTS.md` y verifica que tenga todas las secciones. Ejecuta `git status`: deben aparecer `AGENTS.md`, `.gitignore` y `docs/`.

**Commit:**
```text
Está bien. Haz commit con el mensaje "chore: reglas del proyecto y propuesta" y push a origin main.
```

---

## PROMPT 1 — Estructura base Laravel + PostgreSQL

> Antes de pegarlo, reemplaza `<CLAVE_LIBRERIA_DEV>` por la contraseña del usuario libreria_dev de PostgreSQL.

```text
Tarea: PROMPT 1 — crear la base del proyecto con Laravel. Lee AGENTS.md. El repositorio solo tiene AGENTS.md, .gitignore y docs/.

1. Crea un proyecto Laravel (última versión estable) con Composer en una carpeta temporal (por ejemplo ..\laravel_tmp) y mueve TODO su contenido (incluidos archivos ocultos como .env.example, .editorconfig, .gitattributes) a la raíz del repositorio. NO sobrescribas AGENTS.md, docs/ ni .git. Combina el .gitignore de Laravel con el existente sin perder reglas. Borra la carpeta temporal al final.

2. Configura .env (NO se sube a git):
   APP_NAME="NF Librería"
   APP_LOCALE=es
   APP_FALLBACK_LOCALE=es
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=libreria_dev
   DB_USERNAME=libreria_dev
   DB_PASSWORD=<CLAVE_LIBRERIA_DEV>
   SESSION_DRIVER=database
   Ejecuta php artisan key:generate.
   Actualiza .env.example con las mismas claves pero DB_PASSWORD vacío y APP_KEY vacío.

3. Configura:
   - config/app.php: timezone 'America/La_Paz', locale 'es'.
   - phpunit.xml: los tests deben usar DB_CONNECTION=pgsql y DB_DATABASE=libreria_test (quita la configuración de SQLite en memoria).
   - Traducciones al español: crea lang/es/ con validation.php, auth.php, passwords.php y pagination.php traducidos (puedes publicar los archivos en inglés con php artisan lang:publish y traducirlos, o usar el paquete laravel-lang/common si se instala sin problemas).

4. Frontend sin Node:
   - Elimina package.json, vite.config.js, resources/css/app.css, resources/js/ y cualquier referencia a @vite en las vistas.
   - Descarga Bootstrap 5 (última 5.x): bootstrap.min.css y bootstrap.bundle.min.js en public/vendor/bootstrap/. Descarga también Bootstrap Icons (css + carpeta fonts) en public/vendor/bootstrap-icons/. Si no puedes descargar, dame los enlaces exactos y lo hago yo.
   - Crea public/css/app.css y public/js/app.js vacíos (con un comentario) para estilos y scripts propios.
   - Crea resources/views/layouts/app.blade.php: HTML5, lang="es", meta viewport, carga Bootstrap, Bootstrap Icons, css/app.css y js/app.js locales con asset(), un @yield('titulo') en el <title>, un contenedor principal con @yield('contenido') y un @stack('scripts') al final.
   - Reemplaza la vista de inicio por resources/views/inicio.blade.php que extienda el layout y muestre "NF Librería — sistema en desarrollo" y la fecha/hora actual del servidor.

5. Crea un helper para mostrar dinero: app/Support/helpers.php con la función bs(float|string|null $monto): string que devuelve formato boliviano "Bs. 1.234,50" (punto de miles, coma decimal, 2 decimales). Regístralo en composer.json (autoload.files) y ejecuta composer dump-autoload. Agrega un test unitario tests/Unit/HelperBsTest.php que pruebe 0, 25, 1234.5 y null.

6. Ejecuta php artisan migrate, luego php artisan test, y muéstrame los resultados.

7. Agrega a AGENTS.md, en la sección Stack, los comandos frecuentes:
   - Servidor de desarrollo: php artisan serve
   - Migrar: php artisan migrate
   - Reconstruir BD de desarrollo con datos de prueba: php artisan migrate:fresh --seed
   - Tests: php artisan test

8. Crea docs/desarrollo.md explicando paso a paso cómo levantar el proyecto en una PC de desarrollo nueva: requisitos (PHP 8.4 con pdo_pgsql, Composer, PostgreSQL 17, Git), crear usuario y bases libreria_dev y libreria_test, clonar, composer install, copiar .env.example a .env, key:generate, migrate, serve.

No crees módulos de usuarios, productos ni ventas. No hagas commit hasta que yo pruebe.
Al terminar dime cómo probarlo.
```

**Cómo probar:**
1. En una terminal nueva: `php artisan serve`
2. Abre http://127.0.0.1:8000: debe verse "NF Librería — sistema en desarrollo" con estilo Bootstrap y la hora de Bolivia.
3. Desconecta Internet y recarga (Ctrl+F5): debe verse igual (prueba que no usa CDN).
4. `php artisan test`: todo en verde.
5. `git status`: **no** debe aparecer `.env` ni `vendor/`.

**Commit:**
```text
Funciona. Haz commit con "feat: estructura base Laravel con PostgreSQL" y push.
```

---

## PROMPT 2 — Login, roles, layout y auditoría base ⚠️

```text
Tarea: PROMPT 2 — autenticación, roles, layout con menú y auditoría base. Lee AGENTS.md.
No uses Breeze, Jetstream ni paquetes que requieran Node. Implementa el login manualmente con Auth de Laravel.

## Base de datos
Como el proyecto aún no está en producción, EN ESTA TAREA sí puedes modificar la migración original de users de Laravel y luego ejecutar migrate:fresh.

1. Tabla users (modifica la migración original):
   - id
   - nombre string(100)
   - usuario string(50) unique (con esto se inicia sesión, en minúsculas)
   - password string
   - rol string(20): valores permitidos 'admin', 'encargado', 'cajero'
   - activo boolean default true
   - debe_cambiar_password boolean default false
   - ultimo_acceso timestampTz nullable
   - remember_token
   - timestampsTz
   Quita email y email_verified_at. Mantén las tablas password_reset_tokens (puedes quitarla si no se usa) y sessions.

2. Tabla auditoria:
   - id
   - user_id foreignId nullable (users, nullOnDelete)
   - accion string(50) (ej: LOGIN, LOGIN_FALLIDO, LOGOUT, CREAR, EDITAR, DESACTIVAR, ACTIVAR, ANULAR, AJUSTE_STOCK, CAMBIO_PRECIO, CAMBIO_PASSWORD, CONFIGURACION)
   - entidad string(50) nullable (ej: usuario, producto, venta)
   - entidad_id unsignedBigInteger nullable
   - descripcion text
   - datos_anteriores jsonb nullable
   - datos_nuevos jsonb nullable
   - ip string(45) nullable
   - created_at timestampTz (sin updated_at)
   - índices en (entidad, entidad_id), user_id y created_at.
   Los registros de auditoría nunca se editan ni se borran desde la aplicación.

3. Tabla configuracion:
   - clave string(100) primary key
   - valor text nullable
   - descripcion string nullable
   - timestampsTz

## Código
4. Enum PHP App\Enums\Rol (admin, encargado, cajero) con un método etiqueta() que devuelve "Administrador", "Encargado", "Cajero". Úsalo como cast en el modelo User.

5. App\Services\AuditoriaService con un método registrar(string $accion, string $descripcion, ?Model $entidad = null, ?array $antes = null, ?array $despues = null): toma el usuario autenticado y la IP de la request automáticamente. Nunca guardar contraseñas ni tokens en datos_anteriores/datos_nuevos.

6. Modelo Configuracion y App\Services\ConfiguracionService con get(string $clave, $defecto = null) y set(string $clave, $valor), con caché en memoria por request. Seeder con estos valores iniciales:
   - nombre_negocio = "NF Librería"
   - direccion = ""
   - telefono = ""
   - mensaje_ticket = "¡Gracias por su compra!"
   - permitir_stock_negativo = "1"
   - minutos_inactividad = "60"

7. Login:
   - Ruta GET /login (vista con usuario y contraseña, diseño centrado y limpio) y POST /login.
   - Solo usuarios activos pueden entrar. Mensaje de error genérico: "Usuario o contraseña incorrectos" (no revelar cuál falló).
   - Limitar intentos: 5 intentos fallidos por usuario+IP bloquean 1 minuto (RateLimiter).
   - Al entrar: regenerar sesión, actualizar ultimo_acceso, auditar LOGIN. Intento fallido: auditar LOGIN_FALLIDO con el usuario intentado (sin la contraseña).
   - POST /logout: auditar LOGOUT, invalidar sesión, volver al login.
   - Si debe_cambiar_password es true, después del login obligar a ir a /cambiar-password y no permitir usar otra pantalla hasta cambiarla (middleware).
   - Pantalla /cambiar-password para cualquier usuario: contraseña actual, nueva (mínimo 8 caracteres) y confirmación. Auditar CAMBIO_PASSWORD.
   - Cierre de sesión por inactividad: middleware que cierra la sesión si pasan más minutos que minutos_inactividad desde la última petición, y muestra el mensaje "Tu sesión se cerró por inactividad".
   - Si un usuario es desactivado mientras tiene sesión abierta, en su siguiente petición se cierra la sesión.

8. Permisos:
   - Define Gates en AppServiceProvider según esta matriz (se irán usando en las siguientes tareas):
     gestionar-usuarios: admin
     gestionar-configuracion: admin
     ver-auditoria: admin
     gestionar-categorias: admin, encargado
     gestionar-productos: admin, encargado
     ver-productos: admin, encargado, cajero
     gestionar-stock: admin, encargado
     registrar-entradas: admin, encargado
     realizar-ventas: admin, encargado, cajero
     ver-todas-las-ventas: admin, encargado (el cajero solo ve sus propias ventas del día)
     anular-ventas: admin, encargado
     aplicar-descuentos: admin, encargado
     ver-reportes: admin, encargado
   - Middleware 'rol' reutilizable: ->middleware('rol:admin,encargado').
   - Documenta la matriz en docs/usuarios-y-permisos.md.

9. Layout:
   - Barra de navegación superior con el nombre del negocio (desde configuración), menú según permisos (usa @can; por ahora los enlaces de módulos futuros pueden apuntar a '#' pero deben estar ocultos según el rol), y a la derecha el nombre del usuario, su rol, enlace a "Cambiar contraseña" y botón "Salir" (form POST con @csrf).
   - Zona de mensajes flash (success, error, warning) con alertas Bootstrap que se pueden cerrar.
   - Página de inicio / (requiere login) que por ahora muestre "Bienvenido, {nombre}" y el rol.
   - Páginas de error propias en español para 403 ("No tienes permiso para acceder a esta sección"), 404 y 419 ("La sesión expiró, vuelve a intentarlo").

10. Seeder:
   - UsuarioAdminSeeder: crea el usuario "admin" (nombre "Administrador", rol admin, debe_cambiar_password = true) con la contraseña tomada de la variable ADMIN_PASSWORD_INICIAL del .env. Si no existe la variable, detente con un error claro. Agrega ADMIN_PASSWORD_INICIAL= vacío a .env.example y ADMIN_PASSWORD_INICIAL=Admin12345 a mi .env local.
   - UsuariosDemoSeeder SOLO para entorno local (app()->environment('local')): "encargado" y "cajero1" con contraseña "demo12345" y debe_cambiar_password = false.
   - DatabaseSeeder llama a ConfiguracionSeeder, UsuarioAdminSeeder y (solo en local) UsuariosDemoSeeder.
   - UserFactory actualizada a los nuevos campos.

## Tests (Feature)
- Login correcto redirige a inicio y registra LOGIN en auditoria.
- Login con contraseña incorrecta falla y registra LOGIN_FALLIDO.
- Usuario inactivo no puede iniciar sesión.
- Después de 5 intentos fallidos, el sexto se bloquea.
- Usuario con debe_cambiar_password es redirigido a /cambiar-password.
- Cambiar contraseña funciona y exige la contraseña actual correcta.
- Rutas protegidas redirigen a /login sin sesión.
- Un cajero recibe 403 en una ruta protegida con rol:admin (crea una ruta de prueba solo en tests o usa una ruta real protegida).
- Logout cierra la sesión.
- La tabla auditoria nunca guarda la contraseña.

## Entrega
Ejecuta php artisan migrate:fresh --seed y php artisan test. No hagas commit. Dime cómo probar.
```

**Cómo probar:**
1. `php artisan migrate:fresh --seed` y luego `php artisan serve`.
2. Entra a http://127.0.0.1:8000: debe mandarte al login.
3. Entra con `admin` / `Admin12345`: debe obligarte a cambiar la contraseña. Cámbiala.
4. Sal y entra con `cajero1` / `demo12345`: el menú debe mostrar menos opciones.
5. Intenta 6 veces con contraseña incorrecta: debe bloquearte un minuto.
6. En pgAdmin, revisa la tabla `auditoria` de `libreria_dev`: deben aparecer los LOGIN, LOGIN_FALLIDO y LOGOUT.
7. `php artisan test`: todo en verde.

**Commit:**
```text
Funciona. Haz commit con "feat: login, roles, permisos y auditoría base" y push.
```

---

## PROMPT 3 — Administración de usuarios y configuración

```text
Tarea: PROMPT 3 — pantallas de administración de usuarios, configuración y consulta de auditoría. Lee AGENTS.md. Usa los Gates gestionar-usuarios, gestionar-configuracion y ver-auditoria definidos en la tarea anterior.

## Usuarios (solo admin)
1. Listado /usuarios: tabla con nombre, usuario, rol (etiqueta), estado (badge Activo/Inactivo), último acceso (formato dd/mm/aaaa hh:mm). Búsqueda por nombre o usuario y filtro por rol y estado. Paginación de 20.
2. Crear usuario: nombre, usuario (solo letras minúsculas, números, punto y guion bajo; único), rol, contraseña inicial y confirmación (mínimo 8). Se crea con debe_cambiar_password = true. Auditar CREAR (sin contraseña).
3. Editar usuario: nombre y rol (el usuario no se puede cambiar). Auditar EDITAR con datos anteriores y nuevos.
4. Restablecer contraseña: el admin asigna una contraseña temporal; se marca debe_cambiar_password = true. Auditar CAMBIO_PASSWORD indicando que fue restablecida por el admin.
5. Activar / desactivar (con confirmación). Auditar DESACTIVAR / ACTIVAR.
6. Reglas de seguridad:
   - Un admin no puede desactivarse a sí mismo ni quitarse el rol admin.
   - No se puede desactivar ni quitar el rol al último admin activo del sistema.
   - No existe eliminar usuario.
   - Usa Form Requests para validar, con mensajes en español.

## Configuración (solo admin)
7. Pantalla /configuracion con formulario para: nombre_negocio, direccion, telefono, mensaje_ticket, permitir_stock_negativo (switch), minutos_inactividad (número entre 5 y 480). Guardar con ConfiguracionService y auditar CONFIGURACION con valores anteriores y nuevos.

## Auditoría (solo admin)
8. Pantalla /auditoria de solo lectura: tabla con fecha y hora, usuario, acción, entidad, descripción e IP. Filtros por rango de fechas, usuario y acción. Paginación de 50, más reciente primero. Botón "Ver detalle" que muestre datos anteriores y nuevos en formato legible (tabla clave/valor, no JSON crudo).

## Menú
9. Agrega al menú (solo admin) un desplegable "Administración" con: Usuarios, Configuración, Auditoría.

## Tests (Feature)
- Admin puede crear, editar, desactivar y reactivar usuarios; cada acción queda en auditoría.
- Encargado y cajero reciben 403 en /usuarios, /configuracion y /auditoria.
- No se puede crear usuario con nombre de usuario repetido.
- Admin no puede desactivarse a sí mismo.
- No se puede desactivar al último admin activo.
- Restablecer contraseña deja debe_cambiar_password = true.
- Guardar configuración cambia los valores y audita.

## Entrega
Ejecuta php artisan test. No hagas commit. Dime cómo probar.
```

**Cómo probar:** entra como admin y crea un usuario cajero nuevo. Entra con él: debe pedir cambio de contraseña. Como admin, desactívalo y verifica que ya no pueda entrar. Cambia el nombre del negocio en Configuración y verifica que cambie en la barra superior. Revisa que todo aparezca en Auditoría. Como cajero, intenta abrir http://127.0.0.1:8000/usuarios: debe salir el error 403.

**Commit:**
```text
Funciona. Haz commit con "feat: administración de usuarios, configuración y auditoría" y push.
```

---

## PROMPT 4 — Categorías

```text
Tarea: PROMPT 4 — módulo de categorías. Lee AGENTS.md. Gate: gestionar-categorias (admin, encargado).

## Base de datos
Tabla categorias:
- id
- nombre string(100) unique
- descripcion string(255) nullable
- activo boolean default true
- timestampsTz

## Funcionalidad
1. Listado /categorias: nombre, descripción, cantidad de productos (por ahora 0 si la tabla productos no existe; deja preparado withCount para cuando exista), estado. Búsqueda por nombre, filtro por estado.
2. Crear y editar (nombre obligatorio, único sin distinguir mayúsculas/minúsculas, máximo 100).
3. Activar / desactivar con confirmación. No hay eliminar.
4. Auditar CREAR, EDITAR, DESACTIVAR y ACTIVAR.
5. Agrega "Categorías" al menú dentro de un desplegable "Inventario" (visible para admin y encargado).
6. Seeder CategoriasSeeder con: Cuadernos, Lapiceros, Lápices, Material escolar, Material de oficina, Libros, Carpetas, Hojas y papel, Accesorios, Fotocopias e impresiones, Otros. Agrégalo al DatabaseSeeder (en todos los entornos).
7. CategoriaFactory.

## Tests
- Admin y encargado pueden crear, editar y desactivar categorías.
- Cajero recibe 403.
- No se permiten nombres duplicados (ni "Cuadernos" vs "cuadernos").
- Cada acción queda auditada.

## Entrega
Ejecuta php artisan migrate y php artisan test. No hagas commit. Dime cómo probar.
```

**Cómo probar:** `php artisan migrate:fresh --seed`. Como encargado, entra a Inventario → Categorías, crea una categoría, edítala y desactívala. Intenta crear "cuadernos" (en minúscula): debe rechazarla.

**Commit:**
```text
Funciona. Haz commit con "feat: módulo de categorías" y push.
```

---

## PROMPT 5 — Productos ⚠️

```text
Tarea: PROMPT 5 — módulo de productos. Lee AGENTS.md. Gates: gestionar-productos (admin, encargado) y ver-productos (todos).
IMPORTANTE: en esta tarea el stock NO se puede editar desde el formulario de producto. Solo se crea la columna. El stock inicial y los movimientos se implementan en el PROMPT 6.

## Base de datos
Tabla productos:
- id
- codigo string(30) unique (se guarda en MAYÚSCULAS, sin espacios al inicio/fin)
- nombre string(150)
- descripcion text nullable
- categoria_id foreignId (categorias, restrictOnDelete)
- marca string(100) nullable (marca o editorial)
- unidad string(20) default 'unidad' (unidad, paquete, caja, resma, hoja, docena)
- precio_compra decimal(12,2) default 0
- precio_venta decimal(12,2)
- stock integer default 0
- stock_minimo integer default 0
- controla_stock boolean default true (false para servicios como fotocopias o impresiones, que no tienen stock)
- activo boolean default true
- timestampsTz
- Índices: nombre (para búsquedas), categoria_id, activo.
- Check constraints: precio_venta >= 0, precio_compra >= 0, stock_minimo >= 0.
- Crea también un índice para búsquedas sin distinguir mayúsculas: índice sobre lower(nombre) (con DB::statement si hace falta).

## Funcionalidad
1. Listado /productos (todos los roles pueden verlo; los cajeros solo en modo consulta, sin botones de edición y sin ver precio_compra):
   - Columnas: código, nombre, categoría, marca, precio venta, stock (con badge rojo si stock <= stock_minimo y controla_stock), estado.
   - Búsqueda por código o nombre (sin distinguir mayúsculas; usa ILIKE), filtros por categoría, estado y "solo stock bajo".
   - Paginación de 25. Ordenar por nombre.
   - Precio con el helper bs().
2. Crear producto (admin, encargado): código, nombre, descripción, categoría (solo categorías activas), marca, unidad, precio compra, precio venta, stock mínimo, controla stock.
   - Botón "Sugerir código" que proponga el siguiente código libre usando las 3 primeras letras de la categoría sin tildes (ej: CUA-001, CUA-002). Puede ser una ruta JSON consultada con fetch.
   - Validar: precio_venta >= 0; si precio_venta < precio_compra mostrar ADVERTENCIA (no error) pidiendo confirmar.
3. Editar producto: mismos campos (código editable pero único). Si cambia precio_venta o precio_compra, auditar además CAMBIO_PRECIO con precio anterior y nuevo.
4. Detalle de producto /productos/{id}: todos los datos, y un espacio reservado "Movimientos de stock" (se llena en el PROMPT 6).
5. Activar / desactivar con confirmación. No hay eliminar. Productos inactivos no aparecerán en ventas (se usará en el PROMPT 9).
6. Auditar CREAR, EDITAR, CAMBIO_PRECIO, DESACTIVAR, ACTIVAR.
7. Agrega "Productos" al desplegable "Inventario" (visible para todos los roles; los cajeros ven solo consulta).
8. En el listado de categorías, mostrar ahora la cantidad real de productos.
9. ProductoFactory y ProductosDemoSeeder SOLO en local: 30 productos de librería realistas repartidos en las categorías (con precios en Bs. razonables, ej: cuaderno 100 hojas Bs. 12,00, lapicero azul Bs. 2,50, resma carta Bs. 38,00, fotocopia B/N Bs. 0,30 con controla_stock=false). Deja stock en 0 (se cargará con el PROMPT 6).

## Tests
- Admin y encargado pueden crear y editar productos; cajero recibe 403 al crear/editar pero puede ver el listado.
- El cajero no ve precio_compra en el listado ni en el detalle.
- Código duplicado es rechazado (sin distinguir mayúsculas) y se guarda en mayúsculas.
- Cambio de precio queda en auditoría como CAMBIO_PRECIO con valores anterior y nuevo.
- El formulario de edición NO modifica el stock aunque se envíe un campo stock manipulado.
- La búsqueda encuentra por parte del nombre sin importar mayúsculas.
- Precio se guarda con 2 decimales exactos (ej: 12.50).

## Entrega
Ejecuta migrate y test. No hagas commit. Dime cómo probar.
```

**Cómo probar:** `php artisan migrate:fresh --seed`. Como encargado, crea un producto usando "Sugerir código", edítale el precio y revisa que aparezca CAMBIO_PRECIO en auditoría. Busca "cuad" y verifica que encuentre los cuadernos. Como cajero, entra a Productos: no debe ver el precio de compra ni los botones de edición.

**Commit:**
```text
Funciona. Haz commit con "feat: módulo de productos" y push.
```

---

## PROMPT 6 — Servicio de stock, ajustes y kardex ⚠️

```text
Tarea: PROMPT 6 — núcleo de inventario: StockService, movimientos, stock inicial, ajustes y kardex. Lee AGENTS.md. Esta es una de las partes más críticas del sistema: prioriza la corrección y los tests.

## Base de datos
Tabla movimientos_stock:
- id
- producto_id foreignId (productos, restrictOnDelete)
- tipo string(30): INICIAL, ENTRADA, ANULACION_ENTRADA, VENTA, ANULACION_VENTA, AJUSTE_POSITIVO, AJUSTE_NEGATIVO
- cantidad integer (con signo: positivo suma, negativo resta; nunca 0)
- stock_anterior integer
- stock_nuevo integer
- user_id foreignId (users)
- referencia_tipo string(30) nullable (ej: 'venta', 'entrada')
- referencia_id unsignedBigInteger nullable
- motivo text nullable
- created_at timestampTz (sin updated_at)
- Índices: (producto_id, created_at), (referencia_tipo, referencia_id).
- Check: stock_nuevo = stock_anterior + cantidad.

## App\Services\StockService
1. Método principal:
   mover(int $productoId, int $cantidad, string $tipo, ?string $motivo = null, ?Model $referencia = null, bool $permitirNegativo = null): MovimientoStock
   - DEBE ejecutarse dentro de una transacción: si no hay transacción activa (DB::transactionLevel() == 0), lanzar una excepción LogicException.
   - Bloquea el producto con lockForUpdate() y lee su stock actual DENTRO del bloqueo.
   - Si el producto tiene controla_stock = false, no mueve stock ni crea movimiento: retorna null (ajusta el tipo de retorno a ?MovimientoStock).
   - Calcula stock_nuevo. Si queda negativo y la configuración permitir_stock_negativo es "0" (o $permitirNegativo es false), lanza App\Exceptions\StockInsuficienteException con un mensaje claro: "Stock insuficiente para {nombre}: disponible X, solicitado Y".
   - Actualiza productos.stock y crea el movimiento con el usuario autenticado.
   - cantidad 0 lanza InvalidArgumentException.
2. Método bloquearProductos(array $ids): Collection — bloquea varios productos ordenados por id ascendente (para evitar deadlocks cuando dos cajas venden a la vez). Lo usarán ventas y entradas.
3. Método ajustar(Producto $producto, int $stockReal, string $motivo): calcula la diferencia contra el stock actual y crea AJUSTE_POSITIVO o AJUSTE_NEGATIVO; si la diferencia es 0, no hace nada. Todo en transacción. Audita AJUSTE_STOCK con stock anterior y nuevo y el motivo.
4. Método verificarConsistencia(): devuelve los productos cuyo stock no coincide con la suma de sus movimientos. Crea un comando php artisan stock:verificar que lo muestre en tabla (y termine con código de error si hay diferencias).

## Pantallas
5. Stock inicial: al CREAR un producto (formulario del PROMPT 5), agrega el campo opcional "Stock inicial" (entero >= 0, solo si controla_stock). Si es mayor a 0, dentro de la misma transacción de creación se registra un movimiento INICIAL.
6. Ajuste de stock (gestionar-stock: admin, encargado): desde el detalle del producto, botón "Ajustar stock" que abre un formulario con: stock actual (solo lectura), stock real contado (entero), motivo obligatorio (mínimo 5 caracteres; con sugerencias: Conteo físico, Producto dañado, Pérdida, Error de registro, Otro).
7. Kardex: en el detalle del producto, tabla de movimientos (más reciente primero, paginada de 20): fecha y hora, tipo (con badge de color: verde suma, rojo resta), cantidad, stock anterior, stock nuevo, usuario, referencia (si es venta o entrada, que muestre "Venta #000123"; el enlace se activará en tareas posteriores) y motivo.
8. Pantalla /inventario/stock-bajo: productos activos con controla_stock y stock <= stock_minimo, ordenados por la diferencia. Agrega el enlace al menú Inventario.
9. Ajuste masivo no es necesario en esta tarea.

## Seeder
10. En ProductosDemoSeeder (solo local), cargar stock inicial aleatorio (0 a 80) a los productos con controla_stock usando StockService (movimientos INICIAL con el usuario admin), dentro de transacción.

## Tests (Unit/Feature) — obligatorios
- mover() fuera de transacción lanza LogicException.
- Entrada +10 sobre stock 5 deja 15 y crea movimiento con stock_anterior 5 y stock_nuevo 15.
- Salida que deja stock negativo: con permitir_stock_negativo="1" se permite; con "0" lanza StockInsuficienteException y NO cambia nada (ni stock ni movimientos).
- Producto con controla_stock=false no cambia stock ni crea movimientos.
- ajustar() a stock real 12 con stock actual 20 crea AJUSTE_NEGATIVO de -8 y queda auditado.
- ajustar() con la misma cantidad no crea movimiento.
- Crear producto con stock inicial 25 crea un movimiento INICIAL y deja stock 25.
- Cajero recibe 403 al ajustar stock.
- stock:verificar no reporta diferencias después de varias operaciones, y sí detecta una diferencia si se modifica productos.stock directamente en el test.
- El formulario de edición de producto sigue sin poder modificar el stock.

## Entrega
Ejecuta migrate:fresh --seed, php artisan stock:verificar y php artisan test. No hagas commit. Dime cómo probar.
```

**Cómo probar:**
1. `php artisan migrate:fresh --seed`.
2. Crea un producto con stock inicial 20 y mira su detalle: debe tener un movimiento INICIAL.
3. Ajusta su stock a 17 con motivo "Conteo físico": debe verse AJUSTE_NEGATIVO de -3 en el kardex.
4. Ponle stock mínimo 18: debe aparecer en "Stock bajo".
5. `php artisan stock:verificar`: sin diferencias.
6. **Antes del commit, pega `git diff` en el chat de Claude para revisarlo** (es una parte crítica).

**Commit:**
```text
Funciona. Haz commit con "feat: servicio de stock, ajustes y kardex" y push.
```

---

## PROMPT 7 — Entradas de mercadería ⚠️

```text
Tarea: PROMPT 7 — registro de entradas de mercadería. Lee AGENTS.md. Gate: registrar-entradas (admin, encargado). Usa StockService (PROMPT 6) para todo cambio de stock.

## Base de datos
Tabla entradas_stock:
- id
- fecha timestampTz (fecha de registro)
- proveedor string(150) nullable (texto libre; el módulo de proveedores vendrá después)
- documento_referencia string(50) nullable (número de nota o factura del proveedor)
- observaciones text nullable
- total decimal(12,2)
- estado string(20): REGISTRADA, ANULADA
- user_id foreignId (users)
- anulada_por foreignId nullable (users)
- anulada_en timestampTz nullable
- motivo_anulacion text nullable
- timestampsTz

Tabla detalle_entradas:
- id
- entrada_id foreignId (entradas_stock, cascadeOnDelete)
- producto_id foreignId (productos)
- cantidad integer (> 0)
- costo_unitario decimal(12,2) (>= 0)
- subtotal decimal(12,2)
- Índice en producto_id.

## App\Services\EntradaService
1. registrar(array $datos, array $items): EntradaStock
   - $items: lista de [producto_id, cantidad, costo_unitario]. Si un producto se repite, sumar cantidades en una sola línea (con el último costo).
   - Todo en DB::transaction: bloquea productos con StockService::bloquearProductos (ids ordenados), valida que existan, estén activos y controlen stock; crea la entrada y el detalle; calcula subtotales y total en el servidor; por cada ítem llama a StockService::mover(..., 'ENTRADA', referencia: la entrada).
   - Opción actualizar_precio_compra (por defecto true): actualiza productos.precio_compra con el costo_unitario y audita CAMBIO_PRECIO si cambió.
   - Audita CREAR entrada.
2. anular(EntradaStock $entrada, string $motivo): solo si está REGISTRADA; en transacción revierte el stock con movimientos ANULACION_ENTRADA (esto puede dejar stock negativo si ya se vendió; aplica la regla de configuración y si no se permite, informa qué productos lo impiden). Marca ANULADA con usuario, fecha y motivo. Audita ANULAR. No revierte precio_compra.

## Pantallas
3. Listado /entradas: número (#000001), fecha, proveedor, documento, cantidad de ítems, total, usuario, estado. Filtros por fechas, proveedor y estado.
4. Nueva entrada /entradas/crear:
   - Encabezado: proveedor, documento de referencia, observaciones, checkbox "Actualizar precio de compra de los productos" (marcado).
   - Buscador de productos por código o nombre (fetch a una ruta JSON /api-interna/productos/buscar?q=, solo productos activos con controla_stock; devuelve id, codigo, nombre, stock, precio_compra). Esta ruta requiere login y el permiso adecuado; será reutilizada en ventas.
   - Tabla de ítems editable en JavaScript vanilla (public/js/entradas.js): producto, stock actual, cantidad, costo unitario (precargado con precio_compra), subtotal, botón quitar. Total general en vivo.
   - Botón "Registrar entrada" con confirmación; se desactiva al enviar para evitar doble registro.
   - Validación en servidor con Form Request (al menos 1 ítem, cantidades enteras > 0, costos >= 0).
5. Detalle /entradas/{id}: encabezado, ítems, total, estado y, si está anulada, quién, cuándo y por qué. Botón "Anular" (admin y encargado) con motivo obligatorio.
6. En el kardex del producto, la referencia "Entrada #000001" ahora enlaza al detalle.
7. Menú Inventario: agregar "Entradas de mercadería".

## Tests
- Registrar entrada con 2 productos suma stock correctamente y crea movimientos ENTRADA con referencia a la entrada.
- El total se calcula en el servidor (enviar un total falso no tiene efecto).
- Productos repetidos se agrupan en una sola línea.
- Actualiza precio_compra cuando la opción está activa y no cuando está desactivada.
- Si un ítem es inválido (producto inactivo), no se guarda NADA (ni entrada ni stock).
- Anular revierte el stock con ANULACION_ENTRADA y no se puede anular dos veces.
- Cajero recibe 403.

## Entrega
Ejecuta migrate, test y stock:verificar. No hagas commit. Dime cómo probar.
```

**Cómo probar:** registra una entrada de 3 productos y comprueba el stock y el kardex de cada uno. Anúlala y verifica que el stock vuelva al valor anterior. Haz doble clic rápido en "Registrar": solo debe crearse una entrada. Ejecuta `php artisan stock:verificar`. **Pega el `git diff` en el chat de Claude antes del commit.**

**Commit:**
```text
Funciona. Haz commit con "feat: entradas de mercadería" y push.
```

---

## PROMPT 8 — Importación de productos desde CSV

```text
Tarea: PROMPT 8 — importar productos desde un archivo CSV (exportado desde Excel) para la carga inicial. Lee AGENTS.md. Solo admin y encargado (gestionar-productos). No instales paquetes pesados; usa las funciones nativas de PHP para CSV.

1. Plantilla descargable /productos/importar/plantilla (CSV UTF-8 con BOM para que Excel muestre bien las tildes, separador ;) con encabezados:
   codigo;nombre;categoria;marca;unidad;precio_compra;precio_venta;stock_inicial;stock_minimo;controla_stock
   y 2 filas de ejemplo.
2. Pantalla /productos/importar:
   - Subir archivo .csv (máximo 2 MB).
   - Detectar separador automáticamente (; o ,) y codificación (convertir de Windows-1252 a UTF-8 si hace falta).
   - Aceptar precios con coma o punto decimal (12,50 o 12.50).
   - controla_stock: acepta si/no, 1/0, vacío = si.
   - Opción: "Si el código ya existe": Omitir (por defecto) o Actualizar datos (nombre, categoría, marca, precios, stock mínimo; NUNCA el stock).
   - Opción: "Crear categorías que no existan" (marcada).
3. Paso 1 — Vista previa (sin guardar nada): tabla con cada fila y su resultado: Nuevo, Actualizar, Omitir o Error (con el motivo: falta nombre, precio inválido, categoría inexistente, código duplicado dentro del archivo, etc.). Resumen con totales. Guarda el archivo procesado temporalmente en storage (no en public) con un identificador.
4. Paso 2 — Confirmar importación: procesa todo en UNA transacción; si algo falla, no se guarda nada. El stock_inicial > 0 de productos nuevos se registra con StockService como movimiento INICIAL. Audita un único registro CREAR con entidad "importacion" y el resumen (cantidad de nuevos, actualizados, omitidos).
5. Mostrar resultado final y enlace a Productos. Borrar el archivo temporal.
6. Agregar botón "Importar desde Excel/CSV" en el listado de productos (admin/encargado).
7. Documenta en docs/importacion-productos.md cómo preparar el archivo en Excel y guardarlo como "CSV UTF-8 (delimitado por comas)".

## Tests
- Importa un CSV con separador ; y otro con , correctamente.
- Precios con coma decimal se interpretan bien.
- Filas con errores se reportan y, si se confirma, solo se importan las válidas (las con error se omiten), todo dentro de una transacción.
- Código existente con opción Omitir no cambia el producto; con Actualizar cambia datos pero NO el stock.
- stock_inicial genera movimiento INICIAL.
- Cajero recibe 403.

## Entrega
Ejecuta test. No hagas commit. Dime cómo probar.
```

**Cómo probar:** descarga la plantilla, ábrela en Excel, agrega unas 10 filas (incluye una con error a propósito), guárdala como CSV e impórtala. Revisa la vista previa, confirma y verifica los productos y su stock.

**Commit:**
```text
Funciona. Haz commit con "feat: importación de productos desde CSV" y push.
```

---

## PROMPT 9 — Ventas: lógica y tests ⚠️⚠️

```text
Tarea: PROMPT 9 — lógica de ventas (SIN pantallas todavía; la pantalla de caja es el PROMPT 10). Lee AGENTS.md. Esta es la parte MÁS CRÍTICA del sistema. Usa StockService. Escribe primero los tests y luego la implementación.

## Base de datos
Tabla ventas:
- id (el número de venta visible será el id con 6 dígitos: #000152)
- token uuid unique (generado por el formulario para evitar ventas duplicadas por doble clic o recarga)
- fecha timestampTz
- user_id foreignId (users) — cajero que vendió
- cliente_nombre string(150) nullable
- subtotal decimal(12,2)
- descuento decimal(12,2) default 0
- total decimal(12,2)
- metodo_pago string(20): EFECTIVO, QR, TRANSFERENCIA, TARJETA, OTRO
- monto_recibido decimal(12,2) nullable (solo efectivo)
- cambio decimal(12,2) nullable (solo efectivo)
- estado string(20): COMPLETADA, ANULADA
- observaciones text nullable
- anulada_por foreignId nullable (users)
- anulada_en timestampTz nullable
- motivo_anulacion text nullable
- timestampsTz
- Índices: fecha, (user_id, fecha), estado.
- Checks: total >= 0, descuento >= 0, descuento <= subtotal.

Tabla detalle_ventas:
- id
- venta_id foreignId (ventas, restrictOnDelete)
- producto_id foreignId (productos)
- codigo_producto string(30) (copia al momento de la venta)
- nombre_producto string(150) (copia)
- cantidad integer (> 0)
- precio_unitario decimal(12,2) (copia del precio_venta al momento de la venta)
- subtotal decimal(12,2)
- Índice en producto_id.

Enum App\Enums\MetodoPago con etiqueta(): Efectivo, QR, Transferencia, Tarjeta, Otro.

## App\Services\VentaService
1. registrar(array $items, array $datos, User $usuario): Venta
   - $items: [producto_id, cantidad]. Los precios NUNCA vienen del navegador: se leen de la base de datos dentro de la transacción.
   - $datos: token, metodo_pago, descuento (opcional), monto_recibido (opcional), cliente_nombre (opcional), observaciones (opcional).
   - Si ya existe una venta con ese token, devolver esa venta sin crear otra (idempotencia).
   - Agrupar productos repetidos sumando cantidades.
   - DB::transaction:
     a) StockService::bloquearProductos(ids ordenados).
     b) Validar que cada producto exista y esté activo; si no, lanzar excepción con mensaje claro.
     c) Crear detalle con copia de codigo, nombre y precio_venta actuales; subtotal = cantidad × precio.
     d) subtotal de la venta = suma de líneas. descuento: si es > 0, el usuario debe tener el permiso aplicar-descuentos (si no, excepción); no puede superar el subtotal.
     e) total = subtotal − descuento.
     f) Si metodo_pago = EFECTIVO y se envía monto_recibido: debe ser >= total; cambio = monto_recibido − total. Para otros métodos, monto_recibido y cambio quedan null.
     g) Por cada ítem: StockService::mover(producto_id, -cantidad, 'VENTA', referencia: venta). Respeta permitir_stock_negativo.
     h) Auditoría: NO auditar cada venta normal (ya queda registrada en la tabla ventas); SÍ auditar si tuvo descuento (acción CREAR, entidad venta, con el descuento).
   - Todos los cálculos con precisión decimal: usa bcmath (bcadd, bcmul, bcsub con escala 2) o trabaja en centavos enteros. Nunca float.
2. anular(Venta $venta, string $motivo, User $usuario): Venta
   - Requiere permiso anular-ventas y motivo de mínimo 5 caracteres.
   - Solo ventas COMPLETADA; una venta ANULADA no se puede anular de nuevo.
   - DB::transaction: bloquear la venta (lockForUpdate) y sus productos; por cada línea StockService::mover(producto_id, +cantidad, 'ANULACION_VENTA', referencia: venta, permitirNegativo: true). Marcar ANULADA con anulada_por, anulada_en, motivo.
   - Auditar ANULAR con los datos de la venta (número, total, ítems) y el motivo.
3. Método auxiliar calcularTotales(array $items, $descuento) para que la pantalla muestre una vista previa (usa los mismos cálculos).

## Tests (Feature/Unit) — obligatorios, todos deben pasar
- Venta de 2 productos: descuenta stock, crea movimientos VENTA con referencia, subtotal/total correctos.
- El precio usado es el de la BD aunque el request intente enviar otro precio.
- El detalle guarda nombre y precio del momento: si después cambia el precio del producto, la venta antigua no cambia.
- Producto repetido en el carrito se agrupa.
- Mismo token dos veces crea UNA sola venta y descuenta stock una sola vez.
- Producto inactivo: la venta falla y no se guarda nada (ni venta, ni detalle, ni stock).
- Stock insuficiente con permitir_stock_negativo="0": falla sin guardar nada. Con "1": se permite.
- Producto con controla_stock=false (fotocopia) se vende sin mover stock.
- Descuento por cajero es rechazado; por encargado se acepta; descuento mayor al subtotal rechazado.
- Efectivo: monto_recibido menor al total rechazado; cambio correcto (ej: total 37,50, recibido 50,00, cambio 12,50).
- Precisión: 3 × 0,30 = 0,90 exacto; 7 × 12,35 = 86,45 exacto.
- Anulación devuelve el stock con ANULACION_VENTA, queda auditada y no se puede anular dos veces.
- Cajero no puede anular (403 / excepción de autorización).
- Concurrencia (simulada): dos ventas del último ítem con stock negativo desactivado → la segunda falla. (Si no puedes simular concurrencia real en PHPUnit, prueba la secuencia y documenta en el test que el bloqueo lo garantiza lockForUpdate.)
- stock:verificar sin diferencias después de ventas y anulaciones.

## Entrega
Ejecuta migrate y test y muéstrame el resumen de tests. No hagas commit. No crees pantallas.
```

**Cómo probar:** `php artisan test` debe salir todo en verde. Revisa que existan los tests de la lista (pídele a OpenCode "muéstrame la lista de tests de ventas"). **Esta tarea sí o sí pásala por revisión: pega el `git diff` en el chat de Claude.**

**Commit:**
```text
Funciona. Haz commit con "feat: lógica de ventas y anulaciones con tests" y push.
```

---

## PROMPT 10 — Ventas: pantalla de caja y ticket ⚠️

```text
Tarea: PROMPT 10 — pantalla de venta (punto de venta) y ticket imprimible. Lee AGENTS.md. Usa VentaService del PROMPT 9; el controlador NO calcula nada por su cuenta. Gate: realizar-ventas.

## Pantalla /ventas/nueva
Diseño pensado para usar rápido con teclado, en una PC de caja (resolución 1366x768 mínimo). JavaScript vanilla en public/js/ventas.js.
1. Columna izquierda (≈65%):
   - Buscador grande con foco automático siempre que se agrega un producto. Busca por código o nombre (fetch a la ruta de búsqueda existente, ampliada para incluir productos sin control de stock y devolver precio_venta; con debounce de 250 ms). Resultados en lista navegable con flechas ↑ ↓ y Enter para agregar. Si el texto coincide exactamente con un código, Enter agrega directamente.
   - Muestra en cada resultado: código, nombre, precio y stock (en rojo si es 0 o menor; "—" si no controla stock).
   - Tabla del carrito: producto, precio unitario, cantidad (input editable, botones + y −), subtotal, botón quitar. Si se agrega un producto ya presente, suma 1 a la cantidad.
   - Advertencia visible (no bloqueante si la configuración lo permite) cuando la cantidad supera el stock.
2. Columna derecha (≈35%):
   - Subtotal, descuento (campo visible solo para quienes tienen aplicar-descuentos), TOTAL grande.
   - Método de pago: botones grandes (Efectivo, QR, Transferencia, Tarjeta, Otro); Efectivo por defecto.
   - Si es efectivo: campo "Recibido" y "Cambio" calculado en vivo; botones rápidos de billetes (10, 20, 50, 100, 200) y "Exacto".
   - Cliente (opcional), observaciones (opcional).
   - Botón "COBRAR (F9)" grande. Botón "Cancelar venta (Esc)" con confirmación que vacía el carrito.
3. Atajos: F2 enfoca el buscador, F9 cobra, Esc cancela (con confirmación).
4. Los totales mostrados en pantalla son solo informativos; el servidor recalcula todo.
5. Al cargar la pantalla se genera un token UUID (en el servidor, dentro del formulario). Al cobrar: se desactiva el botón, se envía por POST (fetch con CSRF o formulario normal), y si hay error se muestra el mensaje del servidor y se reactiva el botón conservando el carrito.
6. Si el carrito se pierde por recargar la página no hay problema, pero advierte con beforeunload si hay productos en el carrito.
7. Al terminar la venta: redirigir a /ventas/{id}/ticket con un mensaje de éxito y el cambio a entregar en grande.

## Ticket /ventas/{id}/ticket
8. Vista para impresora térmica de 80 mm (y que también se vea bien en 58 mm): CSS @media print con @page { size: 80mm auto; margin: 0 }, fuente monoespaciada, sin menú.
   Contenido: nombre del negocio, dirección y teléfono (de configuración), "VENTA #000152", fecha y hora, cajero, cliente si hay, líneas (cantidad × nombre, precio unitario, subtotal), subtotal, descuento si hay, TOTAL, método de pago, recibido y cambio si es efectivo, mensaje_ticket, y la leyenda "Documento sin valor fiscal". Si la venta está ANULADA, mostrar "*** ANULADA ***" en grande.
9. Botones en pantalla (no se imprimen): "Imprimir" (window.print()), "Nueva venta" (con foco por defecto, para que Enter inicie la siguiente venta).
10. Opción en configuración: imprimir_automatico (switch, por defecto desactivado): si está activo, el ticket llama a window.print() al cargar.
11. Permisos del ticket: el cajero solo puede ver tickets de sus propias ventas; admin/encargado todas.
12. Menú: botón destacado "Vender" en la barra superior para todos los roles con realizar-ventas.
13. Documenta en docs/impresion-tickets.md cómo configurar la impresora térmica en Windows y el navegador (márgenes ninguno, sin encabezados/pies, escala 100%, y cómo usar el modo quiosco de Chrome/Edge con --kiosk-printing para imprimir sin diálogo).

## Tests
- GET /ventas/nueva responde para cajero, encargado y admin.
- POST de venta crea la venta usando VentaService (prueba integración completa por HTTP).
- Un cajero que envía descuento recibe error.
- Reenviar el mismo formulario (mismo token) no duplica la venta.
- Cajero no puede ver el ticket de una venta de otro usuario (403); encargado sí.
- El ticket muestra "Documento sin valor fiscal" y, si está anulada, "ANULADA".

## Entrega
Ejecuta test. No hagas commit. Dime cómo probar, incluyendo los atajos de teclado.
```

**Cómo probar:**
1. Como cajero: haz una venta de 3 productos solo con el teclado (F2, escribir, flechas, Enter, F9).
2. Paga en efectivo con 100 y revisa el cambio.
3. Imprime el ticket (o "Microsoft Print to PDF" si no tienes impresora térmica).
4. Revisa el stock y el kardex de los productos vendidos.
5. Intenta cobrar dos veces rápido: debe registrarse una sola venta.
6. Abre dos navegadores (por ejemplo Chrome y Edge) con dos usuarios y vende el mismo producto a la vez.

**Commit:**
```text
Funciona. Haz commit con "feat: pantalla de venta y ticket" y push.
```

---

## PROMPT 11 — Historial y anulación de ventas

```text
Tarea: PROMPT 11 — historial de ventas, detalle y anulación desde la interfaz. Lee AGENTS.md. Usa VentaService::anular. Gates: ver-todas-las-ventas, anular-ventas.

1. Listado /ventas:
   - Admin/encargado: todas las ventas. Cajero: solo sus propias ventas del día actual (forzado en el servidor, no solo en filtros).
   - Columnas: número (#000152), fecha y hora, cajero, cliente, cantidad de ítems, método de pago, total, estado (badge verde COMPLETADA, rojo ANULADA).
   - Filtros (admin/encargado): rango de fechas (por defecto hoy), cajero, método de pago, estado, número de venta.
   - Resumen arriba de la tabla según los filtros: cantidad de ventas completadas, total vendido (solo COMPLETADA), cantidad de anuladas.
   - Paginación de 30, más recientes primero.
2. Detalle /ventas/{id}: encabezado, ítems con precios del momento, totales, pago, estado; si está anulada: quién, cuándo y motivo. Botones: "Reimprimir ticket" y, para quien tenga anular-ventas y si está COMPLETADA, "Anular venta".
3. Anular: modal con motivo obligatorio (mínimo 5 caracteres) y confirmación explícita ("Esta acción devolverá el stock y no se puede deshacer"). Tras anular, volver al detalle con mensaje de éxito.
4. En el kardex del producto, "Venta #000152" ahora enlaza al detalle de la venta.
5. Menú: "Ventas" → "Nueva venta" y "Historial de ventas".

## Tests
- Cajero ve solo sus ventas del día aunque manipule los filtros de la URL.
- Encargado ve todas y filtra por cajero y fechas.
- El resumen suma solo ventas COMPLETADA.
- Anular desde la interfaz funciona para encargado y devuelve el stock; cajero recibe 403.
- No se puede anular sin motivo.

## Entrega
Ejecuta test y stock:verificar. No hagas commit. Dime cómo probar.
```

**Cómo probar:** haz varias ventas con dos cajeros distintos. Como cajero, verifica que solo veas tus ventas. Como encargado, anula una venta y comprueba que el stock se devuelva y que aparezca en Auditoría.

**Commit:**
```text
Funciona. Haz commit con "feat: historial y anulación de ventas" y push.
```

---

## PROMPT 12 — Panel de inicio y reportes ⚠️

```text
Tarea: PROMPT 12 — panel de inicio y reportes básicos. Lee AGENTS.md. Gate: ver-reportes (admin, encargado). Todas las sumas consideran SOLO ventas COMPLETADA. Consultas eficientes con agregaciones en SQL (no cargar todas las ventas en memoria). Gráficos: si se necesitan, descarga Chart.js a public/vendor/chartjs (sin CDN).

## Panel de inicio (/)
1. Admin/encargado:
   - Tarjetas del día: total vendido hoy, cantidad de ventas, ticket promedio, ventas anuladas hoy.
   - Totales de hoy por método de pago.
   - Gráfico de barras de ventas de los últimos 7 días.
   - Lista de productos con stock bajo (máximo 10, con enlace a la lista completa).
   - Últimas 10 ventas.
2. Cajero: sus ventas de hoy (cantidad y total), botón grande "Nueva venta" y sus últimas 5 ventas.

## Reportes (/reportes), todos con filtro de rango de fechas y exportación a CSV (UTF-8 con BOM, separador ;, compatible con Excel)
3. Resumen de ventas por día: fecha, cantidad de ventas, total, descuentos, anuladas.
4. Ventas por cajero: cajero, cantidad, total.
5. Ventas por método de pago: método, cantidad, total.
6. Productos más vendidos: código, nombre, categoría, cantidad vendida, total vendido; ordenar por cantidad o por total; top 50.
7. Ventas por categoría: categoría, cantidad, total.
8. Cierre del día (para imprimir): para una fecha y opcionalmente un cajero: total por método de pago, cantidad de ventas, anuladas con sus números y motivos, total de efectivo esperado. Vista imprimible en A4 y en 80 mm.
9. Inventario valorizado: producto, stock, precio_compra, valor de costo (stock × precio_compra), precio_venta, valor de venta; totales generales. Filtro por categoría. (Solo productos activos con controla_stock.)
10. Movimientos de stock: por rango de fechas, producto, tipo y usuario.

11. Menú "Reportes" con todos los reportes (solo admin y encargado).

## Tests
- Los totales del día coinciden con los datos de prueba y excluyen anuladas.
- Productos más vendidos ordena correctamente y excluye ventas anuladas.
- La exportación CSV devuelve el tipo de contenido correcto y los encabezados.
- Cajero recibe 403 en /reportes.
- El inventario valorizado calcula bien con decimales.

## Entrega
Ejecuta test. No hagas commit. Dime cómo probar.
```

**Cómo probar:** haz ventas de prueba en distintos días (puedes pedir a OpenCode un seeder de ventas demo para local) y compara los totales de los reportes con el historial de ventas. Exporta un CSV y ábrelo en Excel: las tildes y las columnas deben verse bien.

**Commit:**
```text
Funciona. Haz commit con "feat: panel de inicio y reportes" y push.
```

---

## PROMPT 13 — Backups y restauración ⚠️

```text
Tarea: PROMPT 13 — copias de seguridad automáticas y prueba de restauración en Windows. Lee AGENTS.md. Scripts en PowerShell dentro de scripts/. No guardar contraseñas en los scripts ni en git.

1. scripts/backup.config.example.ps1 (se sube a git) y scripts/backup.config.ps1 (NO se sube; agregar a .gitignore) con variables:
   $PgBin = "C:\Program Files\PostgreSQL\17\bin"
   $DbHost = "localhost"; $DbPort = 5432; $DbName = "libreria_dev"; $DbUser = "libreria_dev"
   $CarpetaBackups = "D:\Backups\NF-Libreria"
   $CarpetaCopiaExterna = ""   (ej: carpeta sincronizada de Google Drive o disco externo; vacío = no copiar)
   $DiasRetencion = 30
   La contraseña NO va aquí: se usa el archivo pgpass.conf de PostgreSQL (%APPDATA%\postgresql\pgpass.conf). Explica cómo crearlo en la documentación.

2. scripts/backup.ps1:
   - Ejecuta pg_dump en formato custom (-Fc) → nf-libreria_AAAA-MM-DD_HHMM.backup en $CarpetaBackups.
   - Verifica que el archivo exista y pese más de 0 bytes; valida con pg_restore --list que sea legible.
   - Copia el backup a $CarpetaCopiaExterna si está configurada y disponible (si no está disponible, registra advertencia pero no falla).
   - Borra backups con más de $DiasRetencion días (solo archivos que coincidan con el patrón nf-libreria_*.backup).
   - Registra cada ejecución en $CarpetaBackups\backup.log (fecha, resultado, tamaño, errores).
   - Además escribe el resultado en la base de datos en configuracion (claves ultimo_backup_fecha y ultimo_backup_resultado) mediante un comando artisan php artisan backup:registrar-resultado {ok|error} {mensaje}, o directamente con psql — elige lo más simple y robusto.
   - Código de salida 0 si todo bien, 1 si falla.

3. scripts/instalar-tarea-backup.ps1: registra en el Programador de tareas de Windows una tarea "NF-Libreria Backup" que ejecute backup.ps1 todos los días a las 13:00 y a las 20:30 (configurable), con la opción "ejecutar aunque el usuario no haya iniciado sesión" si es posible, y "ejecutar lo antes posible si se perdió una ejecución". Debe ejecutarse como administrador.

4. scripts/probar-restauracion.ps1:
   - Toma el backup más reciente (o uno indicado por parámetro).
   - Crea la base temporal libreria_restore_test (borrándola antes si existe), restaura con pg_restore, y compara conteos de tablas clave (productos, ventas, detalle_ventas, movimientos_stock, users) contra la base original; muestra una tabla con el resultado y "RESTAURACIÓN OK" o "ERROR".
   - Borra la base temporal al final.
   - Nunca toca la base original.

5. scripts/restaurar.ps1 (para emergencias): restaura un backup elegido sobre la base indicada. Debe pedir confirmación escribiendo el nombre de la base, y ANTES de restaurar hace un backup de seguridad del estado actual.

6. En el panel de inicio del admin: alerta amarilla si el último backup tiene más de 24 horas o si el último resultado fue error, y el dato "Último backup: fecha y resultado".

7. Documentación:
   - docs/backups.md: cómo funciona, cómo configurar pgpass.conf, cómo instalar la tarea programada, dónde quedan los archivos, cómo revisar el log, recomendación de copia externa y de probar la restauración una vez al mes.
   - docs/restauracion.md: paso a paso para restaurar en la misma PC y en una PC nueva (instalar PostgreSQL, crear usuario y base, restaurar, configurar .env).

## Pruebas
- Ejecuta tú mismo backup.ps1 y probar-restauracion.ps1 contra libreria_dev y muéstrame la salida.
- Test de Laravel para la alerta de backup en el panel (con fechas simuladas).

## Entrega
No hagas commit. Dime cómo probar.
```

**Cómo probar:** configura `pgpass.conf` y `backup.config.ps1` siguiendo `docs/backups.md`. Ejecuta `.\scripts\backup.ps1` y revisa el archivo creado y el log. Ejecuta `.\scripts\probar-restauracion.ps1`: debe decir **RESTAURACIÓN OK**. Instala la tarea programada y revísala en el Programador de tareas de Windows.

**Commit:**
```text
Funciona. Haz commit con "feat: backups automáticos y restauración" y push.
```

---

## PROMPT 14 — Instalación en la PC de la librería y red local ⚠️

```text
Tarea: PROMPT 14 — preparar la instalación de producción en la PC principal de la librería (Windows) y el acceso desde la red local. Lee AGENTS.md. NO uses php artisan serve en producción.

Arquitectura de producción:
- Apache HTTP Server para Windows (Apache Lounge, x64) instalado como servicio de Windows, con PHP 8.4 Thread Safe cargado como módulo (php8apache2_4.dll), DocumentRoot apuntando a la carpeta public/ del proyecto, AllowOverride All para el .htaccess de Laravel.
- PostgreSQL 17 como servicio, escuchando SOLO en localhost (listen_addresses = 'localhost').
- Base de producción libreria_prod con usuario libreria_prod y contraseña fuerte (distinta de la de desarrollo).
- Proyecto en C:\NF-Libreria (clonado desde GitHub).

1. docs/instalacion.md, paso a paso para alguien con conocimientos básicos:
   a) Requisitos de la PC (Windows 10/11 actualizado, SSD, 8 GB RAM recomendado, UPS recomendado).
   b) Instalar Git, PHP 8.4 TS, Visual C++ Redistributable, Composer, PostgreSQL 17 y Apache Lounge (con winget cuando sea posible; enlaces oficiales cuando no).
   c) Configurar php.ini de producción (partir de php.ini-production, extensiones necesarias, date.timezone, opcache activado, upload_max_filesize 5M, display_errors Off).
   d) Crear usuario y base de producción.
   e) Clonar el repositorio en C:\NF-Libreria, composer install --no-dev --optimize-autoloader, crear .env de producción (APP_ENV=production, APP_DEBUG=false, APP_URL con la IP local, SESSION_DRIVER=database, credenciales de producción, ADMIN_PASSWORD_INICIAL), key:generate, migrate --force, db:seed --force (solo configuración, categorías y admin; NUNCA datos demo), config:cache, route:cache, view:cache.
   f) Permisos de escritura para el servicio de Apache en storage/ y bootstrap/cache/.
   g) Configurar Apache (httpd.conf / vhost) y registrarlo como servicio con inicio automático.
   h) Configurar backups (PROMPT 13) apuntando a libreria_prod.
   i) Verificación final.

2. docs/red-local.md:
   - Cómo reservar una IP fija para la PC principal (en el router por DHCP reservado, o IP estática en Windows) con ejemplos.
   - Regla del Firewall de Windows: permitir el puerto 80 entrante SOLO en perfil de red Privada; asegurarse de que la red de la librería esté marcada como Privada. Comando PowerShell New-NetFirewallRule incluido.
   - Cómo acceder desde otra PC: http://IP-DE-LA-PC-PRINCIPAL y crear un acceso directo en el escritorio.
   - Confirmar que el puerto 5432 NO está abierto en el firewall.
   - Qué hacer si una PC no puede conectarse (lista de verificación).

3. scripts/instalar-produccion.ps1: automatiza lo que sea seguro automatizar (verificar requisitos, crear carpetas, composer install, copiar .env.example a .env si no existe y pedir los datos, migrate, caches, regla de firewall, registrar tarea de backup). Debe ser idempotente (se puede ejecutar dos veces sin romper nada) y mostrar claramente cada paso. Nada de contraseñas escritas en el script.

4. scripts/actualizar.ps1: procedimiento de actualización de versión en producción: backup previo obligatorio, php artisan down, git pull, composer install --no-dev, migrate --force, limpiar y regenerar caches, php artisan up. Si falla algún paso, detenerse y mostrar cómo volver atrás (git checkout del commit anterior + restaurar backup).

5. docs/configuracion.md: variables del .env explicadas.

6. Verifica que la aplicación funcione con APP_ENV=production y APP_DEBUG=false en mi PC (por ejemplo con una copia de .env temporal) y que las páginas de error no muestren información técnica.

## Entrega
No hagas commit. Dime qué pasos del proceso puedo probar en mi PC de desarrollo antes de ir a la librería.
```

**Cómo probar:** idealmente, en una segunda PC o una máquina virtual con Windows, sigue `docs/instalacion.md` al pie de la letra como si fueras otra persona. Anota lo que no se entienda o falle y pásaselo a OpenCode para corregir la documentación. Luego entra desde otra PC o desde el celular (en la misma red WiFi) usando la IP.

**Commit:**
```text
Funciona. Haz commit con "docs: instalación de producción, red local y scripts de despliegue" y push.
```

---

## PROMPT 15 — Pruebas finales y manuales

```text
Tarea: PROMPT 15 — preparación para producción: pruebas finales, revisión de seguridad y manuales de usuario. Lee AGENTS.md.

1. Revisión de seguridad del código completo. Reporta (sin corregir aún) cualquier:
   - ruta sin middleware auth o sin control de permisos;
   - uso de {!! !!} con datos de usuario;
   - SQL con variables concatenadas;
   - formularios sin @csrf;
   - lugares donde se modifica productos.stock fuera de StockService;
   - cálculos de dinero con float;
   - datos sensibles en logs o en auditoría;
   - archivos que no deberían estar en git.
   Luego corrige lo encontrado, en commits separados que yo apruebe.

2. Ejecuta php artisan test y php artisan stock:verificar y reporta resultados. Si la cobertura de lógica crítica tiene huecos, agrega los tests que falten.

3. Crea docs/pruebas-finales.md con una lista de verificación manual (casillas [ ]) que yo seguiré antes de usar el sistema con ventas reales:
   venta normal; venta con varios productos; venta con fotocopias; venta en efectivo con cambio; venta con QR; descuento (encargado); anulación; entrada de mercadería; anulación de entrada; ajuste de stock; importación CSV; usuarios y permisos por rol; cierre de sesión por inactividad; reportes contra ventas reales; cierre del día; backup manual; backup automático; restauración de prueba; acceso desde segunda PC; dos cajas vendiendo el mismo producto a la vez; reinicio de la PC principal (los servicios deben levantar solos); desconexión de una PC secundaria en medio de una venta; corte de luz simulado (apagar y encender) y verificación de datos; impresión de tickets.

4. Manuales en lenguaje simple, con pasos numerados:
   - docs/manual-cajero.md: iniciar sesión, cambiar contraseña, hacer una venta, atajos de teclado, cobrar en efectivo/QR, imprimir y reimprimir ticket, consultar precios y stock, qué hacer si se equivocó en una venta (avisar al encargado), cerrar sesión.
   - docs/manual-administrador.md: usuarios, configuración, categorías, productos, importación, entradas, ajustes, stock bajo, anulaciones, reportes, cierre del día, auditoría, backups, qué hacer si la PC principal falla.

5. Actualiza README.md con: qué es el sistema, capturas o descripción de módulos, stack, enlaces a toda la documentación de docs/.

## Entrega
No hagas commit hasta que yo revise. Muéstrame el informe de seguridad primero.
```

**Cómo probar:** recorre `docs/pruebas-finales.md` completa, marcando cada casilla. Lo que falle se lo pasas a OpenCode como tarea de corrección (fix).

**Commit:**
```text
Funciona. Haz commit con "docs: pruebas finales, manuales y README" y push.
```

---

## PROMPT 16 — Caja: apertura y cierre (después del MVP)

> Hazlo solo después de usar el sistema unos días en la librería y confirmar que el control de caja es necesario.

```text
Tarea: PROMPT 16 — módulo de caja (apertura, movimientos y cierre con arqueo). Lee AGENTS.md.

## Base de datos
Tabla cajas (sesiones de caja):
- id, user_id (quien abre), abierta_en timestampTz, monto_inicial decimal(12,2),
- cerrada_en timestampTz nullable, cerrada_por nullable (users),
- efectivo_esperado decimal(12,2) nullable, efectivo_contado decimal(12,2) nullable, diferencia decimal(12,2) nullable,
- observaciones_cierre text nullable, estado string(20): ABIERTA, CERRADA.
- Regla: un usuario solo puede tener una caja ABIERTA a la vez (índice único parcial en user_id WHERE estado = 'ABIERTA').

Tabla movimientos_caja:
- id, caja_id, tipo string(20): INGRESO, EGRESO, user_id, monto decimal(12,2) > 0, concepto string(200), created_at timestampTz.

Agregar a ventas: caja_id foreignId nullable (nueva migración).

## Reglas
1. Configuración nueva: exigir_caja_abierta (por defecto "1"). Si está activa, no se puede vender sin caja abierta: la pantalla de venta redirige a "Abrir caja".
2. Abrir caja: monto inicial en efectivo. Auditar.
3. Cada venta se asocia a la caja abierta del usuario que vende.
4. Ingresos y egresos manuales de efectivo (ej: pago de un servicio, cambio de billetes) con concepto obligatorio. Auditar.
5. Cierre: el sistema calcula efectivo_esperado = monto_inicial + ventas en EFECTIVO completadas de esa caja + ingresos − egresos (las ventas anuladas no suman). El usuario ingresa el efectivo contado (con ayuda opcional para contar por denominación: billetes de 200, 100, 50, 20, 10 y monedas de 5, 2, 1, 0,50, 0,20, 0,10). Se guarda la diferencia (sobrante/faltante). Muestra también los totales por los otros métodos de pago (informativo). Auditar.
6. Una caja cerrada no se puede modificar. Anular una venta de una caja ya cerrada: permitido para admin/encargado, pero se muestra en el reporte de esa caja como "anulada después del cierre".
7. Pantallas: mi caja actual (resumen en vivo), abrir, movimientos, cerrar, historial de cajas (admin/encargado ven todas), reporte imprimible de cierre (A4 y 80 mm).

## Tests
- No se puede vender sin caja abierta cuando la opción está activa.
- Un usuario no puede abrir dos cajas.
- El efectivo esperado del cierre es correcto con ventas en efectivo, ventas QR (no suman), anuladas (no suman), ingresos y egresos.
- La diferencia se calcula bien (sobrante y faltante).
- Caja cerrada no acepta movimientos.

## Entrega
Ejecuta test. No hagas commit. Dime cómo probar.
```

**Commit:**
```text
Funciona. Haz commit con "feat: módulo de caja con apertura y cierre" y push.
```

---

## Más adelante (a pedido)

- Proveedores (tabla y vincular entradas).
- Clientes (con historial de compras).
- Código de barras (columna en productos y lectura con lector USB en la pantalla de venta).
- Devoluciones parciales.
- Facturación electrónica (SIN).
- Versión online.
