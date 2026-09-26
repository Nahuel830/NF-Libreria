# Restauración de copias de seguridad

## Restaurar en la misma PC (emergencia)

1. Ubica el backup a restaurar en la carpeta de backups.
2. Abre PowerShell en la carpeta del proyecto y ejecuta:
   `.\scripts\restaurar.ps1 -Archivo "D:\Backups\NF-Libreria\nf-libreria_AAAA-MM-DD_HHMM.backup" -Base libreria_dev`
3. Escribe el nombre de la base cuando lo pida para confirmar.
4. El script primero hace un backup de seguridad del estado actual y luego restaura.
5. Verifica en el sistema que los datos estén correctos.

## Restaurar en una PC nueva

1. Instala PostgreSQL 17 (ver `docs/instalacion.md` cuando exista; si no,
   instala desde https://www.postgresql.org/download/windows/).
2. Crea el usuario y la base vacía:
   ```sql
   CREATE USER libreria_dev WITH PASSWORD 'TU_CONTRASEÑA';
   ALTER USER libreria_dev CREATEDB;
   CREATE DATABASE libreria_dev OWNER libreria_dev;
   CREATE DATABASE libreria_test OWNER libreria_dev;
   ```
3. Copia el archivo `.backup` a la PC nueva.
4. Configura `pgpass.conf` (ver `docs/backups.md`) o usa `PGPASSWORD`.
5. Restaura:
   `pg_restore -h localhost -p 5432 -U libreria_dev -d libreria_dev --clean --if-exists --no-owner --no-privileges ARCHIVO.backup`
6. Configura el `.env` del proyecto con las credenciales nuevas y ejecuta
   `php artisan migrate --force` (no debe crear nada si el backup está al día).
7. Entra al sistema y verifica ventas, stock y usuarios.

## Probar que un backup sirve (sin tocar nada real)

Ejecuta `.\scripts\probar-restauracion.ps1`: restaura en la base temporal
`libreria_restore_test`, compara conteos de `productos`, `ventas`,
`detalle_ventas`, `movimientos_stock` y `users` contra la base original y
borra la base temporal. Debe mostrar **RESTAURACIÓN OK**.
