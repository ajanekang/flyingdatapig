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

return [
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
];
