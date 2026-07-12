/**
 * Quebec Municipalities Reference Data
 * Major cities and regions with coordinates
 */

const quebecMunicipalities = {
    // Major Cities
    'Montreal': { lat: 45.5017, lng: -73.5673, region: 'Montreal', population: 1811008 },
    'Quebec City': { lat: 46.8139, lng: -71.2080, region: 'Capitale-Nationale', population: 548244 },
    'Laval': { lat: 45.6067, lng: -73.7122, region: 'Laval', population: 445050 },
    'Gatineau': { lat: 45.4765, lng: -75.7013, region: 'Outaouais', population: 285715 },
    'Longueuil': { lat: 45.5369, lng: -73.5103, region: 'Montérégie', population: 250425 },
    'Sherbrooke': { lat: 45.4000, lng: -71.9000, region: 'Estrie', population: 167180 },
    'Lévis': { lat: 46.8000, lng: -71.1833, region: 'Chaudière-Appalaches', population: 146235 },
    'Saguenay': { lat: 48.4167, lng: -71.0667, region: 'Saguenay–Lac-Saint-Jean', population: 141115 },
    'Trois-Rivières': { lat: 46.3500, lng: -72.5500, region: 'Mauricie', population: 133675 },
    'Terrebonne': { lat: 45.7000, lng: -73.6333, region: 'Lanaudière', population: 118045 },
    
    // Lanaudière Region (Key for the network)
    'Sainte-Émélie-de-l\'Énergie': { lat: 46.3167, lng: -73.6333, region: 'Lanaudière', population: 1700 },
    'Joliette': { lat: 46.0167, lng: -73.4500, region: 'Lanaudière', population: 20400 },
    'Repentigny': { lat: 45.7500, lng: -73.4500, region: 'Lanaudière', population: 86000 },
    'Mascouche': { lat: 45.6167, lng: -73.6000, region: 'Lanaudière', population: 47000 },
    'L\'Assomption': { lat: 45.8333, lng: -73.4167, region: 'Lanaudière', population: 22000 },
    'Rawdon': { lat: 46.0500, lng: -73.7167, region: 'Lanaudière', population: 11000 },
    'Saint-Lin-Laurentides': { lat: 45.8500, lng: -73.7667, region: 'Lanaudière', population: 18000 },
    'Berthierville': { lat: 46.0833, lng: -73.1833, region: 'Lanaudière', population: 4100 },
    'Saint-Gabriel': { lat: 46.3000, lng: -73.3833, region: 'Lanaudière', population: 3000 },
    'Notre-Dame-des-Prairies': { lat: 46.0167, lng: -73.4333, region: 'Lanaudière', population: 9000 },
    'Crabtree': { lat: 45.9667, lng: -73.4667, region: 'Lanaudière', population: 4000 },
    
    // Other Important Regions
    'Drummondville': { lat: 45.8833, lng: -72.4833, region: 'Centre-du-Québec', population: 79000 },
    'Granby': { lat: 45.4000, lng: -72.7333, region: 'Estrie', population: 69000 },
    'Saint-Jean-sur-Richelieu': { lat: 45.3167, lng: -73.2667, region: 'Montérégie', population: 97000 },
    'Brossard': { lat: 45.4500, lng: -73.4667, region: 'Montérégie', population: 89000 },
    'Saint-Jérôme': { lat: 45.7833, lng: -74.0000, region: 'Laurentides', population: 80000 },
    'Shawinigan': { lat: 46.5667, lng: -72.7500, region: 'Mauricie', population: 50000 },
    'Rimouski': { lat: 48.4500, lng: -68.5167, region: 'Bas-Saint-Laurent', population: 50000 },
    'Rouyn-Noranda': { lat: 48.2333, lng: -79.0167, region: 'Abitibi-Témiscamingue', population: 42000 },
    'Val-d\'Or': { lat: 48.1000, lng: -77.7833, region: 'Abitibi-Témiscamingue', population: 32000 },
    'Baie-Comeau': { lat: 49.2167, lng: -68.1500, region: 'Côte-Nord', population: 22000 },
    
    // Additional Regions
    'Victoriaville': { lat: 46.0500, lng: -71.9667, region: 'Centre-du-Québec', population: 47000 },
    'Sorel-Tracy': { lat: 46.0333, lng: -73.1167, region: 'Montérégie', population: 35000 },
    'Salaberry-de-Valleyfield': { lat: 45.2500, lng: -74.1333, region: 'Montérégie', population: 41000 },
    'Joliette': { lat: 46.0167, lng: -73.4500, region: 'Lanaudière', population: 20400 },
    'Magog': { lat: 45.2667, lng: -72.1500, region: 'Estrie', population: 26000 },
    'Cowansville': { lat: 45.2000, lng: -72.7500, region: 'Estrie', population: 14000 },
    'Thetford Mines': { lat: 46.0833, lng: -71.3000, region: 'Chaudière-Appalaches', population: 26000 },
    'Rivière-du-Loup': { lat: 47.8333, lng: -69.5333, region: 'Bas-Saint-Laurent', population: 20000 },
    'Matane': { lat: 48.8500, lng: -67.5333, region: 'Bas-Saint-Laurent', population: 14000 },
    'Amos': { lat: 48.5667, lng: -78.1167, region: 'Abitibi-Témiscamingue', population: 13000 },
    'La Tuque': { lat: 47.4333, lng: -72.7833, region: 'Mauricie', population: 11000 },
    'Sept-Îles': { lat: 50.2167, lng: -66.3833, region: 'Côte-Nord', population: 25000 },
    'Gaspé': { lat: 48.8333, lng: -64.4833, region: 'Gaspésie–Îles-de-la-Madeleine', population: 15000 },
    'Chicoutimi': { lat: 48.4167, lng: -71.0667, region: 'Saguenay–Lac-Saint-Jean', population: 66000 },
    'Alma': { lat: 48.5500, lng: -71.6500, region: 'Saguenay–Lac-Saint-Jean', population: 31000 },
    'Dolbeau-Mistassini': { lat: 48.8833, lng: -72.2333, region: 'Saguenay–Lac-Saint-Jean', population: 15000 }
};

// Quebec Regions
const quebecRegions = [
    'Bas-Saint-Laurent',
    'Saguenay–Lac-Saint-Jean',
    'Capitale-Nationale',
    'Mauricie',
    'Estrie',
    'Montreal',
    'Outaouais',
    'Abitibi-Témiscamingue',
    'Côte-Nord',
    'Nord-du-Québec',
    'Gaspésie–Îles-de-la-Madeleine',
    'Chaudière-Appalaches',
    'Laval',
    'Lanaudière',
    'Laurentides',
    'Montérégie',
    'Centre-du-Québec'
];

// Make available globally
window.quebecMunicipalities = quebecMunicipalities;
window.quebecRegions = quebecRegions;

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { quebecMunicipalities, quebecRegions };
}

