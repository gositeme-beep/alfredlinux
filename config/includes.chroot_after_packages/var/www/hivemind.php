<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(5);

if (empty($_SESSION['csrf_hivemind'])) $_SESSION['csrf_hivemind'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_hivemind'];

$msg = '';
$msgType = '';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { $msg = 'CSRF validation failed.'; $msgType = 'error'; }
    else {
        $action = $_POST['action'] ?? '';

        if ($action === 'register_node' && $userRankTier >= 9) {
            $code = trim($_POST['node_code'] ?? '');
            $name = trim($_POST['node_name'] ?? '');
            $type = $_POST['node_type'] ?? '';
            $ip   = trim($_POST['server_ip'] ?? '');
            $region = trim($_POST['server_region'] ?? '');
            $validTypes = ['primary','secondary','relay','edge'];
            if ($code === '' || $name === '' || !in_array($type, $validTypes, true) || $ip === '' || $region === '') {
                $msg = 'All fields are required and node type must be valid.'; $msgType = 'error';
            } elseif (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $msg = 'Invalid server IP address.'; $msgType = 'error';
            } else {
                $dup = $db->prepare("SELECT id FROM hivemind_nodes WHERE node_code = ?");
                $dup->execute([$code]);
                if ($dup->fetch()) { $msg = 'Node code already exists.'; $msgType = 'error'; }
                else {
                    $st = $db->prepare("INSERT INTO hivemind_nodes (node_code, node_name, node_type, server_ip, server_region, status, is_active, registered_at) VALUES (?,?,?,?,?,'offline',1,NOW())");
                    $st->execute([$code, $name, $type, $ip, $region]);
                    $msg = "Node {$code} registered."; $msgType = 'success';
                }
            }
        } elseif ($action === 'trigger_sync' && $userRankTier >= 7) {
            $src = trim($_POST['source_node'] ?? '');
            $tgt = trim($_POST['target_node'] ?? '');
            $stype = $_POST['sync_type'] ?? '';
            $validSync = ['full','incremental','rank','xp','territory','mission'];
            if ($src === '' || $tgt === '' || !in_array($stype, $validSync, true)) {
                $msg = 'Source, target, and valid sync type required.'; $msgType = 'error';
            } elseif ($src === $tgt) {
                $msg = 'Source and target must differ.'; $msgType = 'error';
            } else {
                $st = $db->prepare("INSERT INTO hivemind_sync_log (source_node, target_node, sync_type, records_synced, status, started_at) VALUES (?,?,?,0,'started',NOW())");
                $st->execute([$src, $tgt, $stype]);
                $msg = "Sync {$stype} initiated: {$src} → {$tgt}"; $msgType = 'success';
            }
        } elseif ($action === 'run_verification' && $userRankTier >= 7) {
            $nc = trim($_POST['ver_node'] ?? '');
            $vt = $_POST['ver_type'] ?? '';
            $validVer = ['rank','xp','territory','treaty','roster'];
            if ($nc === '' || !in_array($vt, $validVer, true)) {
                $msg = 'Node and verification type required.'; $msgType = 'error';
            } else {
                $cnt = $db->prepare("SELECT COUNT(*) AS c FROM hivemind_nodes WHERE node_code = ? AND is_active = 1");
                $cnt->execute([$nc]);
                $localHash = hash('sha256', $vt . ':' . ($cnt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0) . ':' . time());
                $st = $db->prepare("INSERT INTO hivemind_verification (node_code, verification_type, local_hash, remote_hash, match_status, verified_at) VALUES (?,?,?,'','pending',NOW())");
                $st->execute([$nc, $vt, $localHash]);
                $msg = "Verification ({$vt}) queued for node {$nc}."; $msgType = 'success';
            }
        }
        $_SESSION['csrf_hivemind'] = bin2hex(random_bytes(32));
        $csrf = $_SESSION['csrf_hivemind'];
    }
}

// Data queries
$nodes = $db->query("SELECT * FROM hivemind_nodes WHERE is_active = 1 ORDER BY node_type, node_name")->fetchAll(PDO::FETCH_ASSOC);
$nodeMap = []; foreach ($nodes as $n) $nodeMap[$n['node_code']] = $n;

