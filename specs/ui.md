# Spec: UI — Layouts, Alpine.js, Turbo Drive, Service Worker

## Turbo Drive

Integrado con `@hotwired/turbo` para navegación SPA sin recargas completas.

### Configuración (`resources/js/app.js`)
```js
import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Turbo.config.drive.progressBarDelay = 0; // barra visible desde el primer instante
Alpine.start();
```

Alpine.js v3.15+ es compatible con Turbo sin configuración extra — su MutationObserver observa `document` (no `body`), por lo que detecta correctamente los nuevos elementos tras cada reemplazo de `<body>`.

### Barra de progreso
Turbo muestra su propia barra durante la navegación. Estilizada en `resources/css/app.css`:
```css
.turbo-progress-bar {
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a78bfa, #818cf8);
    box-shadow: 0 0 10px rgba(139, 92, 246, 0.9);
}
```
La barra CSS interna del layout (`@keyframes slm-load`) sigue activa y se reproduce al cargarse el nuevo `<body>`, funcionando como confirmación visual de que la página terminó de renderizar.

### Reglas en vistas
- **`data-turbo="false"`** en todos los links de descarga de archivos (`/reports/earnings/export`, `/reports/students/export`) — Turbo no puede manejar respuestas binarias.
- **`@push('head') <meta name="turbo-cache-control" content="no-cache"> @endpush`** en `attendance/take.blade.php` y `attendance/take-student.blade.php` — evita que Turbo cachee el estado mutable de los toggles de asistencia.
- Ambos layouts exponen `@stack('head')` justo antes de `@vite(...)` para que las vistas puedan inyectar metas en `<head>`.

### CSRF
Ambos layouts incluyen `<meta name="csrf-token" content="{{ csrf_token() }}">`. Los formularios Blade con `@csrf` funcionan correctamente porque Turbo los envía via fetch incluyendo el campo `_token` del body.

### No cachear HTML con tokens de sesión
Las páginas HTML siempre van a la red (network-first en el SW). **No usar stale-while-revalidate para páginas con `@csrf`** — el HTML cacheado tendría un token de sesión expirado y el formulario fallaría con error 419.

### Splash screen y Turbo Drive
El splash screen usa `document.readyState` para detectar si el DOM ya está listo en lugar de `DOMContentLoaded`, porque ese evento **no se dispara en navegaciones Turbo** (solo en la carga inicial del documento). Patrón correcto:
```js
if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', hideSplash);
} else {
    hideSplash(); // Turbo navigation: DOM ya disponible
}
```

---

## Layouts

### `layouts/app.blade.php` (admin)
- Navegación inferior fija: Inicio · Alumnos · Asistencia · Cursos · Reportes (`z-40`)
- Flash messages en `position:fixed; bottom:5rem` con `z-index:50`, auto-dismiss 3–4s via Alpine
- Logo en cabeceras enlaza a `route('dashboard')`
- Fondo fijo: `public/images/fondo.jpg` con overlay oscuro (`rgba(0,0,0,0.25)`), posicionado con `position:fixed; inset:0; z-index:1/2`
- Contenido principal en `<main z-index:3>` con `max-w-lg mx-auto` (centrado en pantallas anchas)
- PWA: registra service worker `/sw.js` y apunta a `/manifest.json`; incluye `apple-touch-icon`, meta `theme-color` y `apple-mobile-web-app-*`
- `<meta name="csrf-token">` en `<head>` para Turbo Drive
- `@stack('head')` justo antes de `@vite(...)` — permite a las vistas inyectar metas (ej: `turbo-cache-control`)
- Barra de progreso superior: CSS puro con `@keyframes slm-load` (`transform:scaleX`), `z-index:99999`, corre automáticamente al renderizarse el nuevo `<body>` en cada navegación Turbo
- Splash screen (`#slm-splash`, `z-index:99998`): logo + nombre + tres puntos animados; visible solo en apertura en frío (sessionStorage `slm_s` vacío); mínimo 900ms, fade out 450ms; usa `document.readyState` para compatibilidad con Turbo Drive
- Favicons: `favicon.ico` (16/32/48px), `favicon-16x16.png`, `favicon-32x32.png` en `public/`

