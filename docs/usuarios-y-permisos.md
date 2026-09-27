# Usuarios y permisos — NF Librería

Roles: `admin` (Administrador), `encargado` (Encargado), `cajero` (Cajero).
Se inicia sesión con el campo `usuario` (en minúsculas), no con correo.

## Matriz de permisos

| Permiso | admin | encargado | cajero |
|---|---|---|---|
| gestionar-usuarios | ✅ | ❌ | ❌ |
| gestionar-configuracion | ✅ | ❌ | ❌ |
| ver-auditoria | ✅ | ❌ | ❌ |
| gestionar-categorias | ✅ | ✅ | ❌ |
| gestionar-productos | ✅ | ✅ | ❌ |
| ver-productos | ✅ | ✅ | ✅ |
| gestionar-stock | ✅ | ✅ | ❌ |
| registrar-entradas | ✅ | ✅ | ❌ |
| gestionar-proveedores | ✅ | ✅ | ❌ |
| gestionar-clientes | ✅ | ✅ | ❌ |
| realizar-ventas | ✅ | ✅ | ✅ |
| ver-todas-las-ventas | ✅ | ✅ | ❌ |
| anular-ventas | ✅ | ✅ | ❌ |
| aplicar-descuentos | ✅ | ✅ | ❌ |
| ver-reportes | ✅ | ✅ | ❌ |

Notas:

- El cajero solo ve sus propias ventas del día (no tiene `ver-todas-las-ventas`).
- Los permisos se verifican en el servidor con Gates en cada ruta y acción, no solo ocultando botones (`@can` en las vistas es solo presentación).
- Middleware reutilizable por rol: `->middleware('rol:admin,encargado')`.
