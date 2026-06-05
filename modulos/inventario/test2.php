<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    include 'includes/verificar_sesion.php';
    echo "1. verificar_sesion OK\n";
    echo "2. rol: " . ($_SESSION['rol'] ?? 'NO') . "\n";
    echo "3. modulos: " . ($_SESSION['user_modulos'] ?? 'NO') . "\n";
    $pdo = $GLOBALS['pdo'];
    $r = $pdo->query("SELECT COUNT(*) FROM marcas")->fetchColumn();
    echo "4. marcas count: $r\n";
    $r = $pdo->query("SELECT COUNT(*) FROM cajas")->fetchColumn();
    echo "5. cajas count: $r\n";
    $r = $pdo->query("SELECT COUNT(*) FROM movimientos_log")->fetchColumn();
    echo "6. movimientos_log count: $r\n";
    $r = $pdo->query("SELECT COUNT(*) FROM modelos")->fetchColumn();
    echo "7. modelos count: $r\n";
    $r = $pdo->query("SELECT COUNT(*) FROM modelo_componentes")->fetchColumn();
    echo "8. modelo_componentes count: $r\n";
    $r = $pdo->query("SELECT COUNT(*) FROM caja_items")->fetchColumn();
    echo "9. caja_items count: $r\n";
    echo "10. ALL OK\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
