<?php
/**
 * Affiliate Program API
 * Handles affiliate registration, tracking, payouts, and management
 * 
 * Actions: register, get_stats, get_referrals, get_link, track_click,
 *          track_signup, track_conversion, update_payout, request_payout,
 *          get_payouts, get_assets, update_tier
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
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

// ── Commission Tier Configuration ──────────────────────────────────────────
define('AFFILIATE_TIERS', [
    'bronze' => [
        'label'       => 'Bronze',
        'min_refs'    => 1,
        'max_refs'    => 10,
        'commission'  => 0.20,
        'cookie_days' => 30,
        'min_payout'  => 50.00,
        'color'       => '#cd7f32',
    ],
    'silver' => [
        'label'       => 'Silver',
        'min_refs'    => 11,
        'max_refs'    => 50,
        'commission'  => 0.25,
        'cookie_days' => 60,
        'min_payout'  => 25.00,
        'color'       => '#c0c0c0',
    ],
    'gold' => [
        'label'       => 'Gold',
        'min_refs'    => 51,
        'max_refs'    => 999999,
        'commission'  => 0.30,
        'cookie_days' => 90,
        'min_payout'  => 10.00,
        'color'       => '#ffd700',
    ],
]);

// ── Route Action ───────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'register':       affiliateRegister(); break;
    case 'get_stats':      getStats();          break;
    case 'get_referrals':  getReferrals();      break;
    case 'get_link':       getLink();           break;
    case 'track_click':    trackClick();        break;
    case 'track_signup':   trackSignup();       break;
    case 'track_conversion': trackConversion(); break;
    case 'update_payout':  updatePayout();      break;
    case 'request_payout': requestPayout();     break;
    case 'get_payouts':    getPayouts();        break;
    case 'get_assets':     getAssets();         break;
    case 'update_tier':    updateTier();        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Require authenticated session and return client_id
 */
function requireAuth() {
    $uid = $_SESSION['uid'] ?? $_SESSION['client_id'] ?? null;
    if (!$uid) {
        jsonResponse(['error' => 'Authentication required', 'login_url' => SITE_URL . '/api/auth.php'], 401);
    }
    return (int) $uid;
}

/**
 * Get affiliate row by client_id — null if not registered
 */
function getAffiliateByClient(int $clientId) {
    $db = getDB();
    if (!$db) return null;
    $stmt = $db->prepare("SELECT * FROM alfred_affiliates WHERE client_id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    return $stmt->fetch() ?: null;
}

/**
 * Get affiliate by partner_id
 */
function getAffiliateByPartner(string $partnerId) {
    $db = getDB();
    if (!$db) return null;
    $stmt = $db->prepare("SELECT * FROM alfred_affiliates WHERE partner_id = ? LIMIT 1");
    $stmt->execute([$partnerId]);
    return $stmt->fetch() ?: null;
}

/**
 * Generate unique partner ID: AF-XXXX (4-6 alphanumeric)
 */
function generatePartnerId(): string {
    $db = getDB();
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I,O,0,1 for readability
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $len = ($attempt < 20) ? 4 : 6;
        $id  = 'AF-';
        for ($i = 0; $i < $len; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_affiliates WHERE partner_id = ?");
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() === 0) return $id;
    }
    return 'AF-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Determine tier key from total referral count
 */
function determineTier(int $refCount): string {
    if ($refCount >= 51) return 'gold';
    if ($refCount >= 11) return 'silver';
    return 'bronze';
}

/**
 * Get commission rate for a tier key
 */
function getCommissionRate(string $tier): float {
    return AFFILIATE_TIERS[$tier]['commission'] ?? 0.20;
}

// ── Actions ────────────────────────────────────────────────────────────────

/**
 * 1. Register as affiliate
 */
function affiliateRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $clientId = requireAuth();
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    // Check if already registered
    $existing = getAffiliateByClient($clientId);
    if ($existing) {
        jsonResponse([
            'success'    => true,
            'message'    => 'Already registered',
            'partner_id' => $existing['partner_id'],
            'tier'       => $existing['tier'],
        ]);
    }

    $partnerId = generatePartnerId();

    // Get client name/email from session
    $name  = sanitize($_SESSION['client_name'] ?? $_SESSION['username'] ?? ($_POST['name'] ?? ''), 200);
    $email = sanitize($_SESSION['client_email'] ?? ($_POST['email'] ?? ''), 200);

    // Payout method defaults
    $payoutMethod  = 'paypal';
    $payoutDetails = '{}';

    $stmt = $db->prepare("
        INSERT INTO alfred_affiliates 
            (client_id, user_id, partner_id, name, email, tier, commission_rate, payout_method, payout_details, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'bronze', 0.20, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$clientId, $clientId, $partnerId, $name, $email, $payoutMethod, $payoutDetails]);

    jsonResponse([
        'success'     => true,
        'partner_id'  => $partnerId,
        'tier'        => 'bronze',
        'referral_link' => SITE_URL . '/?ref=' . $partnerId,
        'message'     => 'Welcome to the affiliate program!',
    ]);
}

