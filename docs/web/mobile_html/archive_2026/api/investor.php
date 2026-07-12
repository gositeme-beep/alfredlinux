<?php
/**
 * Investor API — Handles investor interest submissions and dashboard data
 * POST /api/investor.php          — Submit investment interest form
 * GET  /api/investor.php?action=dashboard  — Get investor dashboard data (authenticated)
 * GET  /api/investor.php?action=metrics    — Get public platform metrics
 */
define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // Stripe webhook signature verification
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// Stripe configuration — keys loaded from config.php via getenv()
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Initialize DB tables if needed
initInvestorTables();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    if ($action === 'admin_update') {
        handleAdminUpdate();
    } elseif ($action === 'create_checkout') {
        handleCreateCheckout();
    } elseif ($action === 'stripe_webhook') {
        handleStripeWebhook();
    } else {
        handleInvestorSubmission();
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? 'metrics';
    switch ($action) {
        case 'dashboard':
            handleDashboard();
            break;
        case 'metrics':
            handlePublicMetrics();
            break;
        case 'admin':
            handleAdminDashboard();
            break;
        case 'verify_payment':
            handleVerifyPayment();
            break;
        default:
            jsonResponse(['error' => 'Unknown action'], 400);
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Handle new investor interest submission
 */
function handleInvestorSubmission() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON input'], 400);
        return;
    }
    
    // Validate required fields
    $required = ['name', 'email', 'tier', 'amount'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            jsonResponse(['error' => "Missing required field: $field"], 400);
            return;
        }
    }
    
    // Validate email
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        jsonResponse(['error' => 'Invalid email address'], 400);
        return;
    }
    
    // Validate tier
    $validTiers = ['seed', 'growth', 'strategic'];
    $tier = $input['tier'];
    if (!in_array($tier, $validTiers)) {
        jsonResponse(['error' => 'Invalid investment tier'], 400);
        return;
    }
    
    // Validate amount
    $amount = floatval($input['amount']);
    if ($amount < 100) {
        jsonResponse(['error' => 'Minimum investment is $100'], 400);
        return;
    }
    
    // Sanitize inputs
    $name = sanitize($input['name']);
    $phone = sanitize($input['phone'] ?? '');
    $message = sanitize($input['message'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Rate limiting: max 3 submissions per email per day
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_investor_interests 
                          WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->execute([$email]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    if ($count >= 3) {
        jsonResponse(['error' => 'Too many submissions. Please try again tomorrow.'], 429);
        return;
    }
    
    // Generate a unique investor reference code
    $refCode = 'INV-' . strtoupper(substr(md5($email . time()), 0, 8));
    
    // Store in database
    $stmt = $db->prepare("INSERT INTO alfred_investor_interests 
                          (ref_code, name, email, phone, tier, amount, message, ip_address, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$refCode, $name, $email, $phone, $tier, $amount, $message, $ip]);
    
    // Send notification email to admin
    $adminSubject = "🚀 New Investor Interest: $name ($tier - \$$amount)";
    $adminBody = "New investment interest received:\n\n";
    $adminBody .= "Name: $name\n";
    $adminBody .= "Email: $email\n";
    $adminBody .= "Phone: $phone\n";
    $adminBody .= "Tier: $tier\n";
    $adminBody .= "Amount: \$$amount\n";
    $adminBody .= "Message: $message\n";
    $adminBody .= "Ref Code: $refCode\n";
    $adminBody .= "IP: $ip\n";
    $adminBody .= "Date: " . date('Y-m-d H:i:s') . "\n";
    
    @mail('invest@gositeme.com', $adminSubject, $adminBody, "From: noreply@gositeme.com\r\nReply-To: $email");
    
    // Send confirmation to investor
    $investorSubject = "GoSiteMe — Investment Interest Received ($refCode)";
    $investorBody = "Hi $name,\n\n";
    $investorBody .= "Thank you for your interest in investing in GoSiteMe! 🎉\n\n";
    $investorBody .= "We've received your investment interest:\n";
    $investorBody .= "  Tier: " . ucfirst($tier) . "\n";
    $investorBody .= "  Amount: \$$amount\n";
    $investorBody .= "  Reference: $refCode\n\n";
    $investorBody .= "Our team will review your submission and reach out within 24 hours to discuss next steps.\n\n";
    $investorBody .= "In the meantime, you can track our progress at: https://gositeme.com/invest\n\n";
    $investorBody .= "Best regards,\nThe GoSiteMe Team\n";
    $investorBody .= "1-833-GOSITEME | invest@gositeme.com\n";
    
    @mail($email, $investorSubject, $investorBody, "From: invest@gositeme.com\r\nReply-To: invest@gositeme.com");
    
    jsonResponse([
        'success' => true,
        'ref_code' => $refCode,
        'message' => 'Investment interest submitted successfully'
    ]);
}

/**
 * Dashboard data for authenticated investors
 */
function handleDashboard() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_email'])) {
        jsonResponse(['error' => 'Authentication required', 'login_url' => '/?login=1'], 401);
        return;
    }
    
    $email = $_SESSION['client_email'];
    $db = getDB();
    
    // Get investor record
    $stmt = $db->prepare("SELECT * FROM alfred_investor_interests 
                          WHERE email = ? AND status IN ('approved', 'funded') 
                          ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $investor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$investor) {
        jsonResponse(['error' => 'No active investment found for this account', 'invest_url' => '/invest'], 404);
        return;
    }
    
    // Get platform metrics
    $metrics = getPlatformMetrics($db);
    
    // Calculate investor returns based on tier
    $returns = calculateReturns($investor, $metrics);
    
    jsonResponse([
        'success' => true,
        'investor' => [
            'ref_code' => $investor['ref_code'],
            'name' => $investor['name'],
            'tier' => $investor['tier'],
            'amount' => floatval($investor['amount']),
            'status' => $investor['status'],
            'invested_date' => $investor['funded_at'] ?? $investor['created_at'],
        ],
        'returns' => $returns,
        'metrics' => $metrics,
    ]);
}

