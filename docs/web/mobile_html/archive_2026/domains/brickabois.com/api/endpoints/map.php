<?php
/**
 * Interactive Map API - City Locations with Member Data
 */

$db = getDBConnection();

switch ($request_method) {
    case 'GET':
        // Get all Quebec cities with member counts from villages in those cities
        // Match villages to cities by location (lat/lng proximity or city name)
        $stmt = $db->prepare("
            SELECT 
                v.id,
                v.name,
                v.name_fr,
                v.slug,
                v.description,
                v.description_fr,
                v.location_lat,
                v.location_lng,
                v.location_address,
                v.region,
                v.country,
                v.status,
                v.founded_date,
                v.photo_url,
                COUNT(DISTINCT vm.user_id) as member_count,
                COUNT(DISTINCT p.id) as post_count,
                COUNT(DISTINCT e.id) as event_count
            FROM villages v
            LEFT JOIN village_members vm ON v.id = vm.village_id
            LEFT JOIN posts p ON v.id = p.village_id AND p.deleted_at IS NULL
            LEFT JOIN events e ON v.id = e.village_id AND e.start_date >= NOW()
            WHERE (v.status != 'archived' OR v.status IS NULL)
            GROUP BY v.id
            ORDER BY v.status DESC, v.created_at DESC
        ");
        $stmt->execute();
        $villages = $stmt->fetchAll();
        
        // For villages without location, try to match them to cities by name
        $municipalities = [];
        if (file_exists(__DIR__ . '/../../assets/data/quebec-municipalities.js')) {
            $municipalitiesContent = file_get_contents(__DIR__ . '/../../assets/data/quebec-municipalities.js');
            // Simple regex to extract city names and their data
            preg_match_all("/'([^']+)':\s*\{\s*lat:\s*([0-9.-]+),\s*lng:\s*([0-9.-]+),\s*region:\s*'([^']+)',\s*population:\s*([0-9]+)\s*\}/", $municipalitiesContent, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $municipalities[$match[1]] = [
                    'lat' => floatval($match[2]),
                    'lng' => floatval($match[3]),
                    'region' => $match[4],
                    'population' => intval($match[5])
                ];
            }
        }
        
        // Assign location to villages without coordinates by matching city name
        foreach ($villages as &$village) {
            if (!$village['location_lat'] || !$village['location_lng']) {
                $villageName = $village['name'];
                // Try exact match first
                if (isset($municipalities[$villageName])) {
                    $village['location_lat'] = $municipalities[$villageName]['lat'];
                    $village['location_lng'] = $municipalities[$villageName]['lng'];
                } else {
                    // Try case-insensitive match
                    foreach ($municipalities as $cityName => $cityData) {
                        if (strcasecmp($cityName, $villageName) === 0 || 
                            stripos($cityName, $villageName) !== false ||
                            stripos($villageName, $cityName) !== false) {
                            $village['location_lat'] = $cityData['lat'];
                            $village['location_lng'] = $cityData['lng'];
                            break;
                        }
                    }
                }
            }
        }
        unset($village);
        
        // Return villages data - the frontend will merge this with quebecMunicipalities
        jsonResponse([
            'cities' => $villages, // Keep same structure for compatibility
            'villages' => $villages, // Also return as villages
            'count' => count($villages)
        ]);
        break;
        
    default:
        errorResponse('Method not allowed', 405);
}
