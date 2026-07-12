<?php
/**
 * GoCodeMe Editor - Publish API
 * Deploy projects to user's hosting via FTP
 */

require_once dirname(__DIR__) . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
// CSRF protection
require_once dirname(dirname(__DIR__)) . '/includes/api-security.php';

// CORS headers
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check authentication
$user = checkPermission('publish');
if (!$user) {
    jsonResponse([
        'error' => 'Publishing requires an active hosting account',
        'upgrade_url' => BILLING_URL . '/cart.php'
    ], 403);
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'publish';

switch ($action) {
    // ===================================
    // GET USER'S HOSTING/FTP DETAILS
    // ===================================
    case 'get_hosting':
        if ($method !== 'GET') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        // Get user's active hosting accounts (DirectAdmin/cPanel credentials stored here)
        $stmt = $pdo->prepare("
            SELECT 
                h.id,
                h.domain,
                h.username,
                h.server,
                s.ipaddress,
                s.hostname as server_hostname,
                p.name as product_name
            FROM services h
            LEFT JOIN servers s ON h.server = s.id
            LEFT JOIN products p ON h.product_id = p.id
            WHERE h.client_id = ? AND h.status = 'Active'
            ORDER BY h.domain
        ");
        $stmt->execute([$user['id']]);
        $hostingAccounts = $stmt->fetchAll();
        // Return only safe fields to frontend (no password)
        foreach ($hostingAccounts as &$h) {
            $h = [
                'id' => $h['id'],
                'domain' => $h['domain'],
                'username' => $h['username'],
                'server' => $h['server'],
                'ipaddress' => $h['ipaddress'],
                'server_hostname' => $h['server_hostname'],
                'product_name' => $h['product_name'],
            ];
        }
        unset($h);
        
        // Get saved FTP settings
        $stmt = $pdo->prepare("
            SELECT ftp_host, ftp_user, ftp_path 
            FROM editor_user_settings 
            WHERE user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $savedSettings = $stmt->fetch();
        
        jsonResponse([
            'hosting_accounts' => $hostingAccounts,
            'saved_ftp' => $savedSettings ? [
                'host' => $savedSettings['ftp_host'],
                'user' => $savedSettings['ftp_user'],
                'path' => $savedSettings['ftp_path']
            ] : null
        ]);
        break;
    
    // ===================================
    // TEST FTP CONNECTION
    // ===================================
    case 'test_ftp':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $ftpHost = trim($input['ftp_host'] ?? '');
        $ftpUser = trim($input['ftp_user'] ?? '');
        $ftpPass = $input['ftp_pass'] ?? '';
        $ftpPath = trim($input['ftp_path'] ?? '/' . FTP_DEFAULT_PUBLIC_PATH);
        
        if (empty($ftpHost) || empty($ftpUser) || empty($ftpPass)) {
            jsonResponse(['error' => 'FTP credentials required'], 400);
        }
        
        // Test connection
        $conn = @ftp_connect($ftpHost, FTP_DEFAULT_PORT, FTP_TIMEOUT);
        if (!$conn) {
            jsonResponse(['error' => 'Could not connect to FTP server'], 400);
        }
        
        $login = @ftp_login($conn, $ftpUser, $ftpPass);
        if (!$login) {
            ftp_close($conn);
            jsonResponse(['error' => 'FTP login failed - check username/password'], 400);
        }
        
        // Enable passive mode
        ftp_pasv($conn, FTP_PASSIVE);
        
        // Check if path exists
        $pathExists = @ftp_chdir($conn, $ftpPath);
        if (!$pathExists) {
            // Try to create it
            $created = @ftp_mkdir($conn, $ftpPath);
            if (!$created) {
                ftp_close($conn);
                jsonResponse(['error' => "Path '$ftpPath' does not exist and could not be created"], 400);
            }
        }
        
        ftp_close($conn);
        
        jsonResponse([
            'success' => true,
            'message' => 'FTP connection successful!'
        ]);
        break;
    
    // ===================================
    // SAVE FTP SETTINGS
    // ===================================
    case 'save_ftp':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $ftpHost = trim($input['ftp_host'] ?? '');
        $ftpUser = trim($input['ftp_user'] ?? '');
        $ftpPass = $input['ftp_pass'] ?? '';
        $ftpPath = trim($input['ftp_path'] ?? '/' . FTP_DEFAULT_PUBLIC_PATH);
        
        // Encrypt password
        $encryptedPass = !empty($ftpPass) ? encryptString($ftpPass) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO editor_user_settings (user_id, ftp_host, ftp_user, ftp_pass_encrypted, ftp_path)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                ftp_host = VALUES(ftp_host),
                ftp_user = VALUES(ftp_user),
                ftp_pass_encrypted = COALESCE(VALUES(ftp_pass_encrypted), ftp_pass_encrypted),
                ftp_path = VALUES(ftp_path)
        ");
        $stmt->execute([$user['id'], $ftpHost, $ftpUser, $encryptedPass, $ftpPath]);
        
        jsonResponse([
            'success' => true,
            'message' => 'FTP settings saved'
        ]);
        break;
    
    // ===================================
    // PUBLISH PROJECT
    // ===================================
    case 'publish':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = isset($input['project_id']) ? (int)$input['project_id'] : null;
        $hostingId = isset($input['hosting_id']) ? (int)$input['hosting_id'] : null;
        
        if (!$projectId) {
            jsonResponse(['error' => 'Project ID required'], 400);
        }
        
        // Get project
        $stmt = $pdo->prepare("SELECT * FROM editor_projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, $user['id']]);
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(['error' => 'Project not found'], 404);
        }
        
        // Get FTP settings: from hosting (DirectAdmin/cPanel) when hosting_id given and no password, else input or saved
        $ftpHost = trim($input['ftp_host'] ?? '');
        $ftpUser = trim($input['ftp_user'] ?? '');
        $ftpPass = $input['ftp_pass'] ?? '';
        $ftpPath = trim($input['ftp_path'] ?? '/' . FTP_DEFAULT_PUBLIC_PATH);
        $subdirectory = trim($input['subdirectory'] ?? $project['slug']);
        // Security: prevent path traversal in FTP subdirectory
        $subdirectory = str_replace(['..', '/', '\\', "\0"], '', $subdirectory);
        
        // Use stored DirectAdmin/hosting credentials if user selected a hosting account and left password empty
        if ($hostingId > 0 && $ftpPass === '') {
            require_once dirname(__DIR__) . '/includes/legacy_decrypt.php';
            $stmt = $pdo->prepare("
                SELECT h.username, h.password, s.hostname, s.ipaddress
                FROM services h
                LEFT JOIN servers s ON h.server = s.id
                WHERE h.id = ? AND h.client_id = ? AND h.status = 'Active'
            ");
            $stmt->execute([$hostingId, $user['id']]);
            $hosting = $stmt->fetch();
            if ($hosting && !empty($hosting['password'])) {
                $decrypted = legacy_decrypt_password($hosting['password']);
                if ($decrypted !== '') {
                    $ftpHost = trim($hosting['hostname'] ?? $hosting['ipaddress'] ?? '');
                    $ftpUser = trim($hosting['username'] ?? '');
                    $ftpPass = $decrypted;
                    if ($ftpPath === '' || $ftpPath === '/') {
                        $ftpPath = '/' . FTP_DEFAULT_PUBLIC_PATH;
                    }
                }
            }
        }
        
        // If still no credentials, use saved FTP settings
        if ((empty($ftpHost) || empty($ftpUser) || $ftpPass === '') && $hostingId <= 0) {
            $stmt = $pdo->prepare("SELECT * FROM editor_user_settings WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $settings = $stmt->fetch();
            
            if ($settings && !empty($settings['ftp_host'])) {
                $ftpHost = $settings['ftp_host'];
                $ftpUser = $settings['ftp_user'];
                $ftpPass = decryptString($settings['ftp_pass_encrypted']);
                $ftpPath = $settings['ftp_path'] ?? $ftpPath;
            }
        }
        
        if (empty($ftpHost) || empty($ftpUser) || $ftpPass === '') {
            jsonResponse([
                'error' => 'FTP credentials required. Enter your DirectAdmin/FTP password, or save FTP details in the form first.'
            ], 400);
        }
        
        if ($ftpPath !== '' && $ftpPath[0] !== '/') {
            $ftpPath = '/' . $ftpPath;
        }
        
        // Connect to FTP
        $conn = @ftp_connect($ftpHost, FTP_DEFAULT_PORT, FTP_TIMEOUT);
        if (!$conn) {
            jsonResponse(['error' => 'Could not connect to FTP server'], 500);
        }
        
        $login = @ftp_login($conn, $ftpUser, $ftpPass);
        if (!$login) {
            ftp_close($conn);
            jsonResponse(['error' => 'FTP login failed'], 500);
        }
        
        ftp_pasv($conn, FTP_PASSIVE);
        
        // Navigate to base path
        if (!@ftp_chdir($conn, $ftpPath)) {
            ftp_close($conn);
            jsonResponse(['error' => "Could not access path: $ftpPath"], 500);
        }
        
        // Create subdirectory if needed
        $targetPath = $subdirectory ? "$ftpPath/$subdirectory" : $ftpPath;
        
        if ($subdirectory) {
            @ftp_mkdir($conn, $subdirectory);
            if (!@ftp_chdir($conn, $subdirectory)) {
                ftp_close($conn);
                jsonResponse(['error' => "Could not create/access: $subdirectory"], 500);
            }
        }
        
        // Create temp files
        $tempDir = TEMP_PATH . '/' . uniqid('publish_');
        mkdir($tempDir, 0755, true);
        
        // Build the HTML with embedded CSS/JS or linked files
        $htmlContent = $project['html_content'];
        $cssContent = $project['css_content'];
        $jsContent = $project['js_content'];
        
        // Write files
        file_put_contents($tempDir . '/index.html', $htmlContent);
        file_put_contents($tempDir . '/styles.css', $cssContent);
        file_put_contents($tempDir . '/script.js', $jsContent);
        
        // Upload files
        $uploadErrors = [];
        
        $files = ['index.html', 'styles.css', 'script.js'];
        foreach ($files as $file) {
            $localFile = $tempDir . '/' . $file;
            if (!@ftp_put($conn, $file, $localFile, FTP_ASCII)) {
                $uploadErrors[] = $file;
            }
        }
        
        ftp_close($conn);
        
        // Cleanup temp files
        array_map('unlink', glob("$tempDir/*"));
        rmdir($tempDir);
        
        if (!empty($uploadErrors)) {
            jsonResponse([
                'error' => 'Some files failed to upload: ' . implode(', ', $uploadErrors)
            ], 500);
        }
        
        // Build published URL
        // Try to determine the domain
        $publishedUrl = '';
        $stmt = $pdo->prepare("
            SELECT domain FROM services 
            WHERE userid = ? AND domainstatus = 'Active' 
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $hosting = $stmt->fetch();
        
        if ($hosting) {
            $publishedUrl = 'https://' . $hosting['domain'];
            if ($subdirectory) {
                $publishedUrl .= '/' . $subdirectory;
            }
        }
        
        // Update project as published
        $stmt = $pdo->prepare("
            UPDATE editor_projects 
            SET is_published = 1, published_url = ?, published_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$publishedUrl, $projectId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Project published successfully!',
            'url' => $publishedUrl,
            'files_uploaded' => $files
        ]);
        break;
    
    // ===================================
    // UNPUBLISH (remove from hosting)
    // ===================================
    case 'unpublish':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = isset($input['project_id']) ? (int)$input['project_id'] : null;
        
        if (!$projectId) {
            jsonResponse(['error' => 'Project ID required'], 400);
        }
        
        // Just update the database - don't delete files (user might want to keep them)
        $stmt = $pdo->prepare("
            UPDATE editor_projects 
            SET is_published = 0 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$projectId, $user['id']]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Project marked as unpublished'
        ]);
        break;
    
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
