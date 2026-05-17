<?php
$team    = require __DIR__ . '/includes/team.php';
$sources = require __DIR__ . '/includes/sources.php';
$year    = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flying Data Pig</title>
    <meta name="description" content="A non-profit platform providing public data visualization based on Open APIs and JSON data collected by contributors.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="#home" class="brand">Flying Data Pig</a>
            <nav class="site-nav">
                <a href="#home">Home</a>
                <a href="#features">Features</a>
                <a href="#data">Data</a>
                <a href="#team">Team</a>
            </nav>
        </div>
    </header>

    <main>
        <section id="home" class="map-app">
            <div class="globe-area">
                <div id="globe" class="globe"></div>
                <div class="title-overlay">
                    <h1>North America Food Banks</h1>
                    <p class="subtitle">Food banks, pantries &amp; soup kitchens — click any point for details.</p>
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
                        <p class="info-attribution">Data © OpenStreetMap contributors (ODbL)</p>
                    </div>
                </div>
            </aside>
        </section>

        <section id="features" class="features">
            <div class="container">
                <h2>Features</h2>
                <ul class="feature-list">
                    <li>
                        <h3>Interactive Visualizations</h3>
                        <p>Explore publicly available datasets through interactive charts and maps.</p>
                    </li>
                    <li>
                        <h3>Open APIs</h3>
                        <p>Access comprehensive public information through open, well-documented APIs.</p>
                    </li>
                    <li>
                        <h3>Community-Driven</h3>
                        <p>Built on contributions from a community committed to public data access.</p>
                    </li>
                </ul>
            </div>
        </section>

        <section id="data" class="data-sources">
            <div class="container">
                <h2>Data</h2>
                <p class="section-lede">Public datasets we mirror on our server. Updates are checked weekly.</p>
                <ul class="source-list">
                    <?php foreach ($sources as $id => $src): ?>
                        <li class="source">
                            <h3><?= htmlspecialchars($src['label']) ?></h3>
                            <?php if (!empty($src['description'])): ?>
                                <p class="description"><?= htmlspecialchars($src['description']) ?></p>
                            <?php endif; ?>
                            <p class="attribution">Source: <?= htmlspecialchars($src['attribution'] ?? 'Unknown') ?></p>
                            <p class="actions">
                                <a class="btn" href="api/data.php?source=<?= urlencode($id) ?>&download=1">Download GeoJSON</a>
                                <a class="link" href="api/data.php?source=<?= urlencode($id) ?>" target="_blank" rel="noopener">View raw</a>
                                <?php if (!empty($src['source_url'])): ?>
                                    <a class="link" href="<?= htmlspecialchars($src['source_url']) ?>" target="_blank" rel="noopener">Original source</a>
                                <?php endif; ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section id="team" class="team">
            <div class="container">
                <h2>Team</h2>
                <ul class="team-list">
                    <?php foreach ($team as $member): ?>
                        <li class="team-member">
                            <h3><?= htmlspecialchars($member['name']) ?></h3>
                            <p class="role"><?= htmlspecialchars($member['role']) ?></p>
                            <p class="affiliation"><?= htmlspecialchars($member['affiliation']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= $year ?> Flying Data Pig &nbsp;·&nbsp; Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap contributors</a></p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/globe.gl"></script>
    <script src="assets/js/globe.js" defer></script>
</body>
</html>
