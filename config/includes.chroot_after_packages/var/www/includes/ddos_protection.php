<?php
/**
 * DDoS PROTECTION SYSTEM for root.com
 * 
 * This file provides aggressive DDoS protection with automatic IP blocking.
 * It runs BEFORE WordPress loads to minimize resource usage.
 * 
 * Usage: require_once 'includes/ddos_protection.php';
 */

// Load config if not already loaded
if (!defined('DDOS_ENABLED')) {
    define('DDOS_ENABLED', true); // ENABLED - DDoS protection active
    define('DDOS_RATE_LIMIT_PER_MINUTE', 30); // Max requests per minute per IP
    define('DDOS_RATE_LIMIT_PER_HOUR', 200); // Max requests per hour per IP
    define('DDOS_BLOCK_DURATION', 3600); // Block for 1 hour if limit exceeded
    define('DDOS_AUTO_BLOCK_THRESHOLD', 3); // Auto-block after 3 violations
    define('DDOS_CACHE_DIR', __DIR__ . '/../cache/ddos_protection/');
    // IP Whitelist - exact IPs that are never blocked
    define('DDOS_IP_WHITELIST', ['207.134.122.24', '15.235.50.60']); // Cooptel + server IP
    // Dynamic whitelist file — Commander IPs auto-added on login (48h expiry)
    define('DDOS_DYNAMIC_WHITELIST', __DIR__ . '/../cache/ddos_protection/commander_ips.json');
}

/**
 * Check if an IP is whitelisted (exact match or CIDR subnet match)
 */
function ddos_is_whitelisted(string $ip): bool {
    // Exact match (static)
    if (defined('DDOS_IP_WHITELIST') && in_array($ip, DDOS_IP_WHITELIST)) {
        return true;
    }
    // Dynamic Commander whitelist (auto-populated on login)
    if (defined('DDOS_DYNAMIC_WHITELIST') && file_exists(DDOS_DYNAMIC_WHITELIST)) {
        $dyn = json_decode(@file_get_contents(DDOS_DYNAMIC_WHITELIST), true);
        if (is_array($dyn) && isset($dyn[$ip]) && $dyn[$ip]['expires'] > time()) {
            return true;
        }
    }
    return false;
}

/**
 * Auto-whitelist Commander's current IP for 48 hours.
 * Called after successful login when client_id === 33.
 */
function ddos_commander_whitelist(string $ip): void {
    $file = defined('DDOS_DYNAMIC_WHITELIST')
        ? DDOS_DYNAMIC_WHITELIST
        : dirname(__DIR__) . '/cache/ddos_protection/commander_ips.json';
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $data = [];
    if (file_exists($file)) {
        $data = json_decode(@file_get_contents($file), true) ?: [];
    }

    // Clean expired entries
    $now = time();
    foreach ($data as $k => $v) {
        if (($v['expires'] ?? 0) <= $now) unset($data[$k]);
    }

    // Add/refresh this IP — 48 hour window
    $data[$ip] = [
        'added'   => $now,
        'expires' => $now + 172800,
        'source'  => 'auto_login',
    ];

    @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Check if an IP falls within a CIDR range (supports IPv4 and IPv6)
 */
function ddos_ip_in_cidr(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === false) return $ip === $cidr;
    [$subnet, $bits] = explode('/', $cidr, 2);
    $bits = (int)$bits;

    $ipBin = @inet_pton($ip);
    $subBin = @inet_pton($subnet);
    if ($ipBin === false || $subBin === false) return false;
    // Must be same address family (both v4 or both v6)
    if (strlen($ipBin) !== strlen($subBin)) return false;

    $totalBits = strlen($ipBin) * 8; // 32 for IPv4, 128 for IPv6
    if ($bits < 0 || $bits > $totalBits) return false;

    // Build byte-level mask and compare
    $fullBytes = intdiv($bits, 8);
    $remBits   = $bits % 8;
    for ($i = 0; $i < $fullBytes; $i++) {
        if ($ipBin[$i] !== $subBin[$i]) return false;
    }
    if ($remBits > 0 && $fullBytes < strlen($ipBin)) {
        $mask = 0xFF << (8 - $remBits) & 0xFF;
        if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($subBin[$fullBytes]) & $mask)) return false;
    }
    return true;
}

