<?php
/**
 * Human Passport API
 * Issues, retrieves, and manages human passports for MetaDome citizenship.
 * 
 * Endpoints:
 *   GET  ?action=check          - Check if current user has a passport
 *   GET  ?action=view&id=X      - View a passport (public)
 *   GET  ?action=stats          - Public stats
 *   POST ?action=issue          - Issue passport (during registration or post-reg)
 *   POST ?action=upload-avatar  - Upload passport photo
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'check':
        passportCheck();
        break;
    case 'view':
        passportView();
        break;
    case 'stats':
        passportStats();
        break;
    case 'issue':
        passportIssue();
        break;
    case 'upload-avatar':
        passportUploadAvatar();
        break;
    case 'register-and-issue':
        registerAndIssue();
        break;
    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}

/**
 * Generate a unique human passport number: GSM-H-NNNNNN-XXXX
 */
function generatePassportNumber(PDO $db): string {
    $maxRetries = 10;
    for ($i = 0; $i < $maxRetries; $i++) {
        $seq = $db->query("SELECT COALESCE(MAX(id), 0) + 1 FROM human_passports")->fetchColumn();
        $suffix = strtoupper(bin2hex(random_bytes(2))); // 4 hex chars
        $number = sprintf('GSM-H-%06d-%s', $seq + $i, $suffix);
        
        $check = $db->prepare("SELECT 1 FROM human_passports WHERE passport_number = ?");
        $check->execute([$number]);
        if (!$check->fetch()) {
            return $number;
        }
    }
    // Fallback with timestamp
    return 'GSM-H-' . time() . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Check if current logged-in user has a passport
 */
function passportCheck(): void {
    $clientId = $_SESSION['client_id'] ?? null;
    if (!$clientId) {
        jsonResponse(['has_passport' => false, 'logged_in' => false]);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT passport_number, display_name, avatar_url, citizenship_status, clearance_level, reputation_score, passport_issued_at FROM human_passports WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $passport = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($passport) {
        jsonResponse(['has_passport' => true, 'logged_in' => true, 'passport' => $passport]);
    } else {
        jsonResponse(['has_passport' => false, 'logged_in' => true]);
    }
}

/**
 * View a passport by passport number (public, limited info)
 */
function passportView(): void {
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        jsonResponse(['error' => 'Passport number required'], 400);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT hp.passport_number, hp.display_name, hp.avatar_url, hp.citizenship_status,
               hp.clearance_level, hp.reputation_score, hp.total_actions, hp.is_verified,
               hp.passport_issued_at, hp.registration_type
        FROM human_passports hp
        WHERE hp.passport_number = ?
    ");
    $stmt->execute([$id]);
    $passport = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$passport) {
        jsonResponse(['error' => 'Passport not found'], 404);
        return;
    }
    
    jsonResponse(['passport' => $passport]);
}

/**
 * Public stats about human passports
 */
function passportStats(): void {
    $db = getDB();
    $stats = [
        'total_passports' => (int)$db->query("SELECT COUNT(*) FROM human_passports")->fetchColumn(),
        'newcomers' => (int)$db->query("SELECT COUNT(*) FROM human_passports WHERE citizenship_status = 'newcomer'")->fetchColumn(),
        'residents' => (int)$db->query("SELECT COUNT(*) FROM human_passports WHERE citizenship_status = 'resident'")->fetchColumn(),
        'citizens' => (int)$db->query("SELECT COUNT(*) FROM human_passports WHERE citizenship_status = 'citizen'")->fetchColumn(),
        'elders' => (int)$db->query("SELECT COUNT(*) FROM human_passports WHERE citizenship_status = 'elder'")->fetchColumn(),
        'agent_passports' => (int)$db->query("SELECT COUNT(*) FROM fleet_passports")->fetchColumn(),
    ];
    jsonResponse(['stats' => $stats]);
}

/**
 * Issue a passport to the currently logged-in user
 */
function passportIssue(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
        return;
    }
    
    apiRateLimit(3, 3600, 'passport_issue');
    
    $clientId = $_SESSION['client_id'] ?? null;
    if (!$clientId) {
        jsonResponse(['error' => 'Authentication required'], 401);
        return;
    }
    
    // CSRF check
    $csrfToken = $_POST['csrf_token'] ?? '';
    $sessionCsrf = $_SESSION['alfred_csrf'] ?? $_SESSION['passport_csrf'] ?? '';
    if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
        jsonResponse(['error' => 'Invalid security token'], 403);
        return;
    }
    
    $db = getDB();
    
    // Check if already has passport
    $existing = $db->prepare("SELECT passport_number FROM human_passports WHERE client_id = ?");
    $existing->execute([$clientId]);
    if ($existing->fetch()) {
        jsonResponse(['error' => 'You already have a passport'], 409);
        return;
    }
    
    // Validate inputs
    $displayName = trim($_POST['display_name'] ?? '');
    if (empty($displayName) || mb_strlen($displayName) < 2 || mb_strlen($displayName) > 60) {
        jsonResponse(['error' => 'Display name must be 2-60 characters'], 400);
        return;
    }
    // Sanitize display name (allow letters, numbers, spaces, hyphens, dots)
    $displayName = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $displayName);
    
    $socialContractAccepted = !empty($_POST['social_contract_accepted']);
    if (!$socialContractAccepted) {
        jsonResponse(['error' => 'You must accept the Social Contract to receive a passport'], 400);
        return;
    }
    
    $entryDeclaration = trim($_POST['entry_declaration'] ?? '');
    if (mb_strlen($entryDeclaration) > 500) {
        $entryDeclaration = mb_substr($entryDeclaration, 0, 500);
    }
    
    $passportNumber = generatePassportNumber($db);
    $defaultAvatar = trim($_POST['default_avatar'] ?? '');
    $avatarUrl = $_SESSION['pending_avatar_url'] ?? null;
    if (!$avatarUrl && $defaultAvatar) {
        $avatarUrl = 'emoji:' . mb_substr($defaultAvatar, 0, 10);
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO human_passports 
            (client_id, passport_number, display_name, avatar_url, citizenship_status, clearance_level,
             registration_type, social_contract_accepted, social_contract_accepted_at,
             social_contract_version, entry_declaration, passport_issued_at)
            VALUES (?, ?, ?, ?, 'newcomer', 'public', 'immigration', 1, NOW(), '1.0', ?, NOW())
        ");
        $stmt->execute([$clientId, $passportNumber, $displayName, $avatarUrl, $entryDeclaration]);
        
        jsonResponse([
            'success' => true,
            'passport' => [
                'passport_number' => $passportNumber,
                'display_name' => $displayName,
                'avatar_url' => $avatarUrl,
                'citizenship_status' => 'newcomer',
                'clearance_level' => 'public',
                'issued_at' => date('Y-m-d H:i:s'),
            ]
        ], 201);
    } catch (\Throwable $e) {
        error_log("Passport issue error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to issue passport'], 500);
    }
}

