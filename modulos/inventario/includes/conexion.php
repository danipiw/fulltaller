<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$BASE_PATH = '';
$script_path = $_SERVER['SCRIPT_NAME'];
foreach (['/inventario', '/ordenes', '/pos'] as $folder) {
    $pos = strpos($script_path, $folder);
    if ($pos !== false) { $BASE_PATH = substr($script_path, 0, $pos + strlen($folder)); break; }
}

$host = $_SESSION['taller_db_host'] ?? 'localhost';
$user = $_SESSION['taller_db_user'] ?? '';
$pass = $_SESSION['taller_db_pass'] ?? '';
$name = $_SESSION['taller_db_name'] ?? '';

if (empty($user) || empty($name)) {
    $host_actual = $_SERVER['HTTP_HOST'] ?? '';
    $partes = explode('.', $host_actual);
    $subdominio = '';
    if (count($partes) >= 2) {
        $primer_parte = $partes[0];
        if ($primer_parte !== 'www' && $primer_parte !== 'admin') {
            $subdominio = $primer_parte;
        }
    }
    if (empty($subdominio)) {
        header("Location: $BASE_PATH/../taller_selector.php");
        exit;
    }
    require_once __DIR__ . '/../../ordenes/includes/conexion_central.php';
    $s = $conn_central->real_escape_string($subdominio);
    $r = $conn_central->query("SELECT id, nombre, db_host, db_user, db_pass, db_name, modulos FROM talleres WHERE subdominio = '$s' AND activo = 1 LIMIT 1");
    $taller = $r->fetch_assoc();
    if (!$taller) die("Taller no encontrado");
    $_SESSION['taller_id'] = (int)$taller['id'];
    $_SESSION['taller_nombre'] = $taller['nombre'];
    $_SESSION['taller_subdominio'] = $subdominio;
    $_SESSION['taller_db_host'] = $taller['db_host'] ?: 'localhost';
    $_SESSION['taller_db_user'] = $taller['db_user'];
    $_SESSION['taller_db_pass'] = $taller['db_pass'];
    $_SESSION['taller_db_name'] = $taller['db_name'];
    $host = $_SESSION['taller_db_host'];
    $user = $_SESSION['taller_db_user'];
    $pass = $_SESSION['taller_db_pass'];
    $name = $_SESSION['taller_db_name'];
    $conn_central->close();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}
$GLOBALS['pdo'] = $pdo;
initInventarioTables($pdo);

// Variables globales
$GLOBALS['taller_id'] = $_SESSION['taller_id'] ?? 0;
$GLOBALS['taller_nombre'] = $_SESSION['taller_nombre'] ?? 'Taller';
$GLOBALS['taller_subdominio'] = $_SESSION['taller_subdominio'] ?? '';
$GLOBALS['taller_db_host'] = $host;
$GLOBALS['taller_db_user'] = $user;
$GLOBALS['taller_db_pass'] = $pass;
$GLOBALS['taller_db_name'] = $name;

function initInventarioTables($db) {
    $tables = [
        'marcas' => 'CREATE TABLE IF NOT EXISTS `marcas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre` VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
        'modelos' => 'CREATE TABLE IF NOT EXISTS `modelos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `marca_id` INT NOT NULL,
            `nombre` VARCHAR(255) NOT NULL,
            `tipo` VARCHAR(50) NOT NULL DEFAULT "celular"
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'modelo_componentes' => 'CREATE TABLE IF NOT EXISTS `modelo_componentes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `modelo_id` INT NOT NULL,
            `componente_nombre` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'cajas' => 'CREATE TABLE IF NOT EXISTS `cajas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `numero` INT NOT NULL,
            `fecha_ingreso` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_cajas_numero` (`numero`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'caja_items' => 'CREATE TABLE IF NOT EXISTS `caja_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `caja_id` INT NOT NULL,
            `modelo_id` INT NOT NULL,
            `componente_nombre` VARCHAR(255) NOT NULL,
            `notas` TEXT NULL,
            `usado` TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'movimientos_log' => 'CREATE TABLE IF NOT EXISTS `movimientos_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `tipo` VARCHAR(20) NOT NULL,
            `caja_id` INT NOT NULL,
            `modelo` VARCHAR(255) NOT NULL,
            `marca` VARCHAR(255) NOT NULL,
            `componente` VARCHAR(255) NOT NULL,
            `descripcion` TEXT NOT NULL,
            `fecha` DATETIME NOT NULL,
            KEY `idx_log_fecha` (`fecha`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    ];
    $insertMarcas = false;
    $check = $db->query("SHOW TABLES LIKE 'marcas'");
    if ($check->rowCount() === 0) $insertMarcas = true;
    foreach ($tables as $name => $sql) {
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            // ignore if table already exists
        }
    }
    if ($insertMarcas) {
        $db->exec("INSERT IGNORE INTO marcas (nombre) VALUES ('Samsung'),('Apple'),('Motorola'),('Xiaomi'),('LG'),('Huawei'),('Sony'),('Alcatel'),('Nokia'),('TCL')");
    }
    // Intentar agregar UNIQUE KEY para prevenir duplicados futuros
    try {
        $db->exec("ALTER TABLE modelos ADD UNIQUE KEY `uq_modelos_marca_nombre` (`marca_id`, `nombre`)");
    } catch (PDOException $e) {
        // Si falla (por duplicados existentes), ignoramos silenciosamente
    }
}