/**
 * Normalize any IP to a consistent string (expands IPv6, strips ::ffff: mapped prefix)
 */
function ddos_normalize_ip(string $ip): string {
    // Strip IPv4-mapped IPv6 prefix so ::ffff:1.2.3.4 becomes 1.2.3.4
    if (stripos($ip, '::ffff:') === 0 && strpos($ip, '.') !== false) {
        $ip = substr($ip, 7);
    }
    $bin = @inet_pton($ip);
    if ($bin === false) return $ip;
    return inet_ntop($bin); // Canonical form
}

/**
 * Main DDoS protection check
 */
function ddos_protect() {
    // Safety check - ensure constants are defined
    if (!defined('DDOS_ENABLED') || !DDOS_ENABLED) {
        return true;
    }
    
    // Ensure cache directory exists
    if (!defined('DDOS_CACHE_DIR')) {
        return true; // Skip if not configured
    }
    
    $cache_dir = DDOS_CACHE_DIR;
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
        if (!is_dir($cache_dir)) {
            return true; // Can't create directory, skip protection
        }
    }
    
    // Skip if already in WordPress admin (let WordPress handle it)
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $request_path = parse_url($request_uri, PHP_URL_PATH);
    
    if (strpos($request_path, '/wp-admin') === 0 || 
        strpos($request_path, '/wp-login.php') === 0) {
        return true;
    }
    
    // Skip static files
    $extension = strtolower(pathinfo($request_path, PATHINFO_EXTENSION));
    $static_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
                          'woff', 'woff2', 'ttf', 'eot', 'otf', 'mp3', 'wav', 'ogg',
                          'mp4', 'webm', 'pdf', 'zip', 'xml', 'json', 'txt'];
    if (in_array($extension, $static_extensions)) {
        return true;
    }
    
    $ip = ddos_get_client_ip();
    
    // CRITICAL: Check IP whitelist FIRST - whitelisted IPs bypass ALL checks
    if (ddos_is_whitelisted($ip)) {
        return true; // Whitelisted IP, always allow - skip all checks
    }
    
    // Check if IP is blocked (this function also checks whitelist internally)
    if (ddos_is_ip_blocked($ip)) {
        ddos_log('blocked_ip_access', $ip, ['action' => 'blocked', 'reason' => 'ip_in_blocklist']);
        http_response_code(403);
        die('Access denied. Your IP has been blocked due to suspicious activity.');
    }
    
    // Check rate limits (whitelisted IPs skip this)
    $minute_violations = ddos_check_rate_limit($ip, 'minute', DDOS_RATE_LIMIT_PER_MINUTE, 60);
    $hour_violations = ddos_check_rate_limit($ip, 'hour', DDOS_RATE_LIMIT_PER_HOUR, 3600);
    
    if ($minute_violations || $hour_violations) {
        // Double-check whitelist before blocking
        if (ddos_is_whitelisted($ip)) {
            return true; // Whitelisted, ignore violations
        }
        
        $violation_count = ddos_record_violation($ip);
        ddos_log('rate_limit_exceeded', $ip, [
            'minute_violations' => $minute_violations,
            'hour_violations' => $hour_violations,
            'total_violations' => $violation_count
        ]);
        
        // Auto-block if threshold exceeded (but not if whitelisted)
        if ($violation_count >= DDOS_AUTO_BLOCK_THRESHOLD) {
            // Final whitelist check before blocking
            if (ddos_is_whitelisted($ip)) {
                return true; // Whitelisted, don't block
            }
            ddos_block_ip($ip, DDOS_BLOCK_DURATION, 'auto_block_threshold');
            http_response_code(403);
            die('Access denied. Your IP has been automatically blocked due to excessive requests.');
        }
        
        http_response_code(429);
        // Redirect browsers to help page
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'text/html') !== false) {
            header('Location: /rate-limit-help.html');
            exit;
        }
        die('Too many requests. Please slow down.');
    }
    
    return true;
}

