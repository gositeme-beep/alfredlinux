<?php
/**
 * Alfred Webhook Management API
 * 
 * Endpoints (GET ?action=...):
 *   list        — List user's webhooks
 *   deliveries  — List recent deliveries for a webhook (?webhook_id=)
 * 
 * Endpoints (POST ?action=...):
 *   create      — Create a new webhook
 *   update      — Update webhook (url, events, is_active, name)
 *   delete      — Delete a webhook
 *   test        — Send a test event to a webhook
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // External webhook receivers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/webhook-dispatch.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . SITE_URL);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// -------------------------------------------------------------------
// Auth check — all endpoints require a logged-in user
// -------------------------------------------------------------------
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

$userId = (int) $_SESSION['client_id'];
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------------
// Route
// -------------------------------------------------------------------
switch ($action) {
    // ---- READ ----
    case 'list':
        handleList($userId);
        break;

    case 'deliveries':
        handleDeliveries($userId);
        break;

    // ---- WRITE ----
    case 'create':
        requireMethod('POST');
        handleCreate($userId);
        break;

    case 'update':
        requireMethod('POST'); // accept POST as PUT proxy
        handleUpdate($userId);
        break;

    case 'delete':
        requireMethod('POST'); // accept POST as DELETE proxy
        handleDelete($userId);
        break;

    case 'test':
        requireMethod('POST');
        handleTest($userId);
        break;

    default:
        jsonResponse(['error' => 'Invalid action. Valid: list, deliveries, create, update, delete, test'], 400);
}

// ===================================================================
// Handlers
// ===================================================================

/**
 * GET ?action=list
 * Returns all webhooks for the current user.
 */
function handleList(int $userId): void {
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $stmt = $db->prepare(
        "SELECT id, name, url, events, is_active, failure_count, last_triggered, created_at
         FROM alfred_webhooks
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$userId]);
    $webhooks = $stmt->fetchAll();

    // Decode events JSON for each row
    foreach ($webhooks as &$wh) {
        $wh['events']       = json_decode($wh['events'], true) ?: [];
        $wh['is_active']    = (bool) $wh['is_active'];
        $wh['failure_count'] = (int) $wh['failure_count'];
    }
    unset($wh);

    jsonResponse(['webhooks' => $webhooks]);
}

/**
 * GET ?action=deliveries&webhook_id=123&limit=50&offset=0
 * Returns recent delivery attempts for a specific webhook.
 */
function handleDeliveries(int $userId): void {
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $webhookId = (int) ($_GET['webhook_id'] ?? 0);
    if ($webhookId <= 0) {
        jsonResponse(['error' => 'webhook_id is required'], 400);
    }

    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM alfred_webhooks WHERE id = ? AND user_id = ?");
    $stmt->execute([$webhookId, $userId]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'Webhook not found'], 404);
    }

    $limit  = min(max((int) ($_GET['limit'] ?? 25), 1), 100);
    $offset = max((int) ($_GET['offset'] ?? 0), 0);

    $stmt = $db->prepare(
        "SELECT id, event, response_code, duration_ms, status, error_message, created_at
         FROM alfred_webhook_deliveries
         WHERE webhook_id = ?
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );
    dbExecute($stmt, [$webhookId, $limit, $offset]);
    $deliveries = $stmt->fetchAll();

    foreach ($deliveries as &$d) {
        $d['response_code'] = (int) $d['response_code'];
        $d['duration_ms']   = (int) $d['duration_ms'];
    }
    unset($d);

    // Total count for pagination
    $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_webhook_deliveries WHERE webhook_id = ?");
    $countStmt->execute([$webhookId]);
    $total = (int) $countStmt->fetchColumn();

    jsonResponse([
        'deliveries' => $deliveries,
        'total'      => $total,
        'limit'      => $limit,
        'offset'     => $offset
    ]);
}

/**
 * POST ?action=create
 * Body JSON: { url, events: [...], name? }
 */
