<?php
$team = require __DIR__ . '/includes/team.php';
$year = date('Y');
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
                <a href="#team">Team</a>
            </nav>
        </div>
    </header>

    <main>
        <section id="home" class="hero">
            <div class="container">
                <h1>Flying Data Pig</h1>
                <p class="tagline">
                    A non-profit platform providing public data visualization based on
                    Open APIs and JSON data collected by contributors.
                </p>
                <p class="established">Established 2024</p>
            </div>
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
            <p>&copy; <?= $year ?> Flying Data Pig</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
