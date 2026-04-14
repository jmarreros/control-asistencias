# Control de Asistencias — Academia de Baile

App mobile-first para que el profesor registre asistencias desde el celular. Sin personal adicional.

**Stack:** Laravel 11 · SQLite · Tailwind CSS (JIT) · Blade · Alpine.js v3 · Vite

---

## Comandos habituales

```bash
php artisan serve          # servidor en http://localhost:8000
npm run dev                # Vite (Tailwind JIT + HMR)
php artisan migrate        # correr migraciones pendientes
php artisan migrate:fresh  # reiniciar BD completa
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

### Portal alumno (público)
```
GET       /                               → redirige a /student/login
GET/POST  /student/login                 StudentAuthController (auth por DNI)
POST      /student/logout                StudentAuthController@logout
GET       /student                       StudentPortalController@index (dashboard alumno)
GET       /student/clase/{clase}         StudentPortalController@byClase (detalle por día)
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

GET       /attendance                    AttendanceController@index
GET       /attendance/{clase}/take       AttendanceController@take
POST      /attendance/{clase}/save       AttendanceController@save
POST      /attendance/{clase}/toggle     AttendanceController@toggle
POST      /attendance/{clase}/add-student AttendanceController@addStudent

GET       /reports                       ReportController@index
GET       /reports/clase/{clase}         ReportController@byClase
GET       /reports/clase/{clase}/student/{student} ReportController@byClaseStudent
GET       /reports/student/{student}     ReportController@byStudent
GET       /reports/earnings              ReportController@earnings
GET       /reports/earnings/export       ReportController@earningsExport
```

---

## Autenticación

### Admin (PIN)
- PIN se lee de `Setting::get('app_pin')` primero; si no existe cae a `env('APP_PIN', '1234')`
- Se puede cambiar desde `/settings` (sección colapsable al final de la página)
- `PinController`: verifica PIN → `session(['pin_authenticated' => true])`
- Al autenticar como admin se elimina la sesión de alumno (`session()->forget('student_id')`)
- `CheckPin` middleware: comprueba `session('pin_authenticated')`
- Alias `check.pin` en `bootstrap/app.php`

### Alumno (DNI)
- `StudentAuthController`: busca alumno por DNI → `session(['student_id' => $id])`
- Solo alumnos con `active = true` pueden ingresar (sin restricción de plan)
- `CheckStudentAuth` middleware: comprueba `session('student_id')`
- Alias `check.student` en `bootstrap/app.php`
- Las dos sesiones coexisten de forma independiente

---

## Vistas y layouts

### `layouts/app.blade.php` (admin)
- Navegación inferior fija: Inicio · Alumnos · Asistencia · Cursos · Reportes (`z-40`)
- Flash messages en `position:fixed; bottom:5rem` con `z-index:50`, auto-dismiss 3–4s via Alpine
- Logo en cabeceras enlaza a `route('dashboard')`
- Fondo fijo: `public/images/fondo.jpg` con overlay oscuro (`rgba(0,0,0,0.25)`), posicionado con `position:fixed; inset:0; z-index:1/2`
- Contenido principal en `<main z-index:3>` con `max-w-lg mx-auto` (centrado en pantallas anchas)
- PWA: registra service worker `/sw.js` y apunta a `/manifest.json`; incluye `apple-touch-icon`, meta `theme-color` y `apple-mobile-web-app-*`
- Barra de progreso superior: CSS puro con `@keyframes slm-load` (`transform:scaleX`), `z-index:99999`, corre automáticamente en cada carga de página sin JS
- Splash screen (`#slm-splash`, `z-index:99998`): logo + nombre + tres puntos animados; visible solo en apertura en frío (sessionStorage `slm_s` vacío); mínimo 900ms, fade out 450ms; en navegación interna se oculta de inmediato
- Favicons: `favicon.ico` (16/32/48px), `favicon-16x16.png`, `favicon-32x32.png` en `public/`

### `layouts/student.blade.php` (portal alumno)
- Sin navegación inferior — solo contenido y botón logout en header
- Flash messages en `position:fixed; bottom:1.5rem`
- Logo en cabeceras enlaza a `route('student.dashboard')`

