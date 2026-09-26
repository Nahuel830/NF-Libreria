# Propuesta de implementación — NF Librería

> **Decisiones tomadas (actualizado 26/09/2026)** — prevalecen sobre el resto del documento si hay contradicción:
>
> - **Stack:** Laravel (última versión estable) + PHP 8.4 + PostgreSQL 17. Vistas Blade con Bootstrap 5 guardado localmente. Sin Node/npm/Vite ni CDNs.
> - **Proyecto nuevo desde cero** (no hay código existente), por lo que la Fase 1 de auditoría se reduce a verificar el entorno.
> - **Sin facturación electrónica por ahora**, pero sin diseñar nada que impida agregarla más adelante.
> - **MVP primero:** usuarios y roles, categorías, productos, entradas de stock, ventas con descuento de stock, anulación, auditoría básica y backup diario. Caja, reportes avanzados, proveedores y clientes vienen después.
> - **Reglas técnicas:** dinero en NUMERIC(12,2); ventas bloquean stock con SELECT ... FOR UPDATE dentro de transacciones; detalle de venta guarda nombre y precio del momento; stock negativo configurable (por defecto permitido con advertencia); productos, categorías y usuarios se desactivan, no se borran.
> - **Pendientes a considerar:** impresión de tickets en impresora térmica, lector de código de barras, venta rápida sin código, importación inicial de productos desde Excel/CSV, cierre de sesión automático, copia de backups fuera del local (nube cifrada).

# Propuesta de implementación — Sistema de gestión y ventas para librería

## 1. Objetivo del proyecto

El objetivo es implementar un sistema informático para administrar y controlar las operaciones de una librería.

El sistema deberá permitir gestionar principalmente:

* Productos.
* Categorías.
* Precios.
* Inventario.
* Entradas de mercadería.
* Salidas y ajustes de stock.
* Ventas.
* Detalle de cada venta.
* Usuarios.
* Roles y permisos.
* Clientes, si son necesarios.
* Caja.
* Reportes.
* Historial de operaciones.
* Auditoría.
* Copias de seguridad.

La primera versión se implementará **de manera completamente local**, de forma que la librería pueda utilizar el sistema incluso si no tiene conexión a Internet.

La arquitectura estará preparada para que posteriormente pueda ampliarse a una modalidad online si el negocio lo necesita.

---

# 2. Arquitectura general

La idea principal será tener una **PC principal** dentro de la librería.

Esta PC funcionará como servidor local.

En ella estarán:

```text
PC PRINCIPAL
│
├── Sistema de la librería
│
├── PHP
│
├── Servidor web
│
├── PostgreSQL
│
├── Base de datos
│
└── Sistema de backups
```

Las demás computadoras no necesitarán tener PostgreSQL ni la base de datos.

Simplemente estarán conectadas a la misma red local.

Por ejemplo:

```text
                    ROUTER
                      │
              RED LOCAL / WIFI
                      │
        ┌─────────────┼─────────────┐
        │             │             │
        ▼             ▼             ▼
   PC PRINCIPAL    PC CAJA 2     LAPTOP
     SERVIDOR
        │
        ▼
   PostgreSQL
        │
        ▼
   BASE DE DATOS
```

La PC principal será el centro del sistema.

---

# 3. ¿Cómo utilizará el sistema el personal?

En la PC principal se podrá abrir el sistema desde:

```text
http://localhost
```

o una dirección local definida.

Desde otra computadora de la misma red se podría entrar mediante la IP de la PC principal:

```text
http://192.168.1.100
```

La dirección real dependerá de la red de la librería.

Por ejemplo:

```text
PC PRINCIPAL
IP: 192.168.1.100

PC CAJA:
http://192.168.1.100

LAPTOP:
http://192.168.1.100
```

De esta manera, **todos trabajan sobre la misma aplicación y la misma base de datos**.

No habrá una base de datos diferente en cada computadora.

---

# 4. ¿Por qué una PC principal?

Porque queremos evitar tener esto:

```text
PC 1 → BD diferente
PC 2 → BD diferente
Laptop → BD diferente
```

Eso produciría problemas de sincronización.

En cambio:

```text
                BASE DE DATOS
                      ▲
                      │
              PC PRINCIPAL
                      ▲
             ┌────────┼────────┐
             │        │        │
           Caja     Laptop   Admin
```

Todos trabajan con la misma información.

Si un cajero vende un producto:

