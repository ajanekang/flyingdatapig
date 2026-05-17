<?php
declare(strict_types=1);

// North America bounding box used in the Overpass query below.
// south=14, west=-170, north=84, east=-50 — covers Mexico through Alaska / northern Canada.
$overpass_query = '[out:json][timeout:180];'
    . '('
    . 'node["social_facility"~"food"](14.0,-170.0,84.0,-50.0);'
    . 'way["social_facility"~"food"](14.0,-170.0,84.0,-50.0);'
    . ');'
    . 'out center tags;';

return [
    'na-food-banks' => [
        'label'        => 'North America Food Banks, Pantries & Soup Kitchens',
        'description'  => 'Food distribution sites across North America — food banks, food pantries, and soup kitchens — from OpenStreetMap.',
        'url'          => 'https://overpass-api.de/api/interpreter?data=' . rawurlencode($overpass_query),
        'format'       => 'geojson',
        'ttl_days'     => 7,
        'attribution'  => '© OpenStreetMap contributors (ODbL)',
        'source_url'   => 'https://www.openstreetmap.org/about',
        'transform'    => 'osm_to_geojson',
        'timeout'      => 240,
        'lazy_refresh' => false,
    ],
];
