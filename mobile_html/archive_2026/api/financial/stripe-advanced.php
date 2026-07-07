<?php
/**
 * Stripe Advanced Financial API
 * Handles: Stripe Tax, Connect, Issuing, Billing Meters, Treasury
 * ATLAS Agents: Treasurer (#38), Invoicer (#39), Underwriter (#43), Auditor-F (#46)
 */
define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/config.php';
require_once dirname(dirname(__DIR__)) . '/pay/vendor/autoload.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// Use existing Stripe keys from stripe.php
\Stripe\Stripe::setApiKey(defined('STRIPE_RESTRICTED_KEY') ? STRIPE_RESTRICTED_KEY : (getenv('STRIPE_SECRET_KEY') ?: ''));

// ─── Schema ───────────────────────────────────────────────────
function ensureStripeAdvancedSchema(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS fin_stripe_connect_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        stripe_account_id VARCHAR(50) NOT NULL,
        account_type ENUM('express','standard','custom') DEFAULT 'express',
        business_type VARCHAR(30),
        charges_enabled TINYINT(1) DEFAULT 0,
        payouts_enabled TINYINT(1) DEFAULT 0,
        onboarding_complete TINYINT(1) DEFAULT 0,
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client (client_id),
        UNIQUE KEY uk_stripe (stripe_account_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_stripe_meters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meter_id VARCHAR(50) NOT NULL,
        meter_name VARCHAR(100) NOT NULL,
        event_name VARCHAR(100) NOT NULL,
        display_name VARCHAR(100),
        unit VARCHAR(30) DEFAULT 'unit',
        aggregation_type ENUM('sum','last','max') DEFAULT 'sum',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_meter (meter_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_stripe_usage_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        meter_name VARCHAR(100) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        stripe_customer_id VARCHAR(50),
        timestamp INT NOT NULL,
        idempotency_key VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_meter (meter_name),
        INDEX idx_time (timestamp)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_stripe_issued_cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        card_id VARCHAR(50) NOT NULL,
        cardholder_id VARCHAR(50) NOT NULL,
        card_type ENUM('virtual','physical') DEFAULT 'virtual',
        brand VARCHAR(20) DEFAULT 'Visa',
        last4 VARCHAR(4),
        status ENUM('active','inactive','canceled') DEFAULT 'active',
        spending_limit_cents BIGINT DEFAULT 50000,
        spending_interval ENUM('per_authorization','daily','weekly','monthly','yearly','all_time') DEFAULT 'monthly',
        label VARCHAR(100),
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_card (card_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_tax_calculations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        stripe_tax_calc_id VARCHAR(50),
        amount_cents BIGINT NOT NULL,
        tax_cents BIGINT NOT NULL,
        currency VARCHAR(10) DEFAULT 'usd',
        customer_country VARCHAR(5),
        customer_state VARCHAR(10),
        tax_type VARCHAR(30),
        line_items JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    )");
}
ensureStripeAdvancedSchema();

// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Stripe Tax
    case 'tax_calculate':       finRequireAuth(); taxCalculate(); break;
    case 'tax_rates':           finRequireAuth(); taxGetRates(); break;
    case 'tax_settings':        finRequireAdminOrInternal(); taxSettings(); break;

    // Stripe Connect
    case 'connect_onboard':     finRequireAuth(); connectOnboard(); break;
    case 'connect_status':      finRequireAuth(); connectStatus(); break;
    case 'connect_dashboard':   finRequireAuth(); connectDashboard(); break;
    case 'connect_payout':      finRequireAdminOrInternal(); connectPayout(); break;
    case 'connect_list':        finRequireAdminOrInternal(); connectList(); break;

    // Stripe Billing Meters
    case 'meter_create':        finRequireAdminOrInternal(); meterCreate(); break;
    case 'meter_list':          finRequireAdminOrInternal(); meterList(); break;
    case 'meter_report':        finRequireAuth(); meterReport(); break;
    case 'meter_usage':         finRequireAuth(); meterGetUsage(); break;

    // Stripe Issuing
    case 'card_create':         finRequireAdminOrInternal(); cardCreate(); break;
    case 'card_list':           finRequireAuth(); cardList(); break;
    case 'card_update':         finRequireAdminOrInternal(); cardUpdate(); break;
    case 'card_transactions':   finRequireAuth(); cardTransactions(); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'tax_calculate','tax_rates','tax_settings',
            'connect_onboard','connect_status','connect_dashboard','connect_payout','connect_list',
            'meter_create','meter_list','meter_report','meter_usage',
            'card_create','card_list','card_update','card_transactions'
        ]], 400);
}