```text
Cajero
 ↓
Venta
 ↓
PostgreSQL
 ↓
Stock actualizado
```

Cuando el dueño entra desde la laptop:

```text
Laptop
 ↓
Sistema
 ↓
PostgreSQL
 ↓
Ve el stock actualizado
```

No hay que copiar información manualmente.

---

# 5. Base de datos

La base de datos propuesta será **PostgreSQL**.

La razón principal para utilizar PostgreSQL es tener una base sólida para manejar:

* relaciones entre tablas;
* transacciones;
* integridad de datos;
* múltiples usuarios;
* operaciones simultáneas;
* consultas complejas;
* crecimiento futuro del sistema.

La base de datos estará únicamente en la PC principal.

Las otras computadoras no necesitarán PostgreSQL.

---

# 6. Estructura conceptual de la base de datos

La estructura exacta se definirá después de revisar el código existente, pero conceptualmente debería incluir entidades similares a:

```text
usuarios
roles
permisos
productos
categorias
proveedores
clientes
ventas
detalle_ventas
entradas_stock
detalle_entradas
movimientos_stock
cajas
movimientos_caja
auditoria
configuracion
```

No necesariamente todas estas tablas serán necesarias.

La estructura definitiva se determinará según las necesidades reales de la librería.

---

# 7. Módulo de productos

El sistema deberá permitir administrar los productos de la librería.

Información posible:

* Código.
* Código de barras, si corresponde.
* Nombre.
* Descripción.
* Categoría.
* Marca/editorial, si corresponde.
* Precio de compra.
* Precio de venta.
* Stock actual.
* Stock mínimo.
* Estado.
* Fecha de creación.
* Fecha de modificación.

Ejemplo:

```text
Producto:
Cuaderno universitario

Código:
CUA-001

Categoría:
Cuadernos

Precio:
Bs. 25

Stock:
35

Stock mínimo:
5
```

---

# 8. Categorías

Los productos deberían poder organizarse.

Ejemplo:

```text
Cuadernos
Lapiceros
Lápices
Material escolar
Material de oficina
Libros
Carpetas
Hojas
Papel
Accesorios
Otros
```

El administrador podrá crear, editar o desactivar categorías.

---

# 9. Control de inventario

El sistema deberá registrar los movimientos de stock.

No solamente queremos conocer:

> "Hay 50 unidades."

También queremos poder saber:

> "¿Por qué ahora hay 50?"

Por eso habrá movimientos.

Ejemplo:

```text
Stock inicial: 20

Entrada de proveedor: +30

Venta: -5

Ajuste: -2

Stock actual: 43
```

---

# 10. Entradas de stock

Cuando llegue mercadería nueva:

```text
Proveedor
     ↓
Entrada de mercadería
     ↓
Productos
     ↓
Aumenta stock
```

Ejemplo:

```text
Producto: Cuaderno
Cantidad: 50
Costo: Bs. 15
```

El sistema registra la entrada y actualiza el stock.

Además, sería conveniente guardar:

* usuario que realizó la entrada;
* fecha;
* proveedor;
* productos;
* cantidades;
* costo;
* observaciones.

---

# 11. Ventas

Este será uno de los módulos principales.

Una venta deberá registrar como mínimo:

* Número de venta.
* Fecha.
* Hora.
* Usuario.
* Productos.
* Cantidades.
* Precio unitario.
* Descuento, si existe.
* Subtotal.
* Total.
* Método de pago.
* Estado.

Ejemplo:

```text
VENTA #000152

Cuaderno       x2    Bs. 25
Lapicero       x3    Bs. 5
Carpeta        x1    Bs. 15

TOTAL: Bs. 80

Usuario: cajero01
Fecha: 26/09/2026
Hora: 15:32
```

---

# 12. Las ventas no deberían eliminarse

Esta será una regla importante.

No queremos que alguien pueda borrar una venta y hacer que desaparezca del historial.

En lugar de:

```text
DELETE venta
```

se utilizará:

```text
Venta #152
Estado: ANULADA
```

Y se conservará:

* quién la anuló;
* cuándo;
* motivo;
* información original.

Esto permite mantener un historial confiable.

---

# 13. Métodos de pago

El sistema puede contemplar:

* Efectivo.
* Transferencia.
* QR.
* Tarjeta.
* Otros métodos que la librería utilice.

Más adelante se puede ampliar.

---

# 14. Caja

