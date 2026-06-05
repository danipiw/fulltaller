<?php
session_start();
$es_admin = isset($_SESSION['admin_id']);
session_destroy();
if ($es_admin) {
    header('Location: admin/index.php');
} else {
    header('Location: login.php');
}
exit;
