# 🛡️ Tiketera Adex — Sistema de Gestión de Incidencias (PHP Hexagonal)

> **Backend/Core:** PHP 8.0+ · **Frontend:** PHP / HTML5 · **Base de Datos:** SQL Server (`SistemaIncidencias`)  
> **Arquitectura:** Hexagonal (Ports & Adapters) en PHP Puro

---

## 📋 Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura de Carpetas](#estructura-de-carpetas)
4. [Requisitos Previos](#requisitos-previos)
5. [Configuración y Arranque](#configuración-y-arranque)
6. [Base de Datos](#base-de-datos)
7. [API HTTP Interna](#api-http-interna)
8. [Vistas del Sistema](#vistas-del-sistema)

---

## 📖 Descripción General

**Tiketera Adex** es una plataforma web completa para la gestión de incidencias en un entorno educativo, construida en PHP puro bajo arquitectura hexagonal y conectada de forma nativa a **SQL Server**.

El sistema permite:
- 📊 **Dashboard** con estadísticas de incidencias abiertas, resueltas, promedio de resolución y timelines de actividad.
- ⚠️ **Gestión de Incidencias** — Registro, filtrado combinado por prioridad y estado, resolución y exportación a CSV.
- 🏫 **Gestión de Aulas** — Administración de aulas, capacidades, ubicaciones físicas y equipamiento.
- 💬 **Chat de Soporte en Vivo** — Sistema de mensajería persistido en la base de datos asociado a las incidencias de soporte técnico.
- 👥 **Gestión de Usuarios** — Listado del personal del sistema con sus respectivos roles y estados.

---

## 🏗️ Arquitectura del Sistema

El sistema implementa **Arquitectura Hexagonal (Puertos y Adaptadores)** en PHP puro para desacoplar el core del negocio de la tecnología de persistencia e interfaces de entrada.

```
┌─────────────────────────────────────────────────────────────────┐
│                      MUNDO EXTERIOR                             │
│                                                                 │
│   ┌──────────────┐              ┌────────────────────┐          │
│   │  Frontend    │              │   SQL Server        │          │
│   │  HTML/PHP    │              │   (Base de Datos)   │          │
│   └──────┬───────┘              └────────────┬────────┘          │
│          │                                   │                   │
│   ───────┼───────────────────────────────────┼────────────────  │
│          │ (Request HTTP)                    │                   │
│          ▼                                   ▼                   │
│   ┌──────┴───────────────────────────────────┴────────────┐     │
│   │                 APLICACIÓN PHP (Hexágono)             │     │
│   │                                                       │     │
│   │  ┌──────────────────┐      ┌────────────────────────┐ │     │
│   │  │  Adaptador HTTP  │      │  Adaptador SQL Server  │ │     │
│   │  │  (api/*.php)     │      │  (Infrastructure)      │ │     │
│   │  └────────┬─────────┘      └────────────┬───────────┘ │     │
│   │           │    PUERTOS (Interfaces)     │             │     │
│   │           └────────────┬────────────────┘             │     │
│   │                        ▼                              │     │
│   │          ┌──────────────────────────┐                 │     │
│   │          │       CORE / DOMINIO     │                 │     │
│   │          │  Entidades + Casos de Uso│                 │     │
│   │          └──────────────────────────┘                 │     │
│   └───────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────┘
```

### Componentes Clave

| Capa | Ubicación | Descripción |
|---|---|---|
| **Dominio (Core)** | `src/Domain/` | **Entidades** (`Aula`, `Incidencia`, etc.) y **Puertos/Interfaces** que definen los contratos de persistencia. Sin dependencias externas. |
| **Casos de Uso** | `src/Application/` | Reglas de negocio puras (`CreateIncidenciaUseCase`, `GetDashboardStatsUseCase`, etc.). |
| **Adaptador de Salida** | `src/Infrastructure/` | Conexión e implementación concreta para SQL Server usando la extensión nativa `sqlsrv` de PHP. |
| **Adaptadores de Entrada** | `public/api/` y `public/*.php` | Endpoints API y vistas que capturan peticiones de usuario e invocan los Casos de Uso. |

---

## 📁 Estructura de Carpetas

```
SistemIncidencia/
│
├── 📄 README.md                     ← Este archivo
├── 📄 start.ps1                     ← Script de inicio rápido de servidor de desarrollo PHP
├── 📄 .gitignore                    ← Exclusión de temporales y configuraciones locales
│
├── 📂 database/
│   └── 📄 setup.sql                 ← Script de estructura y datos iniciales de SQL Server
│
└── 📂 frontend/
    ├── 📂 src/                      ← Código fuente Hexagonal (PHP)
    │   ├── 📄 autoload.php          ← Autoloader PSR-4 personalizado (namespace App\)
    │   │
    │   ├── 📂 Domain/               ← Capa de Dominio (Hexágono interior)
    │   │   ├── 📂 Entities/         ← Entidades puras (Aula, Incidencia, Usuario, ChatConversacion, ChatMensaje)
    │   │   └── 📂 Ports/            ← Puertos (Interfaces del repositorio)
    │   │
    │   ├── 📂 Application/          ← Capa de Aplicación (Casos de Uso)
    │   │   └── 📂 UseCases/         ← Casos de negocio específicos (Aula, Incidencia, Usuario, Chat, Dashboard)
    │   │
    │   └── 📂 Infrastructure/       ← Capa de Infraestructura (Adaptadores de Salida)
    │       └── 📂 Persistence/
    │           └── 📂 Sqlsrv/       # Implementación específica de persistencia con SQL Server (sqlsrv)
    │
    └── 📂 public/                   ← Capa Web (Vistas y API enrutadora)
        ├── 📄 index.php             ← Vista: Dashboard principal
        ├── 📄 incidencias.php       ← Vista: Gestión de Incidencias
        ├── 📄 aulas.php             ← Vista: Gestión de Aulas
        ├── 📄 chat.php              ← Vista: Soporte en Vivo (Chat de incidencias)
        ├── 📄 usuarios.php          ← Vista: Listado de Personal
        ├── 📄 reportes.php          ← Vista: Módulo de Reportes
        │
        ├── 📂 api/                  ← Adaptador de Entrada HTTP (Controladores de API JSON)
        │   ├── 📄 config.php        ← Configuración global de la conexión a SQL Server
        │   ├── 📄 dashboard.php
        │   ├── 📄 incidencias.php
        │   ├── 📄 aulas.php
        │   ├── 📄 chat.php
        │   └── 📄 usuarios.php
        │
        ├── 📂 components/           ← Cabecera, sidebar, topbar y pie de página reutilizables
        └── 📂 css/                  ← Estilos globales y layouts responsivos
```

---

## ✅ Requisitos Previos

1. **PHP:** Versión `8.0+`
2. **SQL Server:** 2017 o superior.
3. **Controlador ODBC:** Microsoft ODBC Driver 17 o 18 para SQL Server.
4. **Extensiones PHP:** `sqlsrv` y `pdo_sqlsrv` instaladas y activas en el archivo `php.ini`.
   
   *Si utilizas XAMPP en Windows con PHP 8.0, asegúrate de habilitar en tu `php.ini`:*
   ```ini
   extension=php_sqlsrv.dll
   extension=php_pdo_sqlsrv.dll
   ```

---

## 🚀 Configuración y Arranque

### Paso 1 — Estructura de la Base de Datos

Abre tu gestor de base de datos SQL Server (ej: SSMS) y ejecuta el archivo:
📄 `database/setup.sql`

Este script creará o poblará la base de datos **`SistemaIncidencias`** con las tablas del sistema:
- `Rol` y `Persona` (unificados en `Usuario`)
- `Aula` y `QRAula`
- `Incidencia` (que representa las incidencias y cabecera de chats)
- `MensajeIncidencia` (para el chat en tiempo real)

### Paso 2 — Configuración del Servidor y Credenciales

Edita el archivo [frontend/public/api/config.php](file:///C:/xampp/htdocs/SistemIncidencia/frontend/public/api/config.php) para configurar el acceso al servidor de base de datos:

```php
define('DB_SERVER',   'GUTIERREZ\\MSSQLSERVERMULTI');  // Instancia de tu SQL Server
define('DB_NAME',     'SistemaIncidencias');          // Base de datos del sistema
```
*(Nota: Al omitir `UID` y `PWD`, el sistema usará de forma segura la **Autenticación de Windows** / Trusted Connection de la máquina local).*

### Paso 3 — Iniciar el Servidor de Desarrollo

1. Abre una consola de PowerShell.
2. Ejecuta el script de inicio rápido en la raíz del proyecto:
   ```powershell
   .\start.ps1
   ```
3. Accede desde tu navegador a:
   👉 **`http://localhost:8000`**

---

## 💬 Módulo de Chat

El chat de soporte técnico utiliza un mecanismo de **Polling Diferencial** optimizado en JS que consume la API PHP de forma asíncrona cada 3 segundos:

```
Vista Web (chat.php)            API HTTP (api/chat.php)             SQL Server (DB)
      │                                    │                               │
      │── 1. Cargar mensajes ─────────────►│── Invoca UseCase ────────────►│
      │◄── [historial completo] ───────────│◄── Retorna entidades ─────────│
      │                                    │                               │
      │   (Cada 3 segundos)                │                               │
      │── 2. GET mensajes nuevos ─────────►│── Invoca UseCase (desdeId) ──►│
      │◄── [solo mensajes recibidos] ──────│◄── Retorna nuevos registros ───│
      │                                    │                               │
      │── 3. Enviar mensaje ──────────────►│── Invoca SendMessageUseCase ──►│
      │◄── { mensaje enviado } ────────────│◄── INSERT en base de datos ───│
```

---

## 🖥️ Vistas Desarrolladas

- **Dashboard (`index.php`):** Gráficos analíticos con Chart.js de comportamiento semanal y distribución de incidencias por estado, junto a un timeline histórico.
- **Incidencias (`incidencias.php`):** Grid principal con filtros inteligentes de estado y nivel de prioridad, acciones para detallar, resolver y botón para exportación CSV.
- **Aulas (`aulas.php`):** Gestión cuadrícula de aulas según su estado actual (`Operativo`, `Mantenimiento`, `Con Incidencias`).
- **Chat Live (`chat.php`):** Canal directo de comunicación en vivo para interactuar y reportar problemas vinculados a incidencias específicas.
- **Personal (`usuarios.php`):** Administración visual de la lista de usuarios y roles registrados.

---
*Tiketera Adex — Sistema de Gestión de Incidencias estructurado en Arquitectura Hexagonal*
