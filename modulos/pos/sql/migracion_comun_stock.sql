-- Migración: agregar columna descripcion a venta_detalle + producto COMUN
-- Ejecutar en la base de datos del POS

ALTER TABLE venta_detalle ADD COLUMN descripcion VARCHAR(255) DEFAULT NULL AFTER producto_id;

INSERT IGNORE INTO productos (codigo, descripcion, precio, stock) VALUES ('COMUN', 'Producto Común', 0, 999999);
