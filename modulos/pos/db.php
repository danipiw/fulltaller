<?php
function esAdminPOS() {
    return in_array($_SESSION['rol'] ?? '', ['admin', 'full']);
}

function getDB() {
    $host = $_SESSION['taller_db_host'] ?? 'localhost';
    $user = $_SESSION['taller_db_user'] ?? '';
    $pass = $_SESSION['taller_db_pass'] ?? '';
    $name = $_SESSION['taller_db_name'] ?? '';

    if (empty($user) || empty($name)) {
        die('Error: no hay conexión configurada para este taller.');
    }

    $mysqli = new mysqli($host, $user, $pass, $name);
    if ($mysqli->connect_error) {
        die('Error de conexión a la BD del taller: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");

    $check = $mysqli->query("SHOW TABLES LIKE 'pos_productos'");
    if (!$check || $check->num_rows == 0) {
        initPosTables($mysqli);
    } else {
        fixPosFKs($mysqli);
        fixAnulada($mysqli);
    }
    fixConfiguracion($mysqli);

    return $mysqli;
}

function fixPosFKs($mysqli) {
    $needs_drop = false;
    foreach (['pos_caja_movimientos', 'pos_caja', 'pos_venta_detalle', 'pos_ventas'] as $t) {
        $fk = $mysqli->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND REFERENCED_TABLE_NAME = 'pos_usuarios'");
        if ($fk && $fk->num_rows > 0) { $needs_drop = true; break; }
    }
    if ($needs_drop) {
        $mysqli->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['pos_caja_movimientos', 'pos_caja', 'pos_venta_detalle', 'pos_ventas'] as $t) {
            $mysqli->query("DROP TABLE IF EXISTS $t");
        }
        $mysqli->query('SET FOREIGN_KEY_CHECKS = 1');
        // recreate with correct FKs
        $mysqli->query('SET FOREIGN_KEY_CHECKS = 0');
        $mysqli->query('CREATE TABLE IF NOT EXISTS pos_ventas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            items INT NOT NULL,
            metodo_pago VARCHAR(20) DEFAULT "efectivo",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
        $mysqli->query('CREATE TABLE IF NOT EXISTS pos_venta_detalle (
            id INT AUTO_INCREMENT PRIMARY KEY,
            venta_id INT NOT NULL,
            producto_id INT NOT NULL,
            descripcion VARCHAR(255) DEFAULT NULL,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (venta_id) REFERENCES pos_ventas(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES pos_productos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
        $mysqli->query('CREATE TABLE IF NOT EXISTS pos_caja (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            fecha_apertura DATE NOT NULL,
            hora_apertura TIME NOT NULL,
            monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
            fecha_cierre DATE DEFAULT NULL,
            hora_cierre TIME DEFAULT NULL,
            monto_cierre DECIMAL(10,2) DEFAULT NULL,
            monto_esperado DECIMAL(10,2) DEFAULT NULL,
            estado ENUM("abierta","cerrada") DEFAULT "abierta",
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_caja_usuario_fecha (usuario_id, fecha_apertura)
        ) ENGINE=InnoDB');
        $mysqli->query('CREATE TABLE IF NOT EXISTS pos_caja_movimientos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            caja_id INT NOT NULL,
            tipo ENUM("ingreso","egreso") NOT NULL,
            concepto VARCHAR(255) NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            usuario_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (caja_id) REFERENCES pos_caja(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
        $mysqli->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}

function initPosTables($mysqli) {
    $mysqli->query('SET FOREIGN_KEY_CHECKS = 0');

    // Limpiar FKs viejas que referencian pos_usuarios (tabla eliminada en migración)
    foreach (['pos_caja_movimientos', 'pos_caja', 'pos_venta_detalle', 'pos_ventas'] as $t) {
        $fk = $mysqli->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND REFERENCED_TABLE_NAME = 'pos_usuarios'");
        if ($fk && $fk->num_rows > 0) {
            $mysqli->query("DROP TABLE IF EXISTS pos_caja_movimientos");
            $mysqli->query("DROP TABLE IF EXISTS pos_caja");
            $mysqli->query("DROP TABLE IF EXISTS pos_venta_detalle");
            $mysqli->query("DROP TABLE IF EXISTS pos_ventas");
            break;
        }
    }

    // Asegurar que la tabla usuarios tenga columna modulos
    $col = $mysqli->query("SHOW COLUMNS FROM usuarios LIKE 'modulos'");
    if (!$col || $col->num_rows == 0) {
        $mysqli->query("ALTER TABLE usuarios ADD COLUMN modulos VARCHAR(100) NOT NULL DEFAULT 'ordenes' AFTER rol");
    }

    $mysqli->query('CREATE TABLE IF NOT EXISTS pos_productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        costo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        stock INT DEFAULT 0,
        activo TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_codigo (codigo)
    ) ENGINE=InnoDB');

    $mysqli->query('CREATE TABLE IF NOT EXISTS pos_ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        items INT NOT NULL,
        metodo_pago VARCHAR(20) DEFAULT "efectivo",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');

    $mysqli->query('CREATE TABLE IF NOT EXISTS pos_venta_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venta_id INT NOT NULL,
        producto_id INT NOT NULL,
        descripcion VARCHAR(255) DEFAULT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (venta_id) REFERENCES pos_ventas(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES pos_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');

    $mysqli->query('CREATE TABLE IF NOT EXISTS pos_caja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        fecha_apertura DATE NOT NULL,
        hora_apertura TIME NOT NULL,
        monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
        fecha_cierre DATE DEFAULT NULL,
        hora_cierre TIME DEFAULT NULL,
        monto_cierre DECIMAL(10,2) DEFAULT NULL,
        monto_esperado DECIMAL(10,2) DEFAULT NULL,
        estado ENUM("abierta","cerrada") DEFAULT "abierta",
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_caja_usuario_fecha (usuario_id, fecha_apertura)
    ) ENGINE=InnoDB');

    $mysqli->query('CREATE TABLE IF NOT EXISTS pos_caja_movimientos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        caja_id INT NOT NULL,
        tipo ENUM("ingreso","egreso") NOT NULL,
        concepto VARCHAR(255) NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        usuario_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (caja_id) REFERENCES pos_caja(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');

    $mysqli->query('SET FOREIGN_KEY_CHECKS = 1');

    fixAnulada($mysqli);
    $mysqli->query("INSERT IGNORE INTO pos_productos (codigo, descripcion, precio, stock) VALUES ('COMUN', 'Producto Común', 0, 999999)");

    // Usuarios por defecto (solo si la tabla usuarios está vacía)
    $count = $mysqli->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'];
    if ((int)$count === 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $mysqli->query("INSERT INTO usuarios (nombre, usuario, password, rol, modulos) VALUES ('Administrador', 'admin', '$pass', 'admin', 'ordenes,pos,inventario')");
        $pass2 = password_hash('recepcion123', PASSWORD_DEFAULT);
        $mysqli->query("INSERT INTO usuarios (nombre, usuario, password, rol, modulos) VALUES ('Recepcion', 'recepcion', '$pass2', 'recepcion', 'ordenes')");
        $pass3 = password_hash('tecnico123', PASSWORD_DEFAULT);
        $mysqli->query("INSERT INTO usuarios (nombre, usuario, password, rol, modulos) VALUES ('Tecnico', 'tecnico', '$pass3', 'tecnico', 'ordenes')");
        $pass4 = password_hash('cajero123', PASSWORD_DEFAULT);
        $mysqli->query("INSERT INTO usuarios (nombre, usuario, password, rol, modulos) VALUES ('Cajero', 'cajero', '$pass4', 'cajero', 'pos')");
    }
}

function fixAnulada($mysqli) {
    $col = $mysqli->query("SHOW COLUMNS FROM pos_ventas LIKE 'anulada'");
    if (!$col || $col->num_rows == 0) {
        $mysqli->query("ALTER TABLE pos_ventas ADD COLUMN anulada TINYINT NOT NULL DEFAULT 0 AFTER metodo_pago");
    }
}

function fixConfiguracion($mysqli) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS configuracion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT NOT NULL
    ) ENGINE=InnoDB");
}
