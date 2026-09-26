# Impresión de tickets en impresora térmica

El ticket (`/ventas/{id}/ticket`) está diseñado para papel térmico de 80 mm
(también se ve bien en 58 mm): fuente monoespaciada, `@page { size: 80mm auto; margin: 0 }`
y sin menú. Si en Configuración se activa "Imprimir ticket automáticamente",
el ticket llama a `window.print()` al cargar.

## Configurar la impresora térmica en Windows

1. Instala el controlador de la impresora (sigue el manual del fabricante) y
   comprueba que imprime una página de prueba.
2. En Panel de control → Dispositivos e impresoras → clic derecho en la
   impresora → Preferencias de impresión: elige el tamaño de papel de 80 mm
   (o 58 mm según tu rollo).
3. Márcala como impresora predeterminada en la PC de caja.

## Configurar el navegador (Chrome o Edge)

1. Abre el ticket de una venta y pulsa **Imprimir**.
2. En el diálogo de impresión elige la impresora térmica y ajusta:
   - Márgenes: **Ninguno**.
   - Encabezados y pies de página: **desactivados**.
   - Escala: **100%**.
   - Tamaño de papel: el del rollo (80 mm o 58 mm).
3. Imprime una prueba y verifica que nada se corte a los lados.

## Imprimir sin diálogo (modo quiosco)

Para que el ticket salga directamente al cobrar:

1. Activa "Imprimir ticket automáticamente" en Configuración.
2. Cierra el navegador y vuelve a abrirlo con el modo quiosco de impresión:
   - Chrome: `chrome.exe --kiosk-printing http://IP-DE-LA-PC-PRINCIPAL`
   - Edge: `msedge.exe --kiosk-printing http://IP-DE-LA-PC-PRINCIPAL`
3. Crea un acceso directo en el escritorio de la caja con ese destino.

Sin impresora térmica puedes probar con "Microsoft Print to PDF".
