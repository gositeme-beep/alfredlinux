<?php
/**
 * GSM Alfred OS — MQTT Broker Infrastructure v1.0
 * Message Queuing Telemetry Transport for Alfred Robot Fleet
 *
 * Endpoints:
 *   POST   ?action=publish           — Publish message to topic
 *   POST   ?action=subscribe         — Register device subscription
 *   POST   ?action=unsubscribe       — Remove device subscription
 *   GET    ?action=topics             — List active topics
 *   GET    ?action=messages           — Get recent messages for a topic
 *   POST   ?action=broadcast          — Broadcast to all devices in a group
 *   GET    ?action=device_subs        — Get subscriptions for a device
 *   POST   ?action=command            — Send command via MQTT to device
 *   GET    ?action=health             — MQTT broker health check
 *
 * Supports QoS 0, 1, 2; topic hierarchy with wildcards (+, #);
 * retained messages; will messages; per-topic ACLs.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Device-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
mqttEnsureSchema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'health';

switch ($action) {
    case 'publish':      handlePublish($auth); break;
    case 'subscribe':    handleSubscribe($auth); break;
    case 'unsubscribe':  handleUnsubscribe($auth); break;
    case 'topics':       handleTopics($auth); break;
    case 'messages':     handleMessages($auth); break;
    case 'broadcast':    handleBroadcast($auth); break;
    case 'device_subs':  handleDeviceSubs($auth); break;
    case 'command':      handleCommand($auth); break;
    case 'health':       handleHealth($auth); break;
    default:             agentos_error('Unknown action');
}

// ── Schema ─────────────────────────────────────────────────────

function mqttEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = agentos_pdo();
    $r = $pdo->query("SHOW TABLES LIKE 'agentos_mqtt_topics'");
    if ($r->rowCount() > 0) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_mqtt_topics (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            topic           VARCHAR(512) NOT NULL UNIQUE,
            topic_type      ENUM('telemetry','command','status','alert','system','custom') NOT NULL DEFAULT 'custom',
            description     VARCHAR(512),
            retain_last     TINYINT(1) NOT NULL DEFAULT 0,
            max_qos         TINYINT UNSIGNED NOT NULL DEFAULT 1,
            ttl_seconds     INT UNSIGNED NOT NULL DEFAULT 86400 COMMENT 'Message retention period',
            acl_read        JSON COMMENT 'Array of device_ids/roles allowed to subscribe',
            acl_write       JSON COMMENT 'Array of device_ids/roles allowed to publish',
            message_count   BIGINT UNSIGNED NOT NULL DEFAULT 0,
            last_message_at TIMESTAMP NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type (topic_type),
            INDEX idx_topic_prefix (topic(128))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_mqtt_messages (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id      VARCHAR(64) NOT NULL UNIQUE,
            topic           VARCHAR(512) NOT NULL,
            payload         MEDIUMTEXT NOT NULL,
            payload_format  ENUM('json','binary','text','protobuf') NOT NULL DEFAULT 'json',
            qos             TINYINT UNSIGNED NOT NULL DEFAULT 0,
            retained        TINYINT(1) NOT NULL DEFAULT 0,
            sender_id       VARCHAR(128) NOT NULL COMMENT 'device_id or user_id',
            sender_type     ENUM('device','user','system','agent') NOT NULL DEFAULT 'device',
            correlation_id  VARCHAR(64) COMMENT 'For request-response pattern',
            expires_at      TIMESTAMP NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_topic (topic(128)),
            INDEX idx_sender (sender_id(64)),
            INDEX idx_created (created_at),
            INDEX idx_expires (expires_at),
            INDEX idx_correlation (correlation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_mqtt_subscriptions (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subscriber_id   VARCHAR(128) NOT NULL COMMENT 'device_id or user_id',
            subscriber_type ENUM('device','user','agent','service') NOT NULL DEFAULT 'device',
            topic_filter    VARCHAR(512) NOT NULL COMMENT 'Supports + and # wildcards',
            qos             TINYINT UNSIGNED NOT NULL DEFAULT 1,
            is_active       TINYINT(1) NOT NULL DEFAULT 1,
            last_delivered  TIMESTAMP NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sub_topic (subscriber_id(128), topic_filter(256)),
            INDEX idx_subscriber (subscriber_id(128)),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_mqtt_will_messages (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            device_id       VARCHAR(128) NOT NULL UNIQUE,
            topic           VARCHAR(512) NOT NULL,
            payload         TEXT NOT NULL,
            qos             TINYINT UNSIGNED NOT NULL DEFAULT 1,
            retain          TINYINT(1) NOT NULL DEFAULT 0,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_device (device_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Seed default topics for Alfred Robot fleet
    $defaultTopics = [
        ['fleet/+/telemetry',    'telemetry', 'Device telemetry data',   0, 1, 3600],
        ['fleet/+/status',       'status',    'Device online/offline',   1, 1, 86400],
        ['fleet/+/command',      'command',   'Commands to devices',     0, 2, 300],
        ['fleet/+/alert',        'alert',     'Device alerts/warnings',  0, 1, 86400],
        ['fleet/+/firmware',     'system',    'Firmware update signals',  0, 2, 3600],
        ['fleet/+/safety',       'alert',     'Safety system events',    1, 2, 604800],
        ['fleet/+/geofence',     'alert',     'Geofence breach events',  1, 2, 604800],
        ['fleet/+/sensor/+',     'telemetry', 'Individual sensor data',  0, 0, 1800],
        ['fleet/broadcast',      'system',    'Broadcast to all robots', 0, 1, 3600],
        ['system/health',        'system',    'System health checks',    1, 0, 600],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO agentos_mqtt_topics 
        (topic, topic_type, description, retain_last, max_qos, ttl_seconds) VALUES (?,?,?,?,?,?)");
    foreach ($defaultTopics as $t) $stmt->execute($t);

    error_log("[AGENTOS-MQTT] Schema auto-migrated with default topics");
}

// ── Handlers ───────────────────────────────────────────────────

function handlePublish(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) agentos_error('Invalid JSON body');

    $topic = validateTopic($input['topic'] ?? '');
    $payload = $input['payload'] ?? '';
    $qos = min(2, max(0, intval($input['qos'] ?? 0)));
    $retained = (bool)($input['retained'] ?? false);
    $senderId = $auth['device_id'] ?? $auth['user_id'] ?? 'unknown';
    $senderType = $auth['device_id'] ? 'device' : 'user';

    // ACL check
    if (!checkTopicAcl($topic, $senderId, 'write')) {
        agentos_error('Not authorized to publish to this topic', 403);
    }

    // Get topic config
    $pdo = agentos_pdo();
    $topicConfig = getTopicConfig($topic);
    $ttl = $topicConfig['ttl_seconds'] ?? 86400;

    // Enforce max QoS
    if ($topicConfig && $qos > $topicConfig['max_qos']) {
        $qos = $topicConfig['max_qos'];
    }

    $msgId = agentos_id('msg');
    $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

    $payloadStr = is_array($payload) ? json_encode($payload) : (string)$payload;
    $format = is_array($payload) ? 'json' : ($input['payload_format'] ?? 'text');

    $stmt = $pdo->prepare("INSERT INTO agentos_mqtt_messages 
        (message_id, topic, payload, payload_format, qos, retained, sender_id, sender_type, correlation_id, expires_at)
        VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $msgId, $topic, $payloadStr, $format, $qos, $retained ? 1 : 0,
        $senderId, $senderType, $input['correlation_id'] ?? null, $expiresAt
    ]);

    // Update topic message count
    $pdo->prepare("UPDATE agentos_mqtt_topics SET message_count = message_count + 1, last_message_at = NOW() WHERE topic = ?")
        ->execute([$topic]);

    // Fan-out: deliver to matching subscribers via WebSocket
    $subscribers = getMatchingSubscribers($topic);
    foreach ($subscribers as $sub) {
        agentos_push("device:{$sub['subscriber_id']}", 'mqtt_message', [
            'message_id' => $msgId,
            'topic' => $topic,
            'payload' => $payload,
            'qos' => $qos,
            'sender_id' => $senderId
        ]);

        // Update last_delivered
        $pdo->prepare("UPDATE agentos_mqtt_subscriptions SET last_delivered = NOW() WHERE id = ?")
            ->execute([$sub['id']]);
    }

    // Redis pub/sub for real-time consumers
    $redis = agentos_redis();
    if ($redis) {
        $redis->publish("mqtt:{$topic}", json_encode([
            'message_id' => $msgId,
            'payload' => $payload,
            'sender_id' => $senderId,
            'qos' => $qos,
            'timestamp' => time()
        ]));
    }

    agentos_respond(['ok' => true, 'message_id' => $msgId, 'subscribers_notified' => count($subscribers)]);
}

function handleSubscribe(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $topicFilter = validateTopic($input['topic_filter'] ?? '', true);
    $subscriberId = $auth['device_id'] ?? $auth['user_id'] ?? '';
    $subscriberType = $auth['device_id'] ? 'device' : 'user';
    $qos = min(2, max(0, intval($input['qos'] ?? 1)));

    if (!$subscriberId) agentos_error('Authentication required');

    if (!checkTopicAcl($topicFilter, $subscriberId, 'read')) {
        agentos_error('Not authorized to subscribe to this topic', 403);
    }

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("INSERT INTO agentos_mqtt_subscriptions 
        (subscriber_id, subscriber_type, topic_filter, qos, is_active)
        VALUES (?,?,?,?,1)
        ON DUPLICATE KEY UPDATE qos = VALUES(qos), is_active = 1");
    $stmt->execute([$subscriberId, $subscriberType, $topicFilter, $qos]);

    agentos_respond(['ok' => true, 'subscribed' => $topicFilter, 'qos' => $qos]);
}

function handleUnsubscribe(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $topicFilter = validateTopic($input['topic_filter'] ?? '', true);
    $subscriberId = $auth['device_id'] ?? $auth['user_id'] ?? '';

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("UPDATE agentos_mqtt_subscriptions SET is_active = 0 
        WHERE subscriber_id = ? AND topic_filter = ?");
    $stmt->execute([$subscriberId, $topicFilter]);

    agentos_respond(['ok' => true, 'unsubscribed' => $topicFilter]);
}

function handleTopics(array $auth): void {
    $pdo = agentos_pdo();
    $type = $_GET['type'] ?? null;

    $sql = "SELECT topic, topic_type, description, retain_last, max_qos, message_count, last_message_at
            FROM agentos_mqtt_topics";
    $params = [];

    if ($type && in_array($type, ['telemetry','command','status','alert','system','custom'])) {
        $sql .= " WHERE topic_type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY message_count DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);

    agentos_respond(['ok' => true, 'topics' => $stmt->fetchAll()]);
}

function handleMessages(array $auth): void {
    $pdo = agentos_pdo();
    $topic = validateTopic($_GET['topic'] ?? '');
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $since = $_GET['since'] ?? null;

    $sql = "SELECT message_id, topic, payload, payload_format, qos, retained, sender_id, sender_type, created_at
            FROM agentos_mqtt_messages WHERE topic = ?";
    $params = [$topic];

    if ($since) {
        $sql .= " AND created_at > ?";
        $params[] = $since;
    }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);

    agentos_respond(['ok' => true, 'topic' => $topic, 'messages' => $stmt->fetchAll()]);
}

function handleBroadcast(array $auth): void {
    if (!$auth['is_internal'] && !mqttIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $payload = $input['payload'] ?? '';
    $groupId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['group_id'] ?? '');

    $pdo = agentos_pdo();

    // Get target devices
    if ($groupId) {
        $stmt = $pdo->prepare("SELECT device_id FROM agentos_devices WHERE group_id = ? AND status = 'active'");
        $stmt->execute([$groupId]);
    } else {
        $stmt = $pdo->query("SELECT device_id FROM agentos_devices WHERE status = 'active'");
    }
    $devices = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $msgId = agentos_id('bcast');

    // Store broadcast message
    $pdo->prepare("INSERT INTO agentos_mqtt_messages 
        (message_id, topic, payload, payload_format, qos, sender_id, sender_type)
        VALUES (?,?,?,?,?,?,?)")
        ->execute([$msgId, 'fleet/broadcast', json_encode($payload), 'json', 1, 
                   $auth['user_id'] ?? 'system', 'system']);

    // Fan-out to all devices
    foreach ($devices as $deviceId) {
        agentos_push("device:{$deviceId}", 'mqtt_broadcast', [
            'message_id' => $msgId,
            'payload' => $payload,
            'timestamp' => time()
        ]);
    }

    agentos_audit([
        'action_type' => 'mqtt_broadcast',
        'user_id' => $auth['user_id'],
        'risk_level' => 'high',
        'status' => 'completed',
        'input' => ['group_id' => $groupId, 'device_count' => count($devices)]
    ]);

    agentos_respond(['ok' => true, 'message_id' => $msgId, 'devices_notified' => count($devices)]);
}

function handleDeviceSubs(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $stmt = $pdo->prepare("SELECT topic_filter, qos, is_active, last_delivered, created_at
        FROM agentos_mqtt_subscriptions WHERE subscriber_id = ? ORDER BY created_at DESC");
    $stmt->execute([$deviceId]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'subscriptions' => $stmt->fetchAll()]);
}

function handleCommand(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $command = $input['command'] ?? '';
    $params = $input['params'] ?? [];

    if (!$deviceId) agentos_error('device_id required');
    if (!$command) agentos_error('command required');

    $topic = "fleet/{$deviceId}/command";
    $correlationId = agentos_id('cmd');

    $payload = [
        'command' => $command,
        'params' => $params,
        'correlation_id' => $correlationId,
        'timestamp' => time(),
        'sender' => $auth['user_id'] ?? 'system'
    ];

    $pdo = agentos_pdo();
    $msgId = agentos_id('msg');

    $pdo->prepare("INSERT INTO agentos_mqtt_messages 
        (message_id, topic, payload, payload_format, qos, sender_id, sender_type, correlation_id)
        VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$msgId, $topic, json_encode($payload), 'json', 2,
                   $auth['user_id'] ?? 'system', 'user', $correlationId]);

    agentos_push("device:{$deviceId}", 'mqtt_command', $payload);

    agentos_audit([
        'action_type' => 'mqtt_command',
        'user_id' => $auth['user_id'],
        'risk_level' => 'high',
        'status' => 'completed',
        'input' => ['device_id' => $deviceId, 'command' => $command]
    ]);

    agentos_respond(['ok' => true, 'message_id' => $msgId, 'correlation_id' => $correlationId]);
}

function handleHealth(array $auth): void {
    $pdo = agentos_pdo();

    $topicCount = $pdo->query("SELECT COUNT(*) FROM agentos_mqtt_topics")->fetchColumn();
    $msgCount = $pdo->query("SELECT COUNT(*) FROM agentos_mqtt_messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
    $activeSubs = $pdo->query("SELECT COUNT(*) FROM agentos_mqtt_subscriptions WHERE is_active = 1")->fetchColumn();

    $redis = agentos_redis();
    $redisAlive = $redis ? true : false;

    agentos_respond([
        'ok' => true,
        'broker' => 'gsm-mqtt-v1',
        'topics' => (int)$topicCount,
        'messages_last_hour' => (int)$msgCount,
        'active_subscriptions' => (int)$activeSubs,
        'redis_connected' => $redisAlive,
        'features' => [
            'qos_levels' => [0, 1, 2],
            'wildcards' => true,
            'retained_messages' => true,
            'will_messages' => true,
            'topic_acls' => true,
            'websocket_fanout' => true
        ]
    ]);
}

// ── Topic Helpers ──────────────────────────────────────────────

function validateTopic(string $topic, bool $allowWildcards = false): string {
    $topic = trim($topic);
    if (strlen($topic) === 0 || strlen($topic) > 512) {
        agentos_error('Topic must be 1-512 characters');
    }
    // Allow alphanumeric, /, -, _, .
    if (!$allowWildcards) {
        if (!preg_match('#^[a-zA-Z0-9/_.\-]+$#', $topic)) {
            agentos_error('Topic contains invalid characters');
        }
    } else {
        if (!preg_match('#^[a-zA-Z0-9/_.\-+#]+$#', $topic)) {
            agentos_error('Topic filter contains invalid characters');
        }
        // Validate wildcard usage
        if (strpos($topic, '#') !== false && substr($topic, -1) !== '#') {
            agentos_error('Multi-level wildcard # must be last character');
        }
    }
    return $topic;
}

function getTopicConfig(string $topic): ?array {
    $pdo = agentos_pdo();
    // Exact match first
    $stmt = $pdo->prepare("SELECT * FROM agentos_mqtt_topics WHERE topic = ?");
    $stmt->execute([$topic]);
    $config = $stmt->fetch();
    if ($config) return $config;

    // Try wildcard matches
    $parts = explode('/', $topic);
    $stmt = $pdo->query("SELECT * FROM agentos_mqtt_topics WHERE topic LIKE '%+%' OR topic LIKE '%#%'");
    while ($row = $stmt->fetch()) {
        if (topicMatches($row['topic'], $topic)) return $row;
    }
    return null;
}

function topicMatches(string $filter, string $topic): bool {
    $filterParts = explode('/', $filter);
    $topicParts = explode('/', $topic);

    for ($i = 0; $i < count($filterParts); $i++) {
        if ($filterParts[$i] === '#') return true;
        if (!isset($topicParts[$i])) return false;
        if ($filterParts[$i] !== '+' && $filterParts[$i] !== $topicParts[$i]) return false;
    }
    return count($filterParts) === count($topicParts);
}

function checkTopicAcl(string $topic, string $entityId, string $mode): bool {
    $config = getTopicConfig($topic);
    if (!$config) return true; // No config = open topic

    $aclField = $mode === 'write' ? 'acl_write' : 'acl_read';
    $acl = $config[$aclField] ? json_decode($config[$aclField], true) : null;

    if (!$acl) return true; // No ACL = open
    return in_array($entityId, $acl) || in_array('*', $acl);
}

function getMatchingSubscribers(string $topic): array {
    $pdo = agentos_pdo();
    $stmt = $pdo->query("SELECT id, subscriber_id, subscriber_type, topic_filter FROM agentos_mqtt_subscriptions WHERE is_active = 1");
    $matches = [];
    while ($row = $stmt->fetch()) {
        if (topicMatches($row['topic_filter'], $topic)) {
            $matches[] = $row;
        }
    }
    return $matches;
}

function mqttIsAdmin(array $auth): bool {
    if (!$auth['user_id']) return false;
    $pdo = agentos_pdo();
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$auth['user_id']]);
        $role = $stmt->fetchColumn();
        return in_array($role, ['admin', 'supreme_admin', 'owner']);
    } catch (\Throwable $e) {
        return false;
    }
}
