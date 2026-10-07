-- Ejecutar una sola vez en la base existente para agregar las coordenadas de los traslados.
ALTER TABLE traslado
    ADD COLUMN origen_latitud DECIMAL(10,7) NULL DEFAULT NULL,
    ADD COLUMN origen_longitud DECIMAL(10,7) NULL DEFAULT NULL,
    ADD COLUMN destino_latitud DECIMAL(10,7) NULL DEFAULT NULL,
    ADD COLUMN destino_longitud DECIMAL(10,7) NULL DEFAULT NULL;
