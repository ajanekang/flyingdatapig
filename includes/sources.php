<?php
declare(strict_types=1);

// NA bounding box for food-bank Overpass query.
// south=14, west=-170, north=84, east=-50 — Mexico through Alaska/northern Canada.
$food_query = '[out:json][timeout:180];'
    . '('
    . 'node["social_facility"~"food"](14.0,-170.0,84.0,-50.0);'
    . 'way["social_facility"~"food"](14.0,-170.0,84.0,-50.0);'
    . ');'
    . 'out center tags;';

// Universities worldwide. Nodes only (~16k) — including ways would push
// the dataset to 50k+ features, much of which is just campus-building
// duplicates of node-tagged universities.
// `out;` (a.k.a. `out body;`) emits id + lat/lon + tags. `out tags;`
// strips the coordinates, which the GeoJSON transform requires — so
// don't use it for node datasets.
$uni_query = '[out:json][timeout:300];'
    . 'node["amenity"="university"];'
    . 'out;';

// Walmart stores worldwide. `nwr` matches nodes/ways/relations; the
// brand:wikidata clause catches stores tagged only by Wikidata ID. ~7,600
// features at last probe.
$walmart_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="Walmart"];'
    . 'nwr["brand:wikidata"="Q483551"];'
    . ');'
    . 'out center;';

// Costco warehouses worldwide. Same pattern as Walmart. ~2,300 features.
$costco_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="Costco"];'
    . 'nwr["brand:wikidata"="Q715583"];'
    . ');'
    . 'out center;';

// Starbucks cafes worldwide (Q37158). Tens of thousands of stores
// globally; we accept the larger payload for completeness.
$starbucks_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="Starbucks"];'
    . 'nwr["brand:wikidata"="Q37158"];'
    . ');'
    . 'out center;';

// Trader Joe's stores (Q688825). US-only chain; small dataset.
$traderjoes_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="Trader Joe\'s"];'
    . 'nwr["brand:wikidata"="Q688825"];'
    . ');'
    . 'out center;';

// McDonald's restaurants worldwide (Q38076). Largest of the bunch —
// expect ~30-40k features.
$mcdonalds_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="McDonald\'s"];'
    . 'nwr["brand:wikidata"="Q38076"];'
    . ');'
    . 'out center;';

// FedEx locations worldwide (Q459477). Includes drop-off points, Office
// (Kinko's) stores, and shipping centers tagged under the FedEx brand.
$fedex_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="FedEx"];'
    . 'nwr["brand:wikidata"="Q459477"];'
    . ');'
    . 'out center;';

// Airports and aerodromes worldwide. `aeroway=aerodrome` is the OSM
// canonical tag — it captures everything from international hubs to small
// regional fields. Expect tens of thousands of features.
$airports_query = '[out:json][timeout:300];'
    . '('
    . 'node["aeroway"="aerodrome"];'
    . 'way["aeroway"="aerodrome"];'
    . ');'
    . 'out center;';

// Power plants worldwide (power=plant). `plant:source` (solar, wind, hydro,
// nuclear, coal, gas, oil, biomass, geothermal, waste, …) drives the legend
// and per-type filtering. `nwr` covers nodes, ways, and relations.
$power_plants_query = '[out:json][timeout:300];'
    . 'nwr["power"="plant"];'
    . 'out center;';

// H Mart (Q5635873). Korean-American supermarket chain — mostly US and
// Canada, a few UK locations. ~100 stores last probe.
$hmart_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="H Mart"];'
    . 'nwr["brand:wikidata"="Q5635873"];'
    . ');'
    . 'out center;';

// Target Corporation (Q1046951). US-only big-box retailer, ~1,950 stores.
$target_query = '[out:json][timeout:300];'
    . '('
    . 'nwr["brand"="Target"];'
    . 'nwr["brand:wikidata"="Q1046951"];'
    . ');'
    . 'out center;';

// Convenience stores worldwide (shop=convenience). Restricted to features
// with a `brand` tag so the payload stays manageable and the dataset is
// dominated by recognizable chains (7-Eleven, FamilyMart, Lawson, GS25,
// Circle K, etc.) rather than untagged corner shops. Even so this is one
// of the larger datasets — expect 100k+ features.
$convenience_query = '[out:json][timeout:600];'
    . 'nwr["shop"="convenience"]["brand"];'
    . 'out center;';

