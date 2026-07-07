<?php
/**
 * SHIELD PROTECTION MIDDLEWARE for root.com
 * 
 * Include this file at the top of your main entry points (index.php, etc.)
 * to enable bot protection screening.
 * 
 * Usage: require_once 'includes/shield.php';
 * 
 * If Shield is disabled, this file does nothing.
 */

// Load config (only if not already loaded)
if (!defined('SHIELD_ENABLED')) {
    require_once __DIR__ . '/../config/shield_config.php';
}

// DO NOT start session here - it breaks WordPress headers
// Session will be started only when needed in shield_protect() function
// This prevents header conflicts with WordPress CSS/JS loading

/**
 * Main Shield check - call this to protect a page
 */
function shield_protect() {
    // SHIELD COMPLETELY DISABLED - Return immediately, do nothing
    return true;
    
    global $SHIELD_BOT_WHITELIST, $SHIELD_IP_WHITELIST, $SHIELD_SKIP_PATHS, $SHIELD_SKIP_EXTENSIONS;
    
    // CRITICAL: NEVER run Shield in WordPress admin - check this FIRST before anything else
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $request_path = parse_url($request_uri, PHP_URL_PATH);
    
    // Skip WordPress admin completely - do this BEFORE any other checks
    if (strpos($request_path, '/wp-admin') === 0 || 
        strpos($request_path, '/wp-login.php') === 0 ||
        (defined('WP_ADMIN') && WP_ADMIN) ||
        (defined('WP_CLI') && WP_CLI)) {
        return true; // Never protect admin, return immediately
    }
    
    // Check if Shield is enabled
    if (!shield_is_enabled()) {
        return true; // Shield disabled, allow all
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Skip static files
    $extension = strtolower(pathinfo($request_path, PATHINFO_EXTENSION));
    if (in_array($extension, $SHIELD_SKIP_EXTENSIONS)) {
        return true;
    }
    
    // Skip configured paths
    foreach ($SHIELD_SKIP_PATHS as $skip_path) {
        if (strpos($request_path, $skip_path) === 0) {
            return true;
        }
    }
    
    // Start session ONLY when we actually need it (not for static files or skipped paths)
    // This will NEVER run for wp-admin because we return early above
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    
    // Check IP whitelist
    if (shield_is_ip_whitelisted($ip, $SHIELD_IP_WHITELIST)) {
        return true;
    }
    
    // Whitelist speed test and monitoring tools (GTmetrix, Lighthouse, etc.)
    $speed_test_bots = ['GTmetrix', 'PageSpeed', 'Lighthouse', 'PTST', 'Chrome-Lighthouse',
                        'pingdom', 'uptimerobot', 'StatusCake', 'Site24x7', 'Datadog', 
                        'WebPageTest', 'YSlow', 'DareBoost'];
    foreach ($speed_test_bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            shield_log('speed_test_allowed', $ip, ['bot' => $bot, 'action' => 'bypassed']);
            return true;
        }
    }
    
    // Check for verified search engine bots
    if (shield_is_verified_bot($ip, $user_agent, $SHIELD_BOT_WHITELIST)) {
        shield_log('bot_allowed', $ip, ['bot' => 'verified_search_engine', 'ua' => $user_agent]);
        return true;
    }
    
    // Check for valid verification cookie
    if (shield_has_valid_cookie()) {
        return true;
    }
    
    // Check rate limit for challenges
    if (!shield_check_rate_limit($ip)) {
        shield_log('rate_limited', $ip, ['action' => 'blocked']);
        http_response_code(429);
        // Redirect browsers to help page
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'text/html') !== false) {
            header('Location: /rate-limit-help.html');
            exit;
        }
        die('Too many requests. Please try again later.');
    }
    
    // Redirect to challenge - DISABLED
    // shield_redirect_to_challenge($request_uri);
    // exit;
    // Just allow access if we get here
    return true;
}

