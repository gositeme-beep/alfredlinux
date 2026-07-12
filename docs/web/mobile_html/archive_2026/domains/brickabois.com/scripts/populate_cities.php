<?php
/**
 * Populate Cities Table from Quebec Municipalities Data
 * Run this script once to populate all cities from quebec-municipalities.js
 */

require_once dirname(__DIR__, 2) . '/private_html/config.php';

$db = getDBConnection();

// Read quebec-municipalities.js file
$municipalitiesFile = __DIR__ . '/../assets/data/quebec-municipalities.js';
if (!file_exists($municipalitiesFile)) {
    die("Error: quebec-municipalities.js not found at $municipalitiesFile\n");
}

$content = file_get_contents($municipalitiesFile);

// Parse JavaScript object - match each city entry (all on one line)
$municipalities = [];
// Match pattern: 'City Name': { lat: X, lng: Y, region: 'Region', population: Z },
preg_match_all("/'([^']*(?:\\\\.[^']*)*)':\s*\{\s*lat:\s*([0-9.-]+),\s*lng:\s*([0-9.-]+),\s*region:\s*'([^']*(?:\\\\.[^']*)*)',\s*population:\s*([0-9]+)\s*\}/", $content, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
    $municipalities[] = [
        'name' => str_replace("\\'", "'", $match[1]),
        'lat' => floatval($match[2]),
        'lng' => floatval($match[3]),
        'region' => str_replace("\\'", "'", $match[4]),
        'population' => intval($match[5])
    ];
}

if (empty($municipalities)) {
    die("Error: No municipalities found. Please check the data file format.\n");
}

echo "Found " . count($municipalities) . " cities to insert.\n";

// Insert cities
$stmt = $db->prepare("
    INSERT INTO cities (name, lat, lng, region, population, country, is_active)
    VALUES (?, ?, ?, ?, ?, 'Canada', 0)
    ON DUPLICATE KEY UPDATE
        lat = VALUES(lat),
        lng = VALUES(lng),
        region = VALUES(region),
        population = VALUES(population),
        updated_at = CURRENT_TIMESTAMP
");

$inserted = 0;
$updated = 0;

foreach ($municipalities as $city) {
    try {
        $stmt->execute([
            $city['name'],
            $city['lat'],
            $city['lng'],
            $city['region'],
            $city['population'] ?? 0
        ]);
        
        if ($stmt->rowCount() == 1) {
            $inserted++;
        } else {
            $updated++;
        }
    } catch (Exception $e) {
        echo "Error inserting {$city['name']}: " . $e->getMessage() . "\n";
    }
}

echo "Done! Inserted: $inserted, Updated: $updated\n";

