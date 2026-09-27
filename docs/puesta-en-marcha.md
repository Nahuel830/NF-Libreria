# Puesta en marcha — primeros días en la librería

## Antes de abrir

1. Instalar siguiendo `docs/instalacion.md` (o el despliegue web cuando exista).
2. Cargar las categorías reales (Inventario → Categorías).
3. Importar los productos con la plantilla CSV (`docs/importacion-productos.md`).
4. Hacer el **conteo físico**: Inventario → Conteo físico (se puede descargar
   la hoja de conteo en CSV para contar en papel y luego cargarla por
   categoría). Revisar los ajustes en el kardex de cada producto.
5. Crear los usuarios reales (**2 admins** para poder restablecerse
   contraseñas mutuamente) y desactivar o cambiar los demo.
6. Configurar datos del negocio, logo y mensaje del ticket.
7. Probar la impresora de tickets (`docs/impresion-tickets.md`).
8. Ejecutar `php artisan sistema:estado`: debe decir OK en todo y sin datos demo.

## Primera semana

- Usar el sistema **EN PARALELO** con el método actual (cuaderno, Excel...).
- Al cierre de cada día: comparar el "Cierre del día" del sistema
  (Reportes → Cierre del día) con el efectivo real contado y con el registro
  anterior. Anotar cualquier diferencia.
- Revisar que el backup automático se ejecuta (panel del admin) y probar una
  restauración de prueba una vez.

## Criterio para dejar el método anterior

**5 días seguidos sin diferencias inexplicadas.** Recién entonces el sistema
pasa a ser el registro oficial.

## Si el sistema falla en medio del día

1. Seguir anotando las ventas **en papel** (fecha, productos, cantidades, total).
2. No intentar arreglos apresurados en la PC.
3. Cuando vuelva a funcionar, cargar las ventas en orden desde la pantalla
   de venta (se registrarán con la fecha actual; anotar la fecha real en
   Observaciones).
4. Avisar al encargado/admin para revisar stock y auditoría al cierre.
