# Control de Asistencias — Academia de Baile

App mobile-first para que el profesor registre asistencias desde el celular. Sin personal adicional.

**Stack:** Laravel 11 · SQLite · Tailwind CSS (JIT) · Blade · Alpine.js v3 · Hotwire Turbo Drive · Vite

---

## Comandos habituales

```bash
php artisan serve          # servidor en http://localhost:8000
npm run dev                # Vite (Tailwind JIT + HMR)
php artisan migrate        # correr migraciones pendientes
php artisan migrate:fresh  # reiniciar BD completa
```

---

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

## Base de datos (SQLite)

| Tabla | Descripción |
|---|---|
| `students` | id, name, dni (unique nullable), phone, notes, active (bool) |
| `clases` | id, name, schedule (JSON text), description, active (bool) |
| `clase_student` | clase_id, student_id, enrolled_at (date nullable) — pivot |
| `attendances` | id, clase_id, student_id, plan_id (FK nullable), date, present (bool) — UNIQUE(clase_id, student_id, date) |
| `student_plans` | id, student_id, start_date, end_date, class_quota, classes_remaining (nullable), price, promotion (nullable), deleted_at (soft delete) |
| `settings` | key (PK string), value |

### Columna `schedule` en `clases`
Tipo `text`, cast `'array'` en el modelo. Formato JSON:
```json
{"lun": {"start": "18:00", "end": "19:30"}, "mie": {"start": "18:00", "end": "19:30"}}
```
Claves de día: `lun mar mie jue vie sab dom`.

### Tabla `settings`
Clave primaria string. Registros actuales:

| Grupo | Claves |
|---|---|
| Precios | `price_8h`, `price_12h`, `price_16h`, `price_24h`, `price_full1`, `price_full2` |
| Promociones | `promo_10`, `promo_20`, `promo_30`, `promo_2x1` |
| Notificaciones WA | `notify_days_before`, `notify_classes_remaining`, `notify_message`, `notify_expired_message` |
| Reportes | `show_earnings` (bool; 0 = oculto, 1 = visible en /reports) |
| Seguridad | `app_pin` (PIN admin; si no existe, cae a `env('APP_PIN', '1234')`) |

Usar `Setting::get('key', default)` y `Setting::set('key', value)`.

### Columna `plan_id` en `attendances`
FK nullable a `student_plans`. Se asigna al guardar/toggle asistencia buscando el plan activo del alumno en esa fecha. Permite calcular `classesUsed()` sin range scan de fechas. `nullOnDelete` — si se cancela el plan el registro de asistencia se conserva con `plan_id = null`.

### Columna `classes_remaining` en `student_plans`
Entero nullable (null para `full1`/`full2`). Se mantiene incrementando/decrementando en cada cambio de asistencia desde `AttendanceController`. Evita consultar `attendances` para calcular clases restantes — `classesRemaining()` y `status()` leen directamente este campo.
- Al crear un plan, se inicializa con el valor de `class_quota` (ej: `8` → `classes_remaining = 8`)
- `adjustRemaining($planId, $delta)` aplica `MAX(0, classes_remaining + delta)` — nunca baja de 0

### Índice en `attendances`
Índice compuesto `(student_id, present, date)` — útil para consultas históricas sobre asistencias.

### Columna `promotion` en `student_plans`
Almacena la clave de la promoción aplicada al crear el plan. Valores posibles: `null`, `'promo_10'`, `'promo_20'`, `'promo_30'`, `'promo_2x1'`.

### Soft delete en `student_plans`
Los planes eliminados no se borran físicamente — se marca `deleted_at`. Usar `withTrashed()` para incluirlos en el historial. `currentPlan()` excluye automáticamente los soft-deleted.

### Fechas en `attendances`
Guardadas como string `YYYY-MM-DD` (sin cast a date en el modelo). Usar siempre `Carbon::parse($date)->toDateString()` al guardar.

---

## Modelos

### `Student`
- `fillable`: name, dni, phone, notes, active
- `casts`: active → boolean
- Relaciones: `clases()` BelongsToMany, `attendances()` HasMany, `plans()` HasMany, `currentPlan()` HasOne (latestOfMany start_date)

### `Clase`
- `fillable`: name, schedule, description, active
- `casts`: active → boolean, schedule → array
- `scheduleText(): string` — devuelve HTML con días y horas formateados (usar `{!! !!}` en vistas)
- Relaciones: `students()` BelongsToMany (ordenados por name), `attendances()` HasMany

