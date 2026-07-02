<?php
/**
 * Stripe Integration API
 * Handles subscriptions, checkout, webhooks, and billing portal
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // Stripe webhook signature verification
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Stripe configuration — keys loaded from config.php via getenv()
if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Payment system not configured']);
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Plan definitions — 6 tiers (prices in cents)
define('PLANS', [
    'free' => [
        'name' => 'Alfred Free',
        'price' => 0,
        'annual_price' => 0,
        'display_price' => 'Free',
        'annual_display' => 'Free',
        'features' => ['10 tools', '5 voice min/day', '1 agent', 'Web chat only', '100 API calls/day'],
        'tool_limit' => 10,
        'fleet_limit' => 0,
        'agent_limit' => 1,
        'api_daily_limit' => 100,
        'voice_daily_min' => 5,
        'storage_mb' => 1024,
        'conference_max' => 0,
    ],
    'starter' => [
        'name' => 'Alfred Starter',
        'price' => 399,
        'annual_price' => 3326,  // ~$3.33/mo billed annually (save 17%)
        'display_price' => '$3.99/mo',
        'annual_display' => '$33.26/yr (save 17%)',
        'features' => ['100 tools', '60 voice min/day', '3 agents', 'Web + voice', 'Email support', '10,000 API calls/day', '4-person rooms'],
        'tool_limit' => 100,
        'fleet_limit' => 1,
        'agent_limit' => 3,
        'api_daily_limit' => 10000,
        'voice_daily_min' => 60,
        'storage_mb' => 10240,
        'conference_max' => 4,
    ],
    'professional' => [
        'name' => 'Alfred Professional',
        'price' => 999,
        'annual_price' => 8325,
        'display_price' => '$9.99/mo',
        'annual_display' => '$83.25/yr (save 17%)',
        'features' => ['ALL 1,220+ tools', 'Unlimited voice', '5 agents', 'All channels', 'Priority support', '100,000 API/day', '10-person rooms', 'Marketplace publish'],
        'tool_limit' => -1,
        'fleet_limit' => 3,
        'agent_limit' => 5,
        'api_daily_limit' => 100000,
        'voice_daily_min' => -1,
        'storage_mb' => 51200,
        'conference_max' => 10,
    ],
    'enterprise' => [
        'name' => 'Alfred Enterprise',
        'price' => 2499,
        'annual_price' => 20825,
        'display_price' => '$24.99/mo',
        'annual_display' => '$208.25/yr (save 17%)',
        'features' => ['ALL tools + priority', 'Unlimited voice', '20 agents', 'All channels', '24/7 support', '500,000 API/day', '20-person rooms', 'Org accounts', 'Team management'],
        'tool_limit' => -1,
        'fleet_limit' => 10,
        'agent_limit' => 20,
        'api_daily_limit' => 500000,
        'voice_daily_min' => -1,
        'storage_mb' => 204800,
        'conference_max' => 20,
    ],
    'enterprise_plus' => [
        'name' => 'Alfred Enterprise Plus',
        'price' => 9900,
        'annual_price' => 82500,
        'display_price' => '$99/mo',
        'annual_display' => '$825/yr (save 17%)',
        'features' => ['Everything in Enterprise', 'SSO (SAML/OIDC)', 'Audit logging', 'Dedicated CSM', 'Unlimited API', '50-person rooms', 'Revenue sharing', 'Voice cloning', 'Data residency'],
        'tool_limit' => -1,
        'fleet_limit' => -1,
        'agent_limit' => -1,
        'api_daily_limit' => -1,
        'voice_daily_min' => -1,
        'storage_mb' => 1048576,
        'conference_max' => 50,
    ],
    'custom' => [
        'name' => 'Alfred Enterprise Custom',
        'price' => 29900,
        'annual_price' => 249166,
        'display_price' => '$299+/mo',
        'annual_display' => 'Custom annual pricing',
        'features' => ['Everything in Enterprise Plus', 'White-label deploy', 'Custom SLA (99.95%)', 'Dedicated support', 'Unlimited everything', 'On-site onboarding', 'Custom training', 'Dedicated infrastructure'],
        'tool_limit' => -1,
        'fleet_limit' => -1,
        'agent_limit' => -1,
        'api_daily_limit' => -1,
        'voice_daily_min' => -1,
        'storage_mb' => -1,
        'conference_max' => -1,
    ],
]);

// Overage rate constants (USD per unit)
define('OVERAGE_RATES', [
    'api_call'        => 0.001,   // $0.001 per call above limit
    'voice_minute'    => 0.05,    // $0.05 per minute above limit
    'agent_hour'      => 1.00,    // $1.00 per agent per month (always-on)
    'storage_mb'      => 0.0001,  // $0.10 per GB per month
    'conference_min'  => 0.02,    // $0.02 per participant per minute
    'voice_clone'     => 5.00,    // $5.00 per voice profile
    'outbound_min'    => 0.03,    // $0.03 per outbound minute
    'sms'             => 0.01,    // $0.01 per SMS
    'whatsapp'        => 0.02,    // $0.02 per WhatsApp message
    'image_gen'       => 0.02,    // $0.02 per image
    'doc_analysis'    => 0.05,    // $0.05 per document
    'translation_min' => 0.08,    // $0.08 per translation minute
]);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Strict rate limits for payment operations (fraud protection)
if (in_array($action, ['create_checkout', 'portal', 'switch-plan', 'switch_plan'], true)) {
    apiRateLimit(10, 60, 'stripe_sensitive');
}

switch ($action) {
    case 'plans':
        getPlans();
        break;
    case 'create_checkout':
        requireAuth();
        createCheckout();
        break;
    case 'portal':
        requireAuth();
        createPortal();
        break;
    case 'status':
        requireAuth();
        getSubscriptionStatus();
        break;
    case 'webhook':
        handleWebhook();
        break;
    case 'report-usage':
    case 'report_usage':
        requireAuth();
        reportUsage();
        break;
    case 'usage-summary':
    case 'usage_summary':
        requireAuth();
        getUsageSummary();
        break;
    case 'switch-plan':
    case 'switch_plan':
        requireAuth();
        switchPlan();
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function getClientId() {
    return (int) $_SESSION['client_id'];
}

function loadPreferenceSettings($db, $clientId) {
    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    $settings = [];
    if ($prefs && !empty($prefs['notification_settings'])) {
        $settings = json_decode($prefs['notification_settings'], true) ?: [];
    }

    return [$prefs, $settings];
}

function savePreferenceSettings($db, $clientId, array $settings, $prefsExists = false) {
    $jsonSettings = json_encode($settings);

    if ($prefsExists) {
        $stmt = $db->prepare("UPDATE alfred_user_preferences SET notification_settings = ? WHERE user_id = ?");
        return $stmt->execute([$jsonSettings, $clientId]);
    }

    $stmt = $db->prepare("INSERT INTO alfred_user_preferences (user_id, notification_settings) VALUES (?, ?)");
    return $stmt->execute([$clientId, $jsonSettings]);
}

function clientsStripeCustomerColumnExists($db) {
    static $hasColumn = null;

    if ($hasColumn !== null) {
        return $hasColumn;
    }

    $stmt = $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'stripe_customer_id'");
    $hasColumn = ((int) $stmt->fetchColumn()) > 0;

    return $hasColumn;
}

function getStoredStripeCustomerId($db, $clientId) {
    if (clientsStripeCustomerColumnExists($db)) {
        $stmt = $db->prepare("SELECT stripe_customer_id FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $stripeCustomerId = $stmt->fetchColumn();
        if (!empty($stripeCustomerId)) {
            return $stripeCustomerId;
        }
    }

    [$prefs, $settings] = loadPreferenceSettings($db, $clientId);
    $stripeCustomerId = $settings['stripe_customer_id'] ?? null;

    if ($stripeCustomerId && clientsStripeCustomerColumnExists($db)) {
        $stmt = $db->prepare("UPDATE clients SET stripe_customer_id = ? WHERE id = ?");
        $stmt->execute([$stripeCustomerId, $clientId]);
    }

    return $stripeCustomerId;
}

function storeStripeCustomerId($db, $clientId, $stripeCustomerId) {
    if (empty($stripeCustomerId)) {
        return false;
    }

    if (clientsStripeCustomerColumnExists($db)) {
        $stmt = $db->prepare("UPDATE clients SET stripe_customer_id = ? WHERE id = ?");
        $stmt->execute([$stripeCustomerId, $clientId]);
    }

    [$prefs, $settings] = loadPreferenceSettings($db, $clientId);
    if (($settings['stripe_customer_id'] ?? null) === $stripeCustomerId) {
        return true;
    }

    $settings['stripe_customer_id'] = $stripeCustomerId;
    return savePreferenceSettings($db, $clientId, $settings, (bool) $prefs);
}

/**
 * Get or create Stripe customer linked to client
 */
