<?php
/**
 * SECURITY MONITOR for gositeme.com
 * Logs all suspicious activity
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * Analyze request for suspicious patterns and log threats
 */
function security_monitor_request() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $query_string = $_SERVER['QUERY_STRING'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    $threats = [];
    $threat_level = 0; // 0=none, 1=low, 2=medium, 3=high, 4=critical
    
    // ==========================================
    // SQL INJECTION DETECTION
    // ==========================================
    $sql_patterns = [
        '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
        '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',
        '/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i',
        '/((\%27)|(\'))union/i',
        '/exec(\s|\+)+(s|x)p\w+/i',
        '/UNION(\s+)SELECT/i',
        '/INSERT(\s+)INTO/i',
        '/DELETE(\s+)FROM/i',
        '/DROP(\s+)TABLE/i',
        '/UPDATE(\s+)\w+(\s+)SET/i',
        '/SLEEP\s*\(/i',
        '/BENCHMARK\s*\(/i',
        '/LOAD_FILE\s*\(/i',
        '/INTO\s+OUTFILE/i',
        '/INTO\s+DUMPFILE/i',
    ];
    
    $check_string = $uri . ' ' . $query_string;
    foreach ($sql_patterns as $pattern) {
        if (preg_match($pattern, $check_string)) {
            $threats[] = ['type' => 'SQL_INJECTION', 'pattern' => $pattern, 'severity' => 'critical'];
            $threat_level = max($threat_level, 4);
        }
    }
    
    // ==========================================
    // XSS DETECTION
    // ==========================================
    $xss_patterns = [
        '/<script[^>]*>.*<\/script>/i',
        '/<script/i',
        '/javascript:/i',
        '/vbscript:/i',
        '/on\w+\s*=/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/<svg[^>]*onload/i',
        '/document\.cookie/i',
        '/document\.location/i',
        '/window\.location/i',
        '/eval\s*\(/i',
        '/expression\s*\(/i',
    ];
    
    foreach ($xss_patterns as $pattern) {
        if (preg_match($pattern, urldecode($check_string))) {
            $threats[] = ['type' => 'XSS_ATTEMPT', 'pattern' => $pattern, 'severity' => 'high'];
            $threat_level = max($threat_level, 3);
        }
    }
    
    // ==========================================
    // PATH TRAVERSAL DETECTION
    // ==========================================
    $path_patterns = [
        '/\.\.\//i',
        '/\.\.\\\/i',
        '/%2e%2e%2f/i',
        '/%2e%2e\//i',
        '/\.%2e\//i',
        '/%2e\.\//i',
        '/etc\/passwd/i',
        '/etc\/shadow/i',
        '/proc\/self/i',
        '/var\/log/i',
        '/windows\/system32/i',
    ];
    
    foreach ($path_patterns as $pattern) {
        if (preg_match($pattern, $check_string)) {
            $threats[] = ['type' => 'PATH_TRAVERSAL', 'pattern' => $pattern, 'severity' => 'high'];
            $threat_level = max($threat_level, 3);
        }
    }
    
    // ==========================================
    // SUSPICIOUS USER AGENTS
    // ==========================================
    $bad_agents = [
        '/sqlmap/i',
        '/nikto/i',
        '/nmap/i',
        '/masscan/i',
        '/ZmEu/i',
        '/python-requests/i',
        '/python-urllib/i',
        '/curl\/\d/i',
        '/wget/i',
        '/libwww-perl/i',
        '/lwp-trivial/i',
        '/scanner/i',
        '/exploit/i',
        '/attack/i',
        '/havij/i',
        '/sqlninja/i',
        '/acunetix/i',
        '/nessus/i',
        '/burpsuite/i',
        '/dirbuster/i',
        '/gobuster/i',
        '/wfuzz/i',
        '/ffuf/i',
    ];
    
    foreach ($bad_agents as $pattern) {
        if (preg_match($pattern, $user_agent)) {
            $threats[] = ['type' => 'MALICIOUS_USER_AGENT', 'agent' => $user_agent, 'severity' => 'high'];
            $threat_level = max($threat_level, 3);
        }
    }
    
    // Empty user agent (often bots)
    if (empty(trim($user_agent))) {
        $threats[] = ['type' => 'EMPTY_USER_AGENT', 'severity' => 'low'];
        $threat_level = max($threat_level, 1);
    }
    
    // ==========================================
    // RAPID REQUEST DETECTION (basic)
    // ==========================================
    $rate_key = 'security_rate_' . md5($ip);
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = ['count' => 0, 'start' => time()];
    }
    
    $rate = &$_SESSION[$rate_key];
    if (time() - $rate['start'] > 60) {
        $rate = ['count' => 0, 'start' => time()];
    }
    $rate['count']++;
    
    if ($rate['count'] > 100) { // More than 100 requests per minute
        $threats[] = ['type' => 'RAPID_REQUESTS', 'count' => $rate['count'], 'severity' => 'medium'];
        $threat_level = max($threat_level, 2);
    }
    
    // ==========================================
    // LOG IF ANY THREATS DETECTED
    // ==========================================
    if (!empty($threats)) {
        security_log_threat($ip, $uri, $method, $user_agent, $referer, $threats, $threat_level);
    }
    
    return [
        'has_threats' => !empty($threats),
        'threats' => $threats,
        'threat_level' => $threat_level
    ];
}

