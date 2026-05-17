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
        .globeImageUrl('//unpkg.com/three-globe/example/img/earth-night.jpg')
        .bumpImageUrl('//unpkg.com/three-globe/example/img/earth-topology.png')
        .backgroundColor('rgba(0,0,0,0)')
        .showAtmosphere(true)
        .atmosphereColor('#3a8bd4')
        .atmosphereAltitude(0.18)
        .pointAltitude(0.004)
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

    // Keep point markers legible across zoom levels: scale radius with the
    // camera altitude (small dots when close, larger when far). Throttled to
    // one update per animation frame so a flick of the scroll wheel can't
    // queue dozens of re-renders.
    let zoomRaf = null;
    world.onZoom((pov) => {
        if (zoomRaf !== null) return;
        zoomRaf = requestAnimationFrame(() => {
            zoomRaf = null;
            world.pointRadius(radiusForAltitude(pov.altitude));
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
