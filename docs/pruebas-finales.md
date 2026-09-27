# Pruebas finales — día de instalación en la librería

Marcar cada casilla antes de usar el sistema con ventas reales.

- [ ] La PC reiniciada levanta sola PostgreSQL y Apache; el sistema responde sin hacer nada.
- [ ] Acceso desde cada PC secundaria por `http://IP-DE-LA-PC-PRINCIPAL`.
- [ ] Login con los 2 usuarios admin reales + cambio de contraseña.
- [ ] Cajeros reales creados; los demo desactivados o eliminados.
- [ ] `php artisan sistema:estado`: OK en todo y sin datos demo.
- [ ] Impresión de tickets en la impresora térmica real (ancho y corte correctos).
- [ ] Lector de código de barras: escanea 3 productos seguidos a la venta.
- [ ] Venta de prueba en efectivo con cambio + anulación de prueba (luego `sistema:limpiar-demo` si ensuciaron datos, o mejor: reinstalar limpio).
- [ ] Backup automático ejecutado (revisar archivo y log).
- [ ] Restauración de prueba: RESTAURACIÓN OK.
- [ ] Conteo físico inicial cargado; kardex con movimientos INICIAL.
- [ ] Cierre del día cuadra con el efectivo contado.
- [ ] Usuarios capacitados con los manuales (cajero + administrador).
