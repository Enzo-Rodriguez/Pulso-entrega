-- Ejecutar en la base existente si se creó antes de incorporar el inicio de sesión.
-- Se puede repetir: no borra personas ni modifica contraseñas ya registradas.
SET @cambio_acceso = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persona' AND COLUMN_NAME = 'contrasenia'),
    'SELECT ''contrasenia ya existe'' AS resultado',
    'ALTER TABLE persona ADD COLUMN contrasenia VARCHAR(255) NULL DEFAULT NULL AFTER email'
);
PREPARE actualizar_acceso FROM @cambio_acceso;
EXECUTE actualizar_acceso;
DEALLOCATE PREPARE actualizar_acceso;
