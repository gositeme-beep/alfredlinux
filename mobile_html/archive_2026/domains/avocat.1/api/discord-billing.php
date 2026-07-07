<?php
/**
 * Discord → Stripe Billing Bridge
 * Generates checkout sessions for Discord users to subscribe to Alfred plans.
 * Usage: GET /api/discord-billing.php?plan=starter&discord_id=123&discord_name=User
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$plans = [
    'starter'         => ['name' => 'Alfred Starter',         'price' => 399,   'display' => '$3.99/mo'],
    'professional'    => ['name' => 'Alfred Professional',    'price' => 999,   'display' => '$9.99/mo'],
    'enterprise'      => ['name' => 'Alfred Enterprise',      'price' => 2499,  'display' => '$24.99/mo'],
    'enterprise_plus' => ['name' => 'Alfred Enterprise Plus', 'price' => 9900,  'display' => '$99/mo'],
];

$planKey     = preg_replace('/[^a-z_]/', '', $_GET['plan'] ?? '');
$discordId   = preg_replace('/[^0-9]/', '', $_GET['discord_id'] ?? '');
$discordName = substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET['discord_name'] ?? 'DiscordUser'), 0, 50);

if (!$planKey || !isset($plans[$planKey])) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html><html><head><title>Alfred Plans</title><style>body{font-family:system-ui;max-width:600px;margin:50px auto;padding:20px;background:#1a1a2e;color:#eee}h1{color:#7289da}.plan{background:#16213e;border:1px solid #333;border-radius:12px;padding:20px;margin:15px 0}.plan h3{color:#f39c12;margin:0}.plan .price{font-size:1.5em;color:#2ecc71}.plan a{display:inline-block;background:#5865f2;color:#fff;padding:10px 25px;border-radius:8px;text-decoration:none;margin-top:10px}.plan a:hover{background:#4752c4}</style></head><body>';
    echo '<h1>⚡ Alfred Premium Plans</h1><p>Choose a plan to unlock premium Discord bot features:</p>';
    foreach ($plans as $key => $p) {
        $url = htmlspecialchars("?plan=$key&discord_id=$discordId&discord_name=" . urlencode($discordName));
        echo "<div class=\"plan\"><h3>{$p['name']}</h3><div class=\"price\">{$p['display']}</div><a href=\"$url\">Subscribe Now →</a></div>";
    }
    echo '<p style="color:#888;margin-top:30px">14-day free trial on all plans. Cancel anytime.</p></body></html>';
    exit;
}

if (!$discordId) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html><html><body><h1>Error</h1><p>Missing Discord user ID. Please use the /deploy billing command in Discord.</p></body></html>';
    exit;
}

// Find or create Stripe customer for this Discord user
try {
    $db = new PDO(
        'mysql:host=' . getenv('GOSITEME_DB_HOST') . ';dbname=' . getenv('GOSITEME_DB_NAME') . ';charset=utf8mb4',
        getenv('GOSITEME_DB_USER'),
        getenv('GOSITEME_DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $db->exec("CREATE TABLE IF NOT EXISTS discord_stripe_customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL UNIQUE,
        discord_name VARCHAR(50),
        stripe_customer_id VARCHAR(100),
        plan VARCHAR(50) DEFAULT 'free',
        subscription_id VARCHAR(100),
        subscription_status VARCHAR(30) DEFAULT 'none',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_stripe (stripe_customer_id)
    )");

    $stmt = $db->prepare("SELECT stripe_customer_id FROM discord_stripe_customers WHERE discord_id = ?");
    $stmt->execute([$discordId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['stripe_customer_id']) {
        $customerId = $row['stripe_customer_id'];
    } else {
        $customer = \Stripe\Customer::create([
            'name' => $discordName,
            'metadata' => [
                'discord_id' => $discordId,
                'discord_name' => $discordName,
                'source' => 'discord_bot',
            ],
        ]);
        $customerId = $customer->id;

        $stmt = $db->prepare("INSERT INTO discord_stripe_customers (discord_id, discord_name, stripe_customer_id) 
                              VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE stripe_customer_id = ?, discord_name = ?");
        $stmt->execute([$discordId, $discordName, $customerId, $customerId, $discordName]);
    }

    // Create Stripe price if needed
    $plan = $plans[$planKey];
    $product = \Stripe\Product::create([
        'name' => $plan['name'] . ' (Discord)',
        'metadata' => ['alfred_plan' => $planKey, 'source' => 'discord'],
    ]);

    $price = \Stripe\Price::create([
        'product' => $product->id,
        'unit_amount' => $plan['price'],
        'currency' => 'usd',
        'recurring' => ['interval' => 'month'],
    ]);

    // Create checkout session
    $session = \Stripe\Checkout\Session::create([
        'customer' => $customerId,
        'payment_method_types' => ['card'],
        'line_items' => [['price' => $price->id, 'quantity' => 1]],
        'mode' => 'subscription',
        'success_url' => SITE_URL . '/api/discord-billing.php?success=1&discord_id=' . $discordId . '&plan=' . $planKey,
        'cancel_url'  => SITE_URL . '/api/discord-billing.php?cancelled=1',
        'subscription_data' => [
            'trial_period_days' => 14,
            'metadata' => [
                'discord_id' => $discordId,
                'discord_name' => $discordName,
                'alfred_plan' => $planKey,
                'source' => 'discord_bot',
            ],
        ],
        'metadata' => [
            'discord_id' => $discordId,
            'alfred_plan' => $planKey,
        ],
    ]);

    // Redirect to Stripe checkout
    header('Location: ' . $session->url);
    exit;

} catch (\Exception $e) {
    error_log("Discord billing error: " . $e->getMessage());
    header('Content-Type: text/html');
    echo '<!DOCTYPE html><html><body style="font-family:system-ui;max-width:600px;margin:50px auto;padding:20px;background:#1a1a2e;color:#eee">';
    echo '<h1 style="color:#e74c3c">⚠️ Billing Error</h1>';
    echo '<p>Something went wrong setting up your subscription. Please try again or contact support.</p>';
    echo '<a href="https://gositeme.com/contact" style="color:#5865f2">Contact Support</a>';
    echo '</body></html>';
    exit;
}
