<?php
/**
 * GoSiteMe API Configuration
 * Connects to billing database
 */

// Prevent direct access
if (!defined('GOSITEME_API')) {
    die('Direct access not allowed');
}

// Include validation library
require_once dirname(__DIR__) . '/includes/validation.php';

// Shared database configuration (single source of truth)
require_once dirname(__DIR__) . '/includes/db-config.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// Alias constants for backward compatibility with existing API code
if (!defined('DB_HOST')) define('DB_HOST', GOSITEME_DB_HOST);
if (!defined('DB_NAME')) define('DB_NAME', GOSITEME_DB_NAME);
if (!defined('DB_USER')) define('DB_USER', GOSITEME_DB_USER);
if (!defined('DB_PASS')) define('DB_PASS', GOSITEME_DB_PASS);

// Outbound API
define('OUTBOUND_SECRET', getenv('OUTBOUND_SECRET') ?: '');

// ─── Messaging Platform Constants ──────────────────────────────────
if (!defined('INTERNAL_SECRET'))          define('INTERNAL_SECRET',          getenv('INTERNAL_SECRET') ?: '');
if (!defined('TELEGRAM_BOT_TOKEN'))       define('TELEGRAM_BOT_TOKEN',       getenv('TELEGRAM_BOT_TOKEN') ?: '');
if (!defined('TELEGRAM_WEBHOOK_SECRET'))  define('TELEGRAM_WEBHOOK_SECRET',  getenv('TELEGRAM_WEBHOOK_SECRET') ?: '');
if (!defined('DISCORD_BOT_TOKEN'))        define('DISCORD_BOT_TOKEN',        getenv('DISCORD_BOT_TOKEN') ?: '');
if (!defined('DISCORD_APP_ID'))           define('DISCORD_APP_ID',           getenv('DISCORD_APP_ID') ?: '');
if (!defined('DISCORD_PUBLIC_KEY'))       define('DISCORD_PUBLIC_KEY',       getenv('DISCORD_PUBLIC_KEY') ?: '');
if (!defined('SLACK_BOT_TOKEN'))          define('SLACK_BOT_TOKEN',          getenv('SLACK_BOT_TOKEN') ?: '');
if (!defined('SLACK_SIGNING_SECRET'))     define('SLACK_SIGNING_SECRET',     getenv('SLACK_SIGNING_SECRET') ?: '');
if (!defined('WHATSAPP_TOKEN'))           define('WHATSAPP_TOKEN',           getenv('WHATSAPP_TOKEN') ?: '');
if (!defined('WHATSAPP_PHONE_ID'))        define('WHATSAPP_PHONE_ID',        getenv('WHATSAPP_PHONE_ID') ?: '');
if (!defined('WHATSAPP_VERIFY_TOKEN'))    define('WHATSAPP_VERIFY_TOKEN',    getenv('WHATSAPP_VERIFY_TOKEN') ?: '');
if (!defined('TELNYX_API_KEY'))           define('TELNYX_API_KEY',           getenv('TELNYX_API_KEY') ?: '');
if (!defined('TELNYX_FROM_NUMBER'))       define('TELNYX_FROM_NUMBER',       getenv('TELNYX_FROM_NUMBER') ?: '');
if (!defined('SENDGRID_API_KEY'))         define('SENDGRID_API_KEY',         getenv('SENDGRID_API_KEY') ?: '');
if (!defined('GATEWAY_SECRET'))           define('GATEWAY_SECRET',           getenv('INTERNAL_SECRET') ?: '');

// ─── Stripe Constants ──────────────────────────────────────────────
if (!defined('STRIPE_SECRET_KEY'))        define('STRIPE_SECRET_KEY',        getenv('STRIPE_SECRET_KEY') ?: '');
if (!defined('STRIPE_PUBLISHABLE_KEY'))   define('STRIPE_PUBLISHABLE_KEY',   getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
if (!defined('STRIPE_WEBHOOK_SECRET'))    define('STRIPE_WEBHOOK_SECRET',    getenv('STRIPE_WEBHOOK_SECRET') ?: '');
if (!defined('STRIPE_WEBHOOK_SECRET_THIN')) define('STRIPE_WEBHOOK_SECRET_THIN', getenv('STRIPE_WEBHOOK_SECRET_THIN') ?: '');

// Site configuration
if (!defined('SITE_URL')) define('SITE_URL', 'https://gositeme.com');
if (!defined('SITE_NAME')) define('SITE_NAME', 'GoSiteMe');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '$');
if (!defined('CURRENCY_CODE')) define('CURRENCY_CODE', 'USD');

// Session configuration (only set before session starts)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 86400);   // Server-side: 24h session timeout
    ini_set('session.gc_probability', 1);        // Enable garbage collection
    ini_set('session.gc_divisor', 100);          // 1% chance per request
    ini_set('session.sid_length', 48);           // Longer session IDs (harder to brute-force)
    ini_set('session.sid_bits_per_character', 6); // More entropy per character
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('UTC');

/**
 * Database connection
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
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * Execute a prepared statement with proper type binding.
 * PHP integers are bound as PARAM_INT (required for LIMIT/OFFSET
 * when ATTR_EMULATE_PREPARES is false).
 */
if (!function_exists('dbExecute')) {
    function dbExecute(PDOStatement $stmt, array $params): PDOStatement {
        foreach ($params as $i => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue($i + 1, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . SITE_URL);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode($data);
    exit;
}

/**
 * Sanitize input
 */
function sanitize($input, $max_length = 255) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    $input = htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    if ($max_length > 0) {
        $input = substr($input, 0, $max_length);
    }
    return $input;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
