/**
 * Quebec Regional Maps Page JavaScript
 */

(function() {
    'use strict';

    function initMapsPage() {
        const mapsGrid = document.getElementById('mapsGrid');
        if (!mapsGrid) return;

        // Get all maps
        const maps = window.getAllMaps ? window.getAllMaps() : [];
        const lang = document.documentElement.lang || 'en';

        if (maps.length === 0) {
            mapsGrid.innerHTML = `<p style="text-align: center; color: var(--color-text-secondary);">${lang === 'fr' ? 'Aucune carte disponible' : 'No maps available'}</p>`;
            return;
        }

        // Sort maps by region code
        maps.sort((a, b) => parseInt(a.code) - parseInt(b.code));

        // Generate map cards
        mapsGrid.innerHTML = maps.map(map => {
            const regionName = lang === 'fr' ? map.nameFr : map.name;
            const downloadText = lang === 'fr' ? 'Télécharger' : 'Download';
            const viewText = lang === 'fr' ? 'Voir' : 'View';
            const sizeText = lang === 'fr' ? 'Taille' : 'Size';

            return `
                <div class="map-card">
                    <div class="map-card-header">
                        <div class="map-region-code">${map.code}</div>
                        <div class="map-region-name">
                            <h3>${regionName}</h3>
                            <p>${lang === 'fr' ? 'Région Administrative' : 'Administrative Region'}</p>
                        </div>
                    </div>
                    <div class="map-card-body">
                        <div class="map-info">
                            <span class="map-info-label">${sizeText}:</span>
                            <span class="map-info-value">${map.size}</span>
                        </div>
                    </div>
                    <div class="map-card-footer">
                        <a href="${map.pdfUrl}" target="_blank" rel="noopener noreferrer" class="map-btn">
                            <span class="map-icon">📥</span>
                            ${downloadText}
                        </a>
                        <a href="${map.pdfUrl}" target="_blank" rel="noopener noreferrer" class="map-btn map-btn-secondary">
                            <span class="map-icon">👁️</span>
                            ${viewText}
                        </a>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMapsPage);
    } else {
        initMapsPage();
    }
})();