### `StudentPlan`
- `fillable`: student_id, start_date, end_date, class_quota, classes_remaining, price, promotion
- `casts`: price → decimal:2
- Traits: `HasFactory`, `SoftDeletes`
- `class_quota`: string — valores `'8' | '12' | '16' | '24' | 'full1' | 'full2'`
- `classes_remaining`: entero nullable — null para `full1`/`full2`; mantenido por `AttendanceController`
- `promotion`: string nullable — valores `'promo_10' | 'promo_20' | 'promo_30' | 'promo_2x1' | null`
- `PROMOTION_LABELS`: constante array que mapea clave → etiqueta legible
- Métodos: `status()` → `'ok' | 'exhausted' | 'expired' | 'pending'`, `classesUsed()` (deriva de quota - remaining, sin query), `classesRemaining()` (lee `$this->classes_remaining`, sin query), `canAttend()`, `promotionLabel(): ?string`

### `Attendance`
- `fillable`: clase_id, student_id, plan_id, date, present, notes
- Relaciones: `clase()`, `student()`, `plan()` BelongsTo StudentPlan

### `Setting`
- PK string (`key`), `$incrementing = false`
- `Setting::get('key', default)` / `Setting::set('key', value)`
- `Setting::preload(array $keys)` — carga varias claves en una sola query `WHERE key IN (...)` y las mete al caché; usar antes de llamar `get()` múltiples veces en un mismo request
- Cache en memoria (`static $cache[]`): cada clave se consulta una sola vez por request

---

## Rutas

### Portal alumno (público, sin autenticación)
```
GET  /                    StudentPortalController@publicSearch  ← búsqueda pública por DNI
GET  /student/lookup      StudentPortalController@lookup        ← endpoint AJAX JSON (dni=?)
```

### Admin (protegidas por `check.pin`)
```
GET/POST  /login                         PinController (auth PIN)
POST      /logout                        PinController@logout

GET       /admin                         DashboardController@index
GET       /settings                      SettingController@edit
POST      /settings                      SettingController@update

GET/POST  /students                      StudentController (index, create, store, edit, update, destroy)
GET       /students/{student}/plans      StudentPlanController@index
POST      /students/{student}/plans      StudentPlanController@store
DELETE    /students/{student}/plans/{plan} StudentPlanController@destroy

GET/POST  /clases                        ClaseController (index, create, store, edit, update, destroy)
GET/POST  /clases/{clase}/enroll         EnrollmentController (edit, update)

GET       /attendance                    AttendanceController@index         ← buscador de alumnos
GET       /attendance/student/{student}  AttendanceController@takeByStudent ← cursos del día del alumno
GET       /attendance/{clase}/take       AttendanceController@take
POST      /attendance/{clase}/save       AttendanceController@save
POST      /attendance/{clase}/toggle     AttendanceController@toggle
POST      /attendance/{clase}/add-student AttendanceController@addStudent

GET       /import                        ImportController@show
POST      /import                        ImportController@import

GET       /reports                       ReportController@index
GET       /reports/students/export       ReportController@studentsExport  ← Excel alumnos + plan actual
GET       /reports/earnings              ReportController@earnings         ← visible solo si show_earnings=1
GET       /reports/earnings/export       ReportController@earningsExport
GET       /reports/clase/{clase}         ReportController@byClase
GET       /reports/clase/{clase}/student/{student} ReportController@byClaseStudent
GET       /reports/student/{student}     ReportController@byStudent
```

---

## Autenticación

### Admin (PIN)
- PIN se lee de `Setting::get('app_pin')` primero; si no existe cae a `env('APP_PIN', '1234')`
- Se puede cambiar desde `/settings` (sección colapsable al final de la página)
- `PinController`: verifica PIN → `session(['pin_authenticated' => true])`
- `CheckPin` middleware: comprueba `session('pin_authenticated')`
- Alias `check.pin` en `bootstrap/app.php`

### Portal alumno
- **Sin autenticación** — cualquier persona puede consultar ingresando un DNI en `/`
- `StudentPortalController@lookup`: busca alumno activo por DNI, retorna JSON con nombre y datos del plan actual
- No hay sesión ni cookie de alumno

---

## Vistas y layouts

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

## Lógica de negocio clave

### Dashboard (`DashboardController@index`)
Tres tarjetas de resumen + lista de cursos para tomar asistencia:

| Query | Dato |
|---|---|
| `StudentPlan` count por mes | Planes este mes |
| `Setting::preload([notify_days_before, notify_classes_remaining])` | Umbrales por vencer |
| `Student::with('currentPlan')->get()` (2 queries) | Base para las dos métricas siguientes |
| `Clase::where('active')->withCount('students')->get()` | Lista de cursos |