function getOrCreateStripeCustomer($clientId) {
    $db = getDB();
    if (!$db) {
        error_log("Stripe: DB connection failed for client $clientId");
        return null;
    }

    $stripeCustomerId = getStoredStripeCustomerId($db, $clientId);

    // Verify the customer still exists in Stripe
    if ($stripeCustomerId) {
        try {
            $customer = \Stripe\Customer::retrieve($stripeCustomerId);
            if ($customer && !$customer->deleted) {
                return $customer;
            }
        } catch (\Exception $e) {
            error_log("Stripe: Customer retrieval failed: " . $e->getMessage());
            $stripeCustomerId = null;
        }
    }

    // Look up client info
    $stmt = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    if (!$client) {
        error_log("Stripe: client $clientId not found");
        return null;
    }

    // Create Stripe customer
    try {
        $customer = \Stripe\Customer::create([
            'email' => $client['email'],
            'name' => $client['firstname'] . ' ' . $client['lastname'],
            'metadata' => [
                'billing_client_id' => $clientId,
                'source' => 'alfred_platform',
            ],
        ]);

        storeStripeCustomerId($db, $clientId, $customer->id);

        return $customer;
    } catch (\Exception $e) {
        error_log("Stripe: Customer creation failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Get or create a Stripe Price for a plan
 * @param string $planKey  Plan identifier
 * @param bool   $annual   Whether to use annual pricing
 */
function getOrCreatePrice($planKey, $annual = false) {
    $plan = PLANS[$planKey] ?? null;
    if (!$plan) return null;

    $interval = $annual ? 'year' : 'month';
    $amount   = $annual ? ($plan['annual_price'] ?? $plan['price'] * 10) : $plan['price'];
    $metaKey  = $planKey . '_' . $interval;

    // Search for existing price with matching metadata
    try {
        $prices = \Stripe\Price::search([
            'query' => "active:'true' AND metadata['alfred_plan_interval']:'" . $metaKey . "'",
        ]);

        if ($prices->data && count($prices->data) > 0) {
            return $prices->data[0];
        }
    } catch (\Exception $e) {
        error_log("Stripe: Price search failed, creating new: " . $e->getMessage());
    }

    // Also try legacy metadata for backward compatibility
    if (!$annual) {
        try {
            $prices = \Stripe\Price::search([
                'query' => "active:'true' AND metadata['alfred_plan']:'" . $planKey . "'",
            ]);
            if ($prices->data && count($prices->data) > 0) {
                return $prices->data[0];
            }
        } catch (\Exception $e) {
            // continue to create
        }
    }

    // Create product and price
    try {
        // Try to find existing product first
        $product = null;
        try {
            $products = \Stripe\Product::search([
                'query' => "active:'true' AND metadata['alfred_plan']:'" . $planKey . "'",
            ]);
            if ($products->data && count($products->data) > 0) {
                $product = $products->data[0];
            }
        } catch (\Exception $e) {
            // will create new
        }

        if (!$product) {
            $product = \Stripe\Product::create([
                'name' => $plan['name'],
                'metadata' => ['alfred_plan' => $planKey],
            ]);
        }

        $price = \Stripe\Price::create([
            'unit_amount' => $amount,
            'currency' => 'usd',
            'recurring' => ['interval' => $interval],
            'product' => $product->id,
            'metadata' => [
                'alfred_plan' => $planKey,
                'alfred_plan_interval' => $metaKey,
            ],
        ]);

        return $price;
    } catch (\Exception $e) {
        error_log("Stripe: Price creation failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Store plan info in alfred_user_preferences
 */
function storePlanInfo($clientId, $planKey, $stripeSubscriptionId, $status = 'active') {
    $db = getDB();
    if (!$db) return false;

    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch();

    $settings = [];
    if ($prefs && $prefs['notification_settings']) {
        $settings = json_decode($prefs['notification_settings'], true) ?: [];
    }

    $settings['plan'] = $planKey;
    $settings['stripe_subscription_id'] = $stripeSubscriptionId;
    $settings['subscription_status'] = $status;
    $settings['plan_updated_at'] = date('c');

    $plan = PLANS[$planKey] ?? null;
    if ($plan) {
        $settings['tool_limit'] = $plan['tool_limit'];
        $settings['fleet_limit'] = $plan['fleet_limit'];
        $settings['agent_limit'] = $plan['agent_limit'] ?? 1;
        $settings['api_daily_limit'] = $plan['api_daily_limit'] ?? 0;
        $settings['voice_daily_min'] = $plan['voice_daily_min'] ?? 0;
        $settings['storage_mb'] = $plan['storage_mb'] ?? 0;
        $settings['conference_max'] = $plan['conference_max'] ?? 0;
    }

    $jsonSettings = json_encode($settings);

    try {
        if ($prefs) {
            $stmt = $db->prepare("UPDATE alfred_user_preferences SET notification_settings = ? WHERE user_id = ?");
            $stmt->execute([$jsonSettings, $clientId]);
        } else {
            $stmt = $db->prepare("INSERT INTO alfred_user_preferences (user_id, notification_settings) VALUES (?, ?)");
            $stmt->execute([$clientId, $jsonSettings]);
        }
        return true;
    } catch (\Exception $e) {
        error_log("Stripe: storePlanInfo failed: " . $e->getMessage());
        return false;
    }
}

// ─── Actions ────────────────────────────────────────────────────────────────

/**
 * Return available plans (public, no auth) — includes monthly & annual pricing
 */
function getPlans() {
    $plans = [];
    foreach (PLANS as $key => $plan) {
        $plans[] = [
            'key' => $key,
            'name' => $plan['name'],
            'price' => $plan['price'] / 100,
            'price_cents' => $plan['price'],
            'annual_price' => ($plan['annual_price'] ?? 0) / 100,
            'annual_price_cents' => $plan['annual_price'] ?? 0,
            'display_price' => $plan['display_price'],
            'annual_display' => $plan['annual_display'] ?? '',
            'features' => $plan['features'],
            'tool_limit' => $plan['tool_limit'] ?? 0,
            'fleet_limit' => $plan['fleet_limit'] ?? 0,
            'agent_limit' => $plan['agent_limit'] ?? 1,
            'api_daily_limit' => $plan['api_daily_limit'] ?? 0,
            'voice_daily_min' => $plan['voice_daily_min'] ?? 0,
            'storage_mb' => $plan['storage_mb'] ?? 0,
            'conference_max' => $plan['conference_max'] ?? 0,
        ];
    }
    jsonResponse(['success' => true, 'plans' => $plans, 'overage_rates' => OVERAGE_RATES]);
}

/**
 * Create Stripe Checkout Session
 */
function createCheckout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $planKey = sanitize($input['plan'] ?? '', 30);
    $billingPeriod = sanitize($input['billing_period'] ?? $input['interval'] ?? 'monthly', 10);
    $isAnnual = in_array($billingPeriod, ['annual', 'year', 'yearly']);

    if (!isset(PLANS[$planKey])) {
        jsonResponse(['error' => 'Invalid plan. Choose: free, starter, professional, enterprise, enterprise_plus, or custom'], 400);
    }

    if ($planKey === 'free') {
        jsonResponse(['error' => 'Free plan does not require checkout'], 400);
    }

    if ($planKey === 'custom') {
        jsonResponse(['success' => true, 'redirect' => SITE_URL . '/enterprise.php?contact=sales', 'message' => 'Please contact sales for custom pricing'], 200);
        return;
    }

    $clientId = getClientId();
    $customer = getOrCreateStripeCustomer($clientId);
    if (!$customer) {
        jsonResponse(['error' => 'Failed to create payment customer'], 500);
    }

    $price = getOrCreatePrice($planKey, $isAnnual);
    if (!$price) {
        jsonResponse(['error' => 'Failed to configure pricing'], 500);
    }

    try {
        $sessionParams = [
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $price->id,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => SITE_URL . '/dashboard.php?subscription=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => SITE_URL . '/dashboard.php?subscription=cancelled',
            'metadata' => [
                'billing_client_id' => $clientId,
                'alfred_plan' => $planKey,
                'billing_period' => $isAnnual ? 'annual' : 'monthly',
            ],
            'subscription_data' => [
                'metadata' => [
                    'billing_client_id' => $clientId,
                    'alfred_plan' => $planKey,
                    'billing_period' => $isAnnual ? 'annual' : 'monthly',
                ],
            ],
        ];

        // Add trial for non-free plans
        if (PLANS[$planKey]['price'] > 0) {
            $sessionParams['subscription_data']['trial_period_days'] = 14;
        }

        $session = \Stripe\Checkout\Session::create($sessionParams);

        jsonResponse([
            'success' => true,
            'session_id' => $session->id,
            'url' => $session->url,
            'checkout_url' => $session->url,
        ]);
    } catch (\Exception $e) {
        error_log("Stripe: Checkout session creation failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to create checkout session'], 500);
    }
}

/**
 * Create Stripe Billing Portal Session
 */
function createPortal() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $clientId = getClientId();
    $customer = getOrCreateStripeCustomer($clientId);
    if (!$customer) {
        jsonResponse(['error' => 'No billing account found'], 404);
    }

    try {
        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $customer->id,
            'return_url' => SITE_URL . '/dashboard.php',
        ]);

        jsonResponse([
            'success' => true,
            'url' => $session->url,
        ]);
    } catch (\Exception $e) {
        error_log("Stripe: Portal session creation failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to create billing portal session'], 500);
    }
}

/**
 * Get current subscription status
 */
function getSubscriptionStatus() {
    $clientId = getClientId();
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch();

    if (!$prefs || !$prefs['notification_settings']) {
        jsonResponse([
            'success' => true,
            'subscription' => null,
            'plan' => 'free',
            'status' => 'none',
        ]);
    }

    $settings = json_decode($prefs['notification_settings'], true) ?: [];
    $plan = $settings['plan'] ?? 'free';
    $status = $settings['subscription_status'] ?? 'none';
    $subscriptionId = $settings['stripe_subscription_id'] ?? null;

    $subscriptionDetails = null;
    if ($subscriptionId) {
        try {
            $sub = \Stripe\Subscription::retrieve($subscriptionId);
            $subscriptionDetails = [
                'id' => $sub->id,
                'status' => $sub->status,
                'current_period_start' => date('c', $sub->current_period_start),
                'current_period_end' => date('c', $sub->current_period_end),
                'cancel_at_period_end' => $sub->cancel_at_period_end,
            ];
            // Update local status if Stripe differs
            if ($sub->status !== $status) {
                $settings['subscription_status'] = $sub->status;
                $newJson = json_encode($settings);
                $stmt2 = $db->prepare("UPDATE alfred_user_preferences SET notification_settings = ? WHERE user_id = ?");
                $stmt2->execute([$newJson, $clientId]);
                $status = $sub->status;
            }
        } catch (\Exception $e) {
            error_log("Stripe: Subscription retrieval failed: " . $e->getMessage());
        }
    }

    $planInfo = PLANS[$plan] ?? null;

    jsonResponse([
        'success' => true,
        'plan' => $plan,
        'plan_name' => $planInfo ? $planInfo['name'] : 'Free',
        'display_price' => $planInfo ? $planInfo['display_price'] : '$0/mo',
        'status' => $status,
        'subscription' => $subscriptionDetails,
        'tool_limit' => $settings['tool_limit'] ?? 10,
        'fleet_limit' => $settings['fleet_limit'] ?? 0,
        'plan_updated_at' => $settings['plan_updated_at'] ?? null,
    ]);
}

/**
 * Handle Stripe Webhooks
 */
function handleWebhook() {
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (empty($payload) || empty($sigHeader)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing payload or signature']);
        exit;
    }

    // Try both webhook secrets (snapshot + thin destinations)
    $event = null;
    $secrets = [STRIPE_WEBHOOK_SECRET, STRIPE_WEBHOOK_SECRET_THIN];
    foreach ($secrets as $secret) {
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
            break; // Success — stop trying
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            continue; // Try next secret
        } catch (\Exception $e) {
            error_log("Stripe webhook payload error: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            exit;
        }
    }
    if (!$event) {
        error_log("Stripe webhook signature verification failed for all secrets");
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }

    error_log("Stripe webhook received: " . $event->type . " (ID: " . $event->id . ")");

    switch ($event->type) {
        case 'checkout.session.completed':
            handleCheckoutCompleted($event->data->object);
            break;

        case 'customer.subscription.updated':
            handleSubscriptionUpdated($event->data->object);
            break;

        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($event->data->object);
            break;

        case 'invoice.payment_succeeded':
            handleInvoicePaymentSucceeded($event->data->object);
            break;

        case 'invoice.payment_failed':
            handleInvoicePaymentFailed($event->data->object);
            break;

        default:
            error_log("Stripe webhook: Unhandled event type " . $event->type);
            break;
    }

    http_response_code(200);
    echo json_encode(['received' => true]);
    exit;
}

/**
 * Handle checkout.session.completed
 */
function handleCheckoutCompleted($session) {
    $clientId = $session->metadata->billing_client_id ?? $session->metadata->whmcs_client_id ?? null;
    $planKey = $session->metadata->alfred_plan ?? null;
    $subscriptionId = $session->subscription ?? null;

    // ── Discord billing path ──────────────────────────────────────────────
    $discordId = $session->metadata->discord_id ?? null;
    $discordPlan = $session->metadata->discord_plan ?? null;
    if ($discordId && $discordPlan) {
        error_log("Stripe: Discord checkout completed for discord_id $discordId, plan: $discordPlan, sub: $subscriptionId");
        $db = getDB();
        if ($db) {
            try {
                $stmt = $db->prepare("UPDATE discord_users SET plan = ?, stripe_customer_id = ?, stripe_subscription_id = ? WHERE discord_id = ?");
                $stmt->execute([$discordPlan, $session->customer, $subscriptionId, $discordId]);
                if ($stmt->rowCount() === 0) {
                    // User hasn't messaged the bot yet — insert a basic record
                    $stmt = $db->prepare("INSERT INTO discord_users (discord_id, username, plan, stripe_customer_id, stripe_subscription_id) VALUES (?, 'pending', ?, ?, ?)");
                    $stmt->execute([$discordId, $discordPlan, $session->customer, $subscriptionId]);
                }
                // Log revenue
                $stmt = $db->prepare("SELECT id FROM discord_users WHERE discord_id = ?");
                $stmt->execute([$discordId]);
                $userId = $stmt->fetchColumn();
                if ($userId) {
                    $planPrices = ['starter' => 3.99, 'pro' => 9.99, 'enterprise' => 24.99];
                    $stmt = $db->prepare("INSERT INTO discord_revenue (discord_user_id, amount_usd, type, stripe_payment_id, description) VALUES (?, ?, 'subscription', ?, ?)");
                    $stmt->execute([$userId, $planPrices[$discordPlan] ?? 0, $subscriptionId, "Discord $discordPlan plan subscription"]);
                }
            } catch (\Exception $e) {
                error_log("Stripe webhook: Discord plan update failed: " . $e->getMessage());
            }
        }
        // If this is Discord-only (no billing_client_id), we're done
        if (!$clientId) return;
    }

    if (!$clientId || !$planKey) {
        error_log("Stripe webhook: checkout.session.completed missing metadata");
        return;
    }

    error_log("Stripe: Checkout completed for client $clientId, plan: $planKey, sub: $subscriptionId");

    // Store the Stripe customer ID
    $db = getDB();
    if ($db && $session->customer) {
        storeStripeCustomerId($db, $clientId, $session->customer);
    }

    storePlanInfo($clientId, $planKey, $subscriptionId, 'active');

    // Log to activity log
    if ($db) {
        try {
            $planName = PLANS[$planKey]['name'] ?? $planKey;
            $stmt = $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)");
            $stmt->execute([
                "Alfred Subscription: Client #$clientId subscribed to $planName",
                "system",
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
        } catch (\Exception $e) {
            error_log("Stripe webhook: activity log failed: " . $e->getMessage());
        }
    }
}

/**
 * Handle customer.subscription.updated
 */
function handleSubscriptionUpdated($subscription) {
    // ── Discord subscription update ───────────────────────────────────────
    $discordId = $subscription->metadata->discord_id ?? null;
    $discordPlan = $subscription->metadata->discord_plan ?? null;
    if ($discordId) {
        $status = $subscription->status;
        $db = getDB();
        if ($db) {
            if ($status === 'active' || $status === 'trialing') {
                $plan = $discordPlan ?: 'starter';
                $stmt = $db->prepare("UPDATE discord_users SET plan = ?, stripe_subscription_id = ? WHERE discord_id = ?");
                $stmt->execute([$plan, $subscription->id, $discordId]);
            } elseif (in_array($status, ['past_due', 'unpaid', 'canceled'])) {
                $stmt = $db->prepare("UPDATE discord_users SET plan = 'free', stripe_subscription_id = NULL WHERE discord_id = ?");
                $stmt->execute([$discordId]);
            }
            error_log("Stripe: Discord subscription updated for $discordId — status: $status");
        }
        if (!($subscription->metadata->billing_client_id ?? null)) return;
    }

    $clientId = $subscription->metadata->billing_client_id ?? $subscription->metadata->whmcs_client_id ?? null;
    $planKey = $subscription->metadata->alfred_plan ?? null;

    if (!$clientId) {
        // Try to find client by Stripe customer ID
        $clientId = findClientByStripeCustomer($subscription->customer);
    }

    if (!$clientId) {
        error_log("Stripe webhook: subscription.updated — cannot identify client");
        return;
    }

    // Determine plan from subscription items if not in metadata
    if (!$planKey && $subscription->items && $subscription->items->data) {
        foreach ($subscription->items->data as $item) {
            $planKey = $item->price->metadata->alfred_plan ?? null;
            if ($planKey) break;
        }
    }

    $planKey = $planKey ?: 'starter';
    $status = $subscription->status; // active, past_due, canceled, unpaid, etc.

    error_log("Stripe: Subscription updated for client $clientId — plan: $planKey, status: $status");

    storePlanInfo($clientId, $planKey, $subscription->id, $status);
}

/**
 * Handle customer.subscription.deleted
 */
function handleSubscriptionDeleted($subscription) {
    // ── Discord subscription cancellation ─────────────────────────────────
    $discordId = $subscription->metadata->discord_id ?? null;
    if ($discordId) {
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("UPDATE discord_users SET plan = 'free', stripe_subscription_id = NULL WHERE discord_id = ?");
            $stmt->execute([$discordId]);
            error_log("Stripe: Discord subscription deleted for $discordId — downgraded to free");
        }
        if (!($subscription->metadata->billing_client_id ?? null)) return;
    }

    $clientId = $subscription->metadata->billing_client_id ?? $subscription->metadata->whmcs_client_id ?? null;
    if (!$clientId) {
        $clientId = findClientByStripeCustomer($subscription->customer);
    }

    if (!$clientId) {
        error_log("Stripe webhook: subscription.deleted — cannot identify client");
        return;
    }

    error_log("Stripe: Subscription deleted for client $clientId");

    // Downgrade to free plan
    $db = getDB();
    if (!$db) return;

    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch();

    $settings = [];
    if ($prefs && $prefs['notification_settings']) {
        $settings = json_decode($prefs['notification_settings'], true) ?: [];
    }

    $settings['plan'] = 'free';
    $settings['subscription_status'] = 'canceled';
    $settings['stripe_subscription_id'] = null;
    $settings['tool_limit'] = 10;
    $settings['fleet_limit'] = 0;
    $settings['plan_updated_at'] = date('c');

    $jsonSettings = json_encode($settings);

    if ($prefs) {
        $stmt = $db->prepare("UPDATE alfred_user_preferences SET notification_settings = ? WHERE user_id = ?");
        $stmt->execute([$jsonSettings, $clientId]);
    } else {
        $stmt = $db->prepare("INSERT INTO alfred_user_preferences (user_id, notification_settings) VALUES (?, ?)");
        $stmt->execute([$clientId, $jsonSettings]);
    }
}

/**
 * Handle invoice.payment_succeeded
 */
function handleInvoicePaymentSucceeded($invoice) {
    $customerId = $invoice->customer ?? null;
    if (!$customerId) return;

    $clientId = findClientByStripeCustomer($customerId);
    if (!$clientId) {
        error_log("Stripe webhook: invoice.payment_succeeded — cannot identify client for customer $customerId");
        return;
    }

    error_log("Stripe: Payment succeeded for client $clientId, amount: " . ($invoice->amount_paid / 100));

    // Log payment in activity log
    $db = getDB();
    if ($db) {
        try {
            $amount = number_format($invoice->amount_paid / 100, 2);
            $stmt = $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)");
            $stmt->execute([
                "Alfred Payment: Client #$clientId paid \$$amount (Invoice: {$invoice->id})",
                "system",
                "0.0.0.0"
            ]);
        } catch (\Exception $e) {
            error_log("Stripe webhook: activity log failed: " . $e->getMessage());
        }
    }
}

/**
 * Handle invoice.payment_failed
 */
function handleInvoicePaymentFailed($invoice) {
    $customerId = $invoice->customer ?? null;
    if (!$customerId) return;

    $clientId = findClientByStripeCustomer($customerId);
    if (!$clientId) {
        error_log("Stripe webhook: invoice.payment_failed — cannot identify client for customer $customerId");
        return;
    }

    error_log("Stripe: Payment FAILED for client $clientId");

    // Update subscription status to past_due
    $db = getDB();
    if (!$db) return;

    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch();

    if ($prefs && $prefs['notification_settings']) {
        $settings = json_decode($prefs['notification_settings'], true) ?: [];
        $settings['subscription_status'] = 'past_due';
        $settings['payment_failed_at'] = date('c');
        $jsonSettings = json_encode($settings);
        $stmt = $db->prepare("UPDATE alfred_user_preferences SET notification_settings = ? WHERE user_id = ?");
        $stmt->execute([$jsonSettings, $clientId]);
    }

    // Log in activity log
    try {
        $stmt = $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)");
        $stmt->execute([
            "Alfred Payment FAILED: Client #$clientId — action required",
            "system",
            "0.0.0.0"
        ]);
    } catch (\Exception $e) {
        error_log("Stripe webhook: activity log failed: " . $e->getMessage());
    }
}

/**
 * Find client ID by Stripe customer ID
 */
function findClientByStripeCustomer($stripeCustomerId) {
    $db = getDB();
    if (!$db) return null;

    if (clientsStripeCustomerColumnExists($db)) {
        $stmt = $db->prepare("SELECT id FROM clients WHERE stripe_customer_id = ? LIMIT 1");
        $stmt->execute([$stripeCustomerId]);
        $clientId = $stmt->fetchColumn();
        if ($clientId) {
            return (int) $clientId;
        }
    }

    $stmt = $db->prepare("SELECT user_id, notification_settings FROM alfred_user_preferences WHERE notification_settings LIKE ?");
    $stmt->execute(['%' . $stripeCustomerId . '%']);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $settings = json_decode($row['notification_settings'], true) ?: [];
        if (($settings['stripe_customer_id'] ?? '') === $stripeCustomerId) {
            if (clientsStripeCustomerColumnExists($db)) {
                $stmt = $db->prepare("UPDATE clients SET stripe_customer_id = ? WHERE id = ?");
                $stmt->execute([$stripeCustomerId, $row['user_id']]);
            }
            return (int) $row['user_id'];
        }
    }

    return null;
}

// ─── Metered Billing Endpoints ──────────────────────────────────────────────

/**
 * POST report-usage — Report metered usage to Stripe + local DB
 */
function reportUsage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $resourceType = sanitize($input['resource_type'] ?? '', 50);
    $quantity = max(0, (float) ($input['quantity'] ?? 1));

    if (empty($resourceType)) {
        jsonResponse(['error' => 'resource_type is required'], 400);
    }

    $validTypes = [
        'api_call', 'voice_minute', 'storage_mb', 'agent_hour', 'tool_execution',
        'sms', 'whatsapp', 'image_gen', 'doc_analysis', 'translation_min',
        'conference_min', 'voice_clone', 'outbound_min'
    ];
    if (!in_array($resourceType, $validTypes)) {
        jsonResponse(['error' => 'Invalid resource_type. Valid: ' . implode(', ', $validTypes)], 400);
    }

    $clientId = getClientId();
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database unavailable'], 500);
    }

    // Get user plan
    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch();
    $settings = ($prefs && $prefs['notification_settings']) ? (json_decode($prefs['notification_settings'], true) ?: []) : [];
    $plan = $settings['plan'] ?? 'free';
    $planConfig = PLANS[$plan] ?? PLANS['free'];

    // Check limits
    $period = date('Y-m');
    $stmtUsage = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM alfred_usage WHERE user_id = ? AND resource_type = ? AND billing_period = ?");
    $stmtUsage->execute([$clientId, $resourceType, $period]);
    $currentUsage = (float) $stmtUsage->fetchColumn();

    // Determine limit for this resource type
    $limitMap = [
        'api_call' => ($planConfig['api_daily_limit'] ?? 0) * 30,
        'voice_minute' => ($planConfig['voice_daily_min'] ?? 0) * 30,
        'storage_mb' => $planConfig['storage_mb'] ?? 0,
        'agent_hour' => ($planConfig['agent_limit'] ?? 1) * 720,
        'conference_min' => ($planConfig['conference_max'] ?? 0) * 60 * 30,
    ];
    $monthlyLimit = $limitMap[$resourceType] ?? 0;
    $isUnlimited = ($monthlyLimit < 0 || ($planConfig['tool_limit'] ?? 0) < 0);
    $isOverage = false;
    $overageRate = OVERAGE_RATES[$resourceType] ?? 0;

    if (!$isUnlimited && $monthlyLimit > 0) {
        if (($currentUsage + $quantity) > $monthlyLimit) {
            if ($overageRate > 0) {
                $isOverage = true;
            } else {
                jsonResponse([
                    'success' => false,
                    'error' => 'Usage limit exceeded',
                    'resource' => $resourceType,
                    'used' => $currentUsage,
                    'limit' => $monthlyLimit,
                    'plan' => $plan,
                    'upgrade_url' => SITE_URL . '/pricing.php',
                ], 429);
            }
        }
    }

    // Record in local DB
    $unitCost = $isOverage ? $overageRate : 0;
    $enumValues = ['api_call','voice_minute','agent_hour','storage_mb','sms','whatsapp','image_gen','doc_analysis','translation_min','conference_min'];
    $enumResource = in_array($resourceType, $enumValues) ? $resourceType : 'api_call';

    try {
        $ins = $db->prepare("INSERT INTO alfred_usage (user_id, resource, resource_type, quantity, unit_cost, is_overage, billing_period, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $ins->execute([$clientId, $enumResource, $resourceType, $quantity, $unitCost, $isOverage ? 1 : 0, $period]);
    } catch (\Exception $e) {
        error_log("Stripe report-usage: local DB insert failed: " . $e->getMessage());
    }

    // Report to Stripe Usage Records if subscription has a metered item
    $stripeReported = false;
    $subscriptionId = $settings['stripe_subscription_id'] ?? null;
    if ($subscriptionId) {
        try {
            $sub = \Stripe\Subscription::retrieve($subscriptionId);
            foreach ($sub->items->data as $item) {
                if (isset($item->price->recurring->usage_type) && $item->price->recurring->usage_type === 'metered') {
                    \Stripe\SubscriptionItem::createUsageRecord($item->id, [
                        'quantity' => (int) $quantity,
                        'timestamp' => time(),
                        'action' => 'increment',
                    ]);
                    $stripeReported = true;
                    break;
                }
            }
        } catch (\Exception $e) {
            error_log("Stripe usage record failed: " . $e->getMessage());
        }
    }

    jsonResponse([
        'success' => true,
        'resource_type' => $resourceType,
        'quantity' => $quantity,
        'total_used' => $currentUsage + $quantity,
        'monthly_limit' => $isUnlimited ? -1 : $monthlyLimit,
        'is_overage' => $isOverage,
        'overage_rate' => $overageRate,
        'stripe_reported' => $stripeReported,
    ]);
}

