<?php
/**
 * GoCodeMe Online Editor Configuration
 * 
 * IMPORTANT: Update these settings with your actual values!
 */

// Prevent direct access
if (!defined('GOCODEME_EDITOR')) {
    define('GOCODEME_EDITOR', true);
}

// Load session bootstrap so editor shares login with billing portal (must be before any session_start)
require_once __DIR__ . '/bootstrap_session.php';

// ===========================================
// DATABASE CONFIGURATION
// ===========================================
// Use shared database configuration (single source of truth)
require_once dirname(__DIR__) . '/includes/db-config.inc.php';
if (!defined('DB_HOST')) define('DB_HOST', GOSITEME_DB_HOST);
if (!defined('DB_NAME')) define('DB_NAME', GOSITEME_DB_NAME);
if (!defined('DB_USER')) define('DB_USER', GOSITEME_DB_USER);
if (!defined('DB_PASS')) define('DB_PASS', GOSITEME_DB_PASS);
define('DB_PREFIX', 'editor_');      // Prefix for editor tables

// ===========================================
// AI API CONFIGURATION
// ===========================================
// Choose your AI provider: 'openai' or 'anthropic'
define('AI_PROVIDER', getenv('EDITOR_AI_PROVIDER') ?: 'openai');

// OpenAI Settings — NEVER hardcode API keys; load from env
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_MODEL', 'gpt-4o');  // Options: gpt-4o, gpt-4-turbo, gpt-3.5-turbo
define('OPENAI_MAX_TOKENS', 4000);

// Anthropic Claude Settings (alternative)
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');
define('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514');

// AI Usage Limits
define('AI_DAILY_LIMIT_FREE', 10);      // Free users: 10 generations/day
define('AI_DAILY_LIMIT_PAID', 100);     // Paid hosting users: 100/day
define('AI_MONTHLY_LIMIT_FREE', 50);    // Free users: 50/month
define('AI_MONTHLY_LIMIT_PAID', 1000);  // Paid users: 1000/month

// ===========================================
// BILLING INTEGRATION
// ===========================================
define('BILLING_URL', 'https://gositeme.com/pay');
define('REQUIRE_LOGIN', true);          // Require login to use editor
define('ALLOW_GUEST_PREVIEW', true);    // Allow guests to preview (no save)

// ===========================================
// FILE STORAGE
// ===========================================
define('STORAGE_PATH', __DIR__ . '/storage');
define('PROJECTS_PATH', STORAGE_PATH . '/projects');
define('TEMP_PATH', STORAGE_PATH . '/temp');
define('MAX_PROJECT_SIZE', 10 * 1024 * 1024); // 10MB max per project

// ===========================================
// FTP PUBLISHING DEFAULTS
// ===========================================
// Public folder on user's FTP = document root (site is visible at their domain)
// Common: public_html (cPanel), www, htdocs (Plesk), httpdocs
define('FTP_DEFAULT_PUBLIC_PATH', 'public_html');
define('FTP_DEFAULT_PORT', 21);
define('FTP_TIMEOUT', 30);
define('FTP_PASSIVE', true);

// ===========================================
// SECURITY
// ===========================================
define('ENCRYPTION_KEY', getenv('EDITOR_ENCRYPTION_KEY') ?: bin2hex(random_bytes(16))); // For encrypting FTP passwords
define('SESSION_NAME', 'gocodeme_session');
define('CSRF_TOKEN_NAME', 'gocodeme_csrf');

// ===========================================
// EDITOR SETTINGS
// ===========================================
define('EDITOR_VERSION', '1.0.0');
define('AUTO_SAVE_INTERVAL', 30000);    // Auto-save every 30 seconds
define('MAX_PROJECTS_FREE', 3);         // Free users: 3 projects
define('MAX_PROJECTS_PAID', 50);        // Paid users: 50 projects

// ===========================================
// DEBUG MODE
// ===========================================
define('DEBUG_MODE', false); // Set to true for development
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ===========================================
// HELPER FUNCTIONS
// ===========================================

/**
 * Get database connection
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            }
            die("Database connection failed");
        }
    }
    return $pdo;
}

/**
 * Send JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Encrypt string (for FTP passwords)
 */
function encryptString($string) {
    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($string, $cipher, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt string
 */
function decryptString($encrypted) {
    $data = base64_decode($encrypted);
    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = substr($data, 0, $ivlen);
    $encrypted = substr($data, $ivlen);
    return openssl_decrypt($encrypted, $cipher, ENCRYPTION_KEY, 0, $iv);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
}

/**
 * Generate unique slug
 */
function generateSlug($name, $existingCheck = null) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $slug = trim($slug, '-');
    
    if ($existingCheck && is_callable($existingCheck)) {
        $originalSlug = $slug;
        $counter = 1;
        while ($existingCheck($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
    
    return $slug;
}

// Create storage directories if they don't exist
if (!is_dir(STORAGE_PATH)) mkdir(STORAGE_PATH, 0755, true);
if (!is_dir(PROJECTS_PATH)) mkdir(PROJECTS_PATH, 0755, true);
if (!is_dir(TEMP_PATH)) mkdir(TEMP_PATH, 0755, true);