Si el funcionamiento de la librería lo requiere, habrá un módulo de caja.

Podría manejar:

```text
Apertura de caja
       ↓
Ventas
       ↓
Entradas
       ↓
Salidas
       ↓
Cierre de caja
```

Al cierre:

```text
Total ventas:
Bs. XXXXX

Efectivo:
Bs. XXXXX

Transferencias:
Bs. XXXXX

Otros:
Bs. XXXXX
```

El sistema puede permitir comparar lo registrado contra el dinero realmente disponible.

---

# 15. Usuarios

Cada persona deberá utilizar su propia cuenta.

Por ejemplo:

```text
admin
cajero1
cajero2
encargado
```

No sería recomendable que todos utilicen una misma cuenta.

De esa forma el sistema sabe quién realizó cada operación.

---

# 16. Roles y permisos

Propongo inicialmente tres roles principales.

## Administrador

Tendrá acceso completo.

Podrá:

* gestionar usuarios;
* gestionar permisos;
* crear/modificar productos;
* administrar categorías;
* administrar proveedores;
* gestionar stock;
* registrar entradas;
* realizar ventas;
* anular operaciones;
* consultar reportes;
* administrar caja;
* consultar auditoría;
* realizar configuraciones.

---

## Encargado / propietario

Podrá:

* consultar ventas;
* consultar reportes;
* administrar productos;
* consultar y modificar inventario;
* registrar entradas;
* revisar caja;
* consultar movimientos.

Algunas funciones administrativas podrían restringirse según lo que decida el propietario.

---

## Cajero

Tendrá acceso principalmente a:

* realizar ventas;
* consultar productos;
* consultar precios;
* consultar disponibilidad;
* trabajar con caja.

No debería poder:

* eliminar productos;
* modificar usuarios;
* modificar configuraciones críticas;
* borrar ventas;
* acceder a la configuración de la base de datos.

---

# 17. Auditoría

Este módulo será especialmente importante.

El sistema debe poder registrar acciones importantes.

Ejemplo:

```text
Usuario: cajero1
Fecha: 26/09/2026 15:42

Acción:
Anuló venta #152

Motivo:
Producto ingresado incorrectamente
```

Otro ejemplo:

```text
Usuario: admin

Producto:
Cuaderno ABC

Stock anterior:
20

Stock nuevo:
50

Acción:
Ajuste de inventario
```

Esto permitirá saber qué ocurrió cuando exista alguna diferencia.

---

# 18. Seguridad de usuarios

Las contraseñas nunca deberán almacenarse directamente.

La aplicación utilizará hash de contraseñas.

También se deberán implementar:

* sesiones;
* cierre de sesión;
* control de permisos;
* bloqueo de acceso a módulos no autorizados;
* protección contra SQL Injection;
* validación de datos;
* protección de formularios;
* control de sesiones.

---

# 19. Seguridad de PostgreSQL

PostgreSQL no debería quedar expuesto innecesariamente.

La arquitectura será:

```text
PC cliente
    ↓
Servidor web
    ↓
Aplicación PHP
    ↓
PostgreSQL
```

No:

```text
PC cliente
    ↓
PostgreSQL directamente
```

Esto permite centralizar el acceso mediante la aplicación.

---

# 20. Backups

Este punto será **obligatorio**.

No debemos pensar:

> "Como es local, está seguro."

La PC puede:

* dañarse;
* ser robada;
* sufrir un problema de disco;
* tener corrupción de datos;
* sufrir un apagón;
* tener un error humano.

Por eso tendremos backups.

Propuesta:

```text
BASE DE DATOS
      ↓
BACKUP AUTOMÁTICO
      ↓
backup_2026-09-26
backup_2026-09-27
backup_2026-09-28
...
```

Se conservarán varias copias.

Además, se recomienda mantener una segunda copia en un dispositivo externo.

---

# 21. Restauración

No basta con crear backups.

Hay que probar que funcionan.

Cada cierto tiempo se debería hacer una prueba:

```text
Backup
  ↓
Restauración en BD de prueba
  ↓
Comprobar productos
  ↓
Comprobar ventas
  ↓
Comprobar stock
```

Así sabemos que el backup realmente sirve.

---

# 22. PC principal

La PC principal tendrá una responsabilidad especial.

Debe:

* estar encendida durante el horario de funcionamiento;
* estar conectada a la red;
* tener PostgreSQL;
* tener el servidor web;
* tener la aplicación;
* ejecutar backups;
* tener una IP local estable.

