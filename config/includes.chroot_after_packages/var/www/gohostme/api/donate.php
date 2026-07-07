<?php
/**
 * Donation API — Creates Stripe Checkout sessions for one-time donations
 * Links donations to citizen passports for reputation/XP rewards
 * 
 * POST /api/donate.php
 *   amount      — amount in dollars (min $1, max $10,000)
 *   project     — general|alfred-linux|metadome|alfred-ide|veil|pulse
 *   name        — donor display name (optional)
 *   email       — donor email (optional but needed for receipt)
 *   message     — donor message (optional, max 500 chars)
 *   anonymous   — 1 to hide name from public wall (optional)
 *   source      — referring domain for redirect (optional)
 * 
 * GET /api/donate.php?action=wall
 *   Returns recent completed donations for the donor wall
 * 
 * GET /api/donate.php?action=stats
 *   Returns total raised, donor count, project breakdown
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true;
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// DB connection
$conf = file_get_contents(dirname(__DIR__) . '/whmcs/configuration.php');
eval('?>' . $conf);
$pdo = new PDO("mysql:host=localhost;dbname=gositeme_whmcs;unix_socket=/run/mysql/mysql.sock", $db_username, $db_password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? ($_POST['action'] ?? 'checkout');

// ── PUBLIC: Donor wall ──
if ($action === 'wall') {
    $project = isset($_GET['project']) ? preg_replace('/[^a-z0-9-]/', '', $_GET['project']) : null;
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    
    $sql = "SELECT donor_name, amount_cents, donor_message, project, created_at 
            FROM donations WHERE status='completed' AND is_anonymous=0 AND donor_name IS NOT NULL";
    $params = [];
    if ($project) { $sql .= " AND project = ?"; $params[] = $project; }
    $sql .= " ORDER BY created_at DESC LIMIT " . $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($donors as &$d) {
        $d['amount'] = number_format($d['amount_cents'] / 100, 2);
        unset($d['amount_cents']);
    }
    
    echo json_encode(['ok' => true, 'donors' => $donors]);
    exit;
}

// ── PUBLIC: Stats ──
if ($action === 'stats') {
    $stats = $pdo->query("SELECT 
        COALESCE(SUM(amount_cents), 0) as total_cents,
        COUNT(*) as total_donations,
        COUNT(DISTINCT COALESCE(client_id, donor_email)) as unique_donors
        FROM donations WHERE status='completed'")->fetch(PDO::FETCH_ASSOC);
    
    $byProject = $pdo->query("SELECT project, 
        COALESCE(SUM(amount_cents), 0) as total_cents, COUNT(*) as count
        FROM donations WHERE status='completed' GROUP BY project ORDER BY total_cents DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'total_raised' => number_format($stats['total_cents'] / 100, 2),
        'total_donations' => (int)$stats['total_donations'],
        'unique_donors' => (int)$stats['unique_donors'],
        'by_project' => $byProject
    ]);
    exit;
}

// ── WEBHOOK: Stripe completion ──
if ($action === 'webhook') {
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    if (!$sigHeader || !defined('STRIPE_WEBHOOK_SECRET') || STRIPE_WEBHOOK_SECRET === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Webhook not configured']);
        exit;
    }
    
    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;
        $meta = $session->metadata ?? (object)[];
        
        if (($meta->type ?? '') === 'donation') {
            $stripeSessionId = $session->id;
            $paymentIntent = $session->payment_intent ?? null;
            
            // Update donation record
            $stmt = $pdo->prepare("UPDATE donations SET 
                status = 'completed', 
                stripe_payment_intent = ?,
                completed_at = NOW()
                WHERE stripe_session_id = ? AND status = 'pending'");
            $stmt->execute([$paymentIntent, $stripeSessionId]);
            
            // Award reputation + XP to linked citizen
            if ($stmt->rowCount() > 0) {
                $donation = $pdo->prepare("SELECT id, client_id, amount_cents FROM donations WHERE stripe_session_id = ?");
                $donation->execute([$stripeSessionId]);
                $don = $donation->fetch(PDO::FETCH_ASSOC);
                
                if ($don && $don['client_id']) {
                    // Reputation: +1 per $5 donated (max +20 per donation)
                    $repBonus = min(20, floor($don['amount_cents'] / 500));
                    // XP: 10 per $1 donated (max 1000)
                    $xpBonus = min(1000, floor($don['amount_cents'] / 10));
                    
                    if ($repBonus > 0) {
                        $pdo->prepare("UPDATE human_passports SET reputation_score = LEAST(999.99, reputation_score + ?) WHERE client_id = ?")
                            ->execute([$repBonus, $don['client_id']]);
                    }
                    if ($xpBonus > 0) {
                        $pdo->prepare("UPDATE user_ranks SET xp = xp + ? WHERE client_id = ?")
                            ->execute([$xpBonus, $don['client_id']]);
                    }
                    
                    // Record awards
                    $pdo->prepare("UPDATE donations SET reputation_awarded = ?, xp_awarded = ? WHERE id = ?")
                        ->execute([$repBonus, $xpBonus, $don['id']]);
                    
                    // GSM transaction log
                    $pdo->prepare("INSERT IGNORE INTO gsm_transactions (client_id, tx_type, amount, source, description, created_at) VALUES (?, 'earn', ?, 'donation_reward', ?, NOW())")
                        ->execute([$don['client_id'], $xpBonus, "Donation reward: \${$don['amount_cents']}/100"]);
                }
            }
        }
    }
    
    echo json_encode(['ok' => true]);
    exit;
}

// ── POST: Create checkout session ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Payment system not configured. Please try again later.']);
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Parse input
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$amount = (float)($input['amount'] ?? 0);
$project = preg_replace('/[^a-z0-9-]/', '', $input['project'] ?? 'general');
$name = mb_substr(trim($input['name'] ?? ''), 0, 120);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
$message = mb_substr(trim($input['message'] ?? ''), 0, 500);
$anonymous = (int)($input['anonymous'] ?? 0);
$source = preg_replace('/[^a-z0-9.-]/', '', $input['source'] ?? 'gositeme.com');

// Validate
$validProjects = ['general', 'alfred-linux', 'metadome', 'alfred-ide', 'veil', 'pulse', 'voice', 'browser', 'search'];
if (!in_array($project, $validProjects)) { $project = 'general'; }
if ($amount < 1 || $amount > 10000) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount must be between $1 and $10,000']);
    exit;
}
$amountCents = (int)round($amount * 100);

// Find linked citizen
$clientId = null;
$passportNumber = null;
if ($email) {
    $stmt = $pdo->prepare("SELECT c.id as client_id, hp.passport_number 
        FROM tblclients c LEFT JOIN human_passports hp ON hp.client_id = c.id 
        WHERE c.email = ? LIMIT 1");
    $stmt->execute([$email]);
    $citizen = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($citizen) {
        $clientId = (int)$citizen['client_id'];
        $passportNumber = $citizen['passport_number'];
    }
}

// Also check current session
if (!$clientId && session_status() === PHP_SESSION_NONE) {
    session_start();
    if (!empty($_SESSION['client_id'])) {
        $clientId = (int)$_SESSION['client_id'];
        $stmt = $pdo->prepare("SELECT passport_number FROM human_passports WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $passportNumber = $stmt->fetchColumn() ?: null;
    }
}

// Project display names
$projectNames = [
    'general' => 'GoSiteMe Ecosystem',
    'alfred-linux' => 'Alfred Linux',
    'metadome' => 'MetaDome VR',
    'alfred-ide' => 'Alfred IDE',
    'veil' => 'Veil Encryption',
    'pulse' => 'Pulse Social',
    'voice' => 'Alfred Voice',
    'browser' => 'Alfred Browser',
    'search' => 'Alfred Search',
];
$projectLabel = $projectNames[$project] ?? 'GoSiteMe';

// Determine success redirect
$successDomains = [
    'alfredlinux.com' => 'https://alfredlinux.com/donate-thanks.php',
    'meta-dome.com' => 'https://meta-dome.com/donate-thanks.php',
];
$successUrl = $successDomains[$source] ?? (SITE_URL . '/donate.php?thanks=1&session_id={CHECKOUT_SESSION_ID}');
$cancelUrl = $successDomains[$source] 
    ? str_replace('donate-thanks', 'donate', $successDomains[$source] ?? '') 
    : (SITE_URL . '/donate.php?cancelled=1');

// Insert pending donation
$stmt = $pdo->prepare("INSERT INTO donations (client_id, passport_number, amount_cents, currency, donor_name, donor_email, donor_message, project, is_anonymous, status) VALUES (?, ?, ?, 'USD', ?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$clientId, $passportNumber, $amountCents, $name ?: null, $email, $message ?: null, $project, $anonymous]);
$donationId = $pdo->lastInsertId();

// Create Stripe Checkout Session 
try {
    $sessionParams = [
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => "Donation to {$projectLabel}",
                    'description' => $message ? "Message: " . mb_substr($message, 0, 200) : "Supporting {$projectLabel}",
                ],
                'unit_amount' => $amountCents,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'metadata' => [
            'type' => 'donation',
            'donation_id' => $donationId,
            'project' => $project,
            'client_id' => $clientId ?? 'anonymous',
            'passport' => $passportNumber ?? 'none',
        ],
    ];

    if ($email) {
        $sessionParams['customer_email'] = $email;
    }

    $session = \Stripe\Checkout\Session::create($sessionParams);

    // Store session ID
    $pdo->prepare("UPDATE donations SET stripe_session_id = ? WHERE id = ?")
        ->execute([$session->id, $donationId]);

    echo json_encode([
        'ok' => true,
        'checkout_url' => $session->url,
        'session_id' => $session->id,
        'donation_id' => $donationId,
        'citizen_linked' => $clientId ? true : false,
        'passport' => $passportNumber,
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Mark donation as failed
    $pdo->prepare("UPDATE donations SET status = 'failed' WHERE id = ?")->execute([$donationId]);
    http_response_code(500);
    echo json_encode(['error' => 'Payment service error. Please try again.']);
}