- **Con plan activo**: alumnos cuyo `currentPlan` tiene `start_date <= hoy`, `end_date >= hoy` y `classes_remaining > 0` (o null para ilimitados).
- **Planes este mes**: planes con `start_date` en el mes actual (excluye soft-deleted).
- **Por vencer**: alumnos cuyo `currentPlan` tiene status `ok` o `exhausted` Y (`daysLeft <= notify_days_before` O `classes_remaining <= notify_classes_remaining`).
- Los cursos activos aparecen con badge "Hoy" si tienen clase el día actual — enlazan a `attendance.take`.

### Asistencia — flujo por alumno (nuevo)
La pantalla principal de asistencia (`/attendance`) es ahora un **buscador de alumnos** por nombre o DNI. Al seleccionar un alumno se navega a `/attendance/student/{student}`:

- Muestra los cursos del **día seleccionado** en dos secciones:
  - **Cursos de hoy** — cursos en los que el alumno está inscrito y tienen clase ese día
  - **Cursos de hoy no inscritos** — cursos con clase ese día en los que el alumno NO está inscrito; botón "+ Añadir" llama a `addStudent` (inscribe + marca presente)
- Cada curso tiene toggle inmediato que llama a `POST /attendance/{clase}/toggle`
- `todayIds` es una variable reactiva Alpine — al añadir un curso no inscrito se mueve automáticamente a la sección "Cursos de hoy" sin recargar
- Selector de fecha: al cambiar fecha recarga la página con `?date=YYYY-MM-DD`

### Asistencia — flujo por curso (conservado)
Desde el dashboard, los cursos activos enlazan a `/attendance/{clase}/take`:
- Muestra todos los alumnos inscritos en ese curso con toggles
- El botón de retroceso lleva al **dashboard** (no a `/attendance`)
- Búsqueda por nombre o DNI en la lista de alumnos
- Modal "Añadir alumno" para inscribir alumnos no matriculados
- `$dateInSchedule`: si el día no está en el horario del curso, muestra banner rojo y bloquea controles

### Plan de alumno
- `class_quota` acepta `'8' | '12' | '16' | '24' | 'full1' | 'full2'` (string, no int)
- `classes_remaining`: contador persistido en BD, mantenido por `AttendanceController` — no requiere consultar `attendances`
- `classesUsed()` = `class_quota - classes_remaining` (sin query a BD)
- `classesRemaining()` retorna `$this->classes_remaining` directamente (null para `full1`/`full2`)
- `status()` retorna: `pending` (no iniciado), `ok` (activo), `exhausted` (cuota agotada), `expired` (vencido) — sin query a BD
- Al cancelar un plan se usa soft delete — queda en historial con badge "Cancelado"
- El botón "Cancelar plan" aparece en planes con status `ok` o `pending`

#### `StudentPlanController@index` — lógica de planes
Distingue dos slots activos (ambos excluyendo soft-deleted):
- `$currentPlan`: primer plan con status `ok`, `exhausted` o `expired`
- `$nextPlan`: primer plan con status `pending`
- Si ninguno aplica, `$currentPlan` toma el primer plan disponible
- `$enrolledIds` default a todos los cursos activos cuando el alumno no tiene inscripciones ni planes previos

#### `StudentPlanController@store` — sincronizar cursos
Acepta campo `clases[]` (array de IDs). Al guardar el plan:
```php
$student->clases()->sync([$id => ['enrolled_at' => $request->start_date], ...]);
```

#### Fecha fin automática (`calcEndDate()` en Alpine)
- Cuotas cortas (`8`, `12`, `full1`): 20 días hábiles desde `start_date`
- Cuotas largas (`16`, `24`, `full2`): 40 días hábiles desde `start_date`
- Cuenta días de lunes a viernes, ignorando sábados y domingos

#### Selección de cursos al crear plan (`students/plans.blade.php`)
Botones de filtro rápido:
- **Marcar todos** / **Sólo salsa** / **Sólo bachata** / **Sólo lady** / **Todos menos lady**
- Para planes `full1`/`full2`, al cambiar cuota se seleccionan todos los cursos automáticamente

### Portal del alumno (público)
- **Sin login** — cualquier persona consulta ingresando un DNI en `/`
- `StudentPortalController@lookup` busca alumno activo por DNI y retorna JSON:
  - `found`: boolean
  - `name`: nombre del alumno
  - `plan`: null o `{ quota_label, status, status_label, remaining, start_date, end_date }`
- La vista (`student/lookup.blade.php`) usa Alpine.js con `$watch('dni', ...)` — al borrar el campo se limpian automáticamente los resultados
- El buscador permanece activo tras mostrar resultados para que otro alumno pueda consultar sin recargar

