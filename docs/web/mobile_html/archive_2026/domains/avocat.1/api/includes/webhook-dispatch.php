<?php
/**
 * Alfred Webhook Dispatch Engine
 * 
 * Include from any API file:
 *   require_once __DIR__ . '/includes/webhook-dispatch.php';
 * 
 * Usage:
 *   dispatchWebhook('agent.created', ['agent_id' => 123, 'name' => 'My Agent'], $userId);
 *   dispatchWebhook('call.ended', $callData, $userId, $orgId);
 * 
 * Existing DB schema:
 *   alfred_webhooks: id, user_id, org_id, name, url, events(JSON), secret, is_active, failure_count, last_triggered, created_at
 *   alfred_webhook_deliveries: id, webhook_id, event, payload, response_code, response_body, duration_ms, status(enum), attempts, next_retry_at, error_message, created_at
 */

if (!function_exists('getDB')) {
    // If included standalone (unlikely), load config
    define('GOSITEME_API', true);
    require_once __DIR__ . '/../config.php';
}

/**
 * Dispatch webhook to all subscribers for an event
 * 
 * @param string   $eventType  e.g., 'agent.created', 'call.ended'
 * @param array    $data       Event payload
 * @param int|null $userId     Filter to specific user's webhooks (null = all)
 * @param int|null $orgId      Filter to specific org's webhooks
 * @return int     Number of webhooks dispatched to
 */
