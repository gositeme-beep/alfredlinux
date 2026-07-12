/**
 * Advanced Interactive Map - All 17 Quebec Regions
 * Shows all cities with interactive features
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
            default: 1.2,
            step: 1.3
        },
        cityRadius: {
            major: 12,      // > 100k
            medium: 8,      // > 50k
            small: 5,       // > 10k
            tiny: 3         // < 10k
        }
    };

    // State
    let state = {
        cities: [],
        villages: [], // Direct villages from database
        filteredCities: [],
        filteredVillages: [],
        selectedCity: null,
        selectedVillage: null,
        hoveredCity: null,
        hoveredVillage: null,
        zoom: CONFIG.zoom.default,
        panX: 0,
        panY: 0,
        isDragging: false,
        dragStart: { x: 0, y: 0 },
        searchQuery: '',
        regionFilter: 'all',
        regions: []
    };

    // Elements
    let canvas, ctx, container;
    let quebecMunicipalities = {};
    let quebecRegions = [];
    let regionalMaps = {};
    let villagesData = []; // Village data from database

    // Initialize
    function init() {
        container = document.getElementById('advancedInteractiveMap');
        if (!container) return;

        // Create canvas
        canvas = document.getElementById('mapCanvas');
        if (!canvas) return;

        ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Load data from database
        loadData();
        
        // Setup UI
        setupUI();
        setupEvents();
        
        // Start animation
        resize();
        animate();
    }

    async function loadData() {
        try {
            // Load village data from database API
            const res = await fetch('/api/map');
            const data = await res.json();
            villagesData = data.villages || data.cities || [];
            console.log('Loaded village data from database:', villagesData.length);
            console.log('Sample villages:', villagesData.slice(0, 5).map(v => v.name));
            
            // Get municipalities and regional maps
            quebecMunicipalities = window.quebecMunicipalities || {};
            quebecRegions = window.quebecRegions || [];
            regionalMaps = window.quebecRegionalMaps || {};

            // Process cities with database data
            processCities();
            populateRegionFilter();
        } catch (err) {
            console.error('Failed to load map data:', err);
            // Fallback to static data
            quebecMunicipalities = window.quebecMunicipalities || {};
            quebecRegions = window.quebecRegions || [];
            regionalMaps = window.quebecRegionalMaps || {};
            processCities();
            populateRegionFilter();
        }
    }

    function processCities() {
        const bounds = CONFIG.quebecBounds;
        
        // Create cities from quebecMunicipalities
        state.cities = Object.keys(quebecMunicipalities).map(cityName => {
            const data = quebecMunicipalities[cityName];
            return {
                name: cityName,
                lat: data.lat,
                lng: data.lng,
                region: data.region,
                population: data.population || 0,
                member_count: 0, // From database
                post_count: 0,   // From database
                event_count: 0,  // From database
                villages: [],     // Villages near this city
                x: 0,
                y: 0,
                radius: getCityRadius(data.population || 0)
            };
        }).filter(city => 
            city.lat >= bounds.minLat && city.lat <= bounds.maxLat &&
            city.lng >= bounds.minLng && city.lng <= bounds.maxLng
        );

        // Process villages from database - show them directly on the map
        state.villages = villagesData.map(village => {
            const vLat = parseFloat(village.location_lat);
            const vLng = parseFloat(village.location_lng);
            if (isNaN(vLat) || isNaN(vLng)) return null;
            
            return {
                id: village.id,
                name: village.name,
                name_fr: village.name_fr,
                slug: village.slug,
                lat: vLat,
                lng: vLng,
                region: village.region,
                member_count: village.member_count || 0,
                post_count: village.post_count || 0,
                event_count: village.event_count || 0,
                status: village.status,
                x: 0,
                y: 0,
                radius: Math.max(8, Math.min(20, 8 + (village.member_count || 0) * 0.5)) // Size based on members
            };
        }).filter(v => v !== null && 
            v.lat >= bounds.minLat && v.lat <= bounds.maxLat &&
            v.lng >= bounds.minLng && v.lng <= bounds.maxLng
        );

        // Also merge village data into cities for aggregated view
        villagesData.forEach(village => {
            if (!village.location_lat || !village.location_lng) return;
            const vLat = parseFloat(village.location_lat);
            const vLng = parseFloat(village.location_lng);
            if (isNaN(vLat) || isNaN(vLng)) return;

            // Find closest city (within 0.1 degrees ≈ 11km)
            let closest = null;
            let minDist = Infinity;
            state.cities.forEach(city => {
                const dist = Math.sqrt(Math.pow(city.lat - vLat, 2) + Math.pow(city.lng - vLng, 2));
                if (dist < minDist && dist < 0.1) {
                    minDist = dist;
                    closest = city;
                }
            });

            // Merge village member data into city
            if (closest) {
                closest.member_count += village.member_count || 0;
                closest.post_count += village.post_count || 0;
                closest.event_count += village.event_count || 0;
                closest.villages.push(village);
                // Update radius if city has members
                if (closest.member_count > 0) {
                    closest.radius = Math.max(closest.radius, getCityRadius(closest.population) + 2);
                }
            }
        });

        state.filteredCities = [...state.cities];
        state.filteredVillages = [...state.villages];
        updateCityPositions();
        updateVillagePositions();
        console.log('Processed cities:', state.cities.length);
        console.log('Processed villages from database:', state.villages.length);
        console.log('Cities with members:', state.cities.filter(c => c.member_count > 0).length);
    }

    function getCityRadius(population) {
        if (population > 100000) return CONFIG.cityRadius.major;
        if (population > 50000) return CONFIG.cityRadius.medium;
        if (population > 10000) return CONFIG.cityRadius.small;
        return CONFIG.cityRadius.tiny;
    }

    function populateRegionFilter() {
        const filter = document.getElementById('regionFilter');
        if (!filter) return;

        // Clear existing options except "All"
        while (filter.children.length > 1) {
            filter.removeChild(filter.lastChild);
        }

        // Add regions
        quebecRegions.forEach(region => {
            const option = document.createElement('option');
            option.value = region;
            option.textContent = region;
            filter.appendChild(option);
        });
    }

    function setupUI() {
        // Zoom controls
        const zoomIn = document.getElementById('zoomIn');
        const zoomOut = document.getElementById('zoomOut');
        const resetView = document.getElementById('resetView');
        const searchInput = document.getElementById('citySearch');
        const regionFilter = document.getElementById('regionFilter');
        const closePanel = document.getElementById('closePanel');

        if (zoomIn) zoomIn.addEventListener('click', () => zoom(1));
        if (zoomOut) zoomOut.addEventListener('click', () => zoom(-1));
        if (resetView) resetView.addEventListener('click', resetMapView);
        if (searchInput) searchInput.addEventListener('input', handleSearch);
        if (regionFilter) regionFilter.addEventListener('change', handleRegionFilter);
        if (closePanel) closePanel.addEventListener('click', closeCityPanel);
    }

    function setupEvents() {
        // Canvas events
        canvas.addEventListener('mousedown', handleMouseDown);
        canvas.addEventListener('mousemove', handleMouseMove);
        canvas.addEventListener('mouseup', handleMouseUp);
        canvas.addEventListener('wheel', handleWheel);
        canvas.addEventListener('click', handleClick);

        // Touch events
        canvas.addEventListener('touchstart', handleTouchStart);
        canvas.addEventListener('touchmove', handleTouchMove);
        canvas.addEventListener('touchend', handleTouchEnd);

        // Window resize
        window.addEventListener('resize', resize);
    }

    function resize() {
        if (!canvas || !container) return;
        const rect = container.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
        
        if (state.panX === 0 && state.panY === 0) {
            state.panX = canvas.width / 2;
            state.panY = canvas.height / 2;
        }
        
        updateCityPositions();
    }

    function updateCityPositions() {
        const bounds = CONFIG.quebecBounds;
        const width = canvas.width;
        const height = canvas.height;

        state.filteredCities.forEach(city => {
            city.x = ((city.lng - bounds.minLng) / (bounds.maxLng - bounds.minLng)) * width * state.zoom + (state.panX - (width * state.zoom) / 2);
            city.y = ((bounds.maxLat - city.lat) / (bounds.maxLat - bounds.minLat)) * height * state.zoom + (state.panY - (height * state.zoom) / 2);
        });
    }

    function updateVillagePositions() {
        const bounds = CONFIG.quebecBounds;
        const width = canvas.width;
        const height = canvas.height;

        state.filteredVillages.forEach(village => {
            village.x = ((village.lng - bounds.minLng) / (bounds.maxLng - bounds.minLng)) * width * state.zoom + (state.panX - (width * state.zoom) / 2);
            village.y = ((bounds.maxLat - village.lat) / (bounds.maxLat - bounds.minLat)) * height * state.zoom + (state.panY - (height * state.zoom) / 2);
        });
    }

    function zoom(direction) {
        const oldZoom = state.zoom;
        state.zoom = Math.max(CONFIG.zoom.min, Math.min(CONFIG.zoom.max, 
            state.zoom * (direction > 0 ? CONFIG.zoom.step : 1 / CONFIG.zoom.step)));
        
        // Adjust pan to zoom towards center
        const zoomFactor = state.zoom / oldZoom;
        state.panX = canvas.width / 2 - (canvas.width / 2 - state.panX) * zoomFactor;
        state.panY = canvas.height / 2 - (canvas.height / 2 - state.panY) * zoomFactor;
        
        updateCityPositions();
        updateVillagePositions();
    }

    function resetMapView() {
        state.zoom = CONFIG.zoom.default;
        state.panX = canvas.width / 2;
        state.panY = canvas.height / 2;
        updateCityPositions();
        updateVillagePositions();
    }

    function handleSearch(e) {
        state.searchQuery = e.target.value.toLowerCase();
        applyFilters();
    }

    function handleRegionFilter(e) {
        state.regionFilter = e.target.value;
        applyFilters();
    }

    function applyFilters() {
        state.filteredCities = state.cities.filter(city => {
            const matchesSearch = !state.searchQuery || 
                city.name.toLowerCase().includes(state.searchQuery) ||
                city.region.toLowerCase().includes(state.searchQuery);
            const matchesRegion = state.regionFilter === 'all' || city.region === state.regionFilter;
            return matchesSearch && matchesRegion;
        });
        state.filteredVillages = state.villages.filter(village => {
            const matchesSearch = !state.searchQuery || 
                village.name.toLowerCase().includes(state.searchQuery) ||
                (village.name_fr && village.name_fr.toLowerCase().includes(state.searchQuery)) ||
                village.region.toLowerCase().includes(state.searchQuery);
            const matchesRegion = state.regionFilter === 'all' || village.region === state.regionFilter;
            return matchesSearch && matchesRegion;
        });
        updateCityPositions();
        updateVillagePositions();
    }

    function handleMouseDown(e) {
        const rect = canvas.getBoundingClientRect();
        state.isDragging = true;
        state.dragStart.x = e.clientX - rect.left;
        state.dragStart.y = e.clientY - rect.top;
        canvas.style.cursor = 'grabbing';
    }

    function handleMouseMove(e) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        if (state.isDragging) {
            state.panX += x - state.dragStart.x;
            state.panY += y - state.dragStart.y;
            state.dragStart.x = x;
            state.dragStart.y = y;
            updateCityPositions();
            updateVillagePositions();
        } else {
            // Check hover - villages first (they're on top)
            const hoveredVillage = getVillageAtPosition(x, y);
            const hoveredCity = hoveredVillage ? null : getCityAtPosition(x, y);
            if (hoveredVillage !== state.hoveredVillage) {
                state.hoveredVillage = hoveredVillage;
            }
            if (hoveredCity !== state.hoveredCity) {
                state.hoveredCity = hoveredCity;
            }
        }
    }

    function handleMouseUp() {
        state.isDragging = false;
        canvas.style.cursor = 'grab';
    }

    function handleWheel(e) {
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const oldZoom = state.zoom;
        const zoomFactor = e.deltaY > 0 ? 1 / CONFIG.zoom.step : CONFIG.zoom.step;
        state.zoom = Math.max(CONFIG.zoom.min, Math.min(CONFIG.zoom.max, state.zoom * zoomFactor));
        
        // Zoom towards mouse position
        const zoomChange = state.zoom / oldZoom;
        state.panX = x - (x - state.panX) * zoomChange;
        state.panY = y - (y - state.panY) * zoomChange;
        
        updateCityPositions();
        updateVillagePositions();
    }

    function handleClick(e) {
        if (state.isDragging) return;
        
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Check villages first (they're on top)
        const village = getVillageAtPosition(x, y);
        if (village) {
            state.selectedVillage = village;
            state.selectedCity = null;
            showVillageDetails(village);
            return;
        }
        
        const city = getCityAtPosition(x, y);
        if (city) {
            state.selectedCity = city;
            state.selectedVillage = null;
            showCityDetails(city);
        }
    }

    function handleTouchStart(e) {
        if (e.touches.length === 1) {
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            state.isDragging = true;
            state.dragStart.x = touch.clientX - rect.left;
            state.dragStart.y = touch.clientY - rect.top;
        }
    }

    function handleTouchMove(e) {
        if (e.touches.length === 1 && state.isDragging) {
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;
            
            state.panX += x - state.dragStart.x;
            state.panY += y - state.dragStart.y;
            state.dragStart.x = x;
            state.dragStart.y = y;
            updateCityPositions();
        }
    }

    function handleTouchEnd() {
        state.isDragging = false;
    }

    function getCityAtPosition(x, y) {
        for (let i = state.filteredCities.length - 1; i >= 0; i--) {
            const city = state.filteredCities[i];
            const distance = Math.sqrt(Math.pow(x - city.x, 2) + Math.pow(y - city.y, 2));
            if (distance <= city.radius + 5) {
                return city;
            }
        }
        return null;
    }

    function getVillageAtPosition(x, y) {
        for (let i = state.filteredVillages.length - 1; i >= 0; i--) {
            const village = state.filteredVillages[i];
            const distance = Math.sqrt(Math.pow(x - village.x, 2) + Math.pow(y - village.y, 2));
            if (distance <= village.radius + 5) {
                return village;
            }
        }
        return null;
    }

    function showCityDetails(city) {
        const panel = document.getElementById('cityDetailsPanel');
        const content = document.getElementById('cityDetailsContent');
        if (!panel || !content) return;

        // Find regional map
        const regionMap = Object.values(regionalMaps).find(map => map.region === city.region);
        
        const lang = document.documentElement.lang || 'en';
        const isFr = lang === 'fr';
        const hasMembers = (city.member_count || 0) > 0;
        
        content.innerHTML = `
            <h3>${city.name}</h3>
            <div class="city-info">
                <div class="info-item">
                    <span class="info-label">${isFr ? 'Région' : 'Region'}:</span>
                    <span class="info-value">${city.region}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${isFr ? 'Population' : 'Population'}:</span>
                    <span class="info-value">${city.population.toLocaleString()}</span>
                </div>
                ${hasMembers ? `
                    <div class="info-item">
                        <span class="info-label">👥 ${isFr ? 'Membres' : 'Members'}:</span>
                        <span class="info-value">${city.member_count}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">💬 ${isFr ? 'Posts' : 'Posts'}:</span>
                        <span class="info-value">${city.post_count || 0}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">📅 ${isFr ? 'Événements' : 'Events'}:</span>
                        <span class="info-value">${city.event_count || 0}</span>
                    </div>
                ` : `
                    <div class="info-item">
                        <span class="info-label">${isFr ? 'Statut' : 'Status'}:</span>
                        <span class="info-value">${isFr ? 'Aucun membre' : 'No members yet'}</span>
                    </div>
                `}
                <div class="info-item">
                    <span class="info-label">${isFr ? 'Coordonnées' : 'Coordinates'}:</span>
                    <span class="info-value">${city.lat.toFixed(4)}, ${city.lng.toFixed(4)}</span>
                </div>
            </div>
            ${city.villages && city.villages.length > 0 ? `
                <div class="city-villages">
                    <h4>${isFr ? 'Villages dans cette ville:' : 'Villages in this city:'}</h4>
                    ${city.villages.map(v => `
                        <div style="margin: 0.5rem 0;">
                            <a href="/villages/village/${v.slug}" style="color: var(--color-accent);">
                                ${isFr && v.name_fr ? v.name_fr : v.name}
                            </a>
                        </div>
                    `).join('')}
                </div>
            ` : ''}
            ${regionMap ? `
                <div class="city-actions">
                    <a href="${regionMap.pdfUrl}" target="_blank" class="action-btn">
                        ${isFr ? '📥 Télécharger la Carte Régionale' : '📥 Download Regional Map'}
                    </a>
                </div>
            ` : ''}
            <div class="city-actions">
                <a href="/city?city=${encodeURIComponent(city.name)}" class="action-btn">
                    ${isFr ? 'Voir la page de la ville' : 'View City Page'} →
                </a>
            </div>
        `;
        
        panel.classList.add('active');
    }

    function showVillageDetails(village) {
        const panel = document.getElementById('cityDetailsPanel');
        const content = document.getElementById('cityDetailsContent');
        if (!panel || !content) return;
        
        const lang = document.documentElement.lang || 'en';
        const isFr = lang === 'fr';
        const villageName = (isFr && village.name_fr) ? village.name_fr : village.name;
        
        content.innerHTML = `
            <h3>${villageName}</h3>
            <div class="city-info">
                <div class="info-item">
                    <span class="info-label">${isFr ? 'Région' : 'Region'}:</span>
                    <span class="info-value">${village.region || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">👥 ${isFr ? 'Membres' : 'Members'}:</span>
                    <span class="info-value">${village.member_count || 0}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">💬 ${isFr ? 'Posts' : 'Posts'}:</span>
                    <span class="info-value">${village.post_count || 0}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">📅 ${isFr ? 'Événements' : 'Events'}:</span>
                    <span class="info-value">${village.event_count || 0}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${isFr ? 'Statut' : 'Status'}:</span>
                    <span class="info-value">${village.status || 'forming'}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${isFr ? 'Coordonnées' : 'Coordinates'}:</span>
                    <span class="info-value">${village.lat.toFixed(4)}, ${village.lng.toFixed(4)}</span>
                </div>
            </div>
            <div class="city-actions">
                <a href="/land/village/${village.slug}" class="action-btn">
                    ${isFr ? 'Voir le Village' : 'View Village'} →
                </a>
            </div>
        `;
        
        panel.classList.add('active');
    }

    function closeCityPanel() {
        const panel = document.getElementById('cityDetailsPanel');
        if (panel) {
            panel.classList.remove('active');
            state.selectedCity = null;
            state.selectedVillage = null;
        }
    }

    function draw() {
        if (!ctx || !canvas) return;

        // Clear
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw background
        const bgColor = getComputedStyle(document.documentElement).getPropertyValue('--color-bg-light').trim() || '#1a1a1a';
        ctx.fillStyle = bgColor;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Draw region boundaries (simplified)
        drawRegionBoundaries();

        // Draw cities
        drawCities();
        
        // Draw villages (on top of cities)
        drawVillages();

        // Draw connections for selected city
        if (state.selectedCity) {
            drawConnections(state.selectedCity);
        }
    }

    function drawRegionBoundaries() {
        // Simplified region visualization
        ctx.strokeStyle = 'rgba(212, 165, 116, 0.2)';
        ctx.lineWidth = 1;
        ctx.setLineDash([5, 5]);
        
        // Draw grid lines for visual reference
        const gridSize = 50;
        for (let x = 0; x < canvas.width; x += gridSize) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, canvas.height);
            ctx.stroke();
        }
        for (let y = 0; y < canvas.height; y += gridSize) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(canvas.width, y);
            ctx.stroke();
        }
        
        ctx.setLineDash([]);
    }

    function drawCities() {
        // Draw ALL cities from quebecMunicipalities - always show them
        state.cities.forEach(city => {
            const isHovered = state.hoveredCity === city;
            const isSelected = state.selectedCity === city;
            const isMajor = city.population > 100000;
            const isMedium = city.population > 50000;
            const hasMembers = (city.member_count || 0) > 0;

            // Glow effect for hovered/selected or cities with members
            if (isHovered || isSelected || hasMembers) {
                const gradient = ctx.createRadialGradient(
                    city.x, city.y, 0,
                    city.x, city.y, city.radius * 3
                );
                const glowColor = hasMembers ? '212, 165, 116' : '139, 195, 74';
                gradient.addColorStop(0, `rgba(${glowColor}, ${isSelected ? 0.6 : hasMembers ? 0.5 : 0.4})`);
                gradient.addColorStop(1, `rgba(${glowColor}, 0)`);
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(city.x, city.y, city.radius * 3, 0, Math.PI * 2);
                ctx.fill();
            }

            // City marker - gold for cities with members
            const color = hasMembers ? '212, 165, 116' : (isMajor ? '212, 165, 116' : isMedium ? '139, 195, 74' : '100, 150, 200');
            const opacity = isSelected ? 1 : isHovered ? 0.9 : (hasMembers ? 0.9 : 0.7);
            const radius = isSelected ? city.radius * 1.3 : isHovered ? city.radius * 1.1 : city.radius;

            ctx.fillStyle = `rgba(${color}, ${opacity})`;
            ctx.beginPath();
            ctx.arc(city.x, city.y, radius, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = `rgba(${color}, ${opacity + 0.2})`;
            ctx.lineWidth = (hasMembers || isSelected) ? 3 : 2;
            ctx.stroke();

            // Member indicator dot
            if (hasMembers) {
                ctx.fillStyle = '#10b981';
                ctx.beginPath();
                ctx.arc(city.x + radius * 0.6, city.y - radius * 0.6, radius * 0.3, 0, Math.PI * 2);
                ctx.fill();
            }

            // City label - show for major cities, cities with members, or when zoomed in enough
            const shouldShowLabel = (isMajor || hasMembers || state.zoom > 1.8) && (isHovered || isSelected || hasMembers || state.zoom > 1.5);
            if (shouldShowLabel) {
                const textColor = getComputedStyle(document.documentElement).getPropertyValue('--color-text').trim() || '#f5f5f5';
                const labelText = city.name + (hasMembers ? ` (${city.member_count})` : '');
                ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
                ctx.font = `${isSelected ? 'bold ' : ''}12px sans-serif`;
                const metrics = ctx.measureText(labelText);
                const pad = 6;
                ctx.fillRect(city.x - metrics.width / 2 - pad, city.y + radius + 2, metrics.width + pad * 2, 18);
                
                ctx.fillStyle = `rgba(${color}, 1)`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(labelText, city.x, city.y + radius + 5);
            }
        });
    }

    function drawVillages() {
        state.filteredVillages.forEach(village => {
            const isHovered = state.hoveredVillage === village;
            const isSelected = state.selectedVillage === village;
            const hasMembers = (village.member_count || 0) > 0;

            // Glow effect for hovered/selected villages
            if (isHovered || isSelected) {
                const gradient = ctx.createRadialGradient(
                    village.x, village.y, 0,
                    village.x, village.y, village.radius * 4
                );
                gradient.addColorStop(0, `rgba(212, 165, 116, ${isSelected ? 0.8 : 0.6})`);
                gradient.addColorStop(1, 'rgba(212, 165, 116, 0)');
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(village.x, village.y, village.radius * 4, 0, Math.PI * 2);
                ctx.fill();
            }

            // Village marker - gold color
            const color = '212, 165, 116';
            const opacity = isSelected ? 1 : isHovered ? 0.95 : (hasMembers ? 0.9 : 0.7);
            const radius = isSelected ? village.radius * 1.4 : isHovered ? village.radius * 1.2 : village.radius;

            // Outer ring for villages
            ctx.strokeStyle = `rgba(${color}, ${opacity + 0.3})`;
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(village.x, village.y, radius + 2, 0, Math.PI * 2);
            ctx.stroke();

            // Village circle
            ctx.fillStyle = `rgba(${color}, ${opacity})`;
            ctx.beginPath();
            ctx.arc(village.x, village.y, radius, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = `rgba(${color}, ${opacity + 0.2})`;
            ctx.lineWidth = 2;
            ctx.stroke();

            // Village label - always show for villages
            if (isHovered || isSelected || state.zoom > 1.5) {
                const textColor = getComputedStyle(document.documentElement).getPropertyValue('--color-text').trim() || '#f5f5f5';
                const labelText = village.name + (hasMembers ? ` (${village.member_count})` : '');
                ctx.fillStyle = 'rgba(0, 0, 0, 0.9)';
                ctx.font = `${isSelected ? 'bold ' : ''}13px sans-serif`;
                const metrics = ctx.measureText(labelText);
                const pad = 8;
                ctx.fillRect(village.x - metrics.width / 2 - pad, village.y + radius + 3, metrics.width + pad * 2, 20);
                
                ctx.fillStyle = `rgba(${color}, 1)`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(labelText, village.x, village.y + radius + 5);
            }
        });
    }

    function drawConnections(city) {
        // Draw lines to nearby cities in same region
        const nearbyCities = state.filteredCities.filter(c => 
            c !== city && 
            c.region === city.region &&
            Math.sqrt(Math.pow(c.x - city.x, 2) + Math.pow(c.y - city.y, 2)) < 200
        );

        ctx.strokeStyle = 'rgba(212, 165, 116, 0.3)';
        ctx.lineWidth = 1;
        ctx.setLineDash([3, 3]);

        nearbyCities.forEach(nearby => {
            ctx.beginPath();
            ctx.moveTo(city.x, city.y);
            ctx.lineTo(nearby.x, nearby.y);
            ctx.stroke();
        });

        ctx.setLineDash([]);
    }

    function animate() {
        draw();
        requestAnimationFrame(animate);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

