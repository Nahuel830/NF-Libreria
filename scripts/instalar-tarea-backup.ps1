<#Requires -Version 5.1
<#Requires -RunAsAdministrator
<#
.SYNOPSIS
  Registra la tarea programada "NF-Libreria Backup" (diaria 13:00 y 20:30).
.DESCRIPTION
  Debe ejecutarse como administrador. La tarea ejecuta scripts\backup.ps1,
  intenta correr aunque el usuario no haya iniciado sesión y se ejecuta
  lo antes posible si se perdió una ejecución.
#>

$ErrorActionPreference = 'Stop'

$Proyecto = Split-Path $PSScriptRoot -Parent
. "$PSScriptRoot\backup.config.ps1"

$nombre = 'NF-Libreria Backup'
$accion = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$PSScriptRoot\backup.ps1`""
$disparadores = @(
    (New-ScheduledTaskTrigger -Daily -At '13:00'),
    (New-ScheduledTaskTrigger -Daily -At '20:30')
)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
$ajustes = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

$existente = Get-ScheduledTask -TaskName $nombre -ErrorAction SilentlyContinue

if ($existente) {
    Set-ScheduledTask -TaskName $nombre -Action $accion -Trigger $disparadores -Settings $ajustes -Principal $principal
    Write-Output "Tarea '$nombre' actualizada."
} else {
    Register-ScheduledTask -TaskName $nombre -Action $accion -Trigger $disparadores -Settings $ajustes -Principal $principal -Description 'Copia diaria de la base NF-Librería (13:00 y 20:30).'
    Write-Output "Tarea '$nombre' creada."
}

Write-Output "Proyecto: $Proyecto"
Write-Output "Backups en: $CarpetaBackups"