/**
 * 2. Get affiliate stats
 */
function getStats() {
    $clientId  = requireAuth();
    $affiliate = getAffiliateByClient($clientId);
    if (!$affiliate) jsonResponse(['error' => 'Not registered as affiliate', 'register' => true], 404);

    $db = getDB();
    $pid = $affiliate['partner_id'];

    // Total referrals
    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_referrals WHERE partner_id = ?");
    $stmt->execute([$pid]);
    $totalReferrals = (int) $stmt->fetchColumn();

    // Active referrals (converted, not churned)
    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_referrals WHERE partner_id = ? AND status = 'converted'");
    $stmt->execute([$pid]);
    $activeReferrals = (int) $stmt->fetchColumn();

    // Total revenue
    $stmt = $db->prepare("SELECT COALESCE(SUM(revenue), 0) FROM alfred_referrals WHERE partner_id = ? AND status IN ('converted','churned')");
    $stmt->execute([$pid]);
    $totalRevenue = (float) $stmt->fetchColumn();

    // Total commission earned
    $stmt = $db->prepare("SELECT COALESCE(SUM(commission), 0) FROM alfred_referrals WHERE partner_id = ?");
    $stmt->execute([$pid]);
    $totalCommission = (float) $stmt->fetchColumn();

    // Pending payout (commission earned minus paid out)
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM alfred_affiliate_payouts WHERE partner_id = ? AND status = 'completed'");
    $stmt->execute([$pid]);
    $totalPaid = (float) $stmt->fetchColumn();
    $pendingPayout = max(0, $totalCommission - $totalPaid);

    // Clicks in last 30 days
    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_referrals WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([$pid]);
    $clicksLast30 = (int) $stmt->fetchColumn();

    // Signups in last 30 days
    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_referrals WHERE partner_id = ? AND status IN ('signed_up','converted','churned') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([$pid]);
    $signupsLast30 = (int) $stmt->fetchColumn();

    // Conversions in last 30 days
    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_referrals WHERE partner_id = ? AND status IN ('converted') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([$pid]);
    $conversionsLast30 = (int) $stmt->fetchColumn();

    // Daily stats for chart (last 30 days)
    $stmt = $db->prepare("
        SELECT DATE(created_at) AS day,
               COUNT(*) AS clicks,
               SUM(CASE WHEN status IN ('signed_up','converted','churned') THEN 1 ELSE 0 END) AS signups,
               SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) AS conversions
        FROM alfred_referrals
        WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $stmt->execute([$pid]);
    $dailyStats = $stmt->fetchAll();

    $tierInfo = AFFILIATE_TIERS[$affiliate['tier']] ?? AFFILIATE_TIERS['bronze'];

    jsonResponse([
        'success' => true,
        'partner_id'      => $pid,
        'tier'            => $affiliate['tier'],
        'tier_info'       => $tierInfo,
        'total_referrals' => $totalReferrals,
        'active_referrals'=> $activeReferrals,
        'total_revenue'   => round($totalRevenue, 2),
        'total_commission'=> round($totalCommission, 2),
        'pending_payout'  => round($pendingPayout, 2),
        'clicks_30d'      => $clicksLast30,
        'signups_30d'     => $signupsLast30,
        'conversions_30d' => $conversionsLast30,
        'daily_stats'     => $dailyStats,
        'referral_link'   => SITE_URL . '/?ref=' . $pid,
    ]);
}

