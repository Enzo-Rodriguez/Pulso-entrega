-- Ejecutar en la base existente. Se puede repetir sin borrar traslados ni ubicaciones.
-- Los traslados anteriores conservan el comportamiento de solo ida.
SET @cambio_traslados = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'traslado' AND COLUMN_NAME = 'ida_vuelta'),
    'SELECT ''ida_vuelta ya existe'' AS resultado',
    'ALTER TABLE traslado ADD COLUMN ida_vuelta TINYINT NOT NULL DEFAULT 0 AFTER id_estado_traslado'
);
PREPARE actualizar_traslados FROM @cambio_traslados;
EXECUTE actualizar_traslados;
DEALLOCATE PREPARE actualizar_traslados;

SET @cambio_traslados = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'traslado' AND COLUMN_NAME = 'margen_minutos'),
    'SELECT ''margen_minutos ya existe'' AS resultado',
    'ALTER TABLE traslado ADD COLUMN margen_minutos INT NOT NULL DEFAULT 0 AFTER ida_vuelta'
);
PREPARE actualizar_traslados FROM @cambio_traslados;
EXECUTE actualizar_traslados;
DEALLOCATE PREPARE actualizar_traslados;

SET @cambio_traslados = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'traslado' AND COLUMN_NAME = 'llegada_destino'),
    'SELECT ''llegada_destino ya existe'' AS resultado',
    'ALTER TABLE traslado ADD COLUMN llegada_destino DATETIME NULL AFTER salida_real'
);
PREPARE actualizar_traslados FROM @cambio_traslados;
EXECUTE actualizar_traslados;
DEALLOCATE PREPARE actualizar_traslados;

SET @cambio_traslados = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'traslado' AND COLUMN_NAME = 'inicio_regreso'),
    'SELECT ''inicio_regreso ya existe'' AS resultado',
    'ALTER TABLE traslado ADD COLUMN inicio_regreso DATETIME NULL AFTER llegada_destino'
);
PREPARE actualizar_traslados FROM @cambio_traslados;
EXECUTE actualizar_traslados;
DEALLOCATE PREPARE actualizar_traslados;

INSERT INTO estado_traslado (nombre)
SELECT 'En destino' WHERE NOT EXISTS (SELECT 1 FROM estado_traslado WHERE nombre = 'En destino');
INSERT INTO estado_traslado (nombre)
SELECT 'En regreso' WHERE NOT EXISTS (SELECT 1 FROM estado_traslado WHERE nombre = 'En regreso');
INSERT INTO estado_traslado (nombre)
SELECT 'En regreso por cancelación' WHERE NOT EXISTS (SELECT 1 FROM estado_traslado WHERE nombre = 'En regreso por cancelación');