/**
 * Public platform metrics (no auth needed)
 */
function handlePublicMetrics() {
    try {
        $db = getDB();
        $metrics = getPlatformMetrics($db);
        jsonResponse(['success' => true, 'metrics' => $metrics]);
    } catch (Exception $e) {
        error_log("Investor metrics error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to load metrics'], 500);
    }
}

/**
 * Get platform metrics from DB and filesystem
 */
function getPlatformMetrics($db) {
    // Try cache first
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);
    $cacheFile = $docRoot . '/cache/investor_metrics.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    // Count real metrics
    $metrics = [];
    
    // Tool count from vapi-tools
    $vapiFile = $docRoot . '/api/vapi-tools.php';
    $metrics['total_tools'] = 1220;
    if (file_exists($vapiFile)) {
        $content = file_get_contents($vapiFile);
        $metrics['api_endpoints'] = substr_count($content, "case '");
    }
    
    // Page counts
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);
    $metrics['use_case_pages'] = max(0, count(glob($docRoot . '/use-cases/*.php')) - 1);
    $metrics['articles'] = max(0, count(glob($docRoot . '/articles/*.php')) - 1);
    $metrics['compare_pages'] = count(glob($docRoot . '/compare/*.php'));
    
    // Safe shell_exec with fallback
    $metrics['total_php_files'] = 18000; // fallback
    if (function_exists('shell_exec')) {
        $phpCount = @shell_exec("find " . escapeshellarg($docRoot) . " -name '*.php' 2>/dev/null | wc -l");
        if ($phpCount !== null) $metrics['total_php_files'] = intval($phpCount);
    }
    
    // Codebase size
    $metrics['codebase_bytes'] = 0;
    $metrics['codebase_mb'] = 0;
    if (function_exists('shell_exec')) {
        $size = @shell_exec("du -sb " . escapeshellarg($docRoot) . " 2>/dev/null | cut -f1");
        if ($size !== null) {
            $metrics['codebase_bytes'] = intval($size);
            $metrics['codebase_mb'] = round($metrics['codebase_bytes'] / 1024 / 1024, 1);
        }
    }
    
    // User count from DB
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM clients WHERE status = 'Active'");
        $metrics['active_users'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['cnt']);
    } catch (Exception $e) {
        $metrics['active_users'] = 0;
    }
    
    // Revenue metrics (from orders/services)
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM services WHERE domainstatus = 'Active'");
        $metrics['active_services'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['cnt']);
    } catch (Exception $e) {
        $metrics['active_services'] = 0;
    }
    
    // Monthly Recurring Revenue estimate
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as mrr FROM services 
                            WHERE domainstatus = 'Active' AND billingcycle = 'Monthly'");
        $mrr = floatval($stmt->fetch(PDO::FETCH_ASSOC)['mrr']);
        $metrics['mrr'] = round($mrr, 2);
    } catch (Exception $e) {
        $metrics['mrr'] = 0;
    }
    
    // Investor count
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM alfred_investor_interests WHERE status IN ('approved','funded')");
        $metrics['total_investors'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['cnt']);
    } catch (Exception $e) {
        $metrics['total_investors'] = 0;
    }
    
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM alfred_investor_interests WHERE status = 'funded'");
        $metrics['total_invested'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    } catch (Exception $e) {
        $metrics['total_invested'] = 0;
    }
    
    $metrics['pricing_tiers'] = 6;
    $metrics['sdks'] = 3;
    $metrics['voice_tools'] = 85;
    $metrics['industry_verticals'] = $metrics['use_case_pages'];
    $metrics['updated_at'] = date('c');
    
    // Cache
    @file_put_contents($cacheFile, json_encode($metrics));
    
    return $metrics;
}

