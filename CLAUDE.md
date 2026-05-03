# Control de Asistencias — Academia de Baile

App mobile-first para que el profesor registre asistencias desde el celular. Sin personal adicional.

**Stack:** Laravel 13 · PHP 8.4 · SQLite · Tailwind CSS v3 (JIT) · Blade · Alpine.js v3 · Hotwire Turbo Drive · Vite · Laravel Boost (MCP)

---

## Comandos habituales

```bash
php artisan serve          # servidor en http://localhost:8000
npm run dev                # Vite (Tailwind JIT + HMR)
php artisan migrate        # correr migraciones pendientes
php artisan migrate:fresh  # reiniciar BD completa
php artisan logs:purge     # elimina logs con más de 60 días (--days=N para otro valor)
php artisan schedule:run   # ejecutar tareas programadas manualmente
```

### Cron en producción
```
* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## Especificaciones

@specs/database.md
@specs/models.md
@specs/routes.md
@specs/auth.md
@specs/ui.md
@specs/features/attendance.md
@specs/features/plans.md
@specs/features/portal.md
@specs/features/notifications.md
@specs/features/reports.md
@specs/features/import.md
@specs/features/logs.md
