# Copias de seguridad (backups)

## Cómo funciona

- `scripts/backup.ps1` genera `nf-libreria_AAAA-MM-DD_HHMM.backup` (formato custom
  de PostgreSQL) en la carpeta de backups, lo valida con `pg_restore --list`,
  opcionalmente lo copia a una ubicación externa, borra los de más de 30 días y
  anota el resultado en `backup.log` y en la base de datos
  (`ultimo_backup_fecha`, `ultimo_backup_resultado`).
- El panel de inicio del admin muestra una alerta amarilla si el último backup
  tiene más de 24 horas o si el último resultado fue error.
- La tarea programada "NF-Libreria Backup" lo ejecuta todos los días a las
  13:00 y a las 20:30.

## Configurar pgpass.conf (para no escribir la contraseña)

1. Crea la carpeta `%APPDATA%\postgresql` si no existe.
2. Crea el archivo `%APPDATA%\postgresql\pgpass.conf` con una línea así
   (sin espacios alrededor de los `:`):
   `localhost:5432:libreria_dev:libreria_dev:TU_CONTRASEÑA`
3. Guarda el archivo. PostgreSQL lo usa automáticamente para `pg_dump`,
   `pg_restore` y `psql` sin pedir contraseña.

Alternativa temporal: definir la variable de entorno `PGPASSWORD` en la
terminal antes de ejecutar los scripts.

## Configurar y probar a mano

1. Copia `scripts/backup.config.example.ps1` como `scripts/backup.config.ps1`
   (este último NO se sube a git) y ajusta las rutas.
2. Ejecuta `.\scripts\backup.ps1` desde la carpeta del proyecto.
3. Revisa el archivo `.backup` creado y `backup.log` en la carpeta de backups.
4. Ejecuta `.\scripts\probar-restauracion.ps1`: debe decir **RESTAURACIÓN OK**.
   - Necesita que el usuario de BD tenga permiso CREATEDB. Si falla con error
     de permisos, conéctate como superusuario (postgres) en pgAdmin o psql y ejecuta:
     `ALTER USER libreria_dev CREATEDB;`

## Instalar la tarea programada

1. Abre PowerShell **como administrador**.
2. Ejecuta `.\scripts\instalar-tarea-backup.ps1`.
3. Verifica en el Programador de tareas de Windows que exista
   "NF-Libreria Backup" con los dos horarios.

## Dónde quedan los archivos

- Backups: la carpeta configurada (por defecto `D:\Backups\NF-Libreria`).
- Log: `backup.log` en esa misma carpeta.
- Copia externa: si se configuró `CarpetaCopiaExterna` (carpeta sincronizada
  de Google Drive, disco externo...), una copia de cada backup.

## Recomendaciones

- Mantén siempre una copia fuera de la PC (nube o disco externo).
- Prueba la restauración una vez al mes con `probar-restauracion.ps1`.
- Revisa `backup.log` cada semana.
