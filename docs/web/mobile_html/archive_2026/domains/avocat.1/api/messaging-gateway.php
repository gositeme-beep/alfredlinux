<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Alfred Unified Messaging Gateway — Sprint 1
 * 
 * Single entry point for ALL inbound messages across channels.
 * Normalizes format, routes to Alfred AI, formats response back.
 * 
 * Supported channels:
 *   - web        (Alfred chat widget)
 *   - telegram   (Telegram Bot webhook)
 *   - discord    (Discord Bot webhook)
 *   - slack      (Slack Bolt webhook)
 *   - whatsapp   (Meta Cloud API webhook)
 *   - sms        (Telnyx webhook)
 *   - email      (SendGrid inbound parse)
 *   - voice      (VAPI transcription)
 * 
 * Endpoints:
 *   POST ?channel=telegram   → Telegram webhook handler
 *   POST ?channel=discord    → Discord interaction handler
 *   POST ?channel=slack      → Slack event handler
 *   POST ?channel=whatsapp   → WhatsApp webhook handler
 *   POST ?channel=sms        → SMS inbound handler
 *   POST ?channel=email      → Email inbound handler
 *   GET  ?action=channels    → List active channels
 *   GET  ?action=stats       → Message stats per channel
 */

define('GOSITEME_API', true);
define('GOSITEME_GATEWAY', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/veil-protocol.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . SITE_URL);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Telegram-Bot-Api-Secret-Token, X-Slack-Signature, X-Slack-Request-Timestamp');
    exit(0);
}

// ─── Channel Registry ──────────────────────────────────────────────────
$CHANNELS = [
    'web'       => ['name' => 'Web Chat',      'status' => 'active',    'icon' => '💬'],
    'telegram'  => ['name' => 'Telegram',       'status' => 'active',    'icon' => '✈️'],
    'discord'   => ['name' => 'Discord',        'status' => 'ready',     'icon' => '🎮'],
    'slack'     => ['name' => 'Slack',          'status' => 'ready',     'icon' => '💼'],
    'whatsapp'  => ['name' => 'WhatsApp',       'status' => 'ready',     'icon' => '📱'],
    'sms'       => ['name' => 'SMS',            'status' => 'active',    'icon' => '📲'],
    'email'     => ['name' => 'Email',          'status' => 'ready',     'icon' => '📧'],
    'voice'     => ['name' => 'Voice (VAPI)',   'status' => 'active',    'icon' => '🎤'],
];

// ─── Normalized Message Format ─────────────────────────────────────────
/**
 * Every channel message is normalized to this format before processing:
 * {
 *   channel: string,
 *   sender_id: string,       // Platform-specific user ID
 *   sender_name: string,
 *   message: string,
 *   conversation_id: string, // Platform thread/chat/channel ID
 *   timestamp: string,       // ISO 8601
 *   metadata: {}             // Channel-specific extras
 * }
 */

