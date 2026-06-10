<?php
require_once __DIR__ . '/includes/verificar_sesion_admin.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID inválido');
}

$taller_id = (int)$_GET['id'];

require_once __DIR__ . '/../includes/conexion_central.php';

$taller = $conn_central->query("SELECT * FROM talleres WHERE id = $taller_id")->fetch_assoc();
if (!$taller) {
    die('Taller no encontrado');
}

$conn_taller = new mysqli($taller['db_host'], $taller['db_user'], $taller['db_pass'], $taller['db_name']);
if ($conn_taller->connect_error) {
    die('Error conectando a la BD del taller: ' . $conn_taller->connect_error);
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $usuario = trim($_POST['usuario']);
        $password = $_POST['password'];
        $nombre = trim($_POST['nombre']);
        $rol = $_POST['rol'];
        $modulos = [];
        if (!empty($_POST['mod_ordenes'])) $modulos[] = 'ordenes';
        if (!empty($_POST['mod_pos'])) $modulos[] = 'pos';
        if (!empty($_POST['mod_inventario'])) $modulos[] = 'inventario';
        if (!empty($_POST['mod_finanzas'])) $modulos[] = 'finanzas';
        if (!empty($_POST['mod_tienda'])) $modulos[] = 'tienda';
        $modulos_str = implode(',', $modulos);
        if (empty($modulos_str)) $modulos_str = 'ordenes';

        if (empty($usuario) || empty($password) || empty($nombre)) {
            $error = 'Todos los campos son obligatorios';
        } else {
            $u = $conn_taller->real_escape_string($usuario);
            $n = $conn_taller->real_escape_string($nombre);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $r = $conn_taller->real_escape_string($rol);
            $m = $conn_taller->real_escape_string($modulos_str);

            if ($conn_taller->query("INSERT INTO usuarios (usuario, password, nombre, rol, modulos) VALUES ('$u', '$hash', '$n', '$r', '$m')")) {
                $mensaje = "Usuario <strong>" . htmlspecialchars($usuario) . "</strong> creado correctamente";
            } else {
                $error = 'Error: ' . $conn_taller->error;
            }
        }
    } elseif ($action === 'editar_modulos') {
        $uid = (int)$_POST['user_id'];
        $mods = [];
        if (!empty($_POST['mod_ordenes'])) $mods[] = 'ordenes';
        if (!empty($_POST['mod_pos'])) $mods[] = 'pos';
        if (!empty($_POST['mod_inventario'])) $mods[] = 'inventario';
        if (!empty($_POST['mod_finanzas'])) $mods[] = 'finanzas';
        if (!empty($_POST['mod_tienda'])) $mods[] = 'tienda';
        $mods_str = implode(',', $mods);
        if (empty($mods_str)) $mods_str = 'ordenes';
        $conn_taller->query("UPDATE usuarios SET modulos = '$mods_str' WHERE id = $uid");
        $mensaje = 'Módulos actualizados';
    } elseif ($action === 'desactivar') {
        $id = (int)$_POST['user_id'];
        $conn_taller->query("UPDATE usuarios SET activo = 0 WHERE id = $id");
        $mensaje = 'Usuario desactivado';
    } elseif ($action === 'activar') {
        $id = (int)$_POST['user_id'];
        $conn_taller->query("UPDATE usuarios SET activo = 1 WHERE id = $id");
        $mensaje = 'Usuario activado';
    } elseif ($action === 'eliminar') {
        $id = (int)$_POST['user_id'];
        $u_eliminar = $conn_taller->query("SELECT usuario FROM usuarios WHERE id = $id")->fetch_assoc();
        if ($u_eliminar) {
            $conn_taller->query("DELETE FROM usuarios WHERE id = $id");
            $mensaje = 'Usuario eliminado permanentemente';
        }
    } elseif ($action === 'editar_usuario') {
        $uid = (int)$_POST['user_id'];
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $rol = $_POST['rol'];
        if (!empty($nombre) && !empty($usuario) && !empty($rol)) {
            $n = $conn_taller->real_escape_string($nombre);
            $u = $conn_taller->real_escape_string($usuario);
            $r = $conn_taller->real_escape_string($rol);
            $conn_taller->query("UPDATE usuarios SET nombre='$n', usuario='$u', rol='$r' WHERE id=$uid");
            $mensaje = 'Usuario actualizado';
        } else {
            $error = 'Todos los campos son obligatorios';
        }
    } elseif ($action === 'reset_pass') {
        $id = (int)$_POST['user_id'];
        $nueva_pass = $_POST['nueva_pass'];
        if (!empty($nueva_pass)) {
            $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
            $conn_taller->query("UPDATE usuarios SET password = '$hash' WHERE id = $id");
            $mensaje = 'Contraseña actualizada';
        }
    }
}

