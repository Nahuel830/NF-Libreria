<#Requires -Version 5.1
<#
.SYNOPSIS
  Restaura un backup sobre una base indicada (solo emergencias).
.DESCRIPTION
  Pide escribir el nombre de la base para confirmar y ANTES hace un
  backup de seguridad del estado actual. Requiere pgpass.conf o PGPASSWORD.
.PARAMETER Archivo
  Ruta del backup a restaurar.
.PARAMETER Base
  Nombre de la base destino.
#>

param(
    [Parameter(Mandatory = $true)][string]$Archivo,
    [Parameter(Mandatory = $true)][string]$Base
)

$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\backup.config.ps1"

if (-not (Test-Path -LiteralPath $Archivo)) {
    Write-Output "No existe el archivo: $Archivo"
    exit 1
}

$confirmacion = Read-Host "Vas a restaurar sobre la base '$Base'. Escribe el nombre de la base para confirmar"

if ($confirmacion -cne $Base) {
    Write-Output 'Confirmación incorrecta. Operación cancelada.'
    exit 1
}

Write-Output 'Haciendo backup de seguridad del estado actual...'
& "$PSScriptRoot\backup.ps1"

if ($LASTEXITCODE -ne 0) {
    Write-Output 'El backup de seguridad falló. Operación cancelada.'
    exit 1
}

Write-Output "Restaurando $Archivo sobre $Base ..."
& "$PgBin\pg_restore.exe" -h $DbHost -p $DbPort -U $DbUser -d $Base --clean --if-exists --no-owner --no-privileges $Archivo

if ($LASTEXITCODE -ne 0) {
    Write-Output "pg_restore terminó con código $LASTEXITCODE. Revisa el estado con el backup de seguridad recién creado."
    exit 1
}

Write-Output 'Restauración terminada.'
exit 0
