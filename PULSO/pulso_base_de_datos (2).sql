CREATE TABLE persona (
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
);

-- Separa los datos médicos de los datos personales y registra automáticamente cuándo se creó.
CREATE TABLE paciente (
    id_paciente INT AUTO_INCREMENT,
    id_persona INT NOT NULL UNIQUE,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) NOT NULL DEFAULT 'estable',
    patologia VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id_paciente),
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
);

CREATE TABLE funcionario (
    id_funcionario INT AUTO_INCREMENT,
    id_persona INT NOT NULL UNIQUE,
    cargo VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_funcionario),
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
);

CREATE TABLE tipo_funcionario (
    id_tipo_funcionario INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (id_tipo_funcionario)
);

CREATE TABLE documento (
    id_documento INT AUTO_INCREMENT,
    id_funcionario INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    fecha_carga DATETIME NOT NULL,
    PRIMARY KEY (id_documento),
    FOREIGN KEY (id_funcionario) REFERENCES funcionario(id_funcionario)
);

CREATE TABLE ambulancia (
    matricula VARCHAR(20) NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    capacidad INT NOT NULL,
    estado VARCHAR(50) NOT NULL,
    activa BOOLEAN NOT NULL,
    PRIMARY KEY (matricula)
);

CREATE TABLE traslado (
    id_traslado INT AUTO_INCREMENT,
    id_paciente INT,
    id_funcionario INT ,
    matricula_ambulancia VARCHAR(20) NOT NULL,
    tipo_paciente VARCHAR(100) NOT NULL,
    descripcion_carga VARCHAR(255),
    origen VARCHAR(200) NOT NULL,
    destino VARCHAR(200) NOT NULL,
    prioridad VARCHAR(50) NOT NULL,
    salida_prevista DATETIME NOT NULL,
    salida_real DATETIME,
    llegada_real DATETIME,
    estado VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_traslado),
    FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente),
    FOREIGN KEY (matricula_ambulancia) REFERENCES ambulancia(matricula),
    FOREIGN KEY (id_funcionario) REFERENCES funcionario(id_funcionario)
);


CREATE TABLE equipamiento (
    id_equipamiento INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    activo BOOLEAN NOT NULL,
    PRIMARY KEY (id_equipamiento)
);
