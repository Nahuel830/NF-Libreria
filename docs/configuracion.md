# Variables del .env

| Variable | Para qué | Desarrollo | Producción |
|---|---|---|---|
| APP_NAME | Nombre que se muestra | "NF Librería" | "NF Librería" |
| APP_ENV | Entorno (`local`/`production`) | `local` | `production` |
| APP_KEY | Clave de cifrado (generada) | generada | generada |
| APP_DEBUG | Muestra errores detallados | `true` | **`false`** |
| APP_URL | URL pública del sistema | `http://localhost:8000` | `http://IP-DE-LA-PC` |
| APP_LOCALE / APP_FALLBACK_LOCALE | Idioma | `es` | `es` |
| DB_CONNECTION | Motor de BD | `pgsql` | `pgsql` |
| DB_HOST / DB_PORT | Dónde está PostgreSQL | `127.0.0.1` / `5432` | igual |
| DB_DATABASE | Base de datos | `libreria_dev` | `libreria_prod` |
| DB_USERNAME / DB_PASSWORD | Credenciales | `libreria_dev` / la de desarrollo | `libreria_prod` / fuerte y distinta |
| SESSION_DRIVER | Dónde van las sesiones | `database` | `database` |
| LOG_LEVEL | Nivel de log | `debug` | `warning` |
| ADMIN_PASSWORD_INICIAL | Contraseña inicial del admin (solo seed) | cualquiera | fuerte; se cambia al entrar |

Nunca subas el `.env` a git. En producción genera cachés después de
cambiarlo: `config:cache`, `route:cache`, `view:cache`.
