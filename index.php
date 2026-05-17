<?php
$sources = require __DIR__ . '/includes/sources.php';
$team    = require __DIR__ . '/includes/team.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flying Data Pig — 3D Data Globe</title>
    <meta name="description" content="A non-profit platform providing public data visualization based on Open APIs and JSON data collected by contributors.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="map-app">
        <div class="globe-area">
            <div id="globe" class="globe"></div>
            <div class="title-overlay">
                <h1>Flying Data Pig</h1>
                <p class="subtitle">North America food banks, pantries &amp; soup kitchens</p>
            </div>
        </div>

        <aside class="side-panel" aria-label="Detail panel">
            <div class="panel-section panel-controls">
                <input
                    id="search"
                    type="search"
                    class="search-input"
                    placeholder="Search by name or city..."
                    autocomplete="off">
                <button id="reset" type="button" class="ghost-btn">Reset view</button>
            </div>

            <div class="panel-section panel-legend" id="legend">
                <h3 class="panel-section-label">Facility types</h3>
                <div class="legend-items" id="legend-items"></div>
            </div>

            <div class="panel-section panel-info" id="info-panel">
                <div class="info-empty">
                    <h2>North America Food Banks</h2>
                    <p class="info-stats" id="globe-count">Loading…</p>
                    <p class="info-hint">Click a point on the globe to see details about that location.</p>
                </div>
            </div>

            <div class="panel-section panel-footer">
                <?php foreach ($sources as $id => $src): ?>
                    <p class="panel-source-label">Data source</p>
                    <p class="panel-source-name"><?= htmlspecialchars($src['label']) ?></p>
                    <p class="panel-source-attr"><?= htmlspecialchars($src['attribution'] ?? '') ?></p>
                    <p class="panel-source-links">
                        <a class="panel-link" href="api/data.php?source=<?= urlencode($id) ?>&amp;download=1">Download GeoJSON</a>
                        <?php if (!empty($src['source_url'])): ?>
                            <a class="panel-link" href="<?= htmlspecialchars($src['source_url']) ?>" target="_blank" rel="noopener">Original</a>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>

                <p class="panel-team-label">Team</p>
                <p class="panel-team">
                    <?php foreach ($team as $i => $member): ?>
                        <?= htmlspecialchars($member['name']) ?> <span class="panel-team-role"><?= htmlspecialchars($member['role']) ?></span><?= $i < count($team) - 1 ? '<br>' : '' ?>
                    <?php endforeach; ?>
                </p>
            </div>
        </aside>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/globe.gl"></script>
    <script src="assets/js/globe.js" defer></script>
</body>
</html>