/**
 * Log threat to file
 */
function security_log_threat($ip, $uri, $method, $user_agent, $referer, $threats, $threat_level) {
    $log_dir = dirname(__DIR__) . '/logs/';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $level_names = ['none', 'low', 'medium', 'high', 'critical'];
    
    $log_entry = [
        'time' => date('Y-m-d H:i:s'),
        'ip' => $ip,
        'uri' => $uri,
        'method' => $method,
        'user_agent' => $user_agent,
        'referer' => $referer,
        'threats' => $threats,
        'level' => $level_names[$threat_level] ?? 'unknown'
    ];
    
    $log_file = $log_dir . 'security_threats_' . date('Y-m-d') . '.log';
    @file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Get recent security threats from log files
 */
function security_get_threats($limit = 100, $min_level = 1) {
    $log_dir = dirname(__DIR__) . '/logs/';
    $level_values = ['none' => 0, 'low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
    $threats = [];
    
    $log_file = $log_dir . 'security_threats_' . date('Y-m-d') . '.log';
    
    if (file_exists($log_file)) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines); // Most recent first
        
        foreach ($lines as $line) {
            if (count($threats) >= $limit) break;
            
            $entry = json_decode($line, true);
            if (!$entry) continue;
            
            $entry_level = $level_values[$entry['level'] ?? 'none'] ?? 0;
            if ($entry_level >= $min_level) {
                $threats[] = $entry;
            }
        }
    }
    
    return $threats;
}

/**
 * Get threat statistics
 */
function security_get_threat_stats() {
    $log_dir = dirname(__DIR__) . '/logs/';
    $log_file = $log_dir . 'security_threats_' . date('Y-m-d') . '.log';
    
    $stats = [
        'total_today' => 0,
        'critical_today' => 0,
        'today_by_level' => [],
        'top_attackers' => []
    ];
    
    if (!file_exists($log_file)) {
        return $stats;
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $level_counts = [];
    $ip_counts = [];
    
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry) continue;
        
        $stats['total_today']++;
        
        $level = $entry['level'] ?? 'unknown';
        $level_counts[$level] = ($level_counts[$level] ?? 0) + 1;
        
        if ($level === 'critical') {
            $stats['critical_today']++;
        }
        
        $ip = $entry['ip'] ?? 'unknown';
        if (!isset($ip_counts[$ip])) {
            $ip_counts[$ip] = ['count' => 0, 'max_level' => 0];
        }
        $ip_counts[$ip]['count']++;
    }
    
    // Format level counts
    foreach ($level_counts as $level => $count) {
        $stats['today_by_level'][] = [
            'threat_level_name' => $level,
            'count' => $count
        ];
    }
    
    // Get top attackers
    arsort($ip_counts);
    foreach (array_slice($ip_counts, 0, 10, true) as $ip => $data) {
        $stats['top_attackers'][] = [
            'ip_address' => $ip,
            'count' => $data['count'],
            'country' => 'Unknown',
            'org' => 'Unknown',
            'max_level' => 3
        ];
    }
    
    return $stats;
}

// Run the monitor
security_monitor_request();

