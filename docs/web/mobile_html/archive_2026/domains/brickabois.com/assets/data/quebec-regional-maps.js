/**
 * Quebec Regional Maps - Official Government Cartography
 * Source: Ministère des Affaires municipales et de l'Habitation
 */

const quebecRegionalMaps = {
    '01': {
        code: '01',
        name: 'Bas-Saint-Laurent',
        nameFr: 'Bas-Saint-Laurent',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/01.pdf',
        size: '3 Mo',
        region: 'Bas-Saint-Laurent'
    },
    '02': {
        code: '02',
        name: 'Saguenay–Lac-Saint-Jean',
        nameFr: 'Saguenay–Lac-Saint-Jean',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/02.pdf',
        size: '3 Mo',
        region: 'Saguenay–Lac-Saint-Jean'
    },
    '03': {
        code: '03',
        name: 'Capitale-Nationale',
        nameFr: 'Capitale-Nationale',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/03.pdf',
        size: '2 Mo',
        region: 'Capitale-Nationale'
    },
    '04': {
        code: '04',
        name: 'Mauricie',
        nameFr: 'Mauricie',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/04.pdf',
        size: '2 Mo',
        region: 'Mauricie'
    },
    '05': {
        code: '05',
        name: 'Estrie',
        nameFr: 'Estrie',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/05.pdf',
        size: '2 Mo',
        region: 'Estrie'
    },
    '06': {
        code: '06',
        name: 'Montreal',
        nameFr: 'Montréal',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/06.pdf',
        size: '1 Mo',
        region: 'Montreal'
    },
    '07': {
        code: '07',
        name: 'Outaouais',
        nameFr: 'Outaouais',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/07.pdf',
        size: '2 Mo',
        region: 'Outaouais'
    },
    '08': {
        code: '08',
        name: 'Abitibi-Témiscamingue',
        nameFr: 'Abitibi-Témiscamingue',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/08.pdf',
        size: '2 Mo',
        region: 'Abitibi-Témiscamingue'
    },
    '09': {
        code: '09',
        name: 'Côte-Nord',
        nameFr: 'Côte-Nord',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/09.pdf',
        size: '5 Mo',
        region: 'Côte-Nord'
    },
    '10': {
        code: '10',
        name: 'Nord-du-Québec',
        nameFr: 'Nord-du-Québec',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/10.pdf',
        size: '5 Mo',
        region: 'Nord-du-Québec'
    },
    '11': {
        code: '11',
        name: 'Gaspésie–Îles-de-la-Madeleine',
        nameFr: 'Gaspésie–Îles-de-la-Madeleine',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/11.pdf',
        size: '2 Mo',
        region: 'Gaspésie–Îles-de-la-Madeleine'
    },
    '12': {
        code: '12',
        name: 'Chaudière-Appalaches',
        nameFr: 'Chaudière-Appalaches',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/12.pdf',
        size: '3 Mo',
        region: 'Chaudière-Appalaches'
    },
    '13': {
        code: '13',
        name: 'Laval',
        nameFr: 'Laval',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/13.pdf',
        size: '1 Mo',
        region: 'Laval'
    },
    '14': {
        code: '14',
        name: 'Lanaudière',
        nameFr: 'Lanaudière',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/14.pdf',
        size: '2 Mo',
        region: 'Lanaudière'
    },
    '15': {
        code: '15',
        name: 'Laurentides',
        nameFr: 'Laurentides',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/15.pdf',
        size: '2 Mo',
        region: 'Laurentides'
    },
    '16': {
        code: '16',
        name: 'Montérégie',
        nameFr: 'Montérégie',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/16.pdf',
        size: '3 Mo',
        region: 'Montérégie'
    },
    '17': {
        code: '17',
        name: 'Centre-du-Québec',
        nameFr: 'Centre-du-Québec',
        pdfUrl: 'https://cdn-contenu.quebec.ca/cdn-contenu/adm/min/affaires-municipales/publications/cartes/region/17.pdf',
        size: '2 Mo',
        region: 'Centre-du-Québec'
    }
};

// Helper function to get map by region name
function getMapByRegion(regionName) {
    return Object.values(quebecRegionalMaps).find(map => 
        map.region === regionName || 
        map.name === regionName || 
        map.nameFr === regionName
    );
}

// Helper function to get all maps
function getAllMaps() {
    return Object.values(quebecRegionalMaps);
}

// Make available globally
window.quebecRegionalMaps = quebecRegionalMaps;
window.getMapByRegion = getMapByRegion;
window.getAllMaps = getAllMaps;

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { quebecRegionalMaps, getMapByRegion, getAllMaps };
}

