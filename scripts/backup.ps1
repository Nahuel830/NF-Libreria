<#Requires -Version 5.1
<#
.SYNOPSIS
  Copia de seguridad de la base de datos NF-Librería con pg_dump.
.DESCRIPTION
  Genera nf-libreria_AAAA-MM-DD_HHMM.backup en formato custom, lo valida,
  opcionalmente lo copia a una ubicación externa, borra los antiguos y
  registra el resultado en backup.log y en la base de datos.
  La autenticación con PostgreSQL se hace vía pgpass.conf o la variable
  de entorno PGPASSWORD. Nunca guarda contraseñas.
#>

$ErrorActionPreference = 'Stop'

$Proyecto = Split-Path $PSScriptRoot -Parent
. "$PSScriptRoot\backup.config.ps1"

function Registrar-Log([string]$mensaje) {
    $linea = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') $mensaje"
    Add-Content -LiteralPath (Join-Path $CarpetaBackups 'backup.log') -Value $linea
    Write-Output $linea
}

function Registrar-Resultado([string]$estado, [string]$mensaje) {
    try {
        & php "$Proyecto\artisan" backup:registrar-resultado $estado $mensaje 2>&1 | Out-String | Write-Output
    } catch {
        Write-Output "Aviso: no se pudo registrar el resultado en la BD: $_"
    }
}

try {
    if (-not (Test-Path -LiteralPath "$PgBin\pg_dump.exe")) {
        throw "No se encontró pg_dump.exe en $PgBin."
    }

    if (-not (Test-Path -LiteralPath $CarpetaBackups)) {
        New-Item -ItemType Directory -Path $CarpetaBackups -Force | Out-Null
    }

    $nombre = "nf-libreria_$(Get-Date -Format 'yyyy-MM-dd_HHmm').backup"
    $ruta = Join-Path $CarpetaBackups $nombre

    & "$PgBin\pg_dump.exe" -h $DbHost -p $DbPort -U $DbUser -d $DbName -Fc -f $ruta

    if ($LASTEXITCODE -ne 0) {
        throw "pg_dump terminó con código $LASTEXITCODE."
    }

    $info = Get-Item -LiteralPath $ruta

    if ($info.Length -le 0) {
        throw "El backup quedó vacío: $ruta."
    }

    & "$PgBin\pg_restore.exe" --list $ruta | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw "El backup no pasó la validación con pg_restore --list."
    }

    if ($CarpetaCopiaExterna -ne '') {
        if (Test-Path -LiteralPath $CarpetaCopiaExterna) {
            Copy-Item -LiteralPath $ruta -Destination (Join-Path $CarpetaCopiaExterna $nombre) -Force
            Registrar-Log "OK copia externa: $nombre"
        } else {
            Registrar-Log "ADVERTENCIA copia externa no disponible: $CarpetaCopiaExterna"
        }
    }

    $limite = (Get-Date).AddDays(-$DiasRetencion)

    Get-ChildItem -LiteralPath $CarpetaBackups -Filter 'nf-libreria_*.backup' |
        Where-Object { $_.LastWriteTime -lt $limite } |
        ForEach-Object {
            Remove-Item -LiteralPath $_.FullName -Force
            Registrar-Log "OK borrado antiguo: $($_.Name)"
        }

    $mb = [math]::Round($info.Length / 1MB, 2)
    Registrar-Log "OK backup: $nombre ($mb MB)"
    Registrar-Resultado 'ok' "$nombre ($mb MB)"
    exit 0
} catch {
    $mensaje = $_.Exception.Message
    try { Registrar-Log "ERROR backup: $mensaje" } catch { Write-Output "ERROR backup: $mensaje" }
    try { Registrar-Resultado 'error' $mensaje } catch { }
    exit 1
}
