<?php
/**
 * Discord Billing — Stripe Checkout for Discord AI Plans
 * URL: /discord-billing?uid=DISCORD_ID&plan=starter|pro|enterprise
 */
session_start();

// Load environment + Stripe
require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/pay/vendor/autoload.php';

// Load .env.php for Stripe keys
if (file_exists(dirname(__DIR__) . '/.env.php')) {
    require_once dirname(__DIR__) . '/.env.php';
}

$stripeSecretKey  = getenv('STRIPE_SECRET_KEY') ?: '';
$stripePubKey     = getenv('STRIPE_PUBLISHABLE_KEY') ?: '';

// Discord plan definitions
$discordPlans = [
    'starter'    => ['name' => 'Starter',    'price' => 399,  'display' => '$3.99/mo',  'msgs' => '200/day',  'features' => ['200 messages/day', 'Deep research', 'All tools', 'Priority queue']],
    'pro'        => ['name' => 'Pro',        'price' => 999,  'display' => '$9.99/mo',  'msgs' => 'Unlimited', 'features' => ['Unlimited messages', 'Priority responses', 'Deep research', 'All tools', 'Voice support']],
    'enterprise' => ['name' => 'Enterprise', 'price' => 2499, 'display' => '$24.99/mo', 'msgs' => 'Unlimited', 'features' => ['Unlimited messages', 'Priority responses', 'Custom AI agents', 'API access', 'Voice support', 'Dedicated support']],
];

// ─── Handle checkout creation (AJAX POST) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    header('Content-Type: application/json');

    if (empty($stripeSecretKey)) {
        echo json_encode(['error' => 'Payment system not configured']);
        exit;
    }

    $plan = $_POST['plan'] ?? '';
    $discordId = preg_replace('/[^0-9]/', '', $_POST['discord_id'] ?? '');

    if (!isset($discordPlans[$plan])) {
        echo json_encode(['error' => 'Invalid plan']);
        exit;
    }
    if (strlen($discordId) < 15 || strlen($discordId) > 22) {
        echo json_encode(['error' => 'Invalid Discord ID']);
        exit;
    }

    // Rate limit: 5 checkout attempts per Discord ID per hour
    $rateLimitKey = 'discord_checkout_' . $discordId;
    session_start();
    $now = time();
    $attempts = $_SESSION[$rateLimitKey] ?? [];
    $attempts = array_filter($attempts, fn($t) => $t > $now - 3600);
    if (count($attempts) >= 5) {
        echo json_encode(['error' => 'Too many attempts. Try again later.']);
        exit;
    }
    $attempts[] = $now;
    $_SESSION[$rateLimitKey] = $attempts;

    \Stripe\Stripe::setApiKey($stripeSecretKey);

    try {
        // Check if Discord user already has a Stripe customer
        $db = new PDO(
            'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
            GOSITEME_DB_USER,
            GOSITEME_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $stmt = $db->prepare("SELECT stripe_customer_id, username FROM discord_users WHERE discord_id = ?");
        $stmt->execute([$discordId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $customerId = null;
        if ($user && !empty($user['stripe_customer_id'])) {
            // Verify customer still exists in Stripe
            try {
                $customer = \Stripe\Customer::retrieve($user['stripe_customer_id']);
                if (!$customer->deleted) {
                    $customerId = $customer->id;
                }
            } catch (\Exception $e) {
                // Customer doesn't exist, will create new one
            }
        }

        if (!$customerId) {
            $customer = \Stripe\Customer::create([
                'metadata' => [
                    'discord_id' => $discordId,
                    'discord_username' => $user['username'] ?? 'Unknown',
                    'source' => 'discord-billing',
                ],
            ]);
            $customerId = $customer->id;

            // Store Stripe customer ID
            if ($user) {
                $stmt = $db->prepare("UPDATE discord_users SET stripe_customer_id = ? WHERE discord_id = ?");
                $stmt->execute([$customerId, $discordId]);
            }
        }

        // Get or create Stripe price for this Discord plan
        $planDef = $discordPlans[$plan];
        $priceId = null;

        // Check if we have a stored price ID in discord_plans
        $stmt = $db->prepare("SELECT stripe_price_id FROM discord_plans WHERE plan_key = ?");
        $stmt->execute([$plan]);
        $storedPrice = $stmt->fetchColumn();

        if ($storedPrice) {
            $priceId = $storedPrice;
        } else {
            // Create product + price in Stripe
            $product = \Stripe\Product::create([
                'name' => "Alfred Discord — {$planDef['name']}",
                'description' => "Discord AI Assistant — {$planDef['name']} Plan ({$planDef['msgs']} messages)",
                'metadata' => ['discord_plan' => $plan],
            ]);

            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => $planDef['price'],
                'currency' => 'usd',
                'recurring' => ['interval' => 'month'],
                'metadata' => ['discord_plan' => $plan],
            ]);

            $priceId = $price->id;

            // Store for reuse
            $stmt = $db->prepare("UPDATE discord_plans SET stripe_price_id = ? WHERE plan_key = ?");
            $stmt->execute([$priceId, $plan]);
        }

        // Create Checkout Session
        $session = \Stripe\Checkout\Session::create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'mode' => 'subscription',
            'success_url' => 'https://root.com/discord-billing?success=1&plan=' . urlencode($plan) . '&uid=' . urlencode($discordId),
            'cancel_url' => 'https://root.com/discord-billing?cancelled=1&plan=' . urlencode($plan) . '&uid=' . urlencode($discordId),
            'metadata' => [
                'discord_id' => $discordId,
                'discord_plan' => $plan,
                'source' => 'discord-billing',
            ],
            'subscription_data' => [
                'metadata' => [
                    'discord_id' => $discordId,
                    'discord_plan' => $plan,
                ],
                'trial_period_days' => 7,
            ],
        ]);

        echo json_encode(['url' => $session->url]);
    } catch (\Exception $e) {
        error_log("Discord billing error: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to create checkout session. Please try again.']);
    }
    exit;
}

