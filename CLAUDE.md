# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- Backend: PHP 8 (rendered server-side). Do not introduce Node, Laravel, or other frameworks without asking.
- Frontend: vanilla JavaScript + HTML, with CSS under `assets/css/`. No bundler, no JS framework.
- Dev runtime: Docker (`php:8.3-cli` image). The host does not need PHP installed — everything runs in the container.

## Layout

```
index.php                  # Single-page entry; renders Home / Features / Data / Explore / Team
api/data.php               # Serves cached GeoJSON: ?source=<id>[&download=1]
includes/team.php          # Team data — array of [name, role, affiliation]
includes/sources.php       # Data-source registry — id => [label, url, ttl_days, ...]
includes/data_cache.php    # Fetch + cache + lazy-refresh helpers
bin/refresh_data.php       # CLI: weekly refresh entry point (for cron)
bin/refresh.sh             # Docker wrapper around refresh_data.php
data/cache/                # Cached GeoJSON + meta (gitignored)
assets/css/                # Stylesheet
assets/js/                 # Client-side scripts
run_server.sh              # Dev server (Docker, port 38005)
run_debug.sh               # Debug server (same port, error flags, opens browser)
```

The site mirrors the live https://flyingdatapig.org single-page layout. Both the team list and data sources are data-driven — to add or change a member edit `includes/team.php`, to add or change a source edit `includes/sources.php`. Don't bake either into the markup.

## Running locally

- `./run_server.sh` — starts the Docker dev server on http://127.0.0.1:38005.
- `./run_debug.sh` — same port, but enables `display_errors`, `E_ALL`, and assertions, waits for the container to be ready, and opens the URL in your default browser.

Both scripts free port 38005 before starting: they remove any prior container with the matching name, stop any other Docker container publishing the port, and kill any host process listening on it. The PHP version is pinned via the `PHP_IMAGE` env var (default `php:8.3-cli`) and the image is `docker pull`ed on every run.

## Data subsystem

Each source in `includes/sources.php` has an `id`, upstream `url`, and `ttl_days`. Caching works as follows:

- `bin/refresh_data.php` iterates every source. For each, it sends `If-None-Match` / `If-Modified-Since` based on the saved meta. On 304, it just bumps `last_checked`. On 200, it hashes the body — if the hash differs it atomically replaces the cached file; otherwise it leaves the file alone.
- `api/data.php?source=<id>` serves the cached file. If the cache is older than `ttl_days`, it triggers an inline refresh under a non-blocking `flock` — only one concurrent request does the fetch, the rest serve stale until that one finishes. This is the lazy fallback in case cron didn't fire.
- Cached payloads live at `data/cache/<id>.geojson` with meta at `data/cache/<id>.meta.json`. Both are gitignored.
- The API also accepts `&download=1` to set `Content-Disposition: attachment`.

### Source options

Each source entry in `includes/sources.php` may set:
- `transform` — name of a function in `data_cache.php` that takes the raw response body and returns canonical GeoJSON. Used for the OSM/Overpass source via `osm_to_geojson` (converts OSM nodes/ways with `out center` to a `FeatureCollection` of Points).
- `timeout` — per-source curl timeout in seconds (default 60). Overpass needs ~240.
- `lazy_refresh` — set `false` for slow upstreams so an API request never waits on the fetch. Refreshes then come exclusively from cron.

### Weekly refresh (cron)

Run `bin/refresh.sh` (Docker wrapper) weekly. Example host crontab line:

```
0 4 * * 0 cd /path/to/flyingdatapig.org && ./bin/refresh.sh >> data/cache/refresh.log 2>&1
```

The lazy refresh in `api/data.php` makes cron a backstop, not a hard requirement — but cron is preferred so the first weekly visitor doesn't wait for the upstream fetch.

## Visualization

The Explore section renders cached GeoJSON on a 3D globe using [Globe.gl](https://globe.gl/) loaded from `unpkg.com` (no bundler). The init code is in `assets/js/globe.js`:

- Fetches `api/data.php?source=dc-food-bank` (which transparently lazy-refreshes if stale).
- Filters features whose `geometry.coordinates` aren't valid `[lng, lat]` numbers — the upstream dataset has some null-coordinate rows.
- Plots each location as a point colored with the site accent (`--color-accent`).

If you add more sources or want to swap visualizations (hex bins, choropleth, arcs), do it inside `globe.js` rather than the markup — the page just provides an empty `#globe` div.

## What does not exist yet

- No test suite, no lint config, no CI. Don't invent commands for these — ask if a feature requires them.
- No database. If one is added, document the connection setup here.
- No build step. Edits to PHP/CSS/JS are live on next request.
