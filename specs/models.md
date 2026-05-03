# Spec: Modelos

## `Student`
- `fillable`: name, dni, phone, notes, active
- `casts`: active → boolean
- Relaciones: `clases()` BelongsToMany, `attendances()` HasMany, `plans()` HasMany, `currentPlan()` HasOne (latestOfMany start_date)

## `Clase`
- `fillable`: name, schedule, description, active
- `casts`: active → boolean, schedule → array
- `scheduleText(): string` — devuelve HTML con días y horas formateados (usar `{!! !!}` en vistas)
- Relaciones: `students()` BelongsToMany (ordenados por name), `attendances()` HasMany

## `StudentPlan`
- `fillable`: student_id, start_date, end_date, class_quota, classes_remaining, price, promotion
- `casts`: price → decimal:2
- Traits: `HasFactory`, `SoftDeletes`
- `class_quota`: string — valores `'8' | '12' | '16' | '24' | 'full1' | 'full2'`
- `classes_remaining`: entero nullable — null para `full1`/`full2`; mantenido por `AttendanceController`
- `promotion`: string nullable — valores `'promo_10' | 'promo_20' | 'promo_30' | 'promo_2x1' | null`
- `PROMOTION_LABELS`: constante array que mapea clave → etiqueta legible
- Métodos: `status()` → `'ok' | 'exhausted' | 'expired' | 'pending'`, `classesUsed()` (deriva de quota - remaining, sin query), `classesRemaining()` (lee `$this->classes_remaining`, sin query), `canAttend()`, `promotionLabel(): ?string`

## `Attendance`
- `fillable`: clase_id, student_id, plan_id, date, present, notes
- Relaciones: `clase()`, `student()`, `plan()` BelongsTo StudentPlan

## `Setting`
- PK string (`key`), `$incrementing = false`
- `Setting::get('key', default)` / `Setting::set('key', value)`
- `Setting::preload(array $keys)` — carga varias claves en una sola query `WHERE key IN (...)` y las mete al caché; usar antes de llamar `get()` múltiples veces en un mismo request
- Cache en memoria (`static $cache[]`): cada clave se consulta una sola vez por request. **No es caché persistente** — el array se destruye al final de cada request, no hay invalidación entre requests.

## `AccessLog`
- `$timestamps = false` — solo tiene `created_at` (se inserta automáticamente via `useCurrent()`)
- `fillable`: type, action, detail, ip, created_at
- `casts`: created_at → datetime
- `AccessLog::record(type, action, detail, ip)` — método estático de conveniencia; toma IP del request si no se pasa
- Valores de `type`: `'admin'` | `'portal'`
- Valores de `action`: `'login'` | `'logout'` | `'login_failed'` | `'page_visit'` | `'dni_lookup'`
- Purga automática: el scheduler ejecuta `logs:purge --days=60` cada día — los logs se eliminan a los 60 días