/**
 * Check if IP is in whitelist (supports CIDR notation)
 */
function shield_is_ip_whitelisted($ip, $whitelist) {
    foreach ($whitelist as $allowed) {
        if (strpos($allowed, '/') !== false) {
            // CIDR notation
            if (shield_ip_in_cidr($ip, $allowed)) {
                return true;
            }
        } else {
            if ($ip === $allowed) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Check if IP is in CIDR range (supports IPv4 and IPv6)
 */
function shield_ip_in_cidr($ip, $cidr) {
    list($subnet, $bits) = explode('/', $cidr);
    $bits = (int)$bits;
    $ipBin = @inet_pton($ip);
    $subBin = @inet_pton($subnet);
    if ($ipBin === false || $subBin === false) return false;
    if (strlen($ipBin) !== strlen($subBin)) return false;
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
 * Verify if a bot claiming to be a search engine is legitimate
 * Uses reverse DNS verification
 */
function shield_is_verified_bot($ip, $user_agent, $whitelist) {
    // TEMPORARILY DISABLED - DNS lookups cause timeouts
    // Just check user agent for now
    foreach ($whitelist as $bot_name => $config) {
        // Check if user agent contains the bot identifier
        if (stripos($user_agent, $config['ua_contains']) !== false) {
            // Skip DNS verification to prevent timeout
            shield_log('bot_allowed_ua_only', $ip, ['claimed' => $bot_name, 'ua' => $user_agent]);
            return true;
        }
    }
    
    return false;
    
    // DISABLED DNS VERIFICATION - CAUSES TIMEOUTS
    // // Do reverse DNS lookup
    // $hostname = gethostbyaddr($ip);
    // if ($hostname === $ip) {
    //     // DNS lookup failed - not verified
    //     shield_log('bot_unverified', $ip, ['claimed' => $bot_name, 'reason' => 'dns_failed']);
    //     return false;
    // }
    // 
    // // Check if hostname ends with allowed suffix
    // $verified = false;
    // foreach ($config['dns_suffix'] as $suffix) {
    //     if (substr($hostname, -strlen($suffix)) === $suffix) {
    //         // Forward DNS to verify
    //         $forward_ip = gethostbyname($hostname);
    //         if ($forward_ip === $ip) {
    //             $verified = true;
    //             break;
    //         }
    //     }
    // }
}

/**
 * Check if visitor has valid verification cookie
 */
function shield_has_valid_cookie() {
    $cookie_name = 'shield_verified';
    
    if (!isset($_COOKIE[$cookie_name])) {
        return false;
    }
    
    $token = $_COOKIE[$cookie_name];
    
    // Parse token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($payload_b64, $expires, $signature) = $parts;
    
    // Check expiration
    if (time() > (int)$expires) {
        return false;
    }
    
    // Verify signature
    $expected_sig = hash_hmac('sha256', $payload_b64 . '.' . $expires, SHIELD_SECRET_KEY);
    if (!hash_equals($expected_sig, $signature)) {
        return false;
    }
    
    // Optionally verify payload matches current visitor
    $payload = json_decode(base64_decode($payload_b64), true);
    if (!$payload) {
        return false;
    }
    
    // Loose IP check (first 3 octets) to allow for dynamic IPs within ISP
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $token_ip = $payload['ip'] ?? '';
    
    $current_prefix = implode('.', array_slice(explode('.', $current_ip), 0, 3));
    $token_prefix = implode('.', array_slice(explode('.', $token_ip), 0, 3));
    
    if ($current_prefix !== $token_prefix) {
        shield_log('cookie_ip_mismatch', $current_ip, [
            'token_ip' => $token_ip
        ]);
        return false;
    }
    
    return true;
}

/**
 * Generate verification cookie
 */
function shield_generate_cookie($ip, $fingerprint = '') {
    $payload = [
        'ip' => $ip,
        'fp' => substr(hash('sha256', $fingerprint), 0, 16),
        'ts' => time()
    ];
    
    $payload_b64 = base64_encode(json_encode($payload));
    $expires = time() + SHIELD_COOKIE_LIFETIME;
    $signature = hash_hmac('sha256', $payload_b64 . '.' . $expires, SHIELD_SECRET_KEY);
    
    $token = $payload_b64 . '.' . $expires . '.' . $signature;
    
    setcookie('shield_verified', $token, [
        'expires' => $expires,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    return $token;
}

/**
 * Check rate limit for challenges
 */
function shield_check_rate_limit($ip) {
    $rate_dir = __DIR__ . '/../cache/shield_rates/';
    
    if (!is_dir($rate_dir)) {
        @mkdir($rate_dir, 0755, true);
    }
    
    $key = hash('sha256', $ip);
    $file = $rate_dir . $key . '.json';
    
    $window = 3600; // 1 hour
    $limit = SHIELD_RATE_LIMIT;
    
    $data = ['count' => 0, 'reset_time' => time() + $window];
    
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content) {
            $existing = json_decode($content, true);
            if ($existing && isset($existing['reset_time'])) {
                if (time() > $existing['reset_time']) {
                    $data = ['count' => 0, 'reset_time' => time() + $window];
                } else {
                    $data = $existing;
                }
            }
        }
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    
    return true;
}

/**
 * Redirect to challenge page
 */
function shield_redirect_to_challenge($return_url) {
    $challenge_token = bin2hex(random_bytes(16));
    $challenge_time = time();
    
    // Store challenge in session
    $_SESSION['shield_challenge'] = [
        'token' => $challenge_token,
        'time' => $challenge_time,
        'return_url' => $return_url
    ];
    
    // Redirect to challenge page
    $challenge_url = '/shield_challenge.php?t=' . $challenge_token;
    header('Location: ' . $challenge_url);
    exit;
}

/**
 * Log Shield events
 */
function shield_log($event, $ip, $data = []) {
    $log_dir = __DIR__ . '/../logs/';
    
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . 'shield_' . date('Y-m-d') . '.log';
    
    $log_entry = [
        'time' => date('Y-m-d H:i:s'),
        'timestamp' => time(),
        'event' => $event,
        'ip' => $ip,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'data' => $data
    ];
    
    @file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Get IP intelligence (geolocation, organization, etc.)
 * Uses ip-api.com (free, 45 requests/minute)
 */
function shield_get_ip_info($ip) {
    // TEMPORARILY DISABLED - External API calls and DNS lookups cause timeouts
    // Return minimal info to prevent timeout
    return [
        'status' => 'cached',
        'query' => $ip,
        'reverse' => null,
        'country' => 'Unknown',
        'countryCode' => '??',
        'city' => 'Unknown',
        'isp' => 'Unknown',
        'org' => 'Unknown'
    ];
    
    // DISABLED - External API calls and DNS lookups cause timeouts
    // This code is commented out to prevent Gateway Timeout errors
}

/**
 * Get Shield statistics
 */
function shield_get_stats() {
    $log_dir = __DIR__ . '/../logs/';
    $stats = [
        'challenges_today' => 0,
        'verified_today' => 0,
        'blocked_today' => 0,
        'bots_allowed' => 0,
        'bots_blocked' => 0
    ];
    
    $log_file = $log_dir . 'shield_' . date('Y-m-d') . '.log';
    
    if (file_exists($log_file)) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry) continue;
            
            switch ($entry['event'] ?? '') {
                case 'challenge_served':
                    $stats['challenges_today']++;
                    break;
                case 'verified':
                    $stats['verified_today']++;
                    break;
                case 'blocked':
                case 'rate_limited':
                    $stats['blocked_today']++;
                    break;
                case 'bot_allowed':
                    $stats['bots_allowed']++;
                    break;
                case 'bot_unverified':
                    $stats['bots_blocked']++;
                    break;
            }
        }
    }
    
    return $stats;
}

