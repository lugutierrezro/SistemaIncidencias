-- =============================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS
-- Sistema de Incidencias - SQL Server
-- =============================================

USE master;
GO

IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = 'sistemincidencia')
BEGIN
  CREATE DATABASE sistemincidencia;
END
GO

USE sistemincidencia;
GO

-- ─── TABLA: USUARIOS ───────────────────────────────────────────
IF OBJECT_ID('dbo.usuarios', 'U') IS NULL
BEGIN
  CREATE TABLE dbo.usuarios (
    id            UNIQUEIDENTIFIER  NOT NULL DEFAULT NEWID() PRIMARY KEY,
    nombre        NVARCHAR(150)     NOT NULL,
    email         NVARCHAR(255)     NOT NULL UNIQUE,
    rol           NVARCHAR(50)      NOT NULL DEFAULT 'usuario',  -- 'admin', 'soporte', 'usuario'
    estado        NVARCHAR(30)      NOT NULL DEFAULT 'activo',
    inserted_at   DATETIME2         NOT NULL DEFAULT GETUTCDATE(),
    updated_at    DATETIME2         NOT NULL DEFAULT GETUTCDATE()
  );
  PRINT 'Tabla usuarios creada.'
END
GO

-- ─── TABLA: AULAS ──────────────────────────────────────────────
IF OBJECT_ID('dbo.aulas', 'U') IS NULL
BEGIN
  CREATE TABLE dbo.aulas (
    id            UNIQUEIDENTIFIER  NOT NULL DEFAULT NEWID() PRIMARY KEY,
    nombre        NVARCHAR(150)     NOT NULL,
    edificio      NVARCHAR(150),
    piso          NVARCHAR(50),
    capacidad     INT               NOT NULL DEFAULT 30,
    equipamiento  NVARCHAR(500),
    estado        NVARCHAR(50)      NOT NULL DEFAULT 'disponible', -- 'disponible','mantenimiento','incidencia'
    inserted_at   DATETIME2         NOT NULL DEFAULT GETUTCDATE(),
    updated_at    DATETIME2         NOT NULL DEFAULT GETUTCDATE()
  );
  PRINT 'Tabla aulas creada.'
END
GO

-- ─── TABLA: INCIDENCIAS ────────────────────────────────────────
IF OBJECT_ID('dbo.incidencias', 'U') IS NULL
BEGIN
  CREATE TABLE dbo.incidencias (
    id            UNIQUEIDENTIFIER  NOT NULL DEFAULT NEWID() PRIMARY KEY,
    titulo        NVARCHAR(255)     NOT NULL,
    descripcion   NVARCHAR(MAX),
    estado        NVARCHAR(50)      NOT NULL DEFAULT 'abierta', -- 'abierta','en_proceso','resuelta','critica'
    prioridad     NVARCHAR(30)      NOT NULL DEFAULT 'media',   -- 'alta','media','baja'
    aula_id       UNIQUEIDENTIFIER  NULL REFERENCES dbo.aulas(id),
    reportado_por NVARCHAR(200),
    asignado_a    UNIQUEIDENTIFIER  NULL REFERENCES dbo.usuarios(id),
    fecha_cierre  DATETIME2         NULL,
    inserted_at   DATETIME2         NOT NULL DEFAULT GETUTCDATE(),
    updated_at    DATETIME2         NOT NULL DEFAULT GETUTCDATE()
  );
  PRINT 'Tabla incidencias creada.'
END
GO

-- ─── TABLA: CONVERSACIONES DE CHAT ─────────────────────────────
IF OBJECT_ID('dbo.chat_conversaciones', 'U') IS NULL
BEGIN
  CREATE TABLE dbo.chat_conversaciones (
    id              UNIQUEIDENTIFIER  NOT NULL DEFAULT NEWID() PRIMARY KEY,
    titulo          NVARCHAR(255)     NOT NULL,
    incidencia_id   UNIQUEIDENTIFIER  NULL REFERENCES dbo.incidencias(id),
    usuario_nombre  NVARCHAR(150)     NOT NULL,
    estado          NVARCHAR(30)      NOT NULL DEFAULT 'activa', -- 'activa','cerrada','espera'
    inserted_at     DATETIME2         NOT NULL DEFAULT GETUTCDATE(),
    updated_at      DATETIME2         NOT NULL DEFAULT GETUTCDATE()
  );
  PRINT 'Tabla chat_conversaciones creada.'
END
GO

-- ─── TABLA: MENSAJES DE CHAT ───────────────────────────────────
IF OBJECT_ID('dbo.chat_mensajes', 'U') IS NULL
BEGIN
  CREATE TABLE dbo.chat_mensajes (
    id                  UNIQUEIDENTIFIER  NOT NULL DEFAULT NEWID() PRIMARY KEY,
    conversacion_id     UNIQUEIDENTIFIER  NOT NULL REFERENCES dbo.chat_conversaciones(id) ON DELETE CASCADE,
    contenido           NVARCHAR(MAX)     NOT NULL,
    remitente           NVARCHAR(150)     NOT NULL,
    tipo_remitente      NVARCHAR(30)      NOT NULL DEFAULT 'usuario', -- 'usuario','soporte'
    inserted_at         DATETIME2         NOT NULL DEFAULT GETUTCDATE()
  );
  PRINT 'Tabla chat_mensajes creada.'