/**
 * Calculate returns for an investor
 */
function calculateReturns($investor, $metrics) {
    $tier = $investor['tier'];
    $amount = floatval($investor['amount']);
    $mrr = $metrics['mrr'] ?? 0;
    
    // Revenue share percentages by tier
    $sharePercentages = [
        'seed' => 0.005,      // 0.5%
        'growth' => 0.01,     // 1%
        'strategic' => 0.02,  // 2%
    ];
    
    // Return caps by tier
    $returnCaps = [
        'seed' => 3,          // 3x
        'growth' => 5,        // 5x
        'strategic' => 10,    // 10x
    ];
    
    $sharePercent = $sharePercentages[$tier] ?? 0.005;
    $returnCap = $returnCaps[$tier] ?? 3;
    
    $monthlyShare = $mrr * $sharePercent;
    $maxReturn = $amount * $returnCap;
    
    // Calculate projected returns at different growth rates
    $projections = [];
    foreach ([1, 2, 5, 10] as $growthFactor) {
        $projectedMrr = $mrr * $growthFactor;
        $projectedMonthlyReturn = $projectedMrr * $sharePercent;
        $projectedAnnualReturn = $projectedMonthlyReturn * 12;
        $monthsToMax = $projectedMonthlyReturn > 0 ? ceil($maxReturn / $projectedMonthlyReturn) : 0;
        
        $projections["{$growthFactor}x"] = [
            'projected_mrr' => round($projectedMrr, 2),
            'monthly_return' => round($projectedMonthlyReturn, 2),
            'annual_return' => round($projectedAnnualReturn, 2),
            'months_to_cap' => $monthsToMax,
            'roi_percent' => $amount > 0 ? round(($projectedAnnualReturn / $amount) * 100, 1) : 0,
        ];
    }
    
    return [
        'share_percent' => $sharePercent * 100,
        'return_cap' => $returnCap,
        'max_return' => $maxReturn,
        'current_monthly_share' => round($monthlyShare, 2),
        'current_annual_share' => round($monthlyShare * 12, 2),
        'total_earned_to_date' => 0, // Will be computed from payment history
        'projections' => $projections,
    ];
}

// ── Admin Helpers ──────────────────────────────────────────────────

define('ADMIN_EMAILS', ['gositeme@gmail.com']);

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_email'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
        exit;
    }
    if (!in_array(strtolower($_SESSION['client_email']), ADMIN_EMAILS)) {
        jsonResponse(['error' => 'Admin access required'], 403);
        exit;
    }
}

/**
 * Admin: List all investors with stats
 */
