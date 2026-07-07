<?php
/**
 * GoSiteMe World Events & Intel Feed API
 * Real-time intelligence monitoring for the Commander
 * Memory-assisted: helps Commander stay on top of rapidly advancing world events
 * Sources: AI analysis of current events across tech, politics, energy, crypto, security
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();
$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
if (!$client_id && !$is_internal) { echo json_encode(['error' => 'Auth required']); exit; }
require_once dirname(__DIR__) . '/includes/api-security.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `world_events` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `event_id` VARCHAR(50) UNIQUE NOT NULL,
        `title` VARCHAR(300) NOT NULL,
        `summary` TEXT,
        `analysis` TEXT,
        `category` ENUM('technology','politics','energy','crypto','security','military','science','economics','legal','health') DEFAULT 'technology',
        `region` VARCHAR(50) DEFAULT 'Global',
        `priority` ENUM('flash','urgent','important','routine','background') DEFAULT 'routine',
        `impact_assessment` TEXT,
        `commander_notes` TEXT,
        `source` VARCHAR(100),
        `tags` JSON,
        `is_read` TINYINT DEFAULT 0,
        `is_bookmarked` TINYINT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`category`), INDEX(`priority`), INDEX(`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `commander_memory` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `mem_id` VARCHAR(50) UNIQUE NOT NULL,
        `category` VARCHAR(50) DEFAULT 'general',
        `title` VARCHAR(200) NOT NULL,
        `content` TEXT NOT NULL,
        `importance` ENUM('critical','high','medium','low') DEFAULT 'medium',
        `remind_at` TIMESTAMP NULL,
        `is_active` TINYINT DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`category`), INDEX(`remind_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']); exit;
}

$action = $_REQUEST['action'] ?? 'feed';
$is_admin = ($client_id == 33) || $is_internal;

switch ($action) {

// ─── Intel Feed ──────────────────────────────────────────────────
case 'feed':
    $cat = $_GET['category'] ?? null;
    $priority = $_GET['priority'] ?? null;
    $unread = $_GET['unread'] ?? null;

    $sql = "SELECT * FROM world_events WHERE 1=1";
    $params = [];
    if ($cat) { $sql .= " AND category = ?"; $params[] = $cat; }
    if ($priority) { $sql .= " AND priority = ?"; $params[] = $priority; }
    if ($unread) { $sql .= " AND is_read = 0"; }
    $sql .= " ORDER BY FIELD(priority,'flash','urgent','important','routine','background'), created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $unread_count = $pdo->query("SELECT COUNT(*) FROM world_events WHERE is_read = 0")->fetchColumn();
    $flash_count = $pdo->query("SELECT COUNT(*) FROM world_events WHERE priority = 'flash' AND is_read = 0")->fetchColumn();

    echo json_encode([
        'success' => true,
        'events' => $stmt->fetchAll(),
        'unread_count' => intval($unread_count),
        'flash_alerts' => intval($flash_count)
    ]);
    break;

// ─── Mark Read ───────────────────────────────────────────────────
case 'mark-read':
    $eid = $_POST['event_id'] ?? '';
    $pdo->prepare("UPDATE world_events SET is_read = 1 WHERE event_id = ?")->execute([$eid]);
    echo json_encode(['success' => true]);
    break;

// ─── Bookmark ────────────────────────────────────────────────────
case 'bookmark':
    $eid = $_POST['event_id'] ?? '';
    $pdo->prepare("UPDATE world_events SET is_bookmarked = NOT is_bookmarked WHERE event_id = ?")->execute([$eid]);
    echo json_encode(['success' => true]);
    break;

// ─── Add Commander Note ─────────────────────────────────────────
case 'add-note':
    if (!$is_admin) { echo json_encode(['error' => 'Commander only']); exit; }
    $eid = $_POST['event_id'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $pdo->prepare("UPDATE world_events SET commander_notes = CONCAT(COALESCE(commander_notes,''), '\n[', NOW(), '] ', ?) WHERE event_id = ?")
        ->execute([$note, $eid]);
    echo json_encode(['success' => true]);
    break;

// ─── Generate Fresh Intel (AI-powered) ──────────────────────────
case 'refresh':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }

    $categories = ['technology', 'crypto', 'energy', 'security', 'science', 'economics'];
    $focus = $_POST['focus'] ?? implode(', ', $categories);

    $prompt = "You are an intelligence analyst briefing a technology company commander. Generate 8 CURRENT world events and developments as of today's date that would be important for someone tracking:
- AI/Technology advancement
- Cryptocurrency & blockchain developments  
- Energy technology (including renewable, zero-point research, fusion)
- Cybersecurity threats and developments
- Scientific breakthroughs
- Economic/market developments

For each event, provide a JSON array of objects with these fields:
- title (string, compelling headline)
- summary (string, 2-3 sentence summary)
- analysis (string, what this means strategically and how it connects to broader trends)
- category (one of: technology, politics, energy, crypto, security, military, science, economics, legal, health)
- region (string, geographic region)
- priority (one of: flash, urgent, important, routine, background)
- impact_assessment (string, how this affects our ecosystem)
- tags (array of strings)

Respond ONLY with valid JSON array. No markdown, no explanation.";

    $response = callAI($prompt);
    if (empty($response)) {
        echo json_encode(['error' => 'AI unavailable — try again shortly']);
        exit;
    }

    // Parse JSON from response
    $events = json_decode($response, true);
    if (!is_array($events)) {
        // Try to extract JSON from response
        if (preg_match('/\[.*\]/s', $response, $m)) {
            $events = json_decode($m[0], true);
        }
    }

    if (!is_array($events)) {
        echo json_encode(['error' => 'Failed to parse intel — try again']);
        exit;
    }

    $inserted = 0;
    foreach ($events as $e) {
        if (empty($e['title'])) continue;
        $eid = 'EVT-' . strtoupper(substr(md5($e['title'] . date('Y-m-d')), 0, 12));
        $stmt = $pdo->prepare("INSERT IGNORE INTO world_events (event_id, title, summary, analysis, category, region, priority, impact_assessment, source, tags) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $eid,
            $e['title'] ?? 'Untitled',
            $e['summary'] ?? '',
            $e['analysis'] ?? '',
            $e['category'] ?? 'technology',
            $e['region'] ?? 'Global',
            $e['priority'] ?? 'routine',
            $e['impact_assessment'] ?? '',
            'Alfred Intelligence Network',
            json_encode($e['tags'] ?? [])
        ]);
        $inserted++;
    }

    echo json_encode(['success' => true, 'events_generated' => $inserted, 'message' => "Fresh intel generated: {$inserted} events"]);
    break;

// ─── Commander Memory System ────────────────────────────────────
case 'memory-list':
    $cat = $_GET['category'] ?? null;
    $sql = "SELECT * FROM commander_memory WHERE is_active = 1";
    $params = [];
    if ($cat) { $sql .= " AND category = ?"; $params[] = $cat; }
    $sql .= " ORDER BY FIELD(importance,'critical','high','medium','low'), created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $reminders = $pdo->query("SELECT * FROM commander_memory WHERE remind_at IS NOT NULL AND remind_at <= NOW() AND is_active = 1 ORDER BY remind_at")->fetchAll();

    echo json_encode(['success' => true, 'memories' => $stmt->fetchAll(), 'reminders' => $reminders]);
    break;

case 'memory-add':
    if (!$is_admin) { echo json_encode(['error' => 'Commander only']); exit; }
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $category = $_POST['category'] ?? 'general';
    $importance = $_POST['importance'] ?? 'medium';
    $remind_at = !empty($_POST['remind_at']) ? $_POST['remind_at'] : null;

    if (empty($title)) { echo json_encode(['error' => 'Title required']); exit; }

    $mid = 'MEM-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));
    $stmt = $pdo->prepare("INSERT INTO commander_memory (mem_id, category, title, content, importance, remind_at) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$mid, $category, $title, $content, $importance, $remind_at]);

    echo json_encode(['success' => true, 'mem_id' => $mid]);
    break;

case 'memory-dismiss':
    $mid = $_POST['mem_id'] ?? '';
    $pdo->prepare("UPDATE commander_memory SET is_active = 0 WHERE mem_id = ?")->execute([$mid]);
    echo json_encode(['success' => true]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['feed','mark-read','bookmark','add-note','refresh','memory-list','memory-add','memory-dismiss']]);
}

function callAI($prompt) {
    $providers = [
        ['url' => 'https://api.anthropic.com/v1/messages', 'key_env' => 'ANTHROPIC_API_KEY', 'type' => 'anthropic'],
        ['url' => 'https://api.groq.com/openai/v1/chat/completions', 'key_env' => 'GROQ_API_KEY', 'type' => 'openai'],
        ['url' => 'https://api.openai.com/v1/chat/completions', 'key_env' => 'OPENAI_API_KEY', 'type' => 'openai'],
    ];
    foreach ($providers as $p) {
        $key = getenv($p['key_env']);
        if (empty($key)) continue;
        $ch = curl_init($p['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($p['type'] === 'anthropic') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01']);
            $body = json_encode(['model' => 'claude-sonnet-4-20250514', 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $key]);
            $model = $p['key_env'] === 'GROQ_API_KEY' ? 'llama-3.3-70b-versatile' : 'gpt-4o-mini';
            $body = json_encode(['model' => $model, 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            return $p['type'] === 'anthropic' ? ($data['content'][0]['text'] ?? null) : ($data['choices'][0]['message']['content'] ?? null);
        }
    }
    return null;
}
