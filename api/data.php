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

$etag          = !empty($meta['sha256'])       ? '"' . substr($meta['sha256'], 0, 16) . '"' : null;
$last_modified = !empty($meta['last_updated']) ? gmdate('D, d M Y H:i:s', (int) $meta['last_updated']) . ' GMT' : null;

// Honor conditional GET so the browser's revalidation after Cache-Control
// max-age expires gets a cheap 304 instead of re-downloading the whole
// dataset. Skipped for ?download=1 (the user wants the bytes either way).
if (!$download) {
    $inm = trim($_SERVER['HTTP_IF_NONE_MATCH']     ?? '');
    $ims = trim($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
    if (($etag && $inm === $etag) || ($last_modified && $ims === $last_modified)) {
        http_response_code(304);
        if ($etag)          header('ETag: ' . $etag);
        if ($last_modified) header('Last-Modified: ' . $last_modified);
        header('Cache-Control: public, max-age=3600');
        exit;
    }
}

header('Content-Type: application/geo+json');
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');
header('Vary: Accept-Encoding');
if ($etag)          header('ETag: ' . $etag);
if ($last_modified) header('Last-Modified: ' . $last_modified);
if ($download) {
    header('Content-Disposition: attachment; filename="' . $id . '.geojson"');
}

// Serve the pre-gzipped variant when the client accepts gzip and we have
// it on disk. Downloads always go uncompressed so the saved .geojson file
// is usable as-is.
$accepts_gzip = !$download
    && stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false
    && is_file($paths['gz']);

if ($accepts_gzip) {
    header('Content-Encoding: gzip');
    header('Content-Length: ' . filesize($paths['gz']));
    readfile($paths['gz']);
} else {
    header('Content-Length: ' . filesize($paths['data']));
    readfile($paths['data']);
}
