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

        <aside class="side-panel" aria-label="Side panel">
            <nav class="tab-bar" role="tablist">
                <button class="tab is-active" data-tab="details" type="button" role="tab" aria-selected="true">Details</button>
                <button class="tab" data-tab="data" type="button" role="tab" aria-selected="false">Data</button>
                <button class="tab" data-tab="team" type="button" role="tab" aria-selected="false">Team</button>
                <button class="tab" data-tab="about" type="button" role="tab" aria-selected="false">About</button>
            </nav>

            <div class="panel-pane" data-pane="details" role="tabpanel">
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
            </div>

            <div class="panel-pane" data-pane="data" role="tabpanel" hidden>
                <div class="panel-section">
                    <h3 class="panel-section-label">Data sources</h3>
                    <ul class="source-list">
                        <?php foreach ($sources as $id => $src): ?>
                            <li class="source">
                                <h4 class="source-name"><?= htmlspecialchars($src['label']) ?></h4>
                                <?php if (!empty($src['description'])): ?>
                                    <p class="source-description"><?= htmlspecialchars($src['description']) ?></p>
                                <?php endif; ?>
                                <p class="source-attribution"><?= htmlspecialchars($src['attribution'] ?? '') ?></p>
                                <p class="source-actions">
                                    <a class="panel-btn" href="api/data.php?source=<?= urlencode($id) ?>&amp;download=1">Download GeoJSON</a>
                                    <a class="panel-link" href="api/data.php?source=<?= urlencode($id) ?>" target="_blank" rel="noopener">View raw</a>
                                    <?php if (!empty($src['source_url'])): ?>
                                        <a class="panel-link" href="<?= htmlspecialchars($src['source_url']) ?>" target="_blank" rel="noopener">Original source</a>
                                    <?php endif; ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="panel-pane" data-pane="team" role="tabpanel" hidden>
                <div class="panel-section">
                    <h3 class="panel-section-label">Team</h3>
                    <ul class="team-list">
                        <?php foreach ($team as $member): ?>
                            <li class="team-member">
                                <h4 class="member-name"><?= htmlspecialchars($member['name']) ?></h4>
                                <p class="member-role"><?= htmlspecialchars($member['role']) ?></p>
                                <p class="member-affiliation"><?= htmlspecialchars($member['affiliation']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="panel-pane" data-pane="about" role="tabpanel" hidden>
                <div class="panel-section">
                    <h3 class="panel-section-label">About</h3>
                    <p class="about-text">
                        Flying Data Pig is a non-profit initiative — a platform providing
                        public data visualization based on Open APIs and JSON data
                        collected by contributors.
                    </p>
                    <p class="about-meta">Established 2024</p>
                    <p class="about-attribution">Map data &copy; OpenStreetMap contributors (ODbL)</p>
                </div>
            </div>
        </aside>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/globe.gl"></script>
    <script src="https://unpkg.com/topojson-client@3"></script>
    <script src="assets/js/globe.js" defer></script>
</body>
</html>
