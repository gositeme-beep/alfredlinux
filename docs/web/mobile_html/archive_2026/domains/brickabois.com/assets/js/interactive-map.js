/**
 * Interactive Map - Clean, Simple, Working Implementation
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        quebecBounds: {
            minLat: 45.0,
            maxLat: 51.0,
            minLng: -80.0,
            maxLng: -66.0
        },
        zoom: {
            min: 0.5,
            max: 8,
            default: 1,
            step: 1.2
        }
    };

    // State
    let state = {
        cities: [],
        filteredCities: [],
        selectedCity: null,
        hoveredCity: null,
        zoom: CONFIG.zoom.default,
        panX: 0,
        panY: 0,
        isDragging: false,
        dragStart: { x: 0, y: 0 },
        searchQuery: '',
        regionFilter: 'all'
    };

    // Elements
    let canvas, ctx, container;
    let villagesData = [];
    let quebecMunicipalities = {};

    // Initialize
    function init() {
        container = document.getElementById('interactiveVillageMap');
        if (!container) return;

        // Create canvas
        canvas = document.createElement('canvas');
        canvas.id = 'villageMapCanvas';
        canvas.style.cssText = 'width: 100%; height: 100%; position: absolute; top: 0; left: 0; cursor: grab;';
        container.appendChild(canvas);
        ctx = canvas.getContext('2d');
        if (!ctx) return;

        resize();
        createUI();
        setupEvents();
        loadData();
        animate();
    }

    function resize() {
        if (!canvas || !container) return;
        const rect = container.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
        state.panX = canvas.width / 2;
        state.panY = canvas.height / 2;
    }

    function createUI() {
        // Controls
        const controls = document.createElement('div');
        controls.className = 'map-controls-ultimate';
        controls.innerHTML = `
            <div class="map-control-group-ultimate">
                <button id="zoomIn" class="map-btn-ultimate" title="Zoom In">+</button>
                <button id="zoomOut" class="map-btn-ultimate" title="Zoom Out">−</button>
                <button id="reset" class="map-btn-ultimate" title="Reset">⟲</button>
            </div>
            <div class="map-control-group-ultimate">
                <select id="regionFilter" class="map-filter-ultimate">
                    <option value="all">Toutes Régions</option>
                </select>
            </div>
        `;
        container.appendChild(controls);

        // Search
        const search = document.createElement('div');
        search.className = 'map-search-ultimate';
        search.innerHTML = `
            <input type="text" id="citySearch" class="map-search-input-ultimate" placeholder="Rechercher villes..." autocomplete="off">
        `;
        container.appendChild(search);

        // Info panel
        const panel = document.createElement('div');
        panel.id = 'cityInfoPanel';
        panel.className = 'village-info-panel-ultimate';
        panel.style.display = 'none';
        container.appendChild(panel);

        // Event listeners
        document.getElementById('zoomIn').onclick = () => {
            state.zoom = Math.min(CONFIG.zoom.max, state.zoom * CONFIG.zoom.step);
        };
        document.getElementById('zoomOut').onclick = () => {
            state.zoom = Math.max(CONFIG.zoom.min, state.zoom / CONFIG.zoom.step);
        };
        document.getElementById('reset').onclick = () => {
            state.zoom = CONFIG.zoom.default;
            state.panX = canvas.width / 2;
            state.panY = canvas.height / 2;
            state.selectedCity = null;
            hideInfo();
        };
        document.getElementById('regionFilter').onchange = (e) => {
            state.regionFilter = e.target.value;
            filter();
        };
        document.getElementById('citySearch').oninput = (e) => {
            state.searchQuery = e.target.value.toLowerCase().trim();
            filter();
        };
    }

    function setupEvents() {
        let dragStartX = 0, dragStartY = 0, dragStartPanX = 0, dragStartPanY = 0;
        let hasDragged = false;

        canvas.onmousedown = (e) => {
            if (e.target.closest('.map-controls-ultimate') || e.target.closest('.map-search-ultimate')) return;
            state.isDragging = true;
            hasDragged = false;
            dragStartX = e.clientX;
            dragStartY = e.clientY;
            dragStartPanX = state.panX;
            dragStartPanY = state.panY;
            canvas.style.cursor = 'grabbing';
        };

        canvas.onmousemove = (e) => {
            if (state.isDragging) {
                const dx = e.clientX - dragStartX;
                const dy = e.clientY - dragStartY;
                if (Math.abs(dx) > 3 || Math.abs(dy) > 3) hasDragged = true;
                state.panX = dragStartPanX + dx;
                state.panY = dragStartPanY + dy;
            } else {
                const rect = canvas.getBoundingClientRect();
                const x = (e.clientX - rect.left - state.panX) / state.zoom + canvas.width / 2;
                const y = (e.clientY - rect.top - state.panY) / state.zoom + canvas.height / 2;
                hover(x, y);
            }
        };

        canvas.onmouseup = () => {
            state.isDragging = false;
            canvas.style.cursor = 'grab';
        };

        canvas.onclick = (e) => {
            if (hasDragged || state.isDragging) return;
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX - rect.left - state.panX) / state.zoom + canvas.width / 2;
            const y = (e.clientY - rect.top - state.panY) / state.zoom + canvas.height / 2;
            click(x, y);
        };

        canvas.onwheel = (e) => {
            e.preventDefault();
            const factor = e.deltaY > 0 ? 0.9 : 1.1;
            state.zoom = Math.max(CONFIG.zoom.min, Math.min(CONFIG.zoom.max, state.zoom * factor));
        };

        window.onresize = resize;
    }

    async function loadData() {
        try {
            // Load village member data from API (for merging into cities)
            const res = await fetch('/api/map');
            const data = await res.json();
            villagesData = data.cities || data.villages || [];
            console.log('Loaded village member data:', villagesData.length, 'villages');
            
            // CRITICAL: We ONLY show cities from quebecMunicipalities.js, NOT villages
            // Wait for quebecMunicipalities to load
            if (window.quebecMunicipalities) {
                quebecMunicipalities = window.quebecMunicipalities;
                console.log('Found quebecMunicipalities, processing REAL Quebec cities...');
                processCities();
            } else {
                // Wait for it to load
                let attempts = 0;
                const checkMunicipalities = setInterval(() => {
                    attempts++;
                    if (window.quebecMunicipalities) {
                        clearInterval(checkMunicipalities);
                        quebecMunicipalities = window.quebecMunicipalities;
                        console.log('Found quebecMunicipalities, processing REAL Quebec cities...');
                        processCities();
                    } else if (attempts > 50) {
                        clearInterval(checkMunicipalities);
                        console.error('quebecMunicipalities not found after 5 seconds - cities will not display');
                    }
                }, 100);
            }
        } catch (err) {
            console.error('Failed to load map data:', err);
        }
    }

    function processCities() {
        if (!canvas || !quebecMunicipalities || Object.keys(quebecMunicipalities).length === 0) {
            console.log('Waiting for quebecMunicipalities...');
            setTimeout(processCities, 100);
            return;
        }

        console.log('Processing REAL Quebec cities from quebecMunicipalities...', Object.keys(quebecMunicipalities).length, 'municipalities');
        console.log('Village member data to merge:', villagesData.length);

        const bounds = CONFIG.quebecBounds;
        const width = canvas.width;
        const height = canvas.height;

        // IMPORTANT: Create cities ONLY from quebecMunicipalities.js - these are REAL Quebec city names
        // We do NOT display villages directly - we merge village member data INTO cities
        const cities = Object.keys(quebecMunicipalities).map(cityName => {
            const data = quebecMunicipalities[cityName];
            // Use REAL Quebec city names: "Montreal", "Quebec City", "Sainte-Émélie-de-l'Énergie", etc.
            return {
                name: cityName, // REAL city name from quebecMunicipalities
                lat: data.lat,
                lng: data.lng,
                region: data.region,
                population: data.population || 0,
                member_count: 0,
                post_count: 0,
                event_count: 0,
                villages: [], // Store villages that are near this city
                x: 0,
                y: 0
            };
        }).filter(city => 
            city.lat >= bounds.minLat && city.lat <= bounds.maxLat &&
            city.lng >= bounds.minLng && city.lng <= bounds.maxLng
        );

        console.log('REAL Quebec cities in bounds:', cities.length);
        console.log('Sample city names:', cities.slice(0, 5).map(c => c.name));

        // Merge village data
        villagesData.forEach(village => {
            if (!village.location_lat || !village.location_lng) return;
            const vLat = parseFloat(village.location_lat);
            const vLng = parseFloat(village.location_lng);
            if (isNaN(vLat) || isNaN(vLng)) return;

            let closest = null;
            let minDist = Infinity;
            cities.forEach(city => {
                const dist = Math.sqrt(Math.pow(city.lat - vLat, 2) + Math.pow(city.lng - vLng, 2));
                if (dist < minDist && dist < 0.1) {
                    minDist = dist;
                    closest = city;
                }
            });

            if (closest) {
                closest.member_count += village.member_count || 0;
                closest.post_count += village.post_count || 0;
                closest.event_count += village.event_count || 0;
                closest.villages.push(village);
            }
        });

        // Calculate screen coordinates
        cities.forEach(city => {
            city.x = ((city.lng - bounds.minLng) / (bounds.maxLng - bounds.minLng)) * width;
            city.y = ((bounds.maxLat - city.lat) / (bounds.maxLat - bounds.minLat)) * height;
        });

        state.cities = cities;
        state.filteredCities = cities;
        console.log('Processed', cities.length, 'cities');
        console.log('Cities with members:', cities.filter(c => c.member_count > 0).length);
        updateRegionFilter();
        filter();
    }

    function updateRegionFilter() {
        const select = document.getElementById('regionFilter');
        if (!select) return;
        
        const regions = [...new Set(state.cities.map(c => c.region).filter(Boolean))].sort();
        while (select.children.length > 1) select.removeChild(select.lastChild);
        
        regions.forEach(region => {
            const opt = document.createElement('option');
            opt.value = region;
            opt.textContent = region;
            select.appendChild(opt);
        });
    }

    function filter() {
        state.filteredCities = state.cities.filter(city => {
            if (state.searchQuery && !city.name.toLowerCase().includes(state.searchQuery) &&
                !(city.region && city.region.toLowerCase().includes(state.searchQuery))) {
                return false;
            }
            if (state.regionFilter !== 'all' && city.region !== state.regionFilter) {
                return false;
            }
            return true;
        });
    }

    function hover(worldX, worldY) {
        state.hoveredCity = null;
        let closest = null;
        let minDist = Infinity;

        state.filteredCities.forEach(city => {
            const dx = worldX - city.x;
            const dy = worldY - city.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const radius = getRadius(city) * state.zoom;
            if (dist < radius * 2 && dist < minDist) {
                minDist = dist;
                closest = city;
            }
        });

        state.hoveredCity = closest;
        canvas.style.cursor = closest ? 'pointer' : 'grab';
    }

    function click(worldX, worldY) {
        let closest = null;
        let minDist = Infinity;

        state.filteredCities.forEach(city => {
            const dx = worldX - city.x;
            const dy = worldY - city.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const radius = getRadius(city) * state.zoom;
            if (dist < radius * 2 && dist < minDist) {
                minDist = dist;
                closest = city;
            }
        });

        if (closest) {
            state.selectedCity = closest;
            showInfo(closest);
        } else {
            hideInfo();
        }
    }

    function getRadius(city) {
        const hasMembers = city.member_count > 0;
        if (hasMembers) return Math.max(8, Math.min(15, 6 + Math.log10(city.member_count + 1) * 2));
        if (city.population > 100000) return 6;
        if (city.population > 50000) return 5;
        if (city.population > 10000) return 4;
        return 3;
    }

    function draw() {
        if (!ctx || !canvas) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Background
        const grad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        grad.addColorStop(0, 'rgba(8, 8, 18, 0.98)');
        grad.addColorStop(1, 'rgba(18, 18, 32, 0.98)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Grid
        ctx.strokeStyle = 'rgba(212, 165, 116, 0.06)';
        ctx.lineWidth = 1;
        for (let x = 0; x < canvas.width; x += 50) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, canvas.height);
            ctx.stroke();
        }
        for (let y = 0; y < canvas.height; y += 50) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(canvas.width, y);
            ctx.stroke();
        }

        // Transform
        ctx.save();
        ctx.translate(state.panX, state.panY);
        ctx.scale(state.zoom, state.zoom);
        ctx.translate(-canvas.width / 2, -canvas.height / 2);

        // Draw cities
        if (state.filteredCities.length === 0) {
            ctx.fillStyle = '#fff';
            ctx.font = '16px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Loading cities...', canvas.width / 2, canvas.height / 2);
        }

        state.filteredCities.forEach(city => {
            if (!city.x || !city.y || isNaN(city.x) || isNaN(city.y)) {
                console.log('Skipping city with invalid coords:', city.name);
                return;
            }

            const isHovered = state.hoveredCity === city;
            const isSelected = state.selectedCity === city;
            const hasMembers = city.member_count > 0;
            const radius = getRadius(city);
            const scale = isHovered ? 1.4 : isSelected ? 1.2 : 1;
            const r = radius * scale;

            // Glow
            if (hasMembers || isHovered) {
                const glow = ctx.createRadialGradient(city.x, city.y, 0, city.x, city.y, r * 3);
                const color = hasMembers ? '212, 165, 116' : '139, 195, 74';
                glow.addColorStop(0, `rgba(${color}, 0.4)`);
                glow.addColorStop(1, `rgba(${color}, 0)`);
                ctx.fillStyle = glow;
                ctx.beginPath();
                ctx.arc(city.x, city.y, r * 3, 0, Math.PI * 2);
                ctx.fill();
            }

            // Circle
            const color = hasMembers ? '212, 165, 116' : '139, 195, 74';
            ctx.fillStyle = `rgba(${color}, ${hasMembers ? 0.9 : 0.7})`;
            ctx.beginPath();
            ctx.arc(city.x, city.y, r, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = `rgba(${color}, 1)`;
            ctx.lineWidth = hasMembers ? 3 : 2;
            ctx.stroke();

            // Member dot
            if (hasMembers) {
                ctx.fillStyle = '#10b981';
                ctx.beginPath();
                ctx.arc(city.x + r * 0.6, city.y - r * 0.6, r * 0.3, 0, Math.PI * 2);
                ctx.fill();
            }

            // Label
            if (isHovered || isSelected || (state.zoom > 1.5 && city.population > 10000) || city.population > 100000) {
                const lang = document.documentElement.lang || 'en';
                const text = city.name + (hasMembers ? ` (${city.member_count})` : '');
                ctx.font = `bold ${12 * Math.min(state.zoom, 1.5)}px sans-serif`;
                const metrics = ctx.measureText(text);
                const pad = 6;

                ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
                ctx.fillRect(city.x + r + pad, city.y - 12, metrics.width + pad * 2, 20);

                ctx.fillStyle = `rgba(${color}, 1)`;
                ctx.textAlign = 'left';
                ctx.fillText(text, city.x + r + pad * 1.5, city.y + 4);
            }
        });

        ctx.restore();

        // Stats
        const withMembers = state.filteredCities.filter(c => c.member_count > 0).length;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.75)';
        ctx.fillRect(10, canvas.height - 80, 200, 70);
        ctx.fillStyle = '#fff';
        ctx.font = '12px sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText(`Zoom: ${(state.zoom * 100).toFixed(0)}%`, 15, canvas.height - 60);
        ctx.fillText(`Cities: ${state.filteredCities.length}`, 15, canvas.height - 45);
        ctx.fillText(`With Members: ${withMembers}`, 15, canvas.height - 30);
        ctx.fillText(`Total: ${state.filteredCities.reduce((s, c) => s + c.member_count, 0)}`, 15, canvas.height - 15);
    }

    function showInfo(city) {
        const panel = document.getElementById('cityInfoPanel');
        if (!panel) return;
        
        const lang = document.documentElement.lang || 'en';
        panel.innerHTML = `
            <button onclick="this.parentElement.style.display='none'" style="float: right; background: none; border: none; color: var(--color-text); font-size: 1.5rem; cursor: pointer;">×</button>
            <h3>${city.name}</h3>
            <div style="margin: 1rem 0; color: var(--color-text-secondary);">
                ${city.region ? `<div>📍 ${city.region}</div>` : ''}
                ${city.population ? `<div>👥 ${city.population.toLocaleString()} ${lang === 'fr' ? 'habitants' : 'residents'}</div>` : ''}
            </div>
            ${city.member_count > 0 ? `
                <div style="display: flex; gap: 1rem; margin: 1rem 0;">
                    <div>👥 ${city.member_count} ${lang === 'fr' ? 'membres' : 'members'}</div>
                    <div>💬 ${city.post_count || 0} posts</div>
                    <div>📅 ${city.event_count || 0} ${lang === 'fr' ? 'événements' : 'events'}</div>
                </div>
                ${city.villages.length > 0 ? `
                    <div style="margin-top: 1rem;">
                        <strong>${lang === 'fr' ? 'Villages:' : 'Villages:'}</strong>
                        ${city.villages.map(v => `<div style="margin-top: 0.5rem;"><a href="/villages/village/${v.slug}" style="color: var(--color-accent);">${lang === 'fr' && v.name_fr ? v.name_fr : v.name}</a></div>`).join('')}
                    </div>
                ` : ''}
            ` : `<p style="color: var(--color-text-secondary); margin-top: 1rem;">${lang === 'fr' ? 'Aucun membre.' : 'No members yet.'}</p>`}
            <a href="/city?city=${encodeURIComponent(city.name)}" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: 8px;">${lang === 'fr' ? 'Voir la page' : 'View Page'} →</a>
        `;
        panel.style.display = 'block';
    }

    function hideInfo() {
        const panel = document.getElementById('cityInfoPanel');
        if (panel) panel.style.display = 'none';
    }

    function animate() {
        draw();
        requestAnimationFrame(animate);
    }

    // Start - ensure only this map initializes
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent other map scripts from running
            if (window.initUltimateMap) window.initUltimateMap = null;
            if (window.initPremiumMap) window.initPremiumMap = null;
            setTimeout(init, 200);
        });
    } else {
        // Prevent other map scripts from running
        if (window.initUltimateMap) window.initUltimateMap = null;
        if (window.initPremiumMap) window.initPremiumMap = null;
        setTimeout(init, 200);
    }

    // Export for manual control
    window.initInteractiveMap = init;

})();
