<?php
include 'includes/verificar_sesion.php';
$pdo = $GLOBALS['pdo'];

// Obtener log de movimientos
$movimientos_log = [];
try {
    $stmt_log = $pdo->query("SELECT tipo, caja_id, modelo, marca, componente, descripcion, fecha FROM movimientos_log ORDER BY fecha DESC LIMIT 20");
    $movimientos_log = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e_log) {
    $movimientos_log = [];
}

// Guardar nueva marca vía AJAX
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar_marca') {
    try {
        $stmt = $pdo->prepare("INSERT INTO marcas (nombre) VALUES (?)");
        $stmt->execute([$_POST['nombre']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'nombre' => $_POST['nombre']]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Guardar nuevo modelo vía AJAX
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar_modelo') {
    try {
        // Verificar si ya existe (misma marca + nombre)
        $check = $pdo->prepare("SELECT id FROM modelos WHERE marca_id = ? AND LOWER(nombre) = LOWER(?)");
        $check->execute([$_POST['marca_id'], $_POST['nombre']]);
        $existing = $check->fetchColumn();
        if ($existing) {
            echo json_encode(['success' => true, 'id' => (int)$existing, 'nombre' => $_POST['nombre'], 'existing' => true]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO modelos (marca_id, nombre, tipo) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['marca_id'], $_POST['nombre'], $_POST['tipo'] ?? 'celular']);
        $modelo_id = $pdo->lastInsertId();

        $componentes_default = ['Pantalla','Batería','Placa principal','Cámara trasera','Cámara frontal','Huella','Placa de carga','Receiver','Buzzer','Vibrador','Marco','Tapa trasera','Botones laterales','Flex main','Flex botones'];
        $stmt_comp = $pdo->prepare("INSERT INTO modelo_componentes (modelo_id, componente_nombre) VALUES (?, ?)");
        foreach ($componentes_default as $comp) {
            $stmt_comp->execute([$modelo_id, $comp]);
        }

        echo json_encode(['success' => true, 'id' => $modelo_id, 'nombre' => $_POST['nombre']]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Obtener próximo número de caja
$ultimo = $pdo->query("SELECT MAX(numero) FROM cajas")->fetchColumn();
$proxima_caja = ($ultimo ? $ultimo : 0) + 1;

// Guardar caja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['accion'])) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO cajas (numero) VALUES (?)");
        $stmt->execute([$_POST['numero_caja']]);
        $caja_id = $pdo->lastInsertId();

        if (!empty($_POST['componentes'])) {
            $stmt = $pdo->prepare("INSERT INTO caja_items (caja_id, modelo_id, componente_nombre, notas) VALUES (?, ?, ?, ?)");
            foreach ($_POST['componentes'] as $comp) {
                $stmt->execute([$caja_id, $_POST['modelo_id'], $comp, $_POST['observaciones'] ?? null]);
            }
        }

        $pdo->commit();
        $mensaje = "✅ Guardado correctamente con número #" . $_POST['numero_caja'];
        $ultimo = $pdo->query("SELECT MAX(numero) FROM cajas")->fetchColumn();
        $proxima_caja = ($ultimo ? $ultimo : 0) + 1;

        // Registrar en log
        try {
            $stmt_log = $pdo->prepare("INSERT INTO movimientos_log (tipo, caja_id, modelo, marca, componente, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $modelo_nombre = $pdo->query("SELECT m.nombre, ma.nombre as marca FROM modelos m INNER JOIN marcas ma ON m.marca_id = ma.id WHERE m.id = " . intval($_POST['modelo_id']))->fetch(PDO::FETCH_ASSOC);
            $descripcion = "Ingreso de caja #" . $_POST['numero_caja'] . " - " . $modelo_nombre['marca'] . " " . $modelo_nombre['nombre'];
            $componentes_str = !empty($_POST['componentes']) ? implode(', ', $_POST['componentes']) : 'Sin componentes';
            $stmt_log->execute(['ingreso', $caja_id, $modelo_nombre['nombre'], $modelo_nombre['marca'], $componentes_str, $descripcion]);
        } catch(Exception $e_log) {
        }} catch(Exception $e) {
        $pdo->rollBack();
        $error = "❌ Error: " . $e->getMessage();
    }
}

$marcas = $pdo->query("SELECT * FROM marcas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Repuestos/Scrap - Ingreso</title>
    <base href="<?php echo $BASE_PATH; ?>/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
            --ft-navy: #001845;
            --ft-azul-oscuro: #023e8a;
            --ft-azul: #0077b6;
            --ft-cyan: #00a8e8;
            --ft-cyan-claro: #48cae4;
            --ft-fondo: #f0f4f8;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background-color: #f0f4f8; }

        /* ===== ORDENES NAVBAR (nav-jb) ===== */
        .nav-jb {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            padding: 0.2rem 1.5rem 0.2rem 0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,56,168,0.3);
        }
        .nav-jb .nav-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .nav-jb .nav-brand {
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-jb .nav-brand img { height: 36px; width: auto; }
        .nav-jb .nav-btn {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .nav-jb .nav-btn:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }
        .nav-jb .nav-btn.active {
            background-color: var(--jb-cyan);
            color: var(--jb-navy);
            font-weight: 600;
            border-color: var(--jb-cyan);
        }
        .nav-jb .nav-sep { color: rgba(255,255,255,0.3); margin: 0 0.25rem; }

        .rol-badge {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }



        /* ===== BOTÓN NUEVA ORDEN ===== */
        .btn-nueva-orden-nav {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            font-size: 1.2rem;
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-nueva-orden-nav:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }
        .btn-nueva-orden-nav i { font-size: 1.2rem; }

        /* ===== HAMBURGUESA ===== */
        .btn-hamburger {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.4rem;
            flex-shrink: 0;
            margin-right: 4px;
        }
        .btn-hamburger:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }

        /* ===== SIDEBAR ORDENES (offcanvas) ===== */
        .sidebar-jb {
            background: linear-gradient(180deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 100%) !important;
            color: white;
            border-right: 2px solid var(--jb-cyan);
        }
        .sidebar-jb .offcanvas-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-jb .offcanvas-title { color: var(--jb-cyan); font-weight: 700; }
        .sidebar-jb .btn-close {
            filter: brightness(0) invert(1);
        }
        .sidebar-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.95rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .sidebar-menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(4px);
        }
        .sidebar-menu-item i {
            font-size: 1.3rem;
            width: 24px;
            text-align: center;
            color: var(--jb-cyan);
        }
        .sidebar-menu-item .badge-sidebar {
            margin-left: auto;
            background: var(--jb-cyan);
            color: var(--jb-navy);
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 700;
        }
        .sidebar-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 8px 0;
        }
        .sidebar-menu-item.active {
            background: rgba(0,168,232,0.25);
            color: white;
            border-left: 3px solid var(--jb-cyan);
        }
        body.dark-mode .sidebar-jb {
            background: linear-gradient(180deg, #0a1628 0%, #0f1f3d 100%) !important;
        }

        /* ===== MODO NOCTURNO ===== */
        body.dark-mode {
            background-color: #0f1729 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .card { background-color: #1a2235 !important; border-left-color: var(--jb-cyan) !important; color: #e2e8f0; }
        body.dark-mode .card-header { background-color: #1a2235 !important; border-bottom-color: #2d3748 !important; color: #e2e8f0; }

        body.dark-mode .form-control, body.dark-mode .form-select { background-color: #1a2235 !important; border-color: #2d3748 !important; color: #e2e8f0 !important; }
        body.dark-mode .form-control::placeholder { color: #64748b; }
        body.dark-mode h1 { color: #e2e8f0 !important; }
        body.dark-mode .nav-jb .nav-brand { color: white !important; }
        body.dark-mode .nav-jb .nav-btn { color: rgba(255,255,255,0.9) !important; }
        body.dark-mode .nav-jb .nav-btn.active { color: var(--jb-navy) !important; }
        body.dark-mode .nav-jb .nav-sep { color: rgba(255,255,255,0.3) !important; }
        body.dark-mode .toast { background-color: #1a2235 !important; border: 1px solid #2d3748 !important; }
        body.dark-mode .toast-body { color: #e2e8f0 !important; }
        body.dark-mode .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
        body.dark-mode .nav-logo { filter: brightness(0.9); }

        .nav-logo { height:53px; width:auto; }
        @media (max-width:767.98px) { .nav-logo { height:42px; } }

        /* ===== INVENTARIO: CONTENIDO ===== */
        .container-ft { max-width: 1400px; margin: 0 auto; padding: 0 1rem 2rem; }

        h1 { color: var(--ft-navy); margin-bottom: 5px; font-size: 1.5rem; font-weight: 700; }
        body.dark-mode h1 { color: #e2e8f0 !important; }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        body.dark-mode .alert-success { background: #064e3b; color: #6ee7b7; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        body.dark-mode .alert-error { background: #5c1a1a; color: #fca5a5; }

        .form-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 3px solid var(--ft-cyan);
            border-right: 3px solid var(--ft-cyan);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        body.dark-mode .form-section { background: #1a2235; border-left-color: var(--jb-cyan); border-right-color: var(--jb-cyan); }
        .form-section h2 {
            color: var(--ft-azul-oscuro);
            font-size: 14px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        body.dark-mode .form-section h2 { color: var(--jb-cyan); }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
        }
        body.dark-mode .form-group label { color: #94a3b8; }

        select, input[type="text"], textarea {
            width: 100%;
            padding: 12px 15px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #1e293b;
            font-size: 15px;
            outline: none;
            transition: all 0.2s;
        }
        select:focus, input:focus, textarea:focus {
            border-color: var(--ft-cyan);
            box-shadow: 0 0 0 3px rgba(0,168,232,0.15);
        }
        body.dark-mode select, body.dark-mode input[type="text"], body.dark-mode textarea {
            background: #0f1729; border-color: #2d3748; color: #e2e8f0;
        }

        /* Número */
        .numero-caja {
            background: linear-gradient(135deg, var(--ft-azul-oscuro), var(--ft-azul));
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,56,168,0.3);
        }
        .numero-caja label {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .numero-caja .numero {
            color: white;
            font-size: 42px;
            font-weight: 800;
        }
        .numero-caja input { display: none; }

        .input-con-boton {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .input-con-boton select, .input-con-boton input { flex: 1; }
        .btn-icono {
            width: 44px;
            height: 44px;
            background: var(--ft-cyan);
            border: none;
            border-radius: 8px;
            color: var(--ft-navy);
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-weight: 700;
        }
        .btn-icono:hover {
            background: var(--ft-azul);
            color: white;
            transform: translateY(-2px);
        }

        /* Buscador modelo */
        .buscador-modelo { position: relative; flex: 1; }
        .buscador-modelo input {
            width: 100%;
            padding: 12px 15px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #1e293b;
            font-size: 15px;
        }
        .lista-resultados {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #cbd5e1;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        body.dark-mode .lista-resultados { background: #1a2235; border-color: #2d3748; }
        .lista-resultados.visible { display: block; }
        .resultado-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        body.dark-mode .resultado-item { color: #e2e8f0; border-bottom-color: #2d3748; }
        .resultado-item:hover { background: #f0f9ff; color: var(--ft-azul); }
        body.dark-mode .resultado-item:hover { background: #1e3a5f; }
        .sin-resultados {
            padding: 15px;
            color: #94a3b8;
            text-align: center;
        }

        /* Componentes */
        .componentes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .componente-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        body.dark-mode .componente-card { background: #0f1729; border-color: #2d3748; }
        .componente-card:hover {
            border-color: var(--ft-cyan);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,168,232,0.15);
        }
        .componente-card.seleccionado {
            border-color: var(--ft-cyan);
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            box-shadow: 0 4px 12px rgba(0,168,232,0.2);
        }
        body.dark-mode .componente-card.seleccionado { background: linear-gradient(135deg, #0e2a5a, #1e3a5f); }
        .componente-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--ft-azul);
            flex-shrink: 0;
        }
        .comp-nombre {
            font-weight: 600;
            font-size: 14px;
            color: #334155;
        }
        body.dark-mode .comp-nombre { color: #cbd5e1; }
        .componente-card.seleccionado .comp-nombre {
            color: var(--ft-azul-oscuro);
        }
        body.dark-mode .componente-card.seleccionado .comp-nombre { color: var(--jb-cyan); }

        /* Observaciones */
        .observaciones-box { margin-top: 20px; }
        .observaciones-box textarea {
            width: 100%;
            min-height: 80px;
            resize: vertical;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--ft-cyan), var(--ft-azul));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,168,232,0.4);
        }
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,24,69,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-overlay.visible { display: flex; }
        .modal-ft {
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,24,69,0.3);
        }
        body.dark-mode .modal-ft { background: #1a2235; }
        .modal-ft h3 {
            color: var(--ft-azul-oscuro);
            margin-bottom: 20px;
            font-size: 18px;
        }
        body.dark-mode .modal-ft h3 { color: var(--jb-cyan); }
        .modal-ft .form-group { margin-bottom: 15px; }
        .modal-botones {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-botones button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-guardar-modal {
            background: linear-gradient(135deg, var(--ft-cyan), var(--ft-azul));
            color: white;
        }
        .btn-cancelar-modal {
            background: #f1f5f9;
            color: #64748b;
        }
        body.dark-mode .btn-cancelar-modal { background: #2d3748; color: #94a3b8; }
        .btn-cancelar-modal:hover { background: #e2e8f0; }
        body.dark-mode .btn-cancelar-modal:hover { background: #374151; }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0,168,232,0.3);
            border-radius: 50%;
            border-top-color: var(--ft-cyan);
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .hidden { display: none !important; }

        /* ===== INV-SIDEBAR (movimientos log, antiguo sidebar) ===== */
        .inv-sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,24,69,0.5);
            display: none;
            z-index: 998;
        }
        .inv-sidebar-overlay.visible { display: block; }

        .inv-sidebar {
            position: fixed;
            top: 0;
            left: -320px;
            width: 320px;
            height: 100vh;
            background: white;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            z-index: 999;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        body.dark-mode .inv-sidebar { background: #1a2235; }
        .inv-sidebar.open { left: 0; }

        .inv-sidebar-header {
            background: linear-gradient(135deg, var(--ft-navy) 0%, var(--ft-azul-oscuro) 50%, var(--ft-azul) 100%);
            padding: 1rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .inv-sidebar-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        .inv-sidebar-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .inv-sidebar-close:hover { background: rgba(255,255,255,0.2); }

        .inv-sidebar-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .inv-sidebar-section {
            margin-bottom: 1.5rem;
        }
        .inv-sidebar-section h4 {
            color: var(--ft-azul-oscuro);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }
        body.dark-mode .inv-sidebar-section h4 { color: var(--jb-cyan); }

        .log-lista {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .log-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px 12px;
            border-left: 3px solid var(--ft-cyan);
            font-size: 13px;
        }
        body.dark-mode .log-item { background: #0f1729; }
        .log-item.ingreso {
            border-left-color: #10b981;
        }
        .log-item.retiro {
            border-left-color: #ef4444;
        }
        .log-item.restauracion {
            border-left-color: #00a8e8;
        }
        .log-fecha {
            color: #94a3b8;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .log-texto {
            color: #334155;
            line-height: 1.4;
        }
        body.dark-mode .log-texto { color: #cbd5e1; }
        .log-texto strong {
            color: var(--ft-azul-oscuro);
        }
        .log-vacio {
            color: #94a3b8;
            text-align: center;
            padding: 20px;
            font-size: 13px;
        }

        @media (max-width: 576px) {
            .container-ft { padding: 0 0.5rem 1rem; }
        }
    </style>
</head>
<body>


<!-- Sidebar Offcanvas (Ordenes) -->
<div class="offcanvas offcanvas-start sidebar-jb" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">
            <?php if ($ES_ADMIN): ?>
            <i class="bi bi-shield-lock me-2"></i> Admin
            <?php elseif ($ES_FULL): ?>
            <i class="bi bi-person-check me-2"></i> Full Órdenes
            <?php elseif ($ES_TECNICO): ?>
            <i class="bi bi-tools me-2"></i> Técnico
            <?php else: ?>
            <i class="bi bi-headset me-2"></i> Recepción
            <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3 d-flex flex-column">
        <div>
            <a href="<?php echo $BASE_PATH; ?>/../dashboard.php" class="sidebar-menu-item">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <a href="ingreso.php" class="sidebar-menu-item active">
                <i class="bi bi-box-seam"></i>
                <span>Ingreso</span>
            </a>

            <a href="buscador.php" class="sidebar-menu-item">
                <i class="bi bi-search"></i>
                <span>Buscar</span>
            </a>
        </div>

        <div style="flex:1;"></div>

        <hr class="sidebar-divider">

        <button class="sidebar-menu-item" onclick="toggleDarkMode(); bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu')).hide();">
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i>
            <span id="sidebarTextDark">Modo Nocturno</span>
        </button>

        <hr class="sidebar-divider">

        <a href="<?php echo $BASE_PATH; ?>/../logout.php" class="sidebar-menu-item">
            <i class="bi bi-box-arrow-right"></i>
            <span>Cerrar Sesi&oacute;n</span>
        </a>
    </div>
</div>

<!-- Navbar -->
<nav class="nav-jb" style="padding:0;">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 0 0 0.25rem; position: relative;">
        <div class="nav-left" style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="<?php echo $BASE_PATH; ?>/../dashboard.php" class="nav-btn d-none d-md-inline-flex" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.9);padding:0.4rem 0.9rem;border-radius:6px;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
        <div class="nav-center" style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
            <span style="display:inline-flex; align-items:center; gap:10px; color:white;">
                <img src="<?php echo $BASE_PATH; ?>/../ordenes/logo.png?v=<?php echo file_exists(__DIR__ . '/../ordenes/logo.png') ? filemtime(__DIR__ . '/../ordenes/logo.png') : time(); ?>" alt="FullTaller" class="nav-logo">
                <span class="d-none d-sm-inline" style="font-size:0.95rem; font-weight:500;">Repuestos/Scrap - Ingreso</span>
            </span>
        </div>
        <div class="nav-right" style="display: flex; align-items: center; gap: 2px;">
            <a href="buscador.php" class="nav-btn d-none d-md-inline-flex" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:white;padding:0.6rem 1.2rem;border-radius:8px;font-size:0.95rem;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-weight:600;margin-right:45px;"><i class="bi bi-search"></i> Buscar</a>
            <div style="display: flex; flex-direction: column; align-items: stretch; gap: 2px;" class="rol-salir-wrapper">
                <span class="rol-badge" style="padding:2px 10px; font-size:0.8rem; text-align:center; width:100%; display:block;">
                    <?php if ($ES_ADMIN): ?>
                    <i class="bi bi-shield-lock"></i>
                    <?php else: ?>
                    <i class="bi bi-<?php echo $ES_FULL ? 'person-check' : ($ES_TECNICO ? 'tools' : 'headset'); ?>"></i>
                    <?php endif; ?>
                    <span style="font-weight:400;font-size:0.7rem;"><?php echo htmlspecialchars($NOMBRE_USUARIO); ?></span>
                </span>
            </div>
        </div>
    </div>
</nav>

<div style="padding: 0 0.25rem; margin-bottom: 2rem;">
    <div class="container-ft" style="max-width:1400px;margin:0 auto;padding:0 1rem 2rem;">
        <h1><i class="bi bi-box-seam"></i> Ingreso de Equipo</h1>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success"><?= $mensaje ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" id="formIngreso">

            <div class="numero-caja">
                <label>Número asignado</label>
                <div class="numero">#<?= $proxima_caja ?></div>
                <input type="hidden" name="numero_caja" value="<?= $proxima_caja ?>">
            </div>

            <div class="form-section">
                <h2><i class="bi bi-tag"></i> Marca</h2>
                <div class="input-con-boton">
                    <select id="marca" name="marca" required>
                        <option value="">Seleccionar marca...</option>
                        <?php foreach ($marcas as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-icono" onclick="abrirModal('marca')" title="Agregar marca">+</button>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="bi bi-phone"></i> Modelo</h2>
                <div class="input-con-boton">
                    <div class="buscador-modelo">
                        <input type="text" id="buscador_modelo" placeholder="Escribí para buscar modelo..." autocomplete="off" disabled>
                        <input type="hidden" id="modelo_id" name="modelo_id">
                        <div id="lista_modelos" class="lista-resultados"></div>
                    </div>
                    <button type="button" class="btn-icono" onclick="abrirModal('modelo')" title="Agregar modelo" id="btnAddModelo" disabled>+</button>
                </div>
            </div>

            <div class="form-section hidden" id="seccionComponentes">
                <h2><i class="bi bi-check-square"></i> Componentes disponibles</h2>
                <p style="color: #64748b; font-size: 13px; margin-bottom: 15px;">
                    Seleccioná los componentes que tiene este equipo
                </p>
                <div id="componentesContainer" class="componentes-grid"></div>

                <div class="observaciones-box">
                    <label for="observaciones"><i class="bi bi-pencil-square"></i> Observaciones generales</label>
                    <textarea id="observaciones" name="observaciones" placeholder="Estado general, golpes, detalles importantes..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btnGuardar" disabled>
                <i class="bi bi-save"></i> Guardar #<?= $proxima_caja ?>
            </button>

        </form>
    </div>
</div>

<!-- MODAL MARCA -->
<div class="modal-overlay" id="modalMarca">
    <div class="modal-ft">
        <h3><i class="bi bi-plus-circle"></i> Nueva Marca</h3>
        <div class="form-group">
            <label>Nombre de la marca</label>
            <input type="text" id="nueva_marca" placeholder="Ej: Realme">
        </div>
        <div class="modal-botones">
            <button class="btn-cancelar-modal" onclick="cerrarModal('marca')">Cancelar</button>
            <button class="btn-guardar-modal" onclick="guardarMarca()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL MODELO -->
<div class="modal-overlay" id="modalModelo">
    <div class="modal-ft">
        <h3><i class="bi bi-plus-circle"></i> Nuevo Modelo</h3>
        <div class="form-group">
            <label>Marca</label>
            <input type="text" id="modal_marca_nombre" readonly style="background:#e2e8f0; border-color:#cbd5e1;">
            <input type="hidden" id="modal_marca_id">
        </div>
        <div class="form-group">
            <label>Nombre del modelo</label>
            <input type="text" id="nuevo_modelo" placeholder="Ej: Galaxy S24 Ultra">
        </div>
        <div class="form-group">
            <label>Tipo</label>
            <select id="nuevo_modelo_tipo">
                <option value="celular">Celular</option>
                <option value="notebook">Notebook</option>
                <option value="tablet">Tablet</option>
                <option value="otro">Otro</option>
            </select>
        </div>
        <div class="modal-botones">
            <button class="btn-cancelar-modal" onclick="cerrarModal('modelo')">Cancelar</button>
            <button class="btn-guardar-modal" onclick="guardarModelo()">Guardar</button>
        </div>
    </div>
</div>

<!-- INV-SIDEBAR (Movimientos log) -->
<div class="inv-sidebar-overlay" id="invSidebarOverlay" onclick="toggleInvSidebar()"></div>
<div class="inv-sidebar" id="invSidebar">
    <div class="inv-sidebar-header">
        <h3><i class="bi bi-clock-history"></i> Movimientos</h3>
        <button class="inv-sidebar-close" onclick="toggleInvSidebar()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="inv-sidebar-body">
        <div class="inv-sidebar-section">
            <h4><i class="bi bi-clock-history"></i> Movimientos</h4>
            <div class="log-lista" id="logLista">
                <?php if (empty($movimientos_log)): ?>
                    <div class="log-vacio">No hay movimientos registrados</div>
                <?php else: ?>
                    <?php foreach ($movimientos_log as $log): ?>
                        <div class="log-item <?= htmlspecialchars($log['tipo']) ?>">
                            <div class="log-fecha"><i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($log['fecha'])) ?></div>
                            <div class="log-texto">
                                <?php if ($log['tipo'] == 'ingreso'): ?>
                                    <i class="bi bi-box-seam" style="color:#10b981"></i> <strong>Ingreso</strong> caja #<?= $log['caja_id'] ?><br>
                                    <small><?= htmlspecialchars($log['marca']) ?> <?= htmlspecialchars($log['modelo']) ?></small><br>
                                    <small style="color:#64748b"><?= htmlspecialchars($log['componente']) ?></small>
                                <?php elseif ($log['tipo'] == 'retiro'): ?>
                                    <i class="bi bi-box-arrow-up" style="color:#ef4444"></i> <strong>Retiro</strong> de caja #<?= $log['caja_id'] ?><br>
                                    <small><?= htmlspecialchars($log['marca']) ?> <?= htmlspecialchars($log['modelo']) ?></small><br>
                                    <small style="color:#64748b">Pieza: <?= htmlspecialchars($log['componente']) ?></small>
                                <?php elseif ($log['tipo'] == 'restauracion'): ?>
                                    <i class="bi bi-arrow-counterclockwise" style="color:#00a8e8"></i> <strong>Restauración</strong> caja #<?= $log['caja_id'] ?><br>
                                    <small><?= htmlspecialchars($log['marca']) ?> <?= htmlspecialchars($log['modelo']) ?></small><br>
                                    <small style="color:#64748b">Pieza: <?= htmlspecialchars($log['componente']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ===== INVENTARIO SCRAP =====
    const marcaSelect = document.getElementById('marca');
    const buscadorModelo = document.getElementById('buscador_modelo');
    const modeloIdInput = document.getElementById('modelo_id');
    const listaModelos = document.getElementById('lista_modelos');
    const btnAddModelo = document.getElementById('btnAddModelo');
    const seccionComponentes = document.getElementById('seccionComponentes');
    const container = document.getElementById('componentesContainer');
    const btnGuardar = document.getElementById('btnGuardar');

    let modelosCache = [];

    marcaSelect.addEventListener('change', function() {
        const marcaId = this.value;
        buscadorModelo.value = '';
        modeloIdInput.value = '';
        listaModelos.innerHTML = '';
        listaModelos.classList.remove('visible');
        seccionComponentes.classList.add('hidden');
        btnGuardar.disabled = true;

        if (!marcaId) {
            buscadorModelo.disabled = true;
            btnAddModelo.disabled = true;
            return;
        }

        buscadorModelo.disabled = false;
        btnAddModelo.disabled = false;

        fetch('ajax_modelos.php?marca_id=' + marcaId)
            .then(r => r.json())
            .then(modelos => { modelosCache = modelos; });
    });

    buscadorModelo.addEventListener('input', function() {
        const texto = this.value.toLowerCase().trim();
        listaModelos.innerHTML = '';

        if (texto.length < 1) {
            listaModelos.classList.remove('visible');
            return;
        }

        const filtrados = modelosCache.filter(m => m.nombre.toLowerCase().includes(texto));

        if (filtrados.length === 0) {
            listaModelos.innerHTML = '<div class="sin-resultados">Sin resultados</div>';
        } else {
            filtrados.forEach(m => {
                listaModelos.innerHTML += '<div class="resultado-item" onclick="seleccionarModelo(' + m.id + ", '" + m.nombre.replace(/'/g, "\\'") + "')" + '">' + m.nombre + '</div>';
            });
        }
        listaModelos.classList.add('visible');
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.buscador-modelo')) {
            listaModelos.classList.remove('visible');
        }
    });

    function seleccionarModelo(id, nombre) {
        modeloIdInput.value = id;
        buscadorModelo.value = nombre;
        listaModelos.classList.remove('visible');
        cargarComponentes(id);
    }

    function cargarComponentes(modeloId) {
        container.innerHTML = '<div class="loading"></div>';
        seccionComponentes.classList.remove('hidden');

        fetch('ajax_componentes.php?modelo_id=' + modeloId)
            .then(r => r.json())
            .then(componentes => {
                container.innerHTML = '';
                componentes.forEach((comp, index) => {
                    const compId = 'comp_' + index;
                    container.innerHTML += '<div class="componente-card" onclick="toggleComponente(this, ' + "'" + comp.componente_nombre.replace(/'/g, "\\'") + "'" + ')">' +
                        '<input type="checkbox" name="componentes[]" value="' + comp.componente_nombre + '" id="' + compId + '" onclick="event.stopPropagation()">' +
                        '<span class="comp-nombre">' + comp.componente_nombre + '</span>' +
                        '</div>';
                });
                validarFormulario();
            });
    }

    function toggleComponente(card, nombre) {
        const checkbox = card.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        card.classList.toggle('seleccionado', checkbox.checked);
        validarFormulario();
    }

    function validarFormulario() {
        const tieneMarca = marcaSelect.value !== '';
        const tieneModelo = modeloIdInput.value !== '';
        const tieneComponentes = document.querySelectorAll('.componente-card.seleccionado').length > 0;
        btnGuardar.disabled = !(tieneMarca && tieneModelo && tieneComponentes);
    }

    function abrirModal(tipo) {
        document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).classList.add('visible');
        if (tipo === 'modelo') {
            document.getElementById('modal_marca_id').value = marcaSelect.value;
            document.getElementById('modal_marca_nombre').value = marcaSelect.options[marcaSelect.selectedIndex].text;
        }
    }
    function cerrarModal(tipo) {
        document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).classList.remove('visible');
    }

    function guardarMarca() {
        const nombre = document.getElementById('nueva_marca').value.trim();
        if (!nombre) return alert('Ingresá un nombre de marca');

        const formData = new FormData();
        formData.append('accion', 'agregar_marca');
        formData.append('nombre', nombre);

        fetch('ingreso.php', {method: 'POST', body: formData})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const option = document.createElement('option');
                    option.value = data.id;
                    option.textContent = data.nombre;
                    marcaSelect.appendChild(option);
                    marcaSelect.value = data.id;
                    marcaSelect.dispatchEvent(new Event('change'));
                    document.getElementById('nueva_marca').value = '';
                    cerrarModal('marca');
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }

    function guardarModelo() {
        const marcaId = document.getElementById('modal_marca_id').value;
        const nombre = document.getElementById('nuevo_modelo').value.trim();
        const tipo = document.getElementById('nuevo_modelo_tipo').value;

        if (!nombre) return alert('Ingresá un nombre de modelo');

        const formData = new FormData();
        formData.append('accion', 'agregar_modelo');
        formData.append('marca_id', marcaId);
        formData.append('nombre', nombre);
        formData.append('tipo', tipo);

        fetch('ingreso.php', {method: 'POST', body: formData})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (!data.existing) {
                        modelosCache.push({id: data.id, nombre: data.nombre});
                    }
                    seleccionarModelo(data.id, data.nombre);
                    document.getElementById('nuevo_modelo').value = '';
                    cerrarModal('modelo');
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }

    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('visible');
        });
    });

    function toggleInvSidebar() {
        document.getElementById('invSidebar').classList.toggle('open');
        document.getElementById('invSidebarOverlay').classList.toggle('visible');
    }

    // ===== DARK MODE =====
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('jb_dark_mode', isDark ? '1' : '0');
        updateDarkModeIcon();
    }
    function updateDarkModeIcon() {
        const sidebarIcon = document.getElementById('sidebarIconDark');
        const sidebarText = document.getElementById('sidebarTextDark');
        if (document.body.classList.contains('dark-mode')) {
            if (sidebarIcon) sidebarIcon.className = 'bi bi-sun-fill';
            if (sidebarText) sidebarText.textContent = 'Modo Claro';
        } else {
            if (sidebarIcon) sidebarIcon.className = 'bi bi-moon-stars-fill';
            if (sidebarText) sidebarText.textContent = 'Modo Nocturno';
        }
    }
    if (localStorage.getItem('jb_dark_mode') === '1') {
        document.body.classList.add('dark-mode');
    }
    updateDarkModeIcon();
</script>
</body>
</html>
