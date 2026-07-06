# ⚙️ Guía de Configuración y Arranque
## SysIncidencias

---

## Requisitos del Sistema

| Herramienta | Versión Mínima | Cómo Verificar |
|---|---|---|
| Elixir | 1.14 | `elixir --version` |
| Erlang/OTP | 25 | `erl -version` |
| Python | 3.6+ | `python --version` |
| SQL Server | 2019 | SSMS → About |
| ODBC Driver | 17+ | Panel de control → ODBC |

---

## Paso 1 — Instalar Elixir en Windows

1. Ir a [https://elixir-lang.org/install.html#windows](https://elixir-lang.org/install.html#windows)
2. Descargar el instalador (incluye Erlang/OTP automáticamente)
3. Ejecutar el `.exe` y seguir el asistente
4. **Reiniciar la terminal** después de instalar
5. Verificar:

```powershell
elixir --version
# Elixir 1.16.x (compiled with Erlang/OTP 26)

mix --version
# Mix 1.16.x
```

---

## Paso 2 — Instalar ODBC Driver para SQL Server

Este driver es necesario para que Elixir se conecte a SQL Server.

1. Ir a: [https://aka.ms/downloadmsodbcsql](https://aka.ms/downloadmsodbcsql)
2. Descargar **ODBC Driver 17 for SQL Server** (Windows x64)
3. Instalar el `.msi`

---

## Paso 3 — Configurar SQL Server

### Habilitar conexiones TCP/IP

1. Abrir **SQL Server Configuration Manager**
2. Ir a: `SQL Server Network Configuration → Protocols for MSSQLSERVER`
3. Click derecho en **TCP/IP** → **Enable**
4. Reiniciar el servicio SQL Server

### Habilitar autenticación SQL

1. En SSMS, click derecho en el servidor → **Properties**
2. Ir a **Security**
3. Seleccionar **SQL Server and Windows Authentication mode**
4. Reiniciar SQL Server

### Crear el usuario `sa` (si no existe)

```sql
-- En SSMS como administrador:
ALTER LOGIN sa ENABLE;
ALTER LOGIN sa WITH PASSWORD = 'TuContraseña123!';
```

---

## Paso 4 — Crear la Base de Datos

Abrir SSMS y ejecutar el archivo `database/setup.sql`:

```sql
-- Opción 1: Desde SSMS
-- File → Open → setup.sql → F5 (Ejecutar)

-- Opción 2: Desde PowerShell (sqlcmd debe estar instalado)
sqlcmd -S localhost -U sa -P "TuContraseña" -i database/setup.sql
```

**Resultado esperado:**
```
Tabla usuarios creada.
Tabla aulas creada.
Tabla incidencias creada.
Tabla chat_conversaciones creada.
Tabla chat_mensajes creada.
Datos de aulas insertados.
Usuarios iniciales insertados.
✅ Base de datos SistemIncidenciaDB configurada correctamente.
```

---

## Paso 5 — Configurar el Backend Elixir

Editar `backend/config/config.exs` con tus credenciales reales:

```elixir
import Config

config :sistem_incidencia, SistemIncidencia.Adapters.DB.Repo,
  adapter: Ecto.Adapters.Tds,
  hostname: "localhost",          # Cambiar si SQL Server está en otro servidor
  database: "SistemIncidenciaDB",
  username: "sa",                 # Usuario SQL Server
  password: "TuContraseña123!",   # ⚠️ Cambiar por tu contraseña real
  port: 1433

config :sistem_incidencia, ecto_repos: [SistemIncidencia.Adapters.DB.Repo]
```

---

## Paso 6 — Instalar Dependencias Elixir

```powershell
cd backend
mix deps.get
```

Esto descargará automáticamente:
- `plug_cowboy` (servidor HTTP)
- `jason` (JSON)
- `ecto_sql` + `tds` (SQL Server)
- `cors_plug` (CORS)

---

## Paso 7 — Levantar el Backend

```powershell
cd backend
mix run --no-halt
```

Deberías ver algo como:
```
[info] Running SistemIncidencia.Adapters.HTTP.Router with Cowboy using http://0.0.0.0:4000
```

### Verificar que funciona:
```powershell
Invoke-WebRequest http://localhost:4000/api/health
# StatusCode: 200
# Content: {"status":"ok","timestamp":"..."}
```

---

## Paso 8 — Levantar el Frontend

```powershell
cd frontend/public
python -m http.server 8080
```

---

## ⚡ Script de Arranque Rápido

Una vez configurado todo, puedes usar:

```powershell
# Desde la raíz del proyecto
.\start.ps1
```

Este script abre dos terminales: una para el backend y otra para el frontend.

---

## 🌐 URLs del Sistema

| Servicio | URL | Estado esperado |
|---|---|---|
| Frontend (Dashboard) | http://localhost:8080 | ✅ Página web |
| Frontend (Chat) | http://localhost:8080/chat.html | ✅ Chat en vivo |
| Frontend (Aulas) | http://localhost:8080/aulas.html | ✅ Gestión |
| Frontend (Incidencias) | http://localhost:8080/incidencias.html | ✅ Listado |
| API Health | http://localhost:4000/api/health | ✅ JSON ok |
| API Conversaciones | http://localhost:4000/api/chat/conversaciones | ✅ JSON array |

---

## ❓ Solución de Problemas Frecuentes

### Error: `mix` no reconocido
→ Elixir no está instalado o no está en el PATH. Reinstalar y reiniciar la terminal.

### Error: `ODBC connection failed`
→ Verificar que:
- SQL Server está corriendo (services.msc → SQL Server (MSSQLSERVER))
- TCP/IP está habilitado en SQL Server Configuration Manager
- La contraseña en `config.exs` es correcta
- El ODBC Driver 17 está instalado

### Error de CORS en el frontend
→ El backend no está corriendo. Ejecutar `mix run --no-halt` en la carpeta `backend/`.

### El chat muestra "Backend no disponible"
→ El backend Elixir debe estar corriendo en `http://localhost:4000` antes de abrir el chat.

---

*Guía de Configuración — SysIncidencias*
