CREATE DATABASE IF NOT EXISTS `taller_central` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `taller_central`;

CREATE TABLE `admin_usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `talleres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `subdominio` VARCHAR(100) NOT NULL UNIQUE,
  `db_host` VARCHAR(100) NOT NULL DEFAULT '127.0.0.1',
  `db_user` VARCHAR(100) NOT NULL,
  `db_pass` VARCHAR(255) NOT NULL,
  `db_name` VARCHAR(100) NOT NULL,
  `license_key` VARCHAR(64) NOT NULL UNIQUE,
  `plan` VARCHAR(50) NOT NULL DEFAULT 'basico',
  `fecha_alta` DATE NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `suscripcion_activa` TINYINT(1) NOT NULL DEFAULT 1,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_usuarios` (`usuario`, `password`, `nombre`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin');

CREATE TABLE `pagos_talleres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `taller_id` INT NOT NULL,
  `meses` TINYINT NOT NULL DEFAULT 1,
  `hasta` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`taller_id`) REFERENCES `talleres`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `talleres` (`nombre`, `subdominio`, `db_host`, `db_user`, `db_pass`, `db_name`, `license_key`, `plan`, `fecha_alta`, `fecha_vencimiento`, `suscripcion_activa`, `activo`) VALUES
('Taller Demo', 'demo', '127.0.0.1', 'root', '', 'taller_demo', 'DEMO-LICENSE-2026', 'basico', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 1);
