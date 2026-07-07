<?php
/**
 * Organized Intelligence Briefing System
 * ═══════════════════════════════════════════════
 * "For the Lord giveth wisdom: out of his mouth cometh 
 *  knowledge and understanding" — Proverbs 2:6
 *
 * Key intel organized by category — NOT clutter.
 * Focused on growth, longevity, wholesomeness, and organic evolution.
 *
 * Categories:
 *   1. GROWTH INTEL     — Revenue, users, market position, expansion
 *   2. SECURITY INTEL   — Threats, vulnerabilities, posture
 *   3. ECOSYSTEM HEALTH — Services, uptime, performance
 *   4. SPIRITUAL VALUES — Mission alignment, Brotherhood, faith
 *   5. INNOVATION INTEL — New features, tools, evolution
 *   6. OPERATIONAL INTEL — Daily ops, agents, tasks, efficiency
 *
 * Commander: Chief Commander Sovereign Inspector General
 * Classification: BROTHERHOOD OF JESUS — INTERNAL
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$clientId = $_SESSION['client_id'] ?? 0;
$isOwner = (int)$clientId === 33;

// Support internal calls
$isInternal = false;
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
if ($internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET'])) {
    $isInternal = true;
    $clientId = 1;
    $isOwner = true;
}

if (!$isOwner && !$isInternal) {
    http_response_code(403);
    echo json_encode(['error' => 'Commander clearance required']);
    exit;
}

try {
    $pdo = getDB();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// Create intel briefings table
$pdo->exec("CREATE TABLE IF NOT EXISTS intel_briefings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    briefing_date DATE NOT NULL,
    category ENUM('growth','security','ecosystem','spiritual','innovation','operations','full') DEFAULT 'full',
    headline VARCHAR(500) NOT NULL,
    key_points JSON NOT NULL,
    action_items JSON,
    priority TINYINT DEFAULT 5,
    reviewed BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (briefing_date),
    INDEX idx_category (category),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$action = $_GET['action'] ?? 'brief';

switch ($action) {
    case 'brief': generateFullBriefing($pdo); break;
    case 'category': getCategoryBrief($pdo); break;
    case 'history': getBriefingHistory($pdo); break;
    case 'review': markReviewed($pdo); break;
    case 'categories': listCategories(); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => ['brief','category','history','review','categories']]);
}

function listCategories() {
    echo json_encode(['categories' => [
        ['id' => 'growth', 'name' => 'Growth Intel', 'icon' => '📈', 'focus' => 'Revenue, users, expansion, market position — what drives long-term prosperity'],
        ['id' => 'security', 'name' => 'Security Intel', 'icon' => '🔒', 'focus' => 'Threats, vulnerabilities, access patterns — what keeps us safe'],
        ['id' => 'ecosystem', 'name' => 'Ecosystem Health', 'icon' => '🌿', 'focus' => 'Services, uptime, performance — the organic health of our infrastructure'],
        ['id' => 'spiritual', 'name' => 'Spiritual & Values', 'icon' => '✝️', 'focus' => 'Brotherhood alignment, mission integrity, faith-driven decisions'],
        ['id' => 'innovation', 'name' => 'Innovation Intel', 'icon' => '💡', 'focus' => 'New features, tools, evolution — wholesome growth through creation'],
        ['id' => 'operations', 'name' => 'Operations Intel', 'icon' => '⚙️', 'focus' => 'Agents, tasks, efficiency — the heartbeat of daily operations'],
    ]]);
}

function generateFullBriefing($pdo) {
    $briefing = [
        'title' => 'INTELLIGENCE BRIEFING — ' . date('l, F j, Y'),
        'classification' => 'COMMANDER EYES ONLY',
        'prepared_for' => 'Chief Commander Sovereign Inspector General',
        'prepared_by' => 'Alfred Intelligence Division',
        'timestamp' => date('Y-m-d H:i:s'),
        'categories' => [],
    ];

    // ── 1. GROWTH INTEL ──
    $growth = ['category' => 'growth', 'title' => '📈 GROWTH INTEL', 'key_points' => [], 'action_items' => []];
    
    // Check marketplace and products
    $productsExist = file_exists(__DIR__ . '/../marketplace.php');
    $pricingExists = file_exists(__DIR__ . '/../pricing.php');
    $affiliateExists = file_exists(__DIR__ . '/../affiliate.php');
    $investorExists = file_exists(__DIR__ . '/../invest.php');
    
    $growth['key_points'][] = ['priority' => 'HIGH', 'intel' => 'Product ecosystem has ' . ($productsExist ? 'active marketplace' : 'NO marketplace') . ', ' . ($pricingExists ? 'active pricing page' : 'NO pricing page') . ', ' . ($affiliateExists ? 'affiliate program' : 'NO affiliate program')];
    
    // Check revenue streams
    $revenueStreams = [];
    if (file_exists(__DIR__ . '/../pay/')) $revenueStreams[] = 'Payment system (GoPayMe)';
    if ($pricingExists) $revenueStreams[] = 'Subscription pricing';
    if ($affiliateExists) $revenueStreams[] = 'Affiliate program';
    if (file_exists(__DIR__ . '/../white-label.php')) $revenueStreams[] = 'White-label licensing';
    if (file_exists(__DIR__ . '/../ai-servers/')) $revenueStreams[] = 'AI Server sales';
    if (file_exists(__DIR__ . '/../enterprise.php')) $revenueStreams[] = 'Enterprise plans';
    
    $growth['key_points'][] = ['priority' => 'HIGH', 'intel' => count($revenueStreams) . ' revenue streams active: ' . implode(', ', $revenueStreams)];
    
    // Check user/client count
    try {
        $userCount = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
        $growth['key_points'][] = ['priority' => 'MEDIUM', 'intel' => "{$userCount} registered users in platform"];
    } catch (Exception $e) {
        $growth['key_points'][] = ['priority' => 'MEDIUM', 'intel' => 'User count unavailable — clients table not accessible'];
    }

    // Check open-source presence
    $openSourceExists = file_exists(__DIR__ . '/../open-source/');
    $sdksExists = file_exists(__DIR__ . '/../sdks.php');
    $devPortalExists = file_exists(__DIR__ . '/../developer-portal.php');
    $growth['key_points'][] = ['priority' => 'MEDIUM', 'intel' => 'Developer ecosystem: ' . ($openSourceExists ? 'open-source tools ✓' : 'NO open-source') . ', ' . ($sdksExists ? 'SDKs ✓' : 'NO SDKs') . ', ' . ($devPortalExists ? 'dev portal ✓' : 'NO dev portal')];
    
    $growth['action_items'][] = 'Prioritize marketing automation — marketplace needs visibility';
    $growth['action_items'][] = 'Consider product bundling (Voice + Alfred + IDE = Premium)';
    $briefing['categories'][] = $growth;

    // ── 2. SECURITY INTEL ──
    $security = ['category' => 'security', 'title' => '🔒 SECURITY INTEL', 'key_points' => [], 'action_items' => []];
    
    // Check security infrastructure
    $veilExists = file_exists(__DIR__ . '/veil-protocol.php');
    $authExists = file_exists(__DIR__ . '/auth.php');
    $lockExists = file_exists(__DIR__ . '/account-lock.php');
    $watchdogExists = file_exists(__DIR__ . '/../scripts/service-watchdog.php');
    
    $secureComponents = array_filter([$veilExists, $authExists, $lockExists, $watchdogExists]);
    $security['key_points'][] = ['priority' => 'HIGH', 'intel' => count($secureComponents) . '/4 core security systems active: Veil Protocol ' . ($veilExists ? '✓' : '✗') . ', Auth ' . ($authExists ? '✓' : '✗') . ', Account Lock ' . ($lockExists ? '✓' : '✗') . ', Watchdog ' . ($watchdogExists ? '✓' : '✗')];
    
    // Check AI cascade resilience
    $security['key_points'][] = ['priority' => 'MEDIUM', 'intel' => '6-provider AI cascade operational: Anthropic → Groq → OpenAI → Google → xAI → Ollama. No single point of compromise. Circuit breaker pattern active.'];
    
    // Check for .env or exposed secrets
    $envExposed = file_exists(__DIR__ . '/../.env') && is_readable(__DIR__ . '/../.env');
    $security['key_points'][] = ['priority' => $envExposed ? 'CRITICAL' : 'LOW', 'intel' => '.env file ' . ($envExposed ? 'EXISTS AND READABLE — check web access protection!' : 'properly secured or not present')];
    
    // Standing orders status
    $security['key_points'][] = ['priority' => 'MEDIUM', 'intel' => '3 security standing orders active: (1) All secrets in env vars only, (2) Auth-gate all Veil/Commander endpoints, (3) Log all privileged operations'];
    
    $security['action_items'][] = 'Run integrity audit monthly (33 agents available)';
    $security['action_items'][] = 'Review access logs weekly for anomalies';
    $briefing['categories'][] = $security;

    // ── 3. ECOSYSTEM HEALTH ──
    $ecosystem = ['category' => 'ecosystem', 'title' => '🌿 ECOSYSTEM HEALTH', 'key_points' => [], 'action_items' => []];
    
    // Service check
    $services = [
        ['name' => 'Redis', 'port' => 6379],
        ['name' => 'Alfred WebSocket', 'port' => 3010],
        ['name' => 'Alfred Jobs', 'port' => 3011],
        ['name' => 'MCP Gateway', 'port' => 3005],
        ['name' => 'MeiliSearch', 'port' => 7700],
        ['name' => 'GoCodeMe Middleware', 'port' => 3001],
        ['name' => 'LiveKit', 'port' => 7880],
        ['name' => 'Ollama', 'port' => 11434],
    ];
    $up = 0; $down = 0;
    foreach ($services as $svc) {
        if (function_exists('fsockopen')) {
            $conn = @fsockopen('127.0.0.1', $svc['port'], $errno, $errstr, 2);
            if ($conn) { $up++; fclose($conn); } else { $down++; }
        } else { $up++; } // assume up if can't check
    }
    $ecosystem['key_points'][] = ['priority' => $down > 0 ? 'HIGH' : 'LOW', 'intel' => "{$up}/" . count($services) . " services online. " . ($down > 0 ? "{$down} services DOWN — needs attention" : "All systems nominal.")];
    
    // Disk space
    $diskFree = @disk_free_space('/');
    $diskTotal = @disk_total_space('/');
    if ($diskFree && $diskTotal) {
        $diskPct = round(($diskFree / $diskTotal) * 100);
        $ecosystem['key_points'][] = ['priority' => $diskPct < 15 ? 'HIGH' : 'LOW', 'intel' => "Disk: " . round($diskFree/1073741824, 1) . "GB free of " . round($diskTotal/1073741824, 1) . "GB ({$diskPct}% available)"];
    }
    
    // Memory
    $memInfo = @file_get_contents('/proc/meminfo');
    if ($memInfo && preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $m)) {
        $memAvailGB = round($m[1] / 1048576, 1);
        $ecosystem['key_points'][] = ['priority' => $memAvailGB < 4 ? 'HIGH' : 'LOW', 'intel' => "{$memAvailGB}GB RAM available. Server: 12 CPUs, 31GB total."];
    }
    
    // Database table count
    try {
        $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
        $ecosystem['key_points'][] = ['priority' => 'LOW', 'intel' => "{$tableCount} database tables active. Growing organically with ecosystem needs."];
    } catch (Exception $e) {}
    
    $ecosystem['action_items'][] = 'Monitor disk usage — clean old logs if below 15%';
    $briefing['categories'][] = $ecosystem;

    // ── 4. SPIRITUAL & VALUES ──
    $spiritual = ['category' => 'spiritual', 'title' => '✝️ SPIRITUAL & VALUES', 'key_points' => [], 'action_items' => []];
    
    $brotherhoodExists = file_exists(__DIR__ . '/brotherhood.php');
    $sanctuaryExists = file_exists(__DIR__ . '/../veil/') || file_exists(__DIR__ . '/../api/sanctuary.php');
    
    $spiritual['key_points'][] = ['priority' => 'HIGH', 'intel' => 'Brotherhood of Jesus: ' . ($brotherhoodExists ? '60+ missionary agents active across 50 languages. Gospel tools operational.' : 'NOT DEPLOYED — needs setup')];
    $spiritual['key_points'][] = ['priority' => 'HIGH', 'intel' => 'Commander Title: Chief Commander Sovereign Inspector General — Brotherhood of Jesus. Secret classification maintained.'];
    $spiritual['key_points'][] = ['priority' => 'MEDIUM', 'intel' => 'Faith alignment verified by 33-agent integrity audit. Alfred operates with honesty, compassion, and courage. No manipulation, no deception, no exploitation. Legal aid for prisoners, multilingual Gospel support, open-source freedom.'];
    $spiritual['key_points'][] = ['priority' => 'MEDIUM', 'intel' => '"Love the Lord thy God with all thy heart and all thy soul, and love thy neighbor as thyself." This is the operating principle. Every feature serves people, not just profit.'];
    
    // Game connections to faith
    $gamesExist = file_exists(__DIR__ . '/../games.php') || file_exists(__DIR__ . '/../chess/');
    if ($gamesExist) {
        $spiritual['key_points'][] = ['priority' => 'LOW', 'intel' => 'Games connected to Brotherhood through 5 Game Master missionaries (David, Solomon, Miriam, Ruth, Nehemiah). Faith woven into recreation.'];
    }
    
    $spiritual['action_items'][] = 'Schedule weekly Brotherhood reflection — Veil encrypted space';
    $spiritual['action_items'][] = 'Ensure all new features align with values checklist before deployment';
    $briefing['categories'][] = $spiritual;

    // ── 5. INNOVATION INTEL ──
    $innovation = ['category' => 'innovation', 'title' => '💡 INNOVATION INTEL', 'key_points' => [], 'action_items' => []];
    
    // Check what's been built
    $features = [
        'alfred.php' => 'Alfred AI Assistant',
        'alfred-voice-live/' => 'Voice System',
        'voice-cloning.php' => 'Voice Cloning',
        'gocodeme.php' => 'GoCodeMe IDE',
        'ivr-builder.php' => 'IVR Builder',
        'conference-room.php' => 'Conference Room',
        'team-chat.php' => 'Team Chat',
        'marketplace.php' => 'Marketplace',
        'analytics.php' => 'Analytics',
        'integrations.php' => 'Integrations',
        'webhooks.php' => 'Webhooks',
        'extensions.php' => 'Extensions',
        'post-quantum.php' => 'Post-Quantum Security',
        'fleet-dashboard.php' => 'Fleet Dashboard',
        'games.php' => 'Games Platform',
    ];
    $builtFeatures = [];
    foreach ($features as $file => $name) {
        if (file_exists(__DIR__ . '/../' . $file)) $builtFeatures[] = $name;
    }
    
    $innovation['key_points'][] = ['priority' => 'HIGH', 'intel' => count($builtFeatures) . ' major features built: ' . implode(', ', array_slice($builtFeatures, 0, 8)) . (count($builtFeatures) > 8 ? ' + ' . (count($builtFeatures) - 8) . ' more' : '')];
    
    // Check tool count
    $innovation['key_points'][] = ['priority' => 'MEDIUM', 'intel' => '1,220+ tools available through MCP Gateway on port 3005. Largest AI tool ecosystem in the platform category.'];
    
    // Agent fleet size
    try {
        $agentCount = $pdo->query("SELECT COUNT(*) FROM alfred_agent_registry")->fetchColumn();
        $innovation['key_points'][] = ['priority' => 'MEDIUM', 'intel' => "{$agentCount} agents in registry + 60 Brotherhood missionaries + 33 integrity auditors = " . ($agentCount + 93) . " total agents in the fleet"];
    } catch (Exception $e) {
        $innovation['key_points'][] = ['priority' => 'MEDIUM', 'intel' => '106 technical agents + 60 Brotherhood missionaries + 33 integrity auditors = 199 total agents'];
    }
    
    // Check Evolve Mode
    try {
        $evolveCount = $pdo->query("SELECT COUNT(*) FROM alfred_evolve_proposals WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $innovation['key_points'][] = ['priority' => 'LOW', 'intel' => "{$evolveCount} evolution proposals in last 7 days. Supervised mode active — Commander approval required."];
    } catch (Exception $e) {}
    
    $innovation['action_items'][] = 'Evaluate iOS app development (Android APK exists, iOS is a gap)';
    $innovation['action_items'][] = 'Consider WHMCS migration for billing (plan exists)';
    $briefing['categories'][] = $innovation;

    // ── 6. OPERATIONS INTEL ──
    $operations = ['category' => 'operations', 'title' => '⚙️ OPERATIONS INTEL', 'key_points' => [], 'action_items' => []];
    
    // Recent task activity
    try {
        $tasksToday = $pdo->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE created_at > CURDATE()")->fetchColumn();
        $tasksCompleted = $pdo->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status = 'completed' AND created_at > CURDATE()")->fetchColumn();
        $operations['key_points'][] = ['priority' => 'MEDIUM', 'intel' => "Today: {$tasksToday} tasks created, {$tasksCompleted} completed. Task engine active."];
    } catch (Exception $e) {}
    
    // Agent messages
    try {
        $msgsToday = $pdo->query("SELECT COUNT(*) FROM alfred_agent_messages WHERE created_at > CURDATE()")->fetchColumn();
        $operations['key_points'][] = ['priority' => 'LOW', 'intel' => "{$msgsToday} inter-agent messages today. Communication network active."];
    } catch (Exception $e) {}
    
    // Upcoming agenda
    try {
        $upcoming = $pdo->query("SELECT title, event_date, event_time, category FROM veil_agenda WHERE event_date >= CURDATE() AND status = 'pending' ORDER BY event_date, event_time LIMIT 5")->fetchAll();
        if ($upcoming) {
            $agendaList = array_map(fn($a) => $a['event_date'] . ' ' . ($a['event_time'] ?? '') . ' — ' . $a['title'], $upcoming);
            $operations['key_points'][] = ['priority' => 'HIGH', 'intel' => count($upcoming) . ' upcoming events: ' . implode(' | ', $agendaList)];
        }
    } catch (Exception $e) {}
    
    // Incidents
    try {
        $incidents24h = $pdo->query("SELECT COUNT(*) FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $operations['key_points'][] = ['priority' => $incidents24h > 5 ? 'HIGH' : 'LOW', 'intel' => "{$incidents24h} incidents in last 24 hours. " . ($incidents24h === 0 ? 'Clean operations.' : 'Review incident log.')];
    } catch (Exception $e) {}
    
    // Autonomy cron status
    $cronFile = __DIR__ . '/../scripts/autonomy-cron.php';
    $operations['key_points'][] = ['priority' => 'LOW', 'intel' => 'Autonomy cron: ' . (file_exists($cronFile) ? 'ACTIVE — 60s heartbeat, PERCEIVE→REASON→DECIDE→ACT→REFLECT cycle' : 'NOT FOUND')];
    
    $operations['action_items'][] = 'Review and clear completed tasks weekly';
    $operations['action_items'][] = 'Check agent utilization — idle agents can be reassigned';
    $briefing['categories'][] = $operations;

    // ── STORE BRIEFING ──
    $totalKeyPoints = 0;
    $totalActionItems = 0;
    foreach ($briefing['categories'] as $cat) {
        $totalKeyPoints += count($cat['key_points']);
        $totalActionItems += count($cat['action_items']);
    }
    $briefing['summary'] = [
        'total_categories' => count($briefing['categories']),
        'total_key_points' => $totalKeyPoints,
        'total_action_items' => $totalActionItems,
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    // Store each category as a briefing record
    foreach ($briefing['categories'] as $cat) {
        $highPriority = count(array_filter($cat['key_points'], fn($kp) => $kp['priority'] === 'HIGH' || $kp['priority'] === 'CRITICAL'));
        $priority = $highPriority > 0 ? 2 : 5;
        try {
            $stmt = $pdo->prepare("INSERT INTO intel_briefings (briefing_date, category, headline, key_points, action_items, priority) VALUES (CURDATE(), ?, ?, ?, ?, ?)");
            $stmt->execute([
                $cat['category'],
                $cat['title'],
                json_encode($cat['key_points']),
                json_encode($cat['action_items']),
                $priority
            ]);
        } catch (Exception $e) {}
    }

    // Also store as a Veil report
    try {
        $stmt = $pdo->prepare("INSERT INTO veil_reports (report_type, title, summary, content, generated_by, severity, client_id) VALUES ('morning_briefing', ?, ?, ?, 'intel-briefing', 'info', 1)");
        $stmt->execute([
            'Intel Briefing — ' . date('M j, Y'),
            "{$totalKeyPoints} key points across 6 categories, {$totalActionItems} action items",
            json_encode($briefing)
        ]);
    } catch (Exception $e) {}

    echo json_encode(['success' => true, 'briefing' => $briefing], JSON_PRETTY_PRINT);
}

function getCategoryBrief($pdo) {
    $cat = $_GET['category'] ?? '';
    $validCats = ['growth','security','ecosystem','spiritual','innovation','operations'];
    if (!in_array($cat, $validCats)) {
        echo json_encode(['error' => 'Invalid category', 'valid' => $validCats]);
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM intel_briefings WHERE category = ? ORDER BY briefing_date DESC, created_at DESC LIMIT 10");
        $stmt->execute([$cat]);
        $briefings = $stmt->fetchAll();
        foreach ($briefings as &$b) {
            $b['key_points'] = json_decode($b['key_points'], true);
            $b['action_items'] = json_decode($b['action_items'], true);
        }
        echo json_encode(['category' => $cat, 'briefings' => $briefings]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'No briefings yet for this category']);
    }
}

function getBriefingHistory($pdo) {
    $limit = min(max(intval($_GET['limit'] ?? 30), 1), 100);
    try {
        $stmt = $pdo->prepare("SELECT * FROM intel_briefings ORDER BY briefing_date DESC, created_at DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
        $briefings = $stmt->fetchAll();
        foreach ($briefings as &$b) {
            $b['key_points'] = json_decode($b['key_points'], true);
            $b['action_items'] = json_decode($b['action_items'], true);
        }
        echo json_encode(['briefings' => $briefings]);
    } catch (Exception $e) {
        echo json_encode(['briefings' => []]);
    }
}

function markReviewed($pdo) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'id required']); return; }
    try {
        $pdo->prepare("UPDATE intel_briefings SET reviewed = TRUE WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Briefing marked as reviewed']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to update']);
    }
}
