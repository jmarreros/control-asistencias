# Control de Asistencias — Academia de Baile

App mobile-first para que el profesor registre asistencias desde el celular, sin personal adicional.

## Stack

- **Laravel 11** — backend y rutas
- **SQLite** — base de datos local
- **Tailwind CSS (JIT)** — estilos
- **Alpine.js v3** — interactividad en frontend
- **Blade** — plantillas
- **Vite** — bundler

## Funcionalidades

- Autenticación por PIN simple (configurable en `.env`)
- Gestión de alumnos (crear, editar, desactivar)
- Gestión de clases con horarios JSON (días y horas)
- Inscripción de alumnos a clases
- Toma de asistencia diaria con toggle individual
- Planes de alumnos (8, 12, 16 clases o ilimitado) con control de cuota
- Reportes por clase y por alumno
- Configuración de precios por tipo de plan

## Instalación

```bash
git clone https://github.com/jmarreros/control-asistencias.git
cd control-asistencias

composer install
npm install

cp .env.example .env
php artisan key:generate

# Editar .env: APP_PIN, DB_DATABASE (ruta absoluta al archivo .sqlite)
touch database/database.sqlite
php artisan migrate

npm run build
php artisan serve
```

## Comandos habituales

```bash
php artisan serve          # servidor en http://localhost:8000
npm run dev                # Vite con HMR
php artisan migrate        # correr migraciones pendientes
php artisan migrate:fresh  # reiniciar BD completa
```

## Variables de entorno relevantes

```env
APP_PIN=1234                        # PIN de acceso
DB_CONNECTION=sqlite
DB_DATABASE=/ruta/absoluta/database/database.sqlite
```

## Licencia

MIT
