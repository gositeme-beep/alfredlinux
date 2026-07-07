<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';

// ── Page data ───────────────────────────────────────────────────────
$pageTitle = 'My Account';
$db = getSharedDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Client info
$client = $db->prepare("SELECT id, firstname, lastname, email, phone, company, status, credit, created_at FROM clients WHERE id = ?");
$client->execute([$clientId]);
$client = $client->fetch(PDO::FETCH_ASSOC);

// Active services
$services = [];
try {
    $svcStmt = $db->prepare("SELECT s.id, s.domain, s.status, s.amount, s.billing_cycle, s.next_due_date, p.name as product FROM services s LEFT JOIN products p ON s.product_id = p.id WHERE s.client_id = ? ORDER BY s.status ASC, s.id DESC");
    $svcStmt->execute([$clientId]);
    $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Recent invoices
$invoices = [];
try {
    $invStmt = $db->prepare("SELECT id, invoice_number, total, status, due_date, created_at FROM invoices WHERE client_id = ? ORDER BY id DESC LIMIT 10");
    $invStmt->execute([$clientId]);
    $invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Unpaid total
$unpaid = ['cnt' => 0, 'total' => 0];
try {
    $upStmt = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as total FROM invoices WHERE client_id = ? AND status = 'Unpaid'");
    $upStmt->execute([$clientId]);
    $unpaid = $upStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// AI usage (current month)
$aiUsage = ['total_tokens' => 0, 'total_cost' => 0, 'plan' => 'Free'];
$billingPeriod = date('Y-m');
try {
    $balStmt = $db->prepare("SELECT plan, tokens_included, tokens_used, tokens_overage, cost_overage_usd FROM alfred_ide_balance WHERE user_id = ? AND billing_period = ?");
    $balStmt->execute([$clientId, $billingPeriod]);
    $bal = $balStmt->fetch(PDO::FETCH_ASSOC);
    if ($bal) {
        $aiUsage['plan'] = ucfirst($bal['plan'] ?? 'free');
        $aiUsage['tokens_included'] = (int)($bal['tokens_included'] ?? 0);
        $aiUsage['tokens_used'] = (int)($bal['tokens_used'] ?? 0);
        $aiUsage['total_cost'] = (float)($bal['cost_overage_usd'] ?? 0);
    }
} catch (Exception $e) {}

// Support tickets
$tickets = [];
try {
    $tktStmt = $db->prepare("SELECT id, subject, status, priority, created_at FROM support_tickets WHERE client_id = ? ORDER BY id DESC LIMIT 5");
    $tktStmt->execute([$clientId]);
    $tickets = $tktStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Domains
$domains = [];
try {
    $domStmt = $db->prepare("SELECT id, domain, status, expiry_date, registrar FROM domains WHERE client_id = ? ORDER BY domain ASC");
    $domStmt->execute([$clientId]);
    $domains = $domStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Security: 2FA, sessions
$has2FA = false;
try {
    $tfaStmt = $db->prepare("SELECT totp_secret FROM clients WHERE id = ? AND totp_secret IS NOT NULL AND totp_secret != ''");
    $tfaStmt->execute([$clientId]);
    $has2FA = (bool)$tfaStmt->fetchColumn();
} catch (Exception $e) {}

$isCommander = ($clientId === 33);

// Military rank data
require_once __DIR__ . '/includes/rank-guard.inc.php';
$myRankData = null;
try {
    $rkStmt = $db->prepare("
        SELECT ur.rank_code, ur.xp, ur.joined_at, mr.rank_name, mr.rank_tier, mr.badge_icon, mr.rank_group
        FROM user_ranks ur
        JOIN military_ranks mr ON mr.rank_code = ur.rank_code
        WHERE ur.client_id = ? AND ur.is_active = 1
    ");
    $rkStmt->execute([$clientId]);
    $myRankData = $rkStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

require_once __DIR__ . '/includes/site-header.inc.php';
?>

<main class="account-page">
<div class="container" style="max-width:1200px; margin:2rem auto; padding:0 1rem;">

<h1 style="font-size:1.8rem; margin-bottom:0.5rem;">My Account</h1>
<p style="color:#888; margin-bottom:2rem;">Manage your GoSiteMe account, services, and billing.</p>

<!-- Quick Stats Row -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <div style="background:#1a1a2e; border:1px solid #333; border-radius:12px; padding:1.2rem;">
        <div style="color:#888; font-size:0.85rem;">Account Credit</div>
        <div style="font-size:1.5rem; font-weight:700; color:#4ecdc4;">$<?= number_format((float)($client['credit'] ?? 0), 2) ?></div>
    </div>
    <div style="background:#1a1a2e; border:1px solid #333; border-radius:12px; padding:1.2rem;">
        <div style="color:#888; font-size:0.85rem;">Active Services</div>
        <div style="font-size:1.5rem; font-weight:700; color:#6c5ce7;"><?= count(array_filter($services, fn($s) => $s['status'] === 'Active')) ?></div>
    </div>
    <div style="background:#1a1a2e; border:1px solid #333; border-radius:12px; padding:1.2rem;">
        <div style="color:#888; font-size:0.85rem;">Unpaid Invoices</div>
        <div style="font-size:1.5rem; font-weight:700; color:<?= $unpaid['cnt'] > 0 ? '#e74c3c' : '#2ecc71' ?>;">
            <?= (int)$unpaid['cnt'] ?> ($<?= number_format((float)$unpaid['total'], 2) ?>)
        </div>
    </div>
    <div style="background:#1a1a2e; border:1px solid #333; border-radius:12px; padding:1.2rem;">
        <div style="color:#888; font-size:0.85rem;">AI Plan</div>
        <div style="font-size:1.5rem; font-weight:700; color:#ff6b6b;"><?= htmlspecialchars($aiUsage['plan']) ?></div>
    </div>
</div>

<!-- Tabs -->
<div id="account-tabs" style="margin-bottom:2rem;">
    <div style="display:flex; gap:0.5rem; border-bottom:1px solid #333; margin-bottom:1.5rem; flex-wrap:wrap;">
        <?php foreach (['profile' => 'Profile', 'services' => 'Services', 'billing' => 'Billing', 'rank' => 'Rank', 'ai' => 'AI Usage', 'security' => 'Security', 'domains' => 'Domains'] as $tab => $label): ?>
        <button class="tab-btn<?= $tab === 'profile' ? ' active' : '' ?>" data-tab="<?= $tab ?>"
            style="background:none; border:none; color:#aaa; padding:0.7rem 1.2rem; cursor:pointer; font-size:0.95rem; border-bottom:2px solid transparent; transition:all 0.2s;"
            onmouseover="this.style.color='#fff'" onmouseout="if(!this.classList.contains('active'))this.style.color='#aaa'"
        ><?= $label ?></button>
        <?php endforeach; ?>
    </div>

    <!-- Profile Tab -->
    <div class="tab-panel" id="tab-profile">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; max-width:600px;">
            <div>
                <label style="color:#888; font-size:0.85rem; display:block; margin-bottom:0.3rem;">Name</label>
                <div style="color:#fff; font-size:1.1rem;"><?= htmlspecialchars(($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? '')) ?></div>
            </div>
            <div>
                <label style="color:#888; font-size:0.85rem; display:block; margin-bottom:0.3rem;">Email</label>
                <div style="color:#fff; font-size:1.1rem;"><?= htmlspecialchars($client['email'] ?? '') ?></div>
            </div>
            <div>
                <label style="color:#888; font-size:0.85rem; display:block; margin-bottom:0.3rem;">Phone</label>
                <div style="color:#fff; font-size:1.1rem;"><?= htmlspecialchars($client['phone'] ?? 'Not set') ?></div>
            </div>
            <div>
                <label style="color:#888; font-size:0.85rem; display:block; margin-bottom:0.3rem;">Company</label>
                <div style="color:#fff; font-size:1.1rem;"><?= htmlspecialchars($client['company'] ?? 'Not set') ?></div>
            </div>
            <div>
                <label style="color:#888; font-size:0.85rem; display:block; margin-bottom:0.3rem;">Member Since</label>
                <div style="color:#fff; font-size:1.1rem;"><?= date('F j, Y', strtotime($client['created_at'] ?? 'now')) ?></div>
            </div>
            <div>
                <label style="color:#888; font-size:0.85rem; display:block; margin-bottom:0.3rem;">Status</label>
                <div style="color:<?= ($client['status'] ?? '') === 'Active' ? '#2ecc71' : '#e74c3c' ?>; font-size:1.1rem; font-weight:600;">
                    <?= htmlspecialchars($client['status'] ?? 'Unknown') ?>
                    <?= $isCommander ? ' <span style="color:#ffd700;">⭐ Commander</span>' : '' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Tab -->
    <div class="tab-panel" id="tab-services" style="display:none;">
        <?php if (empty($services)): ?>
            <p style="color:#888;">No services found. <a href="/pricing" style="color:#6c5ce7;">View our plans</a></p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #333;">
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Product</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Domain</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Status</th>
                    <th style="text-align:right; padding:0.8rem; color:#888; font-weight:500;">Amount</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Billing</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Next Due</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($services as $s): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:0.8rem; color:#fff;"><?= htmlspecialchars($s['product'] ?? 'Service') ?></td>
                    <td style="padding:0.8rem; color:#ccc;"><?= htmlspecialchars($s['domain'] ?? '—') ?></td>
                    <td style="padding:0.8rem;">
                        <span style="color:<?= $s['status'] === 'Active' ? '#2ecc71' : '#e74c3c' ?>; font-weight:500;">
                            <?= htmlspecialchars($s['status']) ?>
                        </span>
                    </td>
                    <td style="padding:0.8rem; text-align:right; color:#fff;">$<?= number_format((float)$s['amount'], 2) ?></td>
                    <td style="padding:0.8rem; color:#aaa;"><?= htmlspecialchars($s['billing_cycle'] ?? '—') ?></td>
                    <td style="padding:0.8rem; color:#aaa;"><?= $s['next_due_date'] ? date('M j, Y', strtotime($s['next_due_date'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Billing Tab -->
    <div class="tab-panel" id="tab-billing" style="display:none;">
        <h3 style="font-size:1.1rem; margin-bottom:1rem; color:#ccc;">Recent Invoices</h3>
        <?php if (empty($invoices)): ?>
            <p style="color:#888;">No invoices yet.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #333;">
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">#</th>
                    <th style="text-align:right; padding:0.8rem; color:#888; font-weight:500;">Amount</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Status</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Due Date</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Created</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:0.8rem; color:#6c5ce7;"><?= htmlspecialchars($inv['invoice_number'] ?? $inv['id']) ?></td>
                    <td style="padding:0.8rem; text-align:right; color:#fff;">$<?= number_format((float)$inv['total'], 2) ?></td>
                    <td style="padding:0.8rem;">
                        <span style="padding:0.2rem 0.6rem; border-radius:12px; font-size:0.8rem; font-weight:500;
                            background:<?= $inv['status'] === 'Paid' ? '#1a3a2a' : ($inv['status'] === 'Unpaid' ? '#3a1a1a' : '#2a2a1a') ?>;
                            color:<?= $inv['status'] === 'Paid' ? '#2ecc71' : ($inv['status'] === 'Unpaid' ? '#e74c3c' : '#f1c40f') ?>;">
                            <?= htmlspecialchars($inv['status']) ?>
                        </span>
                    </td>
                    <td style="padding:0.8rem; color:#aaa;"><?= $inv['due_date'] ? date('M j, Y', strtotime($inv['due_date'])) : '—' ?></td>
                    <td style="padding:0.8rem; color:#aaa;"><?= date('M j, Y', strtotime($inv['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        <div style="margin-top:1.5rem; padding:1rem; background:#1a1a2e; border:1px solid #333; border-radius:8px;">
            <p style="margin:0; color:#ccc;">Need to add funds? <a href="/billing" style="color:#6c5ce7;">Go to Billing Portal</a></p>
        </div>
    </div>

    <!-- Rank Tab -->
    <div class="tab-panel" id="tab-rank" style="display:none;">
        <?php if ($myRankData): ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; max-width:600px; margin-bottom:1.5rem;">
            <div style="background:#1a1a2e; padding:1.2rem; border-radius:8px; border:1px solid #333;">
                <div style="color:#888; font-size:0.8rem;">Current Rank</div>
                <div style="font-size:1.3rem; font-weight:700; color:#ffd700;"><?= htmlspecialchars($myRankData['badge_icon'] ?? '') ?> <?= htmlspecialchars($myRankData['rank_name']) ?></div>
                <div style="color:#666; font-size:0.8rem; margin-top:0.2rem;">Tier <?= (int)$myRankData['rank_tier'] ?> &middot; <?= htmlspecialchars(ucfirst($myRankData['rank_group'] ?? 'civilian')) ?></div>
            </div>
            <div style="background:#1a1a2e; padding:1.2rem; border-radius:8px; border:1px solid #333;">
                <div style="color:#888; font-size:0.8rem;">Experience Points</div>
                <div style="font-size:1.3rem; font-weight:700; color:#6c5ce7;"><?= number_format((int)($myRankData['xp'] ?? 0)) ?> XP</div>
                <div style="color:#666; font-size:0.8rem; margin-top:0.2rem;">Enlisted <?= date('M j, Y', strtotime($myRankData['joined_at'] ?? 'now')) ?></div>
            </div>
        </div>
        <div style="padding:1rem; background:#1a1a2e; border:1px solid #333; border-radius:8px;">
            <p style="margin:0 0 0.5rem; color:#ccc;">Your rank determines what you can access across the ecosystem.</p>
            <a href="/docs/field-manual#product-access" style="color:#6c5ce7;">View Product Access by Rank &rarr;</a>
        </div>
        <?php else: ?>
        <div style="text-align:center; padding:2rem;">
            <div style="font-size:2rem; margin-bottom:0.5rem;">&#x1F396;</div>
            <h3 style="color:#fff; margin-bottom:0.5rem;">Not Enlisted Yet</h3>
            <p style="color:#888; margin-bottom:1rem;">Join the GoSiteMe military institution to earn rank, XP, and unlock features across the ecosystem.</p>
            <a href="/enlist" style="padding:0.7rem 1.5rem; background:#6c5ce7; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">Enlist Now</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- AI Usage Tab -->
    <div class="tab-panel" id="tab-ai" style="display:none;">
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div style="background:#1a1a2e; padding:1rem; border-radius:8px; border:1px solid #333;">
                <div style="color:#888; font-size:0.8rem;">Plan</div>
                <div style="font-size:1.3rem; font-weight:700; color:#6c5ce7;"><?= htmlspecialchars($aiUsage['plan']) ?></div>
            </div>
            <div style="background:#1a1a2e; padding:1rem; border-radius:8px; border:1px solid #333;">
                <div style="color:#888; font-size:0.8rem;">Tokens Used (<?= date('F') ?>)</div>
                <div style="font-size:1.3rem; font-weight:700; color:#4ecdc4;"><?= number_format($aiUsage['tokens_used'] ?? 0) ?></div>
                <?php if (($aiUsage['tokens_included'] ?? 0) > 0): ?>
                <div style="margin-top:0.3rem;">
                    <div style="height:4px; background:#333; border-radius:2px; overflow:hidden;">
                        <div style="height:100%; background:#6c5ce7; width:<?= min(100, round(($aiUsage['tokens_used'] / $aiUsage['tokens_included']) * 100)) ?>%;"></div>
                    </div>
                    <div style="color:#666; font-size:0.75rem; margin-top:0.2rem;"><?= number_format($aiUsage['tokens_included']) ?> included</div>
                </div>
                <?php endif; ?>
            </div>
            <div style="background:#1a1a2e; padding:1rem; border-radius:8px; border:1px solid #333;">
                <div style="color:#888; font-size:0.8rem;">Overage Cost</div>
                <div style="font-size:1.3rem; font-weight:700; color:<?= $aiUsage['total_cost'] > 0 ? '#e74c3c' : '#2ecc71' ?>;">$<?= number_format($aiUsage['total_cost'], 4) ?></div>
            </div>
        </div>
        <div style="padding:1rem; background:#1a1a2e; border:1px solid #333; border-radius:8px;">
            <p style="margin:0; color:#ccc;">Launch <a href="/alfred-ide" style="color:#6c5ce7;">Alfred IDE</a> for full AI development tools, or explore <a href="/alfred" style="color:#6c5ce7;">Alfred AI</a> capabilities.</p>
        </div>
    </div>

    <!-- Security Tab -->
    <div class="tab-panel" id="tab-security" style="display:none;">
        <div style="max-width:500px;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 0; border-bottom:1px solid #222;">
                <div>
                    <div style="color:#fff; font-weight:500;">Two-Factor Authentication</div>
                    <div style="color:#888; font-size:0.85rem;">Extra layer of security for your account</div>
                </div>
                <span style="padding:0.3rem 0.8rem; border-radius:12px; font-size:0.8rem; font-weight:600;
                    background:<?= $has2FA ? '#1a3a2a' : '#3a1a1a' ?>;
                    color:<?= $has2FA ? '#2ecc71' : '#e74c3c' ?>;">
                    <?= $has2FA ? 'Enabled' : 'Disabled' ?>
                </span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 0; border-bottom:1px solid #222;">
                <div>
                    <div style="color:#fff; font-weight:500;">Account Status</div>
                    <div style="color:#888; font-size:0.85rem;">Current account standing</div>
                </div>
                <span style="color:<?= ($client['status'] ?? '') === 'Active' ? '#2ecc71' : '#e74c3c' ?>; font-weight:600;">
                    <?= htmlspecialchars($client['status'] ?? 'Unknown') ?>
                </span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 0; border-bottom:1px solid #222;">
                <div>
                    <div style="color:#fff; font-weight:500;">Password</div>
                    <div style="color:#888; font-size:0.85rem;">Change your login password</div>
                </div>
                <a href="/billing" style="color:#6c5ce7; text-decoration:none; font-size:0.9rem;">Change →</a>
            </div>
            <?php if ($isCommander): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 0; border-bottom:1px solid #222;">
                <div>
                    <div style="color:#ffd700; font-weight:500;">⭐ Commander Access</div>
                    <div style="color:#888; font-size:0.85rem;">Owner-level access confirmed (client_id 33)</div>
                </div>
                <span style="color:#2ecc71; font-weight:600;">Active</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Domains Tab -->
    <div class="tab-panel" id="tab-domains" style="display:none;">
        <?php if (empty($domains)): ?>
            <p style="color:#888;">No domains registered. <a href="/domains" style="color:#6c5ce7;">Register a domain</a></p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #333;">
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Domain</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Status</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Registrar</th>
                    <th style="text-align:left; padding:0.8rem; color:#888; font-weight:500;">Expires</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($domains as $d): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:0.8rem; color:#fff; font-weight:500;"><?= htmlspecialchars($d['domain']) ?></td>
                    <td style="padding:0.8rem;">
                        <span style="color:<?= $d['status'] === 'Active' ? '#2ecc71' : '#e74c3c' ?>;">
                            <?= htmlspecialchars($d['status']) ?>
                        </span>
                    </td>
                    <td style="padding:0.8rem; color:#aaa;"><?= htmlspecialchars($d['registrar'] ?? '—') ?></td>
                    <td style="padding:0.8rem; color:#aaa;"><?= $d['expiry_date'] ? date('M j, Y', strtotime($d['expiry_date'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div style="margin-top:2rem; padding:1.5rem; background:#1a1a2e; border:1px solid #333; border-radius:12px;">
    <h3 style="font-size:1rem; margin-bottom:1rem; color:#ccc;">Quick Actions</h3>
    <div style="display:flex; gap:0.8rem; flex-wrap:wrap;">
        <a href="/alfred-ide" style="padding:0.6rem 1.2rem; background:#6c5ce7; color:#fff; text-decoration:none; border-radius:8px; font-size:0.9rem;">Launch Alfred IDE</a>
        <a href="/dashboard" style="padding:0.6rem 1.2rem; background:#333; color:#fff; text-decoration:none; border-radius:8px; font-size:0.9rem;">Dashboard</a>
        <a href="/alfred" style="padding:0.6rem 1.2rem; background:#333; color:#fff; text-decoration:none; border-radius:8px; font-size:0.9rem;">Alfred AI</a>
        <a href="/billing" style="padding:0.6rem 1.2rem; background:#333; color:#fff; text-decoration:none; border-radius:8px; font-size:0.9rem;">Billing Portal</a>
        <?php if (!empty($tickets)): ?>
        <a href="/support" style="padding:0.6rem 1.2rem; background:#333; color:#fff; text-decoration:none; border-radius:8px; font-size:0.9rem;">Support Tickets (<?= count($tickets) ?>)</a>
        <?php endif; ?>
    </div>
</div>

</div>
</main>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
            b.style.color = '#aaa';
            b.style.borderBottomColor = 'transparent';
        });
        document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
        btn.classList.add('active');
        btn.style.color = '#fff';
        btn.style.borderBottomColor = '#6c5ce7';
        document.getElementById('tab-' + btn.dataset.tab).style.display = 'block';
    });
});
// Activate first tab
document.querySelector('.tab-btn.active').click();
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
