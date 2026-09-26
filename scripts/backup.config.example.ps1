# Copia de ejemplo de la configuración de backups. NO pongas contraseñas aquí.
# Copia este archivo como backup.config.ps1 y ajusta los valores.
# La contraseña de PostgreSQL se lee de pgpass.conf (ver docs/backups.md).

$PgBin = "C:\Program Files\PostgreSQL\17\bin"
$DbHost = "localhost"
$DbPort = 5432
$DbName = "libreria_dev"
$DbUser = "libreria_dev"
$CarpetaBackups = "D:\Backups\NF-Libreria"
# Carpeta sincronizada (Google Drive, disco externo...). Vacío = no copiar.
$CarpetaCopiaExterna = ""
$DiasRetencion = 30
