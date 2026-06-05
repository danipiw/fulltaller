<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/../includes/conexion_central.php';

$mensaje = '';
$error = '';

if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['accion'] === 'desactivar') {
        $conn_central->query("UPDATE talleres SET activo = 0 WHERE id = $id");
        $mensaje = 'Taller desactivado correctamente';
    } elseif ($_GET['accion'] === 'activar') {
        $conn_central->query("UPDATE talleres SET activo = 1 WHERE id = $id");
        $mensaje = 'Taller activado correctamente';
    } elseif ($_GET['accion'] === 'suspender') {
        $conn_central->query("UPDATE talleres SET suscripcion_activa = 0 WHERE id = $id");
        $mensaje = 'Suscripción suspendida';
    } elseif ($_GET['accion'] === 'reactivar_suscripcion') {
        $conn_central->query("UPDATE talleres SET suscripcion_activa = 1 WHERE id = $id");
        $mensaje = 'Suscripción reactivada';
    } elseif ($_GET['accion'] === 'eliminar') {
        $t = $conn_central->query("SELECT subdominio, db_name FROM talleres WHERE id = $id")->fetch_assoc();
        if ($t) {
            $conn_central->query("DELETE FROM talleres WHERE id = $id");
            $mensaje = 'Taller <strong>' . htmlspecialchars($t['subdominio']) . '</strong> eliminado. La base de datos ' . htmlspecialchars($t['db_name']) . ' queda en el servidor.';
        }
    }
}

$talleres = $conn_central->query("SELECT * FROM talleres ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Talleres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-navy: #001845; --jb-azul: #0077b6; }
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar-custom { background: var(--jb-navy); padding: 12px 24px; }
        .navbar-custom h4 { color: white; margin: 0; }
        .navbar-custom .user-info { color: rgba(255,255,255,0.8); font-size: 0.9rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .badge-activo { background: #16a34a; }
        .badge-inactivo { background: #dc2626; }
        .table th { border-top: none; color: #64748b; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        .acciones .btn { padding: 4px 8px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <nav class="navbar-custom d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-shield-lock"></i> Panel de Administración</h4>
        <div>
            <span class="user-info me-3"><i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0" style="color:var(--jb-navy);font-weight:700;">Talleres</h5>
            <a href="taller_nuevo.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nuevo Taller</a>
            <a href="importar_csv.php" class="btn btn-info"><i class="bi bi-upload"></i> Importar CSV</a>
            <a href="actualizar.php" class="btn btn-secondary"><i class="bi bi-arrow-repeat"></i> Actualizar</a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Subdominio</th>
                                <th>Módulos</th>
                                <th>Licencia</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($t = $talleres->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $t['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($t['nombre']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($t['subdominio']); ?></code></td>
                                <td>
                                    <?php
                                    $mods = explode(',', $t['modulos'] ?? 'ordenes,pos');
                                    foreach ($mods as $m):
                                        $m = trim($m);
                                        $badge = $m === 'pos' ? 'bg-warning text-dark' : 'bg-info text-dark';
                                    ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($m); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><code style="font-size:0.7rem;"><?php echo htmlspecialchars($t['license_key']); ?></code></td>
                                <td><?php echo date('d/m/Y', strtotime($t['fecha_vencimiento'])); ?></td>
                                <td style="white-space:nowrap;">
                                    <?php if ($t['activo']): ?>
                                        <span class="badge badge-activo">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactivo">Inactivo</span>
                                    <?php endif; ?>
                                    <?php if ($t['suscripcion_activa']): ?>
                                        <span class="badge bg-success">Suscrito</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Vencido</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acciones" style="white-space:nowrap;">
                                    <a href="taller_editar.php?id=<?php echo $t['id']; ?>" class="btn btn-primary btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="taller_usuarios.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm" title="Usuarios"><i class="bi bi-people"></i></a>
                                    <?php if ($t['activo']): ?>
                                        <a href="?accion=desactivar&id=<?php echo $t['id']; ?>" class="btn btn-warning btn-sm" title="Desactivar" onclick="return confirm('¿Desactivar este taller?')"><i class="bi bi-pause-circle"></i></a>
                                    <?php else: ?>
                                        <a href="?accion=activar&id=<?php echo $t['id']; ?>" class="btn btn-success btn-sm" title="Activar"><i class="bi bi-play-circle"></i></a>
                                    <?php endif; ?>
                                    <?php if ($t['suscripcion_activa']): ?>
                                        <a href="?accion=suspender&id=<?php echo $t['id']; ?>" class="btn btn-danger btn-sm ms-1" title="Suspender suscripción" onclick="return confirm('¿Suspender suscripción?')"><i class="bi bi-x-circle"></i></a>
                                    <?php else: ?>
                                        <a href="?accion=reactivar_suscripcion&id=<?php echo $t['id']; ?>" class="btn btn-success btn-sm ms-1" title="Reactivar suscripción"><i class="bi bi-check-circle"></i></a>
                                    <?php endif; ?>
                                    <div class="btn-group ms-1">
                                        <a href="taller_exportar.php?id=<?php echo $t['id']; ?>" class="btn btn-info btn-sm" title="Exportar BD"><i class="bi bi-download"></i></a>
                                        <a href="taller_exportar.php?id=<?php echo $t['id']; ?>&pos=1" class="btn btn-outline-info btn-sm" title="Exportar BD POS"><i class="bi bi-cart"></i></a>
                                    </div>
                                    <a href="?accion=eliminar&id=<?php echo $t['id']; ?>" class="btn btn-danger btn-sm ms-1" title="Eliminar" onclick="return confirm('¿Eliminar este taller? No borra la base de datos.')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
