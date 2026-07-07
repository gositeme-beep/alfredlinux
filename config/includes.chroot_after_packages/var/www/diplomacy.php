<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
requireRank(3);

if (empty($_SESSION['csrf_diplomacy'])) $_SESSION['csrf_diplomacy'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_diplomacy'];

$msg = '';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'propose_treaty' && $userRankTier >= 7) {
        $stmt = $db->prepare("INSERT INTO diplomatic_treaties (treaty_code, treaty_name, treaty_type, party_a_type, party_a_id, party_b_type, party_b_id, terms, status, proposed_by, created_at) VALUES (?, ?, ?, 'unit', ?, ?, ?, ?, 'proposed', ?, NOW())");
        $code = 'TRT-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt->bind_param('sssissis', $code, $_POST['treaty_name'], $_POST['treaty_type'], $clientId, $_POST['party_b_type'], $_POST['party_b_id'], $_POST['terms'], $clientId);
        $stmt->execute();
        $msg = 'Treaty proposed successfully.';
    } elseif ($action === 'treaty_action' && $userRankTier >= 9) {
        $allowed = ['ratified','suspended','terminated'];
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, $allowed, true)) {
            $extra = $newStatus === 'ratified' ? ', ratified_at = NOW()' : '';
            $stmt = $db->prepare("UPDATE diplomatic_treaties SET status = ?{$extra} WHERE id = ?");
            $stmt->bind_param('si', $newStatus, $_POST['treaty_id']);
            $stmt->execute();
            $msg = 'Treaty status updated to ' . htmlspecialchars($newStatus) . '.';
        }
    } elseif ($action === 'appoint_ambassador' && $userRankTier >= 8) {
        $stmt = $db->prepare("INSERT INTO diplomatic_ambassadors (client_id, title, assigned_entity_type, assigned_entity_id, appointment_type, status, appointed_by, appointed_at) VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())");
        $stmt->bind_param('issisi', $_POST['amb_client_id'], $_POST['amb_title'], $_POST['assigned_entity_type'], $_POST['assigned_entity_id'], $_POST['appointment_type'], $clientId);
        $stmt->execute();
        $msg = 'Ambassador appointed.';
    } elseif ($action === 'send_comm' && $userRankTier >= 5) {
        $stmt = $db->prepare("INSERT INTO diplomatic_communications (comm_code, comm_type, from_entity_type, from_entity_id, to_entity_type, to_entity_id, subject, content, classification, sent_by, status, created_at) VALUES (?, ?, 'unit', ?, ?, ?, ?, ?, ?, ?, 'sent', NOW())");
        $code = 'DPC-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt->bind_param('ssississi', $code, $_POST['comm_type'], $clientId, $_POST['to_entity_type'], $_POST['to_entity_id'], $_POST['subject'], $_POST['content'], $_POST['classification'], $clientId);
        $stmt->execute();
        $msg = 'Communication sent.';
    }
    $_SESSION['csrf_diplomacy'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_diplomacy'];
}

// Fetch data
$relations = $db->query("SELECT * FROM diplomatic_relations ORDER BY updated_at DESC");
$treaties = $db->query("SELECT * FROM diplomatic_treaties ORDER BY created_at DESC");
$ambassadors = $db->query("SELECT da.*, CONCAT('Client #', da.client_id) AS ambassador_name FROM diplomatic_ambassadors da WHERE da.status = 'active' ORDER BY da.appointed_at DESC");
$comms = $db->query("SELECT * FROM diplomatic_communications ORDER BY created_at DESC LIMIT 50");