Idealmente:

```text
PC principal
      ↓
UPS
      ↓
Router
```

El UPS ayudará a protegerla frente a cortes eléctricos.

---

# 23. ¿Qué ocurre si agregamos otra PC?

No se instalará otra base de datos.

La nueva computadora simplemente se conecta a la misma red.

Ejemplo:

```text
PC PRINCIPAL
192.168.1.100

Nueva PC
     ↓
http://192.168.1.100
```

Y podrá utilizar el sistema según los permisos del usuario.

Esto permite agregar:

* segunda caja;
* laptop;
* PC administrativa;
* otra computadora de la oficina.

---

# 24. ¿Qué pasa si la PC principal cambia?

La arquitectura debe permitir reemplazarla.

Proceso:

```text
PC anterior
     ↓
Backup
     ↓
Nueva PC
     ↓
Instalar sistema
     ↓
Instalar PostgreSQL
     ↓
Restaurar BD
     ↓
Configurar red
     ↓
Sistema funcionando
```

Esto es una razón para **no guardar información importante solamente en carpetas difíciles de identificar**.

Todo debe estar documentado.

---

# 25. Instalación

La intención final es que la instalación sea sencilla.

Idealmente se llegará a algo como:

```text
Instalador_Sistema_Libreria.exe
```

El instalador podría preparar:

```text
✔ Aplicación
✔ PHP
✔ Servidor web
✔ PostgreSQL
✔ Base de datos
✔ Configuración
✔ Servicios
✔ Backup
✔ Acceso directo
```

La persona que utiliza la librería no debería tener que configurar manualmente todo esto.

---

# 26. Desarrollo en Antigravity + OpenCode

El desarrollo se realizará utilizando **Antigravity** como entorno de trabajo y **OpenCode como agente externo**.

OpenCode podrá ayudar con:

* análisis del proyecto;
* creación de código;
* modificación de módulos;
* revisión de errores;
* refactorización;
* creación de consultas;
* creación de migraciones;
* pruebas;
* documentación.

Pero el agente no debería tener libertad para modificar todo el proyecto sin control.

El proceso será:

```text
IDEA
 ↓
TAREA
 ↓
OpenCode analiza
 ↓
OpenCode propone/aplica cambios
 ↓
PRUEBA
 ↓
REVISIÓN
 ↓
COMMIT
 ↓
GITHUB
 ↓
SIGUIENTE TAREA
```

---

# 27. GitHub

El proyecto tendrá un repositorio privado.

Por ejemplo:

```text
GitHub
└── sistema-libreria
```

Dentro:

```text
/app
/config
/database
/public
/assets
/scripts
/tests
/docs
```

La estructura real dependerá del proyecto existente.

---

# 28. Qué NO subir a GitHub

Nunca deberíamos subir:

```text
❌ Contraseñas
❌ Credenciales PostgreSQL
❌ Claves privadas
❌ Datos reales de clientes
❌ Base de datos de producción
❌ Backups reales
❌ Tokens
❌ Archivos .env reales
```

En su lugar:

```text
.env.example
```

puede mostrar qué variables necesita el sistema sin incluir las contraseñas reales.

---

# 29. Control de versiones

Cada cambio importante tendrá un commit.

Ejemplo:

```text
feat: agregar módulo de entradas de stock
```

Después:

```text
fix: corregir descuento de stock al anular venta
```

Después:

```text
feat: agregar permisos para cajero
```

Esto permite saber qué se modificó.

Si algo rompe el sistema:

```text
Versión actual ❌
       ↓
GitHub
       ↓
Versión anterior ✅
```

---

# 30. Desarrollo por etapas

No intentaría construir todo de una vez.

Lo dividiría así.

### Fase 1 — Auditoría

Revisar el sistema actual:

* código;
* estructura;
* BD actual;
* consultas;
* módulos;
* dependencias;
* conexión a BD.

Objetivo:

**saber exactamente qué tenemos antes de modificarlo.**

---

### Fase 2 — PostgreSQL

Adaptar la base de datos.

Revisar:

* tablas;
* relaciones;
* claves primarias;
* claves foráneas;
* índices;
* tipos de datos;
* consultas;
* transacciones.

Objetivo:

**tener una BD PostgreSQL limpia y documentada.**

---

### Fase 3 — Conexión PHP + PostgreSQL

