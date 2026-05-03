# Spec: Portal del Alumno (público)

- **Sin login** — cualquier persona consulta ingresando un DNI en `/`
- `StudentPortalController@lookup` busca alumno activo por DNI y retorna JSON:
  - `found`: boolean
  - `name`: nombre del alumno
  - `plan`: null o `{ quota_label, status, status_label, remaining, start_date, end_date }`
- La vista (`student/lookup.blade.php`) usa Alpine.js con `$watch('dni', ...)` — al borrar el campo se limpian automáticamente los resultados
- El buscador permanece activo tras mostrar resultados para que otro alumno pueda consultar sin recargar