/**
 * Get client IP address (handles proxies)
 */
function ddos_get_client_ip() {
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle comma-separated IPs (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return ddos_normalize_ip($ip);
            }
        }
    }
    
    $raw = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return ddos_normalize_ip($raw);
}

/**
 * Check rate limit for a specific time window
 */
function ddos_check_rate_limit($ip, $window_type, $limit, $window_seconds) {
    $cache_dir = DDOS_CACHE_DIR;
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    
    $key = hash('sha256', $ip . '_' . $window_type);
    $file = $cache_dir . $key . '.json';
    
    $now = time();
    $data = ['count' => 0, 'window_start' => $now, 'reset_time' => $now + $window_seconds];
    
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content) {
            $existing = json_decode($content, true);
            if ($existing && isset($existing['window_start'])) {
                $elapsed = $now - $existing['window_start'];
                if ($elapsed < $window_seconds) {
                    // Still in same window
                    $data = $existing;
                } else {
                    // New window
                    $data = ['count' => 0, 'window_start' => $now, 'reset_time' => $now + $window_seconds];
                }
            }
        }
    }
    
    $data['count']++;
    $data['last_request'] = $now;
    
    @file_put_contents($file, json_encode($data), LOCK_EX);
    
    // Clean up old files periodically (1% chance)
    if (rand(1, 100) === 1) {
        ddos_cleanup_old_files($cache_dir, $window_seconds);
    }
    
    return $data['count'] > $limit;
}

/**
 * Record a violation for an IP
 */
function ddos_record_violation($ip) {
    $cache_dir = DDOS_CACHE_DIR;
    $violations_file = $cache_dir . 'violations.json';
    
    $violations = [];
    if (file_exists($violations_file)) {
        $content = @file_get_contents($violations_file);
        if ($content) {
            $violations = json_decode($content, true) ?: [];
        }
    }
    
    $ip_hash = hash('sha256', $ip);
    if (!isset($violations[$ip_hash])) {
        $violations[$ip_hash] = ['count' => 0, 'first_seen' => time(), 'last_seen' => time()];
    }
    
    $violations[$ip_hash]['count']++;
    $violations[$ip_hash]['last_seen'] = time();
    
    // Clean up old violations (older than 24 hours)
    foreach ($violations as $hash => $data) {
        if (time() - $data['last_seen'] > 86400) {
            unset($violations[$hash]);
        }
    }
    
    @file_put_contents($violations_file, json_encode($violations), LOCK_EX);
    
    return $violations[$ip_hash]['count'];
}

/**
 * Check if IP is blocked
 */
function ddos_is_ip_blocked($ip) {
    // CRITICAL: Check whitelist FIRST - whitelisted IPs are NEVER blocked
    if (ddos_is_whitelisted($ip)) {
        return false; // Whitelisted IP, never blocked
    }
    
    $cache_dir = DDOS_CACHE_DIR;
    $blocklist_file = $cache_dir . 'blocklist.json';
    
    if (!file_exists($blocklist_file)) {
        return false;
    }
    
    $content = @file_get_contents($blocklist_file);
    if (!$content) {
        return false;
    }
    
    $blocklist = json_decode($content, true);
    if (!$blocklist) {
        return false;
    }
    
    $ip_hash = hash('sha256', $ip);
    
    if (isset($blocklist[$ip_hash])) {
        $block_data = $blocklist[$ip_hash];
        // Check if block has expired
        if (time() < $block_data['expires_at']) {
            // IP is blocked, but check whitelist again (in case it was just added)
            if (ddos_is_whitelisted($ip)) {
                // Remove from blocklist if whitelisted
                unset($blocklist[$ip_hash]);
                @file_put_contents($blocklist_file, json_encode($blocklist), LOCK_EX);
                return false;
            }
            return true;
        } else {
            // Block expired, remove it
            unset($blocklist[$ip_hash]);
            @file_put_contents($blocklist_file, json_encode($blocklist), LOCK_EX);
        }
    }
    
    return false;
}

