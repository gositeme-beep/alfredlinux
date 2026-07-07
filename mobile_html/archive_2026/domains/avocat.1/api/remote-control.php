<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Alfred Remote Control API — Browser-Based System Controller
 * ────────────────────────────────────────────────────────────
 * Allows Alfred to control and manage a user's browsing experience,
 * execute tasks on their behalf, and provide remote assistance —
 * all through the browser with user consent.
 *
 * Capabilities:
 *   - Tab management (open/close/navigate)
 *   - Form auto-fill assistance
 *   - Clipboard management (with consent)
 *   - Notification management
 *   - Screen/page analysis
 *   - Bookmark management
 *   - File download orchestration
 *   - Browser settings suggestions
 *   - Task automation (macros)
 *   - Session state management
 *
 * Endpoints:
 *   GET  ?action=capabilities     → List all remote capabilities
 *   POST ?action=execute          → Execute a remote command
 *   GET  ?action=session          → Get current session state
 *   POST ?action=macro            → Save a task macro
 *   GET  ?action=macros           → List saved macros
 *   POST ?action=consent          → Grant/revoke consent for capabilities
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Auth ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = (int)($_SESSION['user_id'] ?? 0);

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$db = getDB();

// ── Database Setup ──────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS alfred_remote_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token CHAR(64) NOT NULL UNIQUE,
    capabilities JSON DEFAULT NULL,
    consent_granted JSON DEFAULT NULL,
    active TINYINT DEFAULT 1,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_token (session_token),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS alfred_remote_commands (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id BIGINT,
    command_type VARCHAR(50) NOT NULL,
    command_data JSON DEFAULT NULL,
    result JSON DEFAULT NULL,
    status ENUM('pending','executed','failed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed_at TIMESTAMP NULL,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS alfred_remote_macros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    steps JSON NOT NULL,
    trigger_type ENUM('manual','schedule','event') DEFAULT 'manual',
    trigger_config JSON DEFAULT NULL,
    execution_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Capability Registry ─────────────────────────────────────────
$CAPABILITIES = [
    'tab_management' => [
        'name' => 'Tab Management',
        'description' => 'Open, close, and navigate browser tabs',
        'commands' => ['open_tab', 'close_tab', 'navigate', 'switch_tab', 'list_tabs'],
        'consent_required' => true,
        'risk_level' => 'low',
    ],
    'page_analysis' => [
        'name' => 'Page Analysis',
        'description' => 'Analyze current page content, extract data, summarize text',
        'commands' => ['analyze_page', 'extract_data', 'summarize', 'screenshot_analysis'],
        'consent_required' => true,
        'risk_level' => 'low',
    ],
    'form_assist' => [
        'name' => 'Form Auto-Fill',
        'description' => 'Auto-fill forms with saved data or AI suggestions',
        'commands' => ['fill_form', 'suggest_values', 'validate_form'],
        'consent_required' => true,
        'risk_level' => 'medium',
    ],
    'notifications' => [
        'name' => 'Notification Management',
        'description' => 'Send browser notifications, manage alerts',
        'commands' => ['send_notification', 'schedule_notification', 'clear_notifications'],
        'consent_required' => true,
        'risk_level' => 'low',
    ],
    'download_manager' => [
        'name' => 'Download Orchestration',
        'description' => 'Queue and manage file downloads',
        'commands' => ['queue_download', 'list_downloads', 'cancel_download'],
        'consent_required' => true,
        'risk_level' => 'medium',
    ],
    'task_automation' => [
        'name' => 'Task Automation',
        'description' => 'Create and execute multi-step task macros',
        'commands' => ['run_macro', 'record_macro', 'schedule_macro'],
        'consent_required' => true,
        'risk_level' => 'medium',
    ],
    'bookmark_manager' => [
        'name' => 'Bookmark Management',
        'description' => 'Add, organize, and search bookmarks',
        'commands' => ['add_bookmark', 'search_bookmarks', 'organize_bookmarks'],
        'consent_required' => true,
        'risk_level' => 'low',
    ],
    'session_state' => [
        'name' => 'Session State',
        'description' => 'Save and restore browser session states',
        'commands' => ['save_state', 'restore_state', 'list_states'],
        'consent_required' => false,
        'risk_level' => 'low',
    ],
    'mining_control' => [
        'name' => 'Mining Control',
        'description' => 'Start/stop mining, adjust throttle, view stats',
        'commands' => ['start_mining', 'stop_mining', 'set_throttle', 'mining_stats'],
        'consent_required' => true,
        'risk_level' => 'low',
    ],
    'ai_assistant' => [
        'name' => 'AI Assistant',
        'description' => 'Alfred AI assistance — summarize pages, answer questions, translate',
        'commands' => ['summarize_page', 'answer_question', 'translate_text', 'explain_content'],
        'consent_required' => false,
        'risk_level' => 'low',
    ],
];

// ── Handlers ────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'capabilities':
        echo json_encode([
            'capabilities' => $CAPABILITIES,
            'total_commands' => array_sum(array_map(fn($c) => count($c['commands']), $CAPABILITIES)),
        ]);
        break;

    case 'session':
        // Get or create session
        $stmt = $db->prepare("SELECT * FROM alfred_remote_sessions WHERE user_id = ? AND active = 1 ORDER BY last_activity DESC LIMIT 1");
        $stmt->execute([$userId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $token = bin2hex(random_bytes(32));
            $db->prepare("INSERT INTO alfred_remote_sessions (user_id, session_token, capabilities, consent_granted)
                VALUES (?, ?, ?, ?)")
            ->execute([$userId, $token, json_encode(array_keys($CAPABILITIES)), json_encode(['session_state', 'ai_assistant'])]);
            $session = $db->prepare("SELECT * FROM alfred_remote_sessions WHERE session_token = ?")->fetch(PDO::FETCH_ASSOC);
            $stmt->execute([$token]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['session_token' => $token, 'capabilities' => json_encode(array_keys($CAPABILITIES))];
        }

        // Update activity
        $db->prepare("UPDATE alfred_remote_sessions SET last_activity = NOW() WHERE user_id = ? AND active = 1")
            ->execute([$userId]);

        // Recent commands
        $stmt = $db->prepare("SELECT id, command_type, status, created_at FROM alfred_remote_commands
            WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        $recentCmds = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'session' => [
                'token' => $session['session_token'] ?? $token ?? '',
                'capabilities' => json_decode($session['capabilities'] ?? '[]', true),
                'consent' => json_decode($session['consent_granted'] ?? '[]', true),
            ],
            'recent_commands' => $recentCmds,
        ]);
        break;

    case 'consent':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $capability = $input['capability'] ?? '';
        $granted = (bool)($input['granted'] ?? false);

        if (!isset($CAPABILITIES[$capability])) {
            echo json_encode(['error' => 'Unknown capability']);
            break;
        }

        // Get current consent
        $stmt = $db->prepare("SELECT consent_granted FROM alfred_remote_sessions WHERE user_id = ? AND active = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $consent = json_decode($row['consent_granted'] ?? '[]', true) ?: [];

        if ($granted && !in_array($capability, $consent)) {
            $consent[] = $capability;
        } elseif (!$granted) {
            $consent = array_values(array_filter($consent, fn($c) => $c !== $capability));
        }

        $db->prepare("UPDATE alfred_remote_sessions SET consent_granted = ? WHERE user_id = ? AND active = 1")
            ->execute([json_encode($consent), $userId]);

        echo json_encode(['status' => 'updated', 'capability' => $capability, 'granted' => $granted, 'consent' => $consent]);
        break;

    case 'execute':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $commandType = $input['command'] ?? '';
        $commandData = $input['data'] ?? [];

        // Validate command exists in capabilities
        $validCommand = false;
        $requiredCapability = '';
        foreach ($CAPABILITIES as $capKey => $cap) {
            if (in_array($commandType, $cap['commands'])) {
                $validCommand = true;
                $requiredCapability = $capKey;
                break;
            }
        }

        if (!$validCommand) {
            echo json_encode(['error' => 'Unknown command', 'command' => $commandType]);
            break;
        }

        // Check consent
        if ($CAPABILITIES[$requiredCapability]['consent_required']) {
            $stmt = $db->prepare("SELECT consent_granted FROM alfred_remote_sessions WHERE user_id = ? AND active = 1 LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $consent = json_decode($row['consent_granted'] ?? '[]', true) ?: [];

            if (!in_array($requiredCapability, $consent)) {
                echo json_encode(['error' => 'Consent required for ' . $requiredCapability, 'consent_required' => $requiredCapability]);
                break;
            }
        }

        // Log command
        $stmt = $db->prepare("INSERT INTO alfred_remote_commands (user_id, command_type, command_data, status)
            VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$userId, $commandType, json_encode($commandData)]);
        $cmdId = $db->lastInsertId();

        // Execute command (returns instructions for the browser client)
        $result = executeRemoteCommand($commandType, $commandData, $userId);

        // Update status
        $db->prepare("UPDATE alfred_remote_commands SET result = ?, status = 'executed', executed_at = NOW() WHERE id = ?")
            ->execute([json_encode($result), $cmdId]);

        echo json_encode(['status' => 'executed', 'command_id' => $cmdId, 'result' => $result]);
        break;

    case 'macro':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $desc = trim($input['description'] ?? '');
        $steps = $input['steps'] ?? [];

        if (!$name || empty($steps)) {
            echo json_encode(['error' => 'Name and steps required']);
            break;
        }

        $stmt = $db->prepare("INSERT INTO alfred_remote_macros (user_id, name, description, steps) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $desc, json_encode($steps)]);

        echo json_encode(['status' => 'created', 'macro_id' => $db->lastInsertId()]);
        break;

    case 'macros':
        $stmt = $db->prepare("SELECT id, name, description, trigger_type, execution_count, created_at FROM alfred_remote_macros WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        echo json_encode(['macros' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => ['capabilities','session','consent','execute','macro','macros']]);
}

// ── Command Executor ────────────────────────────────────────────
function executeRemoteCommand(string $type, array $data, int $userId): array {
    switch ($type) {
        case 'open_tab':
            $url = filter_var($data['url'] ?? '', FILTER_VALIDATE_URL);
            if (!$url) return ['error' => 'Invalid URL'];
            return ['action' => 'open_tab', 'url' => $url, 'background' => $data['background'] ?? false];

        case 'navigate':
            $url = filter_var($data['url'] ?? '', FILTER_VALIDATE_URL);
            if (!$url) return ['error' => 'Invalid URL'];
            return ['action' => 'navigate', 'url' => $url];

        case 'send_notification':
            return [
                'action' => 'notification',
                'title' => substr($data['title'] ?? 'Alfred', 0, 200),
                'body' => substr($data['body'] ?? '', 0, 500),
                'icon' => '/assets/images/alfred-icon.png',
            ];

        case 'summarize_page':
            return ['action' => 'ai_request', 'type' => 'summarize', 'instructions' => 'Extract page text and send to /api/alfred.php for summarization'];

        case 'answer_question':
            return ['action' => 'ai_request', 'type' => 'answer', 'question' => $data['question'] ?? ''];

        case 'translate_text':
            return ['action' => 'ai_request', 'type' => 'translate', 'text' => $data['text'] ?? '', 'target_lang' => $data['lang'] ?? 'en'];

        case 'start_mining':
            return ['action' => 'mining_control', 'command' => 'start', 'throttle' => min(max((float)($data['throttle'] ?? 0.5), 0.05), 1.0)];

        case 'stop_mining':
            return ['action' => 'mining_control', 'command' => 'stop'];

        case 'set_throttle':
            return ['action' => 'mining_control', 'command' => 'throttle', 'value' => min(max((float)($data['throttle'] ?? 0.5), 0.05), 1.0)];

        case 'mining_stats':
            return ['action' => 'mining_control', 'command' => 'stats'];

        case 'add_bookmark':
            return ['action' => 'bookmark', 'command' => 'add', 'url' => $data['url'] ?? '', 'title' => $data['title'] ?? ''];

        case 'fill_form':
            return ['action' => 'form_fill', 'fields' => $data['fields'] ?? [], 'form_selector' => $data['selector'] ?? 'form'];

        case 'save_state':
            return ['action' => 'state', 'command' => 'save', 'name' => $data['name'] ?? 'default'];

        case 'restore_state':
            return ['action' => 'state', 'command' => 'restore', 'name' => $data['name'] ?? 'default'];

        default:
            return ['action' => $type, 'data' => $data, 'status' => 'queued'];
    }
}
