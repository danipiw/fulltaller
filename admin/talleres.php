<?php
require_once __DIR__ . '/includes/verificar_sesion_admin.php';
require_once __DIR__ . '/includes/verificar_token_admin.php';
verificar_csrf_token();

// Ensure pagos_talleres table exists
$conn_central->query("CREATE TABLE IF NOT EXISTS `pagos_talleres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `taller_id` INT NOT NULL,
  `meses` TINYINT NOT NULL DEFAULT 1,
  `hasta` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`taller_id`) REFERENCES `talleres`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");



$mensaje = '';
$error = '';

// Handle register payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago'])) {
    $taller_id = (int)$_POST['taller_id'];
    $meses = max(1, (int)$_POST['meses']);

    $t = $conn_central->query("SELECT * FROM talleres WHERE id = $taller_id")->fetch_assoc();
    if ($t) {
        $dia_facturacion = (int)date('j', strtotime($t['fecha_alta']));
        $actual = $t['fecha_vencimiento'] ?? $t['fecha_alta'];
        // If expired or never paid, start from today
        $venc_ts = strtotime($actual);
        if (!$actual || $venc_ts < time()) {
            $actual = date('Y-m-d');
        }
        $dt = new DateTime($actual);
        $dt->modify("+$meses months");
        $ultimo_dia = (int)$dt->format('t');
        $dia = min($dia_facturacion, $ultimo_dia);
        $dt->setDate((int)$dt->format('Y'), (int)$dt->format('m'), $dia);
        $hasta = $dt->format('Y-m-d');

        $conn_central->query("UPDATE talleres SET fecha_vencimiento = '$hasta' WHERE id = $taller_id");
        $conn_central->query("INSERT INTO pagos_talleres (taller_id, meses, hasta) VALUES ($taller_id, $meses, '$hasta')");
        $mensaje = 'Pago registrado correctamente. Vence el ' . date('d/m/Y', strtotime($hasta));
    } else {
        $error = 'Taller no encontrado';
    }
}

$accion = $_POST['accion'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($accion && $id > 0) {
    if ($accion === 'desactivar') {
        $conn_central->query("UPDATE talleres SET activo = 0 WHERE id = $id");
        $mensaje = 'Taller desactivado correctamente';
    } elseif ($accion === 'activar') {
        $conn_central->query("UPDATE talleres SET activo = 1 WHERE id = $id");
        $mensaje = 'Taller activado correctamente';
    } elseif ($accion === 'eliminar') {
        $t = $conn_central->query("SELECT subdominio, db_name FROM talleres WHERE id = $id")->fetch_assoc();
        if ($t) {
            $conn_central->query("DELETE FROM talleres WHERE id = $id");
            $mensaje = 'Taller <strong>' . htmlspecialchars($t['subdominio']) . '</strong> eliminado. La base de datos ' . htmlspecialchars($t['db_name']) . ' queda en el servidor.';
        }
    }
}

$talleres = $conn_central->query("SELECT * FROM talleres ORDER BY id DESC");
$hoy = date('Y-m-d');
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
                                <th>Pagos</th>
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
                                <td style="white-space:nowrap;">
                                    <?php
                                    $venc_ts = strtotime($t['fecha_vencimiento']);
                                    if ($venc_ts && $venc_ts >= strtotime($hoy)):
                                    ?>
                                        <span class="badge bg-success">Al día</span>
                                        <small style="font-size:0.7rem;color:#64748b;display:block;">Vence <?php echo date('d/m/Y', $venc_ts); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Vencido</span>
                                        <small style="font-size:0.7rem;color:#64748b;display:block;"><?php echo $t['fecha_vencimiento'] ? 'Vencía ' . date('d/m/Y', $venc_ts) : 'Sin pagos'; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['activo']): ?>
                                        <span class="badge badge-activo">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactivo">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acciones" style="white-space:nowrap;">
                                    <a href="taller_editar.php?id=<?php echo $t['id']; ?>" class="btn btn-primary btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="taller_usuarios.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm" title="Usuarios"><i class="bi bi-people"></i></a>
                                    <button type="button" class="btn btn-success btn-sm" title="Registrar pago" onclick="abrirPago(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars(addslashes($t['nombre'])); ?>')"><i class="bi bi-cash"></i></button>
                                    <?php if ($t['activo']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar este taller?')">
                                            <?php echo csrf_token_field(); ?>
                                            <input type="hidden" name="accion" value="desactivar">
                                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm" title="Desactivar"><i class="bi bi-pause-circle"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <?php echo csrf_token_field(); ?>
                                            <input type="hidden" name="accion" value="activar">
                                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm" title="Activar"><i class="bi bi-play-circle"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="taller_exportar.php?id=<?php echo $t['id']; ?>" class="btn btn-info btn-sm ms-1" title="Exportar BD completa"><i class="bi bi-download"></i></a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este taller? No borra la base de datos.')">
                                        <?php echo csrf_token_field(); ?>
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm ms-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="pagoModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h6 class="modal-title">Registrar pago</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="taller_id" id="pago_taller_id">
                        <p class="mb-2" id="pago_taller_nombre"></p>
                        <div class="mb-2">
                            <label class="form-label small">Meses a abonar</label>
                            <input type="number" name="meses" class="form-control" value="1" min="1" max="12" required>
                        </div>
                        <div class="small text-muted">El vencimiento se calcula según el día de facturación del taller.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="registrar_pago" class="btn btn-success w-100">Registrar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function abrirPago(id, nombre) {
        document.getElementById('pago_taller_id').value = id;
        document.getElementById('pago_taller_nombre').textContent = nombre;
        new bootstrap.Modal(document.getElementById('pagoModal')).show();
    }
    </script>
</body>
</html>
