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

## Versión web (WEB-1)

- **TOTP**: obligatorio para admin, opcional para encargado, no disponible para cajero. Se configura en "Mi seguridad"; el admin puede restablecer el de otro usuario (queda obligado a configurarlo de nuevo).
- **Restricción del cajero por IP** (opcional, desactivada por defecto): Administración → Configuración → "Restringir cajeros por IP" + lista de IPs o rangos CIDR. Si el cajero entra desde otra IP, el login se rechaza con mensaje claro y auditoría. Admin y encargado nunca se restringen.
- **Ventas de contingencia**: solo admin/encargado pueden marcar "Venta registrada en papel durante un corte" con fecha real (máx. 7 días atrás). Quedan marcadas "Contingencia" en historial, detalle, ticket y cierre.