Adaptar el sistema para que PHP trabaje correctamente con PostgreSQL.

Probar:

```text
PHP
 ↓
PostgreSQL
 ↓
SELECT
INSERT
UPDATE
DELETE controlado
TRANSACTION
```

---

### Fase 4 — Usuarios y permisos

Implementar:

```text
Administrador
Encargado
Cajero
```

y controlar cada módulo.

---

### Fase 5 — Productos

Probar:

* crear;
* editar;
* buscar;
* desactivar;
* categorizar;
* precios.

---

### Fase 6 — Inventario

Implementar:

* entradas;
* salidas;
* ajustes;
* historial;
* stock mínimo.

---

### Fase 7 — Ventas

Probar:

* venta;
* detalle;
* total;
* pagos;
* descuento de stock;
* anulación;
* auditoría.

---

### Fase 8 — Caja y reportes

Implementar según las necesidades reales:

* apertura;
* movimientos;
* cierre;
* ventas diarias;
* ventas por usuario;
* ventas por producto;
* inventario.

---

### Fase 9 — Red local

Configurar:

```text
PC principal
     ↓
IP fija/reservada
     ↓
Red local
     ↓
Laptop / segunda PC
```

Probar varios equipos simultáneamente.

---

### Fase 10 — Backups

Automatizar:

```text
Backup diario
       ↓
Almacenamiento
       ↓
Prueba de restauración
```

---

### Fase 11 — Instalación

Preparar la instalación para la PC de la librería.

---

### Fase 12 — Pruebas finales

Antes de comenzar a registrar ventas reales:

* venta normal;
* venta con múltiples productos;
* anulación;
* devolución si corresponde;
* entrada de stock;
* ajuste;
* usuarios;
* permisos;
* reportes;
* backup;
* restauración;
* segunda PC;
* reinicio de PC principal;
* pérdida de conexión de una PC secundaria.

---

# 31. Entorno de pruebas y producción

Es importante separar ambos.

Durante el desarrollo:

```text
TU PC
 ↓
ENTORNO DE DESARROLLO
 ↓
BD DE PRUEBA
```

Cuando esté aprobado:

```text
PC LIBRERÍA
 ↓
ENTORNO DE PRODUCCIÓN
 ↓
BD REAL
```

No se debería experimentar directamente sobre la base de datos que está utilizando la librería.

---

# 32. Flujo de trabajo recomendado

Cada nueva función seguirá:

```text
1. Definir función
       ↓
2. Crear tarea
       ↓
3. OpenCode analiza
       ↓
4. Modificación
       ↓
5. Prueba
       ↓
6. Revisión
       ↓
7. Commit
       ↓
8. Push a GitHub
       ↓
9. Documentar
```

Por ejemplo:

**Tarea:**

> Crear módulo para registrar entradas de stock.

OpenCode primero analiza:

```text
productos
stock
usuarios
auditoria
```

Después modifica lo necesario.

Se prueba.

Si funciona:

```text
git commit
git push
```

Y recién entonces se continúa.

---

# 33. Documentación

El proyecto debería tener una carpeta:

```text
/docs
```

Con documentos como:

```text
instalacion.md
configuracion.md
base-de-datos.md
usuarios-y-permisos.md
backups.md
restauracion.md
red-local.md
manual-administrador.md
manual-cajero.md
```

Esto es importante porque si en el futuro otra persona tiene que mantener el sistema, no dependerá únicamente de ti.

---

# 34. Preparación para futuro acceso remoto

Aunque inicialmente será local, el sistema no debería diseñarse de manera que sea imposible llevarlo posteriormente a Internet.

La idea es:

### Ahora

```text
PC
 ↓
Servidor local
 ↓
PostgreSQL local
```

### Futuro

```text
Internet
 ↓
Servidor
 ↓
PHP
 ↓
PostgreSQL
 ↑
PC librería
 ↑
Laptop dueño
```

La aplicación debería mantener separadas:

* lógica;
* interfaz;
* base de datos;
* configuración.

Así la migración futura será mucho más sencilla.

---

# 35. Qué no se hará

Para mantener el proyecto ordenado:

### No:

* instalar PostgreSQL en cada computadora;
* mantener una BD diferente por computadora;
* copiar BD manualmente entre PCs;
* permitir que cualquiera sea administrador;
* eliminar ventas definitivamente;
* guardar contraseñas en el código;
* subir credenciales a GitHub;
* trabajar directamente sobre producción durante el desarrollo;
* depender de Internet para registrar ventas;
* modificar muchas partes del sistema sin probarlas.