/**
 * 3. Get all referrals
 */
function getReferrals() {
    $clientId  = requireAuth();
    $affiliate = getAffiliateByClient($clientId);
    if (!$affiliate) jsonResponse(['error' => 'Not registered as affiliate'], 404);

    $db  = getDB();
    $pid = $affiliate['partner_id'];

    $page  = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(100, max(10, (int) ($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;
    $search = sanitize($_GET['search'] ?? '', 100);
    $statusFilter = sanitize($_GET['status'] ?? '', 20);

    $where  = "WHERE partner_id = ?";
    $params = [$pid];

    if ($statusFilter && in_array($statusFilter, ['clicked', 'signed_up', 'converted', 'churned'])) {
        $where .= " AND status = ?";
        $params[] = $statusFilter;
    }
    if ($search) {
        $where .= " AND (visitor_ip LIKE ? OR referral_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_referrals $where");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT referral_id, partner_id, status, revenue, commission, visitor_ip, user_agent, created_at, updated_at
        FROM alfred_referrals $where
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $referrals = $stmt->fetchAll();

    // Mask IPs for privacy
    foreach ($referrals as &$r) {
        if (!empty($r['visitor_ip'])) {
            $parts = explode('.', $r['visitor_ip']);
            $r['visitor_ip'] = ($parts[0] ?? '***') . '.' . ($parts[1] ?? '***') . '.***.' . '***';
        }
        unset($r['user_agent']);
    }

    jsonResponse([
        'success'   => true,
        'referrals' => $referrals,
        'total'     => $total,
        'page'      => $page,
        'pages'     => ceil($total / $limit),
    ]);
}

/**
 * 4. Get formatted referral link
 */
function getLink() {
    $clientId  = requireAuth();
    $affiliate = getAffiliateByClient($clientId);
    if (!$affiliate) jsonResponse(['error' => 'Not registered as affiliate'], 404);

    jsonResponse([
        'success'       => true,
        'partner_id'    => $affiliate['partner_id'],
        'referral_link' => SITE_URL . '/?ref=' . $affiliate['partner_id'],
        'qr_data'       => SITE_URL . '/?ref=' . $affiliate['partner_id'],
    ]);
}

/**
 * 5. Track a referral click (called by middleware on ?ref= param)
 */
function trackClick() {
    $partnerId = strtoupper(sanitize($_GET['partner_id'] ?? $_POST['partner_id'] ?? '', 12));
    if (!preg_match('/^AF-[A-Z0-9]{4,8}$/', $partnerId)) {
        jsonResponse(['error' => 'Invalid partner ID'], 400);
    }

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    // Verify affiliate exists
    $aff = getAffiliateByPartner($partnerId);
    if (!$aff || $aff['status'] !== 'active') {
        jsonResponse(['error' => 'Affiliate not found or inactive'], 404);
    }

    // Deduplicate: one click per IP per partner per hour
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM alfred_referrals 
        WHERE partner_id = ? AND visitor_ip = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$partnerId, $ip]);
    if ((int) $stmt->fetchColumn() > 0) {
        jsonResponse(['success' => true, 'message' => 'Click already recorded']);
    }

    $stmt = $db->prepare("
        INSERT INTO alfred_referrals (partner_id, status, visitor_ip, user_agent, created_at, updated_at)
        VALUES (?, 'clicked', ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$partnerId, $ip, $ua]);

    // Update total clicks on affiliate
    $db->prepare("UPDATE alfred_affiliates SET total_clicks = total_clicks + 1 WHERE partner_id = ?")->execute([$partnerId]);

    jsonResponse(['success' => true, 'message' => 'Click tracked']);
}

/**
 * 6. Track signup attribution (called by auth system)
 */
function trackSignup() {
    $partnerId = strtoupper(sanitize($_POST['partner_id'] ?? $_GET['partner_id'] ?? '', 12));
    $refClientId = (int) ($_POST['referred_client_id'] ?? $_GET['referred_client_id'] ?? 0);

    if (!$partnerId || !$refClientId) {
        jsonResponse(['error' => 'partner_id and referred_client_id required'], 400);
    }

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    // Find latest click for this partner from this session/IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Try to update an existing 'clicked' referral to 'signed_up'
    $stmt = $db->prepare("
        UPDATE alfred_referrals 
        SET status = 'signed_up', referred_client_id = ?, updated_at = NOW()
        WHERE partner_id = ? AND status = 'clicked' AND visitor_ip = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$refClientId, $partnerId, $ip]);

    if ($stmt->rowCount() === 0) {
        // Create new referral record
        $stmt = $db->prepare("
            INSERT INTO alfred_referrals (partner_id, referred_client_id, status, visitor_ip, created_at, updated_at)
            VALUES (?, ?, 'signed_up', ?, NOW(), NOW())
        ");
        $stmt->execute([$partnerId, $refClientId, $ip]);
    }

    // Update signup count
    $db->prepare("UPDATE alfred_affiliates SET total_signups = total_signups + 1 WHERE partner_id = ?")->execute([$partnerId]);

    jsonResponse(['success' => true, 'message' => 'Signup attributed']);
}

/**
 * 7. Track conversion to paid (called by Stripe webhook or billing)
 */
function trackConversion() {
    $partnerId    = strtoupper(sanitize($_POST['partner_id'] ?? '', 12));
    $refClientId  = (int) ($_POST['referred_client_id'] ?? 0);
    $revenue      = (float) ($_POST['revenue'] ?? 0);

    if (!$partnerId || !$refClientId || $revenue <= 0) {
        jsonResponse(['error' => 'partner_id, referred_client_id, and revenue required'], 400);
    }

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $aff = getAffiliateByPartner($partnerId);
    if (!$aff) jsonResponse(['error' => 'Affiliate not found'], 404);

    $commissionRate = getCommissionRate($aff['tier']);
    $commission = round($revenue * $commissionRate, 2);

    // Update referral to converted
    $stmt = $db->prepare("
        UPDATE alfred_referrals 
        SET status = 'converted', revenue = revenue + ?, commission = commission + ?, updated_at = NOW()
        WHERE partner_id = ? AND referred_client_id = ? AND status IN ('signed_up','converted')
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$revenue, $commission, $partnerId, $refClientId]);

    if ($stmt->rowCount() === 0) {
        // Create a new referral if none found
        $stmt = $db->prepare("
            INSERT INTO alfred_referrals (partner_id, referred_client_id, status, revenue, commission, created_at, updated_at)
            VALUES (?, ?, 'converted', ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$partnerId, $refClientId, $revenue, $commission]);
    }

    // Update lifetime stats
    $db->prepare("
        UPDATE alfred_affiliates 
        SET total_conversions = total_conversions + 1, 
            total_revenue = total_revenue + ?,
            total_commission = total_commission + ?
        WHERE partner_id = ?
    ")->execute([$revenue, $commission, $partnerId]);

    // Auto-update tier
    autoUpdateTier($partnerId);

    jsonResponse(['success' => true, 'commission' => $commission, 'message' => 'Conversion tracked']);
}

/**
 * 8. Update payout method and details
 */
function updatePayout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $clientId  = requireAuth();
    $affiliate = getAffiliateByClient($clientId);
    if (!$affiliate) jsonResponse(['error' => 'Not registered as affiliate'], 404);

    $method  = sanitize($_POST['payout_method'] ?? '', 20);
    $details = sanitize($_POST['payout_details'] ?? '', 500);

    if (!in_array($method, ['stripe', 'paypal', 'bank'])) {
        jsonResponse(['error' => 'Invalid payout method. Use: stripe, paypal, or bank'], 400);
    }
    if (empty($details)) {
        jsonResponse(['error' => 'Payout details required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE alfred_affiliates SET payout_method = ?, payout_details = ? WHERE partner_id = ?");
    $stmt->execute([$method, $details, $affiliate['partner_id']]);

    jsonResponse(['success' => true, 'message' => 'Payout method updated']);
}

/**
 * 9. Request manual payout
 */
function requestPayout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $clientId  = requireAuth();
    $affiliate = getAffiliateByClient($clientId);
    if (!$affiliate) jsonResponse(['error' => 'Not registered as affiliate'], 404);

    $db = getDB();
    $pid = $affiliate['partner_id'];

    // Calculate available balance
    $stmt = $db->prepare("SELECT COALESCE(SUM(commission), 0) FROM alfred_referrals WHERE partner_id = ?");
    $stmt->execute([$pid]);
    $totalEarned = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM alfred_affiliate_payouts WHERE partner_id = ? AND status IN ('completed','pending')");
    $stmt->execute([$pid]);
    $totalPaid = (float) $stmt->fetchColumn();

    $balance = round($totalEarned - $totalPaid, 2);
    $tierInfo = AFFILIATE_TIERS[$affiliate['tier']] ?? AFFILIATE_TIERS['bronze'];
    $minPayout = $tierInfo['min_payout'];

    if ($balance < $minPayout) {
        jsonResponse([
            'error'       => "Minimum payout is \$$minPayout. Your balance is \$$balance.",
            'balance'     => $balance,
            'min_payout'  => $minPayout,
        ], 400);
    }

    if (empty($affiliate['payout_method']) || empty($affiliate['payout_details'])) {
        jsonResponse(['error' => 'Please set your payout method first'], 400);
    }

    // Check for pending payout
    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_affiliate_payouts WHERE partner_id = ? AND status = 'pending'");
    $stmt->execute([$pid]);
    if ((int) $stmt->fetchColumn() > 0) {
        jsonResponse(['error' => 'You already have a pending payout request'], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO alfred_affiliate_payouts (partner_id, amount, method, status, requested_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$pid, $balance, $affiliate['payout_method']]);

    jsonResponse([
        'success' => true,
        'amount'  => $balance,
        'method'  => $affiliate['payout_method'],
        'message' => "Payout of \$$balance requested. Processing within 5-7 business days.",
    ]);
}

/**
 * 10. Get payout history
 */
function getPayouts() {
    $clientId  = requireAuth();
    $affiliate = getAffiliateByClient($clientId);
    if (!$affiliate) jsonResponse(['error' => 'Not registered as affiliate'], 404);

    $db  = getDB();
    $pid = $affiliate['partner_id'];

    $stmt = $db->prepare("
        SELECT payout_id, amount, method, status, requested_at, processed_at, notes
        FROM alfred_affiliate_payouts
        WHERE partner_id = ?
        ORDER BY requested_at DESC
        LIMIT 100
    ");
    $stmt->execute([$pid]);
    $payouts = $stmt->fetchAll();

    // Balance
    $stmt = $db->prepare("SELECT COALESCE(SUM(commission), 0) FROM alfred_referrals WHERE partner_id = ?");
    $stmt->execute([$pid]);
    $totalEarned = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM alfred_affiliate_payouts WHERE partner_id = ? AND status IN ('completed','pending')");
    $stmt->execute([$pid]);
    $totalPaid = (float) $stmt->fetchColumn();

    jsonResponse([
        'success' => true,
        'payouts' => $payouts,
        'balance' => round($totalEarned - $totalPaid, 2),
        'total_earned' => round($totalEarned, 2),
        'total_paid'   => round($totalPaid, 2),
    ]);
}

/**
 * 11. Get marketing assets
 */
function getAssets() {
    $banners = [
        [
            'name'   => 'Leaderboard (728×90)',
            'url'    => SITE_URL . '/assets/img/affiliate/banner-728x90.png',
            'width'  => 728,
            'height' => 90,
        ],
        [
            'name'   => 'Medium Rectangle (300×250)',
            'url'    => SITE_URL . '/assets/img/affiliate/banner-300x250.png',
            'width'  => 300,
            'height' => 250,
        ],
        [
            'name'   => 'Skyscraper (160×600)',
            'url'    => SITE_URL . '/assets/img/affiliate/banner-160x600.png',
            'width'  => 160,
            'height' => 600,
        ],
        [
            'name'   => 'Mobile (320×50)',
            'url'    => SITE_URL . '/assets/img/affiliate/banner-320x50.png',
            'width'  => 320,
            'height' => 50,
        ],
    ];

    $socialPosts = [
        [
            'platform' => 'twitter',
            'text'     => "🚀 I've been using Alfred AI and it's incredible — 1,220+ AI tools in one platform. Try it free for 14 days! {link} #AI #AlfredAI #GoSiteMe",
        ],
        [
            'platform' => 'linkedin',
            'text'     => "Looking for an all-in-one AI platform? Alfred AI by GoSiteMe has 1,220+ tools across 29 categories — writing, coding, legal, marketing, and more. 14-day free trial, plans from \$3.99/mo.\n\nCheck it out: {link}",
        ],
        [
            'platform' => 'facebook',
            'text'     => "Just discovered Alfred AI — an incredible platform with 1,220+ AI tools! From writing to coding to legal help. Try it free for 14 days 🤖\n\n{link}",
        ],
    ];

    $emailTemplates = [
        [
            'name'    => 'Introduction Email',
            'subject' => 'Try Alfred AI — 1,220+ AI Tools in One Platform',
            'body'    => "Hi {name},\n\nI wanted to share something great with you — Alfred AI by GoSiteMe. It's an all-in-one AI platform with 1,220+ tools for writing, coding, legal, marketing, and more.\n\nWhat I love about it:\n• 1,220+ AI tools across 29 categories\n• Voice-first — talk to Alfred naturally\n• Plans starting at \$3.99/mo\n• 14-day free trial, no credit card required\n\nTry it here: {link}\n\nBest regards",
        ],
        [
            'name'    => 'Quick Share Email',
            'subject' => 'Check out this AI platform',
            'body'    => "Hey,\n\nHave you tried Alfred AI? 1,220+ AI tools, voice commands, and it starts at \$3.99/mo. Free 14-day trial.\n\n{link}\n\nWorth checking out!",
        ],
    ];

    jsonResponse([
        'success'         => true,
        'banners'         => $banners,
        'social_posts'    => $socialPosts,
        'email_templates' => $emailTemplates,
    ]);
}

/**
 * 12. Auto-promote affiliate tier based on performance
 */
function updateTier() {
    // Can be called by cron or manually
    $partnerId = sanitize($_POST['partner_id'] ?? $_GET['partner_id'] ?? '', 12);

    if ($partnerId) {
        autoUpdateTier(strtoupper($partnerId));
        jsonResponse(['success' => true, 'message' => 'Tier updated']);
    }

    // Bulk update all affiliates
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $stmt = $db->query("SELECT partner_id FROM alfred_affiliates WHERE status = 'active'");
    $affiliates = $stmt->fetchAll();
    $updated = 0;

    foreach ($affiliates as $aff) {
        if (autoUpdateTier($aff['partner_id'])) $updated++;
    }

    jsonResponse(['success' => true, 'updated' => $updated, 'total' => count($affiliates)]);
}

/**
 * Internal: auto-update tier for one affiliate
 */
function autoUpdateTier(string $partnerId): bool {
    $db = getDB();
    if (!$db) return false;

    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_referrals WHERE partner_id = ? AND status IN ('signed_up','converted')");
    $stmt->execute([$partnerId]);
    $refCount = (int) $stmt->fetchColumn();

    $newTier = determineTier($refCount);
    $newRate = getCommissionRate($newTier);

    $stmt = $db->prepare("UPDATE alfred_affiliates SET tier = ?, commission_rate = ? WHERE partner_id = ? AND tier != ?");
    $stmt->execute([$newTier, $newRate, $partnerId, $newTier]);

    return $stmt->rowCount() > 0;
}
