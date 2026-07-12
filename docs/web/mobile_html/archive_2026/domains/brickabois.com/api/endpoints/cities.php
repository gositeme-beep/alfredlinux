<?php
/**
 * Cities API Endpoint
 * Returns all cities with GPS coordinates
 */

// Turn off error display, capture everything
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering immediately
if (!ob_get_level()) {
    ob_start();
}

// Send headers first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Clear any output that might have been generated
    ob_clean();
    
    // Try to require config - check multiple possible paths
    $possiblePaths = [
        dirname(__DIR__, 2) . '/private_html/config.php',
        dirname(__DIR__, 3) . '/private_html/config.php',
        __DIR__ . '/../../private_html/config.php'
    ];
    
    $configPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $configPath = $path;
            break;
        }
    }
    
    if (!$configPath) {
        // Return empty array instead of error - map will use fallback
        echo json_encode([
            'success' => true,
            'cities' => [],
            'message' => 'Config not found, using fallback data'
        ]);
        exit;
    }
    
    require_once $configPath;
    
    // Clear any output from config
    ob_clean();
    
    // Try to get database connection
    if (!function_exists('getDBConnection')) {
        throw new Exception('Database connection function not available');
    }
    
    $db = getDBConnection();
    
    // Check if cities table exists first
    try {
        $tableCheck = $db->query("SHOW TABLES LIKE 'cities'");
        if ($tableCheck->rowCount() === 0) {
            // Table doesn't exist, return empty array
            ob_clean();
            echo json_encode([
                'success' => true,
                'cities' => [],
                'message' => 'Cities table does not exist yet'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        // Can't check tables, but continue anyway
    }
    
    // Try to query cities table
    try {
        $stmt = $db->query("SELECT id, name, lat, lng, region, population, is_active FROM cities ORDER BY name LIMIT 1000");
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure all numeric fields are properly formatted
        foreach ($cities as &$city) {
            $city['lat'] = (float)$city['lat'];
            $city['lng'] = (float)$city['lng'];
            $city['population'] = (int)($city['population'] ?? 0);
            $city['is_active'] = (int)($city['is_active'] ?? 0);
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'cities' => $cities
        ], JSON_NUMERIC_CHECK);
        exit;
        
    } catch (PDOException $e) {
        // Table might not exist or query failed, return empty array
        ob_clean();
        echo json_encode([
            'success' => true,
            'cities' => [],
            'message' => 'Could not query cities table: ' . $e->getMessage()
        ]);
        exit;
    }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'cities' => []
    ]);
    exit;
} catch (Error $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error: ' . $e->getMessage(),
        'cities' => []
    ]);
    exit;
}