---

# 36. Resultado final esperado

La situación final debería ser:

```text
                 RED LOCAL
                     │
          ┌──────────┴──────────┐
          │                     │
          ▼                     ▼
    PC PRINCIPAL             LAPTOP
     SERVIDOR               DUEÑO
          │
          │
      ┌───┴───────────────┐
      │                   │
      ▼                   ▼
   PHP/WEB            PostgreSQL
      │                   │
      └─────────┬─────────┘
                │
          BASE DE DATOS
                │
        ┌───────┴───────┐
        ▼               ▼
     VENTAS            STOCK
        │               │
        └───────┬───────┘
                ▼
             REPORTES
```

El propietario podrá utilizar la laptop **si está dentro de la misma red local**, y una nueva PC podrá incorporarse sin instalar otra base de datos.

---

# 37. Requisitos de la PC principal

Idealmente:

* Windows actualizado.
* SSD.
* suficiente RAM para el sistema y las operaciones habituales.
* conexión de red estable.
* PostgreSQL.
* servidor web.
* PHP.
* sistema.
* espacio para backups.
* UPS recomendado.

No es necesario comprar una computadora extremadamente potente para una librería pequeña.

La prioridad es que sea **estable y confiable**.

---

# 38. Requisitos de las PCs secundarias

Una PC secundaria solamente necesitará:

* navegador web actualizado;
* conexión a la misma red;
* usuario y contraseña del sistema.

No debería necesitar:

* PostgreSQL;
* PHP;
* XAMPP;
* servidor web;
* archivos de la aplicación.

Eso hace mucho más sencillo agregar una nueva PC.

---

# 39. Principio fundamental del proyecto

El sistema debe ser construido pensando en:

> **"Una sola fuente de información."**

La base de datos central será la fuente oficial.

Por ejemplo:

```text
Producto
Stock actual
Ventas
Usuarios
Clientes
Caja
```

todo estará en la misma BD.

No habrá archivos independientes con información que pueda quedar desactualizada.

---

# 40. Conclusión

La propuesta es implementar el sistema **localmente con PostgreSQL**, utilizando una **PC principal como servidor local y base de datos central**.

Las demás computadoras podrán acceder al sistema mediante la red local y un navegador.

El desarrollo se realizará progresivamente en **Antigravity con OpenCode**, utilizando **GitHub privado** como sistema de control de versiones.

Cada cambio será:

```text
Desarrollar
   ↓
Probar
   ↓
Revisar
   ↓
Commit
   ↓
GitHub
```

La PC principal será preparada para iniciar automáticamente los servicios necesarios y mantener la aplicación disponible durante el horario de trabajo.

La base de datos tendrá **backups automáticos**, y se deberá comprobar periódicamente que esos backups puedan restaurarse.

Los usuarios tendrán permisos diferenciados y las operaciones importantes quedarán registradas mediante auditoría.

Finalmente, aunque el sistema será local, se desarrollará de manera que posteriormente pueda migrarse a un servidor online si el propietario decide que necesita acceder desde fuera de la librería.

---

## Plan resumido

```text
                 PROYECTO
                    │
                    ▼
            Revisar sistema actual
                    │
                    ▼
             PostgreSQL
                    │
                    ▼
           Adaptar PHP + BD
                    │
                    ▼
           Usuarios / permisos
                    │
                    ▼
        Productos / inventario
                    │
                    ▼
              Ventas / caja
                    │
                    ▼
              Reportes
                    │
                    ▼
              Auditoría
                    │
                    ▼
               Backups
                    │
                    ▼
             Pruebas locales
                    │
                    ▼
             Pruebas en red
                    │
                    ▼
          Segunda PC / laptop
                    │
                    ▼
             Instalación final
                    │
                    ▼
              PRODUCCIÓN
                    │
                    ▼
           Ventas reales
```

**La idea central es que no intentemos hacer todo de golpe.** Primero vamos a conseguir que el sistema funcione correctamente con PostgreSQL en tu PC de desarrollo; después probamos cada módulo, luego probamos que varias computadoras puedan utilizar la misma BD, después hacemos backups/restauración y finalmente lo instalamos en la PC de la librería. Así, cuando la librería empiece a depender del sistema para sus ventas, ya habremos probado los puntos críticos.