function handleCreate(int $userId): void {
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }

    $url    = trim($input['url'] ?? '');
    $events = $input['events'] ?? [];
    $name   = trim($input['name'] ?? '');

    // Validate URL
    if (empty($url)) {
        jsonResponse(['error' => 'url is required'], 400);
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        jsonResponse(['error' => 'Invalid URL format'], 400);
    }
    if (!str_starts_with($url, 'https://')) {
        jsonResponse(['error' => 'Webhook URL must use HTTPS'], 400);
    }

    // Validate events
    if (!is_array($events) || empty($events)) {
        jsonResponse(['error' => 'events must be a non-empty array of event types'], 400);
    }

    $validEvents = getValidEventTypes();
    foreach ($events as $evt) {
        if ($evt === '*') continue;
        if (!in_array($evt, $validEvents, true)) {
            // Allow category wildcards
            if (str_ends_with($evt, '.*')) {
                $cat = substr($evt, 0, -2);
                $catValid = false;
                foreach ($validEvents as $ve) {
                    if (str_starts_with($ve, $cat . '.')) {
                        $catValid = true;
                        break;
                    }
                }
                if (!$catValid) {
                    jsonResponse(['error' => "Invalid event category: $evt"], 400);
                }
            } else {
                jsonResponse(['error' => "Invalid event type: $evt"], 400);
            }
        }
    }

    // Limit webhooks per user
    $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_webhooks WHERE user_id = ?");
    $countStmt->execute([$userId]);
    if ((int) $countStmt->fetchColumn() >= 25) {
        jsonResponse(['error' => 'Maximum of 25 webhooks per account'], 400);
    }

    // Generate secret
    $secret = 'whsec_' . bin2hex(random_bytes(32));

    // Sanitize name
    if ($name) {
        $name = sanitize($name, 100);
    } else {
        $name = null;
    }

    $stmt = $db->prepare(
        "INSERT INTO alfred_webhooks (user_id, name, url, events, secret, is_active, failure_count, created_at)
         VALUES (?, ?, ?, ?, ?, 1, 0, NOW())"
    );
    $stmt->execute([
        $userId,
        $name,
        $url,
        json_encode(array_values($events)),
        $secret
    ]);

    $webhookId = (int) $db->lastInsertId();

    jsonResponse([
        'webhook' => [
            'id'        => $webhookId,
            'name'      => $name,
            'url'       => $url,
            'events'    => array_values($events),
            'is_active' => true,
            'secret'    => $secret  // Only shown once on creation
        ],
        'message' => 'Webhook created. Save the secret — it will not be shown again.'
    ], 201);
}

/**
 * POST ?action=update
 * Body JSON: { id, url?, events?, is_active?, name? }
 */
function handleUpdate(int $userId): void {
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }

    $webhookId = (int) ($input['id'] ?? 0);
    if ($webhookId <= 0) {
        jsonResponse(['error' => 'Webhook id is required'], 400);
    }

    // Verify ownership
    $stmt = $db->prepare("SELECT * FROM alfred_webhooks WHERE id = ? AND user_id = ?");
    $stmt->execute([$webhookId, $userId]);
    $webhook = $stmt->fetch();
    if (!$webhook) {
        jsonResponse(['error' => 'Webhook not found'], 404);
    }

    $updates = [];
    $params  = [];

    // URL
    if (isset($input['url'])) {
        $url = trim($input['url']);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            jsonResponse(['error' => 'Webhook URL must be a valid HTTPS URL'], 400);
        }
        $updates[] = 'url = ?';
        $params[]  = $url;
    }

    // Events
    if (isset($input['events'])) {
        if (!is_array($input['events']) || empty($input['events'])) {
            jsonResponse(['error' => 'events must be a non-empty array'], 400);
        }
        $validEvents = getValidEventTypes();
        foreach ($input['events'] as $evt) {
            if ($evt === '*') continue;
            if (!in_array($evt, $validEvents, true) && !str_ends_with($evt, '.*')) {
                jsonResponse(['error' => "Invalid event type: $evt"], 400);
            }
        }
        $updates[] = 'events = ?';
        $params[]  = json_encode(array_values($input['events']));
    }

    // Active status
    if (isset($input['is_active'])) {
        $updates[] = 'is_active = ?';
        $params[]  = $input['is_active'] ? 1 : 0;
        // Reset failure count when re-enabling
        if ($input['is_active']) {
            $updates[] = 'failure_count = 0';
        }
    }

    // Name
    if (isset($input['name'])) {
        $updates[] = 'name = ?';
        $params[]  = $input['name'] ? sanitize($input['name'], 100) : null;
    }

    if (empty($updates)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $params[] = $webhookId;
    $params[] = $userId;

    $sql = "UPDATE alfred_webhooks SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
    $db->prepare($sql)->execute($params);

    jsonResponse(['message' => 'Webhook updated']);
}

