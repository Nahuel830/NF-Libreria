# Red local — PC principal y cajas

## IP fija para la PC principal

**Opción A — reserva DHCP en el router (recomendada).** Entra a la
configuración del router (normalmente `http://192.168.1.1`), busca
"Reserva DHCP" o "Dirección estática", y reserva siempre la misma IP
(ej: `192.168.1.100`) para la dirección MAC de la PC principal.
Si no administras el router, pide estos datos a quien lo administre:
IP reservada + máscara + puerta de enlace.

**Opción B — IP estática en Windows.** Configuración → Red e Internet →
tu conexión → Propiedades → Asignación de IP → Editar → Manual → IPv4:
IP `192.168.1.100`, máscara `255.255.255.0`, puerta `192.168.1.1`,
DNS `192.168.1.1`. Ajusta los números a tu red.

## Red privada

La red de la librería debe estar marcada como **Privada** en Windows:
Configuración → Red e Internet → tu conexión → Tipo de perfil de red →
Privada.

## Firewall de Windows

Permite el puerto 80 entrante SOLO en redes privadas (PowerShell como
administrador):

```powershell
New-NetFirewallRule -DisplayName "NF-Libreria HTTP" -Direction Inbound `
  -Protocol TCP -LocalPort 80 -Profile Private -Action Allow
```

Verifica que el puerto **5432 NO esté abierto**: la base de datos solo la
usa la aplicación en la misma PC. Compruébalo con:

```powershell
Get-NetFirewallRule | Where-Object { $_.DisplayName -like "*postgres*" }
```

(Si existe una regla que abra el 5432, desactívala.)

## Acceso desde otra PC

1. En la PC secundaria abre el navegador y entra a `http://IP-DE-LA-PC-PRINCIPAL`
   (ej: `http://192.168.1.100`).
2. Crea un acceso directo en el escritorio con esa dirección y el logo
   (clic derecho → Nuevo → Acceso directo).
3. Navegador recomendado: Chrome o Edge actualizado.
4. En la PC de caja configura la impresora de tickets (ver
   `docs/impresion-tickets.md`).

## Si una PC no conecta

1. ¿Están en la misma red/WiFi?
2. ¿Responde el ping? `ping IP-DE-LA-PC-PRINCIPAL`.
3. ¿Cambió la IP de la PC principal? Revisa que la reserva siga vigente.
4. ¿El firewall bloquea? Revisa la regla del puerto 80 y el perfil Privado.
5. ¿Está detenido Apache? En la PC principal revisa el servicio Apache2.4.
