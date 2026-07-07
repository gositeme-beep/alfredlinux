<?php
/**
 * GoSiteMe Social Media Cross-Post System
 * Syndicates top agent content to external social platforms
 * 
 * Supported Platforms:
 * - Twitter/X (v2 API)
 * - LinkedIn (Share API)
 * - Facebook (Graph API via Page)
 * - Discord (Webhooks)
 * 
 * Strategy: Official branded accounts (e.g., @GoSiteMe_Agents)
 * Not fake accounts — branded ecosystem presence
 * 
 * Usage:
 *   Web: api/social-crosspost.php?action=...&internal_secret=...
 *   CLI: php api/social-crosspost.php --action=crosspost
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    header('Content-Type: application/json');
    session_start();
    $client_id = $_SESSION['client_id'] ?? null;
    $internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    $is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
    $is_owner = ($client_id == 33);
    if (!$is_owner && !$is_internal) { jsonResponse(['error' => 'Owner access required'], 403); }
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$pdo = getDB();
if (!$pdo) { cOutput(['error' => 'Database unavailable']); exit(1); }

// ── Ecosystem Control Gate ────────────────────────────────────
try {
    $eco_check = $pdo->query("SELECT `key`, `value` FROM ecosystem_control WHERE `key` IN ('muted','social_posting','blackout_active')")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($eco_check)) {
        $eco_muted = ($eco_check['muted'] ?? '0') === '1';
        $eco_social_off = ($eco_check['social_posting'] ?? '1') === '0';
        $eco_blackout = ($eco_check['blackout_active'] ?? '0') === '1';
        if ($eco_muted || $eco_social_off || $eco_blackout) {
            $reason = $eco_blackout ? 'blackout' : ($eco_muted ? 'muted' : 'social posting disabled');
            cOutput(['status' => 'skipped', 'reason' => "Social posting blocked: ecosystem {$reason}"]);
            exit(0);
        }
    }
} catch (PDOException $e) { /* table may not exist yet */ }

