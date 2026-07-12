<?php
/**
 * Upload Village Photo
 */

require_once dirname(__DIR__, 2) . '/private_html/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$currentUser = getCurrentUser();
$db = getDBConnection();

// Check if user is admin or village steward
$village_id = isset($_POST['village_id']) ? (int)$_POST['village_id'] : 0;
$is_primary = isset($_POST['is_primary']) ? (int)$_POST['is_primary'] : 0;

// Verify user has permission
$permissionCheck = $db->prepare("
    SELECT v.id FROM villages v
    LEFT JOIN village_members vm ON v.id = vm.village_id AND vm.user_id = ? AND vm.role = 'steward'
    WHERE v.id = ? AND (v.steward_id = ? OR vm.id IS NOT NULL OR ? = (SELECT id FROM users WHERE role = 'admin' LIMIT 1))
");
$permissionCheck->execute([$currentUser['id'], $village_id, $currentUser['id'], $currentUser['id']]);
if (!$permissionCheck->fetch()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Upload error']);
        exit;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File too large']);
        exit;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'village_' . $village_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $upload_dir = dirname(__DIR__, 2) . '/public_html/uploads/villages/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $photo_url = '/uploads/villages/' . $filename;
        
        // If this is primary, unset other primary photos
        if ($is_primary) {
            $unsetPrimary = $db->prepare("UPDATE village_photos SET is_primary = 0 WHERE village_id = ?");
            $unsetPrimary->execute([$village_id]);
            
            // Update village photo_url
            $updateVillage = $db->prepare("UPDATE villages SET photo_url = ? WHERE id = ?");
            $updateVillage->execute([$photo_url, $village_id]);
        }
        
        // Insert photo record
        $insertStmt = $db->prepare("
            INSERT INTO village_photos (village_id, user_id, photo_url, is_primary, display_order)
            VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(display_order), 0) + 1 FROM (SELECT display_order FROM village_photos WHERE village_id = ?) as temp))
        ");
        $insertStmt->execute([$village_id, $currentUser['id'], $photo_url, $is_primary, $village_id]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'photo_url' => $photo_url,
            'photo_id' => $db->lastInsertId()
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to save file']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No file uploaded']);
}

