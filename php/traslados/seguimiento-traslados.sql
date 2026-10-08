-- Ejecutar en la base existente para conservar el historial de ubicaciones de cada traslado.
CREATE TABLE IF NOT EXISTS ubicacion_traslado (
    id_ubicacion INT AUTO_INCREMENT,
    id_traslado INT NOT NULL,
    id_conductor INT NOT NULL,
    latitud DECIMAL(10,7) NOT NULL,
    longitud DECIMAL(10,7) NOT NULL,
    precision_metros DECIMAL(10,2) NOT NULL,
    fecha_registro DATETIME NOT NULL,
    PRIMARY KEY (id_ubicacion),
    INDEX idx_ubicacion_traslado (id_traslado, id_ubicacion),
    FOREIGN KEY (id_traslado) REFERENCES traslado(id_traslado),
    FOREIGN KEY (id_conductor) REFERENCES funcionario(id_funcionario)
) ENGINE=InnoDB;
