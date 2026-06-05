<?php
session_start();
require_once 'db.php';

// Intentar conectar sin BD y crearla
$mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    die('Error conectando al servidor MySQL: ' . $mysqli->connect_error);
}

if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME)) {
    die('Error creando la base de datos. Creala manualmente en cPanel con el nombre: ' . DB_NAME . '<br>Luego recargá esta página.');
}

$mysqli->select_db(DB_NAME);

// Crear tablas
$mysqli->query('SET FOREIGN_KEY_CHECKS = 0');

$tables = [
    'productos' => 'CREATE TABLE IF NOT EXISTS productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        stock INT DEFAULT 0,
        activo TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_codigo (codigo)
    ) ENGINE=InnoDB',
    'usuarios' => 'CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        usuario VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        rol ENUM("admin","cajero") DEFAULT "cajero",
        activo TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB',
    'ventas' => 'CREATE TABLE IF NOT EXISTS ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        items INT NOT NULL,
        metodo_pago VARCHAR(20) DEFAULT "efectivo",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB',
    'venta_detalle' => 'CREATE TABLE IF NOT EXISTS venta_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venta_id INT NOT NULL,
        producto_id INT NOT NULL,
        descripcion VARCHAR(255) DEFAULT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB',
    'caja' => 'CREATE TABLE IF NOT EXISTS caja (
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
    ) ENGINE=InnoDB',
    'caja_movimientos' => 'CREATE TABLE IF NOT EXISTS caja_movimientos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        caja_id INT NOT NULL,
        tipo ENUM("ingreso","egreso") NOT NULL,
        concepto VARCHAR(255) NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        usuario_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (caja_id) REFERENCES caja(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
];

$errores = [];
foreach ($tables as $name => $sql) {
    if (!$mysqli->query($sql)) {
        $errores[] = "Error creando tabla $name: " . $mysqli->error;
    }
}

$mysqli->query('SET FOREIGN_KEY_CHECKS = 1');

// Usuarios por defecto
$pass = password_hash('admin123', PASSWORD_DEFAULT);
$mysqli->query("INSERT IGNORE INTO usuarios (nombre, usuario, password, rol) VALUES ('Administrador', 'admin', '$pass', 'admin')");

$pass2 = password_hash('cajero123', PASSWORD_DEFAULT);
$mysqli->query("INSERT IGNORE INTO usuarios (nombre, usuario, password, rol) VALUES ('Cajero Demo', 'cajero', '$pass2', 'cajero')");

// Producto común para ventas rápidas
$mysqli->query("INSERT IGNORE INTO productos (codigo, descripcion, precio, stock) VALUES ('COMUN', 'Producto Común', 0, 999999)");

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup - POS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="pos-wrapper" style="max-width:600px;margin:40px auto;">
    <div class="panel">
        <h1>⚙️ Instalación</h1>
        <?php if (empty($errores)): ?>
        <div class="alert success">✅ Base de datos creada correctamente.</div>
        <p style="margin-top:16px;">
            <strong>Usuarios creados:</strong><br>
            👑 Admin: <code>admin</code> / <code>admin123</code><br>
            👤 Cajero: <code>cajero</code> / <code>cajero123</code>
        </p>
        <p style="margin-top:16px;">
            <a href="index.php" class="btn-guardar" style="text-decoration:none;">👉 Ir al POS</a>
            <a href="setup.php?reset=1" class="btn-cancelar" style="text-decoration:none;margin-left:8px;" onclick="return confirm('¿Reiniciar todas las tablas? Se perderán los datos.')">Reiniciar BD</a>
        </p>
        <?php else: ?>
        <div class="alert error">
            <strong>Errores:</strong>
            <ul><?php foreach ($errores as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
