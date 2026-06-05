-- Agregar columna modulos a la tabla central talleres
ALTER TABLE talleres ADD COLUMN modulos VARCHAR(100) NOT NULL DEFAULT 'ordenes,pos';

-- Agregar columna modulos a la tabla usuarios de cada taller (ejecutar en cada BD de taller)
ALTER TABLE usuarios ADD COLUMN modulos VARCHAR(100) NOT NULL DEFAULT 'ordenes,pos' AFTER activo;

-- Agregar columna modulos a la tabla usuarios del POS (ejecutar en la BD pos)
ALTER TABLE usuarios ADD COLUMN modulos VARCHAR(100) NOT NULL DEFAULT 'pos' AFTER activo;

-- Agregar rol 'full' al ENUM de usuarios (ejecutar en cada BD de taller y en la BD central si existe columna rol en admin_usuarios)
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('recepcion','tecnico','admin','full') NOT NULL DEFAULT 'recepcion';
