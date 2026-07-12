<?php
/**
 * The Land API - Physical Village Nodes
 */

$db = getDBConnection();

switch ($request_method) {
    case 'GET':
        if ($action === 'villages') {
            // Get villages
            $slug = $_GET['slug'] ?? null;
            $status = $_GET['status'] ?? 'active';
            
            if ($slug) {
                $stmt = $db->prepare("
                    SELECT v.*, 
                           u.username as steward_username, 
                           u.display_name as steward_name,
                           COUNT(DISTINCT vm.user_id) as member_count
                    FROM villages v
                    LEFT JOIN users u ON v.steward_id = u.id
                    LEFT JOIN village_members vm ON v.id = vm.village_id
                    WHERE v.slug = ? AND v.status = ?
                    GROUP BY v.id
                ");
                $stmt->execute([$slug, $status]);
                $village = $stmt->fetch();
                
                if (!$village) {
                    errorResponse('Village not found', 404);
                }
                
                jsonResponse(['village' => $village]);
            } else {
                $stmt = $db->prepare("
                    SELECT v.*, 
                           u.username as steward_username, 
                           u.display_name as steward_name,
                           COUNT(DISTINCT vm.user_id) as member_count
                    FROM villages v
                    LEFT JOIN users u ON v.steward_id = u.id
                    LEFT JOIN village_members vm ON v.id = vm.village_id
                    WHERE v.status = ?
                    GROUP BY v.id
                    ORDER BY v.created_at DESC
                ");
                $stmt->execute([$status]);
                $villages = $stmt->fetchAll();
                
                jsonResponse(['villages' => $villages, 'count' => count($villages)]);
            }
        }
        
        if ($action === 'resources') {
            // Get village resources
            $village_id = $_GET['village_id'] ?? null;
            $resource_type = $_GET['type'] ?? null;
            
            if (!$village_id) {
                errorResponse('village_id required', 400);
            }
            
            $sql = "SELECT r.*, u.username, u.display_name, v.name as village_name
                    FROM village_resources r
                    JOIN users u ON r.user_id = u.id
                    JOIN villages v ON r.village_id = v.id
                    WHERE r.village_id = ?";
            
            $params = [$village_id];
            if ($resource_type) {
                $sql .= " AND r.resource_type = ?";
                $params[] = $resource_type;
            }
            
            $sql .= " ORDER BY r.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $resources = $stmt->fetchAll();
            
            jsonResponse(['resources' => $resources, 'count' => count($resources)]);
        }
        
        errorResponse('Invalid action', 400);
        break;
        
    default:
        errorResponse('Method not allowed', 405);
}