function handleAdminDashboard() {
    requireAdmin();
    
    try {
        $db = getDB();
        
        // Get all investors
        $stmt = $db->query("SELECT * FROM alfred_investor_interests ORDER BY created_at DESC");
        $investors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Summary statistics
        $stats = [
            'total' => count($investors),
            'pending' => 0,
            'contacted' => 0,
            'approved' => 0,
            'funded' => 0,
            'declined' => 0,
            'total_raised' => 0,
            'total_pledged' => 0,
        ];
        
        foreach ($investors as $inv) {
            $stats[$inv['status']] = ($stats[$inv['status']] ?? 0) + 1;
            $stats['total_pledged'] += floatval($inv['amount']);
            if ($inv['status'] === 'funded') {
                $stats['total_raised'] += floatval($inv['amount']);
            }
        }
        
        // Platform metrics
        $metrics = getPlatformMetrics($db);
        
        jsonResponse([
            'success' => true,
            'investors' => $investors,
            'stats' => $stats,
            'metrics' => $metrics,
        ]);
    } catch (Exception $e) {
        error_log("Investor admin error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to load admin data'], 500);
    }
}

/**
 * Admin: Update investor status and notes
 */
function handleAdminUpdate() {
    requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id'])) {
        jsonResponse(['error' => 'Missing investor ID'], 400);
        return;
    }
    
    $id = intval($input['id']);
    $status = $input['status'] ?? null;
    $notes = $input['notes'] ?? null;
    
    $validStatuses = ['pending', 'contacted', 'approved', 'funded', 'declined'];
    if ($status && !in_array($status, $validStatuses)) {
        jsonResponse(['error' => 'Invalid status'], 400);
        return;
    }
    
    try {
        $db = getDB();
        
        // Build dynamic update
        $updates = [];
        $params = [];
        
        if ($status) {
            $updates[] = 'status = ?';
            $params[] = $status;
            
            // Set funded_at timestamp when marking as funded
            if ($status === 'funded') {
                $updates[] = 'funded_at = NOW()';
            }
        }
        
        if ($notes !== null) {
            $updates[] = 'notes = ?';
            $params[] = $notes;
        }
        
        if (empty($updates)) {
            jsonResponse(['error' => 'Nothing to update'], 400);
            return;
        }
        
        $params[] = $id;
        $sql = "UPDATE alfred_investor_interests SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Get updated record for notification
        $stmt = $db->prepare("SELECT * FROM alfred_investor_interests WHERE id = ?");
        $stmt->execute([$id]);
        $investor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Send status-change notification email to investor
        if ($status && $investor) {
            $statusMessages = [
                'contacted' => "We've received your investment interest and our team will be reaching out to you shortly.",
                'approved' => "Great news! Your investment has been approved. We'll send you the next steps soon.",
                'funded' => "Your investment of \${$investor['amount']} has been recorded as funded. Welcome aboard as a GoSiteMe investor! Your dashboard is now live at https://gositeme.com/investor-dashboard.php",
                'declined' => "We appreciate your interest in GoSiteMe. Unfortunately, we're unable to proceed with your investment request at this time.",
            ];
            
            if (isset($statusMessages[$status])) {
                $subject = "GoSiteMe Investment Update — " . ucfirst($status);
                $body = "Hi {$investor['name']},\n\n";
                $body .= $statusMessages[$status] . "\n\n";
                $body .= "Reference: {$investor['ref_code']}\n";
                $body .= "\nBest regards,\nGoSiteMe Investment Team\n1-833-GOSITEME | invest@gositeme.com\n";
                @mail($investor['email'], $subject, $body, "From: invest@gositeme.com\r\nReply-To: invest@gositeme.com");
            }
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Investor updated successfully',
            'investor' => $investor,
        ]);
    } catch (Exception $e) {
        error_log("Investor admin update error: " . $e->getMessage());
        jsonResponse(['error' => 'Update failed. Please try again.'], 500);
    }
}

/**
 * Initialize database tables
 */
