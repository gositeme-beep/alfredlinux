/**
 * FREE VILLAGE NETWORK - INTERACTIVE MAP (HOMEPAGE)
 * 
 * GOAL: Show Quebec cities with merged village data
 * - Display all Quebec cities from quebecMunicipalities.js
 * - Merge village member data INTO cities (no separate village markers)
 * - Click cities to see details including villages in that city
 * - Clean, organized, working implementation
 */

(function() {
    'use strict';
    
    console.log('🗺️ HOMEPAGE MAP: Initializing...');

    // ===== CONFIGURATION =====
    const CONFIG = {
        bounds: { minLat: 45.0, maxLat: 51.0, minLng: -80.0, maxLng: -66.0 },
        zoom: { min: 0.5, max: 5, default: 1.2, step: 1.2 },
        cityRadius: { major: 12, medium: 8, small: 5, tiny: 3 }
    };

    // ===== STATE =====
    const map = {
        canvas: null,
        ctx: null,
        cities: [], // Only cities, with merged village data
        zoom: CONFIG.zoom.default,
        panX: 0,
        panY: 0,
        isDragging: false,
        dragStart: { x: 0, y: 0 },
        hasDragged: false,
        hovered: null,
        selected: null
    };

    // ===== COORDINATE CONVERSION =====
    function latLngToScreen(lat, lng) {
        const x = ((lng - CONFIG.bounds.minLng) / (CONFIG.bounds.maxLng - CONFIG.bounds.minLng)) * map.canvas.width * map.zoom + (map.panX - (map.canvas.width * map.zoom) / 2);
        const y = ((CONFIG.bounds.maxLat - lat) / (CONFIG.bounds.maxLat - CONFIG.bounds.minLat)) * map.canvas.height * map.zoom + (map.panY - (map.canvas.height * map.zoom) / 2);
        return { x, y };
    }

    function screenToLatLng(screenX, screenY) {
        const lng = ((screenX - (map.panX - (map.canvas.width * map.zoom) / 2)) / (map.canvas.width * map.zoom)) * (CONFIG.bounds.maxLng - CONFIG.bounds.minLng) + CONFIG.bounds.minLng;
        const lat = CONFIG.bounds.maxLat - ((screenY - (map.panY - (map.canvas.height * map.zoom) / 2)) / (map.canvas.height * map.zoom)) * (CONFIG.bounds.maxLat - CONFIG.bounds.minLat);
        return { lat, lng };
    }

    // ===== INITIALIZATION =====
    function init() {
        map.canvas = document.getElementById('homepageMapCanvas');
        if (!map.canvas) {
            console.error('🗺️ ERROR: Canvas not found');
            return;
        }

        map.ctx = map.canvas.getContext('2d');
        if (!map.ctx) {
            console.error('🗺️ ERROR: Context not found');
            return;
        }

        // Size canvas
        const container = document.getElementById('homepageInteractiveMap');
        if (container) {
            const rect = container.getBoundingClientRect();
            map.canvas.width = rect.width;
            map.canvas.height = rect.height;
            map.panX = map.canvas.width / 2;
            map.panY = map.canvas.height / 2;
            console.log('🗺️ Canvas sized:', map.canvas.width, 'x', map.canvas.height);
        }

        // Setup events
        setupEvents();

        // Setup controls
        setupControls();

        // Load data
        loadData();
        
        // Start animation loop
        animate();
        
        console.log('🗺️ Initialization complete');
    }

    function setupEvents() {
        // Mouse events
        map.canvas.addEventListener('mousedown', handleMouseDown);
        map.canvas.addEventListener('mousemove', handleMouseMove);
        map.canvas.addEventListener('mouseup', handleMouseUp);
        map.canvas.addEventListener('click', handleClick);
        map.canvas.addEventListener('wheel', handleWheel, { passive: false });
        
        // Touch events
        map.canvas.addEventListener('touchstart', handleTouchStart, { passive: false });
        map.canvas.addEventListener('touchmove', handleTouchMove, { passive: false });
        map.canvas.addEventListener('touchend', handleTouchEnd);
        
        // Resize
        window.addEventListener('resize', () => {
            const container = document.getElementById('homepageInteractiveMap');
            if (container) {
                const rect = container.getBoundingClientRect();
                map.canvas.width = rect.width;
                map.canvas.height = rect.height;
            }
        });
    }

    function setupControls() {
        // Zoom buttons
        const zoomIn = document.getElementById('mapZoomIn');
        const zoomOut = document.getElementById('mapZoomOut');
        if (zoomIn) zoomIn.onclick = () => { 
            map.zoom = Math.min(CONFIG.zoom.max, map.zoom * CONFIG.zoom.step); 
            draw(); 
        };
        if (zoomOut) zoomOut.onclick = () => { 
            map.zoom = Math.max(CONFIG.zoom.min, map.zoom / CONFIG.zoom.step); 
            draw(); 
        };

        // Close panel
        const closeBtn = document.getElementById('closeVillagePanel');
        if (closeBtn) {
            closeBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                closeDetailsPanel();
            };
        }
    }

    // ===== DATA LOADING =====
    function loadData() {
        // Wait for quebecMunicipalities to load
        let attempts = 0;
        function checkData() {
            attempts++;
            if (window.quebecMunicipalities && Object.keys(window.quebecMunicipalities).length > 0) {
                console.log('🗺️ Found quebecMunicipalities:', Object.keys(window.quebecMunicipalities).length);
                processCities();
            } else if (attempts < 50) {
                setTimeout(checkData, 100);
            } else {
                console.error('🗺️ ERROR: quebecMunicipalities not found after 5 seconds');
            }
        }
        checkData();
    }

    function processCities() {
        // Load villages from API
        fetch('/api/map')
            .then(res => res.json())
            .then(data => {
                const villagesData = data.villages || data.cities || [];
                console.log('🗺️ Loaded', villagesData.length, 'villages from API');
                
                // Create cities from quebecMunicipalities
                map.cities = [];
                
                for (const [cityName, cityData] of Object.entries(window.quebecMunicipalities)) {
                    const lat = parseFloat(cityData.lat);
                    const lng = parseFloat(cityData.lng);
                    
                    if (isNaN(lat) || isNaN(lng)) continue;
                    if (lat < CONFIG.bounds.minLat || lat > CONFIG.bounds.maxLat) continue;
                    if (lng < CONFIG.bounds.minLng || lng > CONFIG.bounds.maxLng) continue;
                    
                    const pop = parseInt(cityData.population) || 0;
                    
                    // Create city object
                    const city = {
                        name: cityName,
                        lat: lat,
                        lng: lng,
                        region: cityData.region || 'Quebec',
                        population: pop,
                        member_count: 0,
                        post_count: 0,
                        event_count: 0,
                        villages: [], // Villages in this city
                        type: 'city'
                    };
                    
                    // Calculate radius based on population
                    if (pop > 100000) {
                        city.radius = CONFIG.cityRadius.major;
                    } else if (pop > 50000) {
                        city.radius = CONFIG.cityRadius.medium;
                    } else if (pop > 10000) {
                        city.radius = CONFIG.cityRadius.small;
                    } else {
                        city.radius = CONFIG.cityRadius.tiny;
                    }
                    
                    map.cities.push(city);
                }
                
                console.log('🗺️ Created', map.cities.length, 'cities from quebecMunicipalities');
                
                // Merge village data into cities
                villagesData.forEach(village => {
                    const vLat = parseFloat(village.location_lat || village.lat);
                    const vLng = parseFloat(village.location_lng || village.lng);
                    
                    if (isNaN(vLat) || isNaN(vLng)) return;
                    if (vLat < CONFIG.bounds.minLat || vLat > CONFIG.bounds.maxLat) return;
                    if (vLng < CONFIG.bounds.minLng || vLng > CONFIG.bounds.maxLng) return;
                    
                    // Find nearest city (within 0.1 degrees ≈ 11km)
                    let nearest = null;
                    let minDist = Infinity;
                    
                    map.cities.forEach(city => {
                        const dist = Math.sqrt(Math.pow(city.lat - vLat, 2) + Math.pow(city.lng - vLng, 2));
                        if (dist < minDist && dist < 0.1) {
                            minDist = dist;
                            nearest = city;
                        }
                    });
                    
                    // Merge village into city
                    if (nearest) {
                        nearest.member_count += parseInt(village.member_count) || 0;
                        nearest.post_count += parseInt(village.post_count) || 0;
                        nearest.event_count += parseInt(village.event_count) || 0;
                        nearest.villages.push({
                            id: village.id,
                            name: village.name,
                            name_fr: village.name_fr,
                            slug: village.slug,
                            member_count: parseInt(village.member_count) || 0,
                            post_count: parseInt(village.post_count) || 0,
                            event_count: parseInt(village.event_count) || 0,
                            status: village.status
                        });
                        
                        // Increase city radius if it has members
                        if (nearest.member_count > 0) {
                            nearest.radius = Math.max(nearest.radius, nearest.radius + 2);
                        }
                    }
                });
                
                console.log('🗺️ Cities with villages:', map.cities.filter(c => c.villages.length > 0).length);
                console.log('🗺️ Cities with members:', map.cities.filter(c => c.member_count > 0).length);
                
                draw();
            })
            .catch(err => {
                console.error('🗺️ ERROR loading villages:', err);
                // Still show cities even if villages fail to load
                draw();
            });
    }

    // ===== MOUSE EVENTS =====
    function handleMouseDown(e) {
        const rect = map.canvas.getBoundingClientRect();
        map.isDragging = true;
        map.hasDragged = false;
        map.dragStart.x = e.clientX - rect.left;
        map.dragStart.y = e.clientY - rect.top;
        map.canvas.style.cursor = 'grabbing';
    }

    function handleMouseMove(e) {
        const rect = map.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        if (map.isDragging) {
            const dx = mouseX - map.dragStart.x;
            const dy = mouseY - map.dragStart.y;
            
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
                map.hasDragged = true;
            }
            
            map.panX += dx;
            map.panY += dy;
            map.dragStart.x = mouseX;
            map.dragStart.y = mouseY;
            draw();
        } else {
            // Check hover
            const city = getCityAtPosition(mouseX, mouseY);
            if (city !== map.hovered) {
                map.hovered = city;
                map.canvas.style.cursor = city ? 'pointer' : 'grab';
                draw();
            }
        }
    }

    function handleMouseUp() {
        map.isDragging = false;
        map.canvas.style.cursor = map.hovered ? 'pointer' : 'grab';
    }

    function handleClick(e) {
        if (map.hasDragged) {
            map.hasDragged = false;
            return;
        }
        
        const rect = map.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        const city = getCityAtPosition(mouseX, mouseY);
        if (city) {
            map.selected = city;
            showDetails(city);
            draw();
        }
    }

    function handleWheel(e) {
        e.preventDefault();
        const rect = map.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        const oldZoom = map.zoom;
        map.zoom *= e.deltaY > 0 ? 0.9 : 1.1;
        map.zoom = Math.max(CONFIG.zoom.min, Math.min(CONFIG.zoom.max, map.zoom));
        
        const zoomChange = map.zoom / oldZoom;
        map.panX = mouseX - (mouseX - map.panX) * zoomChange;
        map.panY = mouseY - (mouseY - map.panY) * zoomChange;
        
        draw();
    }

    // ===== TOUCH EVENTS =====
    function handleTouchStart(e) {
        e.preventDefault();
        if (e.touches.length === 1) {
            const touch = e.touches[0];
            const rect = map.canvas.getBoundingClientRect();
            map.isDragging = true;
            map.hasDragged = false;
            map.dragStart.x = touch.clientX - rect.left;
            map.dragStart.y = touch.clientY - rect.top;
        }
    }

    function handleTouchMove(e) {
        e.preventDefault();
        if (e.touches.length === 1 && map.isDragging) {
            const touch = e.touches[0];
            const rect = map.canvas.getBoundingClientRect();
            const touchX = touch.clientX - rect.left;
            const touchY = touch.clientY - rect.top;
            
            const dx = touchX - map.dragStart.x;
            const dy = touchY - map.dragStart.y;
            
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
                map.hasDragged = true;
            }
            
            map.panX += dx;
            map.panY += dy;
            map.dragStart.x = touchX;
            map.dragStart.y = touchY;
            draw();
        }
    }

    function handleTouchEnd(e) {
        e.preventDefault();
        if (!map.hasDragged && map.isDragging) {
            // It was a tap, not a drag
            const touch = e.changedTouches[0];
            const rect = map.canvas.getBoundingClientRect();
            const touchX = touch.clientX - rect.left;
            const touchY = touch.clientY - rect.top;
            
            const city = getCityAtPosition(touchX, touchY);
            if (city) {
                map.selected = city;
                showDetails(city);
                draw();
            }
        }
        map.isDragging = false;
        map.hasDragged = false;
    }

    // ===== CLICK DETECTION =====
    function getCityAtPosition(mouseX, mouseY) {
        // Check cities in reverse order (top to bottom)
        for (let i = map.cities.length - 1; i >= 0; i--) {
            const city = map.cities[i];
            const pos = latLngToScreen(city.lat, city.lng);
            const dist = Math.sqrt(Math.pow(mouseX - pos.x, 2) + Math.pow(mouseY - pos.y, 2));
            
            // Use radius + padding for click detection
            if (dist <= city.radius + 8) {
                return city;
            }
        }
        return null;
    }

    // ===== RENDERING =====
    function draw() {
        if (!map.ctx || !map.canvas || map.canvas.width === 0) return;

        const ctx = map.ctx;
        const w = map.canvas.width;
        const h = map.canvas.height;

        // Clear
        ctx.clearRect(0, 0, w, h);

        // Background
        const bg = getComputedStyle(document.documentElement).getPropertyValue('--color-bg-light').trim() || '#1a1a1a';
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, w, h);

        // Grid
        ctx.strokeStyle = 'rgba(212, 165, 116, 0.1)';
        ctx.lineWidth = 1;
        for (let x = 0; x < w; x += 50) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, h);
            ctx.stroke();
        }
        for (let y = 0; y < h; y += 50) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(w, y);
            ctx.stroke();
        }

        // Draw cities
        drawCities(ctx);
    }

    function drawCities(ctx) {
        map.cities.forEach(city => {
            const pos = latLngToScreen(city.lat, city.lng);
            
            // Skip if off-screen
            if (pos.x < -100 || pos.x > map.canvas.width + 100) return;
            if (pos.y < -100 || pos.y > map.canvas.height + 100) return;

            const isHovered = map.hovered === city;
            const isSelected = map.selected === city;
            const hasMembers = city.member_count > 0;
            const isMajor = city.population > 100000;
            const isMedium = city.population > 50000;
            
            const rad = isHovered ? city.radius * 1.15 : city.radius;
            const color = hasMembers ? '#d4a574' : (isMajor ? '#d4a574' : isMedium ? '#8bc34a' : '#6496c8');
            const alpha = isSelected ? 1 : isHovered ? 0.9 : (hasMembers ? 0.9 : 0.7);

            // Glow for cities with members or when hovered
            if (hasMembers || isHovered || isSelected) {
                const grad = ctx.createRadialGradient(pos.x, pos.y, 0, pos.x, pos.y, rad * 3);
                grad.addColorStop(0, 'rgba(212, 165, 116, 0.5)');
                grad.addColorStop(1, 'rgba(212, 165, 116, 0)');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, rad * 3, 0, Math.PI * 2);
                ctx.fill();
            }

            // Circle
            ctx.fillStyle = color;
            ctx.globalAlpha = alpha;
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, rad, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalAlpha = 1;

            // Stroke
            ctx.strokeStyle = color;
            ctx.lineWidth = hasMembers ? 3 : 2;
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, rad, 0, Math.PI * 2);
            ctx.stroke();

            // Member indicator (green dot)
            if (hasMembers) {
                ctx.fillStyle = '#10b981';
                ctx.beginPath();
                ctx.arc(pos.x + rad * 0.6, pos.y - rad * 0.6, rad * 0.3, 0, Math.PI * 2);
                ctx.fill();
            }

            // Label
            const showLabel = (isMajor || hasMembers || map.zoom > 1.8) && (isHovered || isSelected || hasMembers);
            if (showLabel) {
                const txt = city.name + (hasMembers ? ` (${city.member_count})` : '');
                ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
                ctx.font = (isSelected ? 'bold ' : '') + '12px sans-serif';
                const m = ctx.measureText(txt);
                ctx.fillRect(pos.x - m.width / 2 - 6, pos.y + rad + 2, m.width + 12, 18);
                
                ctx.fillStyle = color;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(txt, pos.x, pos.y + rad + 5);
            }
        });
    }

    function animate() {
        draw();
        requestAnimationFrame(animate);
    }

    // ===== DETAILS PANEL =====
    function showDetails(city) {
        console.log('🗺️ Showing details for:', city.name);
        const panel = document.getElementById('villageDetailsPanel');
        const content = document.getElementById('villageDetailsContent');
        
        if (!panel || !content) {
            console.error('🗺️ ERROR: Panel elements not found');
            return;
        }
        
        const lang = document.documentElement.lang || 'en';
        const isFr = lang === 'fr';
        const hasMembers = city.member_count > 0;
        
        content.innerHTML = `
            <h3>${city.name}</h3>
            <div style="margin: 1rem 0; color: var(--color-text-secondary);">
                <div style="margin: 0.5rem 0;">📍 ${city.region}</div>
                <div style="margin: 0.5rem 0;">👥 ${city.population.toLocaleString()} ${isFr ? 'habitants' : 'residents'}</div>
                ${hasMembers ? `
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                        <div style="margin: 0.5rem 0;"><strong>👥 ${city.member_count}</strong> ${isFr ? 'membres du réseau' : 'network members'}</div>
                        ${city.post_count > 0 ? `<div style="margin: 0.5rem 0;">💬 ${city.post_count} ${isFr ? 'publications' : 'posts'}</div>` : ''}
                        ${city.event_count > 0 ? `<div style="margin: 0.5rem 0;">📅 ${city.event_count} ${isFr ? 'événements' : 'events'}</div>` : ''}
                    </div>
                    ${city.villages && city.villages.length > 0 ? `
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                            <strong>${isFr ? 'Villages dans cette ville:' : 'Villages in this city:'}</strong>
                            ${city.villages.map(v => `
                                <div style="margin: 0.5rem 0;">
                                    <a href="/land/village/${v.slug}" style="color: var(--color-accent); text-decoration: none; font-weight: 600;">
                                        ${isFr && v.name_fr ? v.name_fr : v.name}
                                    </a>
                                    ${v.member_count > 0 ? ` <span style="color: var(--color-text-secondary);">(${v.member_count} ${isFr ? 'membres' : 'members'})</span>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                ` : `
                    <div style="margin-top: 1rem; color: var(--color-text-secondary);">${isFr ? 'Aucun village dans cette ville' : 'No villages in this city yet'}</div>
                `}
            </div>
            <div style="margin-top: 1.5rem;">
                <a href="/city?city=${encodeURIComponent(city.name)}" style="display: inline-block; padding: 0.75rem 1.5rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s;">
                    ${isFr ? 'Voir la page de la ville' : 'View City Page'} →
                </a>
            </div>
        `;
        
        // Clear inline styles and activate panel
        panel.style.display = '';
        panel.style.right = '';
        panel.style.visibility = '';
        panel.style.opacity = '';
        panel.classList.add('active');
        
        // Force reflow
        void panel.offsetWidth;
        
        console.log('🗺️ Panel activated');
    }

    function closeDetailsPanel() {
        const panel = document.getElementById('villageDetailsPanel');
        if (panel) {
            panel.classList.remove('active');
            setTimeout(() => {
                panel.style.visibility = 'hidden';
                panel.style.opacity = '0';
            }, 400);
            map.selected = null;
            draw();
        }
    }

    // ===== START =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(init, 200));
    } else {
        setTimeout(init, 200);
    }

})();
