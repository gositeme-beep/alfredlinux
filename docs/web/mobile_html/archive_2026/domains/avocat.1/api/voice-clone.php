<?php
/**
 * Voice Cloning API — Upload, manage, and retrieve voice profiles
 * POST ?action=upload — Upload audio samples for voice cloning
 * GET  ?action=profiles — List user's voice profiles
 * POST ?action=delete — Delete a voice profile
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Auth check
session_start();
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

$clientId = (int) $_SESSION['client_id'];
$action = $_GET['action'] ?? '';

try {
    $db = new PDO(
        'mysql:host=' . (defined('DB_HOST') ? DB_HOST : 'localhost') . ';dbname=' . (defined('DB_NAME') ? DB_NAME : 'gositeme_main') . ';charset=utf8mb4',
        defined('DB_USER') ? DB_USER : 'gositeme_main',
        defined('DB_PASS') ? DB_PASS : '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    error_log("Voice clone DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Ensure tables exist
$db->exec("CREATE TABLE IF NOT EXISTS voice_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    language VARCHAR(50) DEFAULT 'en',
    status ENUM('uploading','processing','training','ready','failed') DEFAULT 'uploading',
    sample_count INT DEFAULT 0,
    storage_path VARCHAR(500) DEFAULT NULL,
    model_id VARCHAR(200) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS voice_recordings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    sentence_index INT DEFAULT 0,
    filename VARCHAR(300) NOT NULL,
    file_size INT DEFAULT 0,
    duration_ms INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_profile (profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

switch ($action) {
    case 'upload':
        handleUpload($db, $clientId);
        break;
    case 'profiles':
        handleListProfiles($db, $clientId);
        break;
    case 'delete':
        handleDeleteProfile($db, $clientId);
        break;
    default:
        echo json_encode(['error' => 'Invalid action. Valid: upload, profiles, delete']);
}

function handleUpload($db, $clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'POST required']);
        return;
    }

    $profileName = trim($_POST['profile_name'] ?? 'My Voice Profile');
    $language = trim($_POST['language'] ?? 'en');
    $sentenceIndex = (int) ($_POST['sentence_index'] ?? 0);

    // Validate language
    $validLangs = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ja', 'ko', 'zh', 'ar', 'hi', 'ru'];
    if (!in_array($language, $validLangs)) $language = 'en';

    // Check for audio file
    if (empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No audio file uploaded']);
        return;
    }

    $file = $_FILES['audio'];

    // Validate file size (max 10MB per sample)
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Audio file too large (max 10MB)']);
        return;
    }

    // Validate MIME type server-side
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['audio/webm', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/mpeg', 'audio/x-wav', 'video/webm'];
    if (!in_array($mimeType, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid audio format. Supported: webm, wav, ogg, mp4, mp3']);
        return;
    }

    // Get or create voice profile
    $profileId = (int) ($_POST['profile_id'] ?? 0);
    if ($profileId > 0) {
        $stmt = $db->prepare("SELECT * FROM voice_profiles WHERE id = ? AND user_id = ?");
        $stmt->execute([$profileId, $clientId]);
        $profile = $stmt->fetch();
        if (!$profile) {
            http_response_code(404);
            echo json_encode(['error' => 'Voice profile not found']);
            return;
        }
    } else {
        // Check limit (max 5 profiles per user)
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM voice_profiles WHERE user_id = ?");
        $stmt->execute([$clientId]);
        if ($stmt->fetch()['cnt'] >= 5) {
            http_response_code(403);
            echo json_encode(['error' => 'Maximum 5 voice profiles allowed. Delete one to continue.']);
            return;
        }

        $stmt = $db->prepare("INSERT INTO voice_profiles (user_id, name, language, status) VALUES (?, ?, ?, 'uploading')");
        $stmt->execute([$clientId, $profileName, $language]);
        $profileId = (int) $db->lastInsertId();
    }

    // Create storage directory
    $storagePath = dirname(__DIR__) . '/voice/clones/' . $clientId . '/' . $profileId;
    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0755, true);
    }

    // Save the audio file
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'webm';
    $ext = preg_replace('/[^a-z0-9]/', '', strtolower($ext));
    $filename = 'sample_' . $sentenceIndex . '_' . time() . '.' . $ext;
    $destPath = $storagePath . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save audio file']);
        return;
    }

    // Record in database
    $stmt = $db->prepare("INSERT INTO voice_recordings (profile_id, sentence_index, filename, file_size) VALUES (?, ?, ?, ?)");
    $stmt->execute([$profileId, $sentenceIndex, $filename, $file['size']]);

    // Update sample count
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM voice_recordings WHERE profile_id = ?");
    $stmt->execute([$profileId]);
    $sampleCount = $stmt->fetch()['cnt'];

    $stmt = $db->prepare("UPDATE voice_profiles SET sample_count = ?, storage_path = ?, status = 'processing' WHERE id = ?");
    $stmt->execute([$sampleCount, 'voice/clones/' . $clientId . '/' . $profileId, $profileId]);

    echo json_encode([
        'success' => true,
        'profile_id' => $profileId,
        'sample_count' => $sampleCount,
        'sentence_index' => $sentenceIndex,
        'message' => "Sample $sampleCount uploaded successfully"
    ]);
}

function handleListProfiles($db, $clientId) {
    $stmt = $db->prepare("SELECT p.*, (SELECT COUNT(*) FROM voice_recordings WHERE profile_id = p.id) as recording_count FROM voice_profiles p WHERE p.user_id = ? ORDER BY p.created_at DESC");
    $stmt->execute([$clientId]);
    $profiles = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'profiles' => $profiles
    ]);
}

function handleDeleteProfile($db, $clientId) {
    $profileId = (int) ($_POST['profile_id'] ?? $_GET['profile_id'] ?? 0);
    if ($profileId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'profile_id required']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM voice_profiles WHERE id = ? AND user_id = ?");
    $stmt->execute([$profileId, $clientId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['error' => 'Profile not found']);
        return;
    }

    // Delete recordings from disk
    $storagePath = dirname(__DIR__) . '/' . ($profile['storage_path'] ?? '');
    if ($storagePath && is_dir($storagePath)) {
        $files = glob($storagePath . '/*');
        foreach ($files as $f) {
            if (is_file($f)) unlink($f);
        }
        @rmdir($storagePath);
    }

    // Delete from database
    $db->prepare("DELETE FROM voice_recordings WHERE profile_id = ?")->execute([$profileId]);
    $db->prepare("DELETE FROM voice_profiles WHERE id = ? AND user_id = ?")->execute([$profileId, $clientId]);

    echo json_encode(['success' => true, 'message' => 'Voice profile deleted']);
}
