-- Ejecutar una sola vez si la base ya existia antes de asociar documentos a pacientes.
-- Las columnas antiguas quedan opcionales para no borrar informacion existente.
ALTER TABLE documento
    ADD COLUMN id_paciente INT NULL AFTER id_documento,
    ADD COLUMN token_descarga CHAR(64) NULL AFTER fecha_carga,
    MODIFY COLUMN id_funcionario INT NULL,
    MODIFY COLUMN id_estado_documento INT NULL,
    ADD UNIQUE INDEX uq_documento_token (token_descarga),
    ADD CONSTRAINT fk_documento_paciente
        FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente)
        ON DELETE SET NULL;
