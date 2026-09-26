# Levantar el proyecto en una PC de desarrollo nueva

## Requisitos

- PHP 8.4 con la extensión `pdo_pgsql` activada (`php -m` debe listar `pdo_pgsql`).
- Composer 2.
- PostgreSQL 17 (servicio en ejecución).
- Git.

## Paso a paso

1. Crear el usuario y las bases de datos en PostgreSQL:
   - Usuario: `libreria_dev` (con contraseña).
   - Bases: `libreria_dev` y `libreria_test`, ambas propiedad de `libreria_dev`.
2. Clonar el repositorio:
   - `git clone https://github.com/Nahuel830/NF-Libreria.git`
   - `cd NF-Libreria` (la carpeta del proyecto; en este equipo es `D:\Nahuel Martinez\Libreria`).
3. Instalar dependencias PHP:
   - `composer install`
4. Copiar la configuración de entorno y ajustarla:
   - Copiar `.env.example` a `.env`.
   - En `.env` completar `DB_DATABASE=libreria_dev`, `DB_USERNAME=libreria_dev` y `DB_PASSWORD` con la contraseña del paso 1.
5. Generar la clave de la aplicación:
   - `php artisan key:generate`
6. Crear las tablas:
   - `php artisan migrate`
7. Levantar el servidor de desarrollo:
   - `php artisan serve`
   - Abrir http://127.0.0.1:8000 en el navegador.

## Comprobación

- `php artisan test`: todo en verde (usa la base `libreria_test`).
- Desconectar Internet y recargar la página: debe verse igual (no se usa CDN).
