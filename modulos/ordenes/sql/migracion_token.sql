-- Agregar columna token a ordenes
ALTER TABLE `ordenes` ADD `token` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `ordenes` ADD UNIQUE INDEX `token` (`token`);

-- Generar token para ordenes existentes
UPDATE `ordenes` SET `token` = MD5(UUID()) WHERE `token` IS NULL;
