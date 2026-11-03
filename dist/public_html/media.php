<?php
/**
 * Générateur de miniatures : media.php?src=uploads/galerie/album/photo.jpg&w=480
 * La miniature est créée une seule fois puis servie directement depuis /cache/thumbs/.
 */
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$rel = Media::resolve((string) ($_GET['src'] ?? ''));
if (!$rel) {
    http_response_code(404);
    exit;
}
$file = Media::thumbnail($rel, (int) ($_GET['w'] ?? 960));
$type = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    default => 'image/jpeg',
};
header('Content-Type: ' . $type);
header('Content-Length: ' . filesize($file));
header('Cache-Control: public, max-age=2592000');
header('X-Content-Type-Options: nosniff');
readfile($file);
