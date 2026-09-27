<#Requires -Version 5.1
<#
.SYNOPSIS
  Prueba la restauración del backup más reciente en una base temporal.
.DESCRIPTION
  Crea libreria_restore_test (borrándola antes si existe), restaura con
  pg_restore, compara conteos de tablas clave contra la base original y
  muestra RESTAURACIÓN OK o ERROR. Borra la base temporal al final.
  Nunca toca la base original.
  Requiere pgpass.conf o PGPASSWORD, y que el usuario tenga permiso
  CREATEDB (ver docs/backups.md).
.PARAMETER Archivo
  Ruta del backup a probar. Por defecto, el más reciente de la carpeta.
#>

param([string]$Archivo = '')

$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\backup.config.ps1"

if (-not (Get-Variable -Name DbRestoreUser -ErrorAction SilentlyContinue) -or $DbRestoreUser -eq '') {
    $DbRestoreUser = $DbUser
}

$Temporal = 'libreria_restore_test'
$Tablas = @('productos', 'ventas', 'detalle_ventas', 'movimientos_stock', 'users')

function Psql([string]$base, [string]$sql) {
    & "$PgBin\psql.exe" -h $DbHost -p $DbPort -U $DbUser -d $base -t -A -c $sql
}

function PsqlRestore([string]$base, [string]$sql) {
    & "$PgBin\psql.exe" -h $DbHost -p $DbPort -U $DbRestoreUser -d $base -t -A -c $sql
}

try {
    if ($Archivo -eq '') {
        $Archivo = Get-ChildItem -LiteralPath $CarpetaBackups -Filter 'nf-libreria_*.backup' |
            Sort-Object LastWriteTime -Descending |
            Select-Object -First 1 -ExpandProperty FullName

        if (-not $Archivo) {
            throw 'No hay backups en la carpeta.'
        }
    }

    Write-Output "Backup: $Archivo"

    PsqlRestore 'postgres' "DROP DATABASE IF EXISTS $Temporal;" | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo borrar/crear la base temporal. Verifica que el usuario tenga permiso CREATEDB (ver docs/backups.md).'
    }

    PsqlRestore 'postgres' "CREATE DATABASE $Temporal OWNER $DbRestoreUser;" | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo crear la base temporal. Verifica que el usuario tenga permiso CREATEDB (ver docs/backups.md).'
    }

    & "$PgBin\pg_restore.exe" -h $DbHost -p $DbPort -U $DbRestoreUser -d $Temporal --no-owner --no-privileges $Archivo

    if ($LASTEXITCODE -ne 0) {
        throw "pg_restore terminó con código $LASTEXITCODE."
    }

    $ok = $true

    foreach ($tabla in $Tablas) {
        $original = (Psql $DbName "SELECT COUNT(*) FROM $tabla;").Trim()
        $restaurado = (Psql $Temporal "SELECT COUNT(*) FROM $tabla;").Trim()

        if ($original -eq $restaurado) {
            $marca = 'OK'
        } else {
            $marca = 'DIFIERE'
            $ok = $false
        }

        Write-Output ("{0,-18} original={1,-8} restaurado={2,-8} {3}" -f $tabla, $original, $restaurado, $marca)
    }

    if ($ok) {
        Write-Output 'RESTAURACIÓN OK'
        exit 0
    } else {
        Write-Output 'ERROR: los conteos difieren.'
        exit 1
    }
} catch {
    Write-Output "ERROR: $($_.Exception.Message)"
    exit 1
} finally {
    PsqlRestore 'postgres' "DROP DATABASE IF EXISTS $Temporal;" | Out-Null
}