$usuarios = $conn_taller->query("SELECT * FROM usuarios ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - <?php echo htmlspecialchars($taller['nombre']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-navy: #001845; }
        body { background: #f1f5f9; font-family: system-ui, sans-serif; }
        .navbar-custom { background: var(--jb-navy); padding: 12px 24px; }
        .navbar-custom h4 { color: white; margin: 0; }
        .navbar-custom .badge { font-size: 0.7rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .table th { border-top: none; color: #64748b; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
    </style>
</head>
<body>
    <nav class="navbar-custom d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-people"></i> Usuarios: <?php echo htmlspecialchars($taller['nombre']); ?></h4>
        <div>
            <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($taller['subdominio']); ?></span>
            <a href="talleres.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-5">
                <div class="card p-3">
                    <h6 class="fw-bold mb-3" style="color:var(--jb-navy);"><i class="bi bi-plus-circle"></i> Nuevo Usuario</h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="crear">
                        <div class="mb-2">
                            <label class="form-label small">Usuario</label>
                            <input type="text" class="form-control form-control-sm" name="usuario" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Contraseña</label>
                            <input type="text" class="form-control form-control-sm" name="password" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Nombre</label>
                            <input type="text" class="form-control form-control-sm" name="nombre" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Rol</label>
                            <select class="form-select form-select-sm" name="rol">
                                <option value="cajero">Cajero</option>
                                <option value="recepcion">Recepción</option>
                                <option value="tecnico">Técnico</option>
                                <option value="full">Full Órdenes</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Módulos</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="mod_ordenes" value="1" id="nu_mod_ordenes" checked>
                                    <label class="form-check-label small" for="nu_mod_ordenes">Órdenes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="mod_pos" value="1" id="nu_mod_pos" checked>
                                    <label class="form-check-label small" for="nu_mod_pos">POS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="mod_inventario" value="1" id="nu_mod_inv" checked>
                                    <label class="form-check-label small" for="nu_mod_inv">Repuestos / Scrap</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="mod_finanzas" value="1" id="nu_mod_fin">
                                    <label class="form-check-label small" for="nu_mod_fin">Finanzas</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="mod_tienda" value="1" id="nu_mod_tienda">
                                    <label class="form-check-label small" for="nu_mod_tienda">Tienda</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg"></i> Crear Usuario</button>
                    </form>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre</th>
                                    <th>Rol</th>
                                    <th>Módulos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($u = $usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $u['id']; ?></td>
                                    <td><code><?php echo htmlspecialchars($u['usuario']); ?></code></td>
                                    <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                    <td><span class="badge bg-<?php echo $u['rol'] === 'tecnico' ? 'warning text-dark' : 'info'; ?>"><?php echo htmlspecialchars($u['rol']); ?></span></td>
                                    <td style="white-space:nowrap;">
                                        <?php
                                        $umods = explode(',', $u['modulos'] ?? 'ordenes,pos');
                                        foreach ($umods as $um):
                                            $um = trim($um);
                                            $ubadge = $um === 'pos' ? 'bg-warning text-dark' : 'bg-info text-dark';
                                        ?>
                                            <span class="badge <?php echo $ubadge; ?>" style="font-size:0.65rem;"><?php echo htmlspecialchars($um); ?></span>
                                        <?php endforeach; ?>
                                        <button class="btn btn-outline-secondary btn-sm ms-1" title="Editar módulos" onclick="editarModulos(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['modulos'] ?? 'ordenes,pos', ENT_QUOTES); ?>')"><i class="bi bi-gear"></i></button>
                                    </td>
                                    <td>
                                        <?php if ($u['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <?php if ($u['activo']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="desactivar">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button class="btn btn-warning btn-sm" title="Desactivar"><i class="bi bi-pause-circle"></i></button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="activar">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button class="btn btn-success btn-sm" title="Activar"><i class="bi bi-play-circle"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-primary btn-sm" title="Editar usuario" onclick="editarUsuario(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['rol'], ENT_QUOTES); ?>')"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-outline-secondary btn-sm" title="Reset pass" onclick="resetPass(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>')"><i class="bi bi-key"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar usuario <?php echo htmlspecialchars($u['usuario'], ENT_QUOTES); ?>?');">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button class="btn btn-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>
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

    </div>

<!-- Modal editar usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-pencil"></i> Editar Usuario</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="editar_usuario">
                    <input type="hidden" name="user_id" id="eu_user_id">
                    <div class="mb-2">
                        <label class="form-label small">Usuario</label>
                        <input type="text" class="form-control form-control-sm" name="usuario" id="eu_usuario" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nombre</label>
                        <input type="text" class="form-control form-control-sm" name="nombre" id="eu_nombre" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Rol</label>
                        <select class="form-select form-select-sm" name="rol" id="eu_rol">
                            <option value="cajero">Cajero</option>
                            <option value="recepcion">Recepción</option>
                            <option value="tecnico">Técnico</option>
                            <option value="full">Full Órdenes</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal editar módulos -->
<div class="modal fade" id="modalEditarModulos" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" id="formEditarModulos">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-gear"></i> Módulos de <span id="em_user_name"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="editar_modulos">
                    <input type="hidden" name="user_id" id="em_user_id">
                    <input type="hidden" name="mod_ordenes" id="f_em_ordenes" value="1" disabled>
                    <input type="hidden" name="mod_pos" id="f_em_pos" value="1" disabled>
                    <input type="hidden" name="mod_inventario" id="f_em_inv" value="1" disabled>
                    <input type="hidden" name="mod_finanzas" id="f_em_fin" value="1" disabled>
                    <input type="hidden" name="mod_tienda" id="f_em_tienda" value="1" disabled>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="em_ordenes">
                            <label class="form-check-label" for="em_ordenes">Órdenes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="em_pos">
                            <label class="form-check-label" for="em_pos">POS</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="em_inv">
                            <label class="form-check-label" for="em_inv">Repuestos / Scrap</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="em_fin">
                            <label class="form-check-label" for="em_fin">Finanzas</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="em_tienda">
                            <label class="form-check-label" for="em_tienda">Tienda</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarModulos(id, usuario, modulosActuales) {
    const tiene = {
        ordenes: modulosActuales.includes('ordenes'),
        pos: modulosActuales.includes('pos'),
        inventario: modulosActuales.includes('inventario'),
        finanzas: modulosActuales.includes('finanzas'),
        tienda: modulosActuales.includes('tienda')
    };
    document.getElementById('em_user_name').textContent = usuario;
    document.getElementById('em_ordenes').checked = tiene.ordenes;
    document.getElementById('em_pos').checked = tiene.pos;
    document.getElementById('em_inv').checked = tiene.inventario;
    document.getElementById('em_fin').checked = tiene.finanzas;
    document.getElementById('em_tienda').checked = tiene.tienda;
    document.getElementById('em_user_id').value = id;
    new bootstrap.Modal(document.getElementById('modalEditarModulos')).show();
}
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formEditarModulos').addEventListener('submit', function() {
        document.getElementById('f_em_ordenes').disabled = !document.getElementById('em_ordenes').checked;
        document.getElementById('f_em_pos').disabled = !document.getElementById('em_pos').checked;
        document.getElementById('f_em_inv').disabled = !document.getElementById('em_inv').checked;
        document.getElementById('f_em_fin').disabled = !document.getElementById('em_fin').checked;
        document.getElementById('f_em_tienda').disabled = !document.getElementById('em_tienda').checked;
    });
});

function editarUsuario(id, usuario, nombre, rol) {
    document.getElementById('eu_user_id').value = id;
    document.getElementById('eu_usuario').value = usuario;
    document.getElementById('eu_nombre').value = nombre;
    document.getElementById('eu_rol').value = rol;
    new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
}

function resetPass(id, usuario) {
    const pass = prompt('Nueva contraseña para "' + usuario + '":');
    if (pass && pass.length >= 4) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.innerHTML = '<input name="action" value="reset_pass">'
            + '<input name="user_id" value="' + id + '">'
            + '<input name="nueva_pass" value="' + pass + '">';
        document.body.appendChild(f);
        f.submit();
    }
}
</script>
</body>
</html>
