# Spec: Autenticación

## Admin (PIN)
- PIN se lee de `Setting::get('app_pin')` primero; si no existe cae a `config('app.pin')` → `env('APP_PIN', '1234')`
- Se puede cambiar desde `/settings` (sección colapsable al final de la página)
- `PinController`: verifica PIN → `session(['pin_authenticated' => true])`; ruta POST `/login` tiene `throttle:5,1` (5 intentos/minuto anti brute-force)
- `CheckPin` middleware: comprueba `session('pin_authenticated')`
- `CheckSessionTimeout` middleware: expira la sesión admin tras 8 horas de inactividad; alias `session.timeout` en `bootstrap/app.php`
- Alias `check.pin` en `bootstrap/app.php`
- Alias `log.access` en `bootstrap/app.php` — middleware `LogAdminAccess` aplicado a todas las rutas admin; registra visitas GET a páginas principales (dashboard, alumnos, cursos, asistencia, reportes, configuración, logs)

## Portal alumno
- **Sin autenticación** — cualquier persona puede consultar ingresando un DNI en `/`
- `StudentPortalController@lookup`: busca alumno activo por DNI, retorna JSON con nombre y datos del plan actual
- No hay sesión ni cookie de alumno
