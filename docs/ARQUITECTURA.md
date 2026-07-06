# 🏗️ Arquitectura del Sistema — SysIncidencias

## Arquitectura Hexagonal (Ports & Adapters)

Este documento explica en detalle la arquitectura adoptada en el proyecto y por qué se eligió.

---

## ¿Por qué Arquitectura Hexagonal?

La **Arquitectura Hexagonal** (también llamada *Ports and Adapters*, propuesta por Alistair Cockburn en 2005) tiene como objetivo **aislar el núcleo de negocio** de los detalles técnicos externos como bases de datos, frameworks web o interfaces de usuario.

### Beneficios aplicados a este proyecto

| Beneficio | Ejemplo concreto |
|---|---|
| **Independencia de BD** | Cambiar de SQL Server a PostgreSQL solo requiere cambiar el adaptador `Tds` por `Postgrex`, sin tocar el dominio |
| **Testabilidad** | El Core puede testearse con repositorios en memoria (mocks) sin BD real |
| **Flexibilidad del frontend** | El frontend puede ser PHP, HTML puro, React o cualquier cliente HTTP |
| **Separación de responsabilidades** | Cada capa tiene una sola razón para cambiar |

---

## Las 3 Capas Principales

### 🔵 1. Core (Dominio)
**Ubicación:** `backend/lib/sistem_incidencia/core/`  
**Responsabilidad:** Contiene la lógica de negocio pura.

- **Entidades:** Representan los objetos del negocio (Incidencia, ChatMensaje, ChatConversacion)
- **No importa** ninguna librería externa (ni Ecto, ni Plug, ni Jason)
- **No sabe** cómo se persisten los datos, ni cómo se expone la API

```
core/
├── incidencia.ex          ← Entidad: datos + constructor
├── chat_mensaje.ex        ← Entidad: mensaje de chat
├── chat_conversacion.ex   ← Entidad: conversación
└── ports/
    ├── incidencia_repository.ex   ← Puerto (interfaz @behaviour)
    └── chat_repository.ex         ← Puerto (interfaz @behaviour)
```

**Ejemplo de Entidad:**
```elixir
defmodule SistemIncidencia.Core.Incidencia do
  defstruct [:id, :titulo, :descripcion, :estado, :fecha_creacion]

  def new(titulo, descripcion) do
    %__MODULE__{
      titulo:         titulo,
      descripcion:    descripcion,
      estado:         "abierta",
      fecha_creacion: DateTime.utc_now()
    }
  end
end
```

### 🔌 2. Puertos (Interfaces)
**Ubicación:** `backend/lib/sistem_incidencia/core/ports/`  
**Responsabilidad:** Definen **contratos** entre el Core y el mundo exterior.

Un Puerto declara _qué operaciones_ existen, sin decir _cómo_ se implementan.

```elixir
defmodule SistemIncidencia.Core.Ports.ChatRepository do
  @callback get_conversaciones() :: [map()]
  @callback enviar_mensaje(map()) :: {:ok, map()} | {:error, any()}
  # ...
end
```

Esto garantiza que si mañana cambiamos de SQL Server a MongoDB, el Core no sabe ni le importa.

### 🟢 3. Adaptadores (Implementaciones)
**Ubicación:** `backend/lib/sistem_incidencia/adapters/`  
**Responsabilidad:** Implementan los puertos usando tecnologías concretas.

```
adapters/
├── db/                            ← Adaptador de Persistencia
│   ├── repo.ex                    → Conexión Ecto a SQL Server
│   ├── chat_repository_impl.ex   → Implementa ChatRepository con Ecto
│   ├── incidencia_repository_impl.ex
│   └── schemas/                  → Mapeo tablas SQL ↔ structs Elixir
│       ├── chat_conversacion_schema.ex
│       ├── chat_mensaje_schema.ex
│       └── incidencia_schema.ex
└── http/                          ← Adaptador de Entrada (Driver)
    └── router.ex                  → API REST con Plug/Cowboy
```

---

## Flujo de Datos Completo

### Ejemplo: Usuario envía un mensaje de chat

```
1. HTTP POST /api/chat/conversaciones/:id/mensajes
        │
        ▼
2. Router (Adaptador HTTP)
   └─ Parsea JSON del body
   └─ Llama a ChatRepositoryImpl.enviar_mensaje(attrs)
        │
        ▼
3. ChatRepositoryImpl (Adaptador DB)
   └─ Crea changeset con ChatMensajeSchema
   └─ Llama a Repo.insert()
        │
        ▼
4. Ecto + Tds (Driver SQL Server)
   └─ INSERT INTO chat_mensajes (...)
        │
        ▼
5. SQL Server
   └─ Persiste el registro
   └─ Devuelve el registro insertado
        │
        ▲
6. Respuesta sube por la cadena
   └─ Repo.insert() → {:ok, schema}
   └─ ChatRepositoryImpl → {:ok, map}
   └─ Router → JSON 201 Created
        │
        ▼
7. Frontend recibe el mensaje guardado
   └─ Muestra en la burbuja de chat
```

---

## Diagrama de Dependencias

```
Router ──────────────────────────────────────────────────────────┐
  │ usa directamente                                             │
  ▼                                                             │
ChatRepositoryImpl ────────────────────────────────────────────► │
  │ implementa @behaviour                                       │
  ▼                                                             │
ChatRepository (Puerto) ◄── Core no importa el impl.            │
  ▲                                                             │
  │ define contrato                                             │
ChatMensaje / ChatConversacion (Entidades)                       │
                                                                │
Repo (Ecto) ◄─────────────────────────────────────────────────── ┘
  │
  ▼
SQL Server (TDS Driver)
```

**Regla clave:** Las flechas de dependencia siempre apuntan hacia el Core, nunca hacia afuera.

---

## Ventajas de esta Arquitectura en Elixir

Elixir es ideal para la Arquitectura Hexagonal porque:

1. **`@behaviour`** — mecanismo nativo para definir puertos (interfaces)
2. **Supervisión OTP** — el Supervisor gestiona el ciclo de vida del Repo y el Router
3. **Pattern Matching** — hace que el manejo de casos de error sea explícito (`{:ok, ...}` / `{:error, ...}`)
4. **Concurrencia** — Cowboy maneja múltiples peticiones HTTP simultáneas sin bloqueo

---

*Arquitectura — SysIncidencias*
