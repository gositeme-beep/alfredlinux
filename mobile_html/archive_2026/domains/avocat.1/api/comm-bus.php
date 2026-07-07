<?php
/**
 * Alfred Communication Bus API — Phase 4: Unified Messaging
 * ──────────────────────────────────────────────────────────
 * Event-driven message routing across 9 channels.
 * Proactive communication triggers, template system, scheduling.
 *
 * Channels: web_chat, sms, email, telegram, discord, slack, push, voice, whatsapp
 *
 * Endpoints:
 *   POST ?action=publish          → Publish event to bus
 *   POST ?action=send             → Send message on specific channel
 *   POST ?action=broadcast        → Broadcast to all channels
 *   GET  ?action=channels         → Channel configuration
 *   POST ?action=configure        → Configure a channel
 *   GET  ?action=events           → Event log
 *   GET  ?action=templates        → Message templates
 *   POST ?action=save-template    → Create/update template
 *   POST ?action=schedule         → Schedule a message
 *   GET  ?action=scheduled        → List scheduled messages
 *   POST ?action=trigger          → Trigger proactive outreach
 *   GET  ?action=stats            → Channel analytics
 *   GET  ?action=subscriptions    → User channel subscriptions
 *   POST ?action=subscribe        → Subscribe to channel
 *   POST ?action=unsubscribe      → Unsubscribe from channel
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function ensureCommBusSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS comm_events (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        event_id        VARCHAR(64) UNIQUE NOT NULL,
        event_type      VARCHAR(50) NOT NULL,
        payload         JSON NOT NULL,
        source          VARCHAR(50) DEFAULT 'system',
        target_channels JSON DEFAULT NULL,
        status          ENUM('pending','processing','delivered','failed','partial') DEFAULT 'pending',
        delivered_to    JSON DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at    TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_type (event_type),
        INDEX idx_status (status),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS comm_channels (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        channel         VARCHAR(30) UNIQUE NOT NULL,
        display_name    VARCHAR(50) NOT NULL,
        is_enabled      TINYINT(1) DEFAULT 0,
        config          JSON DEFAULT NULL,
        priority        INT DEFAULT 5,
        rate_limit_per_min INT DEFAULT 60,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS comm_messages (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        event_id        VARCHAR(64) DEFAULT NULL,
        channel         VARCHAR(30) NOT NULL,
        client_id       INT DEFAULT NULL,
        recipient       VARCHAR(200) NOT NULL,
        subject         VARCHAR(200) DEFAULT NULL,
        body            TEXT NOT NULL,
        template_id     VARCHAR(50) DEFAULT NULL,
        status          ENUM('queued','sent','delivered','failed','bounced') DEFAULT 'queued',
        error_message   VARCHAR(500) DEFAULT NULL,
        external_id     VARCHAR(200) DEFAULT NULL,
        scheduled_for   TIMESTAMP NULL DEFAULT NULL,
        sent_at         TIMESTAMP NULL DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_channel (channel),
        INDEX idx_client (client_id),
        INDEX idx_status (status),
        INDEX idx_scheduled (scheduled_for)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS comm_templates (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        template_id     VARCHAR(50) UNIQUE NOT NULL,
        name            VARCHAR(100) NOT NULL,
        channel         VARCHAR(30) DEFAULT 'all',
        subject         VARCHAR(200) DEFAULT NULL,
        body            TEXT NOT NULL,
        variables       JSON DEFAULT NULL,
        category        VARCHAR(50) DEFAULT 'general',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS comm_subscriptions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        client_id       INT NOT NULL,
        channel         VARCHAR(30) NOT NULL,
        endpoint        VARCHAR(500) NOT NULL,
        is_active       TINYINT(1) DEFAULT 1,
        preferences     JSON DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_sub (client_id, channel),
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed channels
    $count = $db->query("SELECT COUNT(*) FROM comm_channels")->fetchColumn();
    if ($count == 0) {
        $channels = [
            ['web_chat', 'Web Chat', 1, '{}', 1, 120],
            ['sms', 'SMS', 1, '{"provider":"twilio"}', 3, 30],
            ['email', 'Email', 1, '{"provider":"smtp"}', 5, 60],
            ['telegram', 'Telegram', 1, '{}', 2, 30],
            ['discord', 'Discord', 1, '{}', 4, 60],
            ['slack', 'Slack', 0, '{}', 4, 60],
            ['push', 'Push Notifications', 1, '{}', 2, 120],
            ['voice', 'Voice Call', 0, '{"provider":"vapi"}', 6, 10],
            ['whatsapp', 'WhatsApp', 0, '{"provider":"twilio"}', 3, 30],
        ];
        $stmt = $db->prepare("INSERT INTO comm_channels (channel, display_name, is_enabled, config, priority, rate_limit_per_min) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($channels as $c) $stmt->execute($c);
    }

    // Seed templates
    $tplCount = $db->query("SELECT COUNT(*) FROM comm_templates")->fetchColumn();
    if ($tplCount == 0) {
        $templates = [
            ['welcome', 'Welcome Message', 'all', 'Welcome to GoSiteMe!', 'Hi {{name}}, welcome to GoSiteMe! Alfred here — your AI assistant. Type /help to see what I can do.', '["name"]', 'onboarding'],
            ['daily_briefing', 'Daily Briefing', 'all', 'Your Daily Briefing', 'Good morning {{name}}! Here\'s your briefing:\n\n📊 Revenue: ${{revenue}}\n📈 Visitors: {{visitors}}\n🎯 Goals: {{goals_status}}\n\nHave a productive day!', '["name","revenue","visitors","goals_status"]', 'proactive'],
            ['goal_complete', 'Goal Completed', 'all', 'Goal Achieved! 🎉', 'Great news {{name}}! Goal "{{goal_name}}" has been completed with {{progress}}% progress.', '["name","goal_name","progress"]', 'notification'],
            ['security_alert', 'Security Alert', 'sms', 'Security Alert', '⚠️ Security alert for your account. {{details}}. If this wasn\'t you, please secure your account immediately.', '["details"]', 'security'],
            ['invoice_reminder', 'Invoice Reminder', 'email', 'Invoice {{invoice_number}} - Payment Due', 'Hi {{name}},\n\nThis is a reminder that invoice {{invoice_number}} for ${{amount}} is due on {{due_date}}.\n\nPlease make your payment at your earliest convenience.', '["name","invoice_number","amount","due_date"]', 'billing'],
            ['system_health', 'System Health Alert', 'telegram', 'System Alert', '🔧 System Health: {{service}} is {{status}}.\n\nSeverity: {{severity}}\nDetails: {{details}}', '["service","status","severity","details"]', 'system'],
        ];
        $stmt = $db->prepare("INSERT INTO comm_templates (template_id, name, channel, subject, body, variables, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($templates as $t) $stmt->execute($t);
    }

    return true;
}

// ─── Channel Dispatch ──────────────────────────────────────────────
function dispatchToChannel($channel, $recipient, $subject, $body, $eventId = null) {
    $db = getDB();

    // Check channel is enabled
    $ch = $db->prepare("SELECT is_enabled, config FROM comm_channels WHERE channel = ?");
    $ch->execute([$channel]);
    $channelConfig = $ch->fetch();
    if (!$channelConfig || !$channelConfig['is_enabled']) return ['status' => 'failed', 'error' => 'Channel disabled'];

    // Log message
    $db->prepare("INSERT INTO comm_messages (event_id, channel, recipient, subject, body, status) VALUES (?, ?, ?, ?, ?, 'queued')")->execute([
        $eventId, $channel, $recipient, $subject, $body
    ]);
    $messageId = $db->lastInsertId();

    $result = ['status' => 'queued', 'message_id' => $messageId];
    $internalSecret = getenv('INTERNAL_SECRET') ?: '';

    switch ($channel) {
        case 'sms':
            if ($internalSecret) {
                $ch2 = curl_init(SITE_URL . '/api/messaging-gateway.php?action=send');
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $internalSecret],
                    CURLOPT_POSTFIELDS => json_encode(['channel' => 'sms', 'to' => $recipient, 'body' => $body]),
                    CURLOPT_TIMEOUT => 10,
                ]);
                $resp = json_decode(curl_exec($ch2), true);
                curl_close($ch2);
                $result['status'] = ($resp['success'] ?? false) ? 'sent' : 'failed';
            }
            break;

        case 'email':
            if ($internalSecret) {
                $ch2 = curl_init(SITE_URL . '/api/messaging-gateway.php?action=send');
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $internalSecret],
                    CURLOPT_POSTFIELDS => json_encode(['channel' => 'email', 'to' => $recipient, 'subject' => $subject, 'body' => $body]),
                    CURLOPT_TIMEOUT => 10,
                ]);
                $resp = json_decode(curl_exec($ch2), true);
                curl_close($ch2);
                $result['status'] = ($resp['success'] ?? false) ? 'sent' : 'failed';
            }
            break;

        case 'telegram':
            if ($internalSecret) {
                $ch2 = curl_init(SITE_URL . '/api/telegram-bot.php?action=send');
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $internalSecret],
                    CURLOPT_POSTFIELDS => json_encode(['chat_id' => $recipient, 'message' => $body]),
                    CURLOPT_TIMEOUT => 10,
                ]);
                curl_exec($ch2);
                curl_close($ch2);
                $result['status'] = 'sent';
            }
            break;

        case 'push':
            if ($internalSecret) {
                $ch2 = curl_init(SITE_URL . '/api/push-notifications.php?action=send');
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $internalSecret],
                    CURLOPT_POSTFIELDS => json_encode(['title' => $subject ?? 'Alfred', 'body' => $body, 'target' => $recipient]),
                    CURLOPT_TIMEOUT => 10,
                ]);
                curl_exec($ch2);
                curl_close($ch2);
                $result['status'] = 'sent';
            }
            break;

        case 'web_chat':
        case 'discord':
        case 'slack':
        case 'voice':
        case 'whatsapp':
            // Queued for processing by respective services
            $result['status'] = 'queued';
            break;
    }

    // Update message status
    $db->prepare("UPDATE comm_messages SET status = ?, sent_at = IF(? = 'sent', NOW(), NULL) WHERE id = ?")->execute([$result['status'], $result['status'], $messageId]);

    return $result;
}

function renderTemplate($templateBody, $vars) {
    foreach ($vars as $key => $val) {
        $templateBody = str_replace('{{' . $key . '}}', $val, $templateBody);
    }
    return $templateBody;
}

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureCommBusSchema();

switch ($action) {

    case 'publish':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $eventType = sanitize($input['event_type'] ?? '', 50);
        $payload = $input['payload'] ?? [];
        if (!$eventType) jsonResponse(['error' => 'event_type required'], 400);

        $eventId = 'evt_' . bin2hex(random_bytes(16));
        $targetChannels = $input['target_channels'] ?? null;

        $db->prepare("INSERT INTO comm_events (event_id, event_type, payload, source, target_channels) VALUES (?, ?, ?, ?, ?)")->execute([
            $eventId, $eventType, json_encode($payload), sanitize($input['source'] ?? 'system', 50), $targetChannels ? json_encode($targetChannels) : null
        ]);

        // Auto-route based on event type
        $results = [];
        $channels = $targetChannels;
        if (!$channels) {
            // Default routing rules
            $routing = [
                'security_alert'  => ['sms', 'telegram', 'push'],
                'goal_complete'   => ['web_chat', 'push', 'telegram'],
                'daily_briefing'  => ['telegram', 'email'],
                'system_health'   => ['telegram'],
                'invoice_due'     => ['email', 'push'],
                'new_user'        => ['telegram'],
            ];
            $channels = $routing[$eventType] ?? ['web_chat'];
        }

        $delivered = [];
        foreach ($channels as $ch) {
            $recipient = $payload['recipient'] ?? $payload['chat_id'] ?? $payload['email'] ?? $payload['phone'] ?? 'admin';
            $subject = $payload['subject'] ?? ucfirst(str_replace('_', ' ', $eventType));
            $body = $payload['message'] ?? $payload['body'] ?? json_encode($payload);

            $r = dispatchToChannel($ch, $recipient, $subject, $body, $eventId);
            $results[$ch] = $r['status'];
            if ($r['status'] === 'sent' || $r['status'] === 'queued') $delivered[] = $ch;
        }

        $overallStatus = count($delivered) === count($channels) ? 'delivered' : (count($delivered) > 0 ? 'partial' : 'failed');
        $db->prepare("UPDATE comm_events SET status = ?, delivered_to = ?, processed_at = NOW() WHERE event_id = ?")->execute([
            $overallStatus, json_encode($delivered), $eventId
        ]);

        jsonResponse(['success' => true, 'event_id' => $eventId, 'results' => $results, 'status' => $overallStatus]);
        break;

    case 'send':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $channel = sanitize($input['channel'] ?? '', 30);
        $recipient = sanitize($input['recipient'] ?? '', 200);
        $body = $input['body'] ?? $input['message'] ?? '';

        if (!$channel || !$recipient || !$body) jsonResponse(['error' => 'channel, recipient, and body required'], 400);

        // Apply template if specified
        $templateId = sanitize($input['template_id'] ?? '', 50);
        $subject = $input['subject'] ?? null;
        if ($templateId) {
            $tpl = $db->prepare("SELECT * FROM comm_templates WHERE template_id = ?");
            $tpl->execute([$templateId]);
            $template = $tpl->fetch();
            if ($template) {
                $body = renderTemplate($template['body'], $input['variables'] ?? []);
                $subject = $subject ?? $template['subject'];
            }
        }

        $result = dispatchToChannel($channel, $recipient, $subject, $body);
        jsonResponse(['success' => true, 'result' => $result]);
        break;

    case 'broadcast':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $body = $input['body'] ?? $input['message'] ?? '';
        $subject = $input['subject'] ?? 'Alfred Broadcast';
        if (!$body) jsonResponse(['error' => 'body required'], 400);

        $channels = $db->query("SELECT channel FROM comm_channels WHERE is_enabled = 1")->fetchAll(PDO::FETCH_COLUMN);
        $results = [];

        foreach ($channels as $ch) {
            $recipient = $input['recipient'] ?? 'all';
            $r = dispatchToChannel($ch, $recipient, $subject, $body);
            $results[$ch] = $r['status'];
        }

        jsonResponse(['success' => true, 'results' => $results]);
        break;

    case 'channels':
        if (!isInternalCall()) requireAuth();
        $stmt = $db->query("SELECT channel, display_name, is_enabled, priority, rate_limit_per_min FROM comm_channels ORDER BY priority");
        jsonResponse(['success' => true, 'channels' => $stmt->fetchAll()]);
        break;

    case 'configure':
        requireAuth();
        if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $channel = sanitize($input['channel'] ?? '', 30);
        if (!$channel) jsonResponse(['error' => 'channel required'], 400);

        $updates = [];
        $params = [];

        if (isset($input['is_enabled'])) { $updates[] = "is_enabled = ?"; $params[] = $input['is_enabled'] ? 1 : 0; }
        if (isset($input['config']) && is_array($input['config'])) { $updates[] = "config = ?"; $params[] = json_encode($input['config']); }
        if (isset($input['priority'])) { $updates[] = "priority = ?"; $params[] = max(1, min(10, intval($input['priority']))); }
        if (isset($input['rate_limit_per_min'])) { $updates[] = "rate_limit_per_min = ?"; $params[] = max(1, intval($input['rate_limit_per_min'])); }

        if (empty($updates)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $channel;
        $db->prepare("UPDATE comm_channels SET " . implode(', ', $updates) . " WHERE channel = ?")->execute($params);

        jsonResponse(['success' => true]);
        break;

    case 'events':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);
        $type = sanitize($_GET['type'] ?? '', 50);

        $where = "1=1";
        $params = [];
        if ($type) { $where .= " AND event_type = ?"; $params[] = $type; }

        $params[] = $limit;
        $stmt = $db->prepare("SELECT * FROM comm_events WHERE $where ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, $params);

        $events = $stmt->fetchAll();
        foreach ($events as &$e) {
            $e['payload'] = json_decode($e['payload'], true);
            $e['target_channels'] = json_decode($e['target_channels'], true);
            $e['delivered_to'] = json_decode($e['delivered_to'], true);
        }

        jsonResponse(['success' => true, 'events' => $events]);
        break;

    case 'templates':
        if (!isInternalCall()) requireAuth();
        $stmt = $db->query("SELECT * FROM comm_templates ORDER BY category, name");
        $templates = $stmt->fetchAll();
        foreach ($templates as &$t) $t['variables'] = json_decode($t['variables'], true);
        jsonResponse(['success' => true, 'templates' => $templates]);
        break;

    case 'save-template':
        requireAuth();
        if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $templateId = sanitize($input['template_id'] ?? '', 50);
        $name = sanitize($input['name'] ?? '', 100);
        $body = $input['body'] ?? '';

        if (!$templateId || !$name || !$body) jsonResponse(['error' => 'template_id, name, and body required'], 400);

        $db->prepare("INSERT INTO comm_templates (template_id, name, channel, subject, body, variables, category) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), channel = VALUES(channel), subject = VALUES(subject), body = VALUES(body), variables = VALUES(variables), category = VALUES(category)")->execute([
            $templateId, $name,
            sanitize($input['channel'] ?? 'all', 30),
            sanitize($input['subject'] ?? '', 200) ?: null,
            $body,
            json_encode($input['variables'] ?? []),
            sanitize($input['category'] ?? 'general', 50),
        ]);

        jsonResponse(['success' => true]);
        break;

    case 'schedule':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $channel = sanitize($input['channel'] ?? '', 30);
        $recipient = sanitize($input['recipient'] ?? '', 200);
        $body = $input['body'] ?? '';
        $scheduledFor = $input['scheduled_for'] ?? '';

        if (!$channel || !$recipient || !$body || !$scheduledFor) jsonResponse(['error' => 'channel, recipient, body, and scheduled_for required'], 400);

        $db->prepare("INSERT INTO comm_messages (channel, recipient, subject, body, template_id, status, scheduled_for) VALUES (?, ?, ?, ?, ?, 'queued', ?)")->execute([
            $channel, $recipient, sanitize($input['subject'] ?? '', 200) ?: null, $body,
            sanitize($input['template_id'] ?? '', 50) ?: null, $scheduledFor
        ]);

        jsonResponse(['success' => true, 'message_id' => $db->lastInsertId(), 'scheduled_for' => $scheduledFor]);
        break;

    case 'scheduled':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $stmt = $db->query("SELECT * FROM comm_messages WHERE scheduled_for IS NOT NULL AND status = 'queued' ORDER BY scheduled_for");
        jsonResponse(['success' => true, 'scheduled' => $stmt->fetchAll()]);
        break;

    case 'trigger':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $triggerType = sanitize($input['trigger'] ?? '', 50);

        $triggers = [
            'daily_briefing' => ['channels' => ['telegram','email'], 'template' => 'daily_briefing'],
            'weekly_recap'   => ['channels' => ['email'], 'template' => null],
            'goal_reminder'  => ['channels' => ['push','telegram'], 'template' => null],
            'health_check'   => ['channels' => ['telegram'], 'template' => 'system_health'],
            'invoice_chase'  => ['channels' => ['email'], 'template' => 'invoice_reminder'],
        ];

        if (!isset($triggers[$triggerType])) {
            jsonResponse(['error' => 'Unknown trigger', 'available_triggers' => array_keys($triggers)], 400);
        }

        $trigger = $triggers[$triggerType];
        $variables = $input['variables'] ?? [];
        $recipient = $input['recipient'] ?? 'admin';

        $results = [];
        foreach ($trigger['channels'] as $ch) {
            $body = $input['body'] ?? '';
            $subject = $input['subject'] ?? ucfirst(str_replace('_', ' ', $triggerType));

            if ($trigger['template']) {
                $tpl = $db->prepare("SELECT * FROM comm_templates WHERE template_id = ?");
                $tpl->execute([$trigger['template']]);
                $template = $tpl->fetch();
                if ($template) {
                    $body = renderTemplate($template['body'], $variables);
                    $subject = $template['subject'] ? renderTemplate($template['subject'], $variables) : $subject;
                }
            }

            $r = dispatchToChannel($ch, $recipient, $subject, $body);
            $results[$ch] = $r['status'];
        }

        jsonResponse(['success' => true, 'trigger' => $triggerType, 'results' => $results]);
        break;

    case 'stats':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $byChannel = $db->query("SELECT channel, status, COUNT(*) as c FROM comm_messages GROUP BY channel, status")->fetchAll();
        $totalEvents = $db->query("SELECT COUNT(*) FROM comm_events")->fetchColumn();
        $totalMessages = $db->query("SELECT COUNT(*) FROM comm_messages")->fetchColumn();
        $sentToday = $db->query("SELECT COUNT(*) FROM comm_messages WHERE sent_at >= CURDATE()")->fetchColumn();

        $channelStats = [];
        foreach ($byChannel as $r) {
            if (!isset($channelStats[$r['channel']])) $channelStats[$r['channel']] = [];
            $channelStats[$r['channel']][$r['status']] = (int)$r['c'];
        }

        jsonResponse([
            'success' => true,
            'stats' => [
                'total_events' => (int)$totalEvents,
                'total_messages' => (int)$totalMessages,
                'sent_today' => (int)$sentToday,
                'by_channel' => $channelStats,
            ],
        ]);
        break;

    case 'subscriptions':
        requireAuth();
        $stmt = $db->prepare("SELECT s.*, c.display_name FROM comm_subscriptions s JOIN comm_channels c ON s.channel = c.channel WHERE s.client_id = ?");
        $stmt->execute([$_SESSION['client_id']]);
        jsonResponse(['success' => true, 'subscriptions' => $stmt->fetchAll()]);
        break;

    case 'subscribe':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $channel = sanitize($input['channel'] ?? '', 30);
        $endpoint = sanitize($input['endpoint'] ?? '', 500);
        if (!$channel || !$endpoint) jsonResponse(['error' => 'channel and endpoint required'], 400);

        try {
            $db->prepare("INSERT INTO comm_subscriptions (client_id, channel, endpoint, preferences) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), is_active = 1, preferences = VALUES(preferences)")->execute([
                $_SESSION['client_id'], $channel, $endpoint, json_encode($input['preferences'] ?? [])
            ]);
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Subscription failed'], 500);
        }
        break;

    case 'unsubscribe':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $channel = sanitize($input['channel'] ?? '', 30);
        if (!$channel) jsonResponse(['error' => 'channel required'], 400);

        $db->prepare("UPDATE comm_subscriptions SET is_active = 0 WHERE client_id = ? AND channel = ?")->execute([$_SESSION['client_id'], $channel]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['publish','send','broadcast','channels','configure','events','templates','save-template','schedule','scheduled','trigger','stats','subscriptions','subscribe','unsubscribe']], 400);
}