### Promociones
- Se configuran en `/settings` (toggle activo/inactivo por cada tipo)
- Al crear un plan, las promociones activas aparecen como botones seleccionables
- Al seleccionar una, el precio se recalcula automáticamente en Alpine:
  - `promo_10` → 10% descuento
  - `promo_20` → 20% descuento
  - `promo_30` → 30% descuento
  - `promo_2x1` → 50% descuento
- La clave de la promoción se guarda en `student_plans.promotion`
- Visible en detalle del plan, historial y reporte de ganancias (vista + Excel)

### Horario de clases
- `scheduleText()` agrupa días con el mismo horario, formatea horas en 12h con am/pm
- Retorna HTML — usar siempre `{!! $clase->scheduleText() !!}`, nunca `{{ }}`

### Precios (Settings)
Seis claves: `price_8h` (120), `price_12h` (150), `price_16h` (170), `price_24h` (200), `price_full1` (190), `price_full2` (210).

### Notificaciones WhatsApp (Settings)
- `notify_days_before` (default 3): umbral de días para marcar un plan como "por vencer"
- `notify_classes_remaining` (default 1): umbral de clases restantes para alertar
- `notify_message`: plantilla del mensaje; variables `{nombre}`, `{clases}`, `{fecha}`
- `notify_expired_message`: plantilla para plan vencido; variables `{nombre}`, `{fecha}`
- `StudentController@index` usa `Setting::preload([...])` para cargar los 4 settings en una sola query, luego calcula `isExpiring`, `waUrl` y `waUrlExpired` para cada alumno
- Teléfono normalizado: se quitan no-dígitos; si quedan 9 dígitos se antepone `51` (Perú)
- URL generada: `https://wa.me/{phone}?text={encoded_message}`

### Exportación de datos (Reportes)
- **`StudentsExport`**: Excel con todos los alumnos ordenados por nombre + su `currentPlan`. Columnas: Nombre, DNI, Teléfono, Alumno activo, Tipo de plan, Estado del plan, Clases restantes, Fecha inicio, Fecha fin, Promoción. Encabezado azul índigo. Ruta: `GET /reports/students/export`.
- **`EarningsExport`**: Excel de ganancias filtrado por rango de fechas. Ruta: `GET /reports/earnings/export`. La sección Ganancias en `reports/index.blade.php` se muestra solo si `Setting::get('show_earnings') == 1` (configurable desde `/settings`).
- Todos los links de descarga llevan `data-turbo="false"`.

### Importación de alumnos (`/import`)
- `ImportController@show` → vista con instrucciones del formato CSV y formulario de carga
- `ImportController@import` → procesa el archivo:
  - Detecta automáticamente separador `,` o `;`
  - Acepta fechas `DD/MM/YYYY` o `YYYY-MM-DD`
  - Busca alumno existente por DNI primero, luego por nombre (case-insensitive)
  - Si no existe → crea alumno + plan (si hay datos de plan)
  - Si existe con plan activo (`ok` o `pending`) → omite el plan
  - Si existe sin plan activo → crea el plan
- Columnas CSV: `name` (req), `dni`, `phone`, `start_date`, `end_date`, `nombre_plan`, `price`, `clases_restantes`
- Valores válidos para `nombre_plan`: `8 horas`, `12 horas`, `16 horas`, `24 horas`, `Full-1`, `Full-2`
- Acceso desde `/settings` → sección "Importar datos" al final de la página

### Validaciones en español (`StudentController`)
Mensajes personalizados para `store` y `update`:
- `dni.unique` → "El DNI ingresado ya está registrado."
- `phone.required` → "El teléfono es obligatorio."
- `phone.min` → "El teléfono debe tener al menos 8 caracteres."
- `phone.max` → "El teléfono no puede tener más de 20 caracteres."

---

## Service Worker (`public/sw.js`) — versión `slm-v3`

| Tipo de recurso | Estrategia |
|---|---|
| `/build/`, `/images/`, `/icons/` | Cache-first (assets con hash o raramente cambiados) |
| Páginas HTML | Network-first, fallback a cache si offline |

**Actualizar caché de assets**: cambiar `const CACHE = 'slm-vX'` al desplegar si se modificaron imágenes o íconos sin cambiar su nombre. Los assets de `/build/` no necesitan bumping manual — Vite genera nombres con hash de contenido.

**No cachear páginas HTML** con stale-while-revalidate — los tokens CSRF embebidos quedarían obsoletos causando errores 419.

---

## Estructura de archivos relevantes

