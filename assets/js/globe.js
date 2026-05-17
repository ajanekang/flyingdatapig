(function () {
    const container = document.getElementById('globe');
    if (!container || typeof Globe === 'undefined') return;

    // Palette for the most common social_facility values; any unseen type falls
    // back to the default accent color.
    const TYPE_COLORS = {
        food_bank:    '#ff7ab6',
        food_pantry:  '#7cc7ff',
        soup_kitchen: '#ffd166',
        clothing_bank:'#9af7c1',
    };
    const DEFAULT_COLOR = '#c8b6ff';

    let allFeatures = [];
    let visibleTypes = new Set();
    let searchQuery = '';
    let selectedFeature = null;
    let allLabels = [];
    let currentAltitude = 1.9;
    let countryFeatures = [];

    function applyPolygons() {
        world.polygonsData(countryFeatures);
    }

    const infoPanel  = document.getElementById('info-panel');
    const legendEl   = document.getElementById('legend-items');
    const searchEl   = document.getElementById('search');
    const resetEl    = document.getElementById('reset');
    const countEl    = document.getElementById('globe-count');
    const tabs       = document.querySelectorAll('.tab');
    const panes      = document.querySelectorAll('.panel-pane');

    function activateTab(name) {
        tabs.forEach((t) => {
            const on = t.dataset.tab === name;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panes.forEach((p) => {
            p.hidden = p.dataset.pane !== name;
        });
    }

    tabs.forEach((t) => {
        t.addEventListener('click', () => activateTab(t.dataset.tab));
    });

    const world = Globe()(container)
        .backgroundColor('rgba(0,0,0,0)')
        .showAtmosphere(true)
        .atmosphereColor('#3a8bd4')
        .atmosphereAltitude(0.18)
        .polygonCapColor(() => 'rgba(0, 0, 0, 0)')
        .polygonSideColor(() => 'rgba(0, 0, 0, 0)')
        .polygonStrokeColor(() => 'rgba(255, 255, 255, 0.28)')
        .polygonAltitude(0.005)
        .pathPoints((d) => d.geometry.coordinates)
        .pathPointLat((p) => p[1])
        .pathPointLng((p) => p[0])
        .pathPointAlt(0.005)
        .pathColor(() => ['rgba(255, 255, 255, 0.18)'])
        .pathStroke(0.4)
        .pathTransitionDuration(0)
        .labelsData([])
        .labelLat((d) => d.lat)
        .labelLng((d) => d.lng)
        .labelText((d) => d.name)
        .labelSize((d) => d.size)
        .labelDotRadius((d) => d.dot)
        .labelColor((d) => d.color)
        .labelAltitude(0.01)
        .labelResolution(2)
        // Tiny but non-zero altitude: visually reads as a flat disc on the
        // globe surface, but the cylinder still has enough thickness for
        // Globe.gl's raycaster to register hover and click events. With
        // altitude exactly 0 the geometry is degenerate and the picker
        // silently fails.
        .pointAltitude(0.002)
        .pointRadius(radiusForAltitude(1.9))
        .pointColor((d) => colorForType(d.properties && d.properties.social_facility))
        .pointLat((d) => d.geometry.coordinates[1])
        .pointLng((d) => d.geometry.coordinates[0])
        .pointLabel((d) => {
            const p = d.properties || {};
            const name = p.name || p['name:en'] || 'Unnamed';
            const kind = (p.social_facility || '').replace(/_/g, ' ');
            return `<div class="globe-tooltip"><b>${escapeHtml(name)}</b>${kind ? '<br>' + escapeHtml(capitalize(kind)) : ''}</div>`;
        })
        .onPointClick((d) => {
            selectedFeature = d;
            renderInfo(d);
            activateTab('details');
            const [lng, lat] = d.geometry.coordinates;
            world.pointOfView({ lat, lng, altitude: 0.7 }, 900);
        })
        .onGlobeClick(() => {
            selectedFeature = null;
            renderInfoEmpty();
        });

    const HOME_VIEW = { lat: 40.0, lng: -98.0, altitude: 1.9 };
    world.pointOfView(HOME_VIEW);

    // Stylized solid globe: mutate the default material so the sphere reads as
    // a deep blue object rather than the default white. The atmosphere shader
    // above gives the rim glow.
    const globeMat = world.globeMaterial();
    if (globeMat) {
        globeMat.color.set('#1e5a8a');
        if (globeMat.emissive) globeMat.emissive.set('#0d3b66');
        if ('emissiveIntensity' in globeMat) globeMat.emissiveIntensity = 0.5;
        if ('shininess' in globeMat) globeMat.shininess = 25;
        globeMat.transparent = false;
        globeMat.opacity = 1;
        globeMat.needsUpdate = true;
    }

    // Country outlines layered on top. Loaded from world-atlas TopoJSON
    // (~100KB) and converted to GeoJSON in the browser via topojson-client.
    // Country labels reuse the same dataset (centroid via bbox center).
    if (typeof topojson !== 'undefined') {
        fetch('https://unpkg.com/world-atlas@2/countries-110m.json')
            .then((r) => r.json())
            .then((topo) => {
                const fc = topojson.feature(topo, topo.objects.countries);
                const features = (fc.features || []).filter(
                    (f) => f.properties && f.properties.name !== 'Antarctica'
                );
                countryFeatures = features.map((f) => ({ ...f, _layer: 'country' }));
                applyPolygons();

                // Label only the ~40 largest countries (by bbox area) so the
                // world view doesn't draw 170 separate text meshes per frame.
                // Below altitude 0.55 the country layer hides anyway.
                const ranked = features
                    .map((f) => {
                        const bb = bboxOf(f);
                        return {
                            feature: f,
                            lng: (bb.minLng + bb.maxLng) / 2,
                            lat: (bb.minLat + bb.maxLat) / 2,
                            area: (bb.maxLng - bb.minLng) * (bb.maxLat - bb.minLat),
                        };
                    })
                    .sort((a, b) => b.area - a.area)
                    .slice(0, 40);

                ranked.forEach(({ feature, lat, lng }) => {
                    allLabels.push({
                        name:  feature.properties.name,
                        lat, lng,
                        size:  0.42,
                        dot:   0,
                        color: 'rgba(255, 255, 255, 0.55)',
                        type:  'country',
                        minAlt: 0.55,
                        maxAlt: 999,
                    });
                });
                world.labelsData(visibleLabelsForAltitude(currentAltitude));
            })
            .catch((err) => console.warn('Country borders unavailable:', err));
    }

    // State / province borders (Natural Earth admin_1 lines @ 110m, ~117KB,
    // 109 LineStrings). Rendered via Globe.gl paths so they hug the surface.
    fetch('https://cdn.jsdelivr.net/gh/nvkelso/natural-earth-vector/geojson/ne_110m_admin_1_states_provinces_lines.geojson')
        .then((r) => r.json())
        .then((geo) => {
            const lines = (geo.features || []).filter(
                (f) => f.geometry && f.geometry.type === 'LineString'
            );
            world.pathsData(lines);
        })
        .catch((err) => console.warn('State borders unavailable:', err));

    // Major cities (~243 entries, ~50KB) from Natural Earth via jsDelivr.
    fetch('https://cdn.jsdelivr.net/gh/nvkelso/natural-earth-vector/geojson/ne_110m_populated_places_simple.geojson')
        .then((r) => r.json())
        .then((geo) => {
            (geo.features || []).forEach((f) => {
                if (!f.geometry || !Array.isArray(f.geometry.coordinates)) return;
                const [lng, lat] = f.geometry.coordinates;
                allLabels.push({
                    name:  f.properties.name,
                    lat, lng,
                    size:  0.3,
                    dot:   0.18,
                    color: 'rgba(255, 255, 255, 0.7)',
                    type:  'city',
                    minAlt: 0,
                    maxAlt: 1.5,
                });
            });
            world.labelsData(visibleLabelsForAltitude(currentAltitude));
        })
        .catch((err) => console.warn('City labels unavailable:', err));

    // Keep point markers legible across zoom levels: scale radius with the
    // camera altitude (small dots when close, larger when far). Throttled to
    // one update per animation frame so a flick of the scroll wheel can't
    // queue dozens of re-renders. Also re-filters which place labels are
    // visible at the current altitude.
    let zoomRaf = null;
    world.onZoom((pov) => {
        currentAltitude = pov.altitude;
        if (zoomRaf !== null) return;
        zoomRaf = requestAnimationFrame(() => {
            zoomRaf = null;
            world.pointRadius(radiusForAltitude(currentAltitude));
            world.labelsData(visibleLabelsForAltitude(currentAltitude));
        });
    });

    if (resetEl) {
        resetEl.addEventListener('click', () => {
            searchQuery = '';
            if (searchEl) searchEl.value = '';
            visibleTypes = new Set(allFeatures.map((f) => f.properties.social_facility).filter(Boolean));
            renderLegend();
            applyFilter();
            selectedFeature = null;
            renderInfoEmpty();
            world.pointOfView(HOME_VIEW, 900);
        });
    }

    if (searchEl) {
        searchEl.addEventListener('input', () => {
            searchQuery = searchEl.value.trim().toLowerCase();
            applyFilter();
        });
    }

    fetch('api/data.php?source=na-food-banks')
        .then((r) => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then((geojson) => {
            allFeatures = (geojson.features || []).filter(
                (f) =>
                    f &&
                    f.geometry &&
                    Array.isArray(f.geometry.coordinates) &&
                    typeof f.geometry.coordinates[0] === 'number' &&
                    typeof f.geometry.coordinates[1] === 'number'
            );
            visibleTypes = new Set(allFeatures.map((f) => f.properties.social_facility).filter(Boolean));
            renderLegend();
            applyFilter();
            renderInfoEmpty();
        })
        .catch((err) => {
            console.error('Globe data load failed:', err);
            if (infoPanel) {
                infoPanel.innerHTML = `<div class="info-empty"><h2>Data unavailable</h2><p class="info-hint">${escapeHtml(err.message)}</p></div>`;
            }
        });

    window.addEventListener('resize', () => {
        world.width(container.clientWidth).height(container.clientHeight);
    });

    // === filtering ==========================================================

    function applyFilter() {
        const filtered = allFeatures.filter((f) => {
            const t = f.properties.social_facility;
            if (t && !visibleTypes.has(t)) return false;
            if (searchQuery) {
                const p = f.properties;
                const haystack = (
                    (p.name || '') + ' ' +
                    (p['name:en'] || '') + ' ' +
                    (p['addr:city'] || '') + ' ' +
                    (p['addr:state'] || '')
                ).toLowerCase();
                if (!haystack.includes(searchQuery)) return false;
            }
            return true;
        });
        world.pointsData(filtered);
        if (countEl) {
            countEl.textContent =
                filtered.length === allFeatures.length
                    ? `${allFeatures.length.toLocaleString()} locations`
                    : `${filtered.length.toLocaleString()} of ${allFeatures.length.toLocaleString()} locations`;
        }
        // Update per-type counts in the legend.
        document.querySelectorAll('.legend-item').forEach((el) => {
            const t = el.dataset.type;
            const c = filtered.filter((f) => f.properties.social_facility === t).length;
            const countSpan = el.querySelector('.legend-count');
            if (countSpan) countSpan.textContent = c.toLocaleString();
        });
    }

    // === legend =============================================================

    function renderLegend() {
        if (!legendEl) return;
        const counts = {};
        for (const f of allFeatures) {
            const t = f.properties.social_facility || 'unknown';
            counts[t] = (counts[t] || 0) + 1;
        }
        const types = Object.keys(counts).sort((a, b) => counts[b] - counts[a]);

        legendEl.innerHTML = types.map((t) => {
            const color = colorForType(t);
            const isOn = visibleTypes.has(t);
            return `
                <label class="legend-item ${isOn ? 'is-on' : 'is-off'}" data-type="${escapeAttr(t)}" style="color:${color}">
                    <input type="checkbox" ${isOn ? 'checked' : ''}>
                    <span class="legend-swatch"></span>
                    <span class="legend-label" style="color:${isOn ? 'rgba(255,255,255,0.85)' : 'rgba(255,255,255,0.4)'}">${escapeHtml(capitalize(t.replace(/_/g, ' ')))}</span>
                    <span class="legend-count">${counts[t].toLocaleString()}</span>
                </label>`;
        }).join('');

        legendEl.querySelectorAll('.legend-item').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const t = el.dataset.type;
                if (visibleTypes.has(t)) visibleTypes.delete(t);
                else visibleTypes.add(t);
                renderLegend();
                applyFilter();
            });
        });
    }

    // === info panel =========================================================

    function renderInfoEmpty() {
        if (!infoPanel) return;
        const total = allFeatures.length;
        infoPanel.innerHTML = `
            <div class="info-empty">
                <h2>North America Food Banks</h2>
                <p class="info-stats" id="globe-count">${total ? total.toLocaleString() + ' locations' : 'Loading…'}</p>
                <p class="info-hint">Click a point on the globe to see details about that location.</p>
                <p class="info-attribution">Data &copy; OpenStreetMap contributors (ODbL)</p>
            </div>`;
    }

    function renderInfo(d) {
        if (!infoPanel) return;
        const p = d.properties || {};
        const name = p.name || p['name:en'] || 'Unnamed location';
        const type = p.social_facility || '';
        const typeLabel = type ? capitalize(type.replace(/_/g, ' ')) : '';
        const typeColor = colorForType(type);

        const curated = [
            ['Address', formatAddress(p), false],
            ['Hours', p.opening_hours, false],
            ['Phone', p.phone ? phoneLink(p.phone) : null, true],
            ['Website', p.website ? webLink(p.website) : null, true],
            ['Email', p.email ? mailLink(p.email) : null, true],
            ['Operator', p.operator, false],
            ['Wheelchair', p.wheelchair, false],
        ];

        const handled = new Set([
            'name', 'name:en', 'social_facility', 'amenity',
            'opening_hours', 'phone', 'website', 'email',
            'operator', 'wheelchair',
            'osm_type', 'osm_id',
            'addr:housenumber', 'addr:street', 'addr:city', 'addr:state',
            'addr:province', 'addr:postcode', 'addr:country', 'addr:postbox',
        ]);
        const extras = Object.entries(p)
            .filter(([k, v]) => !handled.has(k) && v !== null && v !== '')
            .sort(([a], [b]) => a.localeCompare(b));

        const rows = [
            ...curated.map(([label, val, isHtml]) => row(label, val, isHtml)),
            ...extras.map(([k, v]) => row(k, String(v), false)),
        ].filter(Boolean).join('');

        const osmHref = p.osm_id && p.osm_type
            ? `https://www.openstreetmap.org/${escapeAttr(p.osm_type)}/${encodeURIComponent(p.osm_id)}`
            : null;

        infoPanel.innerHTML = `
            <div class="info-detail">
                <h2>${escapeHtml(name)}</h2>
                ${typeLabel ? `<span class="type-badge" style="background-color:${typeColor};color:${onColor(typeColor)}">${escapeHtml(typeLabel)}</span>` : ''}
                ${rows ? `<dl class="detail-grid">${rows}</dl>` : ''}
                ${osmHref ? `<a class="info-osm-link" href="${osmHref}" target="_blank" rel="noopener">View on OpenStreetMap &rarr;</a>` : ''}
            </div>`;
    }

    function row(label, value, isHtml) {
        if (value == null || value === '') return '';
        const v = isHtml ? value : escapeHtml(String(value));
        return `<dt>${escapeHtml(label)}</dt><dd>${v}</dd>`;
    }

    // === helpers ============================================================

    function colorForType(t) {
        return TYPE_COLORS[t] || DEFAULT_COLOR;
    }

    // Linear scale clamped to a sensible range. Globe.gl point radius is in
    // units of globe radius (~100), so 0.04 reads as a tiny dot and 0.3 reads
    // as a chunky marker. At the home altitude (~1.9) this gives ~0.14, which
    // matches the previous fixed radius.
    function radiusForAltitude(alt) {
        return Math.max(0.04, Math.min(0.3, alt * 0.075));
    }

    function visibleLabelsForAltitude(alt) {
        return allLabels.filter((l) => alt >= l.minAlt && alt <= l.maxAlt);
    }

    // Lon/lat bounding box of a Polygon/MultiPolygon feature. Imperfect for
    // countries spanning the antimeridian (Russia, Fiji) but good enough for
    // label placement and a "biggest countries first" ranking.
    function bboxOf(feature) {
        let minLng = Infinity, maxLng = -Infinity;
        let minLat = Infinity, maxLat = -Infinity;
        function walk(g) {
            if (typeof g[0] === 'number') {
                if (g[0] < minLng) minLng = g[0];
                if (g[0] > maxLng) maxLng = g[0];
                if (g[1] < minLat) minLat = g[1];
                if (g[1] > maxLat) maxLat = g[1];
            } else {
                g.forEach(walk);
            }
        }
        if (feature.geometry && Array.isArray(feature.geometry.coordinates)) {
            walk(feature.geometry.coordinates);
        }
        return { minLng, maxLng, minLat, maxLat };
    }

    // Pick black or white text for a given hex background based on luminance.
    function onColor(hex) {
        const m = /^#?([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i.exec(hex);
        if (!m) return '#fff';
        const [r, g, b] = [m[1], m[2], m[3]].map((c) => parseInt(c, 16) / 255);
        const lum = 0.299 * r + 0.587 * g + 0.114 * b;
        return lum > 0.6 ? '#1a0816' : '#fff';
    }

    function formatAddress(p) {
        const street = [p['addr:housenumber'], p['addr:street']].filter(Boolean).join(' ');
        const region = p['addr:state'] || p['addr:province'];
        return [street, p['addr:city'], region, p['addr:postcode'], p['addr:country']]
            .filter(Boolean)
            .join(', ');
    }

    function webLink(url) {
        const href = /^https?:\/\//i.test(url) ? url : 'https://' + url;
        return `<a href="${escapeAttr(href)}" target="_blank" rel="noopener">${escapeHtml(url)}</a>`;
    }
    function phoneLink(phone) {
        const tel = String(phone).replace(/[^+\d]/g, '');
        return `<a href="tel:${escapeAttr(tel)}">${escapeHtml(phone)}</a>`;
    }
    function mailLink(email) {
        return `<a href="mailto:${escapeAttr(email)}">${escapeHtml(email)}</a>`;
    }

    function capitalize(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[c]);
    }

    function escapeAttr(s) {
        return escapeHtml(s);
    }
})();