function initInvestorTables() {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;
    
    try {
        $db = getDB();
        
        $db->exec("CREATE TABLE IF NOT EXISTS alfred_investor_interests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ref_code VARCHAR(20) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) DEFAULT '',
            tier ENUM('seed','growth','strategic') NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            message TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT '',
            status ENUM('pending','contacted','approved','funded','declined') DEFAULT 'pending',
            funded_at DATETIME DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_tier (tier),
            INDEX idx_ref_code (ref_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->exec("CREATE TABLE IF NOT EXISTS alfred_investor_payouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            investor_id INT NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            revenue_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            share_percent DECIMAL(5,4) NOT NULL,
            payout_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            cumulative_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            status ENUM('calculated','approved','paid') DEFAULT 'calculated',
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (investor_id) REFERENCES alfred_investor_interests(id) ON DELETE CASCADE,
            INDEX idx_investor (investor_id),
            INDEX idx_period (period_start, period_end)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Add Stripe columns if they don't exist
        $stripeColumns = [
            'stripe_session_id' => "ALTER TABLE alfred_investor_interests ADD COLUMN stripe_session_id VARCHAR(255) DEFAULT NULL",
            'stripe_payment_intent_id' => "ALTER TABLE alfred_investor_interests ADD COLUMN stripe_payment_intent_id VARCHAR(255) DEFAULT NULL",
            'stripe_customer_id' => "ALTER TABLE alfred_investor_interests ADD COLUMN stripe_customer_id VARCHAR(255) DEFAULT NULL",
        ];
        foreach ($stripeColumns as $col => $sql) {
            try {
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alfred_investor_interests' AND COLUMN_NAME = ?");
                $checkStmt->execute([$col]);
                if ($checkStmt->fetchColumn() == 0) {
                    $db->exec($sql);
                }
            } catch (Exception $e) {
                // Column may already exist
            }
        }

    } catch (Exception $e) {
        // Tables might already exist, that's fine
        error_log("Investor table init: " . $e->getMessage());
    }
}

// ── Stripe Payment Processing ──────────────────────────────────────

/**
 * Create a Stripe Checkout session for investment payment
 * POST /api/investor.php?action=create_checkout
 */
function handleCreateCheckout() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON input'], 400);
        return;
    }

    // Validate required fields
    $required = ['name', 'email', 'tier', 'amount'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            jsonResponse(['error' => "Missing required field: $field"], 400);
            return;
        }
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        jsonResponse(['error' => 'Invalid email address'], 400);
        return;
    }

    $validTiers = ['seed', 'growth', 'strategic'];
    $tier = $input['tier'];
    if (!in_array($tier, $validTiers)) {
        jsonResponse(['error' => 'Invalid investment tier'], 400);
        return;
    }

    $amount = floatval($input['amount']);
    if ($amount < 100) {
        jsonResponse(['error' => 'Minimum investment is $100'], 400);
        return;
    }

    $name = sanitize($input['name']);
    $phone = sanitize($input['phone'] ?? '');
    $message = sanitize($input['message'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Rate limiting
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_investor_interests WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->execute([$email]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] >= 3) {
        jsonResponse(['error' => 'Too many submissions. Please try again tomorrow.'], 429);
        return;
    }

    $refCode = 'INV-' . strtoupper(substr(md5($email . time()), 0, 8));

    try {
        // Store investor record with pending status
        $stmt = $db->prepare("INSERT INTO alfred_investor_interests
                              (ref_code, name, email, phone, tier, amount, message, ip_address, status, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$refCode, $name, $email, $phone, $tier, $amount, $message, $ip]);
        $investorId = $db->lastInsertId();

        // Tier display names
        $tierNames = [
            'seed' => 'Seed Investor',
            'growth' => 'Growth Investor',
            'strategic' => 'Strategic Partner',
        ];

        // Create Stripe Checkout Session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'customer_email' => $email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'GoSiteMe Investment — ' . ($tierNames[$tier] ?? ucfirst($tier)),
                        'description' => "Investment Ref: $refCode | Tier: " . ucfirst($tier),
                    ],
                    'unit_amount' => intval($amount * 100), // Stripe uses cents
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'tier' => $tier,
                'ref_code' => $refCode,
                'investor_id' => $investorId,
            ],
            'success_url' => 'https://gositeme.com/invest?payment=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://gositeme.com/invest?payment=cancelled',
        ]);

        // Store Stripe session ID
        $stmt = $db->prepare("UPDATE alfred_investor_interests SET stripe_session_id = ? WHERE id = ?");
        $stmt->execute([$session->id, $investorId]);

        // Notify admin
        $adminSubject = "💳 New Investment Checkout: $name ($tier - \$$amount)";
        $adminBody = "A new investor has initiated Stripe checkout:\n\n";
        $adminBody .= "Name: $name\nEmail: $email\nTier: $tier\nAmount: \$$amount\nRef: $refCode\n";
        $adminBody .= "Stripe Session: {$session->id}\n";
        @mail('invest@gositeme.com', $adminSubject, $adminBody, "From: noreply@gositeme.com\r\nReply-To: $email");

        jsonResponse([
            'success' => true,
            'checkout_url' => $session->url,
            'ref_code' => $refCode,
        ]);

    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe checkout error: " . $e->getMessage());
        jsonResponse(['error' => 'Payment processing error. Please try again.'], 500);
    } catch (Exception $e) {
        error_log("Checkout error: " . $e->getMessage());
        jsonResponse(['error' => 'An error occurred. Please try again.'], 500);
    }
}

/**
 * Handle Stripe webhook events
 * POST /api/investor.php?action=stripe_webhook
 */
