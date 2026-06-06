<?php
// Dynamic PWA icon generator
// Usage: icon.php?size=192  (or 512)
// Reads logo_login.png and resizes on the fly

$size = max(48, min(1024, (int)($_GET['size'] ?? 192)));
$src = __DIR__ . '/logocel.png';

if (!file_exists($src)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    exit('Source image not found');
}

if (!function_exists('imagecreatefrompng')) {
    http_response_code(500);
    header('Content-Type: text/plain');
    exit('GD not available');
}

$etag = '"' . md5_file($src) . '-' . $size . '"';
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=3600');

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}

$img = imagecreatefrompng($src);
$w = imagesx($img);
$h = imagesy($img);

$out = imagecreatetruecolor($size, $size);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $size, $size, $transparent);
imagecopyresampled($out, $img, 0, 0, 0, 0, $size, $size, $w, $h);
imagedestroy($img);

header('Content-Type: image/png');
header('Content-Length: ' . strlen(imagepng($out, null, 9)));
imagepng($out, null, 9);
imagedestroy($out);
