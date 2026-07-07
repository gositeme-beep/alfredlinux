<?php
/**
 * Alfred Call Centre — Admin Only
 * Requires authenticated admin session (same gate as supreme-admin).
 */
$page_title = 'Alfred Call Centre — GoSiteMe';
$page_robots = 'noindex, nofollow';

include __DIR__ . '/includes/auth-gate.inc.php';

// ── Admin Access Only ───────────────────────────────────────────────
$supremeAdmins = ['gositeme@gmail.com'];
if (!$clientEmail || !in_array(strtolower($clientEmail), $supremeAdmins)) {
    header('Location: /dashboard.php');
    exit;
}

define('GOSITEME_API', true);
require_once __DIR__ . '/api/config.php';
$db = getDB();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS alfred_call_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(64) UNIQUE,
    client_id INT DEFAULT 0,
    caller_number VARCHAR(50),
    started_at DATETIME,
    ended_at DATETIME,
    duration_seconds INT DEFAULT 0,
    ended_reason VARCHAR(100),
    transcript MEDIUMTEXT,
    summary TEXT,
    success_evaluation VARCHAR(10),
    recording_url TEXT,
    cost_usd DECIMAL(8,4) DEFAULT 0,
    created_at DATETIME,
    INDEX idx_client (client_id),
    INDEX idx_started (started_at)
)");

