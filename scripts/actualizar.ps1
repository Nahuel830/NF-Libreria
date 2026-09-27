<#Requires -Version 5.1
<#
.SYNOPSIS
  Actualiza NF-Librería en producción a la última versión de main.
.DESCRIPTION
  Backup previo obligatorio, modo mantenimiento, git pull, dependencias,
  migraciones, cachés y vuelta en línea. Si algo falla, deja el sistema
  en mantenimiento y muestra cómo volver atrás.
.PARAMETER Ruta
  Carpeta del proyecto. Por defecto C:\NF-Libreria.
#>

param([string]$Ruta = 'C:\NF-Libreria')

$ErrorActionPreference = 'Stop'
Set-Location $Ruta

function Fallar([string]$mensaje) {
    Write-Output $mensaje
    Write-Output 'El sistema quedó en mantenimiento. Para volver atrás:'
    Write-Output '  1. git checkout <commit-anterior>'
    Write-Output '  2. Restaura el backup recién creado (docs/restauracion.md).'
    Write-Output '  3. php artisan up'
    exit 1
}

try {
    if ((git -C $Ruta status --porcelain) -ne '') {
        throw 'Hay cambios locales sin commit. Guárdalos o descártalos antes.'
    }

    Write-Output '[1] Backup previo...'
    powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $Ruta 'scripts\backup.ps1')

    if ($LASTEXITCODE -ne 0) {
        throw 'El backup previo falló. No se continúa.'
    }

    $anterior = git -C $Ruta rev-parse HEAD

    Write-Output '[2] Modo mantenimiento...'
    php artisan down --message="Actualizando el sistema, vuelve en unos minutos" --retry=60

    try {
        Write-Output '[3] git pull...'
        git -C $Ruta pull --ff-only

        Write-Output '[4] Dependencias...'
        composer install --no-dev --optimize-autoloader --no-interaction

        Write-Output '[5] Migraciones...'
        php artisan migrate --force

        Write-Output '[6] Cachés...'
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    } catch {
        Fallar "Falló la actualización: $($_.Exception.Message) (commit anterior: $anterior)"
    }

    Write-Output '[7] En línea...'
    php artisan up

    $nueva = Get-Content (Join-Path $Ruta 'VERSION')
    Write-Output "Actualizado a la versión $nueva."
    exit 0
} catch {
    Fallar $_.Exception.Message
}
