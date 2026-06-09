<?php
include 'includes/verificar_sesion.php';

if (!$ES_ADMIN) {
    header('Location: listado.php');
    exit;
}

$mensaje = '';
$error = '';

// Crear usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'crear') {
        $usuario = trim($_POST['usuario']);
        $password = $_POST['password'];
        $nombre = trim($_POST['nombre']);
        $rol = $_POST['rol'];

        if (empty($usuario) || empty($password) || empty($nombre)) {
            $error = 'Todos los campos son obligatorios';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password, nombre, rol) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $usuario, $hash, $nombre, $rol);
            if ($stmt->execute()) {
                $mensaje = 'Usuario creado correctamente';
            } else {
                $error = 'Error al crear usuario: ' . $conn->error;
            }
        }
    } elseif ($_POST['action'] === 'cambiar_password') {
        $id = (int)$_POST['id'];
        $password = $_POST['password'];
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute();
            $mensaje = 'Contraseña actualizada';
        }
    } elseif ($_POST['action'] === 'toggle_activo') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE usuarios SET activo = IF(activo=1, 0, 1) WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $mensaje = 'Estado actualizado';
    }
}

$usuarios = $conn->query("SELECT id, usuario, nombre, rol, activo, created_at FROM usuarios ORDER BY id");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Usuarios - FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
        }
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .page-content { max-width: 900px; margin: 0 auto; padding: 1rem; }
        .card-user { background: white; border-radius: 12px; border-left: 3px solid var(--jb-cyan); box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 20px; margin-bottom: 16px; }
        .badge-rol { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-admin { background: #818cf8; color: white; }
        .badge-recepcion { background: #38bdf8; color: #0c4a6e; }
        .badge-tecnico { background: #fbbf24; color: #78350f; }
        @media (max-width: 480px) { .page-content { padding: 0.5rem; } }
    </style>
</head>
<body>
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center" style="padding: 12px 0;">
        <h1 style="color:var(--jb-navy);font-size:1.3rem;margin:0;"><i class="bi bi-shield-lock"></i> Gestión de Usuarios</h1>
        <a href="listado.php" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($mensaje); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Crear usuario -->
    <div class="card-user">
        <h5 style="margin:0 0 15px;font-size:1rem;"><i class="bi bi-person-plus"></i> Nuevo Usuario</h5>
        <form method="POST" class="row g-2">
            <input type="hidden" name="action" value="crear">
            <div class="col-md-4">
                <input type="text" name="usuario" class="form-control form-control-sm" placeholder="Usuario" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre completo" required>
            </div>
            <div class="col-md-2">
                <select name="rol" class="form-select form-select-sm" required>
                    <option value="recepcion">Recepción</option>
                    <option value="tecnico">Técnico</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="password" name="password" class="form-control form-control-sm" placeholder="Contraseña" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Crear Usuario</button>
            </div>
        </form>
    </div>

    <!-- Lista de usuarios -->
    <?php while ($u = $usuarios->fetch_assoc()): ?>
    <div class="card-user d-flex justify-content-between align-items-center">
        <div>
            <strong style="font-size:1rem;"><?php echo htmlspecialchars($u['nombre']); ?></strong>
            <span class="text-muted" style="font-size:0.85rem;"> (@<?php echo htmlspecialchars($u['usuario']); ?>)</span>
            <span class="badge-rol badge-<?php echo htmlspecialchars($u['rol']); ?>"><?php echo htmlspecialchars(ucfirst($u['rol'])); ?></span>
            <?php if (!$u['activo']): ?>
            <span class="badge bg-secondary">Inactivo</span>
            <?php endif; ?>
            <div style="font-size:0.75rem;color:#94a3b8;margin-top:4px;">Creado: <?php echo $u['created_at']; ?></div>
        </div>
        <div class="d-flex gap-1">
            <button class="btn btn-outline-warning btn-sm" onclick="cambiarPassword(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>')" title="Cambiar contraseña"><i class="bi bi-key"></i></button>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_activo">
                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                <button type="submit" class="btn btn-outline-<?php echo $u['activo'] ? 'danger' : 'success'; ?> btn-sm" title="<?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?>">
                    <i class="bi bi-<?php echo $u['activo'] ? 'pause-circle' : 'play-circle'; ?>"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Modal cambiar password -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Cambiar Contraseña</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="cambiar_password">
                    <input type="hidden" name="id" id="passUserId">
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña para <strong id="passUserLabel"></strong></label>
                        <input type="password" name="password" class="form-control" required minlength="4">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-key"></i> Cambiar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function cambiarPassword(id, usuario) {
    document.getElementById('passUserId').value = id;
    document.getElementById('passUserLabel').textContent = usuario;
    new bootstrap.Modal(document.getElementById('passwordModal')).show();
}
</script>
</body>
</html>