### Imágenes de cursos
Archivos en `public/images/`: `salsa.jpg`, `bachata.jpg`, `lady.jpg`. Se asignan por nombre del curso:
```php
$img = str_contains(strtolower($clase->name), 'salsa')   ? 'salsa.jpg'
     : (str_contains(strtolower($clase->name), 'bachata') ? 'bachata.jpg'
     : (str_contains(strtolower($clase->name), 'lady')    ? 'lady.jpg'
     : null));
```
Si no hay imagen, se muestra un avatar circular con la inicial. Este patrón está presente en dashboard, attendance/index, attendance/take, clases/index, clases/edit, clases/enroll, reports/index y reports/clase.

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

---

## Lógica de negocio clave

### Asistencia
- `$defaultPresent = false` — por defecto todos ausentes (tanto hoy como fechas pasadas)
- `Attendance::updateOrCreate([clase_id, student_id, date], [present, plan_id])` para toggle individual
- `Attendance::upsert($records, [clase_id, student_id, date], [present, plan_id, updated_at])` para guardado masivo
- `addStudent`: inscribe al alumno con `syncWithoutDetaching` y marca presente
- Al guardar/toggle: `resolvePlanId(studentId, date)` busca el plan activo en esa fecha; `adjustRemaining(planId, delta)` actualiza `classes_remaining` solo si cambió el estado de presencia
- En `save()` masivo: los planes se cargan en una sola query (`whereIn student_id`) antes del upsert; los deltas se acumulan por `plan_id` y se aplican en batch
- `$extraStudents` en `take.blade.php` incluye `planStatus` de cada alumno no inscrito (para mostrar badges en el modal de añadir)
- `$dateInSchedule`: booleano que indica si el día de la fecha seleccionada está en el horario del curso; si es `false` se muestra banner rojo y se bloquean todos los controles de asistencia

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

### Portal del alumno
- Muestra asistencias del último plan registrado (`currentPlan`)
- Si no hay plan o está vencido, muestra banner con mensaje de renovación
- `StudentPortalController` filtra asistencias por `start_date`/`end_date` del plan

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

---

## Estructura de archivos relevantes

```
app/
  Http/
    Controllers/
      AttendanceController.php       ← lógica principal diaria
      ClaseController.php            ← parseSchedule() para JSON de horario
      DashboardController.php
      EnrollmentController.php
      PinController.php              ← auth admin por PIN
      ReportController.php
      SettingController.php          ← precios + promociones + notificaciones WA
      StudentAuthController.php      ← auth alumno por DNI
      StudentController.php
      StudentPlanController.php      ← fechas default, promociones activas, cursos del plan, nextPlan
      StudentPortalController.php    ← dashboard y detalle por curso del alumno
    Middleware/
      CheckPin.php                   ← alias check.pin
      CheckStudentAuth.php           ← alias check.student
  Models/
    Student.php · Clase.php · Attendance.php · StudentPlan.php · Setting.php
  Exports/
    EarningsExport.php               ← Excel ganancias (incluye promoción y fecha registro)

resources/views/
  layouts/
    app.blade.php                    ← shell admin con nav inferior
    student.blade.php                ← shell portal alumno (sin nav)
  auth/login.blade.php               ← login PIN admin
  student/
    login.blade.php                  ← login DNI alumno
    dashboard.blade.php              ← asistencias del plan actual
    clase.blade.php                  ← detalle día a día por curso
  dashboard/index.blade.php
  attendance/
    index.blade.php                  ← lista de clases para tomar asistencia
    take.blade.php                   ← pantalla principal (Alpine.js, toggle, modal)
  students/
    index.blade.php                  ← tabs Alpine: Todos · Activos · Por vencer · Vencido; botones WhatsApp
    create.blade.php · edit.blade.php · plans.blade.php
  clases/
    index.blade.php · create.blade.php · edit.blade.php · enroll.blade.php
  settings/edit.blade.php            ← precios + promociones + notificaciones WhatsApp + cambio de PIN
  reports/
    index.blade.php · clase.blade.php · clase-student.blade.php
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
  sw.js                              ← Service Worker para PWA (cache slm-v2; precache fondo, logo-xs, icons)
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
