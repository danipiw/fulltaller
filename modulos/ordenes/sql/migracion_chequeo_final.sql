ALTER TABLE chequeo_final DROP INDEX orden_id;
ALTER TABLE chequeo_final ADD INDEX idx_orden_id (orden_id);
