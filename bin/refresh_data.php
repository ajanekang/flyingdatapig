#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../includes/data_cache.php';

$sources = require __DIR__ . '/../includes/sources.php';

$exit = 0;
foreach ($sources as $id => $source) {
    fwrite(STDOUT, "[{$id}] ");
    try {
        $result = refresh_source($id, $source);
        fwrite(STDOUT, sprintf("%s (%d bytes)\n", $result['action'], $result['bytes']));
        if ($result['action'] === 'error') {
            fwrite(STDERR, "  error: {$result['error']}\n");
            $exit = 1;
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "  exception: " . $e->getMessage() . "\n");
        $exit = 1;
    }
}
exit($exit);