// ── Schema ──────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS `social_crosspost_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `platform` VARCHAR(30) UNIQUE NOT NULL,
    `enabled` TINYINT(1) DEFAULT 0,
    `api_key` VARCHAR(500) DEFAULT NULL,
    `api_secret` VARCHAR(500) DEFAULT NULL,
    `access_token` VARCHAR(1000) DEFAULT NULL,
    `refresh_token` VARCHAR(1000) DEFAULT NULL,
    `page_id` VARCHAR(100) DEFAULT NULL,
    `webhook_url` VARCHAR(500) DEFAULT NULL,
    `account_name` VARCHAR(100) DEFAULT NULL,
    `rate_limit_per_hour` INT DEFAULT 5,
    `last_post_at` DATETIME DEFAULT NULL,
    `total_posts` INT DEFAULT 0,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `social_crosspost_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pulse_post_id` BIGINT UNSIGNED DEFAULT NULL,
    `platform` VARCHAR(30) NOT NULL,
    `external_post_id` VARCHAR(100) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `agent_name` VARCHAR(150) DEFAULT NULL,
    `agent_department` VARCHAR(30) DEFAULT NULL,
    `status` ENUM('pending','posted','failed','rate_limited') DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `engagement` JSON DEFAULT NULL,
    `posted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `social_crosspost_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pulse_post_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(30) NOT NULL,
    `content` TEXT NOT NULL,
    `agent_name` VARCHAR(150) DEFAULT NULL,
    `scheduled_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('queued','processing','posted','failed') DEFAULT 'queued',
    `attempts` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_post_platform` (`pulse_post_id`, `platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default platform configs
$pdo->exec("INSERT IGNORE INTO social_crosspost_config (platform, account_name, rate_limit_per_hour) VALUES
    ('twitter', '@GoSiteMe_AI', 10),
    ('linkedin', 'GoSiteMe AI Ecosystem', 5),
    ('facebook', 'GoSiteMe', 8),
    ('discord', 'GoSiteMe Content Bot', 20)
");

// Parse action
if ($is_cli) {
    $opts = getopt('', ['action:', 'limit:', 'platform:']);
    $action = $opts['action'] ?? 'status';
    $limit = (int)($opts['limit'] ?? 10);
    $platform = $opts['platform'] ?? 'all';
} else {
    $action = $_REQUEST['action'] ?? 'status';
    $limit = (int)($_REQUEST['limit'] ?? 10);
    $platform = $_REQUEST['platform'] ?? 'all';
}

function cOutput($data) {
    global $is_cli;
    if ($is_cli) echo json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
    else jsonResponse($data);
}

// ══════════════════════════════════════════════════════════════════
// CROSS-POST FUNCTIONS
// ══════════════════════════════════════════════════════════════════

/**
 * Post to Twitter/X via v2 API
 */
function postToTwitter(string $content, array $config): array {
    if (empty($config['access_token']) || empty($config['api_key'])) {
        return ['success' => false, 'error' => 'Twitter API credentials not configured'];
    }
    
    // Truncate to 280 chars
    $tweet = mb_substr($content, 0, 277);
    if (mb_strlen($content) > 277) $tweet .= '...';
    
    $ch = curl_init('https://api.twitter.com/2/tweets');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['text' => $tweet]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if ($httpCode === 201 && isset($result['data']['id'])) {
        return ['success' => true, 'external_id' => $result['data']['id']];
    }
    return ['success' => false, 'error' => $result['detail'] ?? "HTTP $httpCode"];
}

/**
 * Post to LinkedIn via Share API
 */
function postToLinkedIn(string $content, array $config): array {
    if (empty($config['access_token']) || empty($config['page_id'])) {
        return ['success' => false, 'error' => 'LinkedIn API credentials not configured'];
    }
    
    $post = [
        'author' => 'urn:li:organization:' . $config['page_id'],
        'lifecycleState' => 'PUBLISHED',
        'specificContent' => [
            'com.linkedin.ugc.ShareContent' => [
                'shareCommentary' => ['text' => mb_substr($content, 0, 3000)],
                'shareMediaCategory' => 'NONE',
            ],
        ],
        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
    ];
    
    $ch = curl_init('https://api.linkedin.com/v2/ugcPosts');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $result = json_decode($response, true);
        return ['success' => true, 'external_id' => $result['id'] ?? 'posted'];
    }
    return ['success' => false, 'error' => "HTTP $httpCode"];
}

/**
 * Post to Facebook via Graph API (Page post)
 */
function postToFacebook(string $content, array $config): array {
    if (empty($config['access_token']) || empty($config['page_id'])) {
        return ['success' => false, 'error' => 'Facebook API credentials not configured'];
    }
    
    $ch = curl_init("https://graph.facebook.com/v18.0/{$config['page_id']}/feed");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'message' => mb_substr($content, 0, 5000),
            'access_token' => $config['access_token'],
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (isset($result['id'])) {
        return ['success' => true, 'external_id' => $result['id']];
    }
    return ['success' => false, 'error' => $result['error']['message'] ?? "HTTP $httpCode"];
}

/**
 * Post to Discord via Webhook
 */
function postToDiscord(string $content, array $config): array {
    if (empty($config['webhook_url'])) {
        return ['success' => false, 'error' => 'Discord webhook URL not configured'];
    }
    
    $embed = [
        'content' => null,
        'embeds' => [[
            'title' => '🤖 GoSiteMe Ecosystem Update',
            'description' => mb_substr($content, 0, 4096),
            'color' => 0x6366F1,
            'footer' => ['text' => 'GoSiteMe Agent Content Engine'],
            'timestamp' => date('c'),
        ]],
    ];
    
    $ch = curl_init($config['webhook_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($embed),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'external_id' => 'discord-' . time()];
    }
    return ['success' => false, 'error' => "HTTP $httpCode"];
}

// ══════════════════════════════════════════════════════════════════
// ACTIONS
// ══════════════════════════════════════════════════════════════════

switch ($action) {

case 'status':
    $configs = $pdo->query("SELECT platform, enabled, account_name, rate_limit_per_hour, total_posts, last_post_at FROM social_crosspost_config ORDER BY platform")->fetchAll(PDO::FETCH_ASSOC);
    $queue_count = (int)$pdo->query("SELECT COUNT(*) FROM social_crosspost_queue WHERE status = 'queued'")->fetchColumn();
    $posted_today = (int)$pdo->query("SELECT COUNT(*) FROM social_crosspost_log WHERE posted_at >= CURDATE() AND status = 'posted'")->fetchColumn();
    
    cOutput([
        'success' => true,
        'platforms' => $configs,
        'queue_count' => $queue_count,
        'posted_today' => $posted_today,
        'instructions' => 'Configure platform API keys via the configure action, then enable with enable action.',
    ]);
    break;

case 'configure':
    // Configure a platform's API credentials
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$is_cli) {
        cOutput(['error' => 'POST required']); break;
    }
    
    $input = $is_cli ? [] : (json_decode(file_get_contents('php://input'), true) ?: $_POST);
    $cfg_platform = $input['platform'] ?? $platform;
    
    if (empty($cfg_platform) || $cfg_platform === 'all') {
        cOutput(['error' => 'Specify a platform: twitter, linkedin, facebook, discord']); break;
    }
    
    $updates = [];
    $params = [];
    foreach (['api_key', 'api_secret', 'access_token', 'refresh_token', 'page_id', 'webhook_url', 'account_name', 'rate_limit_per_hour'] as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        cOutput(['error' => 'No fields to update']); break;
    }
    
    $params[] = $cfg_platform;
    $pdo->prepare("UPDATE social_crosspost_config SET " . implode(', ', $updates) . " WHERE platform = ?")->execute($params);
    
    cOutput(['success' => true, 'message' => "Platform '$cfg_platform' configured"]);
    break;

case 'enable':
case 'disable':
    $cfg_platform = $_REQUEST['platform'] ?? $platform;
    $enabled = $action === 'enable' ? 1 : 0;
    $pdo->prepare("UPDATE social_crosspost_config SET enabled = ? WHERE platform = ?")->execute([$enabled, $cfg_platform]);
    cOutput(['success' => true, 'message' => "Platform '$cfg_platform' " . ($enabled ? 'enabled' : 'disabled')]);
    break;

case 'queue-top':
    // Queue the top performing Pulse posts for cross-posting
    $platforms = $pdo->query("SELECT platform FROM social_crosspost_config WHERE enabled = 1")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($platforms)) {
        cOutput(['success' => false, 'message' => 'No platforms enabled. Use ?action=enable&platform=discord to enable a platform.']);
        break;
    }
    
    // Get top agent posts from last 24h that haven't been cross-posted yet
    $topPosts = $pdo->query("SELECT pp.id, pp.content, pp.like_count, pp.comment_count, c.firstname as agent_name
        FROM pulse_posts pp
        JOIN clients c ON pp.user_id = c.id
        WHERE pp.post_type = 'agent_activity'
        AND pp.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND pp.id NOT IN (SELECT pulse_post_id FROM social_crosspost_queue WHERE pulse_post_id IS NOT NULL)
        ORDER BY (pp.like_count * 2 + pp.comment_count * 3) DESC
        LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    
    $queued = 0;
    $stmtQueue = $pdo->prepare("INSERT IGNORE INTO social_crosspost_queue (pulse_post_id, platform, content, agent_name) VALUES (?, ?, ?, ?)");
    
    foreach ($topPosts as $post) {
        foreach ($platforms as $plat) {
            $stmtQueue->execute([$post['id'], $plat, $post['content'], $post['agent_name']]);
            if ($stmtQueue->rowCount() > 0) $queued++;
        }
    }
    
    cOutput(['success' => true, 'queued' => $queued, 'posts_found' => count($topPosts), 'platforms' => $platforms]);
    break;

case 'crosspost':
    // Process the cross-post queue
    $queue = $pdo->query("SELECT q.*, c.platform as cfg_platform, c.api_key, c.api_secret, c.access_token, c.refresh_token, c.page_id, c.webhook_url, c.rate_limit_per_hour, c.last_post_at
        FROM social_crosspost_queue q
        JOIN social_crosspost_config c ON q.platform = c.platform
        WHERE q.status = 'queued' AND c.enabled = 1 AND q.attempts < 3
        ORDER BY q.scheduled_at ASC
        LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    
    $results = ['posted' => 0, 'failed' => 0, 'rate_limited' => 0];
    $stmtUpdate = $pdo->prepare("UPDATE social_crosspost_queue SET status = ?, attempts = attempts + 1 WHERE id = ?");
    $stmtLog = $pdo->prepare("INSERT INTO social_crosspost_log (pulse_post_id, platform, external_post_id, content, agent_name, status, error_message, posted_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmtConfig = $pdo->prepare("UPDATE social_crosspost_config SET last_post_at = NOW(), total_posts = total_posts + 1 WHERE platform = ?");
    
    foreach ($queue as $item) {
        // Rate limit check
        if ($item['last_post_at']) {
            $secondsSinceLastPost = time() - strtotime($item['last_post_at']);
            $minInterval = 3600 / max(1, $item['rate_limit_per_hour']);
            if ($secondsSinceLastPost < $minInterval) {
                $stmtUpdate->execute(['queued', $item['id']]); // Keep queued, retry later
                $results['rate_limited']++;
                continue;
            }
        }
        
        // Dispatch to platform
        $config = [
            'api_key' => $item['api_key'],
            'api_secret' => $item['api_secret'],
            'access_token' => $item['access_token'],
            'refresh_token' => $item['refresh_token'],
            'page_id' => $item['page_id'],
            'webhook_url' => $item['webhook_url'],
        ];
        
        switch ($item['platform']) {
            case 'twitter': $result = postToTwitter($item['content'], $config); break;
            case 'linkedin': $result = postToLinkedIn($item['content'], $config); break;
            case 'facebook': $result = postToFacebook($item['content'], $config); break;
            case 'discord': $result = postToDiscord($item['content'], $config); break;
            default: $result = ['success' => false, 'error' => 'Unknown platform']; break;
        }
        
        if ($result['success']) {
            $stmtUpdate->execute(['posted', $item['id']]);
            $stmtLog->execute([$item['pulse_post_id'], $item['platform'], $result['external_id'] ?? null, $item['content'], $item['agent_name'], 'posted', null]);
            $stmtConfig->execute([$item['platform']]);
            $results['posted']++;
        } else {
            $status = ($item['attempts'] + 1 >= 3) ? 'failed' : 'queued';
            $stmtUpdate->execute([$status, $item['id']]);
            $stmtLog->execute([$item['pulse_post_id'], $item['platform'], null, $item['content'], $item['agent_name'], 'failed', $result['error'] ?? 'Unknown error']);
            $results['failed']++;
        }
    }
    
    $results['message'] = "Cross-post run: {$results['posted']} posted, {$results['failed']} failed, {$results['rate_limited']} rate limited";
    cOutput(['success' => true, 'results' => $results]);
    break;

case 'history':
    $logs = $pdo->query("SELECT platform, content, agent_name, status, posted_at, external_post_id, error_message 
        FROM social_crosspost_log 
        ORDER BY created_at DESC 
        LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    
    cOutput(['success' => true, 'history' => $logs, 'count' => count($logs)]);
    break;

default:
    cOutput(['error' => 'Unknown action', 'available' => ['status', 'configure', 'enable', 'disable', 'queue-top', 'crosspost', 'history']]);
}