/**
 * GET usage-summary — Billing-period usage from local DB + plan limits
 */
function getUsageSummary() {
    $clientId = getClientId();
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database unavailable'], 500);
    }

    // Get user plan
    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch();
    $settings = ($prefs && $prefs['notification_settings']) ? (json_decode($prefs['notification_settings'], true) ?: []) : [];
    $plan = $settings['plan'] ?? 'free';
    $planConfig = PLANS[$plan] ?? PLANS['free'];

    $period = date('Y-m');
    $periodStart = date('Y-m-01');
    $periodEnd = date('Y-m-t');

    // Get usage by resource type
    $stmtUsage = $db->prepare("
        SELECT resource_type, COALESCE(SUM(quantity), 0) as total, COALESCE(SUM(unit_cost * quantity), 0) as total_cost
        FROM alfred_usage
        WHERE user_id = ? AND billing_period = ?
        GROUP BY resource_type
    ");
    $stmtUsage->execute([$clientId, $period]);
    $usageRows = $stmtUsage->fetchAll();

    $resources = [];
    $totalOverageCost = 0;

    // Map plan config to resource limits
    $limitMap = [
        'api_call' => ($planConfig['api_daily_limit'] ?? 0) * 30,
        'voice_minute' => ($planConfig['voice_daily_min'] ?? 0) * 30,
        'storage_mb' => $planConfig['storage_mb'] ?? 0,
        'agent_hour' => ($planConfig['agent_limit'] ?? 1) * 720,
        'conference_min' => ($planConfig['conference_max'] ?? 0) * 60 * 30,
        'tool_execution' => ($planConfig['tool_limit'] ?? 0) * 30,
    ];

    foreach ($usageRows as $row) {
        $type = $row['resource_type'];
        $used = (float) $row['total'];
        $cost = (float) $row['total_cost'];
        $limit = $limitMap[$type] ?? 0;
        $isUnlimited = $limit < 0;
        $overageRate = OVERAGE_RATES[$type] ?? 0;
        $overage = (!$isUnlimited && $limit > 0 && $used > $limit) ? ($used - $limit) : 0;
        $overageCost = $overage * $overageRate;
        $totalOverageCost += $overageCost;

        $resources[$type] = [
            'used' => $used,
            'limit' => $isUnlimited ? -1 : $limit,
            'unlimited' => $isUnlimited,
            'percentage' => ($isUnlimited || $limit <= 0) ? 0 : round(($used / $limit) * 100, 1),
            'overage' => $overage,
            'overage_rate' => $overageRate,
            'overage_cost' => round($overageCost, 4),
        ];
    }

    // Include zero-usage resources from plan
    foreach ($limitMap as $type => $limit) {
        if (!isset($resources[$type])) {
            $resources[$type] = [
                'used' => 0,
                'limit' => ($limit < 0) ? -1 : $limit,
                'unlimited' => $limit < 0,
                'percentage' => 0,
                'overage' => 0,
                'overage_rate' => OVERAGE_RATES[$type] ?? 0,
                'overage_cost' => 0,
            ];
        }
    }

    // Get Stripe subscription info for period
    $subscriptionId = $settings['stripe_subscription_id'] ?? null;
    $stripePeriod = null;
    if ($subscriptionId) {
        try {
            $sub = \Stripe\Subscription::retrieve($subscriptionId);
            $stripePeriod = [
                'start' => date('c', $sub->current_period_start),
                'end' => date('c', $sub->current_period_end),
            ];
        } catch (\Exception $e) {
            error_log("Stripe usage-summary: subscription retrieval failed: " . $e->getMessage());
        }
    }

    jsonResponse([
        'success' => true,
        'plan' => $plan,
        'plan_name' => $planConfig['name'],
        'billing_period' => [
            'month' => $period,
            'start' => $periodStart,
            'end' => $periodEnd,
        ],
        'stripe_period' => $stripePeriod,
        'resources' => $resources,
        'total_overage_cost' => round($totalOverageCost, 2),
        'overage_rates' => OVERAGE_RATES,
    ]);
}