```
app/
  Http/
    Controllers/
      AttendanceController.php       ← index (buscador alumnos), takeByStudent, take, toggle, save, addStudent
      ClaseController.php            ← parseSchedule() para JSON de horario
      DashboardController.php        ← 5 queries; tarjetas: con plan activo, planes mes, por vencer
      EnrollmentController.php
      ImportController.php           ← show() + import() para carga masiva CSV
      PinController.php              ← auth admin por PIN
      ReportController.php           ← studentsExport() + earningsExport()
      SettingController.php          ← precios + promociones + notificaciones WA + show_earnings
      StudentController.php          ← validaciones en español para DNI y teléfono
      StudentPlanController.php      ← fechas default, promociones activas, cursos del plan, nextPlan
      StudentPortalController.php    ← publicSearch() + lookup() (portal público sin auth)
    Middleware/
      CheckPin.php                   ← alias check.pin
      CheckStudentAuth.php           ← alias check.student (en desuso)
  Models/
    Student.php · Clase.php · Attendance.php · StudentPlan.php · Setting.php
  Exports/
    EarningsExport.php               ← Excel ganancias (incluye promoción y fecha registro)
    StudentsExport.php               ← Excel alumnos + plan actual (sin precio; encabezado índigo)

resources/
  js/app.js                          ← importa Turbo + Alpine; Turbo.config.drive.progressBarDelay = 0
  css/app.css                        ← incluye .turbo-progress-bar con gradiente violeta
  views/
    layouts/
      app.blade.php                  ← shell admin; csrf-token meta; @stack('head'); nav inferior; splash con readyState fix
      student.blade.php              ← shell portal alumno; sin logout; csrf-token meta; @stack('head')
    auth/login.blade.php             ← login PIN admin; link "Acceso alumnos" → route('student.search')
    student/
      lookup.blade.php               ← búsqueda pública por DNI (Alpine.js + fetch); $watch limpia resultados al borrar
    dashboard/index.blade.php        ← 3 tarjetas + lista de cursos con link a attendance.take
    attendance/
      index.blade.php                ← buscador de alumnos por nombre o DNI (sticky); Alpine client-side filter
      take.blade.php                 ← asistencia por curso (Alpine.js, toggle, modal); back → dashboard
      take-student.blade.php         ← asistencia por alumno; cursos del día + no inscritos; imágenes de cursos
    students/
      index.blade.php                ← tabs Alpine: Todos · Activos · Por vencer · Vencido; botones WhatsApp
      create.blade.php · edit.blade.php · plans.blade.php
    clases/
      index.blade.php · create.blade.php · edit.blade.php · enroll.blade.php
      (create y edit usan IIFE con window.Alpine check para scheduleSelector)
    import/
      index.blade.php                ← instrucciones CSV + formulario de carga
    settings/edit.blade.php          ← precios + promociones + reportes (show_earnings) + notificaciones WA + PIN + importar
    reports/
      index.blade.php                ← ganancias condicional (show_earnings); botón exportar alumnos
      clase.blade.php · clase-student.blade.php
      student.blade.php · earnings.blade.php

routes/web.php
bootstrap/app.php                    ← alias middleware check.pin y check.student

database/
  factories/
    ClaseFactory.php · StudentFactory.php · StudentPlanFactory.php
  seeders/
    DatabaseSeeder.php               ← borra alumnos/planes/asistencias, conserva cursos; genera historial

public/
  manifest.json                      ← PWA manifest (nombre, iconos, theme_color, display:standalone)
  sw.js                              ← Service Worker slm-v3; cache-first assets, network-first HTML
  favicon.ico                        ← favicon multi-tamaño (16/32/48px) generado desde logo.png
  favicon-16x16.png · favicon-32x32.png ← favicons PNG para navegadores modernos
  icons/                             ← apple-touch-icon.png y variantes
  images/
    fondo.jpg                        ← fondo dinámico del layout admin
    salsa.jpg · bachata.jpg · lady.jpg ← imágenes de cursos (asignadas por nombre)
    logo-xs.jpg                      ← logo pequeño en cabeceras
    logo.png                         ← logo principal (fuente para favicons)

empaquetar.sh                        ← script para generar zip de deploy (excluye node_modules, ocultos, etc.)

tests/Feature/
  ClaseControllerTest.php · EnrollmentControllerTest.php
  StudentControllerTest.php          ← cubre index (planStatus, isExpiring, waUrl), store, update, destroy
  StudentPlanControllerTest.php      ← cubre store (quota, promoción, solapamiento), destroy (soft delete)
  AttendanceControllerTest.php
tests/Unit/
  ClaseModelTest.php
```