### `layouts/student.blade.php` (portal alumno)
- Sin navegación inferior ni botón logout — solo contenido
- Flash messages en `position:fixed; bottom:1.5rem`
- `<meta name="csrf-token">` en `<head>` y `@stack('head')` antes de `@vite(...)`

### Imágenes de cursos
Archivos en `public/images/`: `salsa.jpg`, `bachata.jpg`, `lady.jpg`. Se asignan por nombre del curso:
```php
$img = str_contains(strtolower($clase->name), 'salsa')   ? 'salsa.jpg'
     : (str_contains(strtolower($clase->name), 'bachata') ? 'bachata.jpg'
     : (str_contains(strtolower($clase->name), 'lady')    ? 'lady.jpg'
     : null));
```
Si no hay imagen, se muestra un avatar circular con la inicial. Este patrón está presente en dashboard, attendance/take, attendance/take-student, clases/index, clases/edit, clases/enroll, reports/index y reports/clase.

### Modales con Alpine.js
Los modales con `position:fixed` deben usar **inline style** para el posicionamiento (no clases Tailwind) porque Tailwind JIT a veces no compila clases usadas solo en modales. Alpine gestiona el `display` del `x-show` via inline style — poner `display:flex` en clase CSS (`class="flex ..."`) no en el inline style, para que Alpine pueda ocultarlo/mostrarlo correctamente.

```html
<div x-show="modalOpen"
     class="flex items-center justify-center"
     style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background-color:rgba(0,0,0,0.5);"
     @click.self="modalOpen = false">
```

---

## Alpine.js — patrones y trampas conocidas

### Arrow functions con `>` en atributos HTML
`>` dentro de un atributo HTML cierra el tag. Si la lógica necesita comparaciones con `>`, mover el componente a `Alpine.data('nombre', () => ({...}))` en un `<script>` y referenciar con `x-data="nombre"`.

### Reactividad tras `array.push()`
Después de `this.students.push(obj)`, el objeto original no es reactivo. Obtener la referencia proxy:
```js
this.students.push({...});
var student = this.students[this.students.length - 1]; // proxy reactivo
```

### Datos PHP → Alpine (JSON en atributos)
`{{ $collection->toJson() }}` pasa por `htmlspecialchars` de Blade — los `"` se escapan a `&quot;` en el HTML pero el browser los decodifica correctamente para Alpine.

### `Alpine.data()` con Turbo Drive
`document.addEventListener('alpine:init', ...)` solo se dispara una vez en la carga inicial. En navegaciones Turbo Drive el script se re-ejecuta pero Alpine ya está inicializado, por lo que `alpine:init` nunca vuelve a disparar y el componente no queda registrado. Patrón correcto:
```js
(function () {
    function register() {
        Alpine.data('nombreComponente', () => ({ ... }));
    }
    if (window.Alpine) {
        register(); // Turbo navigation: Alpine ya está inicializado
    } else {
        document.addEventListener('alpine:init', register);
    }
})();
```
Aplicado en `clases/create.blade.php` y `clases/edit.blade.php` (`scheduleSelector`).

---

## Service Worker (`public/sw.js`) — versión `slm-v3`

| Tipo de recurso | Estrategia |
|---|---|
| `/build/`, `/images/`, `/icons/` | Cache-first (assets con hash o raramente cambiados) |
| Páginas HTML | Network-first, fallback a cache si offline |

**Actualizar caché de assets**: cambiar `const CACHE = 'slm-vX'` al desplegar si se modificaron imágenes o íconos sin cambiar su nombre. Los assets de `/build/` no necesitan bumping manual — Vite genera nombres con hash de contenido.

**No cachear páginas HTML** con stale-while-revalidate — los tokens CSRF embebidos quedarían obsoletos causando errores 419.