// ═══ STRIPE TAX ═══════════════════════════════════════════════

function taxCalculate(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $amount = (int) ($input['amount_cents'] ?? 0);
    $currency = sanitize($input['currency'] ?? 'usd', 10);
    $country = sanitize($input['country'] ?? '', 5);
    $state = sanitize($input['state'] ?? '', 10);
    $lineItems = $input['line_items'] ?? [];

    if ($amount <= 0) {
        jsonResponse(['error' => 'amount_cents required and must be positive'], 400);
    }

    try {
        $calcParams = [
            'currency' => $currency,
            'customer_details' => ['address' => ['country' => $country]],
            'line_items' => []
        ];

        if ($state) {
            $calcParams['customer_details']['address']['state'] = $state;
        }

        if (!empty($lineItems)) {
            foreach ($lineItems as $item) {
                $calcParams['line_items'][] = [
                    'amount' => (int) $item['amount_cents'],
                    'reference' => $item['reference'] ?? 'item',
                    'tax_behavior' => $item['tax_behavior'] ?? 'exclusive',
                    'tax_code' => $item['tax_code'] ?? 'txcd_10000000', // General SaaS
                ];
            }
        } else {
            $calcParams['line_items'][] = [
                'amount' => $amount,
                'reference' => 'single_item',
                'tax_behavior' => 'exclusive',
                'tax_code' => 'txcd_10000000',
            ];
        }

        $calculation = \Stripe\Tax\Calculation::create($calcParams);

        // Store calculation
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO fin_tax_calculations (client_id, stripe_tax_calc_id, amount_cents, tax_cents, currency, customer_country, customer_state, tax_type, line_items)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            finGetClientId(),
            $calculation->id,
            $calculation->amount_total - $calculation->tax_amount_exclusive,
            $calculation->tax_amount_exclusive + $calculation->tax_amount_inclusive,
            $currency,
            $country,
            $state,
            'stripe_tax',
            json_encode($calculation->line_items->data ?? [])
        ]);

        finAuditLog('tax_calculate', 'stripe_tax', [
            'amount' => $amount, 'tax' => $calculation->tax_amount_exclusive,
            'country' => $country, 'state' => $state
        ]);

        jsonResponse([
            'success' => true,
            'calculation_id' => $calculation->id,
            'subtotal_cents' => $amount,
            'tax_cents' => $calculation->tax_amount_exclusive,
            'total_cents' => $calculation->amount_total,
            'currency' => $currency,
            'tax_breakdown' => $calculation->tax_breakdown ?? []
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function taxGetRates(): void {
    $country = sanitize($_GET['country'] ?? '', 5);
    if (!$country) {
        jsonResponse(['error' => 'country parameter required'], 400);
    }
    try {
        $rates = \Stripe\TaxRate::all(['limit' => 100]);
        $filtered = array_filter($rates->data, fn($r) => $r->country === strtoupper($country));
        jsonResponse([
            'success' => true,
            'country' => strtoupper($country),
            'rates' => array_map(fn($r) => [
                'id' => $r->id,
                'display_name' => $r->display_name,
                'percentage' => $r->percentage,
                'inclusive' => $r->inclusive,
                'jurisdiction' => $r->jurisdiction,
                'state' => $r->state ?? null,
            ], array_values($filtered))
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function taxSettings(): void {
    try {
        $settings = \Stripe\Tax\Settings::retrieve();
        jsonResponse([
            'success' => true,
            'status' => $settings->status,
            'head_office' => $settings->head_office ?? null,
            'defaults' => $settings->defaults ?? null,
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

// ═══ STRIPE CONNECT ═══════════════════════════════════════════

function connectOnboard(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $clientId = finGetClientId();
    $accountType = sanitize($input['account_type'] ?? 'express', 20);
    $businessType = sanitize($input['business_type'] ?? 'individual', 30);

    $db = getDB();
    $existing = $db->prepare("SELECT stripe_account_id FROM fin_stripe_connect_accounts WHERE client_id = ?");
    $existing->execute([$clientId]);
    $row = $existing->fetch();

    try {
        if ($row) {
            // Already has account — create new onboarding link
            $accountLink = \Stripe\AccountLink::create([
                'account' => $row['stripe_account_id'],
                'refresh_url' => SITE_URL . '/dashboard.php?tab=payments&connect=refresh',
                'return_url' => SITE_URL . '/dashboard.php?tab=payments&connect=complete',
                'type' => 'account_onboarding',
            ]);
            jsonResponse(['success' => true, 'url' => $accountLink->url, 'account_id' => $row['stripe_account_id']]);
            return;
        }

        // Create new connected account
        $account = \Stripe\Account::create([
            'type' => $accountType,
            'country' => sanitize($input['country'] ?? 'CA', 5),
            'email' => $_SESSION['client_email'] ?? null,
            'business_type' => $businessType,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'metadata' => ['gositeme_client_id' => $clientId],
        ]);

        // Store in DB
        $stmt = $db->prepare("INSERT INTO fin_stripe_connect_accounts (client_id, stripe_account_id, account_type, business_type)
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$clientId, $account->id, $accountType, $businessType]);

        // Create onboarding link
        $accountLink = \Stripe\AccountLink::create([
            'account' => $account->id,
            'refresh_url' => SITE_URL . '/dashboard.php?tab=payments&connect=refresh',
            'return_url' => SITE_URL . '/dashboard.php?tab=payments&connect=complete',
            'type' => 'account_onboarding',
        ]);

        finAuditLog('connect_onboard', 'stripe_connect', ['account_id' => $account->id]);

        jsonResponse(['success' => true, 'url' => $accountLink->url, 'account_id' => $account->id]);

    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function connectStatus(): void {
    $clientId = finGetClientId();
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM fin_stripe_connect_accounts WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['success' => true, 'connected' => false]);
        return;
    }

    try {
        $account = \Stripe\Account::retrieve($row['stripe_account_id']);

        // Sync status
        $update = $db->prepare("UPDATE fin_stripe_connect_accounts SET charges_enabled=?, payouts_enabled=?, onboarding_complete=? WHERE client_id=?");
        $update->execute([
            $account->charges_enabled ? 1 : 0,
            $account->payouts_enabled ? 1 : 0,
            $account->details_submitted ? 1 : 0,
            $clientId
        ]);

        jsonResponse([
            'success' => true,
            'connected' => true,
            'account_id' => $account->id,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
            'onboarding_complete' => $account->details_submitted,
            'country' => $account->country,
            'default_currency' => $account->default_currency,
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function connectDashboard(): void {
    $clientId = finGetClientId();
    $db = getDB();
    $stmt = $db->prepare("SELECT stripe_account_id FROM fin_stripe_connect_accounts WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['error' => 'No connected account found'], 404);
        return;
    }

    try {
        $link = \Stripe\Account::createLoginLink($row['stripe_account_id']);
        jsonResponse(['success' => true, 'url' => $link->url]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function connectPayout(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $accountId = sanitize($input['account_id'] ?? '', 50);
    $amountCents = (int) ($input['amount_cents'] ?? 0);
    $currency = sanitize($input['currency'] ?? 'usd', 10);

    if (!$accountId || $amountCents <= 0) {
        jsonResponse(['error' => 'account_id and amount_cents required'], 400);
    }

    if ($amountCents > finToCents(FIN_MAX_PAYOUT_USD)) {
        jsonResponse(['error' => 'Payout exceeds safety limit of $' . FIN_MAX_PAYOUT_USD], 403);
    }

    try {
        $transfer = \Stripe\Transfer::create([
            'amount' => $amountCents,
            'currency' => $currency,
            'destination' => $accountId,
            'metadata' => ['initiated_by' => 'alfred_paymaster'],
        ]);

        finAuditLog('connect_payout', 'stripe_connect', [
            'account_id' => $accountId, 'amount_cents' => $amountCents,
            'transfer_id' => $transfer->id
        ]);

        jsonResponse(['success' => true, 'transfer_id' => $transfer->id, 'amount_cents' => $amountCents]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function connectList(): void {
    $db = getDB();
    $accounts = $db->query("SELECT * FROM fin_stripe_connect_accounts ORDER BY created_at DESC")->fetchAll();
    jsonResponse(['success' => true, 'accounts' => $accounts, 'count' => count($accounts)]);
}

// ═══ STRIPE BILLING METERS ════════════════════════════════════

function meterCreate(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $eventName = sanitize($input['event_name'] ?? '', 100);
    $displayName = sanitize($input['display_name'] ?? $eventName, 100);
    $aggregation = sanitize($input['aggregation'] ?? 'sum', 10);

    if (!$eventName) {
        jsonResponse(['error' => 'event_name required'], 400);
    }

    try {
        $meter = \Stripe\Billing\Meter::create([
            'display_name' => $displayName,
            'event_name' => $eventName,
            'default_aggregation' => ['formula' => $aggregation],
        ]);

        $db = getDB();
        $stmt = $db->prepare("INSERT INTO fin_stripe_meters (meter_id, meter_name, event_name, display_name, aggregation_type)
            VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE display_name=VALUES(display_name)");
        $stmt->execute([$meter->id, $eventName, $eventName, $displayName, $aggregation]);

        finAuditLog('meter_create', 'stripe_billing', ['meter_id' => $meter->id, 'event_name' => $eventName]);

        jsonResponse(['success' => true, 'meter' => [
            'id' => $meter->id, 'event_name' => $eventName,
            'display_name' => $displayName, 'aggregation' => $aggregation
        ]]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function meterList(): void {
    try {
        $meters = \Stripe\Billing\Meter::all(['limit' => 100]);
        $result = array_map(fn($m) => [
            'id' => $m->id,
            'display_name' => $m->display_name,
            'event_name' => $m->event_name,
            'status' => $m->status,
        ], $meters->data);
        jsonResponse(['success' => true, 'meters' => $result]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function meterReport(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $eventName = sanitize($input['event_name'] ?? '', 100);
    $quantity = (int) ($input['quantity'] ?? 1);
    $clientId = finGetClientId();

    if (!$eventName || $quantity <= 0) {
        jsonResponse(['error' => 'event_name and positive quantity required'], 400);
    }

    // Get Stripe customer ID for this client
    $db = getDB();
    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $pref = $stmt->fetch();
    $settings = $pref ? json_decode($pref['notification_settings'], true) : [];
    $stripeCustomerId = $settings['stripe_customer_id'] ?? null;

    if (!$stripeCustomerId) {
        jsonResponse(['error' => 'No Stripe customer linked to this account'], 400);
    }

    $timestamp = time();
    $idempotencyKey = "meter_{$clientId}_{$eventName}_{$timestamp}_" . bin2hex(random_bytes(4));

    try {
        \Stripe\Billing\MeterEvent::create([
            'event_name' => $eventName,
            'payload' => [
                'value' => $quantity,
                'stripe_customer_id' => $stripeCustomerId,
            ],
            'timestamp' => $timestamp,
        ]);

        // Log locally
        $stmt = $db->prepare("INSERT INTO fin_stripe_usage_events (client_id, meter_name, quantity, stripe_customer_id, timestamp, idempotency_key)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$clientId, $eventName, $quantity, $stripeCustomerId, $timestamp, $idempotencyKey]);

        finAuditLog('meter_report', 'stripe_billing', [
            'event_name' => $eventName, 'quantity' => $quantity
        ]);

        jsonResponse(['success' => true, 'event_name' => $eventName, 'quantity' => $quantity, 'timestamp' => $timestamp]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function meterGetUsage(): void {
    $clientId = finGetClientId();
    $meterName = sanitize($_GET['meter'] ?? '', 100);
    $days = (int) ($_GET['days'] ?? 30);

    $db = getDB();
    $sql = "SELECT meter_name, SUM(quantity) as total, COUNT(*) as events, MIN(timestamp) as first_ts, MAX(timestamp) as last_ts
            FROM fin_stripe_usage_events WHERE client_id = ?";
    $params = [$clientId];

    if ($meterName) {
        $sql .= " AND meter_name = ?";
        $params[] = $meterName;
    }

    $sql .= " AND timestamp >= ? GROUP BY meter_name ORDER BY total DESC";
    $params[] = time() - ($days * 86400);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonResponse(['success' => true, 'days' => $days, 'usage' => $stmt->fetchAll()]);
}

// ═══ STRIPE ISSUING ═══════════════════════════════════════════

function cardCreate(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $clientId = (int) ($input['client_id'] ?? finGetClientId());
    $cardType = sanitize($input['type'] ?? 'virtual', 10);
    $label = sanitize($input['label'] ?? 'Alfred Card', 100);
    $spendingLimitCents = (int) ($input['spending_limit_cents'] ?? 50000);
    $interval = sanitize($input['spending_interval'] ?? 'monthly', 20);

    // Get or create cardholder
    $db = getDB();
    $clientStmt = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id = ?");
    $clientStmt->execute([$clientId]);
    $client = $clientStmt->fetch();
    if (!$client) {
        jsonResponse(['error' => 'Client not found'], 404);
    }

    try {
        $cardholder = \Stripe\Issuing\Cardholder::create([
            'name' => $client['firstname'] . ' ' . $client['lastname'],
            'email' => $client['email'],
            'type' => 'individual',
            'billing' => [
                'address' => [
                    'city' => 'Montreal',
                    'country' => 'CA',
                    'line1' => 'GoSiteMe HQ',
                    'postal_code' => 'H2X 1Y4',
                    'state' => 'QC',
                ],
            ],
        ]);

        $card = \Stripe\Issuing\Card::create([
            'cardholder' => $cardholder->id,
            'currency' => 'usd',
            'type' => $cardType,
            'spending_controls' => [
                'spending_limits' => [[
                    'amount' => $spendingLimitCents,
                    'interval' => $interval,
                ]],
            ],
            'metadata' => ['gositeme_client_id' => $clientId, 'label' => $label],
        ]);

        $stmt = $db->prepare("INSERT INTO fin_stripe_issued_cards
            (client_id, card_id, cardholder_id, card_type, last4, spending_limit_cents, spending_interval, label)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $clientId, $card->id, $cardholder->id, $cardType,
            $card->last4, $spendingLimitCents, $interval, $label
        ]);

        finAuditLog('card_create', 'stripe_issuing', [
            'card_id' => $card->id, 'type' => $cardType, 'limit_cents' => $spendingLimitCents
        ]);

        jsonResponse([
            'success' => true,
            'card' => [
                'id' => $card->id,
                'type' => $cardType,
                'last4' => $card->last4,
                'status' => $card->status,
                'spending_limit' => finFromCents($spendingLimitCents),
                'label' => $label,
            ]
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function cardList(): void {
    $clientId = finGetClientId();
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM fin_stripe_issued_cards WHERE client_id = ? ORDER BY created_at DESC");
    $stmt->execute([$clientId]);
    $cards = $stmt->fetchAll();

    jsonResponse(['success' => true, 'cards' => array_map(fn($c) => [
        'id' => $c['card_id'],
        'type' => $c['card_type'],
        'last4' => $c['last4'],
        'status' => $c['status'],
        'spending_limit' => finFromCents($c['spending_limit_cents']),
        'interval' => $c['spending_interval'],
        'label' => $c['label'],
        'created_at' => $c['created_at'],
    ], $cards)]);
}

function cardUpdate(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $cardId = sanitize($input['card_id'] ?? '', 50);
    $status = sanitize($input['status'] ?? '', 20);
    $newLimitCents = isset($input['spending_limit_cents']) ? (int) $input['spending_limit_cents'] : null;

    if (!$cardId) {
        jsonResponse(['error' => 'card_id required'], 400);
    }

    try {
        $updateParams = [];
        if ($status === 'active' || $status === 'inactive' || $status === 'canceled') {
            $updateParams['status'] = $status;
        }
        if ($newLimitCents !== null) {
            $interval = sanitize($input['spending_interval'] ?? 'monthly', 20);
            $updateParams['spending_controls'] = [
                'spending_limits' => [['amount' => $newLimitCents, 'interval' => $interval]]
            ];
        }

        $card = \Stripe\Issuing\Card::update($cardId, $updateParams);

        $db = getDB();
        $stmt = $db->prepare("UPDATE fin_stripe_issued_cards SET status=?, spending_limit_cents=COALESCE(?,spending_limit_cents) WHERE card_id=?");
        $stmt->execute([$status ?: $card->status, $newLimitCents, $cardId]);

        finAuditLog('card_update', 'stripe_issuing', ['card_id' => $cardId, 'status' => $status]);

        jsonResponse(['success' => true, 'card_id' => $cardId, 'status' => $card->status]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}

function cardTransactions(): void {
    $cardId = sanitize($_GET['card_id'] ?? '', 50);
    $limit = min((int) ($_GET['limit'] ?? 25), 100);

    try {
        $params = ['limit' => $limit];
        if ($cardId) $params['card'] = $cardId;

        $txns = \Stripe\Issuing\Transaction::all($params);

        jsonResponse(['success' => true, 'transactions' => array_map(fn($t) => [
            'id' => $t->id,
            'amount_cents' => $t->amount,
            'currency' => $t->currency,
            'merchant_name' => $t->merchant_data->name ?? 'Unknown',
            'merchant_category' => $t->merchant_data->category ?? '',
            'type' => $t->type,
            'created' => date('Y-m-d H:i:s', $t->created),
        ], $txns->data)]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[stripe-advanced] ' . $e->getMessage()); jsonResponse(['error' => 'Payment processing error'], 500);
    }
}
