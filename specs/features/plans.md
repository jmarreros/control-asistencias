# Spec: Planes, Promociones y Precios

## Plan de alumno

- `class_quota` acepta `'8' | '12' | '16' | '24' | 'full1' | 'full2'` (string, no int)
- `classes_remaining`: contador persistido en BD, mantenido por `AttendanceController` — no requiere consultar `attendances`
- `classesUsed()` = `class_quota - classes_remaining` (sin query a BD)
- `classesRemaining()` retorna `$this->classes_remaining` directamente (null para `full1`/`full2`)
- `status()` retorna: `pending` (no iniciado), `ok` (activo), `exhausted` (cuota agotada), `expired` (vencido) — sin query a BD
- Al cancelar un plan se usa soft delete — queda en historial con badge "Cancelado"
- El botón "Cancelar plan" aparece en planes con status `ok` o `pending`

### `StudentPlanController@index` — lógica de slots activos
Distingue dos slots activos (ambos excluyendo soft-deleted):
- `$currentPlan`: primer plan con status `ok`, `exhausted` o `expired`
- `$nextPlan`: primer plan con status `pending`
- Si ninguno aplica, `$currentPlan` toma el primer plan disponible
- `$enrolledIds` default a todos los cursos activos cuando el alumno no tiene inscripciones ni planes previos

### `StudentPlanController@store` — sincronizar cursos
Acepta campo `clases[]` (array de IDs). Al guardar el plan:
```php
$student->clases()->sync([$id => ['enrolled_at' => $request->start_date], ...]);
```

### Fecha fin automática (`calcEndDate()` en Alpine)
- Cuotas cortas (`8`, `12`, `full1`): 20 días hábiles desde `start_date`
- Cuotas largas (`16`, `24`, `full2`): 40 días hábiles desde `start_date`
- Cuenta días de lunes a viernes, ignorando sábados y domingos

### Selección de cursos al crear plan (`students/plans.blade.php`)
Botones de filtro rápido:
- **Marcar todos** / **Sólo salsa** / **Sólo bachata** / **Sólo lady** / **Todos menos lady**
- Para planes `full1`/`full2`, al cambiar cuota se seleccionan todos los cursos automáticamente

## Promociones

- Se configuran en `/settings` (toggle activo/inactivo por cada tipo)
- Al crear un plan, las promociones activas aparecen como botones seleccionables
- Al seleccionar una, el precio se recalcula automáticamente en Alpine:
  - `promo_10` → 10% descuento
  - `promo_20` → 20% descuento
  - `promo_30` → 30% descuento
  - `promo_2x1` → 50% descuento
- La clave de la promoción se guarda en `student_plans.promotion`
- Visible en detalle del plan, historial y reporte de ganancias (vista + Excel)

## Precios (Settings)
Seis claves con valores por defecto:

| Clave | Precio |
|---|---|
| `price_8h` | 120 |
| `price_12h` | 150 |
| `price_16h` | 170 |
| `price_24h` | 200 |
| `price_full1` | 190 |
| `price_full2` | 210 |

## Horario de clases
- `scheduleText()` agrupa días con el mismo horario, formatea horas en 12h con am/pm
- Retorna HTML — usar siempre `{!! $clase->scheduleText() !!}`, nunca `{{ }}`
