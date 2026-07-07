<?php
/**
 * ═══════════════════════════════════════════
 *  Space Command (SPACECOM) — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_sc'])) $_SESSION['csrf_sc'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS space_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(30) NOT NULL,
    asset_name VARCHAR(120) NOT NULL,
    asset_type ENUM('satellite','probe','weapon_platform','relay','station_module') NOT NULL,
    orbit_type ENUM('LEO','MEO','GEO','HEO','lunar','deep_space') DEFAULT 'LEO',
    altitude_km INT DEFAULT 0,
    inclination_deg DECIMAL(6,2) DEFAULT 0.00,
    status ENUM('operational','degraded','offline','deorbiting','destroyed') DEFAULT 'operational',
    launched_at TIMESTAMP NULL,
    estimated_lifetime_years INT DEFAULT 10,
    deployed_by INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS space_orbits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    orbit_update_type ENUM('insertion','correction','transfer','decay_alert') DEFAULT 'insertion',
    old_altitude_km INT DEFAULT NULL,
    new_altitude_km INT NOT NULL,
    old_inclination DECIMAL(6,2) DEFAULT NULL,
    new_inclination DECIMAL(6,2) DEFAULT NULL,
    delta_v DECIMAL(10,2) DEFAULT 0.00,
    reason TEXT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS space_launches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    launch_code VARCHAR(20) NOT NULL,
    mission_name VARCHAR(120) NOT NULL,
    payload VARCHAR(200) DEFAULT NULL,
    vehicle VARCHAR(80) DEFAULT NULL,
    target_orbit ENUM('LEO','MEO','GEO','HEO','lunar','deep_space') DEFAULT 'LEO',
    status ENUM('manifest','countdown','launched','success','failure','scrubbed') DEFAULT 'manifest',
    scheduled_at TIMESTAMP NULL,
    launched_at TIMESTAMP NULL,
    authorized_by INT DEFAULT NULL,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS space_defense_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_code VARCHAR(20) NOT NULL,
    event_type ENUM('debris_alert','intrusion','asat_threat','collision_warning','emp_event','unknown') DEFAULT 'unknown',
    threat_level ENUM('low','moderate','high','critical') DEFAULT 'low',
    description TEXT NOT NULL,
    response TEXT DEFAULT NULL,
    weapon_authorized BOOLEAN DEFAULT FALSE,
    authorized_by INT DEFAULT NULL,
    logged_by INT NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS space_stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_code VARCHAR(20) NOT NULL,
    station_name VARCHAR(120) NOT NULL,
    crew_capacity INT DEFAULT 6,
    current_crew INT DEFAULT 0,
    modules JSON DEFAULT NULL,
    status ENUM('operational','construction','maintenance','abandoned') DEFAULT 'construction',
    commissioned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$csrf = $_SESSION['csrf_sc'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'register_asset' && $isOfficer) {
            $name    = trim($_POST['asset_name'] ?? '');
            $type    = $_POST['asset_type'] ?? 'satellite';
            $orbit   = $_POST['orbit_type'] ?? 'LEO';
            $alt     = (int)($_POST['altitude_km'] ?? 400);
            $incl    = (float)($_POST['inclination'] ?? 0);
            $life    = (int)($_POST['lifetime'] ?? 10);
            $validAT = ['satellite','probe','weapon_platform','relay','station_module'];
            $validOT = ['LEO','MEO','GEO','HEO','lunar','deep_space'];
            if ($name === '' || !in_array($type, $validAT, true) || !in_array($orbit, $validOT, true)) {
                $msg = 'All fields required.'; $msgType = 'error';
            } elseif ($type === 'weapon_platform' && !$isCommander) {
                $msg = 'Weapon platform deployment requires Commander authorization.'; $msgType = 'error';
            } else {
                $code = 'SAT-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO space_assets (asset_code, asset_name, asset_type, orbit_type, altitude_km, inclination_deg, estimated_lifetime_years, deployed_by) VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$code, $name, $type, $orbit, max(1, $alt), $incl, max(1, $life), $clientId]);
                awardXP($clientId, 'space_asset_deployed', ['code' => $code]);
                $msg = "Asset <strong>$code</strong> deployed to $orbit orbit."; $msgType = 'success';
            }
        } elseif ($action === 'update_orbit' && $isOfficer) {
            $assetId = (int)($_POST['asset_id'] ?? 0);
            $newAlt  = (int)($_POST['new_altitude'] ?? 0);
            $newIncl = (float)($_POST['new_inclination'] ?? 0);
            $reason  = trim($_POST['reason'] ?? '');
            $upType  = $_POST['update_type'] ?? 'correction';
            $validUT = ['insertion','correction','transfer','decay_alert'];
            $asset = $db->prepare("SELECT * FROM space_assets WHERE id = ? AND status IN ('operational','degraded')");
            $asset->execute([$assetId]);
            $assetRow = $asset->fetch(PDO::FETCH_ASSOC);
            if (!$assetRow || !in_array($upType, $validUT, true)) {
                $msg = 'Asset not found or invalid update type.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO space_orbits (asset_id, orbit_update_type, old_altitude_km, new_altitude_km, old_inclination, new_inclination, reason, updated_by) VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$assetId, $upType, $assetRow['altitude_km'], $newAlt, $assetRow['inclination_deg'], $newIncl, $reason, $clientId]);
                $db->prepare("UPDATE space_assets SET altitude_km = ?, inclination_deg = ? WHERE id = ?")
                   ->execute([$newAlt, $newIncl, $assetId]);
                $msg = "Orbit updated. ΔAlt: " . ($newAlt - $assetRow['altitude_km']) . " km."; $msgType = 'success';
            }
        } elseif ($action === 'schedule_launch' && $isOfficer) {
            $mName   = trim($_POST['mission_name'] ?? '');
            $payload = trim($_POST['payload'] ?? '');
            $vehicle = trim($_POST['vehicle'] ?? '');
            $tOrbit  = $_POST['target_orbit'] ?? 'LEO';
            $sched   = trim($_POST['scheduled_at'] ?? '');
            if ($mName === '') { $msg = 'Mission name required.'; $msgType = 'error'; }
            else {
                $code = 'LV-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO space_launches (launch_code, mission_name, payload, vehicle, target_orbit, scheduled_at) VALUES (?,?,?,?,?,?)")
                   ->execute([$code, $mName, $payload, $vehicle, $tOrbit, $sched ?: null]);
                $msg = "Launch <strong>$code</strong> added to manifest."; $msgType = 'success';
            }
        } elseif ($action === 'execute_launch' && $isFlag) {
            $lId = (int)($_POST['launch_id'] ?? 0);
            $success = !empty($_POST['success']);
            $newSt = $success ? 'success' : 'failure';
            $stmt = $db->prepare("UPDATE space_launches SET status = ?, launched_at = NOW(), authorized_by = ? WHERE id = ? AND status IN ('manifest','countdown')");
            $stmt->execute([$newSt, $clientId, $lId]);
            if ($stmt->rowCount() && $success) awardXP($clientId, 'launch_success', []);
            $msg = $stmt->rowCount() ? ($success ? 'Launch SUCCESS!' : 'Launch FAILURE recorded.') : 'Launch not found.';
            $msgType = $stmt->rowCount() ? ($success ? 'success' : 'error') : 'error';

        } elseif ($action === 'log_defense_event' && $isOfficer) {
            $evType  = $_POST['event_type'] ?? 'unknown';
            $tLevel  = $_POST['threat_level'] ?? 'low';
            $desc    = trim($_POST['description'] ?? '');
            $validET = ['debris_alert','intrusion','asat_threat','collision_warning','emp_event','unknown'];
            $validTL = ['low','moderate','high','critical'];
            if (!in_array($evType, $validET, true) || !in_array($tLevel, $validTL, true) || $desc === '') {
                $msg = 'All fields required.'; $msgType = 'error';
            } else {
                $code = 'SDE-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO space_defense_log (event_code, event_type, threat_level, description, logged_by) VALUES (?,?,?,?,?)")
                   ->execute([$code, $evType, $tLevel, $desc, $clientId]);
                $msg = "Defense event <strong>$code</strong> logged."; $msgType = 'success';
            }
        } elseif ($action === 'authorize_weapon' && $isCommander) {
            $evId     = (int)($_POST['event_id'] ?? 0);
            $response = trim($_POST['response'] ?? '');
            $stmt = $db->prepare("UPDATE space_defense_log SET weapon_authorized = TRUE, authorized_by = ?, response = ? WHERE id = ?");
            $stmt->execute([$clientId, $response, $evId]);
            $msg = $stmt->rowCount() ? 'WEAPON PLATFORM AUTHORIZED.' : 'Event not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'commission_station' && $isFlag) {
            $sName   = trim($_POST['station_name'] ?? '');
            $crew    = (int)($_POST['crew_capacity'] ?? 6);
            $modules = trim($_POST['modules'] ?? '');
            if ($sName === '') { $msg = 'Station name required.'; $msgType = 'error'; }
            else {
                $code = 'STA-' . strtoupper(bin2hex(random_bytes(2)));
                $modJson = json_encode(array_filter(array_map('trim', explode(',', $modules))));
                $db->prepare("INSERT INTO space_stations (station_code, station_name, crew_capacity, modules) VALUES (?,?,?,?)")
                   ->execute([$code, $sName, max(2, $crew), $modJson]);
                awardXP($clientId, 'station_commissioned', ['code' => $code]);
                $msg = "Station <strong>$code</strong> commissioned."; $msgType = 'success';
            }
        } elseif ($action === 'update_station' && $isOfficer) {
            $sId    = (int)($_POST['station_id'] ?? 0);
            $crew   = (int)($_POST['current_crew'] ?? 0);
            $status = $_POST['station_status'] ?? 'construction';
            $validSS = ['operational','construction','maintenance','abandoned'];
            if (!in_array($status, $validSS, true)) { $msg = 'Invalid status.'; $msgType = 'error'; }
            else {
                $stmt = $db->prepare("UPDATE space_stations SET current_crew = ?, status = ?, commissioned_at = IF(? = 'operational' AND commissioned_at IS NULL, NOW(), commissioned_at) WHERE id = ?");
                $stmt->execute([max(0, $crew), $status, $status, $sId]);
                $msg = $stmt->rowCount() ? 'Station updated.' : 'Station not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_sc'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_sc'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'constellation';
$assets   = $db->query("SELECT sa.*, CONCAT(c.firstname,' ',c.lastname) AS deployer FROM space_assets sa LEFT JOIN tblclients c ON c.id = sa.deployed_by ORDER BY sa.orbit_type, sa.altitude_km")->fetchAll(PDO::FETCH_ASSOC);
$orbits   = $db->query("SELECT so.*, sa.asset_code, sa.asset_name FROM space_orbits so JOIN space_assets sa ON sa.id = so.asset_id ORDER BY so.updated_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$launches = $db->query("SELECT sl.*, CONCAT(c.firstname,' ',c.lastname) AS auth_name FROM space_launches sl LEFT JOIN tblclients c ON c.id = sl.authorized_by ORDER BY sl.scheduled_at DESC, sl.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$defense  = $db->query("SELECT sd.*, CONCAT(c.firstname,' ',c.lastname) AS logger, CONCAT(c2.firstname,' ',c2.lastname) AS authorizer FROM space_defense_log sd LEFT JOIN tblclients c ON c.id = sd.logged_by LEFT JOIN tblclients c2 ON c2.id = sd.authorized_by ORDER BY sd.logged_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$stations = $db->query("SELECT * FROM space_stations ORDER BY status, station_code")->fetchAll(PDO::FETCH_ASSOC);
$opAssets = count(array_filter($assets, fn($a) => $a['status'] === 'operational'));

$pageTitle = 'Space Command (SPACECOM)';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.sc-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.sc-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.sc-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.sc-card:hover{border-color:#06b6d4;box-shadow:0 0 12px rgba(6,182,212,.12)}
.sc-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.sc-sub{color:#94a3b8;font-size:.85rem}
.sc-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.sc-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.sc-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.sc-tab.active{background:#06b6d4;color:#fff}
.sc-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.sc-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:110px;text-align:center}
.sc-stat .val{font-size:1.5rem;font-weight:700;color:#06b6d4}
.sc-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.sc-btn{background:#06b6d4;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.sc-btn:hover{background:#0891b2}
.sc-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.sc-btn-outline{background:transparent;border:1px solid #06b6d4;color:#06b6d4}
.sc-btn-outline:hover{background:#06b6d4;color:#fff}
.sc-btn-red{background:#ef4444;color:#fff}.sc-btn-red:hover{background:#dc2626}
.sc-btn-green{background:#22c55e;color:#fff}.sc-btn-green:hover{background:#16a34a}
.sc-btn-blue{background:#3b82f6;color:#fff}.sc-btn-blue:hover{background:#2563eb}
.sc-input,.sc-select,.sc-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.sc-textarea{min-height:100px;resize:vertical}
.sc-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.sc-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.sc-msg-success{background:rgba(6,182,212,.12);border:1px solid #06b6d4;color:#67e8f9}
.sc-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.sc-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.sc-modal-bg.open{display:flex}
.sc-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.sc-modal h3{color:#f1f5f9;margin:0 0 1rem}
.sc-form-row{margin-bottom:.75rem}
.sc-orbit-label{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:700;text-transform:uppercase}
</style>
<div class="sc-bg">
<div class="sc-wrap">
    <div class="sc-title"><i class="fas fa-satellite"></i> Space Command — SPACECOM</div>
    <p class="sc-sub" style="margin-bottom:1.25rem">Orbital assets, launch manifest, space defense, and station management — Officer+ rank</p>

    <?php if ($msg): ?><div class="sc-msg sc-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <?php
    $orbitColors = ['LEO'=>'#22c55e','MEO'=>'#3b82f6','GEO'=>'#f59e0b','HEO'=>'#a855f7','lunar'=>'#94a3b8','deep_space'=>'#ef4444'];
    ?>
    <div class="sc-stat-bar">
        <div class="sc-stat"><div class="val"><?= count($assets) ?></div><div class="lbl">Total Assets</div></div>
        <div class="sc-stat"><div class="val" style="color:#22c55e"><?= $opAssets ?></div><div class="lbl">Operational</div></div>
        <div class="sc-stat"><div class="val" style="color:#f59e0b"><?= count($launches) ?></div><div class="lbl">Launches</div></div>
        <div class="sc-stat"><div class="val" style="color:#ef4444"><?= count($defense) ?></div><div class="lbl">Defense Events</div></div>
        <div class="sc-stat"><div class="val" style="color:#a855f7"><?= count($stations) ?></div><div class="lbl">Stations</div></div>
    </div>

    <div class="sc-tabs">
        <a href="?tab=constellation" class="sc-tab <?= $tab==='constellation'?'active':'' ?>"><i class="fas fa-satellite"></i> Constellation</a>
        <a href="?tab=launches" class="sc-tab <?= $tab==='launches'?'active':'' ?>"><i class="fas fa-rocket"></i> Launches</a>
        <a href="?tab=defense" class="sc-tab <?= $tab==='defense'?'active':'' ?>"><i class="fas fa-shield-alt"></i> Defense</a>
        <a href="?tab=stations" class="sc-tab <?= $tab==='stations'?'active':'' ?>"><i class="fas fa-space-shuttle"></i> Stations</a>
        <a href="?tab=orbits" class="sc-tab <?= $tab==='orbits'?'active':'' ?>"><i class="fas fa-globe"></i> Orbit Log</a>
    </div>

    <!-- ═══ TAB: CONSTELLATION ═══ -->
    <?php if ($tab === 'constellation'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="sc-btn" onclick="document.getElementById('modalAsset').classList.add('open')"><i class="fas fa-satellite-dish"></i> Deploy Asset</button></div>
        <?php endif; ?>
        <?php
        $stColors = ['operational'=>'#22c55e','degraded'=>'#f59e0b','offline'=>'#64748b','deorbiting'=>'#ef4444','destroyed'=>'#7f1d1d'];
        $typeIcons = ['satellite'=>'fa-satellite','probe'=>'fa-broadcast-tower','weapon_platform'=>'fa-crosshairs','relay'=>'fa-wifi','station_module'=>'fa-cube'];
        foreach ($assets as $a): ?>
            <div class="sc-card" style="border-left:3px solid <?= $orbitColors[$a['orbit_type']] ?? '#64748b' ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas <?= $typeIcons[$a['asset_type']] ?? 'fa-cube' ?>" style="color:#06b6d4;margin-right:.25rem"></i>
                        <strong style="color:#06b6d4"><?= htmlspecialchars($a['asset_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($a['asset_name']) ?></strong>
                    </div>
                    <div>
                        <span class="sc-orbit-label" style="background:<?= $orbitColors[$a['orbit_type']] ?>20;color:<?= $orbitColors[$a['orbit_type']] ?>"><?= $a['orbit_type'] ?></span>
                        <span class="sc-badge" style="background:<?= $stColors[$a['status']] ?>20;color:<?= $stColors[$a['status']] ?>;margin-left:.25rem"><?= strtoupper($a['status']) ?></span>
                    </div>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Type: <?= ucwords(str_replace('_', ' ', $a['asset_type'])) ?> &bull;
                    Alt: <?= number_format($a['altitude_km']) ?> km &bull;
                    Incl: <?= number_format($a['inclination_deg'], 1) ?>° &bull;
                    Lifetime: <?= $a['estimated_lifetime_years'] ?>yr
                    <?php if ($a['deployer']): ?>&bull; Deployed by: <?= htmlspecialchars($a['deployer']) ?><?php endif; ?>
                </div>
                <?php if ($isOfficer && $a['status'] !== 'destroyed'): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_orbit"><input type="hidden" name="asset_id" value="<?= $a['id'] ?>">
                        <select name="update_type" class="sc-select" style="width:auto"><option value="correction">Correction</option><option value="transfer">Transfer</option><option value="decay_alert">Decay Alert</option></select>
                        <input type="number" name="new_altitude" class="sc-input" style="width:100px" placeholder="Alt km" value="<?= $a['altitude_km'] ?>">
                        <input type="number" name="new_inclination" class="sc-input" style="width:80px" placeholder="Incl°" value="<?= number_format($a['inclination_deg'], 1) ?>" step="0.1">
                        <input type="text" name="reason" class="sc-input" style="flex:1" placeholder="Reason...">
                        <button class="sc-btn-sm sc-btn"><i class="fas fa-sync"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($assets)): ?><div class="sc-card" style="text-align:center;color:#64748b"><p>No orbital assets deployed.</p></div><?php endif; ?>

    <!-- ═══ TAB: LAUNCHES ═══ -->
    <?php elseif ($tab === 'launches'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="sc-btn" onclick="document.getElementById('modalLaunch').classList.add('open')"><i class="fas fa-rocket"></i> Schedule Launch</button></div>
        <?php endif; ?>
        <?php
        $lsColors = ['manifest'=>'#94a3b8','countdown'=>'#f59e0b','launched'=>'#3b82f6','success'=>'#22c55e','failure'=>'#ef4444','scrubbed'=>'#64748b'];
        foreach ($launches as $l): ?>
            <div class="sc-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#06b6d4"><?= htmlspecialchars($l['launch_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($l['mission_name']) ?></strong>
                        <span class="sc-orbit-label" style="background:<?= $orbitColors[$l['target_orbit']] ?? '#64748b' ?>20;color:<?= $orbitColors[$l['target_orbit']] ?? '#64748b' ?>;margin-left:.25rem"><?= $l['target_orbit'] ?></span>
                    </div>
                    <span class="sc-badge" style="background:<?= $lsColors[$l['status']] ?>20;color:<?= $lsColors[$l['status']] ?>;border:1px solid <?= $lsColors[$l['status']] ?>40"><?= strtoupper($l['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    <?php if ($l['payload']): ?>Payload: <?= htmlspecialchars($l['payload']) ?> &bull; <?php endif; ?>
                    <?php if ($l['vehicle']): ?>Vehicle: <?= htmlspecialchars($l['vehicle']) ?> &bull; <?php endif; ?>
                    Scheduled: <?= $l['scheduled_at'] ? date('M j, Y H:i', strtotime($l['scheduled_at'])) : 'TBD' ?>
                </div>
                <?php if ($isFlag && in_array($l['status'], ['manifest','countdown'])): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem">
                        <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="execute_launch"><input type="hidden" name="launch_id" value="<?= $l['id'] ?>"><input type="hidden" name="success" value="1"><button class="sc-btn-sm sc-btn sc-btn-green"><i class="fas fa-rocket"></i> Launch ✓</button></form>
                        <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="execute_launch"><input type="hidden" name="launch_id" value="<?= $l['id'] ?>"><button class="sc-btn-sm sc-btn sc-btn-red"><i class="fas fa-times"></i> Failure</button></form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($launches)): ?><div class="sc-card" style="text-align:center;color:#64748b"><p>Launch manifest empty.</p></div><?php endif; ?>

    <!-- ═══ TAB: DEFENSE ═══ -->
    <?php elseif ($tab === 'defense'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="sc-btn sc-btn-red" onclick="document.getElementById('modalDefense').classList.add('open')"><i class="fas fa-shield-alt"></i> Log Defense Event</button></div>
        <?php endif; ?>
        <?php
        $tlColors = ['low'=>'#22c55e','moderate'=>'#f59e0b','high'=>'#f97316','critical'=>'#ef4444'];
        foreach ($defense as $d): ?>
            <div class="sc-card" style="border-left:3px solid <?= $tlColors[$d['threat_level']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#ef4444"><?= htmlspecialchars($d['event_code']) ?></strong>
                        <span class="sc-badge" style="background:<?= $tlColors[$d['threat_level']] ?>20;color:<?= $tlColors[$d['threat_level']] ?>;margin-left:.25rem"><?= strtoupper($d['threat_level']) ?></span>
                        <span style="color:#94a3b8;font-size:.8rem;margin-left:.25rem"><?= strtoupper(str_replace('_', ' ', $d['event_type'])) ?></span>
                    </div>
                    <span style="color:#64748b;font-size:.75rem"><?= date('M j, Y H:i', strtotime($d['logged_at'])) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($d['description']) ?></p>
                <?php if ($d['response']): ?><div style="color:#86efac;font-size:.85rem;margin-top:.25rem"><strong>Response:</strong> <?= htmlspecialchars($d['response']) ?></div><?php endif; ?>
                <?php if ($d['weapon_authorized']): ?>
                    <div style="background:#ef444420;border:1px solid #ef444440;border-radius:6px;padding:.5rem;margin-top:.5rem;font-size:.8rem;color:#fca5a5"><i class="fas fa-crosshairs"></i> WEAPON PLATFORM AUTHORIZED by <?= htmlspecialchars($d['authorizer'] ?? 'Commander') ?></div>
                <?php elseif ($isCommander && !$d['resolved_at']): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="authorize_weapon"><input type="hidden" name="event_id" value="<?= $d['id'] ?>">
                        <input type="text" name="response" class="sc-input" style="flex:1" placeholder="Response / authorization notes...">
                        <button class="sc-btn-sm sc-btn sc-btn-red"><i class="fas fa-crosshairs"></i> Authorize Weapon</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($defense)): ?><div class="sc-card" style="text-align:center;color:#64748b"><p>No defense events.</p></div><?php endif; ?>

    <!-- ═══ TAB: STATIONS ═══ -->
    <?php elseif ($tab === 'stations'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="sc-btn" onclick="document.getElementById('modalStation').classList.add('open')"><i class="fas fa-space-shuttle"></i> Commission Station</button></div>
        <?php endif; ?>
        <?php
        $ssColors = ['operational'=>'#22c55e','construction'=>'#f59e0b','maintenance'=>'#3b82f6','abandoned'=>'#64748b'];
        foreach ($stations as $s): $modules = json_decode($s['modules'] ?? '[]', true); ?>
            <div class="sc-card" style="border-left:3px solid <?= $ssColors[$s['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-space-shuttle" style="color:#06b6d4"></i>
                        <strong style="color:#06b6d4;margin-left:.25rem"><?= htmlspecialchars($s['station_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($s['station_name']) ?></strong>
                    </div>
                    <span class="sc-badge" style="background:<?= $ssColors[$s['status']] ?>20;color:<?= $ssColors[$s['status']] ?>"><?= strtoupper($s['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Crew: <?= (int)$s['current_crew'] ?>/<?= $s['crew_capacity'] ?>
                    <?php if ($s['commissioned_at']): ?>&bull; Commissioned: <?= date('M j, Y', strtotime($s['commissioned_at'])) ?><?php endif; ?>
                </div>
                <?php if (!empty($modules)): ?>
                    <div style="margin-top:.5rem;display:flex;gap:4px;flex-wrap:wrap">
                        <?php foreach ($modules as $mod): ?>
                            <span style="background:#06b6d420;color:#06b6d4;padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:600"><?= htmlspecialchars($mod) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <!-- Crew bar -->
                <div style="margin-top:.5rem;background:#0a0a14;border-radius:4px;height:6px;overflow:hidden">
                    <div style="width:<?= $s['crew_capacity'] > 0 ? round(($s['current_crew'] / $s['crew_capacity']) * 100) : 0 ?>%;height:100%;background:#06b6d4;border-radius:4px"></div>
                </div>
                <?php if ($isOfficer): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_station"><input type="hidden" name="station_id" value="<?= $s['id'] ?>">
                        <input type="number" name="current_crew" class="sc-input" style="width:80px" placeholder="Crew" value="<?= (int)$s['current_crew'] ?>" min="0" max="<?= $s['crew_capacity'] ?>">
                        <select name="station_status" class="sc-select" style="width:auto"><?php foreach (['operational','construction','maintenance','abandoned'] as $ss): ?><option value="<?= $ss ?>" <?= $s['status']===$ss?'selected':'' ?>><?= ucfirst($ss) ?></option><?php endforeach; ?></select>
                        <button class="sc-btn-sm sc-btn"><i class="fas fa-save"></i> Update</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($stations)): ?><div class="sc-card" style="text-align:center;color:#64748b"><p>No stations commissioned.</p></div><?php endif; ?>

    <!-- ═══ TAB: ORBIT LOG ═══ -->
    <?php elseif ($tab === 'orbits'): ?>
        <?php
        $otColors = ['insertion'=>'#22c55e','correction'=>'#3b82f6','transfer'=>'#f59e0b','decay_alert'=>'#ef4444'];
        foreach ($orbits as $o): ?>
            <div class="sc-card" style="border-left:3px solid <?= $otColors[$o['orbit_update_type']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#06b6d4"><?= htmlspecialchars($o['asset_code']) ?></strong>
                        <span style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($o['asset_name']) ?></span>
                        <span class="sc-badge" style="background:<?= $otColors[$o['orbit_update_type']] ?>20;color:<?= $otColors[$o['orbit_update_type']] ?>;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $o['orbit_update_type'])) ?></span>
                    </div>
                    <span style="color:#64748b;font-size:.75rem"><?= date('M j, Y H:i', strtotime($o['updated_at'])) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Alt: <?= number_format($o['old_altitude_km'] ?? 0) ?> → <?= number_format($o['new_altitude_km']) ?> km &bull;
                    Incl: <?= number_format($o['old_inclination'] ?? 0, 1) ?>° → <?= number_format($o['new_inclination'] ?? 0, 1) ?>° &bull;
                    ΔV: <?= number_format($o['delta_v'], 2) ?> m/s
                </div>
                <?php if ($o['reason']): ?><div style="color:#64748b;font-size:.8rem;margin-top:.25rem"><?= htmlspecialchars($o['reason']) ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($orbits)): ?><div class="sc-card" style="text-align:center;color:#64748b"><p>No orbital updates logged.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="sc-modal-bg" id="modalAsset"><div class="sc-modal"><h3><i class="fas fa-satellite-dish"></i> Deploy Orbital Asset</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="register_asset">
<div class="sc-form-row"><label class="sc-label">Asset Name</label><input type="text" name="asset_name" class="sc-input" required></div>
<div style="display:flex;gap:.75rem"><div class="sc-form-row" style="flex:1"><label class="sc-label">Asset Type</label><select name="asset_type" class="sc-select"><option value="satellite">Satellite</option><option value="probe">Probe</option><option value="relay">Relay</option><option value="station_module">Station Module</option><option value="weapon_platform">Weapon Platform ⚠️</option></select></div><div class="sc-form-row" style="flex:1"><label class="sc-label">Orbit</label><select name="orbit_type" class="sc-select"><option value="LEO">LEO</option><option value="MEO">MEO</option><option value="GEO">GEO</option><option value="HEO">HEO</option><option value="lunar">Lunar</option><option value="deep_space">Deep Space</option></select></div></div>
<div style="display:flex;gap:.75rem"><div class="sc-form-row" style="flex:1"><label class="sc-label">Altitude (km)</label><input type="number" name="altitude_km" class="sc-input" value="400" min="1"></div><div class="sc-form-row" style="flex:1"><label class="sc-label">Inclination (°)</label><input type="number" name="inclination" class="sc-input" value="51.6" step="0.1"></div><div class="sc-form-row" style="flex:1"><label class="sc-label">Lifetime (yr)</label><input type="number" name="lifetime" class="sc-input" value="10" min="1"></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sc-btn sc-btn-outline" onclick="this.closest('.sc-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sc-btn"><i class="fas fa-rocket"></i> Deploy</button></div></form></div></div>

<div class="sc-modal-bg" id="modalLaunch"><div class="sc-modal"><h3><i class="fas fa-rocket"></i> Schedule Launch</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="schedule_launch">
<div class="sc-form-row"><label class="sc-label">Mission Name</label><input type="text" name="mission_name" class="sc-input" required></div>
<div style="display:flex;gap:.75rem"><div class="sc-form-row" style="flex:1"><label class="sc-label">Payload</label><input type="text" name="payload" class="sc-input" placeholder="e.g. Comm Satellite Alpha"></div><div class="sc-form-row" style="flex:1"><label class="sc-label">Vehicle</label><input type="text" name="vehicle" class="sc-input" placeholder="e.g. Falcon Heavy"></div></div>
<div style="display:flex;gap:.75rem"><div class="sc-form-row" style="flex:1"><label class="sc-label">Target Orbit</label><select name="target_orbit" class="sc-select"><option value="LEO">LEO</option><option value="MEO">MEO</option><option value="GEO">GEO</option><option value="HEO">HEO</option><option value="lunar">Lunar</option><option value="deep_space">Deep Space</option></select></div><div class="sc-form-row" style="flex:1"><label class="sc-label">Scheduled Date/Time</label><input type="datetime-local" name="scheduled_at" class="sc-input"></div></div>
<div class="sc-form-row"><label class="sc-label">Notes</label><textarea name="notes" class="sc-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sc-btn sc-btn-outline" onclick="this.closest('.sc-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sc-btn"><i class="fas fa-rocket"></i> Schedule</button></div></form></div></div>

<div class="sc-modal-bg" id="modalDefense"><div class="sc-modal"><h3><i class="fas fa-shield-alt"></i> Log Defense Event</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="log_defense_event">
<div style="display:flex;gap:.75rem"><div class="sc-form-row" style="flex:1"><label class="sc-label">Event Type</label><select name="event_type" class="sc-select"><option value="debris_alert">Debris Alert</option><option value="intrusion">Intrusion</option><option value="asat_threat">ASAT Threat</option><option value="collision_warning">Collision Warning</option><option value="emp_event">EMP Event</option><option value="unknown">Unknown</option></select></div><div class="sc-form-row" style="flex:1"><label class="sc-label">Threat Level</label><select name="threat_level" class="sc-select"><option value="low">Low</option><option value="moderate">Moderate</option><option value="high">High</option><option value="critical">Critical</option></select></div></div>
<div class="sc-form-row"><label class="sc-label">Description</label><textarea name="description" class="sc-textarea" required></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sc-btn sc-btn-outline" onclick="this.closest('.sc-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sc-btn sc-btn-red"><i class="fas fa-shield-alt"></i> Log Event</button></div></form></div></div>

<div class="sc-modal-bg" id="modalStation"><div class="sc-modal"><h3><i class="fas fa-space-shuttle"></i> Commission Station</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="commission_station">
<div class="sc-form-row"><label class="sc-label">Station Name</label><input type="text" name="station_name" class="sc-input" required></div>
<div class="sc-form-row"><label class="sc-label">Crew Capacity</label><input type="number" name="crew_capacity" class="sc-input" value="6" min="2" max="100"></div>
<div class="sc-form-row"><label class="sc-label">Modules (comma-separated)</label><input type="text" name="modules" class="sc-input" placeholder="e.g. Hab-1, Science Lab, Docking Bay, Comms Array"></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="sc-btn sc-btn-outline" onclick="this.closest('.sc-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="sc-btn"><i class="fas fa-space-shuttle"></i> Commission</button></div></form></div></div>

<script>
document.querySelectorAll('.sc-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