/**
 * Block an IP address
 */
function ddos_block_ip($ip, $duration = 3600, $reason = 'manual') {
    // NEVER block whitelisted IPs
    if (ddos_is_whitelisted($ip)) {
        error_log("DDoS Protection: Attempted to block whitelisted IP {$ip}, ignoring");
        return false; // Don't block whitelisted IPs
    }
    
    $cache_dir = DDOS_CACHE_DIR;
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    
    $blocklist_file = $cache_dir . 'blocklist.json';
    $blocklist = [];
    
    if (file_exists($blocklist_file)) {
        $content = @file_get_contents($blocklist_file);
        if ($content) {
            $blocklist = json_decode($content, true) ?: [];
        }
    }
    
    $ip_hash = hash('sha256', $ip);
    $blocklist[$ip_hash] = [
        'ip' => $ip,
        'blocked_at' => time(),
        'expires_at' => time() + $duration,
        'reason' => $reason
    ];
    
    @file_put_contents($blocklist_file, json_encode($blocklist), LOCK_EX);
    
    ddos_log('ip_blocked', $ip, ['duration' => $duration, 'reason' => $reason]);
}

/**
 * Unblock an IP address
 */
function ddos_unblock_ip($ip) {
    $cache_dir = DDOS_CACHE_DIR;
    $blocklist_file = $cache_dir . 'blocklist.json';
    
    if (!file_exists($blocklist_file)) {
        return false;
    }
    
    $content = @file_get_contents($blocklist_file);
    if (!$content) {
        return false;
    }
    
    $blocklist = json_decode($content, true);
    if (!$blocklist) {
        return false;
    }
    
    $ip_hash = hash('sha256', $ip);
    if (isset($blocklist[$ip_hash])) {
        unset($blocklist[$ip_hash]);
        @file_put_contents($blocklist_file, json_encode($blocklist), LOCK_EX);
        ddos_log('ip_unblocked', $ip, ['action' => 'manual_unblock']);
        return true;
    }
    
    return false;
}

/**
 * Clean up old rate limit files
 */
function ddos_cleanup_old_files($cache_dir, $max_age) {
    $files = glob($cache_dir . '*.json');
    $now = time();
    
    foreach ($files as $file) {
        if (basename($file) === 'blocklist.json' || basename($file) === 'violations.json') {
            continue; // Don't delete these
        }
        
        $mtime = @filemtime($file);
        if ($mtime && ($now - $mtime) > $max_age) {
            @unlink($file);
        }
    }
}

/**
 * Log DDoS protection events
 */
function ddos_log($event, $ip, $data = []) {
    $log_dir = __DIR__ . '/../logs/';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . 'ddos_protection_' . date('Y-m-d') . '.log';
    
    $log_entry = [
        'time' => date('Y-m-d H:i:s'),
        'timestamp' => time(),
        'event' => $event,
        'ip' => $ip,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'data' => $data
    ];
    
    @file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

// Run protection if this file is included
// TEMPORARILY DISABLED AUTO-EXECUTION - Call ddos_protect() manually if needed
// Wrapped in error handling to prevent fatal errors
// if (DDOS_ENABLED && function_exists('ddos_protect')) {
//     try {
//         ddos_protect();
//     } catch (Exception $e) {
//         // Log error but don't break the site
//         @error_log('DDoS Protection Error: ' . $e->getMessage());
//     } catch (Error $e) {
//         @error_log('DDoS Protection Fatal Error: ' . $e->getMessage());
//     }
// }

