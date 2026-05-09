# Spec: Reportes y Exportaciones

- **`StudentsExport`**: Excel con los alumnos **activos** ordenados por nombre + su `currentPlan`. Columnas: Nombre, DNI, Teléfono, Alumno activo, Tipo de plan, Estado del plan, Clases restantes, Fecha inicio, Fecha fin, Promoción. Encabezado azul índigo. Ruta: `GET /reports/students/export`.
- **`EarningsExport`**: Excel de ganancias filtrado por rango de fechas, solo planes de alumnos **activos** (`whereHas('student', fn($q) => $q->where('active', true))`). Ruta: `GET /reports/earnings/export`. La sección Ganancias en `reports/index.blade.php` se muestra solo si `Setting::get('show_earnings') == 1` (configurable desde `/settings`).
- `ReportController@byClase`: filtra asistencias solo de alumnos activos (`whereHas('student', fn($q) => $q->where('active', true))`).
- `ReportController@earnings`: filtra planes solo de alumnos activos.
- Todos los links de descarga llevan `data-turbo="false"`.

## Validaciones en español (`StudentController`)
Mensajes personalizados para `store` y `update`:
- `dni.unique` → "El DNI ingresado ya está registrado."
- `phone.required` → "El teléfono es obligatorio."
- `phone.min` → "El teléfono debe tener al menos 8 caracteres."
- `phone.max` → "El teléfono no puede tener más de 20 caracteres."