/**
 * POST ?action=delete
 * Body JSON: { id }
 */
function handleDelete(int $userId): void {
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $input = json_decode(file_get_contents('php://input'), true);
    $webhookId = (int) ($input['id'] ?? $_GET['id'] ?? 0);
    if ($webhookId <= 0) {
        jsonResponse(['error' => 'Webhook id is required'], 400);
    }

    // Verify ownership & delete
    $stmt = $db->prepare("DELETE FROM alfred_webhooks WHERE id = ? AND user_id = ?");
    $stmt->execute([$webhookId, $userId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Webhook not found'], 404);
    }

    // Clean up deliveries
    $db->prepare("DELETE FROM alfred_webhook_deliveries WHERE webhook_id = ?")->execute([$webhookId]);

    jsonResponse(['message' => 'Webhook deleted']);
}

/**
 * POST ?action=test
 * Body JSON: { id }
 */
function handleTest(int $userId): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $webhookId = (int) ($input['id'] ?? 0);
    if ($webhookId <= 0) {
        jsonResponse(['error' => 'Webhook id is required'], 400);
    }

    $result = sendTestWebhook($webhookId, $userId);
    $code   = $result['success'] ? 200 : 502;
    jsonResponse($result, $code);
}

// ===================================================================
// Helpers
// ===================================================================

function requireMethod(string $expected): void {
    if ($_SERVER['REQUEST_METHOD'] !== $expected) {
        jsonResponse(['error' => "Method $expected required"], 405);
    }
}

/**
 * All supported webhook event types
 */
function getValidEventTypes(): array {
    return [
        // Agent events
        'agent.created',
        'agent.deployed',
        'agent.error',
        'agent.status_changed',
        // Call events
        'call.started',
        'call.ended',
        'call.transferred',
        'call.recorded',
        // Fleet events
        'fleet.deployed',
        'fleet.alert',
        'fleet.agent_joined',
        'fleet.agent_left',
        // Tool events
        'tool.executed',
        'tool.error',
        'tool.rate_limited',
        // Marketplace events
        'marketplace.published',
        'marketplace.purchased',
        'marketplace.review',
        // Billing events
        'billing.payment_succeeded',
        'billing.payment_failed',
        'billing.usage_alert',
        // System events
        'webhook.test',
    ];
}

/**
 * Get event types grouped by category (for UI rendering)
 */
function getEventCategories(): array {
    return [
        'Agent' => [
            'agent.created'        => 'Agent Created',
            'agent.deployed'       => 'Agent Deployed',
            'agent.error'          => 'Agent Error',
            'agent.status_changed' => 'Agent Status Changed',
        ],
        'Call' => [
            'call.started'     => 'Call Started',
            'call.ended'       => 'Call Ended',
            'call.transferred' => 'Call Transferred',
            'call.recorded'    => 'Call Recorded',
        ],
        'Fleet' => [
            'fleet.deployed'     => 'Fleet Deployed',
            'fleet.alert'        => 'Fleet Alert',
            'fleet.agent_joined' => 'Agent Joined Fleet',
            'fleet.agent_left'   => 'Agent Left Fleet',
        ],
        'Tool' => [
            'tool.executed'     => 'Tool Executed',
            'tool.error'        => 'Tool Error',
            'tool.rate_limited' => 'Tool Rate Limited',
        ],
        'Marketplace' => [
            'marketplace.published' => 'Extension Published',
            'marketplace.purchased' => 'Extension Purchased',
            'marketplace.review'    => 'Extension Review',
        ],
        'Billing' => [
            'billing.payment_succeeded' => 'Payment Succeeded',
            'billing.payment_failed'    => 'Payment Failed',
            'billing.usage_alert'       => 'Usage Alert',
        ],
    ];
}
