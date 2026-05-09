# Spec: Registro de Matrícula

Pantalla de búsqueda de alumnos orientada al flujo de alta y renovación de planes. Acceso desde el dashboard (tarjeta "Registro de Matrícula y Planes").

## Ruta

```
GET  /matricula   MatriculaController@index   ← buscador de alumnos + gestión de plan
```

Protegida por `check.pin` + `session.timeout` + `log.access`.

## `MatriculaController@index`

Carga alumnos activos con su plan actual:

```php
Student::where('active', true)->with('currentPlan')->orderBy('name')->get(['id', 'name', 'dni'])
```

Pasa `$students` a la vista como colección. El JSON Alpine incluye `id`, `name`, `dni` y `status` (resultado de `currentPlan?->status() ?? 'no_plan'`).

## UI — `matricula/index.blade.php`

### Cabecera

Flecha atrás → dashboard. Título: **"Registro de Matrícula y Planes"**.

### Buscador (sticky)

Input `type="search"` con Alpine `x-model="search"`. Filtra client-side por nombre (`includes`) o DNI (`indexOf`). Botón × para limpiar. Acento: `focus:ring-emerald-400`.

### Estados

| Condición | Vista |
|---|---|
| `!search` | Instrucción: icono + "Escribe el nombre o DNI del alumno" |
| `search && filtered.length === 0` | "No se encontró ningún alumno." + botón **"Registrar nuevo alumno"** → `/students/create` |
| `filtered.length > 0` | Lista de resultados |

### Lista de resultados (`<template x-for>`)

Cada fila es un enlace a `/students/{id}/plans`. Muestra:
- Avatar circular con inicial (verde esmeralda).
- Nombre + DNI (o "Sin DNI").
- Badge de estado del plan actual (reactivo al JSON cargado en Alpine).
- Chevron →.

Badges de estado:

| `status` | Label | Clases Tailwind |
|---|---|---|
| `ok` | Plan activo | `bg-green-500/20 text-green-300` |
| `pending` | Por iniciar | `bg-blue-500/20 text-blue-300` |
| `exhausted` | Clases agotadas | `bg-orange-500/20 text-orange-300` |
| `expired` | Plan vencido | `bg-red-500/20 text-red-300` |
| `no_plan` | Sin plan | `bg-white/10 text-white/40` |

### Flujo tras seleccionar alumno

Navega a `/students/{id}/plans` (pantalla `StudentPlanController@index` existente) que muestra historial completo y formulario para agregar/renovar plan.

### Flujo alumno no encontrado

Botón "Registrar nuevo alumno" lleva a `/students/create`. El formulario de creación incluye el flujo de reactivación si el alumno ya existe como inactivo.

## Dashboard

Tarjeta "Registro de Matrícula y Planes" (color esmeralda, icono clipboard-check) ubicada entre la tarjeta del kiosko y la sección "Tomar asistencia por curso".
