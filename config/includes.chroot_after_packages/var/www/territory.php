<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_territory'])) $_SESSION['csrf_territory'] = bin2hex(random_bytes(32));
requireRank(2);

$msg = '';
$msgType = '';

// Get user's unit
$unitStmt = $db->prepare("SELECT um.unit_id, um.role, mu.unit_name, mu.unit_code FROM unit_members um JOIN military_units mu ON mu.id = um.unit_id WHERE um.client_id = ? AND um.status = 'active' LIMIT 1");
$unitStmt->execute([$clientId]);
$userUnit = $unitStmt->fetch(PDO::FETCH_ASSOC);
$userUnitId = $userUnit['unit_id'] ?? null;
$userUnitRole = $userUnit['role'] ?? null;

// Zone type config
$zoneTypes = [
    'outpost'  => ['icon' => 'fa-campground',    'color' => '#6B7280', 'label' => 'Outpost'],
    'base'     => ['icon' => 'fa-building',       'color' => '#3B82F6', 'label' => 'Base'],
    'fortress' => ['icon' => 'fa-chess-rook',     'color' => '#F59E0B', 'label' => 'Fortress'],
    'citadel'  => ['icon' => 'fa-fort-awesome',   'color' => '#8B5CF6', 'label' => 'Citadel'],
    'capital'  => ['icon' => 'fa-crown',          'color' => '#EAB308', 'label' => 'Capital'],
];

