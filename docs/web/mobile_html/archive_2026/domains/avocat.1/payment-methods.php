<?php
/**
 * Payment Methods — /payment-methods
 * Shows saved payment methods and allows management via Stripe Customer Portal.
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';

$page_title = 'Payment Methods — GoSiteMe';
$page_description = 'Manage your saved payment methods';
$page_canonical = '/payment-methods';

include __DIR__ . '/includes/site-header.inc.php';

$clientId = $_SESSION['client_id'] ?? null;
$clientName = $_SESSION['client_name'] ?? 'User';

// Load payment methods from DB
$methods = [];
if ($clientId) {
    try {
        define('GOSITEME_BILLING', true);
        require_once __DIR__ . '/pay/includes/billing-config.php';
        $db = billingDB();
        $stmt = $db->prepare("
            SELECT id, description, gateway_name, created_at
            FROM payment_methods
            WHERE userid = ? AND deleted_at IS NULL
            ORDER BY order_preference ASC, id DESC
        ");
        $stmt->execute([$clientId]);
        $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // DB unavailable — show empty state
    }
}

$gatewayNames = [
    'stripe' => 'Credit/Debit Card', 'paypal' => 'PayPal',
    'stripe_ach' => 'Bank Account (ACH)', 'paypalcheckout' => 'PayPal',
    'authorizecim' => 'Credit Card'
];
$gatewayIcons = [
    'stripe' => 'fa-credit-card', 'paypal' => 'fa-brands fa-paypal',
    'stripe_ach' => 'fa-building-columns', 'paypalcheckout' => 'fa-brands fa-paypal',
    'authorizecim' => 'fa-credit-card'
];
?>

<style>
.pm-wrap { max-width: 800px; margin: 5rem auto 3rem; padding: 0 1.5rem; min-height: 60vh; }
.pm-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
.pm-header h1 { font-size: 1.6rem; font-weight: 700; }
.pm-card { background: var(--bg-card, #1a1a2e); border: 1px solid var(--border, #2a2a3e); border-radius: 16px; overflow: hidden; }
.pm-item { display: flex; align-items: center; gap: 16px; padding: 20px 24px; border-bottom: 1px solid var(--border, #2a2a3e); transition: background 0.2s; }
.pm-item:last-child { border-bottom: none; }
.pm-item:hover { background: rgba(255,255,255,0.02); }
.pm-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(0,168,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: var(--accent, #00a8ff); flex-shrink: 0; }
.pm-info { flex: 1; }
.pm-desc { font-weight: 600; font-size: 0.95rem; }
.pm-type { font-size: 0.8rem; color: var(--text-muted, #8b8fa3); margin-top: 2px; }
.pm-date { font-size: 0.75rem; color: var(--text-muted, #8b8fa3); }
.pm-empty { text-align: center; padding: 3rem 2rem; }
.pm-empty i { font-size: 3rem; color: var(--text-muted, #8b8fa3); margin-bottom: 1rem; display: block; opacity: 0.5; }
.pm-empty p { color: var(--text-muted, #8b8fa3); margin-bottom: 1.5rem; }
.pm-note { margin-top: 1.5rem; padding: 16px 20px; background: rgba(0,168,255,0.05); border: 1px solid rgba(0,168,255,0.15); border-radius: 12px; font-size: 0.85rem; color: var(--text-muted, #8b8fa3); display: flex; align-items: flex-start; gap: 10px; }
.pm-note i { color: var(--accent, #00a8ff); margin-top: 2px; }
@media (max-width: 768px) {
    .pm-wrap { margin-top: 4rem; padding: 0 1rem; }
    .pm-header { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>

<div class="pm-wrap">
    <div class="pm-header">
        <h1><i class="fas fa-credit-card"></i> Payment Methods</h1>
        <a href="/dashboard#payment-methods" class="btn btn-outline" style="font-size: 0.85rem;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="pm-card">
        <?php if (!empty($methods)): ?>
            <?php foreach ($methods as $m): ?>
                <div class="pm-item">
                    <div class="pm-icon">
                        <i class="fas <?= $gatewayIcons[$m['gateway_name']] ?? 'fa-credit-card' ?>"></i>
                    </div>
                    <div class="pm-info">
                        <div class="pm-desc"><?= htmlspecialchars($m['description'] ?: 'Payment Method') ?></div>
                        <div class="pm-type"><?= htmlspecialchars($gatewayNames[$m['gateway_name']] ?? ucfirst($m['gateway_name'])) ?></div>
                    </div>
                    <div class="pm-date"><?= $m['created_at'] ? date('M j, Y', strtotime($m['created_at'])) : '' ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="pm-empty">
                <i class="fas fa-credit-card"></i>
                <p>No saved payment methods</p>
                <a href="/store" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Browse Store</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="pm-note">
        <i class="fas fa-shield-halved"></i>
        <div>
            Payment methods are securely saved through <strong>Stripe</strong> during checkout.
            Your card details are never stored on our servers — they are managed entirely by Stripe's PCI-compliant infrastructure.
            To add a new payment method, simply proceed through checkout on your next purchase.
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
