CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `rol` ENUM('recepcion','tecnico','admin','full') NOT NULL DEFAULT 'recepcion',
  `activo` TINYINT(1) DEFAULT 1,
  `modulos` VARCHAR(100) NOT NULL DEFAULT 'ordenes,pos',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `dni` VARCHAR(20) NOT NULL,
  `telefono` VARCHAR(30) NOT NULL,
  `opinion` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ordenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente` VARCHAR(100) NOT NULL,
  `dni` VARCHAR(20) DEFAULT NULL,
  `telefono` VARCHAR(30) DEFAULT NULL,
  `imei` VARCHAR(30) DEFAULT NULL,
  `tipo` VARCHAR(50) DEFAULT NULL,
  `marca` VARCHAR(50) DEFAULT NULL,
  `modelo` VARCHAR(100) DEFAULT NULL,
  `falla` TEXT DEFAULT NULL,
  `estado` VARCHAR(100) DEFAULT 'INGRESADO',
  `fecha_ingreso` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` INT DEFAULT NULL,
  `presupuesto` DECIMAL(10,2) DEFAULT NULL,
  `costo` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `observaciones` TEXT DEFAULT NULL,
  `patron` VARCHAR(100) DEFAULT NULL,
  `express` TINYINT(1) NOT NULL DEFAULT 0,
  `sena` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `token` VARCHAR(64) DEFAULT NULL,
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `estados_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `estado` VARCHAR(50) NOT NULL,
  `cambiado_por` VARCHAR(20) NOT NULL,
  `cambiado_por_usuario` VARCHAR(100) DEFAULT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `orden_id` (`orden_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `autor` ENUM('recepcion','tecnico') NOT NULL DEFAULT 'recepcion',
  `autor_nombre` VARCHAR(100) DEFAULT NULL,
  `mensaje` TEXT NOT NULL,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `orden_id` (`orden_id`),
  CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `desde_rol` ENUM('recepcion','tecnico') NOT NULL,
  `para_rol` ENUM('recepcion','tecnico') NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `leida` TINYINT(1) DEFAULT 0,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_para_rol_leida` (`para_rol`,`leida`),
  KEY `idx_orden_id` (`orden_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `chequeo_final` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `imagen` TINYINT(1) DEFAULT 0,
  `touch` TINYINT(1) DEFAULT 0,
  `brillo` TINYINT(1) DEFAULT 0,
  `receiver` TINYINT(1) DEFAULT 0,
  `camaras` TINYINT(1) DEFAULT 0,
  `microfono` TINYINT(1) DEFAULT 0,
  `altavoz` TINYINT(1) DEFAULT 0,
  `sensor` TINYINT(1) DEFAULT 0,
  `wifi` TINYINT(1) DEFAULT 0,
  `botones` TINYINT(1) DEFAULT 0,
  `pegado` TINYINT(1) DEFAULT 0,
  `carga` TINYINT(1) DEFAULT 0,
  `creado_por` VARCHAR(20) DEFAULT NULL,
  `creado_por_usuario` VARCHAR(100) DEFAULT NULL,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_orden_id` (`orden_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `marcas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tipos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `baterias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `marca` VARCHAR(255) DEFAULT NULL,
  `modelo` VARCHAR(255) DEFAULT NULL,
  `precio` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `modulos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `marca` VARCHAR(255) DEFAULT NULL,
  `modelo` VARCHAR(255) DEFAULT NULL,
  `precio` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `fotos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `orden_id` (`orden_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pin_carga` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` VARCHAR(255) DEFAULT NULL,
  `precio` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`usuario`, `password`, `nombre`, `rol`) VALUES
('recepcion', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Recepcion', 'recepcion'),
('tecnico', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tecnico', 'tecnico');

INSERT INTO `marcas` (`nombre`) VALUES
('Samsung'), ('Apple'), ('Motorola'), ('Xiaomi'), ('LG'), ('Huawei'), ('Sony'), ('Alcatel'), ('Nokia'), ('TCL');

INSERT INTO `tipos` (`nombre`) VALUES
('Celular'), ('Tablet'), ('Notebook'), ('PC'), ('Smartwatch');

CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(50) NOT NULL UNIQUE,
  `valor` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `repuestos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` VARCHAR(255) DEFAULT NULL,
  `marca` VARCHAR(255) DEFAULT NULL,
  `modelo` VARCHAR(255) DEFAULT NULL,
  `precio` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `modelos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `marca_id` INT NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'celular',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_inv_modelos_marca` FOREIGN KEY (`marca_id`) REFERENCES `marcas`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `modelo_componentes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `modelo_id` INT NOT NULL,
  `componente_nombre` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_mc_modelo` FOREIGN KEY (`modelo_id`) REFERENCES `modelos_inventario`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cajas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `numero` INT NOT NULL,
  `fecha_ingreso` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cajas_numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `caja_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `caja_id` INT NOT NULL,
  `modelo_id` INT NOT NULL,
  `componente_nombre` VARCHAR(255) NOT NULL,
  `notas` TEXT NULL,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ci_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ci_modelo` FOREIGN KEY (`modelo_id`) REFERENCES `modelos_inventario`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `movimientos_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tipo` VARCHAR(20) NOT NULL COMMENT 'ingreso | retiro | restauracion',
  `caja_id` INT NOT NULL,
  `modelo` VARCHAR(255) NOT NULL,
  `marca` VARCHAR(255) NOT NULL,
  `componente` VARCHAR(255) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `fecha` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_log_fecha` (`fecha` DESC),
  INDEX `idx_log_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
('taller_nombre', 'Mi Taller'),
('taller_direccion', ''),
('taller_telefono', ''),
('legal_terminos', '1- FullTaller no se hace responsable, pasado los 30 días, por equipos olvidados o abandonados en nuestro taller.\n2- Pasados los 5 días de avisado que su equipo está listo para retirar, se cobrará a partir del 6to día el equivalente a 1% del valor de la reparación por cada día de retraso.\n3- Dispositivos que ingresen apagados o no se pueda corroborar el correcto funcionamiento completo NO se dará garantía más que sobre la reparación a realizar.\n4- En trabajos con equipos mojados no hay garantía, se trabajará para que el equipo encienda.\n5- En reparaciones de software y/o desbloqueos no hay garantía.\n6- En caso de extravío de la ORDEN se deberá presentar el titular con su DNI, el dispositivo no se entregará a ningún tercero sin previa autorización del titular de la orden.');
