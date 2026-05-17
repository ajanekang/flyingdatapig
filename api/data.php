<?php
declare(strict_types=1);

require __DIR__ . '/../includes/data_cache.php';

$sources = require __DIR__ . '/../includes/sources.php';

$id       = isset($_GET['source']) ? (string) $_GET['source'] : '';
$download = !empty($_GET['download']);

if (!isset($sources[$id])) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'error'     => 'unknown source',
        'available' => array_keys($sources),
    ]);
    exit;
}

$source = $sources[$id];
ensure_fresh($id, $source);

$paths = cache_paths($id);
if (!is_file($paths['data'])) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'no cached data yet — try again shortly']);
    exit;
}

$meta = read_meta($id) ?? [];

header('Content-Type: application/geo+json');
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');
if (!empty($meta['last_updated'])) {
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) $meta['last_updated']) . ' GMT');
}
if (!empty($meta['sha256'])) {
    header('ETag: "' . substr($meta['sha256'], 0, 16) . '"');
}
if ($download) {
    header('Content-Disposition: attachment; filename="' . $id . '.geojson"');
}
header('Content-Length: ' . filesize($paths['data']));
readfile($paths['data']);
