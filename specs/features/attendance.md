# Spec: Asistencia

## Dashboard (`DashboardController@index`)
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

## Flujo por alumno (`/attendance`)
La pantalla principal de asistencia es un **buscador de alumnos** por nombre o DNI. Al seleccionar un alumno se navega a `/attendance/student/{student}`:

- Muestra los cursos del **día seleccionado** en dos secciones:
  - **Cursos de hoy** — cursos en los que el alumno está inscrito y tienen clase ese día
  - **Cursos de hoy no inscritos** — cursos con clase ese día en los que el alumno NO está inscrito; botón "+ Añadir" llama a `addStudent` (inscribe + marca presente)
- Cada curso tiene toggle inmediato que llama a `POST /attendance/{clase}/toggle`
- `todayIds` es una variable reactiva Alpine — al añadir un curso no inscrito se mueve automáticamente a la sección "Cursos de hoy" sin recargar
- Selector de fecha: al cambiar fecha recarga la página con `?date=YYYY-MM-DD`

## Flujo por curso (`/attendance/{clase}/take`)
Desde el dashboard, los cursos activos enlazan a esta vista:
- Muestra todos los alumnos inscritos en ese curso con toggles
- El botón de retroceso lleva al **dashboard** (no a `/attendance`)
- Búsqueda por nombre o DNI en la lista de alumnos
- Modal "Añadir alumno" para inscribir alumnos no matriculados
- `$dateInSchedule`: si el día no está en el horario del curso, muestra banner rojo y bloquea controles