// NCO+ roles that can claim/attack
$ncoRoles = ['commander', 'officer', 'nco', 'leader'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_territory'], $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } elseif (!$userUnitId) {
        $msg = 'You must be in a unit to perform territory actions.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $zoneId = (int)($_POST['zone_id'] ?? 0);

        $zone = $db->prepare("SELECT * FROM territory_zones WHERE id = ? AND is_active = 1");
        $zone->execute([$zoneId]);
        $zone = $zone->fetch(PDO::FETCH_ASSOC);

        if (!$zone) {
            $msg = 'Zone not found or inactive.'; $msgType = 'error';
        } elseif ($action === 'claim') {
            if ($userRankTier < 4 || !in_array($userUnitRole, $ncoRoles)) {
                $msg = 'NCO+ rank (Tier 4+) required to claim territory.'; $msgType = 'error';
            } else {
                $existing = $db->prepare("SELECT id FROM territory_control WHERE territory_id = ?");
                $existing->execute([$zoneId]);
                if ($existing->fetch()) {
                    $msg = 'This zone is already controlled.'; $msgType = 'error';
                } else {
                    $db->beginTransaction();
                    try {
                        $initDefense = min(20, $userRankTier * 5);
                        $db->prepare("INSERT INTO territory_control (territory_id, controlling_unit_id, controlling_client_id, captured_at, defense_strength) VALUES (?, ?, ?, NOW(), ?)")
                           ->execute([$zoneId, $userUnitId, $clientId, $initDefense]);
                        awardXP($clientId, 'territory_capture', ['zone' => $zone['zone_code'], 'type' => 'claim']);
                        $db->commit();
                        $msg = "Claimed {$zone['zone_name']}! Defense set to {$initDefense}."; $msgType = 'success';
                    } catch (Exception $e) {
                        $db->rollBack();
                        $msg = 'Claim failed. Try again.'; $msgType = 'error';
                    }
                }
            }
        } elseif ($action === 'attack') {
            if ($userRankTier < 4) {
                $msg = 'Tier 4+ required to attack territory.'; $msgType = 'error';
            } else {
                $ctrl = $db->prepare("SELECT tc.*, mu.unit_name AS def_unit_name FROM territory_control tc JOIN military_units mu ON mu.id = tc.controlling_unit_id WHERE tc.territory_id = ?");
                $ctrl->execute([$zoneId]);
                $ctrl = $ctrl->fetch(PDO::FETCH_ASSOC);
                if (!$ctrl) {
                    $msg = 'Zone is unclaimed. Claim it instead.'; $msgType = 'error';
                } elseif ((int)$ctrl['controlling_unit_id'] === $userUnitId) {
                    $msg = 'You cannot attack your own territory.'; $msgType = 'error';
                } else {
                    $db->beginTransaction();
                    try {
                        $attackPower = $userRankTier * 8 + random_int(5, 25);
                        $defensePower = (int)$ctrl['defense_strength'] + random_int(0, 15);
                        $difficulty = (int)$zone['capture_difficulty'];
                        $defensePower += $difficulty;

                        if ($attackPower > $defensePower) {
                            $result = 'attacker_win';
                            $atkXP = 50 + ($difficulty * 5);
                            $defXP = 10;
                        } elseif ($attackPower === $defensePower) {
                            $result = 'draw';
                            $atkXP = 15;
                            $defXP = 15;
                        } else {
                            $result = 'defender_win';
                            $atkXP = 10;
                            $defXP = 25;
                        }

                        $battleLog = json_encode([
                            'attacker_power' => $attackPower,
                            'defender_power' => $defensePower,
                            'difficulty' => $difficulty,
                            'timestamp' => date('Y-m-d H:i:s'),
                        ]);

                        $db->prepare("INSERT INTO territory_battles (territory_id, attacker_unit_id, attacker_client_id, defender_unit_id, defender_client_id, result, attacker_score, defender_score, xp_awarded_attacker, xp_awarded_defender, battle_log, fought_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())")
                           ->execute([$zoneId, $userUnitId, $clientId, $ctrl['controlling_unit_id'], $ctrl['controlling_client_id'], $result, $attackPower, $defensePower, $atkXP, $defXP, $battleLog]);

                        if ($result === 'attacker_win') {
                            $db->prepare("UPDATE territory_control SET controlling_unit_id = ?, controlling_client_id = ?, captured_at = NOW(), defense_strength = ?, last_battle_at = NOW() WHERE territory_id = ?")
                               ->execute([$userUnitId, $clientId, min(20, $userRankTier * 5), $zoneId]);
                        } else {
                            $newDef = max(1, (int)$ctrl['defense_strength'] - ($result === 'draw' ? 3 : 1));
                            $db->prepare("UPDATE territory_control SET defense_strength = ?, last_battle_at = NOW() WHERE territory_id = ?")
                               ->execute([$newDef, $zoneId]);
                        }

                        awardXP($clientId, 'territory_capture', ['zone' => $zone['zone_code'], 'type' => 'attack', 'result' => $result, 'xp' => $atkXP]);
                        if ($ctrl['controlling_client_id']) {
                            awardXP((int)$ctrl['controlling_client_id'], 'territory_capture', ['zone' => $zone['zone_code'], 'type' => 'defend', 'result' => $result, 'xp' => $defXP]);
                        }
                        $db->commit();

                        $resultLabels = ['attacker_win' => 'VICTORY', 'defender_win' => 'DEFEAT', 'draw' => 'DRAW'];
                        $msg = "Battle result: {$resultLabels[$result]}! Attack: {$attackPower} vs Defense: {$defensePower}. +{$atkXP} XP earned.";
                        $msgType = $result === 'attacker_win' ? 'success' : ($result === 'draw' ? 'warning' : 'error');
                    } catch (Exception $e) {
                        $db->rollBack();
                        $msg = 'Battle failed. Try again.'; $msgType = 'error';
                    }
                }
            }
        } elseif ($action === 'harvest') {
            $ctrl = $db->prepare("SELECT * FROM territory_control WHERE territory_id = ? AND controlling_unit_id = ?");
            $ctrl->execute([$zoneId, $userUnitId]);
            if (!$ctrl->fetch()) {
                $msg = 'Your unit does not control this zone.'; $msgType = 'error';
            } else {
                $resources = $db->prepare("SELECT * FROM territory_resources WHERE zone_id = ? AND amount > 0");
                $resources->execute([$zoneId]);
                $harvested = $resources->fetchAll(PDO::FETCH_ASSOC);
                if (empty($harvested)) {
                    $msg = 'No resources available to harvest.'; $msgType = 'error';
                } else {
                    $db->beginTransaction();
                    try {
                        $totalCredits = 0;
                        $totalXP = 0;
                        foreach ($harvested as $res) {
                            $amt = (float)$res['amount'];
                            if ($res['resource_type'] === 'credits') {
                                $totalCredits += $amt;
                                $db->prepare("INSERT IGNORE INTO military_credits (client_id, balance, total_earned, total_spent) VALUES (?, 0, 0, 0)")->execute([$clientId]);
                                $db->prepare("UPDATE military_credits SET balance = balance + ?, total_earned = total_earned + ?, last_transaction_at = NOW() WHERE client_id = ?")->execute([$amt, $amt, $clientId]);
                                $db->prepare("INSERT INTO credit_transactions (client_id, amount, transaction_type, description, reference_type, balance_after, created_at) VALUES (?, ?, 'bonus', ?, 'territory', (SELECT balance FROM military_credits WHERE client_id = ?), NOW())")
                                   ->execute([$clientId, $amt, "Harvest from {$zone['zone_name']}", $clientId]);
                            } elseif ($res['resource_type'] === 'xp') {
                                $totalXP += (int)$amt;
                            }
                            $db->prepare("UPDATE territory_resources SET amount = 0, last_harvest = NOW() WHERE id = ?")->execute([$res['id']]);
                        }
                        if ($totalXP > 0) {
                            awardXP($clientId, 'territory_capture', ['zone' => $zone['zone_code'], 'type' => 'harvest', 'xp' => $totalXP]);
                        }
                        $db->commit();
                        $parts = [];
                        if ($totalCredits > 0) $parts[] = number_format($totalCredits, 2) . ' credits';
                        if ($totalXP > 0) $parts[] = "{$totalXP} XP";
                        $msg = "Harvested from {$zone['zone_name']}: " . implode(', ', $parts) . '.'; $msgType = 'success';
                    } catch (Exception $e) {
                        $db->rollBack();
                        $msg = 'Harvest failed. Try again.'; $msgType = 'error';
                    }
                }
            }
        }
    }
}