/**
 * POST switch-plan — Upgrade/downgrade with proration
 */
function switchPlan() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $newPlanKey = sanitize($input['plan'] ?? '', 30);
    $billingPeriod = sanitize($input['billing_period'] ?? 'monthly', 10);
    $isAnnual = in_array($billingPeriod, ['annual', 'year', 'yearly']);

    if (!isset(PLANS[$newPlanKey])) {
        $validPlans = implode(', ', array_keys(PLANS));
        jsonResponse(['error' => "Invalid plan. Valid: $validPlans"], 400);
    }

    if ($newPlanKey === 'custom') {
        jsonResponse(['success' => true, 'redirect' => SITE_URL . '/enterprise.php?contact=sales', 'message' => 'Please contact sales for custom pricing'], 200);
        return;
    }

    $clientId = getClientId();
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database unavailable'], 500);
    }

    // Get current subscription
    $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $prefs = $stmt->fetch();
    $settings = ($prefs && $prefs['notification_settings']) ? (json_decode($prefs['notification_settings'], true) ?: []) : [];
    $currentPlan = $settings['plan'] ?? 'free';
    $subscriptionId = $settings['stripe_subscription_id'] ?? null;

    if ($currentPlan === $newPlanKey) {
        jsonResponse(['error' => 'You are already on the ' . (PLANS[$newPlanKey]['name'] ?? $newPlanKey) . ' plan'], 400);
    }

    // Downgrade to free — cancel subscription
    if ($newPlanKey === 'free') {
        if ($subscriptionId) {
            try {
                $sub = \Stripe\Subscription::retrieve($subscriptionId);
                $sub->cancel();
            } catch (\Exception $e) {
                error_log("Stripe switch-plan: cancel failed: " . $e->getMessage());
            }
        }
        storePlanInfo($clientId, 'free', null, 'canceled');
        jsonResponse([
            'success' => true,
            'message' => 'Downgraded to Free plan',
            'plan' => 'free',
        ]);
        return;
    }

    // Upgrading from free (no existing subscription) — redirect to checkout
    if (!$subscriptionId || $currentPlan === 'free') {
        $customer = getOrCreateStripeCustomer($clientId);
        if (!$customer) {
            jsonResponse(['error' => 'Failed to create payment customer'], 500);
        }

        $price = getOrCreatePrice($newPlanKey, $isAnnual);
        if (!$price) {
            jsonResponse(['error' => 'Failed to configure pricing'], 500);
        }

        try {
            $sessionParams = [
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [['price' => $price->id, 'quantity' => 1]],
                'mode' => 'subscription',
                'success_url' => SITE_URL . '/dashboard.php?subscription=success&plan=' . $newPlanKey . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => SITE_URL . '/dashboard.php?subscription=cancelled',
                'metadata' => [
                    'billing_client_id' => $clientId,
                    'alfred_plan' => $newPlanKey,
                    'billing_period' => $isAnnual ? 'annual' : 'monthly',
                ],
                'subscription_data' => [
                    'metadata' => [
                        'billing_client_id' => $clientId,
                        'alfred_plan' => $newPlanKey,
                        'billing_period' => $isAnnual ? 'annual' : 'monthly',
                    ],
                    'trial_period_days' => 14,
                ],
            ];

            $session = \Stripe\Checkout\Session::create($sessionParams);

            jsonResponse([
                'success' => true,
                'action' => 'checkout',
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            error_log("Stripe switch-plan checkout: " . $e->getMessage());
            jsonResponse(['error' => 'Failed to create checkout session'], 500);
        }
        return;
    }

    // Modify existing subscription with proration
    try {
        $sub = \Stripe\Subscription::retrieve($subscriptionId);

        if (!$sub || $sub->status === 'canceled') {
            // Subscription is gone — treat as new
            jsonResponse(['error' => 'No active subscription found. Please start a new checkout.', 'action' => 'new_checkout'], 400);
            return;
        }

        $newPrice = getOrCreatePrice($newPlanKey, $isAnnual);
        if (!$newPrice) {
            jsonResponse(['error' => 'Failed to configure pricing for new plan'], 500);
        }

        $currentItem = $sub->items->data[0] ?? null;
        if (!$currentItem) {
            jsonResponse(['error' => 'No subscription item found'], 500);
        }

        // Determine proration behavior
        $currentPlanConfig = PLANS[$currentPlan] ?? PLANS['free'];
        $newPlanConfig = PLANS[$newPlanKey];
        $isUpgrade = ($newPlanConfig['price'] > $currentPlanConfig['price']);

        $updated = \Stripe\Subscription::update($subscriptionId, [
            'items' => [[
                'id' => $currentItem->id,
                'price' => $newPrice->id,
            ]],
            'proration_behavior' => $isUpgrade ? 'always_invoice' : 'create_prorations',
            'metadata' => [
                'billing_client_id' => $clientId,
                'alfred_plan' => $newPlanKey,
                'billing_period' => $isAnnual ? 'annual' : 'monthly',
                'previous_plan' => $currentPlan,
                'switched_at' => date('c'),
            ],
        ]);

        // Update local records
        storePlanInfo($clientId, $newPlanKey, $subscriptionId, $updated->status);

        // Log activity
        try {
            $oldName = $currentPlanConfig['name'] ?? $currentPlan;
            $newName = $newPlanConfig['name'];
            $stmt = $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)");
            $stmt->execute([
                "Alfred Plan Switch: Client #$clientId from $oldName to $newName",
                "system",
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
        } catch (\Exception $e) {
            error_log("Stripe switch-plan: activity log failed: " . $e->getMessage());
        }

        jsonResponse([
            'success' => true,
            'action' => $isUpgrade ? 'upgraded' : 'downgraded',
            'message' => ($isUpgrade ? 'Upgraded' : 'Downgraded') . ' to ' . $newPlanConfig['name'],
            'plan' => $newPlanKey,
            'plan_name' => $newPlanConfig['name'],
            'status' => $updated->status,
            'prorated' => true,
        ]);
    } catch (\Exception $e) {
        error_log("Stripe switch-plan: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to switch plan. Please contact support.'], 500);
    }
}
