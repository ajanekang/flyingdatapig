# Flying Data Pig

A 3D data globe for exploring public, openly-licensed geospatial datasets — built as a non-profit platform for community-contributed visualization of Open API data.

Live site: https://flyingdatapig.org

## What's here

A single-page PHP site that plots cached OpenStreetMap-derived datasets onto a 3D globe via [Globe.gl](https://globe.gl/). Current datasets:

- Costco Warehouses (Global)
- FedEx Locations (Global)
- Global Airports
- Global Convenience Stores (brand-tagged: 7-Eleven, FamilyMart, Lawson, GS25, Circle K, Wawa, etc.)
- Global Power Plants (filterable by source: solar, wind, hydro, nuclear, coal, gas, biomass, etc.)
- Global Universities
- H Mart Stores
- McDonald's Restaurants (Global)
- North America Food Banks, Pantries & Soup Kitchens
- Starbucks Cafes (Global)
- Target Stores
- Tesla Superchargers (Global)
- Trader Joe's Stores
- Walmart Stores (Global)

All data © OpenStreetMap contributors (ODbL).

## Stack

- **Backend:** PHP 8, rendered server-side. No framework.
- **Frontend:** Vanilla JavaScript + HTML, CSS under `assets/css/`. No bundler, no JS framework. Globe.gl loaded from unpkg.
- **Dev runtime:** Docker (`php:8.3-cli`). Host doesn't need PHP installed.

## Running locally

```sh
./run_server.sh   # http://127.0.0.1:38005
./run_debug.sh    # same port, display_errors on, opens browser
```

Both scripts free port 38005 before starting and `docker pull` the image on every run. Override the PHP image with `PHP_IMAGE=php:8.4-cli ./run_server.sh`.

## Layout

```
index.php                  # Single-page entry (Details / Data / Team / About panes)
api/data.php               # Serves cached GeoJSON: ?source=<id>[&download=1]
includes/sources.php       # Data-source registry
includes/team.php          # Team members
includes/data_cache.php    # Fetch + cache + lazy-refresh helpers
bin/refresh_data.php       # CLI: weekly refresh entry point (for cron)
bin/refresh.sh             # Docker wrapper around refresh_data.php
data/cache/                # Cached GeoJSON + meta (gitignored)
assets/css/, assets/js/    # Stylesheet, client scripts
```

## Data subsystem

Each source in `includes/sources.php` has an upstream URL, a TTL, and an optional `transform` function. The cache flow:

- **`bin/refresh_data.php`** iterates every source, sends conditional `If-None-Match` / `If-Modified-Since` headers, and atomically replaces the cached file when the body hash changes. Designed to run weekly under cron.
- **`api/data.php?source=<id>`** serves the cached file. When the cache is older than `ttl_days` and `lazy_refresh` is not `false`, it triggers an inline refresh under a non-blocking `flock` — one request does the fetch, the rest serve stale until it finishes.
- Cached payloads live at `data/cache/<id>.geojson` with meta at `data/cache/<id>.meta.json` (gitignored).
- `&download=1` sets `Content-Disposition: attachment`.

### Source options

Each entry in `includes/sources.php` may set:

- `transform` — function name in `data_cache.php` that converts the raw response to canonical GeoJSON. OSM/Overpass sources use `osm_to_geojson`.
- `timeout` — per-source curl timeout in seconds (default 60). Overpass needs ~240+.
- `lazy_refresh` — set `false` for slow upstreams so API requests never wait on the fetch. Refreshes then come exclusively from cron.
- `default_view` — `[lat, lng, altitude]` initial camera position when the dataset is selected.
- `group_property` — feature property used to build the legend / color groups (e.g. `social_facility`). `null` for single-color datasets.

### Weekly refresh (cron)

Run `bin/refresh.sh` weekly. Example host crontab:

```
0 4 * * 0 cd /path/to/flyingdatapig.org && ./bin/refresh.sh >> data/cache/refresh.log 2>&1
```

The lazy refresh in `api/data.php` is a backstop — cron is preferred so the first weekly visitor doesn't wait for the upstream fetch.

## Editing content

- **Add or change a data source:** edit `includes/sources.php`.
- **Add or change a team member:** edit `includes/team.php`.
- **Swap visualizations (hex bins, choropleth, arcs):** edit `assets/js/globe.js`. The page just provides an empty `#globe` div.

## Contributors

- **Jane Kang** — Founder (Northwood High School)
- **Chun Kang** — Advisor (Software Industry Expert)
- **Johnny Kang** — Technical Architect & Advisor (University of Illinois Urbana-Champaign)

The canonical list lives in `includes/team.php` and drives the site's Team panel — edit it there, not here.

## License

Map data © OpenStreetMap contributors, licensed under the [Open Database License (ODbL)](https://opendatacommons.org/licenses/odbl/).
