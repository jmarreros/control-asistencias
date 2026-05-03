# Spec: Importación de Alumnos

- `ImportController@show` → vista con instrucciones del formato CSV y formulario de carga
- `ImportController@import` → procesa el archivo:
  - Detecta automáticamente separador `,` o `;`
  - Acepta fechas `DD/MM/YYYY` o `YYYY-MM-DD`
  - Busca alumno existente por DNI primero, luego por nombre (case-insensitive)
  - Si no existe → crea alumno + plan (si hay datos de plan)
  - Si existe con plan activo (`ok` o `pending`) → omite el plan
  - Si existe sin plan activo → crea el plan
- Columnas CSV: `name` (req), `dni`, `phone`, `start_date`, `end_date`, `nombre_plan`, `price`, `clases_restantes`
- Valores válidos para `nombre_plan`: `8 horas`, `12 horas`, `16 horas`, `24 horas`, `Full-1`, `Full-2`
- Acceso desde `/settings` → sección "Importar datos" al final de la página
