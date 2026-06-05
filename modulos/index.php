<?php
session_start();
if (isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