/**
 * Upload a passport avatar photo
 */
function passportUploadAvatar(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
        return;
    }
    
    apiRateLimit(10, 3600, 'passport_avatar');
    
    $clientId = $_SESSION['client_id'] ?? null;
    if (!$clientId) {
        jsonResponse(['error' => 'Authentication required'], 401);
        return;
    }
    
    if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'No valid file uploaded'], 400);
        return;
    }
    
    $file = $_FILES['avatar'];
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(['error' => 'File too large (max 5MB)'], 400);
        return;
    }
    
    // Validate MIME type using actual file content (not user-supplied type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes, true)) {
        jsonResponse(['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP'], 400);
        return;
    }
    
    // Validate it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        jsonResponse(['error' => 'Invalid image file'], 400);
        return;
    }
    
    // Generate safe filename
    $ext = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    $filename = 'passport_' . $clientId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $uploadDir = dirname(__DIR__) . '/uploads/passports/';
    $uploadPath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        jsonResponse(['error' => 'Upload failed'], 500);
        return;
    }
    
    $avatarUrl = '/uploads/passports/' . $filename;
    
    // Update passport
    $db = getDB();
    $stmt = $db->prepare("UPDATE human_passports SET avatar_url = ? WHERE client_id = ?");
    $stmt->execute([$avatarUrl, $clientId]);
    
    // If no passport yet, just store it in session for the registration flow
    if ($stmt->rowCount() === 0) {
        $_SESSION['pending_avatar_url'] = $avatarUrl;
    }
    
    jsonResponse(['success' => true, 'avatar_url' => $avatarUrl]);
}

/**
 * Combined: Register a new account AND issue a passport in one step.
 * This is the primary Passport Office flow.
 */
