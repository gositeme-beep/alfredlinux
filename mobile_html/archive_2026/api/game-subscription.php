<?php
/**
 * Game Subscription API — $5/year access to premium games
 * GSM Alfred OS · Chess Masters & Premium Games
 * 
 * Endpoints:
 *   check-access     — Check if current session/user has active subscription
 *   create-checkout  — Create Stripe Checkout Session for $5/year
 *   webhook          — Handle Stripe webhook for payment confirmation
 *   verify-payment   — Verify a completed checkout session
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // Stripe webhook signature verification
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
require_once dirname(__DIR__) . '/pay/vendor/autoload.php';

session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ─── Constants ─────────────────────────────────────────────────────
const GAME_SUB_PRICE   = 500;      // $5.00 in cents
const GAME_SUB_PERIOD  = 365;      // days
const GAME_SUB_NAME    = 'Chess Masters — Annual Pass';
const GAME_SUB_DESC    = '1-year access to Chess Masters premium games, puzzles, tournaments & betting';

// ─── Ensure table ──────────────────────────────────────────────────
function ensureGameSubTable(): void {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS game_subscriptions (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id      VARCHAR(128) NOT NULL,
        client_id       INT UNSIGNED DEFAULT NULL,
        stripe_session   VARCHAR(255) DEFAULT NULL,
        stripe_payment   VARCHAR(255) DEFAULT NULL,
        amount          INT UNSIGNED NOT NULL DEFAULT 500,
        currency        VARCHAR(3) NOT NULL DEFAULT 'usd',
        status          ENUM('pending','active','expired','refunded') DEFAULT 'pending',
        starts_at       DATETIME NOT NULL,
        expires_at      DATETIME NOT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id),
        INDEX idx_client (client_id),
        INDEX idx_status (status, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

ensureGameSubTable();

// ─── Helpers ───────────────────────────────────────────────────────
$sessionId = session_id();
$clientId  = $_SESSION['client_id'] ?? null;

function hasActiveSubscription(): bool {
    global $sessionId, $clientId;
    $db = getDB();
    if (!$db) return false;

    // Admin/owner bypass — creator always has access
    $ownerEmails = ['gositeme@gmail.com'];
    if (!empty($_SESSION['client_email']) && in_array(strtolower($_SESSION['client_email']), $ownerEmails)) {
        return true;
    }

    // Check by client_id first (persistent across sessions), then session_id
    if ($clientId) {
        $stmt = $db->prepare("SELECT id FROM game_subscriptions WHERE client_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$clientId]);
        if ($stmt->fetch()) return true;
    }

    $stmt = $db->prepare("SELECT id FROM game_subscriptions WHERE session_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$sessionId]);
    return (bool) $stmt->fetch();
}

function getSubscriptionInfo(): ?array {
    global $sessionId, $clientId;
    $db = getDB();
    if (!$db) return null;

    $where = "session_id = ?";
    $params = [$sessionId];
    if ($clientId) {
        $where = "(client_id = ? OR session_id = ?)";
        $params = [$clientId, $sessionId];
    }

    $stmt = $db->prepare("SELECT * FROM game_subscriptions WHERE $where AND status = 'active' AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

// ─── Routing ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Check Access ───────────────────────────────────────────────
    case 'check-access':
        $active = hasActiveSubscription();
        $info = $active ? getSubscriptionInfo() : null;
        jsonResponse([
            'success' => true,
            'hasAccess' => $active,
            'subscription' => $info ? [
                'expires_at' => $info['expires_at'],
                'days_left' => max(0, (int) ((strtotime($info['expires_at']) - time()) / 86400)),
            ] : null,
            'price' => '$5.00/year',
        ]);
        break;

    // ── Create Checkout Session ────────────────────────────────────
    case 'create-checkout':
        apiRateLimit('game-sub-checkout', 5, 300); // 5 per 5 min

        if (hasActiveSubscription()) {
            jsonResponse(['success' => false, 'error' => 'You already have an active subscription'], 400);
            break;
        }

        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $checkoutSession = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => GAME_SUB_PRICE,
                        'product_data' => [
                            'name' => GAME_SUB_NAME,
                            'description' => GAME_SUB_DESC,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => SITE_URL . '/vr/chess-masters/?payment=success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => SITE_URL . '/vr/chess-masters/?payment=cancelled',
                'metadata' => [
                    'type' => 'game_subscription',
                    'session_id' => $sessionId,
                    'client_id' => $clientId ?? '',
                ],
            ]);

            // Store pending subscription
            $db = getDB();
            $now = date('Y-m-d H:i:s');
            $expires = date('Y-m-d H:i:s', strtotime("+" . GAME_SUB_PERIOD . " days"));
            $stmt = $db->prepare("INSERT INTO game_subscriptions (session_id, client_id, stripe_session, amount, currency, status, starts_at, expires_at) VALUES (?, ?, ?, ?, 'usd', 'pending', ?, ?)");
            $stmt->execute([$sessionId, $clientId, $checkoutSession->id, GAME_SUB_PRICE, $now, $expires]);

            jsonResponse([
                'success' => true,
                'checkoutUrl' => $checkoutSession->url,
                'sessionId' => $checkoutSession->id,
            ]);
        } catch (Exception $e) {
            error_log('Game subscription checkout error: ' . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Payment setup failed. Please try again.'], 500);
        }
        break;

    // ── Verify Payment (after redirect) ────────────────────────────
    case 'verify-payment':
        $checkoutId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';
        if (!$checkoutId || !preg_match('/^cs_(test_|live_)[a-zA-Z0-9]+$/', $checkoutId)) {
            jsonResponse(['success' => false, 'error' => 'Invalid session'], 400);
            break;
        }

        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $session = \Stripe\Checkout\Session::retrieve($checkoutId);

            if ($session->payment_status !== 'paid') {
                jsonResponse(['success' => false, 'error' => 'Payment not completed'], 400);
                break;
            }

            // Activate subscription
            $db = getDB();
            $now = date('Y-m-d H:i:s');
            $expires = date('Y-m-d H:i:s', strtotime("+365 days"));

            // Update existing pending record
            $stmt = $db->prepare("UPDATE game_subscriptions SET status = 'active', stripe_payment = ?, starts_at = ?, expires_at = ? WHERE stripe_session = ? AND status = 'pending'");
            $stmt->execute([$session->payment_intent, $now, $expires, $checkoutId]);

            if ($stmt->rowCount() === 0) {
                // No pending record — create one (webhook might have created it)
                $metaSession = $session->metadata->session_id ?? $sessionId;
                $metaClient = $session->metadata->client_id ?? $clientId;
                $stmt = $db->prepare("INSERT INTO game_subscriptions (session_id, client_id, stripe_session, stripe_payment, amount, currency, status, starts_at, expires_at) VALUES (?, ?, ?, ?, ?, 'usd', 'active', ?, ?) ON DUPLICATE KEY UPDATE status = 'active', expires_at = VALUES(expires_at)");
                $stmt->execute([$metaSession, $metaClient ?: null, $checkoutId, $session->payment_intent, GAME_SUB_PRICE, $now, $expires]);
            }

            jsonResponse([
                'success' => true,
                'hasAccess' => true,
                'expires_at' => $expires,
            ]);
        } catch (Exception $e) {
            error_log('Game subscription verify error: ' . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Verification failed'], 500);
        }
        break;

    // ── Webhook (Stripe) ───────────────────────────────────────────
    case 'webhook':
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
        } catch (Exception $e) {
            http_response_code(400);
            exit;
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            if (($session->metadata->type ?? '') === 'game_subscription' && $session->payment_status === 'paid') {
                $db = getDB();
                $now = date('Y-m-d H:i:s');
                $expires = date('Y-m-d H:i:s', strtotime("+365 days"));
                $stmt = $db->prepare("UPDATE game_subscriptions SET status = 'active', stripe_payment = ?, starts_at = ?, expires_at = ? WHERE stripe_session = ? AND status = 'pending'");
                $stmt->execute([$session->payment_intent, $now, $expires, $session->id]);
            }
        }

        jsonResponse(['received' => true]);
        break;

    // ── Get Config (Stripe publishable key) ────────────────────────
    case 'get-config':
        jsonResponse([
            'success' => true,
            'stripeKey' => STRIPE_PUBLISHABLE_KEY,
            'price' => GAME_SUB_PRICE,
            'period' => GAME_SUB_PERIOD,
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
