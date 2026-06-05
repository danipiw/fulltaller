<?php
session_start();
include 'includes/conexion.php';

echo "<h2>🔧 Diagnóstico de Notificaciones</h2>";
echo "<pre>";

// 1. Verificar sesión
echo "\n=== 1. SESIÓN ===\n";
if (isset($_SESSION['rol'])) {
    echo "✅ Rol en sesión: " . $_SESSION['rol'] . "\n";
    $rol = $_SESSION['rol'];
} else {
    echo "❌ NO hay sesión activa. Logueate primero.\n";
    exit;
}

// 2. Verificar tabla notificaciones
echo "\n=== 2. TABLA NOTIFICACIONES ===\n";
try {
    $check = $conn->query("SHOW TABLES LIKE 'notificaciones'");
    if ($check->num_rows > 0) {
        echo "✅ Tabla 'notificaciones' existe\n";
        
        // Ver estructura
        $cols = $conn->query("DESCRIBE notificaciones");
        echo "\nEstructura:\n";
        while ($c = $cols->fetch_assoc()) {
            echo "  - " . $c['Field'] . " (" . $c['Type'] . ") " . ($c['Null']=='NO'?'NOT NULL':'NULL') . "\n";
        }
    } else {
        echo "❌ Tabla 'notificaciones' NO EXISTE\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 3. Contar notificaciones
echo "\n=== 3. CONTAR NOTIFICACIONES ===\n";
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE para_rol = ?");
$stmt->bind_param("s", $rol);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
echo "Total notificaciones para '$rol': $total\n";

$stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE para_rol = ? AND leida = 0");
$stmt2->bind_param("s", $rol);
$stmt2->execute();
$no_leidas = $stmt2->get_result()->fetch_assoc()['total'];
echo "No leídas: $no_leidas\n";

// 4. Ver últimas 5 notificaciones
echo "\n=== 4. ÚLTIMAS 5 NOTIFICACIONES ===\n";
$stmt3 = $conn->prepare("SELECT * FROM notificaciones WHERE para_rol = ? ORDER BY fecha DESC LIMIT 5");
$stmt3->bind_param("s", $rol);
$stmt3->execute();
$result = $stmt3->get_result();
if ($result->num_rows == 0) {
    echo "No hay notificaciones.\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "  ID: " . $row['id'] . " | Orden: #" . $row['orden_id'] . " | Desde: " . $row['desde_rol'] . " | Leída: " . $row['leida'] . "\n";
        echo "  Título: " . $row['titulo'] . "\n";
        echo "  Mensaje: " . substr($row['mensaje'], 0, 60) . "...\n";
        echo "  Fecha: " . $row['fecha'] . "\n";
        echo "  ---\n";
    }
}

// 5. Probar insertar una notificación de prueba
echo "\n=== 5. INSERTAR NOTIFICACIÓN DE PRUEBA ===\n";
$test_titulo = 'Test diagnóstico';
$test_msg = 'Mensaje de prueba desde diagnóstico';
$desde = ($rol === 'recepcion') ? 'tecnico' : 'recepcion';

$stmt4 = $conn->prepare("INSERT INTO notificaciones (orden_id, desde_rol, para_rol, titulo, mensaje, leida) VALUES (1, ?, ?, ?, ?, 0)");
$stmt4->bind_param("ssss", $desde, $rol, $test_titulo, $test_msg);
if ($stmt4->execute()) {
    echo "✅ Notificación de prueba insertada (ID: " . $conn->insert_id . ")\n";
} else {
    echo "❌ Error al insertar: " . $stmt4->error . "\n";
}

// 6. Probar obtener_notificaciones.php directamente
echo "\n=== 6. RESPUESTA DE obtener_notificaciones.php ===\n";
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/obtener_notificaciones.php';
echo "URL: $url\n";
$respuesta = @file_get_contents($url);
if ($respuesta) {
    $data = json_decode($respuesta, true);
    echo "Respuesta decodificada:\n";
    print_r($data);
} else {
    echo "❌ No se pudo obtener respuesta. Error: " . error_get_last()['message'] . "\n";
}

echo "</pre>";
echo "<hr><p><a href='listado.php'>Volver al listado</a></p>";