function dispatchWebhook(string $eventType, array $data, ?int $userId = null, ?int $orgId = null): int {
    $db = getDB();
    if (!$db) return 0;

    try {
        // Find all active webhooks with fewer than 10 consecutive failures
        $sql = "SELECT * FROM alfred_webhooks WHERE is_active = 1 AND failure_count < 10";
        $params = [];

        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        if ($orgId) {
            $sql .= " AND org_id = ?";
            $params[] = $orgId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $webhooks = $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Webhook dispatch query failed: " . $e->getMessage());
        return 0;
    }

    $dispatched = 0;

    foreach ($webhooks as $webhook) {
        $events = json_decode($webhook['events'], true) ?: [];

        // Check if this webhook subscribes to this event (supports wildcards)
        if (!webhookMatchesEvent($events, $eventType)) {
            continue;
        }

        // Build payload envelope
        $payload = [
            'id'        => 'evt_' . bin2hex(random_bytes(12)),
            'event'     => $eventType,
            'timestamp' => date('c'),
            'data'      => $data
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

        // Generate HMAC signature
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $webhook['secret']);

        // Deliver (short timeout to avoid blocking the caller)
        $success = deliverWebhook($webhook, $jsonPayload, $signature, $eventType);
        if ($success) {
            $dispatched++;
        }
    }

    return $dispatched;
}

/**
 * Check if a webhook's event patterns match the given event type
 * 
 * Supports:
 *   '*'           → matches everything
 *   'agent.*'     → matches any agent.X event
 *   'call.ended'  → exact match
 * 
 * @param array  $patterns   The webhook's subscribed event patterns
 * @param string $eventType  The event being dispatched
 * @return bool
 */
function webhookMatchesEvent(array $patterns, string $eventType): bool {
    foreach ($patterns as $pattern) {
        if ($pattern === '*' || $pattern === $eventType) {
            return true;
        }
        // Support category wildcards like "agent.*"
        if (str_ends_with($pattern, '.*')) {
            $category = substr($pattern, 0, -2);
            if (str_starts_with($eventType, $category . '.')) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Deliver a webhook payload to a single endpoint
 * 
 * @param array  $webhook    Webhook row from DB
 * @param string $payload    JSON-encoded payload
 * @param string $signature  HMAC signature header value
 * @param string $eventType  Event type string
 * @return bool  Whether delivery succeeded (2xx response)
 */
function deliverWebhook(array $webhook, string $payload, string $signature, string $eventType): bool {
    $db = getDB();
    if (!$db) return false;

    $deliveryId = json_decode($payload, true)['id'] ?? 'evt_unknown';

    $ch = curl_init($webhook['url']);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => $payload,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 10,
        CURLOPT_CONNECTTIMEOUT  => 5,
        CURLOPT_FOLLOWLOCATION  => false,
        CURLOPT_MAXREDIRS       => 0,
        CURLOPT_HTTPHEADER      => [
            'Content-Type: application/json',
            'X-Alfred-Signature: ' . $signature,
            'X-Alfred-Event: ' . $eventType,
            'X-Alfred-Delivery: ' . $deliveryId,
            'User-Agent: Alfred-Webhook/1.0'
        ]
    ]);

    $startTime    = microtime(true);
    $response     = curl_exec($ch);
    $durationMs   = (int) round((microtime(true) - $startTime) * 1000);
    $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    $success = ($httpCode >= 200 && $httpCode < 300);
    $status  = $success ? 'success' : 'failed';

    // Log delivery to alfred_webhook_deliveries
    try {
        $stmt = $db->prepare(
            "INSERT INTO alfred_webhook_deliveries
                (webhook_id, event, payload, response_code, response_body, duration_ms, status, attempts, error_message, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())"
        );
        $stmt->execute([
            $webhook['id'],
            $eventType,
            $payload,
            $httpCode,
            substr($response ?: '', 0, 1000),
            $durationMs,
            $status,
            $curlError ?: null
        ]);
    } catch (\PDOException $e) {
        error_log("Webhook delivery log failed: " . $e->getMessage());
    }

    // Update webhook failure count & last_triggered
    try {
        if (!$success) {
            $db->prepare(
                "UPDATE alfred_webhooks SET failure_count = failure_count + 1, last_triggered = NOW() WHERE id = ?"
            )->execute([$webhook['id']]);

            // Auto-disable after 10 consecutive failures
            if (($webhook['failure_count'] + 1) >= 10) {
                $db->prepare(
                    "UPDATE alfred_webhooks SET is_active = 0 WHERE id = ?"
                )->execute([$webhook['id']]);
            }
        } else {
            // Reset failure count on success
            $db->prepare(
                "UPDATE alfred_webhooks SET failure_count = 0, last_triggered = NOW() WHERE id = ?"
            )->execute([$webhook['id']]);
        }
    } catch (\PDOException $e) {
        error_log("Webhook status update failed: " . $e->getMessage());
    }

    return $success;
}

/**
 * Send a test webhook event to a specific webhook
 * 
 * @param int $webhookId  Webhook ID
 * @param int $userId     Owner user ID (for authorization)
 * @return array          Result with success, status_code, duration_ms
 */
function sendTestWebhook(int $webhookId, int $userId): array {
    $db = getDB();
    if (!$db) return ['success' => false, 'error' => 'Database unavailable'];

    $stmt = $db->prepare("SELECT * FROM alfred_webhooks WHERE id = ? AND user_id = ?");
    $stmt->execute([$webhookId, $userId]);
    $webhook = $stmt->fetch();

    if (!$webhook) {
        return ['success' => false, 'error' => 'Webhook not found'];
    }

    $testPayload = [
        'id'        => 'evt_test_' . bin2hex(random_bytes(8)),
        'event'     => 'webhook.test',
        'timestamp' => date('c'),
        'data'      => [
            'message'    => 'This is a test webhook delivery from Alfred.',
            'webhook_id' => $webhookId,
            'test'       => true
        ]
    ];

    $jsonPayload = json_encode($testPayload, JSON_UNESCAPED_SLASHES);
    $signature   = 'sha256=' . hash_hmac('sha256', $jsonPayload, $webhook['secret']);

    $ch = curl_init($webhook['url']);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => $jsonPayload,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 10,
        CURLOPT_CONNECTTIMEOUT  => 5,
        CURLOPT_HTTPHEADER      => [
            'Content-Type: application/json',
            'X-Alfred-Signature: ' . $signature,
            'X-Alfred-Event: webhook.test',
            'X-Alfred-Delivery: ' . $testPayload['id'],
            'User-Agent: Alfred-Webhook/1.0'
        ]
    ]);

    $startTime  = microtime(true);
    $response   = curl_exec($ch);
    $durationMs = (int) round((microtime(true) - $startTime) * 1000);
    $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    $success = ($httpCode >= 200 && $httpCode < 300);

    // Log test delivery
    try {
        $stmt = $db->prepare(
            "INSERT INTO alfred_webhook_deliveries
                (webhook_id, event, payload, response_code, response_body, duration_ms, status, attempts, error_message, created_at)
             VALUES (?, 'webhook.test', ?, ?, ?, ?, ?, 1, ?, NOW())"
        );
        $stmt->execute([
            $webhookId,
            $jsonPayload,
            $httpCode,
            substr($response ?: '', 0, 1000),
            $durationMs,
            $success ? 'success' : 'failed',
            $curlError ?: null
        ]);
    } catch (\PDOException $e) {
        error_log("Test webhook delivery log failed: " . $e->getMessage());
    }

    return [
        'success'       => $success,
        'status_code'   => $httpCode,
        'duration_ms'   => $durationMs,
        'response_body' => substr($response ?: '', 0, 500),
        'error'         => $curlError ?: null
    ];
}
