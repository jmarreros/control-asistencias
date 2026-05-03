# Spec: Logs de Acceso

- Tabla `access_logs` en SQLite — registra quién accede al sistema y cuándo

## Eventos registrados
- `PinController`: `login` (correcto), `login_failed` (PIN incorrecto), `logout`
- `StudentPortalController@lookup`: `dni_lookup` con el DNI consultado y nombre del alumno (o "no encontrado")
- `LogAdminAccess` middleware: `page_visit` para Dashboard, Alumnos, Cursos, Asistencia, Reportes, Configuración, Logs

## Vista `/logs`
- Lista paginada (50 por página)
- Filtros: Todos / Admin / Portal
- Botón "Borrar" elimina todos los registros
- Acceso desde Configuración → sección "Actividad" → botón "Ver logs"

## Purga automática
`logs:purge --days=60` programado diariamente vía scheduler — los logs se eliminan automáticamente a los 60 días.
