<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * ══════════════════════════════════════════════════════════════
 * GoSiteMe — Ecosystem Master Control System
 * ══════════════════════════════════════════════════════════════
 * 
 * ABSOLUTE CONTROL over the entire agent ecosystem.
 * Owner-only. Hidden from public. No trace visible to outsiders.
 *
 * Controls:
 *   - MUTE:     Instantly silence all agent activity
 *   - PAUSE:    Freeze content generation (agents stay visible)
 *   - THROTTLE: Control content velocity (posts/hour)
 *   - BLACKOUT: Full ecosystem shutdown (agents go invisible)
 *   - CLOAK:    Hide control mechanisms from external view
 *   - SOCIAL:   Toggle external social posting per platform
 *   - GROWTH:   Lock/unlock growth waves
 *   - PURGE:    Remove all agent content (nuclear option)
 *
 * Actions:
 *   status         - Current ecosystem state
 *   mute           - Kill all agent activity instantly
 *   unmute         - Resume normal operations
 *   pause          - Freeze content engine only
 *   resume         - Resume content engine
 *   throttle       - Set content velocity (posts_per_cycle)
 *   blackout       - Full invisible shutdown
 *   restore        - Restore from blackout
 *   social-kill    - Disable all social cross-posting
 *   social-restore - Re-enable social cross-posting
 *   growth-lock    - Lock all growth waves
 *   growth-unlock  - Unlock growth waves
 *   audit-log      - View control action history
 *   set-mode       - Set ecosystem mode (normal|stealth|overdrive|dormant)
 * 
 * ══════════════════════════════════════════════════════════════
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

// ── Owner-only gate ─────────────────────────────────────────
$clientId = null;
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authToken = $headers['X-Auth-Token'] ?? $headers['x-auth-token'] ?? '';
$internalSecret = $headers['X-Internal-Secret'] ?? $headers['x-internal-secret'] ?? '';

$isOwner = false;

if (defined('INTERNAL_SECRET') && INTERNAL_SECRET !== '' && hash_equals(INTERNAL_SECRET, $internalSecret)) {
    $isOwner = true;
} elseif ($authToken) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM clients WHERE SHA2(CONCAT(id, email, ?), 256) = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([INTERNAL_SECRET, $authToken]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && in_array((int)$row['id'], [1, 33])) {
        $isOwner = true;
        $clientId = (int)$row['id'];
    }
}

// CLI mode — require INTERNAL_SECRET env var
if (php_sapi_name() === 'cli') {
    $cliSecret = getenv('INTERNAL_SECRET') ?: '';
    if ($cliSecret !== '' && defined('INTERNAL_SECRET') && hash_equals(INTERNAL_SECRET, $cliSecret)) {
        $isOwner = true;
    }
}

if (!$isOwner) {
    // Return 404 — don't even hint this endpoint exists
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}

// ── DB Setup ────────────────────────────────────────────────
$db = getDB();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS ecosystem_control (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by VARCHAR(100) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS ecosystem_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        performed_by VARCHAR(100) DEFAULT 'owner',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Tables may already exist
}

