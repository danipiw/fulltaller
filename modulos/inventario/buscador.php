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


$componentes_filtro = $pdo->query("
    SELECT DISTINCT componente_nombre 
    FROM modelo_componentes 
    ORDER BY componente_nombre
")->fetchAll(PDO::FETCH_COLUMN);

$resultados = [];
$busqueda = $_GET['buscar'] ?? '';
$filtro_componente = $_GET['componente'] ?? '';

if (!empty($busqueda)) {
    $params = ['%' . $busqueda . '%'];
    $sql_componente = "";
    $sql_usado = " AND ci.usado = 0";
    
    if (!empty($filtro_componente)) {
        $sql_componente = " AND ci.componente_nombre = ?";
        $params[] = $filtro_componente;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id as caja_id,
            c.numero,
            c.fecha_ingreso,
            m.nombre as modelo,
            ma.nombre as marca,
            ci.componente_nombre,
            ci.notas as observaciones
        FROM cajas c
        INNER JOIN caja_items ci ON c.id = ci.caja_id
        INNER JOIN modelos m ON ci.modelo_id = m.id
        INNER JOIN marcas ma ON m.marca_id = ma.id
        WHERE m.nombre LIKE ? 
        $sql_componente
        $sql_usado
        ORDER BY c.numero DESC
    ");
    $stmt->execute($params);
    $resultados_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cajas_agrupadas = [];
    foreach ($resultados_raw as $r) {
        $caja_id = $r['caja_id'];
        if (!isset($cajas_agrupadas[$caja_id])) {
            $cajas_agrupadas[$caja_id] = [
                'numero' => $r['numero'],
                'fecha_ingreso' => $r['fecha_ingreso'],
                'modelo' => $r['modelo'],
                'marca' => $r['marca'],
                'observaciones' => $r['observaciones'],
                'componentes' => []
            ];
        }
        $cajas_agrupadas[$caja_id]['componentes'][] = $r['componente_nombre'];
    }
    $resultados = $cajas_agrupadas;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Repuestos/Scrap - Buscar</title>
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
        html, body { margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', system-ui, sans-serif; }

        /* Navbar */
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

        /* ===== NOTIFICACIONES ===== */
        .notificaciones-container { position: relative; margin-right: 10px; }
        .btn-notificaciones {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            font-size: 1.2rem;
        }
        .btn-notificaciones:hover { background: rgba(255,255,255,0.2); transform: scale(1.05); }
        .btn-notificaciones.pulse { animation: notifPulse 1.5s ease infinite; }
        @keyframes notifPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        .notif-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--jb-navy);
        }
        .dropdown-notificaciones {
            position: absolute;
            top: 50px;
            right: 0;
            width: 340px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 1050;
            display: none;
            overflow: hidden;
        }
        .dropdown-notificaciones.show { display: block; animation: fadeInDown 0.2s ease; }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: var(--jb-navy);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notif-lista { max-height: 350px; overflow-y: auto; }
        .notif-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .notif-item:hover { background: #f8fafc; }
        .notif-item.no-leida {
            background: #eff6ff;
            border-left: 3px solid var(--jb-azul);
        }
        .notif-item.no-leida:hover { background: #dbeafe; }
        .notif-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .notif-icon.recepcion { background: #dbeafe; color: #1d4ed8; }
        .notif-icon.tecnico { background: #dcfce7; color: #15803d; }
        .notif-content { flex: 1; min-width: 0; }
        .notif-titulo { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 2px; }
        .notif-mensaje { font-size: 0.8rem; color: #64748b; line-height: 1.3; }
        .notif-fecha { font-size: 0.7rem; color: #94a3b8; margin-top: 4px; }
        .notif-eliminar {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 2px 6px;
            cursor: pointer;
            border-radius: 4px;
            flex-shrink: 0;
            transition: all 0.15s;
            line-height: 1;
        }
        .notif-eliminar:hover { background: #fee2e2; color: #ef4444; }
        .notif-vacio { padding: 30px; text-align: center; color: #94a3b8; }
        .notif-vacio i { font-size: 2rem; display: block; margin-bottom: 10px; }

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

        /* ===== SIDEBAR ===== */
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
        .sidebar-menu-item.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left: 3px solid var(--jb-cyan);
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
        .sidebar-section-label {
            padding: 12px 16px 4px;
            color: var(--jb-cyan);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        /* ===== MODO NOCTURNO ===== */
        body.dark-mode {
            background-color: #0f1729 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .card { background-color: #1a2235 !important; border-left-color: var(--jb-cyan) !important; color: #e2e8f0; }
        body.dark-mode .card-header { background-color: #1a2235 !important; border-bottom-color: #2d3748 !important; color: #e2e8f0; }
        body.dark-mode .dropdown-notificaciones { background-color: #1a2235 !important; border: 1px solid #2d3748; }
        body.dark-mode .notif-header { color: #e2e8f0; border-bottom-color: #2d3748; }
        body.dark-mode .notif-item { border-bottom-color: #2d3748; }
        body.dark-mode .notif-item:hover { background: #243047; }
        body.dark-mode .notif-item.no-leida { background: #1e3a5f; }
        body.dark-mode .notif-titulo { color: #e2e8f0; }
        body.dark-mode .notif-mensaje { color: #94a3b8; }
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
        body.dark-mode .sidebar-jb {
            background: linear-gradient(180deg, #0a1628 0%, #0f1f3d 100%) !important;
        }

        /* ===== INVENTARIO CONTENIDO ===== */
        .container-ft { max-width: 1400px; margin: 0 auto; padding: 0 1rem 2rem; }
        
        h1 { color: var(--ft-navy); margin-bottom: 5px; font-size: 1.5rem; font-weight: 700; }
        .subtitle { color: #64748b; margin-bottom: 25px; font-size: 14px; }
        
        .buscador-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 3px solid var(--ft-cyan);
            border-right: 3px solid var(--ft-cyan);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .buscador-box h2 {
            color: var(--ft-azul-oscuro);
            font-size: 14px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        
        .input-busqueda {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .input-busqueda input {
            flex: 1;
            padding: 14px 18px;
            background: #f8fafc;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            color: #1e293b;
            font-size: 16px;
            outline: none;
            transition: all 0.2s;
        }
        .input-busqueda input:focus {
            border-color: var(--ft-cyan);
            box-shadow: 0 0 0 3px rgba(0,168,232,0.15);
        }
        .input-busqueda button {
            padding: 14px 25px;
            background: linear-gradient(135deg, var(--ft-cyan), var(--ft-azul));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .input-busqueda button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,168,232,0.4);
        }
        
        .filtro-componente {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filtro-componente label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        .filtro-componente select {
            padding: 10px 15px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #1e293b;
            font-size: 14px;
            cursor: pointer;
            outline: none;
        }
        .filtro-componente select:focus {
            border-color: var(--ft-cyan);
        }
        
        .sin-resultados {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-size: 16px;
        }
        
        .caja-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 3px solid var(--ft-cyan);
            border-right: 3px solid var(--ft-cyan);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.2s;
            cursor: pointer;
        }
        .caja-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,168,232,0.15);
            border-color: var(--ft-azul);
        }
        
        .caja-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .caja-numero {
            background: linear-gradient(135deg, var(--ft-azul-oscuro), var(--ft-azul));
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .caja-info {
            text-align: right;
        }
        .caja-info .modelo {
            color: #1e293b;
            font-weight: 600;
            font-size: 16px;
        }
        .caja-info .marca {
            color: #64748b;
            font-size: 13px;
        }
        .caja-info .fecha {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .componentes-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .componente-tag {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: var(--ft-azul-oscuro);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #bae6fd;
        }
        
        .observaciones {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 15px;
            border-left: 3px solid var(--ft-cyan);
        }
        .observaciones-label {
            color: var(--ft-azul-oscuro);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .observaciones-texto {
            color: #475569;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .sin-obs {
            color: #94a3b8;
            font-style: italic;
            font-size: 13px;
        }
        
        .resultados-count {
            color: #64748b;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .volver-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--ft-azul);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .volver-link:hover {
            color: var(--ft-cyan);
            text-decoration: underline;
        }

        /* ===== INVENTARIO SIDEBAR (movimientos) ===== */
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
        .log-texto strong {
            color: var(--ft-azul-oscuro);
        }
        .log-vacio {
            color: #94a3b8;
            text-align: center;
            padding: 20px;
            font-size: 13px;
        }

        /* Dark mode overrides for inventario elements */
        body.dark-mode .buscador-box {
            background-color: #1a2235 !important;
            border-left-color: var(--ft-cyan) !important;
            border-right-color: var(--ft-cyan) !important;
        }
        body.dark-mode .buscador-box h2 { color: #e2e8f0 !important; }
        body.dark-mode .input-busqueda input {
            background-color: #0f1729 !important;
            border-color: #2d3748 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .filtro-componente select {
            background-color: #0f1729 !important;
            border-color: #2d3748 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .caja-card {
            background-color: #1a2235 !important;
            border-left-color: var(--ft-cyan) !important;
            border-right-color: var(--ft-cyan) !important;
        }
        body.dark-mode .caja-header { border-bottom-color: #2d3748 !important; }
        body.dark-mode .caja-info .modelo { color: #e2e8f0 !important; }
        body.dark-mode .caja-info .marca { color: #94a3b8 !important; }
        body.dark-mode .componente-tag {
            background: linear-gradient(135deg, #0f1729, #1a2235) !important;
            color: #38bdf8 !important;
            border-color: #2d3748 !important;
        }
        body.dark-mode .observaciones { background: #0f1729 !important; }
        body.dark-mode .observaciones-texto { color: #94a3b8 !important; }
        body.dark-mode .resultados-count { color: #94a3b8 !important; }
        body.dark-mode .inv-sidebar { background-color: #1a2235 !important; }
        body.dark-mode .inv-sidebar-body .log-item { background: #0f1729 !important; }
        body.dark-mode .inv-sidebar-body .log-item .log-texto { color: #cbd5e1 !important; }
        body.dark-mode .sin-resultados { color: #64748b !important; }
        body.dark-mode .sin-obs { color: #64748b !important; }
        body.dark-mode .subtitle { color: #94a3b8 !important; }

        .nav-logo { height:53px; width:auto; }
        @media (max-width:767.98px) { .nav-logo { height:42px; } }
        @media (max-width: 767px) {
            .nav-center { position: absolute; left: 50%; transform: translateX(-50%); }
            .nav-left, .nav-right { min-width: 40px; }
            .nav-right .notificaciones-container {
                position: absolute;
                top: 100%;
                right: 0.25rem;
                z-index: 1055;
            }
            .nav-right .notificaciones-container .btn-notificaciones {
                background: var(--jb-cyan);
                border-color: var(--jb-cyan);
                color: white;
                box-shadow: 0 2px 8px rgba(0,56,168,0.4);
            }
        }
    </style>
</head>
<body>

<!-- Toast -->
<div class="toast-container">
    <div id="toastNotificacion" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMensaje">Operación correcta</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Ordenes Sidebar Offcanvas -->
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

            <a href="ingreso.php" class="sidebar-menu-item">
                <i class="bi bi-box-seam"></i>
                <span>Ingreso</span>
            </a>

            <a href="buscador.php" class="sidebar-menu-item active">
                <i class="bi bi-search"></i>
                <span>Buscar</span>
            </a>

            <hr class="sidebar-divider">
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
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>

<!-- Ordenes Navbar -->
<nav class="nav-jb" style="padding:0;">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 0 0 0.25rem; position: relative;">
        <div class="nav-left" style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="ingreso.php" class="nav-btn d-none d-md-inline-flex" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.9);padding:0.4rem 0.9rem;border-radius:6px;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Volver a Ingreso</a>
            <span class="d-none d-sm-inline" style="color:white; font-weight:500; font-size:0.95rem;">
                <i class="bi bi-search me-1"></i> Buscar repuestos
            </span>
        </div>

        <div class="nav-center" style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
            <span style="display:inline-flex; align-items:center; gap:10px; color:white;">
                <img src="<?php echo $BASE_PATH; ?>/../ordenes/logo.png?v=<?php echo file_exists(__DIR__ . '/../ordenes/logo.png') ? filemtime(__DIR__ . '/../ordenes/logo.png') : 0; ?>" alt="FullTaller" class="nav-logo">
                <span class="d-none d-sm-inline" style="font-size:0.95rem; font-weight:500;">Repuestos/Scrap</span>
            </span>
        </div>

        <div class="nav-right" style="display: flex; align-items: center; gap: 2px;">
            <span class="rol-badge" style="padding:2px 10px; font-size:0.8rem; text-align:center;">
                <?php if ($ES_ADMIN): ?>
                <i class="bi bi-shield-lock"></i>
                <?php else: ?>
                <i class="bi bi-<?php echo $ES_FULL ? 'person-check' : ($ES_TECNICO ? 'tools' : 'headset'); ?>"></i>
                <?php endif; ?>
                <span style="font-weight:400;font-size:0.7rem;"><?php echo htmlspecialchars($NOMBRE_USUARIO); ?></span>
            </span>
        </div>
    </div>
</nav>

<div class="container-ft">
    <h1><i class="bi bi-search"></i> Buscador de Repuestos</h1>
    
    <div class="buscador-box">
        <h2>¿Qué modelo necesitás?</h2>
        <form method="GET" id="formBusqueda">
            <div class="input-busqueda">
                <input 
                    type="text" 
                    name="buscar" 
                    placeholder="Ej: A52, G84, S23 Ultra..." 
                    value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>"
                >
                <button type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
            
            <div class="filtro-componente">
                <label for="componente"><i class="bi bi-funnel"></i> Filtrar por componente:</label>
                <select name="componente" id="componente" onchange="document.getElementById('formBusqueda').submit()">
                    <option value="">Todos los componentes</option>
                    <?php foreach ($componentes_filtro as $comp): ?>
                        <option value="<?= htmlspecialchars($comp) ?>" <?= ($filtro_componente === $comp) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($comp) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    
    <?php if (isset($_GET['buscar'])): ?>
        <?php if (empty($resultados)): ?>
            <div class="sin-resultados">
                <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                No se encontraron resultados con ese modelo<br>
                <small>Probá con otro nombre o verificá la ortografía</small>
            </div>
        <?php else: ?>
            <div class="resultados-count">
                <i class="bi bi-check-circle"></i> Se encontraron <?= count($resultados) ?> resultado(s) con "<?= htmlspecialchars($busqueda) ?>"
                <?= !empty($filtro_componente) ? ' + filtro: ' . htmlspecialchars($filtro_componente) : '' ?>
            </div>
            
            <?php foreach ($resultados as $caja_id => $r): ?>
                <div class="caja-card" onclick="window.location.href='detalle.php?caja=<?= $caja_id ?>'">
                    <div class="caja-header">
                        <div class="caja-numero"><i class="bi bi-phone"></i> #<?= $r['numero'] ?></div>
                        <div class="caja-info">
                            <div class="modelo"><?= htmlspecialchars($r['modelo']) ?></div>
                            <div class="marca"><?= htmlspecialchars($r['marca']) ?></div>
                            <div class="fecha"><i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($r['fecha_ingreso'])) ?></div>
                        </div>
                    </div>
                    
                    <div class="componentes-lista">
                        <?php foreach ($r['componentes'] as $comp): ?>
                            <span class="componente-tag"><?= htmlspecialchars($comp) ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($r['observaciones'])): ?>
                        <div class="observaciones">
                            <div class="observaciones-label"><i class="bi bi-pencil-square"></i> Observaciones</div>
                            <div class="observaciones-texto"><?= nl2br(htmlspecialchars($r['observaciones'])) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="sin-obs"><i class="bi bi-dash-circle"></i> Sin observaciones</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Inventario Movimientos Sidebar -->
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
    document.getElementById('formBusqueda').addEventListener('submit', function() {
        document.querySelector('input[name="buscar"]').blur();
    });
    
    <?php if (isset($_GET['buscar']) && !empty($resultados)): ?>
    window.addEventListener('load', function() {
        const resultados = document.querySelector('.resultados-count');
        if (resultados) {
            setTimeout(() => {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    });
    <?php endif; ?>
    
    function toggleInvSidebar() {
        document.getElementById('invSidebar').classList.toggle('open');
        document.getElementById('invSidebarOverlay').classList.toggle('visible');
    }
</script>

<script>
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
