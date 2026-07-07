<?php
/**
 * Map Villages API Endpoint
 * Returns villages with their locations for the map
 */

// Prevent any output before JSON
ob_start();

require_once dirname(__DIR__, 2) . '/private_html/config.php';
require_once dirname(__DIR__, 2) . '/public_html/includes/auth.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');

$db = getDBConnection();
$currentUser = getCurrentUser();

try {
    $isMemberQuery = $currentUser ? 
        "(SELECT COUNT(*) FROM village_members WHERE village_id = v.id AND user_id = " . intval($currentUser['id']) . ") as is_member" :
        "0 as is_member";
    
    $stmt = $db->query("
        SELECT 
            v.id,
            v.name,
            v.name_fr,
            v.slug,
            v.location_lat as lat,
            v.location_lng as lng,
            v.region,
            v.status,
            v.description,
            v.description_fr,
            COUNT(DISTINCT vm.user_id) as member_count,
            $isMemberQuery
        FROM villages v
        LEFT JOIN village_members vm ON v.id = vm.village_id
        WHERE v.location_lat IS NOT NULL AND v.location_lng IS NOT NULL
        GROUP BY v.id
        ORDER BY v.status DESC, v.name
    ");
    
    $villages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'villages' => $villages
    ]);
    exit;
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

