CREATE DATABASE IF NOT EXISTS pulso_entrega
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pulso_entrega;

CREATE TABLE IF NOT EXISTS persona (
    id_persona INT AUTO_INCREMENT,
    ci VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    sexo VARCHAR(20),
    telefono VARCHAR(30),
    direccion VARCHAR(255),
    email VARCHAR(100),
    PRIMARY KEY (id_persona)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS paciente (
    id_paciente INT AUTO_INCREMENT,
    id_persona INT NOT NULL UNIQUE,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) NOT NULL DEFAULT 'estable',
    patologia VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id_paciente),
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tipo_funcionario (
    id_tipo_funcionario INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (id_tipo_funcionario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS funcionario (
    id_funcionario INT AUTO_INCREMENT,
    id_persona INT NOT NULL UNIQUE,
    id_tipo_funcionario INT NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id_funcionario),
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona),
    FOREIGN KEY (id_tipo_funcionario) REFERENCES tipo_funcionario(id_tipo_funcionario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estado_documento (
    id_estado_documento INT AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id_estado_documento)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS documento (
    id_documento INT AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    fecha_carga DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_documento)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estado_ambulancia (
    id_estado_ambulancia INT AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id_estado_ambulancia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ambulancia (
    matricula VARCHAR(20),
    id_estado_ambulancia INT NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    capacidad INT NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (matricula),
    FOREIGN KEY (id_estado_ambulancia) REFERENCES estado_ambulancia(id_estado_ambulancia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipamiento (
    id_equipamiento INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    PRIMARY KEY (id_equipamiento)
) ENGINE=InnoDB;

-- Resuelve la relación muchos a muchos entre ambulancias y equipos.
CREATE TABLE IF NOT EXISTS ambulancia_equipamiento (
    matricula VARCHAR(20),
    id_equipamiento INT,
    cantidad INT NOT NULL DEFAULT 1,
    PRIMARY KEY (matricula, id_equipamiento),
    FOREIGN KEY (matricula) REFERENCES ambulancia(matricula),
    FOREIGN KEY (id_equipamiento) REFERENCES equipamiento(id_equipamiento)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tipo_carga (
    id_tipo_carga INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (id_tipo_carga)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tipo_prioridad (
    id_tipo_prioridad INT AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id_tipo_prioridad)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estado_traslado (
    id_estado_traslado INT AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id_estado_traslado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS traslado (
    id_traslado INT AUTO_INCREMENT,
    id_paciente INT,
    matricula VARCHAR(20) NOT NULL,
    id_creador INT NOT NULL,
    id_conductor INT NOT NULL,
    id_acompanante INT,
    id_tipo_carga INT NOT NULL,
    id_tipo_prioridad INT NOT NULL,
    id_estado_traslado INT NOT NULL,
    descripcion_carga VARCHAR(255),
    origen VARCHAR(200) NOT NULL,
    destino VARCHAR(200) NOT NULL,
    salida_prevista DATETIME NOT NULL,
    salida_real DATETIME,
    llegada_real DATETIME,
    PRIMARY KEY (id_traslado),
    FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente),
    FOREIGN KEY (matricula) REFERENCES ambulancia(matricula),
    FOREIGN KEY (id_creador) REFERENCES funcionario(id_funcionario),
    FOREIGN KEY (id_conductor) REFERENCES funcionario(id_funcionario),
    FOREIGN KEY (id_acompanante) REFERENCES funcionario(id_funcionario),
    FOREIGN KEY (id_tipo_carga) REFERENCES tipo_carga(id_tipo_carga),
    FOREIGN KEY (id_tipo_prioridad) REFERENCES tipo_prioridad(id_tipo_prioridad),
    FOREIGN KEY (id_estado_traslado) REFERENCES estado_traslado(id_estado_traslado)
) ENGINE=InnoDB;

-- Catálogos mínimos para que los próximos formularios tengan opciones reales.
INSERT INTO tipo_funcionario (nombre) VALUES
    ('Médico'),
    ('Enfermería'),
    ('Conductor'),
    ('Administrativo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO estado_documento (nombre) VALUES
    ('Borrador'),
    ('Publicado'),
    ('Archivado')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO estado_ambulancia (nombre) VALUES
    ('Disponible'),
    ('En servicio'),
    ('Mantenimiento')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO tipo_carga (nombre) VALUES
    ('Paciente'),
    ('Insumo médico'),
    ('Equipamiento')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO tipo_prioridad (nombre) VALUES
    ('Baja'),
    ('Media'),
    ('Alta'),
    ('Urgente')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO estado_traslado (nombre) VALUES
    ('Programado'),
    ('En curso'),
    ('Finalizado'),
    ('Cancelado')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
