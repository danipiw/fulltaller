CREATE TABLE IF NOT EXISTS tienda_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tienda_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT DEFAULT NULL,
    cliente_nombre VARCHAR(255) NOT NULL,
    cliente_dni VARCHAR(50) DEFAULT NULL,
    cliente_telefono VARCHAR(50) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','pagado','enviado','entregado','cancelado') DEFAULT 'pendiente',
    metodo_pago VARCHAR(50) DEFAULT NULL,
    pago_data TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tienda_pedido_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    FOREIGN KEY (pedido_id) REFERENCES tienda_pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES tienda_productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