function handleStripeWebhook() {
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (empty($sigHeader)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing signature']);
        exit;
    }

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
    } catch (\UnexpectedValueException $e) {
        error_log("Stripe webhook invalid payload: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        error_log("Stripe webhook signature failed: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }

    $db = getDB();

    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            $refCode = $session->metadata->ref_code ?? null;
            $investorId = $session->metadata->investor_id ?? null;
            $paymentIntentId = $session->payment_intent ?? null;
            $customerId = $session->customer ?? null;

            if ($investorId) {
                $stmt = $db->prepare("UPDATE alfred_investor_interests
                                      SET status = 'funded',
                                          funded_at = NOW(),
                                          stripe_payment_intent_id = ?,
                                          stripe_customer_id = ?,
                                          stripe_session_id = ?
                                      WHERE id = ?");
                $stmt->execute([$paymentIntentId, $customerId, $session->id, $investorId]);

                // Get investor details for emails
                $stmt = $db->prepare("SELECT * FROM alfred_investor_interests WHERE id = ?");
                $stmt->execute([$investorId]);
                $investor = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($investor) {
                    // Send confirmation email to investor
                    sendInvestorEmail($investor['email'], 'funded', [
                        'name' => $investor['name'],
                        'tier' => $investor['tier'],
                        'amount' => $investor['amount'],
                        'ref_code' => $investor['ref_code'],
                    ]);

                    // Notify admin
                    $adminSubject = "✅ Investment FUNDED: {$investor['name']} ({$investor['tier']} - \${$investor['amount']})";
                    $adminBody = "An investment has been successfully funded via Stripe!\n\n";
                    $adminBody .= "Name: {$investor['name']}\n";
                    $adminBody .= "Email: {$investor['email']}\n";
                    $adminBody .= "Tier: {$investor['tier']}\n";
                    $adminBody .= "Amount: \${$investor['amount']}\n";
                    $adminBody .= "Ref: {$investor['ref_code']}\n";
                    $adminBody .= "Payment Intent: $paymentIntentId\n";
                    $adminBody .= "Date: " . date('Y-m-d H:i:s') . "\n";
                    @mail('invest@gositeme.com', $adminSubject, $adminBody, "From: noreply@gositeme.com");
                }
            }
            break;

        case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object;
            $piId = $paymentIntent->id;

            // Update any record that matches this payment intent
            $stmt = $db->prepare("UPDATE alfred_investor_interests
                                  SET status = 'funded', funded_at = COALESCE(funded_at, NOW())
                                  WHERE stripe_payment_intent_id = ? AND status != 'funded'");
            $stmt->execute([$piId]);
            break;

        case 'payment_intent.payment_failed':
            $paymentIntent = $event->data->object;
            $piId = $paymentIntent->id;
            $failureMessage = $paymentIntent->last_payment_error->message ?? 'Unknown error';

            error_log("Stripe payment failed for PI $piId: $failureMessage");

            // Find investor by payment intent and notify
            $stmt = $db->prepare("SELECT * FROM alfred_investor_interests WHERE stripe_payment_intent_id = ?");
            $stmt->execute([$piId]);
            $investor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($investor) {
                $adminSubject = "❌ Investment Payment FAILED: {$investor['name']}";
                $adminBody = "A payment has failed:\n\n";
                $adminBody .= "Name: {$investor['name']}\nEmail: {$investor['email']}\n";
                $adminBody .= "Amount: \${$investor['amount']}\nRef: {$investor['ref_code']}\n";
                $adminBody .= "Error: $failureMessage\n";
                @mail('invest@gositeme.com', $adminSubject, $adminBody, "From: noreply@gositeme.com");
            }
            break;

        case 'charge.refunded':
            $charge = $event->data->object;
            $piId = $charge->payment_intent;

            if ($piId) {
                $stmt = $db->prepare("UPDATE alfred_investor_interests
                                      SET status = 'declined', notes = CONCAT(COALESCE(notes,''), '\nRefunded via Stripe on " . date('Y-m-d H:i:s') . "')
                                      WHERE stripe_payment_intent_id = ?");
                $stmt->execute([$piId]);

                $stmt = $db->prepare("SELECT * FROM alfred_investor_interests WHERE stripe_payment_intent_id = ?");
                $stmt->execute([$piId]);
                $investor = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($investor) {
                    $adminSubject = "🔄 Investment REFUNDED: {$investor['name']}";
                    $adminBody = "An investment payment has been refunded:\n\n";
                    $adminBody .= "Name: {$investor['name']}\nEmail: {$investor['email']}\n";
                    $adminBody .= "Amount: \${$investor['amount']}\nRef: {$investor['ref_code']}\n";
                    @mail('invest@gositeme.com', $adminSubject, $adminBody, "From: noreply@gositeme.com");
                }
            }
            break;

        default:
            error_log("Stripe webhook unhandled event type: " . $event->type);
            break;
    }

    http_response_code(200);
    echo json_encode(['received' => true]);
    exit;
}

/**
 * Verify a Stripe payment session
 * GET /api/investor.php?action=verify_payment&session_id=xxx
 */
function handleVerifyPayment() {
    $sessionId = $_GET['session_id'] ?? '';

    if (empty($sessionId)) {
        jsonResponse(['error' => 'Missing session_id parameter'], 400);
        return;
    }

    try {
        // Retrieve from Stripe
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        // Get investor record
        $db = getDB();
        $stmt = $db->prepare("SELECT ref_code, name, email, tier, amount, status, funded_at, created_at
                              FROM alfred_investor_interests WHERE stripe_session_id = ?");
        $stmt->execute([$sessionId]);
        $investor = $stmt->fetch(PDO::FETCH_ASSOC);

        jsonResponse([
            'success' => true,
            'payment_status' => $session->payment_status,  // 'paid', 'unpaid', 'no_payment_required'
            'session_status' => $session->status,           // 'open', 'complete', 'expired'
            'amount_total' => $session->amount_total / 100, // Convert cents to dollars
            'currency' => $session->currency,
            'customer_email' => $session->customer_details->email ?? $session->customer_email ?? null,
            'investor' => $investor ?: null,
        ]);

    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe verify payment error: " . $e->getMessage());
        jsonResponse(['error' => 'Unable to verify payment session'], 500);
    }
}

// ── Email Helper ───────────────────────────────────────────────────

/**
 * Send branded HTML emails to investors
 *
 * @param string $to     Recipient email
 * @param string $type   Email type: confirmation, funded, status_update, monthly_update, payout
 * @param array  $data   Template data (name, tier, amount, ref_code, etc.)
 * @return bool
 */
function sendInvestorEmail($to, $type, $data = []) {
    $name     = $data['name'] ?? 'Investor';
    $tier     = ucfirst($data['tier'] ?? 'seed');
    $amount   = number_format(floatval($data['amount'] ?? 0), 2);
    $refCode  = $data['ref_code'] ?? '';

    $subjects = [
        'confirmation'   => "GoSiteMe — Investment Interest Received ($refCode)",
        'funded'         => "GoSiteMe — Your Investment is Confirmed! 🎉 ($refCode)",
        'status_update'  => "GoSiteMe — Investment Status Update ($refCode)",
        'monthly_update' => "GoSiteMe — Monthly Investor Update",
        'payout'         => "GoSiteMe — Payout Processed ($refCode)",
    ];
    $subject = $subjects[$type] ?? "GoSiteMe — Investor Update";

    // Build body content per type
    switch ($type) {
        case 'confirmation':
            $heading = 'Investment Interest Received';
            $body = "<p>Hi $name,</p>
                     <p>Thank you for your interest in investing in GoSiteMe!</p>
                     <p>We've received your submission:</p>
                     <table style='border-collapse:collapse;margin:16px 0;'>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Tier:</td><td style='padding:6px 12px;'>$tier</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Amount:</td><td style='padding:6px 12px;'>\$$amount</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Reference:</td><td style='padding:6px 12px;'>$refCode</td></tr>
                     </table>
                     <p>Our team will review your submission and reach out within 24 hours.</p>";
            break;

        case 'funded':
            $heading = 'Investment Confirmed!';
            $body = "<p>Hi $name,</p>
                     <p>Great news! Your investment payment has been <strong>successfully processed</strong>.</p>
                     <table style='border-collapse:collapse;margin:16px 0;'>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Tier:</td><td style='padding:6px 12px;'>$tier</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Amount:</td><td style='padding:6px 12px;'>\$$amount</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Reference:</td><td style='padding:6px 12px;'>$refCode</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Status:</td><td style='padding:6px 12px;color:#16a34a;font-weight:bold;'>Funded ✓</td></tr>
                     </table>
                     <p>Welcome aboard as an official GoSiteMe investor! Your investor dashboard is now live:</p>
                     <p style='text-align:center;margin:24px 0;'>
                       <a href='https://gositeme.com/investor-dashboard' style='background:#2563eb;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>View Your Dashboard</a>
                     </p>
                     <p>You'll receive monthly revenue share updates and can track your returns anytime.</p>";
            break;

        case 'status_update':
            $status = $data['status'] ?? 'updated';
            $heading = 'Investment Status Update';
            $body = "<p>Hi $name,</p>
                     <p>Your investment status has been updated to: <strong>" . ucfirst($status) . "</strong></p>
                     <table style='border-collapse:collapse;margin:16px 0;'>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Reference:</td><td style='padding:6px 12px;'>$refCode</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Status:</td><td style='padding:6px 12px;'>" . ucfirst($status) . "</td></tr>
                     </table>
                     <p>If you have questions, reply to this email or call us at 1-833-GOSITEME.</p>";
            break;

        case 'monthly_update':
            $mrr        = number_format(floatval($data['mrr'] ?? 0), 2);
            $share      = number_format(floatval($data['monthly_share'] ?? 0), 2);
            $cumulative = number_format(floatval($data['cumulative'] ?? 0), 2);
            $heading    = 'Monthly Investor Update';
            $body = "<p>Hi $name,</p>
                     <p>Here's your monthly investment summary:</p>
                     <table style='border-collapse:collapse;margin:16px 0;'>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Platform MRR:</td><td style='padding:6px 12px;'>\$$mrr</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Your Monthly Share:</td><td style='padding:6px 12px;'>\$$share</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Cumulative Earnings:</td><td style='padding:6px 12px;'>\$$cumulative</td></tr>
                     </table>
                     <p style='text-align:center;margin:24px 0;'>
                       <a href='https://gositeme.com/investor-dashboard' style='background:#2563eb;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>View Full Dashboard</a>
                     </p>";
            break;

        case 'payout':
            $payoutAmount = number_format(floatval($data['payout_amount'] ?? 0), 2);
            $period       = $data['period'] ?? date('F Y');
            $heading      = 'Payout Processed';
            $body = "<p>Hi $name,</p>
                     <p>A revenue share payout has been processed for your investment:</p>
                     <table style='border-collapse:collapse;margin:16px 0;'>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Period:</td><td style='padding:6px 12px;'>$period</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Payout Amount:</td><td style='padding:6px 12px;color:#16a34a;font-weight:bold;'>\$$payoutAmount</td></tr>
                       <tr><td style='padding:6px 12px;font-weight:bold;'>Reference:</td><td style='padding:6px 12px;'>$refCode</td></tr>
                     </table>
                     <p>Funds will be deposited to your account within 3-5 business days.</p>";
            break;

        default:
            $heading = 'Investor Update';
            $body = "<p>Hi $name,</p><p>" . ($data['message'] ?? 'You have a new update regarding your investment.') . "</p>";
            break;
    }

    // Assemble full HTML email
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
    <body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
      <tr><td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
          <!-- Header -->
          <tr><td style="background:linear-gradient(135deg,#1e40af,#7c3aed);padding:32px 40px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:24px;letter-spacing:-0.5px;">GoSiteMe</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Investor Portal</p>
          </td></tr>
          <!-- Heading -->
          <tr><td style="padding:32px 40px 0;">
            <h2 style="margin:0 0 8px;color:#1e293b;font-size:20px;">' . $heading . '</h2>
            <hr style="border:none;border-top:2px solid #e2e8f0;margin:16px 0;">
          </td></tr>
          <!-- Body -->
          <tr><td style="padding:0 40px 32px;color:#334155;font-size:15px;line-height:1.6;">
            ' . $body . '
          </td></tr>
          <!-- Footer -->
          <tr><td style="background:#f8fafc;padding:24px 40px;border-top:1px solid #e2e8f0;text-align:center;">
            <p style="margin:0 0 4px;color:#64748b;font-size:13px;">GoSiteMe Inc. — Powering the Future of Communication</p>
            <p style="margin:0;color:#94a3b8;font-size:12px;">1-833-GOSITEME | <a href="mailto:invest@gositeme.com" style="color:#2563eb;text-decoration:none;">invest@gositeme.com</a> | <a href="https://gositeme.com/invest" style="color:#2563eb;text-decoration:none;">gositeme.com/invest</a></p>
          </td></tr>
        </table>
      </td></tr>
    </table>
    </body></html>';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: GoSiteMe Investments <invest@gositeme.com>\r\n";
    $headers .= "Reply-To: invest@gositeme.com\r\n";
    $headers .= "X-Mailer: GoSiteMe/1.0\r\n";

    return @mail($to, $subject, $html, $headers);
}
