<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(4);

if (empty($_SESSION['csrf_milapi'])) $_SESSION['csrf_milapi'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_milapi'];
$msg = '';
$newKeyPlain = '';

// Ensure tables exist
$db->exec("CREATE TABLE IF NOT EXISTS military_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    api_key_hash VARCHAR(64) NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    permissions JSON,
    rate_limit INT DEFAULT 100,
    is_active TINYINT DEFAULT 1,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(client_id), INDEX(api_key_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS military_api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NOT NULL,
    endpoint VARCHAR(255),
    method VARCHAR(10),
    request_data JSON,
    response_code INT,
    response_time_ms INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(512),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(api_key_id), INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { $msg = '<div class="alert alert-danger">Invalid CSRF token.</div>'; }
    else {
        $action = $_POST['action'] ?? '';

        if ($action === 'generate_key') {
            $keyName = trim($_POST['key_name'] ?? '');
            $rateLimit = max(1, min(10000, intval($_POST['rate_limit'] ?? 100)));
            $perms = $_POST['permissions'] ?? [];
            if (!is_array($perms)) $perms = [];
            $allowed = ['rank.read','rank.write','xp.read','xp.write','mission.read','mission.write','territory.read','unit.read','decoration.read'];
            $perms = array_values(array_intersect($perms, $allowed));

            if ($keyName === '') {
                $msg = '<div class="alert alert-danger">Key name is required.</div>';
            } else {
                $existing = $db->prepare("SELECT COUNT(*) FROM military_api_keys WHERE client_id=? AND is_active=1");
                $existing->execute([$clientId]);
                if ($existing->fetchColumn() >= 10) {
                    $msg = '<div class="alert alert-danger">Maximum 10 active keys allowed.</div>';
                } else {
                    $newKeyPlain = 'mil_' . bin2hex(random_bytes(32));
                    $hash = hash('sha256', $newKeyPlain);
                    $stmt = $db->prepare("INSERT INTO military_api_keys (client_id, api_key_hash, key_name, permissions, rate_limit) VALUES (?,?,?,?,?)");
                    $stmt->execute([$clientId, $hash, $keyName, json_encode($perms), $rateLimit]);
                    $msg = '<div class="alert alert-success">API key generated. Copy it now — it will not be shown again.</div>';
                }
            }
        }

        if ($action === 'revoke_key') {
            $keyId = intval($_POST['key_id'] ?? 0);
            $stmt = $db->prepare("UPDATE military_api_keys SET is_active=0 WHERE id=? AND client_id=?");
            $stmt->execute([$keyId, $clientId]);
            if ($stmt->rowCount()) $msg = '<div class="alert alert-warn">API key revoked.</div>';
            else $msg = '<div class="alert alert-danger">Key not found or already revoked.</div>';
        }
    }
}

// Load keys
$keysStmt = $db->prepare("SELECT * FROM military_api_keys WHERE client_id=? ORDER BY created_at DESC");
$keysStmt->execute([$clientId]);
$keys = $keysStmt->fetchAll(PDO::FETCH_ASSOC);

// Load logs for user's keys
$keyIds = array_column($keys, 'id');
$logs = [];
if ($keyIds) {
    $placeholders = implode(',', array_fill(0, count($keyIds), '?'));
    $logStmt = $db->prepare("SELECT l.*, k.key_name FROM military_api_logs l JOIN military_api_keys k ON l.api_key_id=k.id WHERE l.api_key_id IN ($placeholders) ORDER BY l.created_at DESC LIMIT 50");
    $logStmt->execute($keyIds);
    $logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Rate usage (last hour)
$rateUsage = [];
foreach ($keys as $k) {
    if (!$k['is_active']) continue;
    $rs = $db->prepare("SELECT COUNT(*) FROM military_api_logs WHERE api_key_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $rs->execute([$k['id']]);
    $rateUsage[$k['id']] = ['used' => (int)$rs->fetchColumn(), 'limit' => (int)$k['rate_limit'], 'name' => $k['key_name']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Military SDK / API — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0f172a;color:#e2e8f0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;min-height:100vh}
.wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
h1{font-size:1.8rem;font-weight:700;margin-bottom:4px}
h2{font-size:1.25rem;font-weight:600;margin-bottom:12px;color:#93c5fd}
h3{font-size:1rem;font-weight:600;margin-bottom:8px;color:#60a5fa}
.subtitle{color:#94a3b8;font-size:.9rem;margin-bottom:24px}
.card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:20px;margin-bottom:20px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.9rem}
.alert-success{background:#064e3b;border:1px solid #059669;color:#6ee7b7}
.alert-danger{background:#450a0a;border:1px solid #dc2626;color:#fca5a5}
.alert-warn{background:#451a03;border:1px solid #d97706;color:#fcd34d}
.badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:.75rem;font-weight:600}
.badge-active{background:#064e3b;color:#6ee7b7;border:1px solid #059669}
.badge-inactive{background:#450a0a;color:#fca5a5;border:1px solid #dc2626}
.badge-perm{background:#1e3a5f;color:#93c5fd;border:1px solid #3b82f6;margin:2px 3px;font-size:.7rem}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;padding:10px 8px;border-bottom:2px solid #334155;color:#94a3b8;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px}
td{padding:8px;border-bottom:1px solid #1e293b;vertical-align:middle}
tr:hover{background:#1e293b99}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:none;border-radius:6px;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-danger{background:#dc2626;color:#fff}.btn-danger:hover{background:#b91c1c}
.btn-sm{padding:4px 10px;font-size:.78rem}
.btn-copy{background:#334155;color:#e2e8f0}.btn-copy:hover{background:#475569}
input[type=text],input[type=number],select{background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:8px 12px;border-radius:6px;font-size:.85rem;width:100%}
input:focus,select:focus{outline:none;border-color:#3b82f6}
label{display:block;font-size:.8rem;color:#94a3b8;margin-bottom:4px;font-weight:500}
.form-row{margin-bottom:14px}
.check-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px}
.check-item{display:flex;align-items:center;gap:6px;font-size:.8rem;color:#cbd5e1}
.check-item input[type=checkbox]{accent-color:#3b82f6;width:15px;height:15px}
.key-reveal{background:#0f172a;border:1px solid #3b82f6;border-radius:8px;padding:14px;margin:12px 0;font-family:'Courier New',monospace;font-size:.85rem;word-break:break-all;position:relative}
.key-reveal .copy-overlay{position:absolute;top:8px;right:8px}
.resp-code{font-weight:700;font-family:monospace}
.rc-2{color:#6ee7b7}.rc-4{color:#fcd34d}.rc-5{color:#fca5a5}
.endpoint-block{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:14px;margin-bottom:12px}
.endpoint-block .method{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:700;margin-right:8px}
.method-get{background:#064e3b;color:#6ee7b7}.method-post{background:#1e3a5f;color:#93c5fd}
.endpoint-block .path{font-family:monospace;font-size:.85rem;color:#f1f5f9}
.endpoint-block .desc{color:#94a3b8;font-size:.8rem;margin:6px 0}
.endpoint-block .perm-req{font-size:.75rem;color:#60a5fa}
.code-block{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:14px;font-family:'Courier New',monospace;font-size:.8rem;white-space:pre-wrap;overflow-x:auto;color:#a5f3fc;position:relative;margin:8px 0}
.tab-bar{display:flex;gap:4px;margin-bottom:16px;border-bottom:2px solid #334155;padding-bottom:0}
.tab-btn{padding:8px 16px;border:none;background:transparent;color:#94a3b8;font-size:.85rem;font-weight:600;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s}
.tab-btn.active{color:#3b82f6;border-bottom-color:#3b82f6}
.tab-btn:hover{color:#e2e8f0}
.tab-panel{display:none}.tab-panel.active{display:block}
.rate-bar{height:8px;background:#334155;border-radius:4px;overflow:hidden;margin:4px 0}
.rate-fill{height:100%;border-radius:4px;transition:width .3s}
.rate-info{font-size:.78rem;color:#94a3b8;display:flex;justify-content:space-between}
.header-row{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.rank-badge{background:#1e3a5f;border:1px solid #3b82f6;color:#93c5fd;padding:4px 12px;border-radius:6px;font-size:.8rem}
@media(max-width:768px){.grid2{grid-template-columns:1fr}.check-grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="wrap">
<div class="header-row">
    <div>
        <h1><i class="fas fa-satellite-dish"></i> Military SDK / API</h1>
        <div class="subtitle">GoSiteMe Military Rank System — API Key Management & Documentation</div>
    </div>
    <div>
        <span class="rank-badge"><i class="fas fa-chevron-up"></i> <?=htmlspecialchars($userRankCode)?> — <?=htmlspecialchars($userName)?></span>
    </div>
</div>

<?=$msg?>

<?php if ($newKeyPlain): ?>
<div class="card" style="border-color:#3b82f6">
    <h3><i class="fas fa-key"></i> Your New API Key — Copy It Now</h3>
    <p style="color:#fcd34d;font-size:.8rem;margin-bottom:8px"><i class="fas fa-exclamation-triangle"></i> This key will NOT be shown again. Store it securely.</p>
    <div class="key-reveal">
        <span id="newKeyVal"><?=htmlspecialchars($newKeyPlain)?></span>
        <div class="copy-overlay"><button class="btn btn-copy btn-sm" onclick="copyKey()"><i class="fas fa-copy"></i> Copy</button></div>
    </div>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('keys')"><i class="fas fa-key"></i> My Keys</button>
    <button class="tab-btn" onclick="switchTab('docs')"><i class="fas fa-book"></i> API Docs</button>
    <button class="tab-btn" onclick="switchTab('logs')"><i class="fas fa-list-alt"></i> Usage Logs</button>
    <button class="tab-btn" onclick="switchTab('examples')"><i class="fas fa-code"></i> Code Examples</button>
</div>

<!-- TAB: Keys -->
<div class="tab-panel active" id="tab-keys">
<div class="grid2">
<div class="card">
    <h2><i class="fas fa-key"></i> My API Keys</h2>
    <?php if (!$keys): ?>
        <p style="color:#64748b;font-size:.85rem">No API keys yet. Generate one to get started.</p>
    <?php else: ?>
    <table>
    <thead><tr><th>Name</th><th>Created</th><th>Last Used</th><th>Rate</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($keys as $k):
        $perms = json_decode($k['permissions'] ?: '[]', true) ?: [];
    ?>
    <tr>
        <td>
            <strong><?=htmlspecialchars($k['key_name'])?></strong><br>
            <?php foreach ($perms as $p): ?><span class="badge badge-perm"><?=htmlspecialchars($p)?></span><?php endforeach; ?>
        </td>
        <td style="font-size:.78rem"><?=date('M j, Y', strtotime($k['created_at']))?></td>
        <td style="font-size:.78rem"><?=$k['last_used_at'] ? date('M j, H:i', strtotime($k['last_used_at'])) : '<span style="color:#64748b">Never</span>'?></td>
        <td><?=(int)$k['rate_limit']?>/hr</td>
        <td><?php if ($k['is_active']): ?><span class="badge badge-active">Active</span><?php else: ?><span class="badge badge-inactive">Revoked</span><?php endif; ?></td>
        <td>
            <?php if ($k['is_active']): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Revoke this key? This cannot be undone.')">
                <input type="hidden" name="csrf" value="<?=$csrf?>">
                <input type="hidden" name="action" value="revoke_key">
                <input type="hidden" name="key_id" value="<?=(int)$k['id']?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-ban"></i></button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2><i class="fas fa-plus-circle"></i> Generate New Key</h2>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?=$csrf?>">
        <input type="hidden" name="action" value="generate_key">
        <div class="form-row">
            <label>Key Name</label>
            <input type="text" name="key_name" placeholder="e.g. Production Bot" required maxlength="100">
        </div>
        <div class="form-row">
            <label>Rate Limit (requests/hour)</label>
            <input type="number" name="rate_limit" value="100" min="1" max="10000">
        </div>
        <div class="form-row">
            <label>Permissions</label>
            <div class="check-grid">
                <?php
                $permList = [
                    'rank.read'=>'Read Ranks','rank.write'=>'Write Ranks',
                    'xp.read'=>'Read XP','xp.write'=>'Write XP',
                    'mission.read'=>'Read Missions','mission.write'=>'Write Missions',
                    'territory.read'=>'Read Territory','unit.read'=>'Read Units',
                    'decoration.read'=>'Read Decorations'
                ];
                foreach ($permList as $val => $label): ?>
                <label class="check-item"><input type="checkbox" name="permissions[]" value="<?=$val?>"> <?=$label?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Generate API Key</button>
    </form>
</div>
</div>

<!-- Rate Limit Panel -->
<?php if ($rateUsage): ?>
<div class="card">
    <h2><i class="fas fa-tachometer-alt"></i> Rate Limit Status (Last Hour)</h2>
    <div class="grid2">
    <?php foreach ($rateUsage as $kid => $ru):
        $pct = $ru['limit'] > 0 ? min(100, round(($ru['used'] / $ru['limit']) * 100)) : 0;
        $color = $pct < 60 ? '#22c55e' : ($pct < 85 ? '#eab308' : '#ef4444');
    ?>
    <div style="margin-bottom:10px">
        <strong style="font-size:.85rem"><?=htmlspecialchars($ru['name'])?></strong>
        <div class="rate-bar"><div class="rate-fill" style="width:<?=$pct?>%;background:<?=$color?>"></div></div>
        <div class="rate-info"><span><?=$ru['used']?> used</span><span><?=$ru['limit']?> limit</span></div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
</div>

<!-- TAB: Docs -->
<div class="tab-panel" id="tab-docs">
<div class="card">
    <h2><i class="fas fa-book"></i> API Documentation</h2>
    <p style="color:#94a3b8;font-size:.85rem;margin-bottom:16px">Base URL: <code style="background:#0f172a;padding:2px 6px;border-radius:4px;color:#a5f3fc">https://root.com/api/military</code> — All requests require <code style="background:#0f172a;padding:2px 6px;border-radius:4px;color:#a5f3fc">Authorization: Bearer mil_YOUR_KEY</code></p>

    <?php
    $endpoints = [
        ['GET','/api/military/rank?client_id=X','Get user rank info','rank.read','Returns rank tier, code, title, XP, next rank threshold for the given client.'],
        ['POST','/api/military/xp','Award XP to a user','xp.write','Body: {"client_id": X, "amount": 100, "reason": "Mission complete"}. Returns new XP total and rank changes.'],
        ['GET','/api/military/missions','List active missions','mission.read','Returns all currently active missions with status, objectives, and deadlines.'],
        ['POST','/api/military/missions/assign','Assign a mission','mission.write','Body: {"mission_id": X, "client_id": Y}. Assigns a mission to a user. Returns assignment confirmation.'],
        ['GET','/api/military/territory','Territory status','territory.read','Returns all territories with control status, garrison strength, and resource output.'],
        ['GET','/api/military/units','Unit roster','unit.read','Returns all military units with member lists, CO assignment, and readiness rating.'],
        ['GET','/api/military/decorations?client_id=X','User decorations','decoration.read','Returns medals, ribbons, citations, and commendations awarded to the given client.'],
        ['GET','/api/military/leaderboard','XP leaderboard','rank.read','Returns top 50 users by XP with rank, name, and XP total. Supports ?limit=N parameter.'],
    ];
    foreach ($endpoints as $ep): ?>
    <div class="endpoint-block">
        <span class="method method-<?=strtolower($ep[0])?>"><?=$ep[0]?></span>
        <span class="path"><?=$ep[1]?></span>
        <div class="desc"><?=$ep[2]?></div>
        <div class="perm-req"><i class="fas fa-lock"></i> Required: <strong><?=$ep[3]?></strong></div>
        <div style="color:#64748b;font-size:.78rem;margin-top:4px"><?=$ep[4]?></div>
    </div>
    <?php endforeach; ?>

    <h3 style="margin-top:20px"><i class="fas fa-shield-alt"></i> Authentication</h3>
    <div class="endpoint-block">
        <p style="font-size:.85rem;color:#cbd5e1">Include your API key in the <code style="background:#0f172a;padding:2px 6px;border-radius:4px;color:#a5f3fc">Authorization</code> header:</p>
        <div class="code-block">Authorization: Bearer mil_your_api_key_here</div>
        <p style="font-size:.8rem;color:#94a3b8;margin-top:8px">Keys are scoped to the permissions you selected during generation. Requests to endpoints beyond your key's permissions will receive a <strong>403 Forbidden</strong> response.</p>
    </div>

    <h3><i class="fas fa-exclamation-circle"></i> Error Responses</h3>
    <div class="endpoint-block">
        <div class="code-block">{
  "error": true,
  "code": 403,
  "message": "Insufficient permissions: xp.write required"
}</div>
        <table style="margin-top:10px">
            <tr><td><strong>401</strong></td><td>Missing or invalid API key</td></tr>
            <tr><td><strong>403</strong></td><td>Insufficient permissions for this endpoint</td></tr>
            <tr><td><strong>404</strong></td><td>Resource not found</td></tr>
            <tr><td><strong>429</strong></td><td>Rate limit exceeded — retry after cooldown</td></tr>
            <tr><td><strong>500</strong></td><td>Internal server error</td></tr>
        </table>
    </div>
</div>
</div>

<!-- TAB: Logs -->
<div class="tab-panel" id="tab-logs">
<div class="card">
    <h2><i class="fas fa-list-alt"></i> API Usage Logs (Last 50 Requests)</h2>
    <?php if (!$logs): ?>
        <p style="color:#64748b;font-size:.85rem">No API requests logged yet.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
    <thead><tr><th>Key</th><th>Endpoint</th><th>Method</th><th>Status</th><th>Time</th><th>IP</th><th>Timestamp</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l):
        $rc = (int)$l['response_code'];
        $rcClass = $rc >= 500 ? 'rc-5' : ($rc >= 400 ? 'rc-4' : 'rc-2');
    ?>
    <tr>
        <td style="font-size:.78rem"><?=htmlspecialchars($l['key_name'])?></td>
        <td style="font-family:monospace;font-size:.78rem"><?=htmlspecialchars($l['endpoint'])?></td>
        <td><span class="method method-<?=strtolower($l['method'] ?? 'get')?>" style="font-size:.7rem"><?=htmlspecialchars($l['method'] ?? '-')?></span></td>
        <td><span class="resp-code <?=$rcClass?>"><?=$rc?></span></td>
        <td style="font-size:.78rem"><?=(int)$l['response_time_ms']?>ms</td>
        <td style="font-size:.75rem;color:#64748b"><?=htmlspecialchars($l['ip_address'] ?? '-')?></td>
        <td style="font-size:.75rem"><?=date('M j H:i:s', strtotime($l['created_at']))?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- TAB: Code Examples -->
<div class="tab-panel" id="tab-examples">
<div class="card">
    <h2><i class="fas fa-terminal"></i> cURL Examples</h2>

    <h3>Get User Rank</h3>
    <div class="code-block">curl -s -H "Authorization: Bearer mil_YOUR_KEY" \
  "https://root.com/api/military/rank?client_id=33"</div>

    <h3>Award XP</h3>
    <div class="code-block">curl -s -X POST \
  -H "Authorization: Bearer mil_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"client_id": 33, "amount": 250, "reason": "Mission complete"}' \
  "https://root.com/api/military/xp"</div>

    <h3>List Active Missions</h3>
    <div class="code-block">curl -s -H "Authorization: Bearer mil_YOUR_KEY" \
  "https://root.com/api/military/missions"</div>

    <h3>Get Leaderboard</h3>
    <div class="code-block">curl -s -H "Authorization: Bearer mil_YOUR_KEY" \
  "https://root.com/api/military/leaderboard?limit=10"</div>
</div>

<div class="card">
    <h2><i class="fab fa-js-square"></i> JavaScript (fetch) Examples</h2>

    <h3>Get User Rank</h3>
    <div class="code-block">const API_KEY = 'mil_YOUR_KEY';
const BASE = 'https://root.com/api/military';

const res = await fetch(`${BASE}/rank?client_id=33`, {
  headers: { 'Authorization': `Bearer ${API_KEY}` }
});
const data = await res.json();
console.log(data);</div>

    <h3>Award XP</h3>
    <div class="code-block">const res = await fetch(`${BASE}/xp`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${API_KEY}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    client_id: 33,
    amount: 250,
    reason: 'Mission complete'
  })
});
const data = await res.json();
console.log(data);</div>

    <h3>Assign Mission</h3>
    <div class="code-block">const res = await fetch(`${BASE}/missions/assign`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${API_KEY}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    mission_id: 12,
    client_id: 33
  })
});
const data = await res.json();
console.log(data);</div>

    <h3>Get Decorations</h3>
    <div class="code-block">const res = await fetch(`${BASE}/decorations?client_id=33`, {
  headers: { 'Authorization': `Bearer ${API_KEY}` }
});
const medals = await res.json();
console.log(medals);</div>
</div>
</div>

</div><!-- .wrap -->

<script>
function switchTab(id) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    document.querySelector('[onclick="switchTab(\'' + id + '\')"]').classList.add('active');
}

function copyKey() {
    const val = document.getElementById('newKeyVal').textContent;
    navigator.clipboard.writeText(val).then(() => {
        const btn = document.querySelector('.copy-overlay .btn');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i> Copy', 2000);
    });
}
</script>
</body>
</html>