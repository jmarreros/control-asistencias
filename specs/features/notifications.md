# Spec: Notificaciones WhatsApp

- `notify_days_before` (default 3): umbral de días para marcar un plan como "por vencer"
- `notify_classes_remaining` (default 1): umbral de clases restantes para alertar
- `notify_message`: plantilla del mensaje; variables `{nombre}`, `{clases}`, `{fecha}`
- `notify_expired_message`: plantilla para plan vencido; variables `{nombre}`, `{fecha}`
- `StudentController@index` usa `Setting::preload([...])` para cargar los 4 settings en una sola query, luego calcula `isExpiring`, `waUrl` y `waUrlExpired` para cada alumno
- Teléfono normalizado: se quitan no-dígitos; si quedan 9 dígitos se antepone `51` (Perú)
- URL generada: `https://wa.me/{phone}?text={encoded_message}`
