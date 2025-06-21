CREATE DATABASE IF NOT EXISTS bd_recrea_sys;
USE bd_recrea_sys;
-- DROP DATABASE bd_recrea_sys;
-- Tabla: usuario
CREATE TABLE usuario (
    ID_Usuario INT AUTO_INCREMENT PRIMARY KEY,
    ci CHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    tipo ENUM('Administrador', 'Logistica', 'Tecnico', 'Contabilidad') NOT NULL,
    usuario_asignado VARCHAR(25) NOT NULL DEFAULT 'Aun no tiene',
    contrasena VARCHAR(255) NOT NULL DEFAULT 'Aun no tiene',
    estado ENUM('Pendiente de asignacion', 'Activo', 'Inhabilitado') DEFAULT 'Pendiente de asignacion' NOT NULL
);

CREATE TABLE inicio_sesion (
    ID_Inicio_Sesion INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    usuario_asignado VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_sesion DATETIME NULL,
    FOREIGN KEY (ID_Usuario) REFERENCES usuario(ID_Usuario)
);

-- Tabla para técnicos (extiende de Usuario):
CREATE TABLE Tecnico(
    ID_Tecnico INT PRIMARY KEY,
    Especialidad ENUM('Ensamblador', 'Comprobador', 'Mantenimiento') NOT NULL,
    Cantidad_Actividades INT DEFAULT 0 NOT NULL,
    FOREIGN KEY (ID_Tecnico) REFERENCES usuario(ID_Usuario)
);

-- Tabla para logística (extiende de Usuario):
CREATE TABLE Logistica(
    ID_Logistica INT PRIMARY KEY,
    FOREIGN KEY (ID_Logistica) REFERENCES usuario(ID_Usuario)
);

--  HISTORIAL DE ACTIVIDADES
CREATE TABLE historial_actividades (
    ID_Historial_Actividades INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    descripcion TEXT DEFAULT 'Estuvo en su main',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Usuario) REFERENCES usuario(ID_Usuario)
);

-- Tabla de para comercios:
CREATE TABLE Comercio (
    ID_Comercio INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL UNIQUE,
    Tipo ENUM('Minorista', 'Mayorista') NOT NULL,
    Direccion TEXT NOT NULL,
    Telefono VARCHAR(15) NOT NULL UNIQUE,
    Cantidad_Maquinas INT DEFAULT 0 NOT NULL,
    Fecha_Registro DATE NOT NULL
);

-- Tabla para máquinas recreativas:
CREATE TABLE MaquinaRecreativa (
    ID_Maquina INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_Maquina VARCHAR(100) NOT NULL,
    Tipo VARCHAR(50) NOT NULL,
    Etapa ENUM('Montaje', 'Distribucion', 'Recaudacion') DEFAULT 'Montaje' NOT NULL,
    Estado ENUM('Ensamblandose', 'Comprobandose', 'Reensamblandose', 'Distribuyendose', 'Operativa', 'No operativa', 'Retirada') DEFAULT 'Ensamblándose' NOT NULL,
    Fecha_Registro DATE NOT NULL,
    ID_Tecnico_Ensamblador INT NOT NULL,
    ID_Tecnico_Comprobador INT NOT NULL,
    ID_Comercio INT NOT NULL,
    ID_Tecnico_Mantenimiento INT,
    FOREIGN KEY (ID_Tecnico_Ensamblador) REFERENCES Tecnico(ID_Tecnico),
    FOREIGN KEY (ID_Tecnico_Comprobador) REFERENCES Tecnico(ID_Tecnico),
    FOREIGN KEY (ID_Comercio) REFERENCES Comercio(ID_Comercio),
    FOREIGN KEY (ID_Tecnico_Mantenimiento) REFERENCES Tecnico(ID_Tecnico)
);

-- Tabla para notificaciones que siguen el ciclo de vida de las máquinas recreativas:
CREATE TABLE NotificacionMaquinaRecreativa (
    ID_Notificacion INT AUTO_INCREMENT PRIMARY KEY,
    ID_Remitente INT NOT NULL,
    ID_Destinatario INT NOT NULL,
    ID_Maquina INT NOT NULL,
    Tipo ENUM( -- Solo existen estas 7 notificaciones para el flujo de máquinas recreativas. 
        'Nuevo montaje',
        'Comprobar maquina recreativa',
        'Reensamblar maquina recreativa',
        'Distribuir maquina recreativa',
        'Dar mantenimiento a maquina recreativa',
        'Maquina recreativa retirada',
        'Maquina recreativa reparada'
    ) NOT NULL,
    Mensaje TEXT,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('Leido', 'No leido') DEFAULT 'No leido' NOT NULL,
    FOREIGN KEY (ID_Remitente) REFERENCES Usuario(ID_Usuario),
    FOREIGN KEY (ID_Destinatario) REFERENCES Usuario(ID_Usuario),
    FOREIGN KEY (ID_Maquina) REFERENCES MaquinaRecreativa(ID_Maquina)
);

CREATE INDEX idx_notificacion_maquina_estado ON NotificacionMaquinaRecreativa(Estado);

-- Tabla: componente
CREATE TABLE componente (
    ID_Componente INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('Ensamblador', 'Comprobador', 'Mantenimiento', 'Logistico') NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) DEFAULT 10.00
);

-- Tabla: componente_usuario (para registrar qué técnico está usando qué componente)
CREATE TABLE componente_usuario (
    ID_Registro INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ID_Componente INT NOT NULL,
    ID_Usuario INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_liberacion DATETIME NULL,
    ID_Maquina INT NULL,
    FOREIGN KEY (ID_Componente) REFERENCES componente(ID_Componente),
    FOREIGN KEY (ID_Usuario) REFERENCES usuario(ID_Usuario),
    FOREIGN KEY (ID_Maquina) REFERENCES MaquinaRecreativa(ID_Maquina)
);

