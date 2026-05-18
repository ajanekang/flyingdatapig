<?php
$sources = require __DIR__ . '/includes/sources.php';
$team    = require __DIR__ . '/includes/team.php';

// Honor ?source=<id> for shareable deep links. Validated against the
// registry; falls back to food banks if missing or unknown.
$selectedSource = (isset($_GET['source']) && is_string($_GET['source']) && isset($sources[$_GET['source']]))
    ? $_GET['source']
    : 'na-food-banks';

// Slim metadata for the frontend — drops the Overpass URL and other fields
// only the backend needs.
$sources_js = [];
foreach ($sources as $id => $s) {
    $sources_js[$id] = [
        'label'          => $s['label'],
        'subtitle'       => $s['subtitle']        ?? $s['label'],
        'default_view'   => $s['default_view']    ?? null,
        'group_property' => $s['group_property']  ?? null,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars('Flying Data Pig — ' . ($sources[$selectedSource]['label'] ?? '3D Data Globe')) ?></title>
    <meta name="description" content="A non-profit platform providing public data visualization based on Open APIs and JSON data collected by contributors.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!--
        Critical layout fallback. If assets/css/style.css fails to load
        (e.g. a production rewrite rule routes everything to index.php,
        or the file is served with the wrong MIME type), the side panel
        would otherwise block-flow below the globe and be clipped by the
        overflow:hidden on body. These rules keep the split layout
        working without the external sheet.
    -->
    <style>
        /* Aggressive layout fallback: anchor the side panel to the viewport
           with position:fixed so it appears even if every other CSS rule
           fails to load. !important defends against any conflicting
           external rules that load after this. */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%; height: 100%; overflow: hidden;
            background: #0a0a1a; color: #e0e0e0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .map-app { width: 100vw !important; height: 100vh !important; position: relative !important; background: #0a0a1a !important; display: block !important; }
        .globe-area {
            position: absolute !important;
            top: 0 !important; left: 0 !important;
            right: 0 !important; bottom: 50vh !important;
            background: #05060f !important;
            flex: none !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }
        #globe { width: 100%; height: 100%; overflow: hidden; }
        .side-panel {
            position: fixed !important;
            top: 50vh !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: #0f0f23 !important;
            border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
            overflow-y: auto !important;
            display: flex !important;
            flex-direction: column !important;
            z-index: 100 !important;
        }
        @media (min-width: 768px) {
            .globe-area { right: 340px !important; bottom: 0 !important; }
            .side-panel {
                top: 0 !important;
                left: auto !important;
                width: 340px !important;
                border-top: 0 !important;
                border-left: 1px solid rgba(255, 255, 255, 0.05) !important;
            }
        }
        @media (min-width: 1024px) {
            .globe-area { right: 380px !important; }
            .side-panel { width: 380px !important; }
        }
    </style>
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
                    <label class="control-label" for="dataset">Dataset</label>
                    <select id="dataset" class="select-input">
                        <?php foreach ($sources as $id => $src): ?>
                            <option value="<?= htmlspecialchars($id) ?>"<?= $id === $selectedSource ? ' selected' : '' ?>><?= htmlspecialchars($src['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
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

    <script>window.__SOURCES = <?= json_encode($sources_js, JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/globe.gl"></script>
    <script src="https://unpkg.com/topojson-client@3"></script>
    <script src="assets/js/globe.js" defer></script>
</body>
</html>
