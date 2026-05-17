<?php
declare(strict_types=1);

const CACHE_DIR = __DIR__ . '/../data/cache';

function cache_paths(string $id): array
{
    $base = CACHE_DIR . '/' . $id;
    return [
        'data' => $base . '.geojson',
        'meta' => $base . '.meta.json',
        'lock' => $base . '.lock',
    ];
}

function read_meta(string $id): ?array
{
    $paths = cache_paths($id);
    if (!is_file($paths['meta'])) {
        return null;
    }
    $raw = @file_get_contents($paths['meta']);
    if ($raw === false) {
        return null;
    }
    $meta = json_decode($raw, true);
    return is_array($meta) ? $meta : null;
}

function write_meta(string $id, array $meta): void
{
    $paths = cache_paths($id);
    @mkdir(dirname($paths['meta']), 0775, true);
    $tmp = $paths['meta'] . '.tmp';
    file_put_contents($tmp, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    rename($tmp, $paths['meta']);
}

function is_stale(string $id, int $ttl_days): bool
{
    $paths = cache_paths($id);
    if (!is_file($paths['data'])) {
        return true;
    }
    $meta = read_meta($id);
    if (!$meta || !isset($meta['last_checked'])) {
        return true;
    }
    return (time() - (int) $meta['last_checked']) > $ttl_days * 86400;
}

function fetch_url(string $url, array $request_headers = [], int $timeout = 60): array
{
    $headers_out = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'flyingdatapig.org/1.0 (+https://flyingdatapig.org)',
        CURLOPT_HTTPHEADER     => $request_headers,
        CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$headers_out) {
            $line = trim($header);
            if ($line !== '' && strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                // Keep only the final response's headers (overwrite on each set).
                $headers_out[strtolower(trim($k))] = trim($v);
            }
            // Reset on each new HTTP status line so we keep the FINAL response's headers.
            if (stripos($line, 'HTTP/') === 0) {
                $headers_out = [];
            }
            return strlen($header);
        },
    ]);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    return [
        'status'  => $status,
        'headers' => $headers_out,
        'body'    => $body === false ? null : $body,
        'error'   => $error === '' ? null : $error,
    ];
}

function refresh_source(string $id, array $source): array
{
    $paths = cache_paths($id);
    @mkdir(dirname($paths['data']), 0775, true);

    $meta = read_meta($id) ?? [];
    $request_headers = [];
    if (!empty($meta['etag'])) {
        $request_headers[] = 'If-None-Match: ' . $meta['etag'];
    }
    if (!empty($meta['last_modified'])) {
        $request_headers[] = 'If-Modified-Since: ' . $meta['last_modified'];
    }

    $res = fetch_url($source['url'], $request_headers, (int) ($source['timeout'] ?? 60));
    $now = time();

    if ($res['status'] === 304) {
        $meta['last_checked'] = $now;
        unset($meta['last_error']);
        write_meta($id, $meta);
        return [
            'action' => 'not-modified',
            'bytes'  => is_file($paths['data']) ? filesize($paths['data']) : 0,
        ];
    }

    if ($res['status'] < 200 || $res['status'] >= 300 || $res['body'] === null) {
        $meta['last_checked'] = $now;
        $meta['last_error']   = $res['error'] ?: "HTTP {$res['status']}";
        write_meta($id, $meta);
        return ['action' => 'error', 'bytes' => 0, 'error' => $meta['last_error']];
    }

    $body = $res['body'];

    if (!empty($source['transform'])) {
        $transformer = $source['transform'];
        if (!is_callable($transformer)) {
            $meta['last_checked'] = $now;
            $meta['last_error']   = "transform '{$transformer}' is not callable";
            write_meta($id, $meta);
            return ['action' => 'error', 'bytes' => 0, 'error' => $meta['last_error']];
        }
        try {
            $body = $transformer($body);
        } catch (Throwable $e) {
            $meta['last_checked'] = $now;
            $meta['last_error']   = 'transform error: ' . $e->getMessage();
            write_meta($id, $meta);
            return ['action' => 'error', 'bytes' => 0, 'error' => $meta['last_error']];
        }
    }

    $hash    = hash('sha256', $body);
    $changed = ($meta['sha256'] ?? null) !== $hash;

    if ($changed) {
        $tmp = $paths['data'] . '.tmp';
        file_put_contents($tmp, $body);
        rename($tmp, $paths['data']);
    }

    $new_meta = [
        'id'            => $id,
        'url'           => $source['url'],
        'last_checked'  => $now,
        'last_updated'  => $changed ? $now : ($meta['last_updated'] ?? $now),
        'sha256'        => $hash,
        'bytes'         => strlen($body),
        'etag'          => $res['headers']['etag'] ?? null,
        'last_modified' => $res['headers']['last-modified'] ?? null,
    ];
    write_meta($id, $new_meta);

    return [
        'action' => $changed ? 'updated' : 'unchanged',
        'bytes'  => strlen($body),
    ];
}

// Converts an Overpass API JSON response (OSM nodes/ways/relations with
// `out center`) into a GeoJSON FeatureCollection of Point features.
function osm_to_geojson(string $body): string
{
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['elements'])) {
        throw new RuntimeException('Overpass response missing elements');
    }

    $features = [];
    foreach ($data['elements'] as $el) {
        $type = $el['type'] ?? '';
        if ($type === 'node') {
            $lat = $el['lat'] ?? null;
            $lon = $el['lon'] ?? null;
        } elseif (in_array($type, ['way', 'relation'], true)) {
            $lat = $el['center']['lat'] ?? null;
            $lon = $el['center']['lon'] ?? null;
        } else {
            continue;
        }
        if (!is_numeric($lat) || !is_numeric($lon)) {
            continue;
        }
        $features[] = [
            'type'       => 'Feature',
            'geometry'   => [
                'type'        => 'Point',
                'coordinates' => [(float) $lon, (float) $lat],
            ],
            'properties' => array_merge(
                ['osm_type' => $type, 'osm_id' => $el['id'] ?? null],
                $el['tags'] ?? []
            ),
        ];
    }

    return (string) json_encode([
        'type'     => 'FeatureCollection',
        'name'     => 'na_food_facilities',
        'features' => $features,
    ], JSON_UNESCAPED_SLASHES);
}

// Lazy refresh: triggered from the API on read. Uses a non-blocking flock so a
// thundering herd of requests doesn't all fetch — only one wins the lock, the
// rest serve stale data until that one finishes.
function ensure_fresh(string $id, array $source): void
{
    if (($source['lazy_refresh'] ?? true) === false) {
        return;
    }
    $ttl = (int) ($source['ttl_days'] ?? 7);
    if (!is_stale($id, $ttl)) {
        return;
    }

    $paths = cache_paths($id);
    @mkdir(dirname($paths['lock']), 0775, true);
    $lock = @fopen($paths['lock'], 'c');
    if (!$lock) {
        return;
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return;
    }
    try {
        if (is_stale($id, $ttl)) {
            refresh_source($id, $source);
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