CREATE TABLE reporte (
    ID_Reporte INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario_Emisor INT NOT NULL,
    ID_Usuario_Destinatario INT,
    fecha_hora DATETIME NOT NULL,
    descripcion TEXT NOT NULL,
    estado VARCHAR(15) NOT NULL,
    FOREIGN KEY (ID_Usuario_Emisor) REFERENCES usuario(ID_Usuario),
    FOREIGN KEY (ID_Usuario_Destinatario) REFERENCES usuario(ID_Usuario)
);

CREATE TABLE notificaciones (
    ID_Notificaciones INT AUTO_INCREMENT PRIMARY KEY,
    ID_Reporte INT NOT NULL,
    ID_Usuario INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Reporte) REFERENCES reporte(ID_Reporte),
    FOREIGN KEY (ID_Usuario) REFERENCES usuario(ID_Usuario)
);

CREATE TABLE comentario (
    ID_Comentario INT AUTO_INCREMENT PRIMARY KEY,
    ID_Reporte INT NOT NULL,
    ID_Usuario_Emisor INT NOT NULL, 
    fecha_hora DATETIME NOT NULL,
    comentario TEXT NOT NULL,
    FOREIGN KEY (ID_Reporte) REFERENCES reporte(ID_Reporte),
    FOREIGN KEY (ID_Usuario_Emisor) REFERENCES usuario(ID_Usuario)
);

-- Tabla: recaudaciones 
CREATE TABLE recaudaciones (
   ID_Recaudacion INT AUTO_INCREMENT PRIMARY KEY,
   Tipo_Comercio ENUM('Minorista', 'Mayorista') NOT NULL, 
   ID_Maquina INT NOT NULL,
   ID_Usuario INT NOT NULL,
   Monto_Total DECIMAL(10,2) NOT NULL,
   Monto_Empresa DECIMAL(10,2),
   Monto_Comercio DECIMAL(10,2),
   Porcentaje_Comercio DECIMAL(5,2) DEFAULT 0,
    fecha DATETIME NOT NULL,
    detalle TEXT NOT NULL,
    FOREIGN KEY (ID_Usuario) REFERENCES usuario(ID_Usuario),
    FOREIGN KEY (ID_Maquina) REFERENCES MaquinaRecreativa(ID_Maquina)
);

-- Tabla: informe
CREATE TABLE IF NOT EXISTS informes_recaudacion (
  ID_Informe INT AUTO_INCREMENT PRIMARY KEY,
  ID_Recaudacion INT NOT NULL,
  CI_Usuario CHAR(10) NOT NULL,
  Nombre_Maquina VARCHAR(100) NOT NULL,
  ID_Comercio INT NOT NULL,
  Nombre_Comercio VARCHAR(100) NOT NULL,
  Direccion_Comercio TEXT NOT NULL,
  Telefono_Comercio VARCHAR(15) NOT NULL,
  Pago_Ensamblador DECIMAL(10,2) DEFAULT 400.00,
  Pago_Comprobador DECIMAL(10,2) DEFAULT 400.00,
  Pago_Mantenimiento DECIMAL(10,2) DEFAULT 400.00,
  empresa_nombre VARCHAR(100) DEFAULT 'recreasys.s.a',
  empresa_descripcion VARCHAR(255) DEFAULT 'Una empresa encargada en el ciclo de vida de las maquinas recreativas',
  FOREIGN KEY (ID_Recaudacion) REFERENCES recaudaciones(ID_Recaudacion),
  FOREIGN KEY (ID_Comercio) REFERENCES Comercio(ID_Comercio)
);

-- Tabla: informe_detalle
CREATE TABLE IF NOT EXISTS informe_detalle (
  ID_Informe_Detalle INT AUTO_INCREMENT PRIMARY KEY,
  ID_Informe INT NOT NULL,
  ID_Componente INT NOT NULL,
  valor_por_componente DECIMAL(10,2) DEFAULT 10.00,
  FOREIGN KEY (ID_Informe) REFERENCES informes_recaudacion(ID_Informe),
  FOREIGN KEY (ID_Componente) REFERENCES componente(ID_Componente)
);

-- Tabla: distribuciones
CREATE TABLE IF NOT EXISTS informe_distribucion (
  ID_Distribucion INT AUTO_INCREMENT PRIMARY KEY,
  ID_Maquina INT NOT NULL,
  ID_Usuario_Comprobador INT NOT NULL,
  ID_Comercio INT NOT NULL,
  fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_baja DATETIME NULL,
  estado ENUM('Operativa','Retirada','No operativa') DEFAULT 'Operativa',
  FOREIGN KEY (ID_Maquina) REFERENCES MaquinaRecreativa(ID_Maquina),
  FOREIGN KEY (ID_Usuario_Comprobador) REFERENCES usuario(ID_Usuario),
  FOREIGN KEY (ID_Comercio) REFERENCES Comercio(ID_Comercio)
);

-- Tabla: montajes
CREATE TABLE montaje (
    ID_Montaje INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL,
    ID_Maquina INT NOT NULL, 
    ID_Componente INT,
    ID_Tecnico INT,
    detalle TEXT NOT NULL,
    FOREIGN KEY (ID_Maquina) REFERENCES MaquinaRecreativa(ID_Maquina),
    FOREIGN KEY (ID_Componente) REFERENCES componente(ID_Componente),
    FOREIGN KEY (ID_Tecnico) REFERENCES Tecnico(ID_Tecnico)
);