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
