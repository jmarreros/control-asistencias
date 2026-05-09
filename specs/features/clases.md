# Spec: Cursos (Clases)

## Pantallas

- `/clases` — listado de cursos activos e inactivos con conteo de alumnos activos.
- `/clases/create` — formulario de creación.
- `/clases/{clase}/edit` — formulario de edición.

## Validación de horario (obligatorio)

El horario es un campo **requerido** en create y edit. `ClaseController` valida con `validateScheduleInput()` antes de llamar a `parseSchedule()`.

### Reglas

1. **Al menos un día seleccionado** con hora de inicio válida (`HH:MM`). Error: `"Selecciona al menos un día con horario."`.
2. **Hora de fin obligatoria** para cada día seleccionado. Error: `"Todos los días seleccionados deben tener hora de inicio y hora de fin."`.
3. **Fin > Inicio**: la hora de fin debe ser estrictamente mayor a la hora de inicio. Error: `"La hora de fin debe ser mayor a la hora de inicio."`. Comparación como string `HH:MM` — válida para horarios del mismo día.

Estas validaciones ocurren **server-side** en `validateScheduleInput()` y retornan `back()->withErrors(['schedule' => ...])`.

### Alpine (client-side)

El componente `scheduleSelector` (registrado con `Alpine.data`) está en ambas vistas. Se monta en la etiqueta `<form>` para que el botón de guardar quede dentro del scope.

Getters reactivos:

- `hasValidSchedule`: `selected.length > 0` Y todos los días seleccionados tienen `start`, `end` y `end > start`.
- `scheduleHint`: devuelve el primer mensaje de error relevante:
  - `"Completa la hora de inicio y fin de cada día."` — si falta start o end.
  - `"La hora de fin debe ser mayor a la hora de inicio."` — si end ≤ start.
  - `null` — sin errores.

Botón guardar: `:disabled="!hasValidSchedule"` con `opacity-50 cursor-not-allowed` cuando inválido.

### Auto-cálculo de hora de fin (`autoEnd`)

Al cambiar la hora de inicio de un día:
1. Si la hora de fin del mismo día está vacía → se calcula automáticamente como `inicio + 1 hora`.
2. La función `autoEnd(day)` se llama desde el evento `@change` del input de inicio: `@change="autoEnd('lun'); propagate('lun')"`.
3. También se llama al activar un nuevo día en el selector (después de copiar el ref de otro día).

```js
autoEnd(day) {
    var start = this.times[day].start;
    if (!start || this.times[day].end) return; // no sobreescribir fin existente
    var h = parseInt(start.split(':')[0]) + 1;
    if (h > 23) h = 23;
    this.times[day].end = (h < 10 ? '0' + h : '' + h) + ':' + start.split(':')[1];
}
```

### Propagación de horario (`propagate`)

Al cambiar la hora de inicio de un día, se copia ese horario (inicio y fin) a los demás días seleccionados que aún no tienen hora de inicio asignada.

## `parseSchedule()`

Filtra días con `start` válido (`HH:MM`), conserva `end` si también es `HH:MM`, guarda en orden `lun mar mie jue vie sab dom`. Retorna `null` si no hay días válidos.

## Imagen de portada por nombre

Se asigna automáticamente en vistas según el nombre del curso:
- Contiene `salsa` → `salsa.jpg`
- Contiene `bachata` → `bachata.jpg`
- Contiene `lady` → `lady.jpg`
- Otro → sin imagen (avatar con inicial)

Este patrón se aplica en dashboard, checkin, edit, index, attendance/take, enrollment, reports.