END
GO

-- ─── ÍNDICES ───────────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_chat_mensajes_conversacion' AND object_id = OBJECT_ID('dbo.chat_mensajes'))
  CREATE INDEX IX_chat_mensajes_conversacion ON dbo.chat_mensajes(conversacion_id, inserted_at);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_incidencias_estado' AND object_id = OBJECT_ID('dbo.incidencias'))
  CREATE INDEX IX_incidencias_estado ON dbo.incidencias(estado);
GO

-- ─── DATOS INICIALES ───────────────────────────────────────────
-- Insertar aulas de ejemplo
IF NOT EXISTS (SELECT 1 FROM dbo.aulas)
BEGIN
  INSERT INTO dbo.aulas (nombre, edificio, piso, capacidad, equipamiento, estado) VALUES
  ('Aula 101',             'Edificio Principal', 'Piso 1',        30, 'proyector,wifi',            'disponible'),
  ('Laboratorio A',        'Bloque B',           'Piso 1',        25, 'pcs,proyector,wifi,ac',     'disponible'),
  ('Laboratorio B',        'Bloque B',           'Piso 2',        25, 'pcs,wifi',                  'mantenimiento'),
  ('Aula 201',             'Edificio Principal', 'Piso 2',        40, 'proyector,pizarra,ac',      'incidencia'),
  ('Sala de Conferencias', 'Edificio A',         'Planta Baja',   60, 'proyector,ac,wifi',         'disponible'),
  ('Aula 102',             'Edificio Principal', 'Piso 1',        35, 'proyector',                 'disponible');
  PRINT 'Datos de aulas insertados.'
END
GO

-- Insertar usuario admin
IF NOT EXISTS (SELECT 1 FROM dbo.usuarios)
BEGIN
  INSERT INTO dbo.usuarios (nombre, email, rol) VALUES
  ('Admin Operaciones', 'admin@sistem.edu', 'admin'),
  ('Soporte TI',        'soporte@sistem.edu', 'soporte');
  PRINT 'Usuarios iniciales insertados.'
END
GO

-- Insertar incidencias de ejemplo
DECLARE @aulaId1 UNIQUEIDENTIFIER = (SELECT TOP 1 id FROM dbo.aulas WHERE nombre = 'Aula 102');
DECLARE @aulaId2 UNIQUEIDENTIFIER = (SELECT TOP 1 id FROM dbo.aulas WHERE nombre = 'Laboratorio B');
DECLARE @aulaId3 UNIQUEIDENTIFIER = (SELECT TOP 1 id FROM dbo.aulas WHERE nombre = 'Aula 201');

IF NOT EXISTS (SELECT 1 FROM dbo.incidencias) AND @aulaId1 IS NOT NULL
BEGIN
  INSERT INTO dbo.incidencias (titulo, descripcion, estado, prioridad, aula_id, reportado_por) VALUES
  ('Proyector Aula 102 no enciende', 'El proyector no enciende, el LED parpadea en rojo.', 'abierta', 'alta', @aulaId1, 'Juan Pérez'),
  ('Fallo de internet en Laboratorio B', 'Ninguna PC tiene acceso a internet por cable.', 'en_proceso', 'media', @aulaId2, 'Ana Gómez'),
  ('Aire acondicionado inoperativo', 'El AC del Aula 201 no enfría, solo ruido.', 'abierta', 'alta', @aulaId3, 'Luisa Martínez');
  PRINT 'Incidencias de ejemplo insertadas.'
END
GO

-- Insertar conversaciones y mensajes de chat de ejemplo
DECLARE @incId UNIQUEIDENTIFIER = (SELECT TOP 1 id FROM dbo.incidencias WHERE titulo LIKE 'Proyector%');

IF NOT EXISTS (SELECT 1 FROM dbo.chat_conversaciones) AND @incId IS NOT NULL
BEGIN
  DECLARE @convId UNIQUEIDENTIFIER = NEWID();
  INSERT INTO dbo.chat_conversaciones (id, titulo, incidencia_id, usuario_nombre, estado) VALUES
  (@convId, 'Proyector Aula 102 no enciende', @incId, 'Juan Pérez', 'activa');

  INSERT INTO dbo.chat_mensajes (conversacion_id, contenido, remitente, tipo_remitente) VALUES
  (@convId, 'Hola, el proyector del Aula 102 no enciende desde esta mañana.', 'Juan Pérez', 'usuario'),
  (@convId, 'Hola Juan, ¿el proyector hace algún sonido al intentar encenderlo?', 'Admin Operaciones', 'soporte'),
  (@convId, 'Hace un clic pero no enciende. El LED de encendido parpadea en rojo.', 'Juan Pérez', 'usuario');

  PRINT 'Chat de ejemplo insertado.'
END
GO

PRINT '✅ Base de datos sistemincidencia configurada correctamente.'
GO