function trustColor($score) {
    if ($score <= 30) return '#DC2626';
    if ($score <= 60) return '#D97706';
    return '#059669';
}
function relColor($type) {
    $map = ['neutral'=>'#4B5563','friendly'=>'#059669','allied'=>'#2563EB','hostile'=>'#DC2626','embargo'=>'#D97706','protectorate'=>'#7C3AED'];
    return $map[$type] ?? '#4B5563';
}
function statusBadgeColor($s) {
    $m = ['proposed'=>'#D97706','ratified'=>'#2563EB','active'=>'#059669','suspended'=>'#D97706','terminated'=>'#DC2626','sent'=>'#3b82f6','delivered'=>'#059669','read'=>'#7C3AED','replied'=>'#059669','archived'=>'#4B5563'];
    return $m[$s] ?? '#4B5563';
}
function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diplomatic Corps — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh}
.wrap{max-width:1200px;margin:0 auto;padding:20px}
h1{font-size:1.8rem;margin-bottom:6px}
.subtitle{color:#94a3b8;margin-bottom:20px}
.msg{background:#1e3a5f;border:1px solid #3b82f6;color:#93c5fd;padding:10px 16px;border-radius:8px;margin-bottom:16px}
.tabs{display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap}
.tab{padding:10px 20px;background:#1e293b;border:1px solid #334155;border-bottom:none;border-radius:8px 8px 0 0;cursor:pointer;color:#94a3b8;font-weight:600;transition:.2s}
.tab.active,.tab:hover{background:#334155;color:#e2e8f0}
.tab-content{display:none}
.tab-content.active{display:block}
.card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:18px;margin-bottom:14px}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.75rem;font-weight:700;color:#fff;text-transform:uppercase}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px}
.trust-bar{height:8px;background:#334155;border-radius:4px;overflow:hidden;margin-top:6px}
.trust-fill{height:100%;border-radius:4px;transition:width .4s}
.entity-tag{color:#94a3b8;font-size:.85rem}
.form-section{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:20px;margin-top:20px}
.form-section h3{margin-bottom:14px;color:#93c5fd}
.form-row{display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap}
.form-row label{display:block;color:#94a3b8;font-size:.85rem;margin-bottom:4px}
.form-row .field{flex:1;min-width:200px}
input,select,textarea{width:100%;padding:8px 12px;background:#0f172a;border:1px solid #334155;border-radius:6px;color:#e2e8f0;font-size:.9rem}
textarea{resize:vertical;min-height:80px}
.btn{padding:10px 22px;border:none;border-radius:6px;font-weight:700;cursor:pointer;font-size:.9rem;transition:.2s}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-danger{background:#DC2626;color:#fff}.btn-danger:hover{background:#b91c1c}
.btn-warn{background:#D97706;color:#fff}.btn-warn:hover{background:#b45309}
.btn-sm{padding:5px 12px;font-size:.8rem}
.class-badge{font-size:.7rem;padding:2px 8px;border-radius:10px;font-weight:700}
.class-public{background:#059669;color:#fff}.class-confidential{background:#D97706;color:#fff}.class-secret{background:#DC2626;color:#fff}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 12px;text-align:left;border-bottom:1px solid #334155;font-size:.9rem}
th{color:#94a3b8;font-weight:600;white-space:nowrap}
.terms-preview{max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#94a3b8}
.empty{text-align:center;color:#64748b;padding:40px}
@media(max-width:640px){.form-row{flex-direction:column}.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
<h1><i class="fa-solid fa-handshake" style="color:#3b82f6"></i> Diplomatic Corps</h1>
<p class="subtitle">Level 4 — Manage relations, treaties, ambassadors & communications</p>

<?php if ($msg): ?><div class="msg"><i class="fa-solid fa-circle-check"></i> <?=h($msg)?></div><?php endif; ?>

<div class="tabs">
    <div class="tab active" onclick="switchTab('relations')"><i class="fa-solid fa-globe"></i> Relations</div>
    <div class="tab" onclick="switchTab('treaties')"><i class="fa-solid fa-file-contract"></i> Treaties</div>
    <div class="tab" onclick="switchTab('ambassadors')"><i class="fa-solid fa-user-tie"></i> Ambassadors</div>
    <div class="tab" onclick="switchTab('comms')"><i class="fa-solid fa-envelope-open-text"></i> Communications</div>
</div>

<!-- RELATIONS TAB -->
<div id="tab-relations" class="tab-content active">
<div class="grid">
<?php if ($relations && $relations->num_rows): while ($r = $relations->fetch_assoc()): $rc = relColor($r['relation_type']); $tc = trustColor((int)$r['trust_score']); ?>
<div class="card">
    <div class="card-header">
        <div>
            <span class="entity-tag"><?=h($r['entity_a_type'])?> #<?=(int)$r['entity_a_id']?></span>
            <i class="fa-solid fa-arrows-left-right" style="color:#64748b;margin:0 6px"></i>
            <span class="entity-tag"><?=h($r['entity_b_type'])?> #<?=(int)$r['entity_b_id']?></span>
        </div>
        <span class="badge" style="background:<?=$rc?>"><?=h($r['relation_type'])?></span>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:.85rem;color:#94a3b8">
        <span>Trust Score</span><span style="color:<?=$tc?>;font-weight:700"><?=(int)$r['trust_score']?>/100</span>
    </div>
    <div class="trust-bar"><div class="trust-fill" style="width:<?=(int)$r['trust_score']?>%;background:<?=$tc?>"></div></div>
    <div style="font-size:.75rem;color:#64748b;margin-top:8px">Updated <?=h($r['updated_at'])?></div>
</div>
<?php endwhile; else: ?>
<div class="empty" style="grid-column:1/-1"><i class="fa-solid fa-globe" style="font-size:2rem;margin-bottom:8px;display:block"></i>No diplomatic relations established yet.</div>
<?php endif; ?>
</div>
</div>

<!-- TREATIES TAB -->
<div id="tab-treaties" class="tab-content">
<div class="card" style="overflow-x:auto">
<table>
<thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Parties</th><th>Status</th><th>Expires</th><?php if ($userRankTier >= 9): ?><th>Actions</th><?php endif; ?></tr></thead>
<tbody>
<?php if ($treaties && $treaties->num_rows): while ($t = $treaties->fetch_assoc()): $sc = statusBadgeColor($t['status']); ?>
<tr>
    <td style="font-family:monospace;color:#93c5fd"><?=h($t['treaty_code'])?></td>
    <td><?=h($t['treaty_name'])?><div class="terms-preview" title="<?=h($t['terms'])?>"><?=h($t['terms'])?></div></td>
    <td><span class="badge" style="background:#334155"><?=h($t['treaty_type'])?></span></td>
    <td class="entity-tag"><?=h($t['party_a_type'])?> #<?=(int)$t['party_a_id']?> ↔ <?=h($t['party_b_type'])?> #<?=(int)$t['party_b_id']?></td>
    <td><span class="badge" style="background:<?=$sc?>"><?=h($t['status'])?></span></td>
    <td style="color:#94a3b8;font-size:.85rem"><?=$t['expires_at'] ? h($t['expires_at']) : '—'?></td>
    <?php if ($userRankTier >= 9): ?>
    <td>
        <form method="POST" style="display:inline-flex;gap:4px">
            <input type="hidden" name="csrf" value="<?=h($csrf)?>">
            <input type="hidden" name="action" value="treaty_action">
            <input type="hidden" name="treaty_id" value="<?=(int)$t['id']?>">
            <?php if ($t['status'] === 'proposed'): ?>
            <button type="submit" name="new_status" value="ratified" class="btn btn-primary btn-sm">Ratify</button>
            <?php endif; ?>
            <?php if (in_array($t['status'], ['active','ratified'])): ?>
            <button type="submit" name="new_status" value="suspended" class="btn btn-warn btn-sm">Suspend</button>
            <?php endif; ?>
            <?php if ($t['status'] !== 'terminated'): ?>
            <button type="submit" name="new_status" value="terminated" class="btn btn-danger btn-sm" onclick="return confirm('Terminate this treaty?')">Terminate</button>
            <?php endif; ?>
        </form>
    </td>
    <?php endif; ?>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7" class="empty">No treaties on record.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php if ($userRankTier >= 7): ?>
<div class="form-section">
    <h3><i class="fa-solid fa-plus"></i> Propose Treaty (Officer+)</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?=h($csrf)?>">
        <input type="hidden" name="action" value="propose_treaty">
        <div class="form-row">
            <div class="field"><label>Treaty Name</label><input name="treaty_name" required maxlength="200"></div>
            <div class="field"><label>Type</label>
                <select name="treaty_type" required>
                    <option value="alliance">Alliance</option>
                    <option value="trade">Trade</option>
                    <option value="ceasefire">Ceasefire</option>
                    <option value="mutual_defense">Mutual Defense</option>
                    <option value="non_aggression">Non-Aggression</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="field"><label>Other Party Type</label>
                <select name="party_b_type" required>
                    <option value="unit">Unit</option><option value="community">Community</option>
                    <option value="server">Server</option><option value="nation">Nation</option>
                </select>
            </div>
            <div class="field"><label>Other Party ID</label><input name="party_b_id" type="number" min="1" required></div>
        </div>
        <div class="form-row"><div class="field"><label>Terms</label><textarea name="terms" required maxlength="5000" placeholder="Describe treaty terms..."></textarea></div></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Propose Treaty</button>
    </form>
</div>
<?php endif; ?>
</div>

<!-- AMBASSADORS TAB -->
<div id="tab-ambassadors" class="tab-content">
<div class="grid">
<?php if ($ambassadors && $ambassadors->num_rows): while ($a = $ambassadors->fetch_assoc()): ?>
<div class="card">
    <div class="card-header">
        <div><i class="fa-solid fa-user-tie" style="color:#3b82f6;margin-right:6px"></i><strong><?=h($a['title'])?></strong></div>
        <span class="badge" style="background:<?=$a['appointment_type']==='permanent'?'#059669':($a['appointment_type']==='special_envoy'?'#7C3AED':'#D97706')?>"><?=h($a['appointment_type'])?></span>
    </div>
    <div style="font-size:.9rem;color:#94a3b8"><?=h($a['ambassador_name'])?></div>
    <div style="font-size:.85rem;color:#64748b;margin-top:6px">
        Assigned: <?=h($a['assigned_entity_type'])?> #<?=(int)$a['assigned_entity_id']?>
        <br>Appointed: <?=h($a['appointed_at'])?>
    </div>
</div>
<?php endwhile; else: ?>
<div class="empty" style="grid-column:1/-1"><i class="fa-solid fa-user-tie" style="font-size:2rem;margin-bottom:8px;display:block"></i>No active ambassadors.</div>
<?php endif; ?>
</div>

<?php if ($userRankTier >= 8): ?>
<div class="form-section">
    <h3><i class="fa-solid fa-user-plus"></i> Appoint Ambassador (Senior Officer+)</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?=h($csrf)?>">
        <input type="hidden" name="action" value="appoint_ambassador">
        <div class="form-row">
            <div class="field"><label>Client ID</label><input name="amb_client_id" type="number" min="1" required></div>
            <div class="field"><label>Title</label><input name="amb_title" required maxlength="150" placeholder="e.g. Ambassador to Server #2"></div>
        </div>
        <div class="form-row">
            <div class="field"><label>Assigned Entity Type</label>
                <select name="assigned_entity_type" required>
                    <option value="unit">Unit</option><option value="community">Community</option>
                    <option value="server">Server</option><option value="nation">Nation</option>
                </select>
            </div>
            <div class="field"><label>Entity ID</label><input name="assigned_entity_id" type="number" min="1" required></div>
            <div class="field"><label>Appointment Type</label>
                <select name="appointment_type" required>
                    <option value="permanent">Permanent</option>
                    <option value="temporary">Temporary</option>
                    <option value="special_envoy">Special Envoy</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-stamp"></i> Appoint</button>
    </form>
</div>
<?php endif; ?>
</div>

<!-- COMMUNICATIONS TAB -->
<div id="tab-comms" class="tab-content">
<div class="card" style="overflow-x:auto">
<table>
<thead><tr><th>Code</th><th>Type</th><th>From → To</th><th>Subject</th><th>Class</th><th>Status</th><th>Date</th></tr></thead>
<tbody>
<?php if ($comms && $comms->num_rows): while ($c = $comms->fetch_assoc()): $cc = statusBadgeColor($c['status']); ?>
<tr>
    <td style="font-family:monospace;color:#93c5fd"><?=h($c['comm_code'])?></td>
    <td><span class="badge" style="background:#334155"><?=h($c['comm_type'])?></span></td>
    <td class="entity-tag"><?=h($c['from_entity_type'])?> #<?=(int)$c['from_entity_id']?> → <?=h($c['to_entity_type'])?> #<?=(int)$c['to_entity_id']?></td>
    <td><?=h($c['subject'])?></td>
    <td><span class="class-badge class-<?=h($c['classification'])?>"><?=h($c['classification'])?></span></td>
    <td><span class="badge" style="background:<?=$cc?>"><?=h($c['status'])?></span></td>
    <td style="color:#94a3b8;font-size:.85rem;white-space:nowrap"><?=h($c['created_at'])?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7" class="empty">No diplomatic communications.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php if ($userRankTier >= 5): ?>
<div class="form-section">
    <h3><i class="fa-solid fa-envelope"></i> Send Communication (NCO+)</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?=h($csrf)?>">
        <input type="hidden" name="action" value="send_comm">
        <div class="form-row">
            <div class="field"><label>Type</label>
                <select name="comm_type" required>
                    <option value="message">Message</option><option value="proposal">Proposal</option>
                    <option value="protest">Protest</option><option value="announcement">Announcement</option>
                    <option value="treaty_draft">Treaty Draft</option>
                </select>
            </div>
            <div class="field"><label>Classification</label>
                <select name="classification" required>
                    <option value="public">Public</option>
                    <option value="confidential">Confidential</option>
                    <option value="secret">Secret</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="field"><label>To Entity Type</label>
                <select name="to_entity_type" required>
                    <option value="unit">Unit</option><option value="community">Community</option>
                    <option value="server">Server</option><option value="nation">Nation</option>
                </select>
            </div>
            <div class="field"><label>To Entity ID</label><input name="to_entity_id" type="number" min="1" required></div>
        </div>
        <div class="form-row"><div class="field"><label>Subject</label><input name="subject" required maxlength="250"></div></div>
        <div class="form-row"><div class="field"><label>Content</label><textarea name="content" required maxlength="10000" placeholder="Compose your diplomatic communication..."></textarea></div></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send</button>
    </form>
</div>
<?php endif; ?>
</div>

</div>
<script>
function switchTab(name){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    const labels={relations:'Relations',treaties:'Treaties',ambassadors:'Ambassadors',comms:'Communications'};
    document.querySelectorAll('.tab').forEach(t=>{if(t.textContent.trim().includes(labels[name]))t.classList.add('active');});
}
</script>
</body>
</html>
