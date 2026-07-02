<?php
/**
 * ═══════════════════════════════════════════
 *  Signal Corps & Electronic Warfare — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_sg'])) $_SESSION['csrf_sg'] = bin2hex(random_bytes(32));
requireRank(2);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$isNCO       = ($userRankTier >= 4) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS signal_networks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    network_code VARCHAR(30) NOT NULL,
    network_name VARCHAR(120) NOT NULL,
    network_type ENUM('tactical','strategic','emergency','satellite') NOT NULL DEFAULT 'tactical',
    encryption_algo VARCHAR(60) DEFAULT 'AES-256-GCM',
    status ENUM('operational','degraded','offline','compromised') DEFAULT 'operational',
    coverage TEXT DEFAULT NULL,
    capacity INT DEFAULT 100,
    current_load INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS signal_ew_ops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    op_code VARCHAR(20) NOT NULL,
    op_type ENUM('jamming','spoofing','direction_finding','hardening') NOT NULL DEFAULT 'jamming',
    target TEXT DEFAULT NULL,
    status ENUM('planned','active','completed','countered') DEFAULT 'planned',
    initiated_by INT NOT NULL,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    effectiveness_pct INT DEFAULT 0,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS signal_crypto_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_id_public VARCHAR(40) NOT NULL,
    algorithm VARCHAR(60) DEFAULT 'AES-256-GCM',
    key_type ENUM('symmetric','asymmetric','session') DEFAULT 'symmetric',
    issued_to INT DEFAULT NULL,
    status ENUM('active','expired','revoked','compromised') DEFAULT 'active',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS signal_relay_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relay_node VARCHAR(60) NOT NULL,
    relay_type ENUM('ground','satellite','mesh') NOT NULL DEFAULT 'ground',
    status ENUM('online','offline','degraded') DEFAULT 'online',
    latency_ms INT DEFAULT 0,
    uptime_pct DECIMAL(5,2) DEFAULT 99.99,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS signal_intercepts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intercept_type ENUM('signal','data','metadata') NOT NULL DEFAULT 'signal',
    source TEXT DEFAULT NULL,
    classification ENUM('confidential','secret','top_secret') DEFAULT 'confidential',
    content_summary TEXT NOT NULL,
    intercepted_by INT NOT NULL,
    intercepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    analyzed BOOLEAN DEFAULT FALSE,
    intel_report_id INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed networks ──
$nc = $db->query("SELECT COUNT(*) FROM signal_networks")->fetchColumn();
if ($nc == 0) {
    $nets = [
        ['NET-TAC', 'MIL-NET Tactical', 'tactical', 'AES-256-GCM', 'Front-line encrypted comms'],
        ['NET-STR', 'STRATCOM Strategic', 'strategic', 'AES-256-GCM', 'Command-level strategic network'],
        ['NET-EMR', 'GUARDIAN Emergency', 'emergency', 'ChaCha20-Poly1305', 'Emergency broadcast & civil defense'],
        ['NET-SAT', 'SKYLINK Satellite', 'satellite', 'AES-256-GCM', 'SPACECOM satellite relay uplink']
    ];
    $ns = $db->prepare("INSERT INTO signal_networks (network_code, network_name, network_type, encryption_algo, coverage) VALUES (?,?,?,?,?)");
    foreach ($nets as $n) $ns->execute($n);
}

// ── Seed relays ──
$rc = $db->query("SELECT COUNT(*) FROM signal_relay_status")->fetchColumn();
if ($rc == 0) {
    $relays = [
        ['RELAY-HQ', 'ground', 12], ['RELAY-EAST', 'ground', 25], ['RELAY-WEST', 'ground', 31],
        ['SAT-RELAY-1', 'satellite', 180], ['SAT-RELAY-2', 'satellite', 210],
        ['MESH-NODE-1', 'mesh', 8], ['MESH-NODE-2', 'mesh', 5]
    ];
    $rs = $db->prepare("INSERT INTO signal_relay_status (relay_node, relay_type, latency_ms) VALUES (?,?,?)");
    foreach ($relays as $r) $rs->execute($r);
}

$csrf = $_SESSION['csrf_sg'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_network' && $isFlag) {
            $name = trim($_POST['net_name'] ?? '');
            $type = $_POST['net_type'] ?? 'tactical';
            $algo = trim($_POST['encryption'] ?? 'AES-256-GCM');
            $cov  = trim($_POST['coverage'] ?? '');
            $cap  = (int)($_POST['capacity'] ?? 100);
            $validNT = ['tactical','strategic','emergency','satellite'];
            if ($name === '' || !in_array($type, $validNT, true)) {
                $msg = 'Name and valid type required.'; $msgType = 'error';
            } else {
                $code = 'NET-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
                $db->prepare("INSERT INTO signal_networks (network_code, network_name, network_type, encryption_algo, coverage, capacity) VALUES (?,?,?,?,?,?)")
                   ->execute([$code, $name, $type, $algo, $cov, max(10, $cap)]);
                awardXP($clientId, 'network_provisioned', ['code' => $code]);
                $msg = "Network <strong>$code</strong> provisioned."; $msgType = 'success';
            }
        } elseif ($action === 'update_network' && $isOfficer) {
            $netId  = (int)($_POST['net_id'] ?? 0);
            $status = $_POST['net_status'] ?? 'operational';
            $load   = (int)($_POST['current_load'] ?? 0);
            $validNS = ['operational','degraded','offline','compromised'];
            if (!in_array($status, $validNS, true)) { $msg = 'Invalid status.'; $msgType = 'error'; }
            else {
                $stmt = $db->prepare("UPDATE signal_networks SET status = ?, current_load = ? WHERE id = ?");
                $stmt->execute([$status, max(0, $load), $netId]);
                if ($status === 'compromised') awardXP($clientId, 'network_compromise_reported', []);
                $msg = $stmt->rowCount() ? 'Network status updated.' : 'Network not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'start_ew_op' && $isFlag) {
            $opType = $_POST['ew_type'] ?? 'jamming';
            $target = trim($_POST['ew_target'] ?? '');
            $notes  = trim($_POST['ew_notes'] ?? '');
            $validEW = ['jamming','spoofing','direction_finding','hardening'];
            if (!in_array($opType, $validEW, true) || $target === '') {
                $msg = 'Type and target required.'; $msgType = 'error';
            } else {
                $code = 'EW-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO signal_ew_ops (op_code, op_type, target, initiated_by, started_at, notes, status) VALUES (?,?,?,?,NOW(),?,'active')")
                   ->execute([$code, $opType, $target, $clientId, $notes]);
                awardXP($clientId, 'ew_op_launched', ['type' => $opType]);
                $msg = "EW Operation <strong>$code</strong> launched."; $msgType = 'success';
            }
        } elseif ($action === 'end_ew_op' && $isFlag) {
            $ewId   = (int)($_POST['ew_id'] ?? 0);
            $effect = (int)($_POST['effectiveness'] ?? 0);
            $result = $_POST['ew_result'] ?? 'completed';
            $validER = ['completed','countered'];
            if (!in_array($result, $validER, true)) { $msg = 'Invalid result.'; $msgType = 'error'; }
            else {
                $stmt = $db->prepare("UPDATE signal_ew_ops SET status = ?, ended_at = NOW(), effectiveness_pct = ? WHERE id = ? AND status = 'active'");
                $stmt->execute([$result, min(100, max(0, $effect)), $ewId]);
                $msg = $stmt->rowCount() ? 'EW operation terminated.' : 'Operation not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'issue_crypto_key' && $isOfficer) {
            $keyType  = $_POST['key_type'] ?? 'symmetric';
            $algo     = trim($_POST['key_algo'] ?? 'AES-256-GCM');
            $issuedTo = (int)($_POST['issued_to'] ?? 0);
            $expDays  = (int)($_POST['expire_days'] ?? 90);
            $validKT = ['symmetric','asymmetric','session'];
            if (!in_array($keyType, $validKT, true)) { $msg = 'Invalid key type.'; $msgType = 'error'; }
            else {
                $keyPub = 'KEY-' . strtoupper(bin2hex(random_bytes(8)));
                $expDate = date('Y-m-d H:i:s', time() + ($expDays * 86400));
                $db->prepare("INSERT INTO signal_crypto_keys (key_id_public, algorithm, key_type, issued_to, expires_at) VALUES (?,?,?,?,?)")
                   ->execute([$keyPub, $algo, $keyType, $issuedTo > 0 ? $issuedTo : null, $expDate]);
                $msg = "Key <strong>" . htmlspecialchars(substr($keyPub, 0, 12)) . "...</strong> issued. Expires: " . date('M j, Y', strtotime($expDate)); $msgType = 'success';
            }
        } elseif ($action === 'revoke_key' && $isOfficer) {
            $keyId = (int)($_POST['key_id'] ?? 0);
            $stmt = $db->prepare("UPDATE signal_crypto_keys SET status = 'revoked', revoked_at = NOW() WHERE id = ? AND status = 'active'");
            $stmt->execute([$keyId]);
            $msg = $stmt->rowCount() ? 'Key REVOKED.' : 'Key not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'compromise_key' && $isFlag) {
            $keyId = (int)($_POST['key_id'] ?? 0);
            $stmt = $db->prepare("UPDATE signal_crypto_keys SET status = 'compromised', revoked_at = NOW() WHERE id = ? AND status = 'active'");
            $stmt->execute([$keyId]);
            $msg = $stmt->rowCount() ? 'Key marked COMPROMISED.' : 'Key not found.';
            $msgType = $stmt->rowCount() ? ($stmt->rowCount() ? 'error' : 'error') : 'error';
            if ($stmt->rowCount()) $msg = '<i class="fas fa-exclamation-triangle"></i> Key marked COMPROMISED. Immediate re-key required.';
            $msgType = $stmt->rowCount() ? 'error' : 'error';

        } elseif ($action === 'log_intercept' && $isOfficer) {
            $iType   = $_POST['intercept_type'] ?? 'signal';
            $source  = trim($_POST['source'] ?? '');
            $class   = $_POST['classification'] ?? 'confidential';
            $summary = trim($_POST['content_summary'] ?? '');
            $validIT = ['signal','data','metadata'];
            $validCL = ['confidential','secret','top_secret'];
            if (!in_array($iType, $validIT, true) || !in_array($class, $validCL, true) || $summary === '') {
                $msg = 'All fields required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO signal_intercepts (intercept_type, source, classification, content_summary, intercepted_by) VALUES (?,?,?,?,?)")
                   ->execute([$iType, $source, $class, $summary, $clientId]);
                awardXP($clientId, 'signal_intercepted', []);
                $msg = "Intercept logged."; $msgType = 'success';
            }
        } elseif ($action === 'assess_relay' && $isNCO) {
            $relayId = (int)($_POST['relay_id'] ?? 0);
            $status  = $_POST['relay_status'] ?? 'online';
            $latency = (int)($_POST['latency'] ?? 0);
            $uptime  = (float)($_POST['uptime'] ?? 99.99);
            $validRS = ['online','offline','degraded'];
            if (!in_array($status, $validRS, true)) { $msg = 'Invalid status.'; $msgType = 'error'; }
            else {
                $stmt = $db->prepare("UPDATE signal_relay_status SET status = ?, latency_ms = ?, uptime_pct = ?, last_heartbeat = NOW() WHERE id = ?");
                $stmt->execute([$status, max(0, $latency), min(100, max(0, $uptime)), $relayId]);
                $msg = $stmt->rowCount() ? 'Relay status updated.' : 'Relay not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_sg'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_sg'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'networks';
$networks   = $db->query("SELECT * FROM signal_networks ORDER BY network_type, network_code")->fetchAll(PDO::FETCH_ASSOC);
$ewOps      = $db->query("SELECT sew.*, CONCAT(c.firstname,' ',c.lastname) AS initiator FROM signal_ew_ops sew LEFT JOIN tblclients c ON c.id = sew.initiated_by ORDER BY sew.started_at DESC, sew.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$cryptoKeys = $db->query("SELECT sk.*, CONCAT(c.firstname,' ',c.lastname) AS holder FROM signal_crypto_keys sk LEFT JOIN tblclients c ON c.id = sk.issued_to ORDER BY sk.issued_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$relays     = $db->query("SELECT * FROM signal_relay_status ORDER BY relay_type, relay_node")->fetchAll(PDO::FETCH_ASSOC);
$intercepts = $db->query("SELECT si.*, CONCAT(c.firstname,' ',c.lastname) AS interceptor FROM signal_intercepts si LEFT JOIN tblclients c ON c.id = si.intercepted_by ORDER BY si.intercepted_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$onlineRelays = count(array_filter($relays, fn($r) => $r['status'] === 'online'));
$activeEW = count(array_filter($ewOps, fn($e) => $e['status'] === 'active'));
$activeKeys = count(array_filter($cryptoKeys, fn($k) => $k['status'] === 'active'));

$pageTitle = 'Signal Corps & Electronic Warfare';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.sg-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.sg-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.sg-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.sg-card:hover{border-color:#10b981;box-shadow:0 0 12px rgba(16,185,129,.12)}
.sg-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.sg-sub{color:#94a3b8;font-size:.85rem}
.sg-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.sg-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.sg-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.sg-tab.active{background:#10b981;color:#fff}
.sg-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.sg-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:110px;text-align:center}
.sg-stat .val{font-size:1.5rem;font-weight:700;color:#10b981}
.sg-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.sg-btn{background:#10b981;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.sg-btn:hover{background:#059669}
.sg-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.sg-btn-outline{background:transparent;border:1px solid #10b981;color:#10b981}
.sg-btn-outline:hover{background:#10b981;color:#fff}
.sg-btn-red{background:#ef4444;color:#fff}.sg-btn-red:hover{background:#dc2626}
.sg-btn-green{background:#22c55e;color:#fff}.sg-btn-green:hover{background:#16a34a}
.sg-btn-blue{background:#3b82f6;color:#fff}.sg-btn-blue:hover{background:#2563eb}
.sg-btn-yellow{background:#f59e0b;color:#fff}.sg-btn-yellow:hover{background:#d97706}
.sg-input,.sg-select,.sg-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.sg-textarea{min-height:100px;resize:vertical}
.sg-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.sg-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.sg-msg-success{background:rgba(16,185,129,.12);border:1px solid #10b981;color:#6ee7b7}
.sg-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.sg-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.sg-modal-bg.open{display:flex}
.sg-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.sg-modal h3{color:#f1f5f9;margin:0 0 1rem}
.sg-form-row{margin-bottom:.75rem}
.sg-load-bar{background:#0a0a14;border-radius:4px;height:6px;overflow:hidden;margin-top:.25rem}
.sg-load-fill{height:100%;border-radius:4px;transition:width .3s}
</style>
<div class="sg-bg">
<div class="sg-wrap">
    <div class="sg-title"><i class="fas fa-broadcast-tower"></i> Signal Corps & Electronic Warfare</div>
    <p class="sg-sub" style="margin-bottom:1.25rem">Comms networks, EW operations, crypto key management, relay mesh, and signal intercepts — Corporal+</p>

    <?php if ($msg): ?><div class="sg-msg sg-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="sg-stat-bar">
        <div class="sg-stat"><div class="val"><?= count($networks) ?></div><div class="lbl">Networks</div></div>
        <div class="sg-stat"><div class="val" style="color:#22c55e"><?= $onlineRelays ?>/<?= count($relays) ?></div><div class="lbl">Relays Online</div></div>
        <div class="sg-stat"><div class="val" style="color:#f59e0b"><?= $activeEW ?></div><div class="lbl">Active EW Ops</div></div>
        <div class="sg-stat"><div class="val" style="color:#3b82f6"><?= $activeKeys ?></div><div class="lbl">Active Keys</div></div>
        <div class="sg-stat"><div class="val" style="color:#a855f7"><?= count($intercepts) ?></div><div class="lbl">Intercepts</div></div>
    </div>

    <div class="sg-tabs">
        <a href="?tab=networks" class="sg-tab <?= $tab==='networks'?'active':'' ?>"><i class="fas fa-network-wired"></i> Networks</a>
        <a href="?tab=ew" class="sg-tab <?= $tab==='ew'?'active':'' ?>"><i class="fas fa-bolt"></i> EW Ops</a>
        <a href="?tab=crypto" class="sg-tab <?= $tab==='crypto'?'active':'' ?>"><i class="fas fa-key"></i> Crypto</a>
        <a href="?tab=relays" class="sg-tab <?= $tab==='relays'?'active':'' ?>"><i class="fas fa-wifi"></i> Relays</a>
        <a href="?tab=intercepts" class="sg-tab <?= $tab==='intercepts'?'active':'' ?>"><i class="fas fa-ear-listen"></i> Intercepts</a>
    </div>

    <!-- ═══ TAB: NETWORKS ═══ -->
    <?php if ($tab === 'networks'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="sg-btn" onclick="document.getElementById('modalNetwork').classList.add('open')"><i class="fas fa-plus"></i> Provision Network</button></div>
        <?php endif; ?>
        <?php
        $ntColors = ['tactical'=>'#22c55e','strategic'=>'#3b82f6','emergency'=>'#ef4444','satellite'=>'#a855f7'];
        $nsColors = ['operational'=>'#22c55e','degraded'=>'#f59e0b','offline'=>'#64748b','compromised'=>'#ef4444'];
        foreach ($networks as $n):
            $loadPct = $n['capacity'] > 0 ? round(($n['current_load'] / $n['capacity']) * 100) : 0;
            $loadColor = $loadPct > 80 ? '#ef4444' : ($loadPct > 50 ? '#f59e0b' : '#10b981');
        ?>
            <div class="sg-card" style="border-left:3px solid <?= $ntColors[$n['network_type']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-network-wired" style="color:<?= $ntColors[$n['network_type']] ?>"></i>
                        <strong style="color:#10b981;margin-left:.25rem"><?= htmlspecialchars($n['network_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($n['network_name']) ?></strong>
                    </div>
                    <div>
                        <span class="sg-badge" style="background:<?= $ntColors[$n['network_type']] ?>20;color:<?= $ntColors[$n['network_type']] ?>"><?= strtoupper($n['network_type']) ?></span>
                        <span class="sg-badge" style="background:<?= $nsColors[$n['status']] ?>20;color:<?= $nsColors[$n['status']] ?>;margin-left:.25rem"><?= strtoupper($n['status']) ?></span>
                    </div>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Encryption: <span style="color:#10b981"><?= htmlspecialchars($n['encryption_algo']) ?></span> &bull;
                    Load: <?= $n['current_load'] ?>/<?= $n['capacity'] ?> (<?= $loadPct ?>%)
                    <?php if ($n['coverage']): ?>&bull; Coverage: <?= htmlspecialchars($n['coverage']) ?><?php endif; ?>
                </div>
                <div class="sg-load-bar"><div class="sg-load-fill" style="width:<?= $loadPct ?>%;background:<?= $loadColor ?>"></div></div>
                <?php if ($isOfficer): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_network"><input type="hidden" name="net_id" value="<?= $n['id'] ?>">
                        <select name="net_status" class="sg-select" style="width:auto"><?php foreach (['operational','degraded','offline','compromised'] as $ns): ?><option value="<?= $ns ?>" <?= $n['status']===$ns?'selected':'' ?>><?= ucfirst($ns) ?></option><?php endforeach; ?></select>
                        <input type="number" name="current_load" class="sg-input" style="width:80px" value="<?= $n['current_load'] ?>" min="0" max="<?= $n['capacity'] ?>">
                        <button class="sg-btn-sm sg-btn"><i class="fas fa-sync"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: EW OPS ═══ -->
    <?php elseif ($tab === 'ew'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="sg-btn sg-btn-yellow" onclick="document.getElementById('modalEW').classList.add('open')"><i class="fas fa-bolt"></i> Launch EW Operation</button></div>
        <?php endif; ?>
        <?php
        $ewColors = ['planned'=>'#94a3b8','active'=>'#f59e0b','completed'=>'#22c55e','countered'=>'#ef4444'];
        $ewIcons  = ['jamming'=>'fa-ban','spoofing'=>'fa-mask','direction_finding'=>'fa-compass','hardening'=>'fa-shield-alt'];
        foreach ($ewOps as $ew): ?>
            <div class="sg-card" style="border-left:3px solid <?= $ewColors[$ew['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas <?= $ewIcons[$ew['op_type']] ?? 'fa-bolt' ?>" style="color:#f59e0b"></i>
                        <strong style="color:#10b981;margin-left:.25rem"><?= htmlspecialchars($ew['op_code']) ?></strong>
                        <span class="sg-badge" style="background:#f59e0b20;color:#f59e0b;margin-left:.25rem"><?= strtoupper(str_replace('_', ' ', $ew['op_type'])) ?></span>
                    </div>
                    <span class="sg-badge" style="background:<?= $ewColors[$ew['status']] ?>20;color:<?= $ewColors[$ew['status']] ?>"><?= strtoupper($ew['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Target: <span style="color:#fbbf24"><?= htmlspecialchars($ew['target'] ?? 'Classified') ?></span> &bull;
                    Initiated by: <?= htmlspecialchars($ew['initiator'] ?? 'Unknown') ?>
                    <?php if ($ew['effectiveness_pct'] > 0): ?>&bull; Effectiveness: <?= $ew['effectiveness_pct'] ?>%<?php endif; ?>
                </div>
                <?php if ($ew['notes']): ?><p style="color:#64748b;font-size:.8rem;margin-top:.25rem"><?= htmlspecialchars($ew['notes']) ?></p><?php endif; ?>
                <?php if ($ew['status'] === 'active' && $isFlag): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="end_ew_op"><input type="hidden" name="ew_id" value="<?= $ew['id'] ?>">
                        <select name="ew_result" class="sg-select" style="width:auto"><option value="completed">Completed</option><option value="countered">Countered</option></select>
                        <input type="number" name="effectiveness" class="sg-input" style="width:80px" placeholder="Eff %" min="0" max="100" value="50">
                        <button class="sg-btn-sm sg-btn sg-btn-red"><i class="fas fa-stop"></i> End</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($ewOps)): ?><div class="sg-card" style="text-align:center;color:#64748b"><p>No EW operations.</p></div><?php endif; ?>

    <!-- ═══ TAB: CRYPTO ═══ -->
    <?php elseif ($tab === 'crypto'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="sg-btn sg-btn-blue" onclick="document.getElementById('modalCrypto').classList.add('open')"><i class="fas fa-key"></i> Issue Key</button></div>
        <?php endif; ?>
        <?php
        $ksColors = ['active'=>'#22c55e','expired'=>'#64748b','revoked'=>'#f59e0b','compromised'=>'#ef4444'];
        $ktIcons  = ['symmetric'=>'fa-lock','asymmetric'=>'fa-lock-open','session'=>'fa-clock'];
        foreach ($cryptoKeys as $k): ?>
            <div class="sg-card" style="border-left:3px solid <?= $ksColors[$k['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas <?= $ktIcons[$k['key_type']] ?? 'fa-key' ?>" style="color:#3b82f6"></i>
                        <strong style="color:#10b981;margin-left:.25rem;font-family:monospace;font-size:.8rem"><?= htmlspecialchars(substr($k['key_id_public'], 0, 16)) ?>...</strong>
                    </div>
                    <div>
                        <span class="sg-badge" style="background:#3b82f620;color:#3b82f6"><?= strtoupper($k['key_type']) ?></span>
                        <span class="sg-badge" style="background:<?= $ksColors[$k['status']] ?>20;color:<?= $ksColors[$k['status']] ?>;margin-left:.25rem"><?= strtoupper($k['status']) ?></span>
                    </div>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Algorithm: <span style="color:#10b981"><?= htmlspecialchars($k['algorithm']) ?></span> &bull;
                    Holder: <?= htmlspecialchars($k['holder'] ?? 'Unassigned') ?> &bull;
                    Issued: <?= date('M j, Y', strtotime($k['issued_at'])) ?> &bull;
                    Expires: <?= $k['expires_at'] ? date('M j, Y', strtotime($k['expires_at'])) : 'Never' ?>
                </div>
                <?php if ($k['status'] === 'active' && $isOfficer): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem">
                        <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="revoke_key"><input type="hidden" name="key_id" value="<?= $k['id'] ?>"><button class="sg-btn-sm sg-btn sg-btn-yellow"><i class="fas fa-ban"></i> Revoke</button></form>
                        <?php if ($isFlag): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="compromise_key"><input type="hidden" name="key_id" value="<?= $k['id'] ?>"><button class="sg-btn-sm sg-btn sg-btn-red"><i class="fas fa-skull-crossbones"></i> Compromised</button></form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($cryptoKeys)): ?><div class="sg-card" style="text-align:center;color:#64748b"><p>No crypto keys issued.</p></div><?php endif; ?>

    <!-- ═══ TAB: RELAYS ═══ -->
    <?php elseif ($tab === 'relays'): ?>
        <?php
        $rsColors = ['online'=>'#22c55e','offline'=>'#ef4444','degraded'=>'#f59e0b'];
        $rtIcons  = ['ground'=>'fa-broadcast-tower','satellite'=>'fa-satellite','mesh'=>'fa-project-diagram'];
        foreach ($relays as $r):
            $latColor = $r['latency_ms'] > 200 ? '#ef4444' : ($r['latency_ms'] > 50 ? '#f59e0b' : '#22c55e');
        ?>
            <div class="sg-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <div style="width:10px;height:10px;border-radius:50%;background:<?= $rsColors[$r['status']] ?>;box-shadow:0 0 6px <?= $rsColors[$r['status']] ?>"></div>
                <i class="fas <?= $rtIcons[$r['relay_type']] ?? 'fa-wifi' ?>" style="font-size:1.2rem;color:#10b981"></i>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($r['relay_node']) ?></strong>
                    <span class="sg-badge" style="background:<?= $rsColors[$r['status']] ?>20;color:<?= $rsColors[$r['status']] ?>;margin-left:.5rem"><?= strtoupper($r['status']) ?></span>
                    <div style="color:#94a3b8;font-size:.75rem">
                        Type: <?= ucfirst($r['relay_type']) ?> &bull;
                        Latency: <span style="color:<?= $latColor ?>"><?= $r['latency_ms'] ?>ms</span> &bull;
                        Uptime: <?= number_format($r['uptime_pct'], 2) ?>% &bull;
                        Heartbeat: <?= date('H:i:s', strtotime($r['last_heartbeat'])) ?>
                    </div>
                </div>
                <?php if ($isNCO): ?>
                    <form method="POST" style="display:flex;gap:.25rem;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="assess_relay"><input type="hidden" name="relay_id" value="<?= $r['id'] ?>">
                        <select name="relay_status" class="sg-select" style="width:auto;font-size:.75rem"><?php foreach (['online','offline','degraded'] as $rs): ?><option value="<?= $rs ?>" <?= $r['status']===$rs?'selected':'' ?>><?= ucfirst($rs) ?></option><?php endforeach; ?></select>
                        <input type="number" name="latency" class="sg-input" style="width:60px;font-size:.75rem" value="<?= $r['latency_ms'] ?>" min="0">
                        <input type="number" name="uptime" class="sg-input" style="width:70px;font-size:.75rem" value="<?= number_format($r['uptime_pct'], 2) ?>" step="0.01" min="0" max="100">
                        <button class="sg-btn-sm sg-btn" style="font-size:.7rem"><i class="fas fa-sync"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: INTERCEPTS ═══ -->
    <?php elseif ($tab === 'intercepts'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="sg-btn" onclick="document.getElementById('modalIntercept').classList.add('open')"><i class="fas fa-ear-listen"></i> Log Intercept</button></div>
        <?php endif; ?>
        <?php
        $clColors = ['confidential'=>'#3b82f6','secret'=>'#f59e0b','top_secret'=>'#ef4444'];
        foreach ($intercepts as $i): ?>
            <div class="sg-card" style="border-left:3px solid <?= $clColors[$i['classification']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <span class="sg-badge" style="background:<?= $clColors[$i['classification']] ?>20;color:<?= $clColors[$i['classification']] ?>"><?= strtoupper(str_replace('_', ' ', $i['classification'])) ?></span>
                        <span class="sg-badge" style="background:#10b98120;color:#10b981;margin-left:.25rem"><?= strtoupper($i['intercept_type']) ?></span>
                        <?php if ($i['analyzed']): ?><span class="sg-badge" style="background:#22c55e20;color:#22c55e;margin-left:.25rem">ANALYZED</span><?php endif; ?>
                    </div>
                    <span style="color:#64748b;font-size:.75rem"><?= date('M j, Y H:i', strtotime($i['intercepted_at'])) ?></span>
                </div>
                <?php if ($i['source']): ?><div style="color:#f59e0b;font-size:.8rem;margin-top:.25rem">Source: <?= htmlspecialchars($i['source']) ?></div><?php endif; ?>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars($i['content_summary']) ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Intercepted by: <?= htmlspecialchars($i['interceptor'] ?? 'Unknown') ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($intercepts)): ?><div class="sg-card" style="text-align:center;color:#64748b"><p>No intercepts logged.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="sg-modal-bg" id="modalNetwork"><div class="sg-modal"><h3><i class="fas fa-plus"></i> Provision Network</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="create_network">
<div class="sg-form-row"><label class="sg-label">Network Name</label><input type="text" name="net_name" class="sg-input" required></div>
<div style="display:flex;gap:.75rem"><div class="sg-form-row" style="flex:1"><label class="sg-label">Type</label><select name="net_type" class="sg-select"><option value="tactical">Tactical</option><option value="strategic">Strategic</option><option value="emergency">Emergency</option><option value="satellite">Satellite</option></select></div><div class="sg-form-row" style="flex:1"><label class="sg-label">Encryption</label><input type="text" name="encryption" class="sg-input" value="AES-256-GCM"></div></div>
<div style="display:flex;gap:.75rem"><div class="sg-form-row" style="flex:1"><label class="sg-label">Coverage</label><input type="text" name="coverage" class="sg-input" placeholder="e.g. All sectors"></div><div class="sg-form-row" style="flex:1"><label class="sg-label">Capacity</label><input type="number" name="capacity" class="sg-input" value="100" min="10"></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sg-btn sg-btn-outline" onclick="this.closest('.sg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sg-btn"><i class="fas fa-plus"></i> Provision</button></div></form></div></div>

<div class="sg-modal-bg" id="modalEW"><div class="sg-modal"><h3><i class="fas fa-bolt"></i> Launch EW Operation</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="start_ew_op">
<div style="display:flex;gap:.75rem"><div class="sg-form-row" style="flex:1"><label class="sg-label">Operation Type</label><select name="ew_type" class="sg-select"><option value="jamming">Jamming</option><option value="spoofing">Spoofing</option><option value="direction_finding">Direction Finding</option><option value="hardening">Hardening</option></select></div></div>
<div class="sg-form-row"><label class="sg-label">Target</label><input type="text" name="ew_target" class="sg-input" required placeholder="Target designation..."></div>
<div class="sg-form-row"><label class="sg-label">Notes</label><textarea name="ew_notes" class="sg-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sg-btn sg-btn-outline" onclick="this.closest('.sg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sg-btn sg-btn-yellow"><i class="fas fa-bolt"></i> Launch</button></div></form></div></div>

<div class="sg-modal-bg" id="modalCrypto"><div class="sg-modal"><h3><i class="fas fa-key"></i> Issue Crypto Key</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="issue_crypto_key">
<div style="display:flex;gap:.75rem"><div class="sg-form-row" style="flex:1"><label class="sg-label">Key Type</label><select name="key_type" class="sg-select"><option value="symmetric">Symmetric</option><option value="asymmetric">Asymmetric</option><option value="session">Session</option></select></div><div class="sg-form-row" style="flex:1"><label class="sg-label">Algorithm</label><input type="text" name="key_algo" class="sg-input" value="AES-256-GCM"></div></div>
<div style="display:flex;gap:.75rem"><div class="sg-form-row" style="flex:1"><label class="sg-label">Issued To (Client ID)</label><input type="number" name="issued_to" class="sg-input" placeholder="0 = Unassigned" min="0"></div><div class="sg-form-row" style="flex:1"><label class="sg-label">Expiry (days)</label><input type="number" name="expire_days" class="sg-input" value="90" min="1"></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sg-btn sg-btn-outline" onclick="this.closest('.sg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sg-btn sg-btn-blue"><i class="fas fa-key"></i> Issue</button></div></form></div></div>

<div class="sg-modal-bg" id="modalIntercept"><div class="sg-modal"><h3><i class="fas fa-ear-listen"></i> Log Signal Intercept</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="log_intercept">
<div style="display:flex;gap:.75rem"><div class="sg-form-row" style="flex:1"><label class="sg-label">Intercept Type</label><select name="intercept_type" class="sg-select"><option value="signal">Signal</option><option value="data">Data</option><option value="metadata">Metadata</option></select></div><div class="sg-form-row" style="flex:1"><label class="sg-label">Classification</label><select name="classification" class="sg-select"><option value="confidential">Confidential</option><option value="secret">Secret</option><option value="top_secret">Top Secret</option></select></div></div>
<div class="sg-form-row"><label class="sg-label">Source</label><input type="text" name="source" class="sg-input" placeholder="Source designation..."></div>
<div class="sg-form-row"><label class="sg-label">Content Summary</label><textarea name="content_summary" class="sg-textarea" required></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sg-btn sg-btn-outline" onclick="this.closest('.sg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sg-btn"><i class="fas fa-ear-listen"></i> Log</button></div></form></div></div>

<script>
document.querySelectorAll('.sg-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
