# Instalación en la PC principal de la librería (Windows 10/11)

> Lee también `docs/red-local.md` (IP fija y firewall) y `docs/puesta-en-marcha.md`
> (primeros días). No se usa `php artisan serve` en producción.

## a) Requisitos de la PC

- Windows 10/11 actualizado (64 bits), SSD, 8 GB de RAM recomendado.
- UPS recomendado (cortes de luz).
- IP fija en la red local (ver `docs/red-local.md`).
- Desactivar la suspensión automática en horario de trabajo:
  Configuración → Sistema → Energía → Suspender: Nunca (conectado).

## b) Instalar programas

Abre PowerShell y ejecuta (con Internet):

```powershell
winget install --id Git.Git -e
winget install --id Microsoft.VCRedist.2015+.x64 -e
winget install --id PHP.PHP.8.4 -e
winget install --id PostgreSQL.PostgreSQL.17 --interactive
```

Composer (método manual):

1. Descarga `composer-setup.php` de https://getcomposer.org/download/ a `C:\composer`.
2. Ejecuta: `php C:\composer\composer-setup.php --install-dir=C:\composer --filename=composer`
3. Agrega `C:\composer` al PATH y verifica con `composer --version`.

Apache Lounge:

1. Descarga Apache Win64 con VS17 de https://www.apachelounge.com/download/.
2. Descomprime en `C:\Apache24` (debe quedar `C:\Apache24\bin\httpd.exe`).

## c) php.ini de producción

1. En la carpeta de PHP, copia `php.ini-production` como `php.ini`.
2. Ajusta estas líneas (quita el `;` del inicio donde indique):
   ```ini
   extension_dir = "C:\ruta\a\php\ext"
   extension=pdo_pgsql
   extension=pgsql
   extension=mbstring
   extension=openssl
   extension=fileinfo
   extension=curl
   extension=zip
   extension=intl
   date.timezone = America/La_Paz
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.validate_timestamps=1
   opcache.revalidate_freq=60
   upload_max_filesize = 5M
   post_max_size = 8M
   display_errors = Off
   log_errors = On
   ```
3. Verifica con `php -m` que aparezcan `pdo_pgsql` y `intl`.
4. Apache usa el `php.ini` de la carpeta de PHP (se indica con `PHPIniDir`, punto g).

## d) Usuario y base de producción

En PowerShell (reemplaza `CONTRASEÑA_FUERTE` por una distinta a la de desarrollo):

```powershell
$env:PGPASSWORD = "postgres"
psql -h localhost -U postgres -d postgres -c "CREATE USER libreria_prod WITH PASSWORD 'CONTRASEÑA_FUERTE';"
psql -h localhost -U postgres -d postgres -c "CREATE DATABASE libreria_prod OWNER libreria_prod;"
psql -h localhost -U postgres -d postgres -c "CREATE USER libreria_restore WITH PASSWORD 'OTRA_CONTRASEÑA';"
psql -h localhost -U postgres -d postgres -c "ALTER USER libreria_restore CREATEDB;"
Remove-Item Env:PGPASSWORD
```

## e) Proyecto

```powershell
git clone https://github.com/Nahuel830/NF-Libreria.git C:\NF-Libreria
cd C:\NF-Libreria
composer install --no-dev --optimize-autoloader
Copy-Item .env.example .env
```

Edita `.env` de producción:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=http://IP-DE-LA-PC
LOG_LEVEL=warning
SESSION_DRIVER=database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=libreria_prod
DB_USERNAME=libreria_prod
DB_PASSWORD=CONTRASEÑA_FUERTE
ADMIN_PASSWORD_INICIAL=UNA_CONTRASEÑA_INICIAL
```

Luego:

```powershell
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
```

El seeder en producción crea SOLO configuración, categorías y admin
(los datos demo solo corren en entorno `local`: verificado en el punto 7).
Después:

```powershell
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## f) Permisos de escritura (cuenta del servicio Apache)

```powershell
icacls "C:\NF-Libreria\storage" /grant "SERVICIO RED:(OI)(CI)F" /T
icacls "C:\NF-Libreria\bootstrap\cache" /grant "SERVICIO RED:(OI)(CI)F" /T
```

(Si Apache corre con otra cuenta, reemplaza `SERVICIO RED` por esa cuenta.)

## g) Configurar Apache

En `C:\Apache24\conf\httpd.conf`:

```apache
ServerName localhost
ServerTokens Prod
ServerSignature Off
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule php_module "C:/ruta/a/php/php8apache2_4.dll"
PHPIniDir "C:/ruta/a/php"
AddHandler application/x-httpd-php .php
DirectoryIndex index.php index.html
DocumentRoot "C:/NF-Libreria/public"
<Directory "C:/NF-Libreria/public">
    AllowOverride All
    Require all granted
</Directory>
```

(Reemplaza `C:/ruta/a/php` por la carpeta real de PHP, con barras `/`.)
Verificar y registrar el servicio (como administrador):

```powershell
C:\Apache24\bin\httpd.exe -t
C:\Apache24\bin\httpd.exe -k install
sc.exe config Apache2.4 start= auto
```

## h) Backups

1. Crea `pgpass.conf` de la cuenta que ejecuta la tarea (ver `docs/backups.md`).
2. Copia `scripts/backup.config.example.ps1` como `scripts/backup.config.ps1`
   apuntando a `libreria_prod` (y `DbRestoreUser = "libreria_restore"`).
3. Instala la tarea programada (punto B-07 del plan de pruebas).

## i) Verificación final

1. Reinicia la PC: Apache y PostgreSQL deben arrancar solos.
2. Entra a `http://IP-DE-LA-PC`, inicia sesión con `admin` y cambia la contraseña.
3. Ejecuta un backup manual y una prueba de restauración.
4. Sigue con `docs/puesta-en-marcha.md`.
