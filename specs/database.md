# Spec: Base de datos (SQLite)

## Tablas

| Tabla | Descripción |
|---|---|
| `students` | id, name, dni (unique nullable), phone, notes, active (bool) |
| `clases` | id, name, schedule (JSON text), description, active (bool) |
| `clase_student` | clase_id, student_id, enrolled_at (date nullable) — pivot |
| `attendances` | id, clase_id, student_id, plan_id (FK nullable), date, present (bool) — UNIQUE(clase_id, student_id, date) |
| `student_plans` | id, student_id, start_date, end_date, class_quota, classes_remaining (nullable), price, promotion (nullable), deleted_at (soft delete) |
| `settings` | key (PK string), value |
| `access_logs` | id, type (admin\|portal), action, detail (nullable), ip (nullable), created_at — sin updated_at |

## Columna `schedule` en `clases`
Tipo `text`, cast `'array'` en el modelo. Formato JSON:
```json
{"lun": {"start": "18:00", "end": "19:30"}, "mie": {"start": "18:00", "end": "19:30"}}
```
Claves de día: `lun mar mie jue vie sab dom`.

## Tabla `settings`
Clave primaria string. Registros actuales:

| Grupo | Claves |
|---|---|
| Precios | `price_8h`, `price_12h`, `price_16h`, `price_24h`, `price_full1`, `price_full2` |
| Promociones | `promo_10`, `promo_20`, `promo_30`, `promo_2x1` |
| Notificaciones WA | `notify_days_before`, `notify_classes_remaining`, `notify_message`, `notify_expired_message` |
| Reportes | `show_earnings` (bool; 0 = oculto, 1 = visible en /reports) |
| Seguridad | `app_pin` (PIN admin; si no existe, cae a `config('app.pin')` → `env('APP_PIN', '1234')`) |

Usar `Setting::get('key', default)` y `Setting::set('key', value)`.

## Columna `plan_id` en `attendances`
FK nullable a `student_plans`. Se asigna al guardar/toggle asistencia buscando el plan activo del alumno en esa fecha. Permite calcular `classesUsed()` sin range scan de fechas. `nullOnDelete` — si se cancela el plan el registro de asistencia se conserva con `plan_id = null`.

## Columna `classes_remaining` en `student_plans`
Entero nullable (null para `full1`/`full2`). Se mantiene incrementando/decrementando en cada cambio de asistencia desde `AttendanceController`. Evita consultar `attendances` para calcular clases restantes — `classesRemaining()` y `status()` leen directamente este campo.
- Al crear un plan, se inicializa con el valor de `class_quota` (ej: `8` → `classes_remaining = 8`)
- `adjustRemaining($planId, $delta)` aplica `MAX(0, classes_remaining + delta)` — nunca baja de 0

## Índice en `attendances`
Índice compuesto `(student_id, present, date)` — útil para consultas históricas sobre asistencias.

## Columna `promotion` en `student_plans`
Almacena la clave de la promoción aplicada al crear el plan. Valores posibles: `null`, `'promo_10'`, `'promo_20'`, `'promo_30'`, `'promo_2x1'`.

## Soft delete en `student_plans`
Los planes eliminados no se borran físicamente — se marca `deleted_at`. Usar `withTrashed()` para incluirlos en el historial. `currentPlan()` excluye automáticamente los soft-deleted.

## Fechas en `attendances`
Guardadas como string `YYYY-MM-DD` (sin cast a date en el modelo). Usar siempre `Carbon::parse($date)->toDateString()` al guardar.