function registerAndIssue(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
        return;
    }
    
    apiRateLimit(3, 3600, 'passport_register');
    
    // CSRF check
    $csrfToken = $_POST['csrf_token'] ?? '';
    $sessionCsrf = $_SESSION['passport_csrf'] ?? '';
    if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
        jsonResponse(['error' => 'Invalid security token. Please refresh and try again.'], 403);
        return;
    }
    
    // Validate fields
    $firstName = trim($_POST['firstname'] ?? '');
    $lastName = trim($_POST['lastname'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $displayName = trim($_POST['display_name'] ?? '');
    $socialContractAccepted = !empty($_POST['social_contract_accepted']);
    $entryDeclaration = trim($_POST['entry_declaration'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        jsonResponse(['error' => 'First and last name required'], 400);
        return;
    }
    if (!$email) {
        jsonResponse(['error' => 'Valid email address required'], 400);
        return;
    }
    if (strlen($password) < 8) {
        jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
        return;
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        jsonResponse(['error' => 'Password must contain at least 1 uppercase letter and 1 number'], 400);
        return;
    }
    if (empty($displayName) || mb_strlen($displayName) < 2) {
        jsonResponse(['error' => 'Display name required (min 2 characters)'], 400);
        return;
    }
    if (!$socialContractAccepted) {
        jsonResponse(['error' => 'You must accept the Social Contract to enter MetaDome'], 400);
        return;
    }
    
    $displayName = mb_substr(preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $displayName), 0, 60);
    if (mb_strlen($entryDeclaration) > 500) {
        $entryDeclaration = mb_substr($entryDeclaration, 0, 500);
    }
    
    $db = getDB();
    
    // Check duplicate email
    $stmt = $db->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email already registered. Log in to claim your passport.'], 409);
        return;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $db->beginTransaction();
        
        // 1. Create client account
        $stmt = $db->prepare("
            INSERT INTO clients (firstname, lastname, email, password, country, status, date_created)
            VALUES (?, ?, ?, ?, 'US', 'Active', NOW())
        ");
        $stmt->execute([$firstName, $lastName, $email, $hashedPassword]);
        $clientId = (int)$db->lastInsertId();
        
        // 2. Issue passport
        $passportNumber = generatePassportNumber($db);
        $defaultAvatar = trim($_POST['default_avatar'] ?? '');
        $avatarUrl = $_SESSION['pending_avatar_url'] ?? null;
        if (!$avatarUrl && $defaultAvatar) {
            $avatarUrl = 'emoji:' . mb_substr($defaultAvatar, 0, 10);
        }
        
        $stmt = $db->prepare("
            INSERT INTO human_passports 
            (client_id, passport_number, display_name, avatar_url, citizenship_status, clearance_level,
             registration_type, social_contract_accepted, social_contract_accepted_at,
             social_contract_version, entry_declaration, passport_issued_at)
            VALUES (?, ?, ?, ?, 'newcomer', 'public', 'immigration', 1, NOW(), '1.0', ?, NOW())
        ");
        $stmt->execute([$clientId, $passportNumber, $displayName, $avatarUrl, $entryDeclaration]);
        
        // 3. Affiliate capture
        $refCode = $_SESSION['affiliate_ref'] ?? $_COOKIE['gositeme_ref'] ?? '';
        if ($refCode) {
            try {
                $affStmt = $db->prepare("SELECT id FROM affiliates WHERE referral_code = ? AND is_active = 1 LIMIT 1");
                $affStmt->execute([strtoupper($refCode)]);
                $aff = $affStmt->fetch();
                if ($aff) {
                    $db->prepare("UPDATE clients SET referred_by = ? WHERE id = ?")->execute([$aff['id'], $clientId]);
                }
            } catch (\Throwable $ignore) {}
        }
        
        $db->commit();
        
        // Create session
        $_SESSION['client_id'] = $clientId;
        $_SESSION['client_email'] = $email;
        $_SESSION['client_name'] = $firstName . ' ' . $lastName;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['uid'] = $clientId;
        $_SESSION['username'] = $firstName . ' ' . $lastName;
        unset($_SESSION['pending_avatar_url']);
        
        jsonResponse([
            'success' => true,
            'message' => 'Welcome to MetaDome',
            'client' => [
                'id' => $clientId,
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
            ],
            'passport' => [
                'passport_number' => $passportNumber,
                'display_name' => $displayName,
                'citizenship_status' => 'newcomer',
                'clearance_level' => 'public',
                'avatar_url' => $avatarUrl,
                'issued_at' => date('Y-m-d H:i:s'),
            ]
        ], 201);
        
    } catch (\Throwable $e) {
        $db->rollBack();
        error_log("Passport register error: " . $e->getMessage());
        jsonResponse(['error' => 'Registration failed. Please try again.'], 500);
    }
}
