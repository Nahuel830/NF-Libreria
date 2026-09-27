# Pruebas manuales para el usuario — NF Librería

Solo lo que no se puede automatizar. Todo lo demás ya está verificado con
tests automáticos (`docs/informe-pruebas.md`).

1. **Imprimir en la impresora térmica real**: vende y pulsa Imprimir.
   Esperado: ticket angosto, legible, nada cortado. (Ver `docs/impresion-tickets.md`.)
2. **Escanear con el lector físico**: escanea 3 códigos seguidos a la venta.
   Esperado: cantidad 3 sin tocar el mouse.
3. **Abrir desde otra PC**: entra a `http://IP-DE-LA-PC` y vende algo.
   Esperado: funciona igual; el stock se descuenta para todos.
4. **Abrir desde el celular** (mismo WiFi): entra a la IP y haz login.
   Esperado: carga el login.
5. **Reiniciar la PC principal**: al encender, los servicios arrancan solos
   y el sistema responde sin hacer nada.
6. **Tarea programada de backup**: en el Programador de tareas, ejecútala
   manualmente. Esperado: crea el archivo y el log dice OK.
7. **Doble caja real**: dos personas venden el último ítem a la vez.
   Esperado: una vende, la otra ve error de stock; nunca negativo.
8. **Sin Internet 5 minutos**: desconecta el cable/WiFi y vende, busca y abre
   reportes. Esperado: todo funciona.
9. **Exportar un CSV y abrirlo en Excel**: columnas separadas, tildes y números bien.
10. **Revisión visual general**: recorre login, venta, ticket y panel;
    todo legible, botones y menú correctos.
11. **Imprimir el cierre del día** en A4 y en la térmica.
12. **Corte de luz simulado** (apagar y encender): verifica ventas y stock
    contra el papel del día.