$stats = [
    'total'   => count($nodes),
    'online'  => count(array_filter($nodes, fn($n) => $n['status'] === 'online')),
    'lastSync'=> $db->query("SELECT MAX(completed_at) FROM hivemind_sync_log WHERE status='completed'")->fetchColumn() ?: '—',
    'totalRec'=> (int)$db->query("SELECT COALESCE(SUM(records_synced),0) FROM hivemind_sync_log WHERE status='completed'")->fetchColumn(),
];
$verTotal = (int)$db->query("SELECT COUNT(*) FROM hivemind_verification")->fetchColumn();
$verPass  = (int)$db->query("SELECT COUNT(*) FROM hivemind_verification WHERE match_status='verified'")->fetchColumn();
$stats['verRate'] = $verTotal > 0 ? round($verPass / $verTotal * 100, 1) : 0;

$tab = $_GET['tab'] ?? 'overview';
$selNode = isset($_GET['node']) ? trim($_GET['node']) : null;

$syncLogs = $db->query("SELECT * FROM hivemind_sync_log ORDER BY started_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$verRecs  = $db->query("SELECT * FROM hivemind_verification ORDER BY verified_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

if ($selNode && isset($nodeMap[$selNode])) {
    $tab = 'node';
    $nodeDetail = $nodeMap[$selNode];
    $nodeSyncs = $db->prepare("SELECT * FROM hivemind_sync_log WHERE source_node = ? OR target_node = ? ORDER BY started_at DESC LIMIT 20");
    $nodeSyncs->execute([$selNode, $selNode]);
    $nodeSyncs = $nodeSyncs->fetchAll(PDO::FETCH_ASSOC);
    $nodeVers = $db->prepare("SELECT * FROM hivemind_verification WHERE node_code = ? ORDER BY verified_at DESC LIMIT 20");
    $nodeVers->execute([$selNode]);
    $nodeVers = $nodeVers->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hivemind Federation — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh}
.wrap{max-width:1280px;margin:0 auto;padding:24px 16px}
h1{font-size:1.75rem;font-weight:700;display:flex;align-items:center;gap:10px}
h1 i{color:#3b82f6}
.subtitle{color:#94a3b8;font-size:.85rem;margin-top:4px}
.stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:20px 0}
.stat{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:16px;text-align:center}
.stat .val{font-size:1.5rem;font-weight:700;color:#3b82f6}
.stat .lbl{font-size:.75rem;color:#94a3b8;margin-top:4px;text-transform:uppercase;letter-spacing:.5px}
.tabs{display:flex;gap:6px;margin:20px 0;flex-wrap:wrap}
.tabs a{padding:8px 18px;border-radius:8px;background:#1e293b;color:#94a3b8;text-decoration:none;font-size:.85rem;border:1px solid #334155;transition:.2s}
.tabs a:hover,.tabs a.active{background:#3b82f6;color:#fff;border-color:#3b82f6}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;margin-bottom:16px}
.card h2{font-size:1.1rem;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.card h2 i{color:#3b82f6}
.grid-nodes{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.node-card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:16px;cursor:pointer;transition:.15s;text-decoration:none;color:inherit;display:block}
.node-card:hover{border-color:#3b82f6;transform:translateY(-2px)}
.node-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.node-name{font-weight:600;font-size:1rem}
.badge{padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.badge-primary{background:#1e3a5f;color:#60a5fa}.badge-secondary{background:#3b1f5e;color:#c084fc}
.badge-relay{background:#164e3b;color:#34d399}.badge-edge{background:#78350f;color:#fbbf24}
.status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:6px}
.status-online{background:#22c55e;box-shadow:0 0 6px #22c55e;animation:pulse 2s infinite}
.status-offline{background:#6b7280}
.status-syncing{background:#3b82f6;box-shadow:0 0 6px #3b82f6;animation:pulse 1.5s infinite}
.status-error{background:#ef4444;box-shadow:0 0 6px #ef4444;animation:pulse 1s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.node-meta{font-size:.8rem;color:#94a3b8;margin-top:6px}
.node-meta span{display:block;margin-top:3px}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;padding:10px 12px;background:#0f172a;color:#94a3b8;font-weight:600;text-transform:uppercase;font-size:.7rem;letter-spacing:.5px;border-bottom:1px solid #334155}
td{padding:10px 12px;border-bottom:1px solid #1e293b}
tr:hover td{background:#1e293b}
.sync-full{color:#3b82f6}.sync-incremental{color:#a78bfa}.sync-rank{color:#f59e0b}.sync-xp{color:#34d399}.sync-territory{color:#f472b6}.sync-mission{color:#fb923c}
.st-started{color:#d97706}.st-completed{color:#059669}.st-failed{color:#dc2626}.st-conflict{color:#f59e0b}
.ver-verified{color:#059669}.ver-mismatch{color:#dc2626}.ver-pending{color:#d97706}
.hash{font-family:'Courier New',monospace;font-size:.75rem;color:#64748b}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{display:flex;flex-direction:column;gap:4px}
.form-group label{font-size:.8rem;color:#94a3b8;font-weight:600}
.form-group input,.form-group select{background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:8px 12px;border-radius:6px;font-size:.85rem}
.form-group input:focus,.form-group select:focus{outline:none;border-color:#3b82f6}
.btn{padding:8px 20px;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:.85rem;transition:.2s}
.btn-blue{background:#3b82f6;color:#fff}.btn-blue:hover{background:#2563eb}
.btn-green{background:#059669;color:#fff}.btn-green:hover{background:#047857}
.msg{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem;font-weight:500}
.msg-success{background:#052e16;border:1px solid #059669;color:#34d399}
.msg-error{background:#2d0a0a;border:1px solid #dc2626;color:#fca5a5}
.pk{font-family:'Courier New',monospace;background:#0f172a;padding:10px;border-radius:6px;font-size:.8rem;color:#64748b;word-break:break-all;border:1px solid #334155}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.detail-item{padding:12px;background:#0f172a;border-radius:8px;border:1px solid #334155}
.detail-item .dl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
.detail-item .dv{font-size:1rem;font-weight:600;margin-top:4px}
.back-link{color:#3b82f6;text-decoration:none;font-size:.85rem;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.back-link:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="wrap">
<h1><i class="fas fa-network-wired"></i> Hivemind Federation</h1>
<p class="subtitle">Multi-server mesh synchronization &amp; verification — Level 4 Infrastructure</p>

<?php if ($msg): ?>
<div class="msg msg-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Federation Stats -->
<div class="stats-bar">
<div class="stat"><div class="val"><?= $stats['total'] ?></div><div class="lbl">Total Nodes</div></div>
<div class="stat"><div class="val"><?= $stats['online'] ?></div><div class="lbl">Online</div></div>
<div class="stat"><div class="val"><?= htmlspecialchars($stats['lastSync']) ?></div><div class="lbl">Last Sync</div></div>
<div class="stat"><div class="val"><?= number_format($stats['totalRec']) ?></div><div class="lbl">Records Synced</div></div>
<div class="stat"><div class="val"><?= $stats['verRate'] ?>%</div><div class="lbl">Verification Pass</div></div>
</div>

<!-- Tabs -->
<div class="tabs">
<a href="?tab=overview" class="<?= $tab==='overview'?'active':'' ?>"><i class="fas fa-th-large"></i> Network</a>
<a href="?tab=sync" class="<?= $tab==='sync'?'active':'' ?>"><i class="fas fa-sync-alt"></i> Sync Log</a>
<a href="?tab=verify" class="<?= $tab==='verify'?'active':'' ?>"><i class="fas fa-check-double"></i> Verification</a>
<?php if ($userRankTier >= 7): ?>
<a href="?tab=ops" class="<?= $tab==='ops'?'active':'' ?>"><i class="fas fa-terminal"></i> Operations</a>
<?php endif; ?>
</div>

<?php if ($tab === 'overview'): ?>
<!-- Network Overview -->
<div class="grid-nodes">
<?php foreach ($nodes as $n): ?>
<a class="node-card" href="?node=<?= urlencode($n['node_code']) ?>">
<div class="node-header">
    <span class="node-name"><span class="status-dot status-<?= htmlspecialchars($n['status']) ?>"></span><?= htmlspecialchars($n['node_name']) ?></span>
    <span class="badge badge-<?= htmlspecialchars($n['node_type']) ?>"><?= htmlspecialchars($n['node_type']) ?></span>
</div>
<div class="node-meta">
    <span><i class="fas fa-fingerprint"></i> <?= htmlspecialchars($n['node_code']) ?></span>
    <span><i class="fas fa-globe"></i> <?= htmlspecialchars($n['server_region']) ?></span>
    <span><i class="fas fa-heartbeat"></i> <?= $n['last_heartbeat'] ? htmlspecialchars($n['last_heartbeat']) : 'Never' ?></span>
    <span><i class="fas fa-code-branch"></i> v<?= (int)$n['sync_version'] ?></span>
</div>
</a>
<?php endforeach; ?>
<?php if (empty($nodes)): ?>
<div class="card" style="grid-column:1/-1;text-align:center;color:#64748b"><i class="fas fa-satellite-dish"></i> No nodes registered yet.</div>
<?php endif; ?>
</div>

<?php elseif ($tab === 'node' && isset($nodeDetail)): ?>
<!-- Node Detail -->
<a href="?tab=overview" class="back-link"><i class="fas fa-arrow-left"></i> Back to Network</a>
<div class="card">
<h2><span class="status-dot status-<?= htmlspecialchars($nodeDetail['status']) ?>"></span> <?= htmlspecialchars($nodeDetail['node_name']) ?> <span class="badge badge-<?= htmlspecialchars($nodeDetail['node_type']) ?>"><?= htmlspecialchars($nodeDetail['node_type']) ?></span></h2>
<div class="detail-grid">
    <div class="detail-item"><div class="dl">Node Code</div><div class="dv"><?= htmlspecialchars($nodeDetail['node_code']) ?></div></div>
    <div class="detail-item"><div class="dl">Status</div><div class="dv" style="text-transform:capitalize"><?= htmlspecialchars($nodeDetail['status']) ?></div></div>
    <div class="detail-item"><div class="dl">Server IP</div><div class="dv"><?= htmlspecialchars($nodeDetail['server_ip']) ?></div></div>
    <div class="detail-item"><div class="dl">Region</div><div class="dv"><?= htmlspecialchars($nodeDetail['server_region']) ?></div></div>
    <div class="detail-item"><div class="dl">Last Heartbeat</div><div class="dv"><?= $nodeDetail['last_heartbeat'] ? htmlspecialchars($nodeDetail['last_heartbeat']) : '—' ?></div></div>
    <div class="detail-item"><div class="dl">Last Sync</div><div class="dv"><?= $nodeDetail['last_sync'] ? htmlspecialchars($nodeDetail['last_sync']) : '—' ?></div></div>
    <div class="detail-item"><div class="dl">Sync Version</div><div class="dv">v<?= (int)$nodeDetail['sync_version'] ?></div></div>
    <div class="detail-item"><div class="dl">Registered</div><div class="dv"><?= htmlspecialchars($nodeDetail['registered_at']) ?></div></div>
</div>
<?php if (!empty($nodeDetail['public_key'])): ?>
<div style="margin-top:12px"><span style="font-size:.8rem;color:#94a3b8;font-weight:600">Public Key</span>
<div class="pk"><?= htmlspecialchars(substr($nodeDetail['public_key'], 0, 8)) ?>••••••••<?= htmlspecialchars(substr($nodeDetail['public_key'], -8)) ?></div>
</div>
<?php endif; ?>
</div>

<?php if (!empty($nodeSyncs)): ?>
<div class="card">
<h2><i class="fas fa-sync-alt"></i> Recent Syncs</h2>
<div style="overflow-x:auto">
<table>
<tr><th>Type</th><th>Source</th><th>Target</th><th>Records</th><th>Status</th><th>Checksum</th><th>Started</th><th>Duration</th></tr>
<?php foreach ($nodeSyncs as $s): $dur = ($s['completed_at'] && $s['started_at']) ? (strtotime($s['completed_at']) - strtotime($s['started_at'])) . 's' : '—'; ?>
<tr>
<td><span class="sync-<?= htmlspecialchars($s['sync_type']) ?>"><?= htmlspecialchars($s['sync_type']) ?></span></td>
<td><?= htmlspecialchars($s['source_node']) ?></td><td><?= htmlspecialchars($s['target_node']) ?></td>
<td><?= number_format((int)$s['records_synced']) ?></td>
<td><span class="st-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
<td class="hash"><?= $s['checksum'] ? htmlspecialchars(substr($s['checksum'], 0, 12)) . '…' : '—' ?></td>
<td><?= htmlspecialchars($s['started_at']) ?></td><td><?= $dur ?></td>
</tr>
<?php endforeach; ?>
</table></div></div>
<?php endif; ?>

<?php if (!empty($nodeVers)): ?>
<div class="card">
<h2><i class="fas fa-check-double"></i> Verifications</h2>
<div style="overflow-x:auto">
<table>
<tr><th>Type</th><th>Local Hash</th><th>Remote Hash</th><th>Match</th><th>Verified</th></tr>
<?php foreach ($nodeVers as $v): ?>
<tr>
<td><?= htmlspecialchars($v['verification_type']) ?></td>
<td class="hash"><?= htmlspecialchars(substr($v['local_hash'], 0, 16)) ?>…</td>
<td class="hash"><?= $v['remote_hash'] ? htmlspecialchars(substr($v['remote_hash'], 0, 16)) . '…' : '—' ?></td>
<td><span class="ver-<?= htmlspecialchars($v['match_status']) ?>" style="font-weight:700"><?= htmlspecialchars($v['match_status']) ?></span></td>
<td><?= htmlspecialchars($v['verified_at']) ?></td>
</tr>
<?php endforeach; ?>
</table></div></div>
<?php endif; ?>

<?php elseif ($tab === 'sync'): ?>
<!-- Sync Log -->
<div class="card">
<h2><i class="fas fa-sync-alt"></i> Sync Log</h2>
<div style="overflow-x:auto">
<table>
<tr><th>ID</th><th>Type</th><th>Source</th><th>Target</th><th>Records</th><th>Status</th><th>Checksum</th><th>Error</th><th>Started</th><th>Duration</th></tr>
<?php foreach ($syncLogs as $s): $dur = ($s['completed_at'] && $s['started_at']) ? (strtotime($s['completed_at']) - strtotime($s['started_at'])) . 's' : '—'; ?>
<tr>
<td>#<?= (int)$s['id'] ?></td>
<td><span class="sync-<?= htmlspecialchars($s['sync_type']) ?>"><?= htmlspecialchars($s['sync_type']) ?></span></td>
<td><?= htmlspecialchars($s['source_node']) ?></td><td><?= htmlspecialchars($s['target_node']) ?></td>
<td><?= number_format((int)$s['records_synced']) ?></td>
<td><span class="st-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
<td class="hash"><?= $s['checksum'] ? htmlspecialchars(substr($s['checksum'], 0, 12)) . '…' : '—' ?></td>
<td style="color:#fca5a5;font-size:.8rem"><?= $s['error_message'] ? htmlspecialchars(mb_strimwidth($s['error_message'], 0, 60, '…')) : '—' ?></td>
<td><?= htmlspecialchars($s['started_at']) ?></td><td><?= $dur ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($syncLogs)): ?><tr><td colspan="10" style="text-align:center;color:#64748b">No sync records yet.</td></tr><?php endif; ?>
</table></div></div>

<?php elseif ($tab === 'verify'): ?>
<!-- Verification -->
<div class="card">
<h2><i class="fas fa-check-double"></i> Verification Records</h2>
<div style="overflow-x:auto">
<table>
<tr><th>ID</th><th>Node</th><th>Type</th><th>Local Hash</th><th>Remote Hash</th><th>Match</th><th>Verified</th></tr>
<?php foreach ($verRecs as $v): ?>
<tr>
<td>#<?= (int)$v['id'] ?></td>
<td><?= htmlspecialchars($v['node_code']) ?></td>
<td><?= htmlspecialchars($v['verification_type']) ?></td>
<td class="hash"><?= htmlspecialchars(substr($v['local_hash'], 0, 16)) ?>…</td>
<td class="hash"><?= $v['remote_hash'] ? htmlspecialchars(substr($v['remote_hash'], 0, 16)) . '…' : '—' ?></td>
<td><span class="ver-<?= htmlspecialchars($v['match_status']) ?>" style="font-weight:700"><?= htmlspecialchars(ucfirst($v['match_status'])) ?></span></td>
<td><?= htmlspecialchars($v['verified_at']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($verRecs)): ?><tr><td colspan="7" style="text-align:center;color:#64748b">No verification records yet.</td></tr><?php endif; ?>
</table></div></div>

<?php elseif ($tab === 'ops' && $userRankTier >= 7): ?>
<!-- Operations -->
<?php if ($userRankTier >= 9): ?>
<div class="card">
<h2><i class="fas fa-plus-circle"></i> Register Node <span class="badge badge-primary" style="font-size:.65rem;margin-left:8px">Flag Officers Only</span></h2>
<form method="POST">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
<input type="hidden" name="action" value="register_node">
<div class="form-grid">
    <div class="form-group"><label>Node Code</label><input name="node_code" required pattern="[A-Za-z0-9_-]+" maxlength="32" placeholder="e.g. GSM-EU-01"></div>
    <div class="form-group"><label>Node Name</label><input name="node_name" required maxlength="100" placeholder="e.g. Europe Primary"></div>
    <div class="form-group"><label>Node Type</label><select name="node_type" required><option value="">Select…</option><option value="primary">Primary</option><option value="secondary">Secondary</option><option value="relay">Relay</option><option value="edge">Edge</option></select></div>
    <div class="form-group"><label>Server IP</label><input name="server_ip" required maxlength="45" placeholder="e.g. 15.235.50.60"></div>
    <div class="form-group"><label>Server Region</label><input name="server_region" required maxlength="50" placeholder="e.g. ca-east-1"></div>
</div>
<div style="margin-top:14px"><button type="submit" class="btn btn-blue"><i class="fas fa-satellite-dish"></i> Register Node</button></div>
</form></div>
<?php endif; ?>

<div class="card">
<h2><i class="fas fa-sync-alt"></i> Trigger Sync <span class="badge badge-relay" style="font-size:.65rem;margin-left:8px">Officers+</span></h2>
<form method="POST">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
<input type="hidden" name="action" value="trigger_sync">
<div class="form-grid">
    <div class="form-group"><label>Source Node</label><select name="source_node" required><option value="">Select…</option><?php foreach ($nodes as $n): ?><option value="<?= htmlspecialchars($n['node_code']) ?>"><?= htmlspecialchars($n['node_name']) ?> (<?= htmlspecialchars($n['node_code']) ?>)</option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Target Node</label><select name="target_node" required><option value="">Select…</option><?php foreach ($nodes as $n): ?><option value="<?= htmlspecialchars($n['node_code']) ?>"><?= htmlspecialchars($n['node_name']) ?> (<?= htmlspecialchars($n['node_code']) ?>)</option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Sync Type</label><select name="sync_type" required><option value="">Select…</option><option value="full">Full</option><option value="incremental">Incremental</option><option value="rank">Rank</option><option value="xp">XP</option><option value="territory">Territory</option><option value="mission">Mission</option></select></div>
</div>
<div style="margin-top:14px"><button type="submit" class="btn btn-blue"><i class="fas fa-play"></i> Start Sync</button></div>
</form></div>

<div class="card">
<h2><i class="fas fa-check-double"></i> Run Verification <span class="badge badge-relay" style="font-size:.65rem;margin-left:8px">Officers+</span></h2>
<form method="POST">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
<input type="hidden" name="action" value="run_verification">
<div class="form-grid">
    <div class="form-group"><label>Node</label><select name="ver_node" required><option value="">Select…</option><?php foreach ($nodes as $n): ?><option value="<?= htmlspecialchars($n['node_code']) ?>"><?= htmlspecialchars($n['node_name']) ?> (<?= htmlspecialchars($n['node_code']) ?>)</option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Verification Type</label><select name="ver_type" required><option value="">Select…</option><option value="rank">Rank</option><option value="xp">XP</option><option value="territory">Territory</option><option value="treaty">Treaty</option><option value="roster">Roster</option></select></div>
</div>
<div style="margin-top:14px"><button type="submit" class="btn btn-green"><i class="fas fa-shield-alt"></i> Run Verification</button></div>
</form></div>
<?php endif; ?>

</div>
<script>
document.querySelectorAll('.node-card').forEach(c=>{c.style.position='relative'});
</script>
</body>
</html>
