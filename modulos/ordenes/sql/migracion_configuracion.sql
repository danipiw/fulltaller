CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(50) NOT NULL UNIQUE,
  `valor` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
('taller_nombre', 'Mi Taller'),
('taller_direccion', ''),
('taller_telefono', ''),
('legal_terminos', '1- FullTaller no se hace responsable, pasado los 30 días, por equipos olvidados o abandonados en nuestro taller.\n2- Pasados los 5 días de avisado que su equipo está listo para retirar, se cobrará a partir del 6to día el equivalente a 1% del valor de la reparación por cada día de retraso.\n3- Dispositivos que ingresen apagados o no se pueda corroborar el correcto funcionamiento completo NO se dará garantía más que sobre la reparación a realizar.\n4- En trabajos con equipos mojados no hay garantía, se trabajará para que el equipo encienda.\n5- En reparaciones de software y/o desbloqueos no hay garantía.\n6- En caso de extravío de la ORDEN se deberá presentar el titular con su DNI, el dispositivo no se entregará a ningún tercero sin previa autorización del titular de la orden.')
AS nuevas ON DUPLICATE KEY UPDATE valor = nuevas.valor;
