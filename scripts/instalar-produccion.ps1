<#Requires -Version 5.1
<#
.SYNOPSIS
  Instala o actualiza NF-Librería en producción (idempotente).
.DESCRIPTION
  Verifica requisitos, carpetas, composer install, .env, clave, migraciones,
  seed de producción, storage:link, cachés, permisos, firewall y tarea de
  backup. Se detiene ante el primer error. Con -Simulacion solo muestra
  lo que haría sin cambiar nada. Debe ejecutarse como administrador.
.PARAMETER Ruta
  Carpeta del proyecto. Por defecto C:\NF-Libreria.
.PARAMETER Simulacion
  Muestra los pasos sin ejecutarlos.
#>

param(
    [string]$Ruta = 'C:\NF-Libreria',
    [switch]$Simulacion
)

$ErrorActionPreference = 'Stop'
$paso = 0

function Paso([string]$nombre) {
    $script:paso++
    Write-Output "[$script:paso] $nombre"
}

function Ejecutar([string]$descripcion, [scriptblock]$accion) {
    if ($Simulacion) {
        Write-Output "    (simulación) $descripcion"
        return
    }

    try {
        & $accion
        Write-Output "    OK: $descripcion"
    } catch {
        Write-Output "    ERROR: $descripcion : $($_.Exception.Message)"
        throw
    }
}

try {
    Paso 'Verificar administrador'
    $esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

    if (-not $esAdmin -and -not $Simulacion) {
        throw 'Ejecuta esta consola como administrador.'
    }

    Write-Output '    OK: administrador'

    Paso 'Verificar requisitos'
    foreach ($comando in @('git', 'php', 'composer', 'psql')) {
        if (-not (Get-Command $comando -ErrorAction SilentlyContinue)) {
            throw "Falta el requisito: $comando."
        }
    }

    $versionPhp = "$(php -r "echo PHP_MAJOR_VERSION;").$(php -r "echo PHP_MINOR_VERSION;")"

    if ($versionPhp -ne '8.4') {
        throw "Se requiere PHP 8.4 (encontrado: $versionPhp)."
    }

    foreach ($ext in @('pdo_pgsql', 'mbstring', 'openssl', 'zip', 'intl')) {
        php -m | Select-String -Pattern "^$ext$" -Quiet | Out-Null

        if ($LASTEXITCODE -ne 0) {
            throw "Falta la extensión PHP: $ext."
        }
    }

    Write-Output '    OK: requisitos'

    Paso 'Crear carpetas'
    Ejecutar "Carpeta $Ruta" { New-Item -ItemType Directory -Path $Ruta -Force | Out-Null }

    Paso 'Actualizar código'
    Ejecutar 'git pull (si es clon existente)' {
        if (Test-Path -LiteralPath (Join-Path $Ruta '.git')) {
            git -C $Ruta pull --ff-only
        } else {
            Write-Output '    (omitido: no es clon, clónalo a mano la primera vez)'
        }
    }

    Paso 'Dependencias PHP'
    Ejecutar 'composer install --no-dev' {
        Set-Location $Ruta
        composer install --no-dev --optimize-autoloader --no-interaction
    }

    Paso 'Archivo .env'
    Ejecutar 'crear .env si no existe y pedir datos' {
        $env = Join-Path $Ruta '.env'

        if (-not (Test-Path -LiteralPath $env)) {
            Copy-Item (Join-Path $Ruta '.env.example') $env
            Write-Output '    (creado desde .env.example: completa los datos según docs/instalacion.md)'
        }
    }

    Paso 'Clave y base de datos'
    Ejecutar 'key:generate + migrate --force + seed' {
        Set-Location $Ruta

        if (-not (Select-String -LiteralPath (Join-Path $Ruta '.env') -Pattern '^APP_KEY=.+' -Quiet)) {
            php artisan key:generate --force
        }

        php artisan migrate --force
        php artisan db:seed --force
        php artisan storage:link
    }

    Paso 'Cachés'
    Ejecutar 'config/route/view:cache' {
        Set-Location $Ruta
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    }

    Paso 'Permisos de escritura'
    Ejecutar 'icacls en storage y bootstrap/cache' {
        icacls (Join-Path $Ruta 'storage') /grant 'SERVICIO RED:(OI)(CI)F' /T | Out-Null
        icacls (Join-Path $Ruta 'bootstrap\cache') /grant 'SERVICIO RED:(OI)(CI)F' /T | Out-Null
    }

    Paso 'Firewall'
    Ejecutar 'regla puerto 80 (perfil Privado)' {
        if (-not (Get-NetFirewallRule -DisplayName 'NF-Libreria HTTP' -ErrorAction SilentlyContinue)) {
            New-NetFirewallRule -DisplayName 'NF-Libreria HTTP' -Direction Inbound -Protocol TCP -LocalPort 80 -Profile Private -Action Allow | Out-Null
        }
    }

    Paso 'Tarea de backup'
    Ejecutar 'instalar tarea programada' {
        Set-Location $Ruta
        powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $Ruta 'scripts\instalar-tarea-backup.ps1')
    }

    Write-Output 'Instalación terminada sin errores.'
    exit 0
} catch {
    Write-Output "Instalación detenida: $($_.Exception.Message)"
    exit 1
}
