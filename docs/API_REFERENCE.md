# 📡 Referencia Completa de la API REST
## SysIncidencias — Backend Elixir

**Base URL:** `http://localhost:4000/api`  
**Formato de respuesta:** `application/json`  
**CORS:** Habilitado para `localhost:8080` y `127.0.0.1:8080`

---

## 🟢 Health Check

### `GET /api/health`
Verifica que el backend esté en línea y conectado.

**Respuesta exitosa `200`:**
```json
{
  "status": "ok",
  "timestamp": "2026-07-03T22:00:00Z"
}
```

---

## ⚠️ Incidencias

### `GET /api/incidencias`
Lista todas las incidencias ordenadas por fecha.

**Respuesta `200`:**
```json
[
  {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "titulo": "Proyector Aula 102 no enciende",
    "descripcion": "El proyector no responde al encendido.",
    "estado": "abierta",
    "prioridad": "alta",
    "reportado_por": "Juan Pérez",
    "fecha_creacion": "2026-07-03T22:00:00Z"
  }
]
```

---

### `GET /api/incidencias/:id`
Obtiene una incidencia específica por su UUID.

**Respuesta `200`:** (misma estructura que arriba)  
**Respuesta `404`:**
```json
{ "error": "No encontrada" }
```

---

### `POST /api/incidencias`
Crea una nueva incidencia.

**Body (JSON):**
```json
{
  "titulo":        "Proyector sin señal",
  "descripcion":   "No muestra imagen en el Aula 201.",
  "prioridad":     "alta",
  "reportado_por": "Juan Pérez"
}
```

**Campos:**

| Campo | Tipo | Requerido | Valores |
|---|---|---|---|
| `titulo` | string | ✅ | — |
| `descripcion` | string | ❌ | — |
| `prioridad` | string | ❌ | `alta`, `media`, `baja` |
| `reportado_por` | string | ❌ | — |

**Respuesta `201`:** objeto incidencia creada  
**Respuesta `422`:** error de validación

---

## 💬 Chat — Conversaciones

### `GET /api/chat/conversaciones`
Lista todas las conversaciones, ordenadas por última actualización.

**Respuesta `200`:**
```json
[
  {
    "id": "abc123...",
    "titulo": "Problema con red Aula 102",
    "usuario_nombre": "Juan Pérez",
    "estado": "activa",
    "inserted_at": "2026-07-03T10:00:00Z",
    "updated_at": "2026-07-03T10:24:00Z"
  }
]
```

**Estados posibles:** `activa`, `espera`, `cerrada`

---

### `POST /api/chat/conversaciones`
Crea una nueva conversación de soporte.

**Body (JSON):**
```json
{
  "titulo":         "Problema con proyector",
  "usuario_nombre": "Juan Pérez",
  "incidencia_id":  "550e8400-..."
}
```

| Campo | Tipo | Requerido |
|---|---|---|
| `titulo` | string | ✅ |
| `usuario_nombre` | string | ✅ |
| `incidencia_id` | UUID string | ❌ — para vincular a una incidencia |

**Respuesta `201`:** objeto conversación creada

---

### `PUT /api/chat/conversaciones/:id/cerrar`
Cierra/resuelve una conversación activa.

**Sin body requerido.**

**Respuesta `200`:**
```json
{
  "id": "abc123...",
  "estado": "cerrada",
  ...
}
```

**Respuesta `404`:** conversación no encontrada

---

## 💬 Chat — Mensajes

### `GET /api/chat/conversaciones/:id/mensajes`
Obtiene **todos** los mensajes de una conversación, ordenados cronológicamente.

**Respuesta `200`:**
```json
[
  {
    "id": "msg-uuid-1",
    "conversacion_id": "conv-uuid",
    "contenido": "Hola, el proyector no enciende.",
    "remitente": "Juan Pérez",
    "tipo_remitente": "usuario",
    "inserted_at": "2026-07-03T10:18:00Z"
  },
  {
    "id": "msg-uuid-2",
    "conversacion_id": "conv-uuid",
    "contenido": "Enseguida lo revisamos.",
    "remitente": "Admin Operaciones",
    "tipo_remitente": "soporte",
    "inserted_at": "2026-07-03T10:20:00Z"
  }
]
```

---

### `GET /api/chat/conversaciones/:id/mensajes/nuevos?desde=:msg_id`
Obtiene **solo los mensajes nuevos** después del ID indicado.  
Usado para el **polling eficiente** del frontend (cada 3 segundos).

**Query Params:**

| Param | Tipo | Descripción |
|---|---|---|
| `desde` | UUID string | ID del último mensaje conocido |

**Ejemplo:**
```
GET /api/chat/conversaciones/abc123/mensajes/nuevos?desde=msg-uuid-5
```

**Respuesta `200`:** lista de mensajes más recientes que el indicado.  
Si no hay mensajes nuevos, devuelve `[]`.

---

### `POST /api/chat/conversaciones/:id/mensajes`
Envía y persiste un nuevo mensaje en la conversación.

**Body (JSON):**
```json
{
  "contenido":      "¿Cuándo llegará el técnico?",
  "remitente":      "Juan Pérez",
  "tipo_remitente": "usuario"
}
```

| Campo | Tipo | Requerido | Valores |
|---|---|---|---|
| `contenido` | string | ✅ | — |
| `remitente` | string | ✅ | Nombre de quien escribe |
| `tipo_remitente` | string | ❌ | `usuario` (default) o `soporte` |

**Respuesta `201`:**
```json
{
  "id":              "nuevo-msg-uuid",
  "conversacion_id": "conv-uuid",
  "contenido":       "¿Cuándo llegará el técnico?",
  "remitente":       "Juan Pérez",
  "tipo_remitente":  "usuario",
  "inserted_at":     "2026-07-03T10:30:00Z"
}
```

**Respuesta `400`:** si `contenido` está vacío  
**Respuesta `422`:** error de validación de Ecto

---

## ⚠️ Códigos de Error

| Código | Significado |
|---|---|
| `200` | OK — operación exitosa |
| `201` | Created — recurso creado |
| `400` | Bad Request — datos inválidos o vacíos |
| `404` | Not Found — recurso no encontrado |
| `422` | Unprocessable Entity — error de validación |
| `500` | Internal Server Error — error del servidor |

---

## 🔄 Flujo de Polling del Chat

```
Segundo 0   → Frontend carga TODOS los mensajes (GET /mensajes)
Segundo 3   → Frontend pide SOLO nuevos (GET /mensajes/nuevos?desde=ultimo_id)
Segundo 6   → ídem
...
```

Este mecanismo minimiza datos transferidos y carga en la base de datos.

---

*API Reference — SysIncidencias*
