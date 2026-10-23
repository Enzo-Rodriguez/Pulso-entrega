-- Agrega el enlace de acceso solo si falta. Se puede ejecutar nuevamente.
-- No agrega la asignacion a pacientes ni borra columnas o datos anteriores.
SET @actualizacion_documentos = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'documento'
          AND COLUMN_NAME = 'token_descarga'
    ),
    'SELECT ''La columna token_descarga ya existe'' AS resultado',
    'ALTER TABLE documento ADD COLUMN token_descarga CHAR(64) NULL UNIQUE AFTER fecha_carga'
);
PREPARE actualizar_documentos FROM @actualizacion_documentos;
EXECUTE actualizar_documentos;
DEALLOCATE PREPARE actualizar_documentos;