// ─── Helpers ───────────────────────────────────────────────────────────
function gatewayResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function logMessage(string $channel, string $senderId, string $direction, string $message): void {
    $db = getDB();
    if (!$db) return;
    
    try {
        $db->prepare("
            INSERT INTO alfred_gateway_messages 
            (channel, sender_id, direction, message, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ")->execute([$channel, substr($senderId, 0, 100), $direction, substr($message, 0, 5000)]);
    } catch (\Exception $e) {
        error_log("Gateway log error: " . $e->getMessage());
    }
}

function ensureGatewayTable(): void {
    $db = getDB();
    if (!$db) return;
    
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS alfred_gateway_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                channel VARCHAR(20) NOT NULL,
                sender_id VARCHAR(100) NOT NULL,
                direction ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
                message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_gw_channel (channel),
                INDEX idx_gw_sender (sender_id),
                INDEX idx_gw_date (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (\Exception $e) {
        // Table might already exist
    }
}

/**
 * Forward normalized message to Alfred AI and return response.
 */
function processWithAlfred(array $normalized): string {
    $message = $normalized['message'];
    $senderId = $normalized['sender_id'];
    $channel = $normalized['channel'];
    $senderPhone = $normalized['metadata']['phone'] ?? null;
    
    // Internal call to alfred-chat endpoint
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => SITE_URL . '/api/alfred-chat.php',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_POSTFIELDS     => json_encode([
            'message'  => $message,
            'agent'    => 'alfred',
            'channel'  => $channel,
            'external_user_id' => $senderId,
            'sender_phone' => $senderPhone,
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Gateway-Secret: ' . (defined('GATEWAY_SECRET') ? GATEWAY_SECRET : 'internal'),
        ],
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['reply'] ?? $data['response'] ?? 'I received your message but could not generate a response right now.';
}

// ─── Telegram Handler ──────────────────────────────────────────────────
function handleTelegram(): void {
    // Verify secret token header
    $secretToken = defined('TELEGRAM_WEBHOOK_SECRET') ? TELEGRAM_WEBHOOK_SECRET : '';
    if ($secretToken && !hash_equals($secretToken, $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '')) {
        gatewayResponse(['error' => 'Invalid secret token'], 403);
    }
    
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || !isset($body['message'])) {
        gatewayResponse(['ok' => true]); // ACK non-message updates
    }
    
    $msg = $body['message'];
    $text = $msg['text'] ?? '';
    $chatId = $msg['chat']['id'] ?? '';
    $userId = $msg['from']['id'] ?? '';
    $userName = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? ''));
    
    if (empty($text) || empty($chatId)) {
        gatewayResponse(['ok' => true]);
    }
    
    // Handle /start command
    if ($text === '/start') {
        sendTelegramMessage($chatId, "👋 Hello! I'm Alfred AI by GoSiteMe.\n\nI have access to 13,000+ tools. Just ask me anything!\n\nType /help for commands.");
        gatewayResponse(['ok' => true]);
    }
    
    if ($text === '/help') {
        sendTelegramMessage($chatId, "🤖 *Alfred AI Commands*\n\n/start — Welcome message\n/help — This help menu\n/tools — List tool categories\n/search [query] — Search the web\n\nOr just type naturally — I understand context!", 'Markdown');
        gatewayResponse(['ok' => true]);
    }
    
    // Normalize and process
    $normalized = [
        'channel'         => 'telegram',
        'sender_id'       => (string) $userId,
        'sender_name'     => $userName,
        'message'         => $text,
        'conversation_id' => (string) $chatId,
        'timestamp'       => date('c'),
        'metadata'        => ['chat_type' => $msg['chat']['type'] ?? 'private'],
    ];
    
    logMessage('telegram', (string) $userId, 'inbound', $text);
    
    $reply = processWithAlfred($normalized);
    
    logMessage('telegram', (string) $userId, 'outbound', $reply);
    sendTelegramMessage($chatId, $reply);
    
    gatewayResponse(['ok' => true]);
}

function sendTelegramMessage(string $chatId, string $text, string $parseMode = ''): void {
    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN)) {
        error_log('TELEGRAM_BOT_TOKEN not configured');
        return;
    }
    
    $payload = [
        'chat_id' => $chatId,
        'text'    => substr($text, 0, 4096), // Telegram limit
    ];
    
    if ($parseMode) {
        $payload['parse_mode'] = $parseMode;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ─── Discord Handler ───────────────────────────────────────────────────
function handleDiscord(): void {
    // Body already verified at router level; re-read php://input
    // (PHP allows multiple reads in most SAPI configs, but use global if set)
    global $__discord_body;
    $body = $__discord_body ?? file_get_contents('php://input');
    $data = json_decode($body, true);
    
    if (!$data) {
        gatewayResponse(['error' => 'Invalid JSON'], 400);
    }
    
    // Ping already handled at router level, but handle here too for safety
    if (($data['type'] ?? 0) === 1) {
        gatewayResponse(['type' => 1]);
    }
    
    // Slash command or message (type 2)
    if (($data['type'] ?? 0) === 2) {
        $userId = $data['member']['user']['id'] ?? $data['user']['id'] ?? '';
        $userName = $data['member']['user']['username'] ?? $data['user']['username'] ?? '';
        $text = '';
        
        // Extract command and options
        $commandName = $data['data']['name'] ?? '';
        $options = $data['data']['options'] ?? [];
        
        if (in_array($commandName, ['ask', 'search', 'research']) && !empty($options)) {
            $prefix = $commandName === 'ask' ? '' : $commandName . ': ';
            $text = $prefix . ($options[0]['value'] ?? '');
        } elseif ($commandName === 'status') {
            $text = '/status';
        } elseif ($commandName === 'tools') {
            $text = '/tools';
        } elseif ($commandName === 'help') {
            $text = '/help';
        } else {
            $text = $commandName . ' ' . implode(' ', array_column($options, 'value'));
        }
        
        $normalized = [
            'channel'         => 'discord',
            'sender_id'       => $userId,
            'sender_name'     => $userName,
            'message'         => trim($text),
            'conversation_id' => $data['channel_id'] ?? '',
            'timestamp'       => date('c'),
            'metadata'        => ['guild_id' => $data['guild_id'] ?? '', 'command' => $commandName],
        ];
        
        logMessage('discord', $userId, 'inbound', trim($text));
        $reply = processWithAlfred($normalized);
        logMessage('discord', $userId, 'outbound', $reply);
        
        // Respond to interaction
        gatewayResponse([
            'type' => 4,
            'data' => ['content' => substr($reply, 0, 2000)],
        ]);
    }
    
    gatewayResponse(['ok' => true]);
}

// ─── Slack Handler ─────────────────────────────────────────────────────
function handleSlack(): void {
    $body = file_get_contents('php://input');
    
    // Verify Slack signature — fail-closed if unconfigured
    if (!defined('SLACK_SIGNING_SECRET') || empty(SLACK_SIGNING_SECRET)) {
        gatewayResponse(['error' => 'Slack not configured'], 503);
    }
    $timestamp = $_SERVER['HTTP_X_SLACK_REQUEST_TIMESTAMP'] ?? '';
    $sigHeader = $_SERVER['HTTP_X_SLACK_SIGNATURE'] ?? '';
    
    if (abs(time() - (int) $timestamp) > 300) {
        gatewayResponse(['error' => 'Request too old'], 403);
    }
    
    $sigBasestring = 'v0:' . $timestamp . ':' . $body;
    $signature = 'v0=' . hash_hmac('sha256', $sigBasestring, SLACK_SIGNING_SECRET);
    
    if (!hash_equals($signature, $sigHeader)) {
        gatewayResponse(['error' => 'Invalid signature'], 403);
    }
    
    $data = json_decode($body, true);
    
    // URL verification challenge
    if (($data['type'] ?? '') === 'url_verification') {
        gatewayResponse(['challenge' => $data['challenge'] ?? '']);
    }
    
    // Event callback
    if (($data['type'] ?? '') === 'event_callback') {
        $event = $data['event'] ?? [];
        
        // Skip bot messages
        if (isset($event['bot_id'])) {
            gatewayResponse(['ok' => true]);
        }
        
        if (($event['type'] ?? '') === 'app_mention' || ($event['type'] ?? '') === 'message') {
            $text = $event['text'] ?? '';
            $userId = $event['user'] ?? '';
            $channelId = $event['channel'] ?? '';
            
            // Remove bot mention
            $text = preg_replace('/<@[A-Z0-9]+>\s*/', '', $text);
            $text = trim($text);
            
            if (empty($text)) {
                gatewayResponse(['ok' => true]);
            }
            
            $normalized = [
                'channel'         => 'slack',
                'sender_id'       => $userId,
                'sender_name'     => '',
                'message'         => $text,
                'conversation_id' => $channelId,
                'timestamp'       => date('c'),
                'metadata'        => ['team_id' => $data['team_id'] ?? ''],
            ];
            
            logMessage('slack', $userId, 'inbound', $text);
            $reply = processWithAlfred($normalized);
            logMessage('slack', $userId, 'outbound', $reply);
            
            // Post reply to Slack channel
            sendSlackMessage($channelId, $reply, $event['ts'] ?? '');
        }
    }
    
    gatewayResponse(['ok' => true]);
}

function sendSlackMessage(string $channel, string $text, string $threadTs = ''): void {
    if (!defined('SLACK_BOT_TOKEN') || empty(SLACK_BOT_TOKEN)) {
        error_log('SLACK_BOT_TOKEN not configured');
        return;
    }
    
    $payload = [
        'channel' => $channel,
        'text'    => substr($text, 0, 40000),
    ];
    
    if ($threadTs) {
        $payload['thread_ts'] = $threadTs;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://slack.com/api/chat.postMessage',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SLACK_BOT_TOKEN,
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ─── WhatsApp Handler ──────────────────────────────────────────────────
function handleWhatsApp(): void {
    // Webhook verification (GET request)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $mode = $_GET['hub_mode'] ?? '';
        $token = $_GET['hub_verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? '';
        
        $verifyToken = defined('WHATSAPP_VERIFY_TOKEN') ? WHATSAPP_VERIFY_TOKEN : '';
        
        if (!$verifyToken) {
            gatewayResponse(['error' => 'WhatsApp not configured'], 503);
        }
        if ($mode === 'subscribe' && hash_equals($verifyToken, $token)) {
            http_response_code(200);
            echo $challenge;
            exit;
        }
        gatewayResponse(['error' => 'Invalid verify token'], 403);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $entries = $data['entry'] ?? [];
    foreach ($entries as $entry) {
        $changes = $entry['changes'] ?? [];
        foreach ($changes as $change) {
            $messages = $change['value']['messages'] ?? [];
            foreach ($messages as $msg) {
                $from = $msg['from'] ?? '';
                $text = $msg['text']['body'] ?? '';
                $msgId = $msg['id'] ?? '';
                
                if (empty($text) || empty($from)) continue;
                
                $normalized = [
                    'channel'         => 'whatsapp',
                    'sender_id'       => $from,
                    'sender_name'     => '',
                    'message'         => $text,
                    'conversation_id' => $from, // 1:1 chats
                    'timestamp'       => date('c'),
                    'metadata'        => ['message_id' => $msgId],
                ];
                
                logMessage('whatsapp', $from, 'inbound', $text);
                $reply = processWithAlfred($normalized);
                logMessage('whatsapp', $from, 'outbound', $reply);
                
                sendWhatsAppMessage($from, $reply);
            }
        }
    }
    
    gatewayResponse(['ok' => true]);
}

function sendWhatsAppMessage(string $to, string $text): void {
    if (!defined('WHATSAPP_TOKEN') || !defined('WHATSAPP_PHONE_ID')) {
        error_log('WhatsApp not configured');
        return;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://graph.facebook.com/v18.0/' . WHATSAPP_PHONE_ID . '/messages',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POSTFIELDS     => json_encode([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => substr($text, 0, 4096)],
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WHATSAPP_TOKEN,
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ─── SMS Handler ───────────────────────────────────────────────────────
function handleSMS(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Telnyx webhook format
    $from = $data['data']['payload']['from']['phone_number'] ?? $data['from'] ?? '';
    $text = $data['data']['payload']['text'] ?? $data['text'] ?? $data['body'] ?? '';
    
    if (empty($text) || empty($from)) {
        gatewayResponse(['ok' => true]);
    }
    
    $normalized = [
        'channel'         => 'sms',
        'sender_id'       => $from,
        'sender_name'     => '',
        'message'         => $text,
        'conversation_id' => $from,
        'timestamp'       => date('c'),
        'metadata'        => ['phone' => $from],
    ];
    
    logMessage('sms', $from, 'inbound', $text);
    $reply = processWithAlfred($normalized);
    logMessage('sms', $from, 'outbound', $reply);
    
    sendSMSReply($from, $reply);
    
    gatewayResponse(['ok' => true]);
}

function sendSMSReply(string $to, string $text): void {
    if (!defined('TELNYX_API_KEY') || empty(TELNYX_API_KEY) || !defined('TELNYX_FROM_NUMBER') || empty(TELNYX_FROM_NUMBER)) {
        error_log('Telnyx SMS not configured');
        return;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.telnyx.com/v2/messages',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POSTFIELDS     => json_encode([
            'from' => TELNYX_FROM_NUMBER,
            'to'   => $to,
            'text' => substr($text, 0, 1600),
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . TELNYX_API_KEY,
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ─── Email Handler ─────────────────────────────────────────────────────
function handleEmail(): void {
    // SendGrid Inbound Parse webhook
    $from = $_POST['from'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $text = $_POST['text'] ?? $_POST['html'] ?? '';
    
    if (empty($text)) {
        gatewayResponse(['ok' => true]);
    }
    
    // Extract email from "Name <email>" format
    preg_match('/[\w.+-]+@[\w.-]+/', $from, $matches);
    $email = $matches[0] ?? $from;
    
    $normalized = [
        'channel'         => 'email',
        'sender_id'       => $email,
        'sender_name'     => $from,
        'message'         => $subject . "\n\n" . $text,
        'conversation_id' => $email,
        'timestamp'       => date('c'),
        'metadata'        => ['subject' => $subject],
    ];
    
    logMessage('email', $email, 'inbound', $subject);
    $reply = processWithAlfred($normalized);
    logMessage('email', $email, 'outbound', $reply);
    
    gatewayResponse(['ok' => true, 'reply' => $reply]);
}

// ─── Stats ─────────────────────────────────────────────────────────────
function getChannelStats(): void {
    session_start();
    if (empty($_SESSION['logged_in'])) {
        gatewayResponse(['error' => 'Authentication required'], 401);
    }
    
    $db = getDB();
    if (!$db) {
        gatewayResponse(['error' => 'Database unavailable'], 500);
    }
    
    ensureGatewayTable();
    
    try {
        // Messages per channel (last 30 days)
        $stmt = $db->query("
            SELECT channel, direction, COUNT(*) as count 
            FROM alfred_gateway_messages 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY channel, direction
            ORDER BY count DESC
        ");
        $stats = $stmt->fetchAll();
        
        // Total messages
        $total = $db->query("SELECT COUNT(*) FROM alfred_gateway_messages")->fetchColumn();
        
        // Daily trend
        $daily = $db->query("
            SELECT DATE(created_at) as date, channel, COUNT(*) as count
            FROM alfred_gateway_messages
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at), channel
            ORDER BY date ASC
        ")->fetchAll();
        
        gatewayResponse([
            'success'        => true,
            'total_messages'  => (int) $total,
            'by_channel'      => $stats,
            'daily_trend'     => $daily,
        ]);
    } catch (\Exception $e) {
        gatewayResponse(['error' => 'Stats query failed'], 500);
    }
}

// ─── Router ────────────────────────────────────────────────────────────

$channel = strtolower(sanitize($_GET['channel'] ?? '', 20));
$action = sanitize($_GET['action'] ?? '', 20);

// ─── Discord: Handle FIRST before any DB calls (Discord has strict timeout) ───
if ($channel === 'discord' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    $pubKey = getenv('DISCORD_PUBLIC_KEY') ?: '';
    $signature = $_SERVER['HTTP_X_SIGNATURE_ED25519'] ?? '';
    $timestamp = $_SERVER['HTTP_X_SIGNATURE_TIMESTAMP'] ?? '';
    
    // Discord REQUIRES signature verification — reject if missing
    if ($pubKey) {
        if (!$signature || !$timestamp) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing signature headers']);
            exit;
        }
        
        try {
            $message = $timestamp . $rawBody;
            $sigBin = sodium_hex2bin($signature);
            $pubKeyBin = sodium_hex2bin($pubKey);
            
            if (!sodium_crypto_sign_verify_detached($sigBin, $message, $pubKeyBin)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid signature']);
                exit;
            }
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Signature verification failed']);
            exit;
        }
    }
    
    $data = json_decode($rawBody, true);
    
    // Type 1 = PING (Discord endpoint verification)
    if (($data['type'] ?? 0) === 1) {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['type' => 1]);
        exit;
    }
    
    // For type 2+ interactions, store body and fall through to handler
    $GLOBALS['__discord_body'] = $rawBody;
}

// Non-Discord routes: ensure gateway table
ensureGatewayTable();

// Admin actions
if ($action === 'channels') {
    global $CHANNELS;
    gatewayResponse(['success' => true, 'channels' => $CHANNELS]);
}

if ($action === 'stats') {
    getChannelStats();
}

// Channel webhook handlers
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $channel !== 'whatsapp') {
    gatewayResponse(['error' => 'POST required for channel webhooks'], 405);
}

switch ($channel) {
    case 'telegram':
        handleTelegram();
        break;
    case 'discord':
        handleDiscord();
        break;
    case 'slack':
        handleSlack();
        break;
    case 'whatsapp':
        handleWhatsApp();
        break;
    case 'sms':
        handleSMS();
        break;
    case 'email':
        handleEmail();
        break;
    default:
        gatewayResponse(['error' => 'Unknown channel. Valid: telegram, discord, slack, whatsapp, sms, email'], 400);
}
