<?php
/**
 * Alfred Telegram Bot API — Phase 1: Autonomy Foundation
 * ──────────────────────────────────────────────────────
 * Dedicated Telegram Bot management: webhook setup, command handling,
 * proactive messaging, and admin controls.
 *
 * The messaging-gateway.php handles inbound Telegram webhooks for chat,
 * but this file provides the full bot management layer.
 *
 * Endpoints:
 *   POST ?action=setup-webhook     → Register webhook URL with Telegram
 *   POST ?action=send              → Send message to a Telegram chat
 *   POST ?action=broadcast         → Send message to all subscribers
 *   GET  ?action=info              → Get bot info from Telegram
 *   GET  ?action=subscribers       → List subscribers
 *   POST ?action=subscribe         → Register a chat_id as subscriber
 *   POST ?action=unsubscribe       → Remove a subscriber
 *   POST ?action=handle-update     → Process inbound Telegram update (webhook target)
 *   GET  ?action=commands          → List available bot commands
 *   POST ?action=set-commands      → Set bot command menu in Telegram
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Telegram-Bot-Api-Secret-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// ─── Config ────────────────────────────────────────────────────────
$TELEGRAM_BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN') ?: '';
$TELEGRAM_WEBHOOK_SECRET = getenv('TELEGRAM_WEBHOOK_SECRET') ?: '';
$TELEGRAM_API = 'https://api.telegram.org/bot' . $TELEGRAM_BOT_TOKEN;

// ─── Auth ──────────────────────────────────────────────────────────
function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function isAdmin() {
    return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
}

function isInternalCall() {
    $secret = getenv('INTERNAL_SECRET') ?: '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function isTelegramWebhook() {
    global $TELEGRAM_WEBHOOK_SECRET;
    if (!$TELEGRAM_WEBHOOK_SECRET) return true; // No secret configured, skip validation
    $header = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    return hash_equals($TELEGRAM_WEBHOOK_SECRET, $header);
}

// ─── DB Schema ─────────────────────────────────────────────────────
function ensureTelegramSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_telegram_subscribers (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        chat_id     BIGINT UNIQUE NOT NULL,
        username    VARCHAR(100) DEFAULT NULL,
        first_name  VARCHAR(100) DEFAULT NULL,
        chat_type   ENUM('private','group','supergroup','channel') DEFAULT 'private',
        subscribed  BOOLEAN DEFAULT TRUE,
        is_admin    BOOLEAN DEFAULT FALSE,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_subscribed (subscribed),
        INDEX idx_chat_type (chat_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_telegram_messages (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        chat_id     BIGINT NOT NULL,
        direction   ENUM('inbound','outbound') NOT NULL,
        message_text TEXT NOT NULL,
        command     VARCHAR(50) DEFAULT NULL,
        message_id  BIGINT DEFAULT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chat (chat_id),
        INDEX idx_direction (direction),
        INDEX idx_command (command)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// ─── Telegram API Helper ───────────────────────────────────────────
function telegramAPI($method, $data = []) {
    global $TELEGRAM_API;
    if (!$TELEGRAM_API || $TELEGRAM_API === 'https://api.telegram.org/bot') {
        return ['ok' => false, 'description' => 'Bot token not configured'];
    }

    $ch = curl_init("{$TELEGRAM_API}/{$method}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return json_decode($response, true) ?: ['ok' => false, 'description' => 'Failed to parse response'];
}

// ─── Bot Commands ──────────────────────────────────────────────────
$BOT_COMMANDS = [
    ['command' => 'start',      'description' => 'Start the bot and subscribe to updates'],
    ['command' => 'ask',        'description' => 'Ask Alfred anything — /ask What is GoSiteMe?'],
    ['command' => 'status',     'description' => 'Get Alfred system status'],
    ['command' => 'search',     'description' => 'Search the web — /search latest PHP news'],
    ['command' => 'tools',      'description' => 'Browse available tools'],
    ['command' => 'goals',      'description' => 'View current active goals'],
    ['command' => 'balance',    'description' => 'Check treasury balance (admin only)'],
    ['command' => 'news',       'description' => 'Latest feed items'],
    ['command' => 'help',       'description' => 'Show help and available commands'],
    ['command' => 'stop',       'description' => 'Unsubscribe from updates'],
];

// ─── Command Handlers ──────────────────────────────────────────────
function handleCommand($command, $args, $chatId, $from) {
    $db = getDB();

    switch ($command) {
        case '/start':
            // Auto-subscribe
            $stmt = $db->prepare("INSERT INTO alfred_telegram_subscribers (chat_id, username, first_name, chat_type) VALUES (?, ?, ?, 'private') ON DUPLICATE KEY UPDATE subscribed = 1, username = VALUES(username), first_name = VALUES(first_name)");
            $stmt->execute([$chatId, $from['username'] ?? null, $from['first_name'] ?? null]);
            return "🤖 *Alfred AI Online*\n\nWelcome, " . ($from['first_name'] ?? 'friend') . "! I'm Alfred — GoSiteMe's AI commander.\n\n*Available commands:*\n/ask — Ask me anything\n/search — Web search\n/status — System status\n/tools — Browse tools\n/goals — Active goals\n/news — Latest feed items\n/help — Full help\n\nI'll also send you proactive updates about important events.";

        case '/help':
            return "🤖 *Alfred Commands*\n\n/ask `question` — Ask Alfred anything\n/search `query` — Search the web\n/status — System status & health\n/tools — Browse 13,000+ tools\n/goals — View active goals\n/balance — Treasury balance (admin)\n/news — Latest feed items\n/stop — Unsubscribe from updates\n\n💡 You can also just type naturally — Alfred understands free-form messages.";

        case '/status':
            $agents = $db->query("SELECT COUNT(*) as total, SUM(status = 'active' OR status = 'idle') as online FROM alfred_agent_registry")->fetch();
            $goals = $db->query("SELECT COUNT(*) as total, SUM(status = 'active') as active FROM alfred_goals")->fetch();
            $tasks = $db->query("SELECT COUNT(*) as total, SUM(status = 'running') as running FROM alfred_agent_tasks")->fetch();

            $agentTotal = $agents['total'] ?? 0;
            $agentOnline = $agents['online'] ?? 0;
            $goalTotal = $goals['total'] ?? 0;
            $goalActive = $goals['active'] ?? 0;
            $taskTotal = $tasks['total'] ?? 0;
            $taskRunning = $tasks['running'] ?? 0;

            return "📊 *Alfred System Status*\n\n🤖 Agents: {$agentOnline}/{$agentTotal} online\n🎯 Goals: {$goalActive}/{$goalTotal} active\n⚡ Tasks: {$taskRunning} running / {$taskTotal} total\n🟢 Status: Operational\n\n_Last checked: " . date('Y-m-d H:i:s') . " UTC_";

        case '/ask':
            if (!$args) return "❓ Usage: /ask `your question here`\n\nExample: /ask What services does GoSiteMe offer?";
            // Forward to Alfred chat API
            $response = callAlfredChat($args, $chatId);
            return $response;

        case '/search':
            if (!$args) return "🔍 Usage: /search `your query`\n\nExample: /search latest PHP 8.4 features";
            return "🔍 *Searching:* {$args}\n\n_Results will be sent shortly..._";

        case '/tools':
            return "🛠️ *Alfred Tool Registry*\n\n📦 13,000+ tools across 6 providers:\n\n• Native Tools — 170+\n• MCP Server — 807\n• External MCP — 1,200+\n• Composio — 11,000+\n• VAPI Voice — 85\n• Marketplace — Community\n\n🌐 Full catalog: https://gositeme.com/alfred-tools";

        case '/goals':
            $activeGoals = $db->query("SELECT goal_id, description, progress, goal_type FROM alfred_goals WHERE status = 'active' ORDER BY goal_type, progress DESC LIMIT 10")->fetchAll();
            if (!$activeGoals) return "🎯 No active goals at the moment.";

            $text = "🎯 *Active Goals*\n\n";
            foreach ($activeGoals as $g) {
                $bar = str_repeat('█', (int)($g['progress'] / 10)) . str_repeat('░', 10 - (int)($g['progress'] / 10));
                $text .= "• [{$g['goal_type']}] {$g['description']}\n  {$bar} {$g['progress']}%\n\n";
            }
            return $text;

        case '/balance':
            // Check if user is admin subscriber
            $sub = $db->prepare("SELECT is_admin FROM alfred_telegram_subscribers WHERE chat_id = ?");
            $sub->execute([$chatId]);
            $subRow = $sub->fetch();
            if (!$subRow || !$subRow['is_admin']) return "🔒 This command is restricted to admins.";

            $income = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'income'")->fetchColumn();
            $expenses = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'expense'")->fetchColumn();
            $net = ($income - $expenses) / 100;
            return "💰 *Treasury Balance*\n\n📈 Income: $" . number_format($income / 100, 2) . "\n📉 Expenses: $" . number_format($expenses / 100, 2) . "\n💵 Net: $" . number_format($net, 2);

        case '/news':
            $items = $db->query("SELECT fi.title, fi.url, f.category FROM alfred_feed_items fi JOIN alfred_feeds f ON fi.feed_id = f.id ORDER BY fi.created_at DESC LIMIT 5")->fetchAll();
            if (!$items) return "📰 No news items yet. Feeds haven't been polled.";

            $text = "📰 *Latest News*\n\n";
            foreach ($items as $i) {
                $text .= "• [{$i['category']}] {$i['title']}\n";
                if ($i['url']) $text .= "  🔗 {$i['url']}\n";
                $text .= "\n";
            }
            return $text;

        case '/stop':
            $db->prepare("UPDATE alfred_telegram_subscribers SET subscribed = 0 WHERE chat_id = ?")->execute([$chatId]);
            return "👋 You've been unsubscribed from proactive updates. You can still use commands.\n\nSend /start to re-subscribe anytime.";

        default:
            return null; // Not a recognized command
    }
}

function callAlfredChat($message, $chatId) {
    // Call the internal alfred-chat API
    $url = SITE_URL . '/api/alfred-chat.php';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'message' => $message,
            'session_id' => 'telegram_' . $chatId,
            'channel' => 'telegram',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Internal-Secret: ' . (getenv('INTERNAL_SECRET') ?: ''),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['response'] ?? $data['message'] ?? "I received your message but couldn't process it right now. Try again shortly.";
}

// ─── Router ────────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();

if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);

ensureTelegramSchema();

switch ($action) {

    // ── Setup Webhook ───────────────────────────────────────────────
    case 'setup-webhook':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $webhookUrl = filter_var($input['webhook_url'] ?? '', FILTER_VALIDATE_URL);
        if (!$webhookUrl) {
            $webhookUrl = SITE_URL . '/api/telegram-bot.php?action=handle-update';
        }

        $result = telegramAPI('setWebhook', [
            'url' => $webhookUrl,
            'secret_token' => $TELEGRAM_WEBHOOK_SECRET,
            'allowed_updates' => ['message', 'callback_query', 'my_chat_member'],
            'drop_pending_updates' => true,
        ]);

        jsonResponse(['success' => $result['ok'] ?? false, 'telegram_response' => $result]);
        break;

    // ── Handle Telegram Update (Webhook Target) ─────────────────────
    case 'handle-update':
        if (!isTelegramWebhook()) {
            http_response_code(403);
            exit;
        }

        $update = json_decode(file_get_contents('php://input'), true);
        if (!$update) { http_response_code(200); exit; }

        $message = $update['message'] ?? null;
        if (!$message || empty($message['text'])) {
            // Acknowledge non-text updates silently
            http_response_code(200);
            exit;
        }

        $chatId = $message['chat']['id'];
        $text = $message['text'];
        $from = $message['from'] ?? [];

        // Log inbound message
        $command = null;
        if (preg_match('/^\/(\w+)/', $text, $m)) $command = $m[1];

        $stmt = $db->prepare("INSERT INTO alfred_telegram_messages (chat_id, direction, message_text, command, message_id) VALUES (?, 'inbound', ?, ?, ?)");
        $stmt->execute([$chatId, $text, $command, $message['message_id'] ?? null]);

        // Auto-register subscriber on any message
        $db->prepare("INSERT INTO alfred_telegram_subscribers (chat_id, username, first_name, chat_type) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE last_active = NOW(), username = VALUES(username)")->execute([
            $chatId,
            $from['username'] ?? null,
            $from['first_name'] ?? null,
            $message['chat']['type'] ?? 'private',
        ]);

        // Parse command
        $responseText = null;
        if (str_starts_with($text, '/')) {
            $parts = explode(' ', $text, 2);
            $cmd = strtok($parts[0], '@'); // Remove @botname suffix
            $args = $parts[1] ?? '';
            $responseText = handleCommand($cmd, $args, $chatId, $from);
        }

        // Free-form message → forward to Alfred chat
        if (!$responseText) {
            $responseText = callAlfredChat($text, $chatId);
        }

        // Send reply
        telegramAPI('sendMessage', [
            'chat_id' => $chatId,
            'text' => $responseText,
            'parse_mode' => 'Markdown',
        ]);

        // Log outbound
        $db->prepare("INSERT INTO alfred_telegram_messages (chat_id, direction, message_text, command) VALUES (?, 'outbound', ?, ?)")->execute([$chatId, $responseText, $command]);

        http_response_code(200);
        exit;

    // ── Send Message ────────────────────────────────────────────────
    case 'send':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $chatId = intval($input['chat_id'] ?? 0);
        $text = $input['text'] ?? '';

        if (!$chatId || !$text) jsonResponse(['error' => 'chat_id and text required'], 400);

        $result = telegramAPI('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $input['parse_mode'] ?? 'Markdown',
        ]);

        // Log
        $db->prepare("INSERT INTO alfred_telegram_messages (chat_id, direction, message_text) VALUES (?, 'outbound', ?)")->execute([$chatId, $text]);

        jsonResponse(['success' => $result['ok'] ?? false, 'telegram_response' => $result]);
        break;

    // ── Broadcast ───────────────────────────────────────────────────
    case 'broadcast':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $text = $input['text'] ?? '';
        if (!$text) jsonResponse(['error' => 'text required'], 400);

        $subscribers = $db->query("SELECT chat_id FROM alfred_telegram_subscribers WHERE subscribed = 1")->fetchAll(PDO::FETCH_COLUMN);

        $sent = 0;
        $failed = 0;
        foreach ($subscribers as $chatId) {
            $result = telegramAPI('sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $input['parse_mode'] ?? 'Markdown',
            ]);
            if ($result['ok'] ?? false) { $sent++; } else { $failed++; }
            usleep(50000); // 50ms between sends to respect rate limits
        }

        jsonResponse(['success' => true, 'sent' => $sent, 'failed' => $failed, 'total' => count($subscribers)]);
        break;

    // ── Bot Info ─────────────────────────────────────────────────────
    case 'info':
        if (!isInternalCall()) requireAuth();
        jsonResponse(['success' => true, 'bot' => telegramAPI('getMe')]);
        break;

    // ── Subscribers ─────────────────────────────────────────────────
    case 'subscribers':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $subs = $db->query("SELECT * FROM alfred_telegram_subscribers ORDER BY last_active DESC")->fetchAll();
        $totalActive = $db->query("SELECT COUNT(*) FROM alfred_telegram_subscribers WHERE subscribed = 1")->fetchColumn();

        jsonResponse(['success' => true, 'subscribers' => $subs, 'active_count' => (int) $totalActive]);
        break;

    // ── Subscribe ───────────────────────────────────────────────────
    case 'subscribe':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $chatId = intval($input['chat_id'] ?? 0);
        if (!$chatId) jsonResponse(['error' => 'chat_id required'], 400);

        $db->prepare("INSERT INTO alfred_telegram_subscribers (chat_id, is_admin) VALUES (?, ?) ON DUPLICATE KEY UPDATE subscribed = 1, is_admin = VALUES(is_admin)")->execute([
            $chatId, !empty($input['is_admin']) ? 1 : 0,
        ]);

        jsonResponse(['success' => true]);
        break;

    // ── Unsubscribe ─────────────────────────────────────────────────
    case 'unsubscribe':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $chatId = intval($input['chat_id'] ?? 0);
        if (!$chatId) jsonResponse(['error' => 'chat_id required'], 400);

        $db->prepare("UPDATE alfred_telegram_subscribers SET subscribed = 0 WHERE chat_id = ?")->execute([$chatId]);

        jsonResponse(['success' => true]);
        break;

    // ── Commands ────────────────────────────────────────────────────
    case 'commands':
        if (!isInternalCall()) requireAuth();
        global $BOT_COMMANDS;
        jsonResponse(['success' => true, 'commands' => $BOT_COMMANDS]);
        break;

    // ── Set Commands in Telegram ────────────────────────────────────
    case 'set-commands':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        global $BOT_COMMANDS;
        $result = telegramAPI('setMyCommands', ['commands' => $BOT_COMMANDS]);

        jsonResponse(['success' => $result['ok'] ?? false, 'telegram_response' => $result]);
        break;

    default:
        jsonResponse([
            'error' => 'Unknown action',
            'available_actions' => ['setup-webhook', 'send', 'broadcast', 'info', 'subscribers', 'subscribe', 'unsubscribe', 'handle-update', 'commands', 'set-commands'],
        ], 400);
}
