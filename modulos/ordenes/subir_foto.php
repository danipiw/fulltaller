<?php
include 'includes/verificar_sesion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['foto']) || !isset($_POST['orden_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Solicitud inválida']);
    exit;
}

$orden_id = (int)$_POST['orden_id'];
if ($orden_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

// Check max 3 fotos
$q = $conn->query("SELECT COUNT(*) AS cnt FROM fotos WHERE orden_id = $orden_id");
$row = $q->fetch_assoc();
if ((int)$row['cnt'] >= 3) {
    echo json_encode(['ok' => false, 'error' => 'Máximo 3 fotos por orden']);
    exit;
}

$archivo = $_FILES['foto'];
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Error al subir archivo']);
    exit;
}

$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['ok' => false, 'error' => 'Formato no permitido (jpg, png, webp)']);
    exit;
}

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$filename = 'orden_' . $orden_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
$destino = $dir . '/' . $filename;

if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar archivo']);
    exit;
}

// Redimensionar y comprimir
$maxDim = 1024;
$quality = 65;
$info = getimagesize($destino);
if ($info) {
    $srcW = $info[0];
    $srcH = $info[1];
    $ratio = min($maxDim / $srcW, $maxDim / $srcH, 1);
    $dstW = (int)round($srcW * $ratio);
    $dstH = (int)round($srcH * $ratio);
    if ($ratio < 1 || $ext !== 'jpg') {
        $src = null;
        if ($ext === 'jpeg' || $ext === 'jpg') $src = imagecreatefromjpeg($destino);
        elseif ($ext === 'png') $src = imagecreatefrompng($destino);
        elseif ($ext === 'webp') $src = imagecreatefromwebp($destino);
        if ($src) {
            $dst = imagecreatetruecolor($dstW, $dstH);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
            imagejpeg($dst, $destino, $quality);
            imagedestroy($dst);
            imagedestroy($src);
        }
    }
}

$stmt = $conn->prepare("INSERT INTO fotos (orden_id, filename) VALUES (?, ?)");
$stmt->bind_param('is', $orden_id, $filename);
$stmt->execute();

echo json_encode(['ok' => true, 'filename' => $filename]);
