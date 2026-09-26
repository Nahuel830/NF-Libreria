# Diseño — NF Librería

Guía para mantener la identidad visual. Todo funciona sin Internet
(fuente, CSS, JS e íconos están en `public/vendor/`).

## Paleta (`public/css/app.css`)

| Uso | Color |
|---|---|
| Principal (azul tinta) | `#1F4E79` |
| Principal oscuro | `#163A5C` |
| Acento (amarillo lápiz) | `#E0A526` |
| Éxito | `#2E7D32` |
| Error | `#C62828` |
| Advertencia | `#E67E22` (solo fondos con texto oscuro `#1F2933`) |
| Fondo | `#F5F6F8` |
| Superficie (tarjetas) | `#FFFFFF` |
| Texto / secundario | `#1F2933` / `#5B6770` |
| Bordes | `#DDE2E7` |

Están como variables `--nf-*` y sobrescriben las de Bootstrap (`--bs-primary`,
botones, enlaces, foco de formularios). Contraste verificado WCAG AA:
texto y botones blancos sobre los colores principales; el naranja solo se usa
con texto oscuro. No agregues colores nuevos: reutiliza estos.

## Tipografía

Inter local (`public/vendor/fonts/inter/`, pesos 400/500/600/700) con
`@font-face`; respaldo `system-ui, "Segoe UI", Roboto, Arial`. Base 15px,
títulos en peso 600.

## Logo y favicon

- `public/img/logo.svg` (color) y `public/img/logo-bn.svg` (una tinta, ticket).
- `public/favicon.svg` + `public/favicon.ico`.
- El admin puede subir el logo del negocio (PNG/JPG, máx. 1 MB) en
  Configuración → se guarda en `storage/app/public/logo/` (clave
  `logo_negocio`) y hay botón para quitarlo. Nunca aceptar SVG subido.
- Usa `logo_url()` en las vistas: devuelve el del negocio o el de por defecto.

## Componentes (`resources/views/components/`)

- `x-page-header`: título, subtítulo, `migas` (`['Texto' => url]`) y acciones a la derecha.
- `x-card`: tarjeta con `titulo` opcional y slot `pie`.
- `x-estado`: ACTIVO/COMPLETADA/REGISTRADA verde, INACTIVO gris, ANULADA y SIN STOCK rojo, STOCK BAJO naranja.
- `x-dinero`: `bs()` a la derecha con números tabulares.
- `x-empty-state`: `icono`, `mensaje`, `accion-url` + `accion-texto` opcionales.
- `x-confirmar`: modal único en el layout. No se usa directo: los formularios
  llevan `data-confirm="mensaje"` (más `data-motivo` para pedir motivo mín. 5,
  `data-titulo-confirm`, `data-texto-confirm`, `data-color-confirm`) y
  `public/js/app.js` lo muestra. Para acciones JS puras:
  `window.pedirConfirmacion({titulo, mensaje, textoBoton, color, alConfirmar})`.
- `x-alertas`: toasts arriba a la derecha (éxito/info se cierran a los 4 s).
- `x-filtros`: `accion` + campos; colapsable en pantallas chicas.
- Tablas: clase `tabla-nf` (encabezado gris, hover, `.monto` a la derecha),
  acciones con íconos + `title`, dentro de `.table-responsive`.

## Reglas para pantallas nuevas

1. Extender `layouts.app`, título con `@section('titulo', ...)`.
2. Encabezado con `x-page-header` (con migas) y acciones a la derecha.
3. Formularios: etiqueta arriba, `*` en obligatorios, `@error` bajo el campo,
   botones `Guardar` (principal) + `Cancelar`/`Volver` (secundario) al final.
4. Listados: `x-filtros`, tabla `tabla-nf`, `x-empty-state` si está vacío.
5. Montos siempre con `x-dinero`; estados con `x-estado`.
6. Confirmaciones solo con `data-confirm` (nunca `confirm()`).
7. Revisar la pantalla en `/estilos` (solo admin, solo local) si agregas un
   componente nuevo.
