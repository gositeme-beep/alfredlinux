<?php
/**
 * SHIELD PROTECTION SYSTEM - Configuration for gositeme.com
 * 
 * This file controls the bot protection/screening system.
 * If anything goes wrong, set SHIELD_ENABLED to false to disable everything.
 * 
 * KILL SWITCH: Set SHIELD_ENABLED = false to completely disable protection
 */

// ============================================
// MASTER KILL SWITCH  
// Set to false to HARD DISABLE Shield (ignores admin toggle)
// Set to true to allow admin panel control
// ============================================
define('SHIELD_ENABLED', true);  // Re-enabled with tuned thresholds (2025-06-20)

// ============================================
// CONFIGURATION
// ============================================

// Challenge cookie lifetime (seconds) - how long before re-verification
define('SHIELD_COOKIE_LIFETIME', 86400);  // 24 hours

// Challenge timeout (seconds) - how long user has to solve challenge
define('SHIELD_CHALLENGE_TIMEOUT', 30);

// Minimum solve time (milliseconds) - too fast = bot
// Set low (100ms) to avoid false positives on fast connections
define('SHIELD_MIN_SOLVE_TIME', 500);  // Raised from 100 to avoid false positives

// Maximum solve time (milliseconds) - too slow = suspicious
define('SHIELD_MAX_SOLVE_TIME', 30000);

// Rate limit: max challenges per IP per hour (aggressive for DDoS protection)
define('SHIELD_RATE_LIMIT', 30);  // Raised from 10 to be more lenient

// Secret key for signing tokens (auto-generated if not set)
if (!defined('SHIELD_SECRET_KEY')) {
    $secret_file = __DIR__ . '/.shield_secret';
    if (file_exists($secret_file)) {
        define('SHIELD_SECRET_KEY', trim(file_get_contents($secret_file)));
    } else {
        $new_secret = bin2hex(random_bytes(32));
        @file_put_contents($secret_file, $new_secret);
        @chmod($secret_file, 0600);
        define('SHIELD_SECRET_KEY', $new_secret);
    }
}

// ============================================
// SEARCH ENGINE BOT WHITELIST
// These bots bypass the challenge entirely
// Verified via reverse DNS lookup
// ============================================
$SHIELD_BOT_WHITELIST = [
    'googlebot' => [
        'ua_contains' => 'Googlebot',
        'dns_suffix' => ['.googlebot.com', '.google.com']
    ],
    'bingbot' => [
        'ua_contains' => 'bingbot',
        'dns_suffix' => ['.search.msn.com']
    ],
    'duckduckbot' => [
        'ua_contains' => 'DuckDuckBot',
        'dns_suffix' => ['.duckduckgo.com']
    ],
    'yandexbot' => [
        'ua_contains' => 'YandexBot',
        'dns_suffix' => ['.yandex.com', '.yandex.ru', '.yandex.net']
    ],
    'baiduspider' => [
        'ua_contains' => 'Baiduspider',
        'dns_suffix' => ['.baidu.com', '.baidu.jp']
    ],
    'facebookbot' => [
        'ua_contains' => 'facebookexternalhit',
        'dns_suffix' => ['.facebook.com', '.fbsv.net']
    ],
    'twitterbot' => [
        'ua_contains' => 'Twitterbot',
        'dns_suffix' => ['.twitter.com', '.twttr.com']
    ],
    'linkedinbot' => [
        'ua_contains' => 'LinkedInBot',
        'dns_suffix' => ['.linkedin.com']
    ],
    'slackbot' => [
        'ua_contains' => 'Slackbot',
        'dns_suffix' => ['.slack.com']
    ]
];

// ============================================
// IP WHITELIST
// These IPs always bypass (your office, etc.)
// ============================================
$SHIELD_IP_WHITELIST = [
    // Add your trusted IPs here
    '109.231.64.5',  // Your IP - always allowed
    // '192.168.1.1',
    // '10.0.0.0/8',  // Supports CIDR notation
];

