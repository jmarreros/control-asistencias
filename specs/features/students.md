# Spec: Alumnos

## Regla general: solo alumnos activos

Todos los listados, filtros, contadores y reportes trabajan exclusivamente con alumnos que tienen `active = true`. Los alumnos inactivos no aparecen en ninguna pantalla salvo en el flujo de reactivación al crear un nuevo alumno.

Puntos donde se aplica el filtro `where('active', true)`:
- `StudentController@index`
- `DashboardController@index`
- `AttendanceController@take` — alumnos inscritos en el curso
- `AttendanceController@save` — IDs para guardado masivo
- `ClaseController@index` — `withCount(['students' => fn($q) => $q->where('active', true)])`
- `EnrollmentController@edit` — lista de alumnos y `$enrolledIds`
- `ReportController@byClase`, `@earnings`
- `StudentsExport`, `EarningsExport`

## Pantalla `/students` — filtros

Segmented control con cuatro tabs (Alpine.js, client-side):

| Tab | Condición | Color |
|---|---|---|
| Todos | Sin filtro adicional | Gris |
| Plan Activo | `planStatus === 'ok'` | Verde |
| Por vencer | `isExpiring === true` | Naranja |
| Vencido | `planStatus === 'expired' \|\| 'exhausted'` | Rojo |

Badges de estado por alumno: `Plan activo` · `Por iniciar` · `Clases agotadas` · `Plan vencido` · `Sin plan`.

## Crear alumno (`/students/create`) — detección de duplicados

`StudentController@create` pasa `$existingStudents` (activos e inactivos) con campos `id, name, dni, phone, active, edit_url` como JSON a Alpine.

### Comprobación por DNI (dura — bloquea el guardado)
- Al escribir 8 dígitos Alpine busca coincidencia en `existingStudents`.
- **Coincide con alumno activo** → banner rojo "Este DNI ya está registrado — [nombre]", botón deshabilitado.
- **Coincide con alumno inactivo** → auto-selecciona el alumno y activa el flujo de reactivación (banner ámbar).

### Comprobación por nombre (suave — advertencia)
- A partir de 3 caracteres Alpine filtra alumnos **activos** con nombre similar (`includes`).
- Muestra banner amarillo con lista de coincidentes; cada fila tiene enlace "Recuperar alumno" (`edit_url`).
- Botón "Es otro alumno" (junto al título del banner) confirma que es persona diferente y habilita el guardado.

### Flujo de reactivación (alumno inactivo)
- Activado por: selección en autocomplete de nombre (solo inactivos) o coincidencia de DNI con inactivo.
- Campos nombre, DNI y teléfono se rellenan y quedan en solo lectura.
- Banner ámbar "Alumno encontrado en el sistema". Enlace "No es este alumno" limpia la selección.
- Botón cambia a "Reactivar alumno" (índigo).
- Al guardar: `StudentController@store` detecta `inactive_student_id`, ejecuta `update(['active' => true])` y redirige a planes. No crea registro nuevo.

### `canSubmit` — lógica Alpine
```
selectedId !== null          → true  (reactivación siempre permitida)
dniConflict !== null         → false (DNI activo duplicado)
activeNameConflicts.length > 0 && !nameConflictConfirmed → false
```

## Tailwind JIT en templates dinámicos Alpine

Las clases Tailwind usadas **solo dentro de `<template x-for>`** pueden no compilarse. Usar `style="cursor:pointer"` inline en lugar de `cursor-pointer` para garantizar el cursor en enlaces dinámicos renderizados por Alpine.
