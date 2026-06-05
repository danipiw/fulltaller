-- Migración para agregar tablas de caja a BD existentes
-- Ejecutar en la base de datos del POS (pos_db en local, c2821557_pos en DonWeb)

CREATE TABLE IF NOT EXISTS caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_apertura DATE NOT NULL,
    hora_apertura TIME NOT NULL,
    monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_cierre DATE DEFAULT NULL,
    hora_cierre TIME DEFAULT NULL,
    monto_cierre DECIMAL(10,2) DEFAULT NULL,
    monto_esperado DECIMAL(10,2) DEFAULT NULL,
    estado ENUM('abierta','cerrada') DEFAULT 'abierta',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_caja_usuario_fecha (usuario_id, fecha_apertura)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS caja_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caja_id INT NOT NULL,
    tipo ENUM('ingreso','egreso') NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caja_id) REFERENCES caja(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