// ─── Frontend ──────────────────────────────────────────────────────────────
$selectedPlan = preg_replace('/[^a-z]/', '', $_GET['plan'] ?? '');
$discordUid   = preg_replace('/[^0-9]/', '', $_GET['uid'] ?? '');
$isSuccess    = isset($_GET['success']);
$isCancelled  = isset($_GET['cancelled']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred Discord — Upgrade Plan | GoSiteMe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f23;
            color: #e0e0ff;
            min-height: 100vh;
        }
        .hero {
            text-align: center;
            padding: 60px 20px 40px;
            background: linear-gradient(135deg, #1a1a3e 0%, #2d1b69 50%, #1a1a3e 100%);
        }
        .hero h1 { font-size: 2.5rem; color: #7B61FF; margin-bottom: 10px; }
        .hero p { font-size: 1.1rem; color: #a0a0cc; max-width: 600px; margin: 0 auto; }
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            max-width: 1000px;
            margin: -20px auto 60px;
            padding: 0 20px;
        }
        .plan-card {
            background: #1a1a3e;
            border: 2px solid #2a2a5e;
            border-radius: 16px;
            padding: 32px;
            position: relative;
            transition: transform 0.2s, border-color 0.2s;
        }
        .plan-card:hover { transform: translateY(-4px); border-color: #7B61FF; }
        .plan-card.selected { border-color: #7B61FF; box-shadow: 0 0 30px rgba(123,97,255,0.3); }
        .plan-card.popular::before {
            content: 'MOST POPULAR';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #7B61FF;
            color: #fff;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .plan-name { font-size: 1.4rem; font-weight: 700; margin-bottom: 8px; }
        .plan-price { font-size: 2rem; font-weight: 800; color: #7B61FF; margin-bottom: 4px; }
        .plan-msgs { color: #a0a0cc; margin-bottom: 20px; font-size: 0.95rem; }
        .plan-features { list-style: none; margin-bottom: 24px; }
        .plan-features li {
            padding: 6px 0;
            padding-left: 28px;
            position: relative;
            color: #c0c0ee;
        }
        .plan-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #7B61FF;
            font-weight: 700;
        }
        .btn-subscribe {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #7B61FF, #5b3fd9);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-subscribe:hover { opacity: 0.9; }
        .btn-subscribe:disabled { opacity: 0.5; cursor: not-allowed; }
        .discord-id-input {
            width: 100%;
            padding: 12px;
            background: #0f0f23;
            border: 2px solid #2a2a5e;
            border-radius: 8px;
            color: #e0e0ff;
            font-size: 1rem;
            margin-bottom: 16px;
            text-align: center;
        }
        .discord-id-input:focus { outline: none; border-color: #7B61FF; }
        .discord-id-input::placeholder { color: #555; }
        .id-label { font-size: 0.85rem; color: #888; margin-bottom: 8px; display: block; text-align: center; }
        .alert {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-size: 1.1rem;
        }
        .alert-success { background: #1a3e1a; border: 2px solid #2f7a2f; color: #8fef8f; }
        .alert-cancel { background: #3e1a1a; border: 2px solid #7a2f2f; color: #ef8f8f; }
        .footer {
            text-align: center;
            padding: 40px 20px;
            color: #555;
            font-size: 0.85rem;
        }
        .footer a { color: #7B61FF; text-decoration: none; }
        .trial-badge {
            display: inline-block;
            background: #2f7a2f;
            color: #8fef8f;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .error-msg { color: #ef8f8f; font-size: 0.85rem; text-align: center; margin-top: 8px; display: none; }
        @media (max-width: 768px) {
            .hero h1 { font-size: 1.8rem; }
            .plans-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="hero">
    <h1>💎 Upgrade Your Alfred Plan</h1>
    <p>Unlock unlimited AI conversations, priority responses, and premium features on Discord.</p>
</div>

<?php if ($isSuccess): ?>
<div class="alert alert-success">
    ✅ <strong>Subscription activated!</strong><br>
    Head back to Discord and type <code>/account</code> to see your new plan. It may take a minute to update.
</div>
<?php endif; ?>

<?php if ($isCancelled): ?>
<div class="alert alert-cancel">
    ❌ <strong>Checkout cancelled.</strong> No charges were made. You can try again below.
</div>
<?php endif; ?>

<div class="plans-grid">
<?php foreach ($discordPlans as $key => $plan): ?>
    <div class="plan-card<?= $key === 'pro' ? ' popular' : '' ?><?= $key === $selectedPlan ? ' selected' : '' ?>" data-plan="<?= $key ?>">
        <div class="plan-name"><?= $key === 'starter' ? '⭐' : ($key === 'pro' ? '🔮' : '👑') ?> <?= htmlspecialchars($plan['name']) ?></div>
        <div class="plan-price"><?= htmlspecialchars($plan['display']) ?></div>
        <div class="plan-msgs"><?= htmlspecialchars($plan['msgs']) ?> messages</div>
        <div class="trial-badge">7-day free trial</div>
        <ul class="plan-features">
            <?php foreach ($plan['features'] as $feature): ?>
            <li><?= htmlspecialchars($feature) ?></li>
            <?php endforeach; ?>
        </ul>
        <label class="id-label">Your Discord User ID</label>
        <input type="text" class="discord-id-input" placeholder="e.g. 437853620197654530"
               value="<?= htmlspecialchars($discordUid) ?>"
               data-plan="<?= $key ?>" maxlength="22" inputmode="numeric">
        <button class="btn-subscribe" data-plan="<?= $key ?>" onclick="subscribe('<?= $key ?>')">
            Subscribe — <?= htmlspecialchars($plan['display']) ?>
        </button>
        <div class="error-msg" id="error-<?= $key ?>"></div>
    </div>
<?php endforeach; ?>
</div>

<div class="footer">
    <p>Powered by <a href="https://root.com">GoSiteMe</a> • Payments secured by Stripe</p>
    <p style="margin-top:8px;">Need help? DM Alfred on <a href="https://discord.gg/root">Discord</a></p>
    <p style="margin-top:16px; font-size:0.75rem;">
        Don't know your Discord ID? Open Discord → Settings → Advanced → Enable Developer Mode → Right-click your name → Copy User ID
    </p>
</div>

<script>
async function subscribe(plan) {
    const card = document.querySelector(`.plan-card[data-plan="${plan}"]`);
    const input = card.querySelector('.discord-id-input');
    const btn = card.querySelector('.btn-subscribe');
    const errorEl = document.getElementById(`error-${plan}`);
    const discordId = input.value.trim();

    errorEl.style.display = 'none';

    if (!discordId || discordId.length < 15 || !/^\d+$/.test(discordId)) {
        errorEl.textContent = 'Please enter a valid Discord User ID (15-22 digits)';
        errorEl.style.display = 'block';
        input.focus();
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Creating checkout...';

    try {
        const formData = new URLSearchParams();
        formData.append('action', 'checkout');
        formData.append('plan', plan);
        formData.append('discord_id', discordId);

        const resp = await fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        });

        const data = await resp.json();
        if (data.url) {
            window.location.href = data.url;
        } else {
            errorEl.textContent = data.error || 'Something went wrong. Please try again.';
            errorEl.style.display = 'block';
        }
    } catch (e) {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = `Subscribe — ${card.querySelector('.plan-price').textContent}`;
    }
}

// Sync Discord ID across all cards
document.querySelectorAll('.discord-id-input').forEach(input => {
    input.addEventListener('input', () => {
        const val = input.value;
        document.querySelectorAll('.discord-id-input').forEach(other => {
            if (other !== input) other.value = val;
        });
    });
});
</script>

</body>
</html>
