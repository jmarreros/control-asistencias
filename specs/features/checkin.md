# Spec: Registrar Asistencias (Kiosko de entrada)

Pantalla de uso exclusivo del administrador, optimizada para portátil, donde los alumnos ingresan su DNI y presionan Enter para marcar su asistencia sin intervención manual del admin.

---

## Rutas

```
GET    /checkin                  CheckinController@show         ← pantalla kiosko
POST   /checkin                  CheckinController@store        ← marcar asistencia por DNI (JSON)
DELETE /checkin                  CheckinController@destroy      ← eliminar asistencia por DNI (JSON)
GET    /checkin/attendances      CheckinController@attendances  ← asistencias presentes del día para un curso
GET    /checkin/detect-clase     CheckinController@detectClase  ← detección interna (usada por show())
```

Protegidas por `check.pin` + `session.timeout` + `log.access`.  
Acceso desde el dashboard admin: tarjeta "Registrar Asistencias / Kiosko de entrada por DNI" encima de la lista de cursos.

---

## Auto-detección de curso activo

Ocurre **solo en el servidor** al cargar la página (`show()`). No hay botón manual de refresco.

Lógica de `resolveCurrentClase()`:

1. Obtener hora y día actual (`Carbon::now()`).
2. Mapear día de la semana al key del JSON de `schedule`: `lun mar mie jue vie sab dom`.
3. Para cada `Clase::where('active', true)->get()`:
   - Si el día actual no existe en `schedule` → descartar.
   - Calcular ventana válida: `[start - 15min, end - 15min]`.
   - Si `now` está dentro de esa ventana → clase en curso.
4. Se pre-selecciona la primera clase que coincida; el admin puede cambiar manualmente.
5. Si ninguna coincide → selector vacío; el admin elige.

Ejemplo con `schedule = {"lun": {"start": "18:00", "end": "19:30"}}`:
- Ventana válida lunes: 17:45 – 19:15.
- Si `now` es 18:10 → clase en curso ✔.
- Si `now` es 19:20 → fuera de ventana ✗.

---

## UI — `checkin/show.blade.php`

Usa `layouts/app.blade.php`. No mobile-first — centrado en pantalla de portátil dentro del `max-w-lg` del layout.

### Cabecera

- Título: **"Registrar Asistencias"** + fecha del día.
- Selector de curso (`<select>` Alpine): lista todos los cursos activos ordenados por nombre.
  - Opción pre-seleccionada: curso detectado por horario (o vacía si ninguno coincide).
  - Al cambiar: la lista de asistencias se recarga desde el servidor (`GET /checkin/attendances`).
- Imagen del curso a la izquierda del `<select>`: `salsa.jpg` / `bachata.jpg` / `lady.jpg` según el nombre; círculo vacío (`bg-white/10`) si no hay imagen o no hay selección. Calculado con getter Alpine reactivo `claseImage`.
- Badge de estado:
  - Verde "En horario" → curso auto-detectado por ventana horaria.
  - Ámbar "Selección manual" → el admin cambió el selector.

### Campo DNI

- `<input type="text" inputmode="numeric" autocomplete="off">` grande (`text-4xl`), centrado, siempre enfocado.
- Al presionar Enter → `POST /checkin` vía `fetch`.
- Se limpia y recupera el foco automáticamente tras cada respuesta.

### Feedback visual

Área debajo del campo DNI, visible **3 segundos** con animación fade:

| Estado | Color | Mensaje |
|---|---|---|
| `ok` (inscrito) | verde | "Bienvenido/a, {nombre}" |
| `ok` + `not_enrolled` | verde/naranja | "Bienvenido/a, {nombre}" + aviso de inscripción automática |
| `already` | ámbar | "{nombre} ya registró asistencia hoy" |
| `not_found` | rojo | "DNI no encontrado" |
| `no_clase` | rojo | "Selecciona un curso primero" |
| `error` | rojo | "Error de conexión" |

### Lista de asistencias de la sesión

Debajo del feedback. Se carga desde el servidor al entrar a la pantalla y cada vez que cambia el curso seleccionado. Ordenada de **más reciente a más antigua** (getter `sortedSessionList` con `sort` por campo `time` descendente).

Columnas por fila: hora · nombre · badge de estado · botón eliminar (X).

**Eliminar asistencia** — confirmación inline por fila:
1. Admin pulsa la X → la fila cambia a estado de confirmación: `"¿Eliminar asistencia de {nombre}? [Sí, eliminar] [Cancelar]"`.
2. Solo una fila puede estar en confirmación a la vez (`confirmingId` en Alpine).
3. "Sí, eliminar" → `DELETE /checkin` → marca `present = false` en BD + restaura `classes_remaining` → retira la fila.
4. "Cancelar" → la fila vuelve al estado normal.

---

## `CheckinController@store`

Request JSON: `{ "dni": "12345678", "clase_id": 3 }` — la fecha siempre es `today()` server-side.

Flujo:
1. Buscar alumno activo por DNI → no encontrado → `{ "status": "not_found" }`.
2. Si el alumno no está inscrito en la clase → inscribir con `syncWithoutDetaching` y continuar.
3. Comprobar asistencia existente para `(clase_id, student_id, today)`:
   - Ya presente → `{ "status": "already", "name": ..., "student_id": ... }`.
4. `updateOrCreate` con `present = true` + `plan_id` resuelto.
5. Decrementar `classes_remaining` si cambia de ausente a presente.
6. Retornar `{ "status": "ok", "student_id": ..., "name": ..., "not_enrolled": bool }`.

## `CheckinController@destroy`

Request JSON: `{ "student_id": 5, "clase_id": 3 }` — opera siempre sobre `today()`.

Flujo:
1. Buscar `Attendance` con `present = true` para `(clase_id, student_id, today)`.
2. Si no existe → responder `{ "ok": true }` sin error.
3. `update(['present' => false])`.
4. Si `plan_id` no es null → `adjustRemaining(+1)` para restaurar la clase al plan.
5. Retornar `{ "ok": true }`.

## `CheckinController@attendances`

`GET /checkin/attendances?clase_id=X`

Retorna array JSON con los registros `present = true` de hoy para ese curso, ordenados `updated_at DESC`:

```json
[
  { "studentId": 12, "name": "Ana García", "time": "18:15", "status": "ok", "notEnrolled": false },
  ...
]
```

---

## Consideraciones de UX

- El campo DNI recupera el foco automáticamente tras cada checkin, eliminación o cambio de curso.
- La pantalla lleva `<meta name="turbo-cache-control" content="no-cache">`.
- Si no hay curso seleccionado, Enter muestra estado `no_clase` sin llamar al servidor.
- Al cambiar de curso la lista se recarga desde BD; el foco vuelve al input.

---

## Integración con el sistema existente

- Reutiliza la misma lógica de `plan_id` y `classes_remaining` que `AttendanceController@toggle`.
- **No reemplaza** los flujos existentes (`/attendance`, `/attendance/{clase}/take`): es complementario, pensado para el momento de llegada masiva al inicio de la clase.
- Los registros creados o eliminados desde este kiosko aparecen reflejados en tiempo real en `/attendance/{clase}/take`.
