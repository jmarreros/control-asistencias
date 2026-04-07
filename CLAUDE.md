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
| `students` | id, name, phone, notes, active (bool) |
| `clases` | id, name, schedule (JSON text), description, active (bool) |
| `clase_student` | clase_id, student_id, enrolled_at (date nullable) — pivot |
| `attendances` | id, clase_id, student_id, date, present (bool) — UNIQUE(clase_id, student_id, date) |
| `student_plans` | id, student_id, start_date, end_date, class_quota (8/12/16/full) |
| `settings` | key (PK string), value |

### Columna `schedule` en `clases`
Tipo `text`, cast `'array'` en el modelo. Formato JSON:
```json
{"lun": {"start": "18:00", "end": "19:30"}, "mie": {"start": "18:00", "end": "19:30"}}
```
Claves de día: `lun mar mie jue vie sab dom`.

### Tabla `settings`
Clave primaria string. Registros actuales: `price_8h`, `price_12h`, `price_16h`, `price_full`.
Usar `Setting::get('key', default)` y `Setting::set('key', value)`.

### Fechas en `attendances`
Guardadas como string `YYYY-MM-DD` (sin cast a date en el modelo). Usar siempre `Carbon::parse($date)->toDateString()` al guardar.

---

## Modelos

### `Student`
- `fillable`: name, phone, notes, active
- `casts`: active → boolean
- Relaciones: `clases()` BelongsToMany, `attendances()` HasMany, `plans()` HasMany, `currentPlan()` HasOne (latestOfMany start_date)

### `Clase`
- `fillable`: name, schedule, description, active
- `casts`: active → boolean, schedule → array
- `scheduleText(): string` — devuelve HTML con días y horas formateados (usar `{!! !!}` en vistas)
- Relaciones: `students()` BelongsToMany (ordenados por name), `attendances()` HasMany

### `StudentPlan`
- `fillable`: student_id, start_date, end_date, class_quota
- `class_quota`: string — valores `'8' | '12' | '16' | 'full'`
- Métodos: `status()` → `'ok' | 'exhausted' | 'expired' | 'pending'`, `classesUsed()`, `classesRemaining()` (null si full), `canAttend()`

### `Setting`
- PK string (`key`), `$incrementing = false`
- `Setting::get('key', default)` / `Setting::set('key', value)`

---

## Rutas

```
GET/POST  /login                          PinController (auth PIN)
POST      /logout                         PinController@logout

GET       /                               DashboardController@index
GET       /settings                       SettingController@edit
POST      /settings                       SettingController@update

GET/POST  /students                       StudentController (index, create, store, edit, update, destroy)
GET       /students/{student}/plans       StudentPlanController@index
POST      /students/{student}/plans       StudentPlanController@store
DELETE    /students/{student}/plans/{plan} StudentPlanController@destroy

GET/POST  /clases                         ClaseController (index, create, store, edit, update, destroy)
GET/POST  /clases/{clase}/enroll          EnrollmentController (edit, update)

GET       /attendance                     AttendanceController@index
GET       /attendance/{clase}/take        AttendanceController@take
POST      /attendance/{clase}/save        AttendanceController@save
POST      /attendance/{clase}/toggle      AttendanceController@toggle
POST      /attendance/{clase}/add-student AttendanceController@addStudent

GET       /reports                        ReportController@index
GET       /reports/clase/{clase}          ReportController@byClase
GET       /reports/student/{student}      ReportController@byStudent
```

Todas las rutas excepto `/login` y `/logout` están protegidas por el middleware `check.pin`.

---

## Autenticación

PIN simple. Variable `APP_PIN` en `.env` (default `1234`).
- `PinController`: verifica PIN → `session(['pin_authenticated' => true])`
- `CheckPin` middleware: comprueba `session('pin_authenticated')`
- Registrado como alias `check.pin` en `bootstrap/app.php`

---

## Vistas y layout

`layouts/app.blade.php`:
- `<main class="pb-20 min-h-screen max-w-lg mx-auto">` — contenedor mobile centrado
- Navegación inferior fija: Inicio · Alumnos · Asistencia · Cursos · Reportes (`z-40`)
- Flash messages (`session('success')` / `session('error')`) en `position:fixed; bottom:5rem` con `z-index:50`, auto-dismiss 3–4s via Alpine

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
- `Attendance::updateOrCreate([clase_id, student_id, date], [present])` para toggle individual
- `Attendance::upsert($records, [clase_id, student_id, date], [present, updated_at])` para guardado masivo
- `addStudent`: inscribe al alumno con `syncWithoutDetaching` y marca presente

### Plan de alumno
- `class_quota` acepta `'8' | '12' | '16' | 'full'` (string, no int)
- `classesUsed()` cuenta asistencias `present=true` dentro del rango de fechas del plan
- `status()` retorna: `pending` (no iniciado), `ok` (activo), `exhausted` (cuota agotada), `expired` (vencido)

### Horario de clases
- `scheduleText()` agrupa días con el mismo horario, formatea horas en 12h con am/pm
- Retorna HTML — usar siempre `{!! $clase->scheduleText() !!}`, nunca `{{ }}`
- En vistas donde se usaba `$clase->schedule` como string: reemplazar por `scheduleText()`

### Precios (Settings)
Cuatro claves: `price_8h` (120), `price_12h` (150), `price_16h` (170), `price_full` (190).

---

## Estructura de archivos relevantes

```
app/
  Http/
    Controllers/
      AttendanceController.php   ← lógica principal diaria
      ClaseController.php        ← parseSchedule() para JSON de horario
      StudentPlanController.php  ← fechas default: próximo día laborable
      SettingController.php
      PinController.php
    Middleware/CheckPin.php
  Models/
    Student.php · Clase.php · Attendance.php · StudentPlan.php · Setting.php

resources/views/
  layouts/app.blade.php          ← shell con nav inferior y flash messages
  dashboard/index.blade.php
  attendance/
    index.blade.php              ← lista de clases para tomar asistencia
    take.blade.php               ← pantalla principal (Alpine.js, toggle, modal)
  students/
    index.blade.php              ← búsqueda client-side Alpine, badges de plan
    create.blade.php · edit.blade.php · plans.blade.php
  clases/
    index.blade.php · create.blade.php · edit.blade.php · enroll.blade.php
  settings/edit.blade.php
  reports/index.blade.php · clase.blade.php · student.blade.php

routes/web.php
bootstrap/app.php               ← alias middleware 'check.pin'
```
