<?php
/**
 * Alfred IDE — Session / Profile / Stats endpoint
 *
 * GET  /api/alfred-ide-session.php             → { valid, name, email, avatar, client_id }
 * GET  /api/alfred-ide-session.php?action=stats → adds plan, tokens, services, invoices
 *
 * Auth: Bearer token issued by /api/alfred-ide-token.php (alfred_oauth_tokens).
 *       Returns JSON 401 on missing/invalid token (NEVER a 302 to /login —
 *       the IDE cannot follow website-session redirects).
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true;
$GLOBALS['RATE_LIMIT_EXEMPT'] = true;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Alfred-IDE-Token, X-Alfred-Source');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/db-config.inc.php';
require_once dirname(__DIR__) . '/includes/path-guard.inc.php';
require_once dirname(__DIR__) . '/includes/alfred-ide-bearer.inc.php';

function jout(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, gositeme_json_public_encode_flags() | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = getSharedDB();
} catch (Throwable $e) {
    jout(['valid' => false, 'error' => 'db_unavailable'], 503);
}

// -- Authenticate via Bearer token (oauth) OR alfred_ide_users.session_token OR PHP session --
$user = null;
$token = alfred_resolve_ide_bearer_token();

if ($token !== '') {
    $tokenHash = hash('sha256', $token);

    // Try alfred_oauth_tokens first
    $stmt = $pdo->prepare(
        "SELECT t.user_id,
                c.id AS client_id, c.email, c.firstname, c.lastname
         FROM alfred_oauth_tokens t
         JOIN clients c ON c.id = t.user_id
         WHERE (t.access_token = ? OR t.access_token = ?)
           AND (t.expires_at IS NULL OR t.expires_at > NOW())
         LIMIT 1"
    );
    $stmt->execute([$tokenHash, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback: alfred_ide_users.session_token (written by alfred-ide-auth.php ideIssueSession)
    if (!$user) {
        $stmt = $pdo->prepare(
            "SELECT u.client_id AS user_id,
                    c.id AS client_id, c.email, c.firstname, c.lastname
             FROM alfred_ide_users u
             JOIN clients c ON c.id = u.client_id
             WHERE u.session_token = ?
               AND (u.token_expires IS NULL OR u.token_expires > NOW())
             LIMIT 1"
        );
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Final fallback: PHP browser session (for the dashboard webview / cookie-auth users)
if (!$user) {
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    $sessClientId = (int)($_SESSION['client_id'] ?? $_SESSION['ide_user_id'] ?? 0);
    if ($sessClientId > 0) {
        $stmt = $pdo->prepare(
            "SELECT id AS user_id, id AS client_id, email, firstname, lastname
             FROM clients WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$sessClientId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$user) {
    if ($token === '') {
        jout(['valid' => false, 'error' => 'unauthorized', 'message' => 'Bearer token required'], 401);
    }
    jout(['valid' => false, 'error' => 'invalid_token'], 401);
}

$clientId = (int)$user['client_id'];
$displayName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
if ($displayName === '') {
    $displayName = $user['email'] ?: 'User';
}

// Base profile response (used by both /session and /session?action=stats)
$base = [
    'valid'     => true,
    'name'      => $displayName,
    'email'     => (string)($user['email'] ?? ''),
    'avatar'    => '',
    'client_id' => $clientId,
];

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

if ($action !== 'stats') {
    jout($base);
}

// ── Stats payload ───────────────────────────────────────────────────────────
$plan = 'free';
$tokensIncluded = 50000;
$tokensUsed = 0;
$tokensOverage = 0;
$costOverage = 0.0;
$unlimited = false;

// Plan + monthly balance
try {
    $bal = $pdo->prepare(
        "SELECT plan, tokens_used, tokens_included, tokens_overage, cost_overage_usd
         FROM alfred_ide_balance
         WHERE user_id = ? AND billing_period = DATE_FORMAT(NOW(), '%Y-%m')
         LIMIT 1"
    );
    $bal->execute([$clientId]);
    if ($r = $bal->fetch(PDO::FETCH_ASSOC)) {
        $plan = (string)($r['plan'] ?? 'free');
        $tokensUsed = (int)($r['tokens_used'] ?? 0);
        $tokensIncluded = (int)($r['tokens_included'] ?? 50000);
        $tokensOverage = (int)($r['tokens_overage'] ?? 0);
        $costOverage = (float)($r['cost_overage_usd'] ?? 0);
    }
} catch (Throwable $e) {
    error_log('alfred-ide-session: balance lookup failed: ' . $e->getMessage());
}

// Owner / Commander unlimited
if ($clientId === 33) {
    $plan = 'commander';
    $unlimited = true;
}

// Active services (from WHMCS-style tables — soft-fail if schema differs)
$services = [];
try {
    $svc = $pdo->prepare(
        "SELECT s.id,
                COALESCE(p.name, s.domain, 'Service') AS product,
                s.domain,
                s.domainstatus AS status,
                s.amount,
                s.billingcycle AS billing_cycle
         FROM tblhosting s
         LEFT JOIN tblproducts p ON p.id = s.packageid
         WHERE s.userid = ? AND s.domainstatus = 'Active'
         ORDER BY s.id DESC
         LIMIT 10"
    );
    $svc->execute([$clientId]);
    $services = $svc->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { /* schema-tolerant */ }

// Recent invoices
$invoices = [];
try {
    $inv = $pdo->prepare(
        "SELECT id,
                invoicenum AS invoice_number,
                total,
                status,
                duedate AS due_date,
                date AS created_at
         FROM tblinvoices
         WHERE userid = ?
         ORDER BY id DESC
         LIMIT 5"
    );
    $inv->execute([$clientId]);
    $invoices = $inv->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { /* schema-tolerant */ }

jout($base + [
    'plan'             => $plan,
    'tokens_used'      => $tokensUsed,
    'tokens_included'  => $tokensIncluded,
    'tokens_overage'   => $tokensOverage,
    'cost_overage_usd' => $costOverage,
    'unlimited'        => $unlimited,
    'services'         => $services,
    'recent_invoices'  => $invoices,
]);
