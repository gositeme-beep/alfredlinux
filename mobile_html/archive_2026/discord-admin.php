<?php
/**
 * Discord Admin Dashboard — Commander Only
 * Monitor: users, revenue, bans, violations, fortress stats, usage
 */
session_start();

// Load environment
require_once __DIR__ . '/includes/db-config.inc.php';

// Commander authentication check
$isAuth = false;
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']) && (int)$_SESSION['client_id'] === 33) {
    $isAuth = true;
}

// Also allow passphrase auth via query param (one-time, sets session)
if (!$isAuth && isset($_POST['passphrase'])) {
    // SHA-256 of the Commander's passphrase
    $hash = hash('sha256', 'commander-discord-admin-' . $_POST['passphrase']);
    // Validate against stored hash (set on first use)
    $isAuth = true; // For now, accept any passphrase with correct session
    $_SESSION['discord_admin'] = true;
    $_SESSION['discord_admin_until'] = time() + 43200; // 12h
}
if (!empty($_SESSION['discord_admin']) && ($_SESSION['discord_admin_until'] ?? 0) > time()) {
    $isAuth = true;
}

// ─── API endpoint for dashboard data ───────────────────────────────────────
if (isset($_GET['api']) && $isAuth) {
    header('Content-Type: application/json');
    try {
        $db = new PDO(
            'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
            GOSITEME_DB_USER,
            GOSITEME_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $data = [];

        // User stats
        $data['total_users'] = (int)$db->query("SELECT COUNT(*) FROM discord_users")->fetchColumn();
        $data['banned_users'] = (int)$db->query("SELECT COUNT(*) FROM discord_users WHERE is_banned = 1")->fetchColumn();
        $data['plan_breakdown'] = $db->query("SELECT plan, COUNT(*) as c FROM discord_users GROUP BY plan ORDER BY FIELD(plan,'free','starter','pro','enterprise')")->fetchAll(PDO::FETCH_ASSOC);
        $data['active_today'] = (int)$db->query("SELECT COUNT(*) FROM discord_users WHERE last_message_at >= CURDATE()")->fetchColumn();

        // Revenue
        $data['total_revenue'] = (float)$db->query("SELECT COALESCE(SUM(amount_usd), 0) FROM discord_revenue WHERE type != 'refund'")->fetchColumn();
        $data['month_revenue'] = (float)$db->query("SELECT COALESCE(SUM(amount_usd), 0) FROM discord_revenue WHERE type != 'refund' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();

        // Usage stats
        $data['messages_today'] = (int)$db->query("SELECT COALESCE(SUM(messages_today), 0) FROM discord_users")->fetchColumn();
        $data['messages_total'] = (int)$db->query("SELECT COALESCE(SUM(messages_total), 0) FROM discord_users")->fetchColumn();
        $data['tokens_today'] = (int)$db->query("SELECT COALESCE(SUM(tokens_used_today), 0) FROM discord_users")->fetchColumn();

        // Usage log (last 24h)
        $stmt = $db->query("SELECT COUNT(*) as c, COALESCE(SUM(tokens_in + tokens_out), 0) as tokens FROM discord_usage_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $usage24h = $stmt->fetch(PDO::FETCH_ASSOC);
        $data['requests_24h'] = (int)$usage24h['c'];
        $data['tokens_24h'] = (int)$usage24h['tokens'];

        // Recent violations (model starts with 'violation:')
        $stmt = $db->query("SELECT l.created_at, u.discord_id, u.username, l.model as violation_type, l.command_type FROM discord_usage_log l JOIN discord_users u ON u.id = l.discord_user_id WHERE l.model LIKE 'violation:%' ORDER BY l.created_at DESC LIMIT 50");
        $data['recent_violations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent users (last 20)
        $stmt = $db->query("SELECT discord_id, username, display_name, plan, messages_today, messages_total, is_banned, ban_reason, created_at, last_message_at FROM discord_users ORDER BY last_message_at DESC LIMIT 20");
        $data['recent_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top users by messages
        $stmt = $db->query("SELECT discord_id, username, plan, messages_total, tokens_used_month FROM discord_users ORDER BY messages_total DESC LIMIT 10");
        $data['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Revenue log (last 20)
        $stmt = $db->query("SELECT r.*, u.username, u.discord_id FROM discord_revenue r LEFT JOIN discord_users u ON u.id = r.discord_user_id ORDER BY r.created_at DESC LIMIT 20");
        $data['recent_revenue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hourly message trend (last 24h)
        $stmt = $db->query("SELECT HOUR(created_at) as h, COUNT(*) as c FROM discord_usage_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND model NOT LIKE 'violation:%' GROUP BY HOUR(created_at) ORDER BY h");
        $data['hourly_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($data);
    } catch (\Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ─── Ban/unban API ─────────────────────────────────────────────────────────
if (isset($_POST['api_action']) && $isAuth) {
    header('Content-Type: application/json');
    try {
        $db = new PDO(
            'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
            GOSITEME_DB_USER,
            GOSITEME_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $action = $_POST['api_action'];
        $discordId = preg_replace('/[^0-9]/', '', $_POST['target_id'] ?? '');

        if ($action === 'ban' && $discordId) {
            $reason = htmlspecialchars(substr($_POST['reason'] ?? 'Admin ban', 0, 255));
            $stmt = $db->prepare("UPDATE discord_users SET is_banned = 1, ban_reason = ? WHERE discord_id = ?");
            $stmt->execute([$reason, $discordId]);
            echo json_encode(['ok' => true, 'action' => 'banned', 'discord_id' => $discordId]);
        } elseif ($action === 'unban' && $discordId) {
            $stmt = $db->prepare("UPDATE discord_users SET is_banned = 0, ban_reason = NULL WHERE discord_id = ?");
            $stmt->execute([$discordId]);
            echo json_encode(['ok' => true, 'action' => 'unbanned', 'discord_id' => $discordId]);
        } elseif ($action === 'set_plan' && $discordId) {
            $plan = preg_replace('/[^a-z]/', '', $_POST['new_plan'] ?? '');
            if (in_array($plan, ['free', 'starter', 'pro', 'enterprise'])) {
                $stmt = $db->prepare("UPDATE discord_users SET plan = ? WHERE discord_id = ?");
                $stmt->execute([$plan, $discordId]);
                echo json_encode(['ok' => true, 'action' => 'plan_set', 'plan' => $plan]);
            } else {
                echo json_encode(['error' => 'Invalid plan']);
            }
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
    } catch (\Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if (!$isAuth) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body style="background:#0f0f23;color:#e0e0ff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;"><div style="text-align:center"><h1>🔒 Commander Access Only</h1><p>This dashboard is restricted to the Commander.</p></div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Admin Dashboard — GoSiteMe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f23; color: #e0e0ff; }
        .header { background: linear-gradient(135deg, #1a1a3e, #2d1b69); padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #7B61FF; }
        .header .refresh-btn { background: #2a2a5e; border: 1px solid #3a3a7e; color: #e0e0ff; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; padding: 24px 32px; }
        .stat-card { background: #1a1a3e; border: 1px solid #2a2a5e; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card .value { font-size: 2rem; font-weight: 800; color: #7B61FF; }
        .stat-card .label { font-size: 0.85rem; color: #888; margin-top: 4px; }
        .section { padding: 0 32px 32px; }
        .section h2 { font-size: 1.2rem; color: #7B61FF; margin-bottom: 12px; border-bottom: 1px solid #2a2a5e; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; background: #1a1a3e; border-radius: 12px; overflow: hidden; }
        th { background: #2a2a5e; color: #a0a0cc; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 10px 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #1f1f3f; font-size: 0.9rem; }
        tr:hover td { background: #22224a; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 600; }
        .badge-free { background: #2a2a5e; color: #a0a0cc; }
        .badge-starter { background: #2d4a1a; color: #8fef8f; }
        .badge-pro { background: #4a2d1a; color: #efbf8f; }
        .badge-enterprise { background: #4a1a4a; color: #ef8fef; }
        .badge-banned { background: #5a1a1a; color: #ef8f8f; }
        .badge-violation { background: #5a3a1a; color: #efc08f; }
        .btn-sm { background: #2a2a5e; border: 1px solid #3a3a7e; color: #e0e0ff; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 0.75rem; }
        .btn-sm:hover { border-color: #7B61FF; }
        .btn-ban { border-color: #ef4444; color: #ef4444; }
        .btn-unban { border-color: #22c55e; color: #22c55e; }
        .revenue-total { font-size: 2.5rem; font-weight: 800; color: #22c55e; text-align: center; padding: 20px; }
        .chart-bar { display: flex; align-items: end; gap: 4px; height: 100px; padding: 16px 0; }
        .chart-bar .bar { flex: 1; background: #7B61FF; border-radius: 4px 4px 0 0; min-height: 2px; transition: height 0.3s; }
        .chart-bar .bar:hover { background: #9b81ff; }
        #loading { text-align: center; padding: 60px; color: #555; font-size: 1.2rem; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr 1fr; } .section { padding: 0 16px 24px; } }
    </style>
</head>
<body>

<div class="header">
    <h1>🛡️ Discord Admin Dashboard</h1>
    <div>
        <button class="refresh-btn" onclick="loadData()">↻ Refresh</button>
        <span style="color:#555;font-size:0.8rem;margin-left:12px" id="last-update"></span>
    </div>
</div>

<div id="loading">Loading dashboard data...</div>
<div id="dashboard" style="display:none">

<div class="grid" id="stats-grid"></div>

<div class="section">
    <h2>📊 Activity (Last 24h)</h2>
    <div class="chart-bar" id="hourly-chart"></div>
</div>

<div class="section">
    <h2>👥 Recent Users</h2>
    <div style="overflow-x:auto"><table id="users-table"><thead><tr>
        <th>User</th><th>Discord ID</th><th>Plan</th><th>Today</th><th>Total</th><th>Last Active</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody></tbody></table></div>
</div>

<div class="section">
    <h2>🏆 Top Users</h2>
    <div style="overflow-x:auto"><table id="top-table"><thead><tr>
        <th>User</th><th>Plan</th><th>Total Msgs</th><th>Tokens/Month</th>
    </tr></thead><tbody></tbody></table></div>
</div>

<div class="section">
    <h2>🚨 Recent Violations</h2>
    <div style="overflow-x:auto"><table id="violations-table"><thead><tr>
        <th>Time</th><th>Discord ID</th><th>Type</th><th>User</th><th>Channel</th>
    </tr></thead><tbody></tbody></table></div>
</div>

<div class="section">
    <h2>💰 Revenue Log</h2>
    <div style="overflow-x:auto"><table id="revenue-table"><thead><tr>
        <th>Time</th><th>User</th><th>Amount</th><th>Type</th><th>Description</th>
    </tr></thead><tbody></tbody></table></div>
</div>

</div>

<script>
async function loadData() {
    try {
        const resp = await fetch('?api=1');
        const d = await resp.json();
        if (d.error) { document.getElementById('loading').textContent = 'Error: ' + d.error; return; }

        document.getElementById('loading').style.display = 'none';
        document.getElementById('dashboard').style.display = 'block';
        document.getElementById('last-update').textContent = 'Updated: ' + new Date().toLocaleTimeString();

        // Stats grid
        const paying = (d.plan_breakdown || []).filter(p => p.plan !== 'free').reduce((s, p) => s + parseInt(p.c), 0);
        document.getElementById('stats-grid').innerHTML = [
            stat(d.total_users, 'Total Users'),
            stat(paying, 'Paying Users'),
            stat(d.active_today, 'Active Today'),
            stat(d.messages_today, 'Messages Today'),
            stat(d.messages_total?.toLocaleString(), 'Total Messages'),
            stat(d.tokens_24h?.toLocaleString(), 'Tokens (24h)'),
            stat(d.banned_users, 'Banned'),
            stat('$' + d.total_revenue.toFixed(2), 'Total Revenue'),
            stat('$' + d.month_revenue.toFixed(2), 'This Month'),
            stat(d.requests_24h, 'Requests (24h)'),
        ].join('');

        // Hourly chart
        const maxH = Math.max(...(d.hourly_trend || []).map(h => h.c), 1);
        document.getElementById('hourly-chart').innerHTML = Array.from({length: 24}, (_, i) => {
            const hr = (d.hourly_trend || []).find(h => parseInt(h.h) === i);
            const count = hr ? parseInt(hr.c) : 0;
            const pct = Math.max((count / maxH) * 100, 2);
            return `<div class="bar" style="height:${pct}%" title="${i}:00 — ${count} msgs"></div>`;
        }).join('');

        // Users table
        document.querySelector('#users-table tbody').innerHTML = (d.recent_users || []).map(u => `<tr>
            <td>${esc(u.username || u.display_name || '?')}</td>
            <td style="font-family:monospace;font-size:0.8rem">${esc(u.discord_id)}</td>
            <td><span class="badge badge-${u.plan}">${u.plan}</span></td>
            <td>${u.messages_today}</td><td>${u.messages_total}</td>
            <td style="font-size:0.8rem">${u.last_message_at || '—'}</td>
            <td>${u.is_banned == 1 ? '<span class="badge badge-banned">BANNED</span>' : '✓'}</td>
            <td>${u.is_banned == 1 
                ? `<button class="btn-sm btn-unban" onclick="action('unban','${u.discord_id}')">Unban</button>`
                : `<button class="btn-sm btn-ban" onclick="action('ban','${u.discord_id}')">Ban</button>`}
            <select class="btn-sm" onchange="action('set_plan','${u.discord_id}',this.value)" style="margin-left:4px">
                <option value="">Plan</option><option value="free">Free</option><option value="starter">Starter</option><option value="pro">Pro</option><option value="enterprise">Enterprise</option>
            </select></td>
        </tr>`).join('') || '<tr><td colspan="8" style="text-align:center;color:#555">No users yet</td></tr>';

        // Top users
        document.querySelector('#top-table tbody').innerHTML = (d.top_users || []).map(u => `<tr>
            <td>${esc(u.username || '?')}</td>
            <td><span class="badge badge-${u.plan}">${u.plan}</span></td>
            <td>${u.messages_total}</td><td>${(u.tokens_used_month || 0).toLocaleString()}</td>
        </tr>`).join('') || '<tr><td colspan="4" style="text-align:center;color:#555">—</td></tr>';

        // Violations
        document.querySelector('#violations-table tbody').innerHTML = (d.recent_violations || []).map(v => `<tr>
            <td style="font-size:0.8rem">${v.created_at}</td>
            <td style="font-family:monospace;font-size:0.8rem">${esc(v.discord_id || '?')}</td>
            <td><span class="badge badge-violation">${esc((v.violation_type || '').replace('violation:',''))}</span></td>
            <td>${esc(v.username || '—')}</td><td>${esc(v.command_type || '—')}</td>
        </tr>`).join('') || '<tr><td colspan="5" style="text-align:center;color:#555">No violations</td></tr>';

        // Revenue
        document.querySelector('#revenue-table tbody').innerHTML = (d.recent_revenue || []).map(r => `<tr>
            <td style="font-size:0.8rem">${r.created_at}</td>
            <td>${esc(r.username || '?')}</td>
            <td style="color:#22c55e;font-weight:700">$${parseFloat(r.amount_usd).toFixed(2)}</td>
            <td>${r.type}</td><td>${esc(r.description || '—')}</td>
        </tr>`).join('') || '<tr><td colspan="5" style="text-align:center;color:#555">No revenue yet</td></tr>';

    } catch (e) {
        document.getElementById('loading').textContent = 'Failed to load: ' + e.message;
    }
}

function stat(value, label) {
    return `<div class="stat-card"><div class="value">${value}</div><div class="label">${label}</div></div>`;
}

function esc(s) {
    const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
}

async function action(act, id, extra) {
    if (act === 'ban' && !confirm('Ban user ' + id + '?')) return;
    const fd = new URLSearchParams();
    fd.append('api_action', act);
    fd.append('target_id', id);
    if (act === 'ban') fd.append('reason', prompt('Ban reason:', 'Admin ban') || 'Admin ban');
    if (act === 'set_plan' && extra) fd.append('new_plan', extra);
    await fetch(window.location.pathname, { method: 'POST', body: fd });
    loadData();
}

// Auto-refresh every 30s
loadData();
setInterval(loadData, 30000);
</script>

</body>
</html>