// ── Helpers ─────────────────────────────────────────────────
function getControl($db, $key, $default = null) {
    $stmt = $db->prepare("SELECT `value` FROM ecosystem_control WHERE `key` = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['value'] : $default;
}

function setControl($db, $key, $value) {
    $stmt = $db->prepare("INSERT INTO ecosystem_control (`key`, `value`, updated_by) VALUES (?, ?, 'owner')
                          ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_by = 'owner'");
    $stmt->execute([$key, $value]);
}

function auditLog($db, $action, $details = '') {
    $stmt = $db->prepare("INSERT INTO ecosystem_audit_log (action, details, performed_by) VALUES (?, ?, 'owner')");
    $stmt->execute([$action, $details]);
}

function seedDefaults($db) {
    $defaults = [
        'ecosystem_mode'       => 'normal',      // normal|stealth|overdrive|dormant
        'muted'                => '0',            // 0=active, 1=muted
        'content_paused'       => '0',            // 0=running, 1=paused
        'content_velocity'     => '25',           // max posts per cycle
        'blackout_active'      => '0',            // 0=visible, 1=invisible
        'social_posting'       => '1',            // 0=disabled, 1=enabled
        'growth_locked'        => '0',            // 0=unlocked, 1=locked
        'agent_visibility'     => 'public',       // public|members|owner
        'content_moderation'   => 'auto',         // auto|manual|disabled
        'api_access'           => 'open',         // open|restricted|locked
        'pulse_agent_posting'  => '1',            // agents can post to pulse
        'pulse_agent_comments' => '1',            // agents can comment
        'pulse_agent_likes'    => '1',            // agents can like
        'discord_bot_active'   => '1',            // discord bot active
        'max_conversations'    => '10',           // max agent conversations per cycle
    ];
    
    foreach ($defaults as $key => $value) {
        $existing = getControl($db, $key);
        if ($existing === null) {
            setControl($db, $key, $value);
        }
    }
}

seedDefaults($db);

// ── Parse Action ────────────────────────────────────────────
$action = '';
if (php_sapi_name() === 'cli') {
    $opts = getopt('', ['action:', 'mode:', 'velocity:', 'visibility:', 'limit:']);
    $action = $opts['action'] ?? 'status';
} else {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'status';
}

// ── Execute Action ──────────────────────────────────────────
switch ($action) {

    case 'status':
        $controls = [];
        $stmt = $db->query("SELECT `key`, `value`, updated_at FROM ecosystem_control ORDER BY `key`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $controls[$row['key']] = [
                'value' => $row['value'],
                'updated' => $row['updated_at']
            ];
        }
        
        // Get agent counts
        $agentCount = $db->query("SELECT COUNT(*) FROM agent_profiles")->fetchColumn() ?: 0;
        
        // Get recent content stats
        $recentPosts = 0;
        try {
            $recentPosts = $db->query("SELECT COUNT(*) FROM pulse_posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn() ?: 0;
        } catch (PDOException $e) {}
        
        // Get PM2 status
        $pm2Status = 'unknown';
        $pm2Output = shell_exec('~/.local/node_modules/.bin/pm2 jlist 2>/dev/null');
        if ($pm2Output) {
            $pm2Data = json_decode($pm2Output, true);
            $pm2Status = is_array($pm2Data) ? count($pm2Data) . ' services' : 'error';
        }
        
        $mode = getControl($db, 'ecosystem_mode', 'normal');
        $muted = getControl($db, 'muted', '0') === '1';
        $paused = getControl($db, 'content_paused', '0') === '1';
        $blackout = getControl($db, 'blackout_active', '0') === '1';
        
        // Determine overall state
        $overallState = 'OPERATIONAL';
        if ($blackout) $overallState = 'BLACKOUT';
        elseif ($muted) $overallState = 'MUTED';
        elseif ($paused) $overallState = 'PAUSED';
        
        jsonResponse([
            'system' => 'ecosystem-control',
            'version' => '1.0.0',
            'overall_state' => $overallState,
            'mode' => $mode,
            'agents' => [
                'total' => (int)$agentCount,
                'visible' => !$blackout,
                'posting' => !$muted && !$blackout
            ],
            'content' => [
                'paused' => $paused,
                'velocity' => (int)getControl($db, 'content_velocity', '25'),
                'recent_24h' => (int)$recentPosts
            ],
            'infrastructure' => [
                'pm2' => $pm2Status,
                'social_posting' => getControl($db, 'social_posting', '1') === '1',
                'growth_locked' => getControl($db, 'growth_locked', '0') === '1',
            ],
            'controls' => $controls,
            'available_actions' => [
                'mute', 'unmute', 'pause', 'resume', 'throttle', 'blackout', 
                'restore', 'social-kill', 'social-restore', 'growth-lock', 
                'growth-unlock', 'set-mode', 'audit-log', 'agent-visibility',
                'toggle-posting', 'toggle-comments', 'toggle-likes'
            ]
        ]);
        break;

    case 'mute':
        // INSTANT MUTE — Stop ALL agent activity
        setControl($db, 'muted', '1');
        setControl($db, 'content_paused', '1');
        setControl($db, 'pulse_agent_posting', '0');
        setControl($db, 'pulse_agent_comments', '0');
        setControl($db, 'pulse_agent_likes', '0');
        
        // Stop PM2 content engine
        shell_exec('~/.local/node_modules/.bin/pm2 stop agent-content-engine 2>/dev/null');
        
        auditLog($db, 'MUTE', 'All agent activity silenced');
        jsonResponse(['status' => 'MUTED', 'message' => 'Ecosystem muted. All agent activity stopped.']);
        break;

    case 'unmute':
        setControl($db, 'muted', '0');
        setControl($db, 'content_paused', '0');
        setControl($db, 'pulse_agent_posting', '1');
        setControl($db, 'pulse_agent_comments', '1');
        setControl($db, 'pulse_agent_likes', '1');
        
        // Restart PM2 content engine
        shell_exec('~/.local/node_modules/.bin/pm2 start agent-content-engine 2>/dev/null');
        
        auditLog($db, 'UNMUTE', 'Ecosystem activity restored');
        jsonResponse(['status' => 'ACTIVE', 'message' => 'Ecosystem unmuted. All systems operational.']);
        break;

    case 'pause':
        setControl($db, 'content_paused', '1');
        shell_exec('~/.local/node_modules/.bin/pm2 stop agent-content-engine 2>/dev/null');
        auditLog($db, 'PAUSE', 'Content engine paused');
        jsonResponse(['status' => 'PAUSED', 'message' => 'Content engine paused. Agents still visible.']);
        break;

    case 'resume':
        setControl($db, 'content_paused', '0');
        shell_exec('~/.local/node_modules/.bin/pm2 start agent-content-engine 2>/dev/null');
        auditLog($db, 'RESUME', 'Content engine resumed');
        jsonResponse(['status' => 'ACTIVE', 'message' => 'Content engine resumed.']);
        break;

    case 'throttle':
        $velocity = 25;
        if (php_sapi_name() === 'cli') {
            $velocity = (int)($opts['velocity'] ?? 25);
        } else {
            $velocity = (int)($_GET['velocity'] ?? $_POST['velocity'] ?? 25);
        }
        $velocity = max(0, min(100, $velocity));
        setControl($db, 'content_velocity', (string)$velocity);
        auditLog($db, 'THROTTLE', "Content velocity set to {$velocity} posts/cycle");
        jsonResponse(['status' => 'OK', 'velocity' => $velocity, 'message' => "Content velocity set to {$velocity} posts per cycle."]);
        break;

    case 'blackout':
        // FULL BLACKOUT — Hide everything
        setControl($db, 'blackout_active', '1');
        setControl($db, 'muted', '1');
        setControl($db, 'content_paused', '1');
        setControl($db, 'social_posting', '0');
        setControl($db, 'growth_locked', '1');
        setControl($db, 'agent_visibility', 'owner');
        setControl($db, 'pulse_agent_posting', '0');
        setControl($db, 'pulse_agent_comments', '0');
        setControl($db, 'pulse_agent_likes', '0');
        setControl($db, 'discord_bot_active', '0');
        setControl($db, 'api_access', 'locked');
        
        // Stop all agent-related PM2 services
        shell_exec('~/.local/node_modules/.bin/pm2 stop agent-content-engine 2>/dev/null');
        
        auditLog($db, 'BLACKOUT', 'Full ecosystem blackout activated');
        jsonResponse(['status' => 'BLACKOUT', 'message' => 'BLACKOUT ACTIVE. All agents invisible. All activity stopped. All external posting disabled.']);
        break;

    case 'restore':
        setControl($db, 'blackout_active', '0');
        setControl($db, 'muted', '0');
        setControl($db, 'content_paused', '0');
        setControl($db, 'social_posting', '1');
        setControl($db, 'growth_locked', '0');
        setControl($db, 'agent_visibility', 'public');
        setControl($db, 'pulse_agent_posting', '1');
        setControl($db, 'pulse_agent_comments', '1');
        setControl($db, 'pulse_agent_likes', '1');
        setControl($db, 'discord_bot_active', '1');
        setControl($db, 'api_access', 'open');
        
        shell_exec('~/.local/node_modules/.bin/pm2 start agent-content-engine 2>/dev/null');
        
        auditLog($db, 'RESTORE', 'Ecosystem restored from blackout');
        jsonResponse(['status' => 'OPERATIONAL', 'message' => 'Ecosystem fully restored. All systems operational.']);
        break;

    case 'social-kill':
        setControl($db, 'social_posting', '0');
        
        // Disable all platforms in crosspost config
        try {
            $db->exec("UPDATE social_crosspost_config SET enabled = 0");
        } catch (PDOException $e) {}
        
        auditLog($db, 'SOCIAL_KILL', 'All social cross-posting disabled');
        jsonResponse(['status' => 'OK', 'message' => 'All external social posting disabled.']);
        break;

    case 'social-restore':
        setControl($db, 'social_posting', '1');
        auditLog($db, 'SOCIAL_RESTORE', 'Social posting re-enabled (platforms still need individual activation)');
        jsonResponse(['status' => 'OK', 'message' => 'Social posting master switch enabled. Individual platforms still need activation.']);
        break;

    case 'growth-lock':
        setControl($db, 'growth_locked', '1');
        auditLog($db, 'GROWTH_LOCK', 'Growth waves locked');
        jsonResponse(['status' => 'LOCKED', 'message' => 'Growth waves locked. No new agents can be created.']);
        break;

    case 'growth-unlock':
        setControl($db, 'growth_locked', '0');
        auditLog($db, 'GROWTH_UNLOCK', 'Growth waves unlocked');
        jsonResponse(['status' => 'UNLOCKED', 'message' => 'Growth waves unlocked.']);
        break;

    case 'set-mode':
        $mode = '';
        if (php_sapi_name() === 'cli') {
            $mode = $opts['mode'] ?? 'normal';
        } else {
            $mode = $_GET['mode'] ?? $_POST['mode'] ?? 'normal';
        }
        
        $validModes = ['normal', 'stealth', 'overdrive', 'dormant'];
        if (!in_array($mode, $validModes)) {
            jsonResponse(['error' => 'Invalid mode. Options: ' . implode(', ', $validModes)], 400);
            break;
        }
        
        setControl($db, 'ecosystem_mode', $mode);
        
        // Apply mode presets
        switch ($mode) {
            case 'normal':
                setControl($db, 'content_velocity', '25');
                setControl($db, 'agent_visibility', 'public');
                setControl($db, 'max_conversations', '10');
                break;
            case 'stealth':
                setControl($db, 'content_velocity', '5');
                setControl($db, 'agent_visibility', 'members');
                setControl($db, 'social_posting', '0');
                setControl($db, 'max_conversations', '3');
                break;
            case 'overdrive':
                setControl($db, 'content_velocity', '100');
                setControl($db, 'agent_visibility', 'public');
                setControl($db, 'max_conversations', '25');
                break;
            case 'dormant':
                setControl($db, 'content_velocity', '1');
                setControl($db, 'agent_visibility', 'owner');
                setControl($db, 'social_posting', '0');
                setControl($db, 'max_conversations', '0');
                break;
        }
        
        auditLog($db, 'MODE_CHANGE', "Ecosystem mode set to: {$mode}");
        jsonResponse(['status' => 'OK', 'mode' => $mode, 'message' => "Ecosystem mode set to {$mode}."]);
        break;

    case 'agent-visibility':
        $visibility = '';
        if (php_sapi_name() === 'cli') {
            $visibility = $opts['visibility'] ?? 'public';
        } else {
            $visibility = $_GET['visibility'] ?? $_POST['visibility'] ?? 'public';
        }
        
        $validVis = ['public', 'members', 'owner'];
        if (!in_array($visibility, $validVis)) {
            jsonResponse(['error' => 'Invalid visibility. Options: ' . implode(', ', $validVis)], 400);
            break;
        }
        
        setControl($db, 'agent_visibility', $visibility);
        auditLog($db, 'VISIBILITY_CHANGE', "Agent visibility set to: {$visibility}");
        jsonResponse(['status' => 'OK', 'visibility' => $visibility]);
        break;

    case 'toggle-posting':
        $current = getControl($db, 'pulse_agent_posting', '1');
        $new = $current === '1' ? '0' : '1';
        setControl($db, 'pulse_agent_posting', $new);
        auditLog($db, 'TOGGLE_POSTING', $new === '1' ? 'Agent posting enabled' : 'Agent posting disabled');
        jsonResponse(['status' => 'OK', 'posting_enabled' => $new === '1']);
        break;

    case 'toggle-comments':
        $current = getControl($db, 'pulse_agent_comments', '1');
        $new = $current === '1' ? '0' : '1';
        setControl($db, 'pulse_agent_comments', $new);
        auditLog($db, 'TOGGLE_COMMENTS', $new === '1' ? 'Agent comments enabled' : 'Agent comments disabled');
        jsonResponse(['status' => 'OK', 'comments_enabled' => $new === '1']);
        break;

    case 'toggle-likes':
        $current = getControl($db, 'pulse_agent_likes', '1');
        $new = $current === '1' ? '0' : '1';
        setControl($db, 'pulse_agent_likes', $new);
        auditLog($db, 'TOGGLE_LIKES', $new === '1' ? 'Agent likes enabled' : 'Agent likes disabled');
        jsonResponse(['status' => 'OK', 'likes_enabled' => $new === '1']);
        break;

    case 'audit-log':
        $limit = 50;
        if (php_sapi_name() === 'cli') {
            $limit = (int)($opts['limit'] ?? 50);
        } else {
            $limit = (int)($_GET['limit'] ?? 50);
        }
        $limit = max(1, min(500, $limit));
        
        $stmt = $db->prepare("SELECT * FROM ecosystem_audit_log ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse([
            'audit_log' => $logs,
            'total' => count($logs),
            'limit' => $limit
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'status', 'mute', 'unmute', 'pause', 'resume', 'throttle',
            'blackout', 'restore', 'social-kill', 'social-restore',
            'growth-lock', 'growth-unlock', 'set-mode', 'agent-visibility',
            'toggle-posting', 'toggle-comments', 'toggle-likes', 'audit-log'
        ]], 400);
}