// ============================================
// PATHS TO SKIP
// These paths don't get challenged (APIs, webhooks, etc.)
// ============================================
$SHIELD_SKIP_PATHS = [
    // Shield system files
    '/shield_challenge.php',
    '/shield_verify.php',
    
    // WordPress core
    '/wp-admin',
    '/wp-login.php',
    '/wp-cron.php',
    '/wp-json',
    '/xmlrpc.php',
    
    // Billing portal
    '/pay/',
    
    // WordPress AJAX
    '/wp-admin/admin-ajax.php',
    
    // WordPress REST API
    '/wp-json/',
    
    // Error pages
    '/404.php',
    '/500.php',
    
    // SEO & system files
    '/robots.txt',
    '/sitemap.xml',
    '/sitemap',
    '/favicon.ico',
    '/.well-known/',
    
    // Config files (never accessed directly)
    '/config/',
    
    // Webhooks
    '/webhook',
    
    // API endpoints (VAPI, Alfred, auth)
    '/api/',
];

// ============================================
// STATIC FILE EXTENSIONS TO SKIP
// Don't challenge requests for these file types
// ============================================
$SHIELD_SKIP_EXTENSIONS = [
    'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
    'woff', 'woff2', 'ttf', 'eot', 'otf',
    'mp3', 'wav', 'ogg', 'm4a', 'flac',
    'mp4', 'webm', 'avi', 'mov',
    'pdf', 'zip', 'rar', 'gz',
    'xml', 'json', 'txt'
];

// ============================================
// FUNCTIONS
// ============================================

/**
 * Check if Shield is enabled
 */
function shield_is_enabled() {
    // Check constant first - if false, Shield is ALWAYS disabled (hard override)
    if (!defined('SHIELD_ENABLED') || !SHIELD_ENABLED) {
        return false;
    }
    
    // Check runtime file (from admin panel toggle)
    // Default to DISABLED if file doesn't exist
    $runtime_file = __DIR__ . '/.shield_runtime';
    if (file_exists($runtime_file)) {
        $runtime = json_decode(file_get_contents($runtime_file), true);
        if (isset($runtime['enabled'])) {
            return (bool)$runtime['enabled'];
        }
    }
    
    // Default: disabled until explicitly enabled via admin panel
    return false;
}

/**
 * Enable/disable Shield at runtime (from admin panel)
 */
function shield_set_enabled($enabled) {
    $runtime_file = __DIR__ . '/.shield_runtime';
    $data = ['enabled' => (bool)$enabled, 'updated_at' => date('Y-m-d H:i:s')];
    return file_put_contents($runtime_file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Get Shield status
 */
function shield_get_status() {
    $config_enabled = defined('SHIELD_ENABLED') && SHIELD_ENABLED;
    $runtime_enabled = false;  // Default to disabled
    
    $runtime_file = __DIR__ . '/.shield_runtime';
    if (file_exists($runtime_file)) {
        $runtime = json_decode(file_get_contents($runtime_file), true);
        if (isset($runtime['enabled'])) {
            $runtime_enabled = (bool)$runtime['enabled'];
        }
    }
    
    // Shield is enabled only if BOTH config allows AND runtime is enabled
    $is_enabled = $config_enabled && $runtime_enabled;
    
    return [
        'enabled' => $is_enabled,
        'config_enabled' => $config_enabled,
        'runtime_enabled' => $runtime_enabled,
        'cookie_lifetime' => defined('SHIELD_COOKIE_LIFETIME') ? SHIELD_COOKIE_LIFETIME : 86400,
        'min_solve_time' => defined('SHIELD_MIN_SOLVE_TIME') ? SHIELD_MIN_SOLVE_TIME : 100,
        'max_solve_time' => defined('SHIELD_MAX_SOLVE_TIME') ? SHIELD_MAX_SOLVE_TIME : 30000,
        'rate_limit' => defined('SHIELD_RATE_LIMIT') ? SHIELD_RATE_LIMIT : 20
    ];
}

