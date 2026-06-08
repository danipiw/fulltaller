<?php
if (!empty($_SESSION['login_host']) && $_SESSION['login_host'] !== ($_SERVER['HTTP_HOST'] ?? '')) {
    session_destroy();
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Dominio no válido']);
    exit;
}

function verificarToken() {
    if (!isset($_SESSION['api_token'])) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token no generado']);
        exit;
    }

    $token_recibido = $_SERVER['HTTP_X_API_TOKEN'] ?? $_POST['api_token'] ?? '';

    if ($token_recibido !== $_SESSION['api_token']) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token inválido']);
        exit;
    }
}

function verificarAcceso() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarToken();
    }
}