// Stats
$stats = $db->query("SELECT
    COUNT(*) as total,
    SUM(duration_seconds) as total_secs,
    SUM(cost_usd) as total_cost,
    SUM(CASE WHEN success_evaluation='true' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN success_evaluation='false' THEN 1 ELSE 0 END) as unresolved,
    AVG(duration_seconds) as avg_duration
    FROM alfred_call_log WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch(PDO::FETCH_ASSOC);

// Recent calls
$calls = $db->query("
    SELECT l.*, c.firstname, c.lastname, c.email
    FROM alfred_call_log l
    LEFT JOIN clients c ON c.id = l.client_id
    ORDER BY l.started_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// Auth attempts
$authAttempts = $db->query("SELECT * FROM alfred_auth_attempts ORDER BY attempt_time DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);


// Callback security stats
try {
    $cbStats = $db->query("SELECT
        COUNT(*) as total_callbacks,
        SUM(CASE WHEN callback_status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN callback_status='failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN callback_status='pending' OR callback_status='calling' THEN 1 ELSE 0 END) as active,
        SUM(outbound_cost) as outbound_total,
        SUM(inbound_cost) as inbound_total
        FROM alfred_callbacks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch(PDO::FETCH_ASSOC);
    $recentCallbacks = $db->query("SELECT cb.*, c.firstname, c.lastname, c.email
        FROM alfred_callbacks cb
        LEFT JOIN clients c ON c.id = cb.client_id
        ORDER BY cb.created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cbStats = ['total_callbacks'=>0,'completed'=>0,'failed'=>0,'active'=>0,'outbound_total'=>0,'inbound_total'=>0];
    $recentCallbacks = [];
}
// Unpaid invoices for outbound
$unpaid = $db->query("SELECT c.id, c.firstname, c.lastname, c.phone, COUNT(i.id) as cnt, SUM(i.total) as total
    FROM clients c JOIN invoices i ON i.client_id=c.id
    WHERE i.status='Unpaid' AND c.phone!='' AND c.status='Active'
    GROUP BY c.id ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Expiring domains
$expiring = $db->query("SELECT c.id, c.firstname, c.phone, d.domain, d.expiry_date,
    DATEDIFF(d.expiry_date, CURDATE()) as days_left
    FROM clients c JOIN domains d ON d.client_id=c.id
    WHERE d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND d.status='Active' AND c.phone!='' AND c.status='Active'
    ORDER BY d.expiry_date ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

function fmtDur($s) {
    if (!$s) return '-';
    $m = floor($s/60); $sec = $s%60;
    return $m>0 ? "{$m}m {$sec}s" : "{$sec}s";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Alfred Call Centre — GoSiteMe</title>
<link rel="stylesheet" href="/assets/css/fonts.css">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--primary:#0074D9;--cyan:#00D4FF;--purple:#7D00FF;--dark:#0a0a14;--card:#1a1a2e;--text:#e0e0e0;--muted:#a8b2d1;--success:#10b981;--warning:#f59e0b;--danger:#ef4444}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0a0a14,#1a1a2e);min-height:100vh;color:var(--text);padding:32px}
h1{font-family:'Space Grotesk',sans-serif;font-size:2rem;background:linear-gradient(135deg,#00D4FF,#7D00FF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px}
.subtitle{color:var(--muted);margin-bottom:32px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:32px}
.stat{background:var(--card);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px}
.stat .val{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:var(--cyan)}
.stat .lbl{color:var(--muted);font-size:.85rem;margin-top:4px}
.card{background:var(--card);border:1px solid rgba(255,255,255,.08);border-radius:14px;margin-bottom:24px;overflow:hidden}
.card-header{padding:18px 24px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;align-items:center}
.card-header h3{font-size:1.1rem;font-weight:600}
.card-body{padding:0}
table{width:100%;border-collapse:collapse;font-size:.875rem}
th{padding:12px 16px;text-align:left;background:rgba(0,116,217,.1);color:#fff;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px}
td{padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.05);vertical-align:top}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(0,168,255,.04)}
.badge{display:inline-block;padding:3px 8px;border-radius:5px;font-size:.72rem;font-weight:600}
.badge-success{background:rgba(16,185,129,.2);color:var(--success)}
.badge-danger{background:rgba(239,68,68,.2);color:var(--danger)}
.badge-warning{background:rgba(245,158,11,.2);color:var(--warning)}
.badge-info{background:rgba(0,212,255,.2);color:var(--cyan)}
.badge-muted{background:rgba(255,255,255,.08);color:var(--muted)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s}
.btn-primary{background:linear-gradient(135deg,#0074D9,#00A8FF);color:#fff}
.btn-danger{background:linear-gradient(135deg,#c0392b,#ef4444);color:#fff}
.btn-sm{padding:5px 10px;font-size:.78rem}
.transcript-toggle{cursor:pointer;color:var(--cyan);font-size:.8rem;text-decoration:underline}
.transcript-box{display:none;background:#0a0a14;padding:12px;border-radius:8px;font-size:.78rem;line-height:1.6;color:var(--muted);max-height:200px;overflow-y:auto;margin-top:8px;white-space:pre-wrap}
.tabs{display:flex;gap:4px;padding:16px 24px 0;border-bottom:1px solid rgba(255,255,255,.08)}
.tab{padding:10px 20px;border-radius:8px 8px 0 0;cursor:pointer;color:var(--muted);font-size:.9rem;font-weight:500;border:none;background:none;transition:all .2s}
.tab.active{background:rgba(0,212,255,.1);color:var(--cyan)}
.tab-content{display:none;padding:24px}
.tab-content.active{display:block}
.outbound-form{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group label{font-size:.8rem;color:var(--muted);font-weight:500}
.form-group input,.form-group select{background:#0a0a14;border:1px solid rgba(255,255,255,.15);border-radius:8px;padding:9px 13px;color:#fff;font-size:.9rem;min-width:220px}
.quick-call{display:flex;align-items:center;gap:10px;padding:10px 14px;background:rgba(0,0,0,.2);border-radius:8px;margin-bottom:8px}
.quick-call .info{flex:1;font-size:.85rem}
.quick-call .name{font-weight:600}
.quick-call .detail{color:var(--muted);font-size:.78rem}
</style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>

<h1>🤖 Alfred Call Centre</h1>
<p class="subtitle">Last 30 days · <?= date('M j, Y') ?></p>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat"><div class="val"><?= (int)$stats['total'] ?></div><div class="lbl">Total Calls</div></div>
    <div class="stat"><div class="val"><?= (int)$stats['resolved'] ?></div><div class="lbl">Resolved ✅</div></div>
    <div class="stat"><div class="val" style="color:var(--warning)"><?= (int)$stats['unresolved'] ?></div><div class="lbl">Need Follow-up ⚠️</div></div>
    <div class="stat"><div class="val"><?= fmtDur((int)$stats['avg_duration']) ?></div><div class="lbl">Avg Duration</div></div>
    <div class="stat"><div class="val">$<?= number_format($stats['total_cost'], 2) ?></div><div class="lbl">Total Cost (30d)</div></div>
    <div class="stat"><div class="val"><?= $stats['total'] ? round($stats['resolved']/$stats['total']*100) : 0 ?>%</div><div class="lbl">Success Rate</div></div>
    <div class="stat"><div class="val" style="color:#a78bfa"><?= (int)($cbStats['total_callbacks'] ?? 0) ?></div><div class="lbl">Callbacks (30d)</div></div>
    <div class="stat"><div class="val" style="color:var(--success)"><?= (int)($cbStats['completed'] ?? 0) ?></div><div class="lbl">Callbacks ✅</div></div>
    <div class="stat"><div class="val" style="color:var(--cyan)">$<?= number_format(max(0, ($cbStats['inbound_total'] ?? 0) - ($cbStats['outbound_total'] ?? 0)), 2) ?></div><div class="lbl">Cost Savings 💰</div></div>
</div>

<!-- Main Card with Tabs -->
<div class="card">
    <div class="tabs">
        <button class="tab active" onclick="showTab('calls')"><i class="fas fa-phone"></i> Call Log</button>
        <button class="tab" onclick="showTab('outbound')"><i class="fas fa-phone-volume"></i> Outbound</button>
        <button class="tab" onclick="showTab('auth')"><i class="fas fa-shield"></i> Auth Log</button>
        <button class="tab" onclick="showTab('callbacks')"><i class="fas fa-phone-flip"></i> Callbacks</button>
    </div>

    <!-- CALL LOG TAB -->
    <div id="tab-calls" class="tab-content active">
        <?php if (!$calls): ?>
            <p style="color:var(--muted);text-align:center;padding:40px">No calls recorded yet. Calls will appear here after Alfred starts receiving them.</p>
        <?php else: ?>
        <table>
            <thead><tr>
                <th>Date / Time</th><th>Caller</th><th>Customer</th><th>Duration</th>
                <th>Outcome</th><th>Ended Reason</th><th>Cost</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($calls as $i => $call): ?>
            <tr>
                <td><?= $call['started_at'] ? date('M j g:ia', strtotime($call['started_at'])) : '-' ?></td>
                <td><?= htmlspecialchars($call['caller_number'] ?: 'Unknown') ?></td>
                <td>
                    <?php if ($call['firstname']): ?>
                        <strong><?= htmlspecialchars($call['firstname'].' '.$call['lastname']) ?></strong><br>
                        <small style="color:var(--muted)"><?= htmlspecialchars($call['email']) ?></small>
                    <?php else: ?>
                        <span style="color:var(--muted)">Unknown</span>
                    <?php endif; ?>
                </td>
                <td><?= fmtDur((int)$call['duration_seconds']) ?></td>
                <td>
                    <?php if ($call['success_evaluation'] === 'true'): ?>
                        <span class="badge badge-success">✅ Resolved</span>
                    <?php elseif ($call['success_evaluation'] === 'false'): ?>
                        <span class="badge badge-danger">⚠️ Follow-up</span>
                    <?php else: ?>
                        <span class="badge badge-muted">–</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-info"><?= htmlspecialchars($call['ended_reason'] ?: '–') ?></span></td>
                <td>$<?= number_format($call['cost_usd'], 3) ?></td>
                <td>
                    <?php if ($call['recording_url']): ?>
                        <a href="<?= htmlspecialchars($call['recording_url']) ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fas fa-play"></i> Listen
                        </a>
                    <?php endif; ?>
                    <?php if ($call['summary']): ?>
                        <span class="transcript-toggle" onclick="toggleTranscript(<?= $i ?>)">Summary</span>
                    <?php endif; ?>
                    <?php if ($call['summary']): ?>
                        <div id="tr<?= $i ?>" class="transcript-box"><?= htmlspecialchars($call['summary']) ?>

<?php if ($call['transcript']): ?><?= htmlspecialchars($call['transcript']) ?><?php endif; ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- OUTBOUND TAB -->
    <div id="tab-outbound" class="tab-content">
        <h4 style="margin-bottom:16px;color:var(--cyan)">📞 Trigger Outbound Call</h4>
        <div class="outbound-form" style="margin-bottom:28px">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="ob-phone" placeholder="+14504217379">
            </div>
            <div class="form-group">
                <label>Reason</label>
                <select id="ob-reason">
                    <option value="support">General Support</option>
                    <option value="callback">Return Callback</option>
                    <option value="unpaid_invoice">Unpaid Invoice</option>
                    <option value="domain_expiry">Domain Expiry</option>
                    <option value="welcome">Welcome Call</option>
                </select>
            </div>
            <div class="form-group">
                <label>Custom First Message (optional)</label>
                <input type="text" id="ob-message" placeholder="Leave blank for default" style="min-width:300px">
            </div>
            <button class="btn btn-primary" onclick="triggerCall()"><i class="fas fa-phone-volume"></i> Call Now</button>
        </div>
        <div id="ob-result" style="display:none;padding:12px;border-radius:8px;margin-bottom:24px"></div>

        <!-- Bulk actions -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
            <!-- Unpaid invoices -->
            <div>
                <h4 style="margin-bottom:12px;color:var(--warning)">⚠️ Overdue Invoices (7+ days)</h4>
                <?php if ($unpaid): foreach ($unpaid as $u): ?>
                <div class="quick-call">
                    <div class="info">
                        <div class="name"><?= htmlspecialchars($u['firstname'].' '.$u['lastname']) ?></div>
                        <div class="detail"><?= htmlspecialchars($u['phone']) ?> · <?= $u['cnt'] ?> invoice(s) · $<?= number_format($u['total'],2) ?></div>
                    </div>
                    <button class="btn btn-sm" style="background:rgba(245,158,11,.2);color:var(--warning)"
                        onclick="callClient(<?= $u['id'] ?>, 'unpaid_invoice')"><i class="fas fa-phone"></i> Call</button>
                </div>
                <?php endforeach; else: ?>
                <p style="color:var(--muted);font-size:.85rem">No overdue invoices 🎉</p>
                <?php endif; ?>
            </div>

            <!-- Expiring domains -->
            <div>
                <h4 style="margin-bottom:12px;color:var(--danger)">🔴 Expiring Domains (30 days)</h4>
                <?php if ($expiring): foreach ($expiring as $e): ?>
                <div class="quick-call">
                    <div class="info">
                        <div class="name"><?= htmlspecialchars($e['firstname']) ?> — <?= htmlspecialchars($e['domain']) ?></div>
                        <div class="detail"><?= htmlspecialchars($e['phone']) ?> · Expires in <?= $e['days_left'] ?> days (<?= $e['expiry_date'] ?>)</div>
                    </div>
                    <button class="btn btn-sm" style="background:rgba(239,68,68,.2);color:var(--danger)"
                        onclick="callClient(<?= $e['id'] ?>, 'domain_expiry')"><i class="fas fa-phone"></i> Call</button>
                </div>
                <?php endforeach; else: ?>
                <p style="color:var(--muted);font-size:.85rem">No domains expiring soon 🎉</p>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,.08)">
            <h4 style="margin-bottom:12px;color:var(--muted)">Bulk Campaigns</h4>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <button class="btn btn-primary" onclick="bulkCall('unpaid')"><i class="fas fa-file-invoice-dollar"></i> Call All Overdue (<?= count($unpaid) ?>)</button>
                <button class="btn" style="background:rgba(239,68,68,.2);color:var(--danger)" onclick="bulkCall('expiring')"><i class="fas fa-globe"></i> Call All Expiring (<?= count($expiring) ?>)</button>
            </div>
        </div>
    </div>

    <!-- AUTH LOG TAB -->
    <div id="tab-auth" class="tab-content">
        <table>
            <thead><tr><th>Time</th><th>Email</th><th>Caller Phone</th><th>Result</th><th>Method</th></tr></thead>
            <tbody>
            <?php foreach ($authAttempts as $a): ?>
            <tr>
                <td><?= date('M j g:ia', strtotime($a['attempt_time'])) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><?= htmlspecialchars($a['caller_phone'] ?: '–') ?></td>
                <td><?= $a['success'] ? '<span class="badge badge-success">✅ Passed</span>' : '<span class="badge badge-danger">❌ Failed</span>' ?></td>
                <td><span class="badge badge-info"><?= htmlspecialchars($a['method'] ?: '–') ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- CALLBACK LOG TAB -->
    <div id="tab-callbacks" class="tab-content">
        <?php if (!$recentCallbacks): ?>
            <p style="color:var(--muted);text-align:center;padding:40px">No callbacks recorded yet. Callbacks will appear here when callers request secure verification.</p>
        <?php else: ?>
        <table>
            <thead><tr>
                <th>Date</th><th>Customer</th><th>Caller #</th><th>Verified #</th>
                <th>Status</th><th>Tier</th><th>Outbound Cost</th><th>Inbound Saved</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recentCallbacks as $cb): ?>
            <tr>
                <td><?= $cb['created_at'] ? date('M j g:ia', strtotime($cb['created_at'])) : '-' ?></td>
                <td>
                    <?php if (!empty($cb['firstname'])): ?>
                        <strong><?= htmlspecialchars($cb['firstname'].' '.$cb['lastname']) ?></strong><br>
                        <small style="color:var(--muted)"><?= htmlspecialchars($cb['email']) ?></small>
                    <?php else: ?>
                        <span style="color:var(--muted)"><?= htmlspecialchars($cb['email'] ?: 'Unknown') ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($cb['caller_number'] ?: '–') ?></td>
                <td><?= htmlspecialchars($cb['verified_number'] ?: '–') ?></td>
                <td>
                    <?php
                    $statusMap = ['completed'=>'badge-success','connected'=>'badge-info','calling'=>'badge-warning','pending'=>'badge-warning','failed'=>'badge-danger','expired'=>'badge-muted'];
                    $cls = $statusMap[$cb['callback_status']] ?? 'badge-muted';
                    ?>
                    <span class="badge <?= $cls ?>"><?= ucfirst($cb['callback_status']) ?></span>
                </td>
                <td><span class="badge badge-info"><?= ucfirst($cb['security_tier'] ?? 'public') ?></span></td>
                <td>$<?= number_format($cb['outbound_cost'] ?? 0, 3) ?></td>
                <td>$<?= number_format($cb['inbound_cost'] ?? 0, 3) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/alfred-calls-engine.js"></script>
</body>
</html>