return [
    'global-costco' => [
        'label'        => 'Costco Warehouses (Global)',
        'description'  => 'Costco warehouse locations worldwide from OpenStreetMap (brand=Costco or brand:wikidata=Q715583).',
        'subtitle'     => 'Costco warehouses worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($costco_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 360,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 35.0, 'lng' => -100.0, 'altitude' => 2.2],
        'group_property' => null,
    ],
    'global-fedex' => [
        'label'        => 'FedEx Locations (Global)',
        'description'  => 'FedEx shipping center, office, and drop-off locations worldwide from OpenStreetMap (brand=FedEx or brand:wikidata=Q459477).',
        'subtitle'     => 'FedEx locations worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($fedex_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 360,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 38.0, 'lng' => -90.0, 'altitude' => 2.2],
        'group_property' => null,
    ],
    'global-airports' => [
        'label'        => 'Global Airports',
        'description'  => 'Airports and aerodromes worldwide, tagged aeroway=aerodrome on OpenStreetMap — international hubs through regional fields.',
        'subtitle'     => 'Airports worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($airports_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 480,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 20.0, 'lng' => 10.0, 'altitude' => 2.4],
        'group_property' => null,
    ],
    'global-convenience-stores' => [
        'label'        => 'Global Convenience Stores',
        'description'  => 'Brand-tagged convenience stores worldwide from OpenStreetMap (shop=convenience with a brand tag). Captures international chains like 7-Eleven, FamilyMart, Lawson, GS25, Circle K, Wawa, and many more.',
        'subtitle'     => 'Convenience stores worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($convenience_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 600,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 20.0, 'lng' => 10.0, 'altitude' => 2.4],
        'group_property' => 'brand',
        'bin_top_n'      => 20,
    ],
    'global-power-plants' => [
        'label'        => 'Global Power Plants',
        'description'  => 'Power plants worldwide from OpenStreetMap (power=plant), grouped by plant:source — solar, wind, hydro, nuclear, coal, gas, biomass, geothermal, and more.',
        'subtitle'     => 'Power plants worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($power_plants_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 480,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 20.0, 'lng' => 10.0, 'altitude' => 2.4],
        'group_property' => 'plant:source',
    ],
    'global-universities' => [
        'label'        => 'Global Universities',
        'description'  => 'Universities and colleges worldwide, tagged amenity=university on OpenStreetMap.',
        'subtitle'     => 'Universities worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($uni_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 360,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 20.0, 'lng' => 10.0, 'altitude' => 2.4],
        'group_property' => null,
    ],
    'global-hmart' => [
        'label'        => 'H Mart Stores',
        'description'  => 'H Mart Korean-American supermarket locations from OpenStreetMap (brand=H Mart or brand:wikidata=Q5635873). US and Canada with a handful in the UK.',
        'subtitle'     => 'H Mart stores',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($hmart_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 240,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 38.0, 'lng' => -97.0, 'altitude' => 1.9],
        'group_property' => null,
    ],
    'global-mcdonalds' => [
        'label'        => "McDonald's Restaurants (Global)",
        'description'  => "McDonald's restaurant locations worldwide from OpenStreetMap (brand=McDonald's or brand:wikidata=Q38076).",
        'subtitle'     => "McDonald's restaurants worldwide",
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($mcdonalds_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 600,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 25.0, 'lng' => 0.0, 'altitude' => 2.4],
        'group_property' => null,
    ],
    'na-food-banks' => [
        'label'        => 'North America Food Banks, Pantries & Soup Kitchens',
        'description'  => 'Food distribution sites across North America — food banks, food pantries, and soup kitchens — from OpenStreetMap.',
        'subtitle'     => 'North America food banks, pantries & soup kitchens',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($food_query),
        'format'       => 'geojson',
        'ttl_days'     => 7,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 240,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 40.0, 'lng' => -98.0, 'altitude' => 1.9],
        'group_property' => 'social_facility',
    ],
    'global-starbucks' => [
        'label'        => 'Starbucks Cafes (Global)',
        'description'  => 'Starbucks cafe locations worldwide from OpenStreetMap (brand=Starbucks or brand:wikidata=Q37158).',
        'subtitle'     => 'Starbucks cafes worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($starbucks_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 480,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 25.0, 'lng' => 0.0, 'altitude' => 2.4],
        'group_property' => null,
    ],
    'global-target' => [
        'label'        => 'Target Stores',
        'description'  => 'Target store locations from OpenStreetMap (brand=Target or brand:wikidata=Q1046951). US-only big-box chain, ~1,950 stores.',
        'subtitle'     => 'Target stores',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($target_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 360,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 38.0, 'lng' => -97.0, 'altitude' => 1.9],
        'group_property' => null,
    ],
    'global-trader-joes' => [
        'label'        => "Trader Joe's Stores",
        'description'  => "Trader Joe's grocery store locations from OpenStreetMap (brand=Trader Joe's or brand:wikidata=Q688825). US-only chain.",
        'subtitle'     => "Trader Joe's stores",
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($traderjoes_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 240,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 38.0, 'lng' => -97.0, 'altitude' => 1.9],
        'group_property' => null,
    ],
    'global-walmart' => [
        'label'        => 'Walmart Stores (Global)',
        'description'  => 'Walmart store locations worldwide from OpenStreetMap (brand=Walmart or brand:wikidata=Q483551).',
        'subtitle'     => 'Walmart stores worldwide',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($walmart_query),
        'format'       => 'geojson',
        'ttl_days'     => 30,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 360,
        'lazy_refresh' => false,
        'default_view' => ['lat' => 30.0, 'lng' => -90.0, 'altitude' => 2.1],
        'group_property' => null,
    ],
];