// Load all active zones
$zones = $db->query("SELECT tz.*, tc.controlling_unit_id, tc.controlling_client_id, tc.defense_strength, tc.captured_at, mu.unit_name AS ctrl_unit_name FROM territory_zones tz LEFT JOIN territory_control tc ON tc.territory_id = tz.id LEFT JOIN military_units mu ON mu.id = tc.controlling_unit_id WHERE tz.is_active = 1 ORDER BY tz.zone_type DESC, tz.zone_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Detail view
$detailZone = null;
$detailCtrl = null;
$detailBattles = [];
$detailResources = [];
if (!empty($_GET['zone'])) {
    $zCode = trim($_GET['zone']);
    $dz = $db->prepare("SELECT * FROM territory_zones WHERE zone_code = ? AND is_active = 1");
    $dz->execute([$zCode]);
    $detailZone = $dz->fetch(PDO::FETCH_ASSOC);
    if ($detailZone) {
        $dc = $db->prepare("SELECT tc.*, mu.unit_name FROM territory_control tc LEFT JOIN military_units mu ON mu.id = tc.controlling_unit_id WHERE tc.territory_id = ?");
        $dc->execute([$detailZone['id']]);
        $detailCtrl = $dc->fetch(PDO::FETCH_ASSOC);

        $db2 = $db->prepare("SELECT tb.*, a_mu.unit_name AS atk_unit, d_mu.unit_name AS def_unit FROM territory_battles tb LEFT JOIN military_units a_mu ON a_mu.id = tb.attacker_unit_id LEFT JOIN military_units d_mu ON d_mu.id = tb.defender_unit_id WHERE tb.territory_id = ? ORDER BY tb.fought_at DESC LIMIT 20");
        $db2->execute([$detailZone['id']]);
        $detailBattles = $db2->fetchAll(PDO::FETCH_ASSOC);

        $dr = $db->prepare("SELECT * FROM territory_resources WHERE zone_id = ? ORDER BY resource_type");
        $dr->execute([$detailZone['id']]);
        $detailResources = $dr->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Recent battles (global)
$recentBattles = $db->query("SELECT tb.*, tz.zone_name, tz.zone_code, a_mu.unit_name AS atk_unit, d_mu.unit_name AS def_unit FROM territory_battles tb JOIN territory_zones tz ON tz.id = tb.territory_id LEFT JOIN military_units a_mu ON a_mu.id = tb.attacker_unit_id LEFT JOIN military_units d_mu ON d_mu.id = tb.defender_unit_id ORDER BY tb.fought_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

$csrf = $_SESSION['csrf_territory'];
$pageTitle = $detailZone ? htmlspecialchars($detailZone['zone_name']) . ' — Territory' : 'Territory Control';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0f172a;color:#e2e8f0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;min-height:100vh}
a{color:#3b82f6;text-decoration:none}a:hover{text-decoration:underline}
.wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
.hdr{display:flex;align-items:center;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.hdr h1{font-size:1.6rem;color:#f8fafc;display:flex;align-items:center;gap:10px}
.hdr h1 i{color:#3b82f6}
.rank-badge{background:#1e293b;border:1px solid #334155;padding:4px 12px;border-radius:6px;font-size:.8rem;color:#94a3b8}
.unit-badge{background:#1e3a5f;border:1px solid #2563eb;padding:4px 12px;border-radius:6px;font-size:.8rem;color:#93c5fd}
.msg{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.9rem;border:1px solid}
.msg.success{background:#052e16;border-color:#059669;color:#6ee7b7}
.msg.error{background:#450a0a;border-color:#dc2626;color:#fca5a5}
.msg.warning{background:#451a03;border-color:#d97706;color:#fde68a}
.card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:20px;margin-bottom:16px}
.card h2{font-size:1.1rem;color:#f1f5f9;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.card h2 i{font-size:.9rem;color:#60a5fa}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.zone-card{background:#0f172a;border:2px solid #334155;border-radius:8px;padding:14px;text-align:center;transition:all .2s;cursor:pointer;position:relative}
.zone-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.4)}
.zone-card.unclaimed{border-color:#4B5563}.zone-card.yours{border-color:#059669}.zone-card.enemy{border-color:#DC2626}.zone-card.contested{border-color:#D97706}
.zone-icon{font-size:1.8rem;margin-bottom:6px}
.zone-name{font-size:.85rem;font-weight:600;color:#f1f5f9;margin-bottom:4px}
.zone-type-label{font-size:.7rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}
.zone-ctrl{font-size:.7rem;color:#94a3b8;margin-bottom:6px}
.def-bar{height:6px;background:#1e293b;border-radius:3px;overflow:hidden;margin-top:4px}
.def-bar-fill{height:100%;border-radius:3px;transition:width .3s}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:none;border-radius:6px;font-size:.85rem;cursor:pointer;font-weight:600;transition:all .15s}
.btn:hover{filter:brightness(1.15)}
.btn-blue{background:#2563eb;color:#fff}.btn-green{background:#059669;color:#fff}.btn-red{background:#dc2626;color:#fff}.btn-yellow{background:#d97706;color:#fff}
.btn-sm{padding:5px 10px;font-size:.78rem}
.btn:disabled{opacity:.4;cursor:not-allowed}
.back-link{display:inline-flex;align-items:center;gap:6px;color:#60a5fa;font-size:.85rem;margin-bottom:16px}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{text-align:left;color:#94a3b8;padding:8px 10px;border-bottom:1px solid #334155;font-weight:600;text-transform:uppercase;font-size:.7rem;letter-spacing:.5px}
td{padding:8px 10px;border-bottom:1px solid #1e293b;color:#cbd5e1}
tr:hover td{background:#0f172a}
.result-win{color:#34d399;font-weight:700}.result-loss{color:#f87171;font-weight:700}.result-draw{color:#fbbf24;font-weight:700}
.stat-row{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:12px}
.stat-item{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px 16px;flex:1;min-width:140px}
.stat-label{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
.stat-val{font-size:1.2rem;font-weight:700;color:#f1f5f9;margin-top:2px}
.res-tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.72rem;font-weight:600;text-transform:uppercase}
.res-credits{background:#052e16;color:#6ee7b7}.res-xp{background:#1e1b4b;color:#a78bfa}.res-intel{background:#0c4a6e;color:#7dd3fc}
.res-supplies{background:#451a03;color:#fdba74}.res-personnel{background:#4a044e;color:#f0abfc}
.actions-bar{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.empty{text-align:center;color:#64748b;padding:32px;font-size:.9rem}
@media(max-width:640px){.grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}.stat-row{flex-direction:column}.hdr{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body>
<div class="wrap">
<div class="hdr">
    <h1><i class="fas fa-map-marked-alt"></i> Territory Control</h1>
    <span class="rank-badge"><i class="fas fa-chevron-up"></i> <?= htmlspecialchars($userRankCode) ?> (T<?= $userRankTier ?>)</span>
    <?php if ($userUnit): ?>
        <span class="unit-badge"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($userUnit['unit_name']) ?></span>
    <?php endif; ?>
</div>

<?php if ($msg): ?>
<div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($detailZone): ?>
<!-- ZONE DETAIL VIEW -->
<a href="territory.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Territory Map</a>

<?php
    $zt = $zoneTypes[$detailZone['zone_type']] ?? $zoneTypes['outpost'];
    $isYours = $detailCtrl && (int)($detailCtrl['controlling_unit_id'] ?? 0) === $userUnitId;
    $maxDef = (int)$detailZone['max_defense'];
    $curDef = $detailCtrl ? (int)$detailCtrl['defense_strength'] : 0;
    $defPct = $maxDef > 0 ? min(100, round($curDef / $maxDef * 100)) : 0;
?>

<div class="card">
    <h2><i class="fas <?= $zt['icon'] ?>" style="color:<?= $zt['color'] ?>"></i> <?= htmlspecialchars($detailZone['zone_name']) ?></h2>
    <div class="stat-row">
        <div class="stat-item"><div class="stat-label">Zone Type</div><div class="stat-val" style="color:<?= $zt['color'] ?>"><?= $zt['label'] ?></div></div>
        <div class="stat-item"><div class="stat-label">Region</div><div class="stat-val"><?= htmlspecialchars($detailZone['region'] ?? 'Unknown') ?></div></div>
        <div class="stat-item"><div class="stat-label">Difficulty</div><div class="stat-val"><?= (int)$detailZone['capture_difficulty'] ?>/10</div></div>
        <div class="stat-item"><div class="stat-label">Resources/hr</div><div class="stat-val"><?= number_format((float)$detailZone['resource_generation'], 1) ?></div></div>
        <div class="stat-item"><div class="stat-label">XP/hr</div><div class="stat-val"><?= number_format((float)$detailZone['xp_per_hour'], 1) ?></div></div>
    </div>
    <?php if ($detailZone['description']): ?>
        <p style="color:#94a3b8;font-size:.85rem;margin-bottom:12px"><?= htmlspecialchars($detailZone['description']) ?></p>
    <?php endif; ?>

    <div style="margin-top:8px">
        <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#94a3b8;margin-bottom:4px">
            <span>Defense Strength</span><span><?= $curDef ?>/<?= $maxDef ?></span>
        </div>
        <div class="def-bar" style="height:10px">
            <div class="def-bar-fill" style="width:<?= $defPct ?>%;background:<?= $defPct > 60 ? '#059669' : ($defPct > 30 ? '#d97706' : '#dc2626') ?>"></div>
        </div>
    </div>

    <div style="margin-top:12px;padding:10px;background:#0f172a;border-radius:6px;border:1px solid #334155">
        <div style="font-size:.75rem;color:#64748b;text-transform:uppercase;margin-bottom:4px">Controller</div>
        <?php if ($detailCtrl): ?>
            <div style="font-size:.95rem;font-weight:700;color:<?= $isYours ? '#34d399' : '#f87171' ?>">
                <i class="fas fa-<?= $isYours ? 'check-circle' : 'crosshairs' ?>"></i>
                <?= htmlspecialchars($detailCtrl['unit_name'] ?? 'Unknown Unit') ?>
                <?= $isYours ? ' (Your Unit)' : '' ?>
            </div>
            <div style="font-size:.75rem;color:#64748b;margin-top:2px">Captured <?= date('M j, Y H:i', strtotime($detailCtrl['captured_at'])) ?></div>
        <?php else: ?>
            <div style="font-size:.95rem;color:#6B7280"><i class="fas fa-flag"></i> Unclaimed — Ready for capture</div>
        <?php endif; ?>
    </div>

    <?php if ($userUnit): ?>
    <div class="actions-bar">
        <?php if (!$detailCtrl): ?>
            <?php if ($userRankTier >= 4 && in_array($userUnitRole, $ncoRoles)): ?>
            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="claim"><input type="hidden" name="zone_id" value="<?= $detailZone['id'] ?>">
                <button class="btn btn-green" onclick="return confirm('Claim this territory for your unit?')"><i class="fas fa-flag"></i> Claim Territory</button></form>
            <?php else: ?>
            <button class="btn btn-green" disabled title="NCO+ (Tier 4+) required"><i class="fas fa-lock"></i> Claim (NCO+ Required)</button>
            <?php endif; ?>
        <?php elseif ($isYours): ?>
            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="harvest"><input type="hidden" name="zone_id" value="<?= $detailZone['id'] ?>">
                <button class="btn btn-yellow"><i class="fas fa-boxes-stacked"></i> Harvest Resources</button></form>
        <?php else: ?>
            <?php if ($userRankTier >= 4): ?>
            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="attack"><input type="hidden" name="zone_id" value="<?= $detailZone['id'] ?>">
                <button class="btn btn-red" onclick="return confirm('Launch attack on this territory? This will engage in combat!')"><i class="fas fa-crosshairs"></i> Attack</button></form>
            <?php else: ?>
            <button class="btn btn-red" disabled title="Tier 4+ required"><i class="fas fa-lock"></i> Attack (T4+ Required)</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ZONE RESOURCES -->
<?php if (!empty($detailResources)): ?>
<div class="card">
    <h2><i class="fas fa-cubes"></i> Available Resources</h2>
    <table>
        <tr><th>Type</th><th>Amount</th><th>Last Harvest</th></tr>
        <?php foreach ($detailResources as $r): ?>
        <tr>
            <td><span class="res-tag res-<?= htmlspecialchars($r['resource_type']) ?>"><?= htmlspecialchars($r['resource_type']) ?></span></td>
            <td style="font-weight:600"><?= number_format((float)$r['amount'], 2) ?></td>
            <td style="color:#64748b"><?= $r['last_harvest'] ? date('M j H:i', strtotime($r['last_harvest'])) : 'Never' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<!-- ZONE BATTLE HISTORY -->
<div class="card">
    <h2><i class="fas fa-swords"></i> Battle History</h2>
    <?php if (empty($detailBattles)): ?>
        <div class="empty"><i class="fas fa-dove"></i> No battles fought here yet.</div>
    <?php else: ?>
    <table>
        <tr><th>Date</th><th>Attacker</th><th>Defender</th><th>Result</th><th>Score</th></tr>
        <?php foreach ($detailBattles as $b): ?>
        <tr>
            <td style="color:#64748b"><?= date('M j H:i', strtotime($b['fought_at'])) ?></td>
            <td><?= htmlspecialchars($b['atk_unit'] ?? '?') ?></td>
            <td><?= htmlspecialchars($b['def_unit'] ?? '?') ?></td>
            <td>
                <?php if ($b['result'] === 'attacker_win'): ?><span class="result-win">ATK WIN</span>
                <?php elseif ($b['result'] === 'defender_win'): ?><span class="result-loss">DEF WIN</span>
                <?php else: ?><span class="result-draw">DRAW</span><?php endif; ?>
            </td>
            <td><?= (int)$b['attacker_score'] ?> / <?= (int)$b['defender_score'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- TERRITORY MAP VIEW -->
<div class="card">
    <h2><i class="fas fa-globe"></i> Territory Map</h2>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:14px;font-size:.75rem">
        <span><span style="display:inline-block;width:10px;height:10px;background:#4B5563;border-radius:2px;margin-right:4px"></span>Unclaimed</span>
        <span><span style="display:inline-block;width:10px;height:10px;background:#059669;border-radius:2px;margin-right:4px"></span>Your Unit</span>
        <span><span style="display:inline-block;width:10px;height:10px;background:#DC2626;border-radius:2px;margin-right:4px"></span>Enemy</span>
        <span><span style="display:inline-block;width:10px;height:10px;background:#D97706;border-radius:2px;margin-right:4px"></span>Contested</span>
    </div>
    <?php if (empty($zones)): ?>
        <div class="empty"><i class="fas fa-map"></i> No active territory zones yet.</div>
    <?php else: ?>
    <div class="grid">
        <?php foreach ($zones as $z):
            $zt = $zoneTypes[$z['zone_type']] ?? $zoneTypes['outpost'];
            $hasCtrl = !empty($z['controlling_unit_id']);
            $isYoursZ = $hasCtrl && (int)$z['controlling_unit_id'] === $userUnitId;
            $wasRecentBattle = $hasCtrl && $z['captured_at'] && (time() - strtotime($z['captured_at'])) < 3600;
            if (!$hasCtrl) $statusClass = 'unclaimed';
            elseif ($isYoursZ) $statusClass = 'yours';
            elseif ($wasRecentBattle) $statusClass = 'contested';
            else $statusClass = 'enemy';
            $maxD = (int)$z['max_defense'];
            $curD = (int)($z['defense_strength'] ?? 0);
            $dPct = $maxD > 0 ? min(100, round($curD / $maxD * 100)) : 0;
        ?>
        <a href="territory.php?zone=<?= urlencode($z['zone_code']) ?>" style="text-decoration:none;color:inherit">
        <div class="zone-card <?= $statusClass ?>">
            <div class="zone-icon" style="color:<?= $zt['color'] ?>"><i class="fas <?= $zt['icon'] ?>"></i></div>
            <div class="zone-name"><?= htmlspecialchars($z['zone_name']) ?></div>
            <div class="zone-type-label" style="color:<?= $zt['color'] ?>"><?= $zt['label'] ?></div>
            <div class="zone-ctrl">
                <?php if ($isYoursZ): ?>
                    <i class="fas fa-check-circle" style="color:#059669"></i> Your Unit
                <?php elseif ($hasCtrl): ?>
                    <i class="fas fa-shield-alt" style="color:#DC2626"></i> <?= htmlspecialchars($z['ctrl_unit_name'] ?? 'Unknown') ?>
                <?php else: ?>
                    <i class="far fa-flag"></i> Unclaimed
                <?php endif; ?>
            </div>
            <?php if ($hasCtrl): ?>
            <div class="def-bar"><div class="def-bar-fill" style="width:<?= $dPct ?>%;background:<?= $dPct > 60 ? '#059669' : ($dPct > 30 ? '#d97706' : '#dc2626') ?>"></div></div>
            <?php endif; ?>
        </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- BATTLE LOG -->
<div class="card">
    <h2><i class="fas fa-scroll"></i> Recent Battles</h2>
    <?php if (empty($recentBattles)): ?>
        <div class="empty"><i class="fas fa-peace"></i> No territory battles recorded yet.</div>
    <?php else: ?>
    <table>
        <tr><th>When</th><th>Zone</th><th>Attacker</th><th>Defender</th><th>Result</th><th>Score</th><th>XP</th></tr>
        <?php foreach ($recentBattles as $b): ?>
        <tr>
            <td style="color:#64748b;white-space:nowrap"><?= date('M j H:i', strtotime($b['fought_at'])) ?></td>
            <td><a href="territory.php?zone=<?= urlencode($b['zone_code']) ?>"><?= htmlspecialchars($b['zone_name']) ?></a></td>
            <td><?= htmlspecialchars($b['atk_unit'] ?? '?') ?></td>
            <td><?= htmlspecialchars($b['def_unit'] ?? '?') ?></td>
            <td>
                <?php if ($b['result'] === 'attacker_win'): ?><span class="result-win">ATK WIN</span>
                <?php elseif ($b['result'] === 'defender_win'): ?><span class="result-loss">DEF WIN</span>
                <?php else: ?><span class="result-draw">DRAW</span><?php endif; ?>
            </td>
            <td><?= (int)$b['attacker_score'] ?>/<?= (int)$b['defender_score'] ?></td>
            <td style="color:#a78bfa"><?= (int)$b['xp_awarded_attacker'] ?>/<?= (int)$b['xp_awarded_defender'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<!-- TERRITORY STATS -->
<div class="card">
    <h2><i class="fas fa-chart-bar"></i> Territory Overview</h2>
    <?php
        $totalZones = count($zones);
        $yourZones = 0; $enemyZones = 0; $unclaimed = 0;
        foreach ($zones as $z) {
            if (empty($z['controlling_unit_id'])) $unclaimed++;
            elseif ((int)$z['controlling_unit_id'] === $userUnitId) $yourZones++;
            else $enemyZones++;
        }
    ?>
    <div class="stat-row">
        <div class="stat-item"><div class="stat-label">Total Zones</div><div class="stat-val"><?= $totalZones ?></div></div>
        <div class="stat-item"><div class="stat-label">Your Zones</div><div class="stat-val" style="color:#34d399"><?= $yourZones ?></div></div>
        <div class="stat-item"><div class="stat-label">Enemy Zones</div><div class="stat-val" style="color:#f87171"><?= $enemyZones ?></div></div>
        <div class="stat-item"><div class="stat-label">Unclaimed</div><div class="stat-val" style="color:#6B7280"><?= $unclaimed ?></div></div>
    </div>
</div>
<?php endif; ?>

</div>
</body>
</html>
