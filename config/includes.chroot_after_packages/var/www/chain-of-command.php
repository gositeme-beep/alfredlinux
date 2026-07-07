<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(1);

if (empty($_SESSION['csrf_coc'])) $_SESSION['csrf_coc'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_coc'];

$classLevels = ['unclassified' => 1, 'confidential' => 4, 'secret' => 7, 'top_secret' => 9];
$classColors = ['unclassified' => '#059669', 'confidential' => '#2563EB', 'secret' => '#D97706', 'top_secret' => '#DC2626'];
$priorityColors = ['routine' => '#64748b', 'priority' => '#2563EB', 'urgent' => '#D97706', 'flash' => '#DC2626'];
$msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { $msg = '❌ Invalid CSRF token.'; }
    else {
        $action = $_POST['action'] ?? '';

        if ($action === 'issue_order' && $userRankTier >= 7) {
            $title = trim($_POST['title'] ?? '');
            $orderType = $_POST['order_type'] ?? 'general';
            $content = trim($_POST['content'] ?? '');
            $priority = $_POST['priority'] ?? 'routine';
            $classification = $_POST['classification'] ?? 'unclassified';
            $targetUnit = !empty($_POST['target_unit_id']) ? (int)$_POST['target_unit_id'] : null;
            $targetRank = !empty($_POST['target_rank_tier']) ? (int)$_POST['target_rank_tier'] : null;
            if ($title === '' || $content === '') { $msg = '❌ Title and content are required.'; }
            elseif ($userRankTier < ($classLevels[$classification] ?? 99)) { $msg = '❌ Insufficient rank for that classification.'; }
            else {
                $code = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $db->prepare("INSERT INTO command_orders (order_code,title,order_type,content,issued_by,target_unit_id,target_rank_tier,priority,classification,status,effective_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,'active',NOW(),NOW())");
                $stmt->bind_param('ssssissss', $code, $title, $orderType, $content, $clientId, $targetUnit, $targetRank, $priority, $classification);
                $msg = $stmt->execute() ? '✅ Order issued: ' . htmlspecialchars($code) : '❌ DB error: ' . htmlspecialchars($stmt->error);
                $stmt->close();
            }
        }

        if ($action === 'file_report' && $userRankTier >= 4) {
            $title = trim($_POST['title'] ?? '');
            $reportType = $_POST['report_type'] ?? 'sitrep';
            $content = trim($_POST['content'] ?? '');
            $classification = $_POST['classification'] ?? 'unclassified';
            $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
            if ($title === '' || $content === '') { $msg = '❌ Title and content are required.'; }
            elseif ($userRankTier < ($classLevels[$classification] ?? 99)) { $msg = '❌ Insufficient rank for that classification.'; }
            else {
                $code = 'RPT-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $db->prepare("INSERT INTO command_reports (report_code,title,report_type,content,filed_by,unit_id,classification,status,created_at) VALUES (?,?,?,?,?,?,?,'filed',NOW())");
                $stmt->bind_param('ssssiis', $code, $title, $reportType, $content, $clientId, $unitId, $classification);
                $msg = $stmt->execute() ? '✅ Report filed: ' . htmlspecialchars($code) : '❌ DB error: ' . htmlspecialchars($stmt->error);
                $stmt->close();
            }
        }

        if ($action === 'join_channel') {
            $chanId = (int)($_POST['channel_id'] ?? 0);
            $ch = $db->prepare("SELECT min_rank_tier, is_active FROM command_channels WHERE id=?");
            $ch->bind_param('i', $chanId);
            $ch->execute();
            $chRow = $ch->get_result()->fetch_assoc();
            $ch->close();
            if (!$chRow || !$chRow['is_active']) { $msg = '❌ Channel not found or inactive.'; }
            elseif ($userRankTier < (int)$chRow['min_rank_tier']) { $msg = '❌ Insufficient rank for this channel.'; }
            else {
                $dup = $db->prepare("SELECT id FROM command_channel_members WHERE channel_id=? AND client_id=?");
                $dup->bind_param('ii', $chanId, $clientId);
                $dup->execute();
                if ($dup->get_result()->num_rows > 0) { $msg = '⚠️ Already a member of this channel.'; }
                else {
                    $ins = $db->prepare("INSERT INTO command_channel_members (channel_id,client_id,role,joined_at) VALUES (?,?,'member',NOW())");
                    $ins->bind_param('ii', $chanId, $clientId);
                    $msg = $ins->execute() ? '✅ Joined channel.' : '❌ DB error.';
                    $ins->close();
                }
                $dup->close();
            }
        }
    }
}

// Fetch orders visible to user
$maxClass = array_keys(array_filter($classLevels, fn($v) => $v <= $userRankTier));
$placeholders = implode(',', array_fill(0, count($maxClass), '?'));
$types = str_repeat('s', count($maxClass));
$ordersStmt = $db->prepare("SELECT o.*, u.unit_name AS target_unit_name FROM command_orders o LEFT JOIN military_units u ON o.target_unit_id=u.id WHERE o.status='active' AND o.classification IN ($placeholders) ORDER BY FIELD(o.priority,'flash','urgent','priority','routine'), o.created_at DESC LIMIT 50");
$ordersStmt->bind_param($types, ...$maxClass);
$ordersStmt->execute();
$orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ordersStmt->close();

// Fetch reports visible to user
$reportsStmt = $db->prepare("SELECT r.*, u.unit_name FROM command_reports r LEFT JOIN military_units u ON r.unit_id=u.id WHERE r.classification IN ($placeholders) ORDER BY r.created_at DESC LIMIT 50");
$reportsStmt->bind_param($types, ...$maxClass);
$reportsStmt->execute();
$reports = $reportsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reportsStmt->close();

// Fetch channels accessible to user
$chanStmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM command_channel_members m WHERE m.channel_id=c.id) AS member_count, (SELECT id FROM command_channel_members m2 WHERE m2.channel_id=c.id AND m2.client_id=?) AS my_membership FROM command_channels c WHERE c.is_active=1 AND c.min_rank_tier<=? ORDER BY c.channel_type, c.channel_name");
$chanStmt->bind_param('ii', $clientId, $userRankTier);
$chanStmt->execute();
$channels = $chanStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$chanStmt->close();

// Fetch unit hierarchy
$units = $db->query("SELECT u.*, (SELECT COUNT(*) FROM command_channel_members cm INNER JOIN command_channels cc ON cm.channel_id=cc.id WHERE cc.unit_id=u.id) AS personnel_count FROM military_units u WHERE u.is_active=1 ORDER BY u.parent_unit_id IS NULL DESC, u.parent_unit_id, u.unit_name")->fetch_all(MYSQLI_ASSOC);

// Fetch all units for dropdowns
$allUnits = $db->query("SELECT id, unit_code, unit_name FROM military_units WHERE is_active=1 ORDER BY unit_name")->fetch_all(MYSQLI_ASSOC);

// Build unit tree
function buildTree(array $units, $parentId = null): array {
    $tree = [];
    foreach ($units as $u) {
        $pid = $u['parent_unit_id'] ? (int)$u['parent_unit_id'] : null;
        if ($pid === $parentId) {
            $u['children'] = buildTree($units, (int)$u['id']);
            $tree[] = $u;
        }
    }
    return $tree;
}
$unitTree = buildTree($units);

function renderTree(array $nodes): string {
    if (empty($nodes)) return '';
    $h = '<ul style="list-style:none;padding-left:18px;margin:4px 0">';
    foreach ($nodes as $n) {
        $badge = htmlspecialchars($n['unit_type'] ?? 'unit');
        $name = htmlspecialchars($n['unit_name']);
        $code = htmlspecialchars($n['unit_code']);
        $pc = (int)($n['personnel_count'] ?? 0);
        $h .= "<li style='margin:6px 0'><i class='fas fa-sitemap' style='color:#3b82f6;margin-right:6px'></i><strong>{$name}</strong> <span style='color:#94a3b8;font-size:13px'>({$code} · {$badge} · {$pc} personnel)</span>";
        $h .= renderTree($n['children'] ?? []);
        $h .= '</li>';
    }
    return $h . '</ul>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chain of Command — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh}
.wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
h1{font-size:28px;margin-bottom:4px}
.sub{color:#94a3b8;font-size:14px;margin-bottom:24px}
.tabs{display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap}
.tab{padding:10px 18px;background:#1e293b;border:1px solid #334155;border-radius:8px 8px 0 0;cursor:pointer;color:#94a3b8;font-size:14px;transition:.2s}
.tab.active,.tab:hover{background:#334155;color:#e2e8f0;border-bottom-color:#334155}
.panel{display:none;background:#1e293b;border:1px solid #334155;border-radius:0 8px 8px 8px;padding:20px}
.panel.active{display:block}
.card{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:16px;margin-bottom:12px}
.card-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px}
.card-title{font-size:16px;font-weight:600}
.badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#fff}
.card-meta{font-size:12px;color:#94a3b8;margin-bottom:6px}
.card-body{font-size:14px;line-height:1.6;color:#cbd5e1;white-space:pre-wrap}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:768px){.grid2{grid-template-columns:1fr}}
.ch-card{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.ch-info{flex:1}
.ch-name{font-size:15px;font-weight:600}
.ch-meta{font-size:12px;color:#94a3b8;margin-top:2px}
.btn{padding:8px 16px;border:none;border-radius:6px;font-size:13px;cursor:pointer;font-weight:600;transition:.2s}
.btn-blue{background:#3b82f6;color:#fff}.btn-blue:hover{background:#2563eb}
.btn-sm{padding:5px 12px;font-size:12px}
.btn-green{background:#059669;color:#fff}.btn-green:hover{background:#047857}
.form-section{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:20px;margin-top:16px}
.form-section h3{font-size:16px;margin-bottom:12px;color:#3b82f6}
.fg{margin-bottom:12px}
.fg label{display:block;font-size:13px;color:#94a3b8;margin-bottom:4px}
.fg input,.fg select,.fg textarea{width:100%;padding:8px 12px;background:#1e293b;border:1px solid #334155;border-radius:6px;color:#e2e8f0;font-size:14px;font-family:inherit}
.fg textarea{resize:vertical;min-height:100px}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:#3b82f6}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.msg{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;border:1px solid #334155;background:#1e293b}
.tree-wrap{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:16px}
.empty{text-align:center;padding:40px;color:#64748b;font-size:14px}
</style>
</head>
<body>
<div class="wrap">
<h1><i class="fas fa-satellite-dish" style="color:#3b82f6;margin-right:8px"></i>Chain of Command</h1>
<p class="sub">Communications Hub — <?= htmlspecialchars($userName) ?> · Rank Tier <?= (int)$userRankTier ?> · <?= htmlspecialchars($userRankCode) ?></p>

<?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

<div class="tabs">
<div class="tab active" onclick="showTab('orders')"><i class="fas fa-scroll"></i> Orders</div>
<div class="tab" onclick="showTab('reports')"><i class="fas fa-file-alt"></i> Reports</div>
<div class="tab" onclick="showTab('channels')"><i class="fas fa-broadcast-tower"></i> Channels</div>
<div class="tab" onclick="showTab('hierarchy')"><i class="fas fa-sitemap"></i> Hierarchy</div>
</div>

<!-- ORDERS PANEL -->
<div id="panel-orders" class="panel active">
<h2 style="font-size:18px;margin-bottom:14px"><i class="fas fa-scroll" style="color:#3b82f6"></i> Active Orders</h2>
<?php if (empty($orders)): ?>
<div class="empty"><i class="fas fa-inbox"></i><br>No active orders visible at your clearance level.</div>
<?php else: foreach ($orders as $o):
    $cc = $classColors[$o['classification']] ?? '#64748b';
    $pc = $priorityColors[$o['priority']] ?? '#64748b';
?>
<div class="card" style="border-left:3px solid <?= $cc ?>">
    <div class="card-head">
        <span class="card-title"><?= htmlspecialchars($o['title']) ?></span>
        <span>
            <span class="badge" style="background:<?= $pc ?>"><?= htmlspecialchars($o['priority']) ?></span>
            <span class="badge" style="background:<?= $cc ?>"><?= htmlspecialchars(str_replace('_', ' ', $o['classification'])) ?></span>
        </span>
    </div>
    <div class="card-meta">
        <i class="fas fa-hashtag"></i> <?= htmlspecialchars($o['order_code']) ?>
        · <i class="fas fa-tag"></i> <?= htmlspecialchars($o['order_type']) ?>
        · <i class="fas fa-user"></i> Issued by #<?= (int)$o['issued_by'] ?>
        <?php if ($o['target_unit_name']): ?> · <i class="fas fa-users"></i> <?= htmlspecialchars($o['target_unit_name']) ?><?php endif; ?>
        <?php if ($o['target_rank_tier']): ?> · <i class="fas fa-chevron-up"></i> Tier <?= (int)$o['target_rank_tier'] ?>+<?php endif; ?>
        · <i class="fas fa-clock"></i> <?= htmlspecialchars($o['created_at']) ?>
        <?php if ($o['expires_at']): ?> · Expires <?= htmlspecialchars($o['expires_at']) ?><?php endif; ?>
    </div>
    <div class="card-body"><?= htmlspecialchars($o['content']) ?></div>
</div>
<?php endforeach; endif; ?>

<?php if ($userRankTier >= 7): ?>
<div class="form-section">
<h3><i class="fas fa-pen-fancy"></i> Issue New Order (Officers+)</h3>
<form method="POST">
<input type="hidden" name="csrf" value="<?= $csrf ?>">
<input type="hidden" name="action" value="issue_order">
<div class="fg"><label>Title</label><input type="text" name="title" required maxlength="200"></div>
<div class="row2">
<div class="fg"><label>Order Type</label><select name="order_type"><option value="general">General</option><option value="standing">Standing</option><option value="operations">Operations</option><option value="directive">Directive</option></select></div>
<div class="fg"><label>Priority</label><select name="priority"><option value="routine">Routine</option><option value="priority">Priority</option><option value="urgent">Urgent</option><option value="flash">Flash</option></select></div>
</div>
<div class="fg"><label>Content</label><textarea name="content" required></textarea></div>
<div class="row2">
<div class="fg"><label>Classification</label><select name="classification">
<?php foreach ($classLevels as $cl => $lv): if ($lv <= $userRankTier): ?>
<option value="<?= $cl ?>"><?= ucfirst(str_replace('_', ' ', $cl)) ?></option>
<?php endif; endforeach; ?>
</select></div>
<div class="fg"><label>Target Unit</label><select name="target_unit_id"><option value="">— All Units —</option>
<?php foreach ($allUnits as $au): ?><option value="<?= (int)$au['id'] ?>"><?= htmlspecialchars($au['unit_name']) ?> (<?= htmlspecialchars($au['unit_code']) ?>)</option><?php endforeach; ?>
</select></div>
</div>
<div class="fg"><label>Target Rank Tier (minimum)</label><input type="number" name="target_rank_tier" min="1" max="10" placeholder="Leave blank for all ranks"></div>
<button type="submit" class="btn btn-blue"><i class="fas fa-paper-plane"></i> Issue Order</button>
</form>
</div>
<?php endif; ?>
</div>

<!-- REPORTS PANEL -->
<div id="panel-reports" class="panel">
<h2 style="font-size:18px;margin-bottom:14px"><i class="fas fa-file-alt" style="color:#3b82f6"></i> Filed Reports</h2>
<?php if (empty($reports)): ?>
<div class="empty"><i class="fas fa-folder-open"></i><br>No reports visible at your clearance level.</div>
<?php else: foreach ($reports as $r):
    $cc = $classColors[$r['classification']] ?? '#64748b';
    $statusIcons = ['filed' => 'inbox', 'reviewed' => 'check', 'acknowledged' => 'check-double', 'archived' => 'archive'];
?>
<div class="card" style="border-left:3px solid <?= $cc ?>">
    <div class="card-head">
        <span class="card-title"><?= htmlspecialchars($r['title']) ?></span>
        <span>
            <span class="badge" style="background:#475569"><i class="fas fa-<?= $statusIcons[$r['status']] ?? 'circle' ?>"></i> <?= htmlspecialchars($r['status']) ?></span>
            <span class="badge" style="background:<?= $cc ?>"><?= htmlspecialchars(str_replace('_', ' ', $r['classification'])) ?></span>
        </span>
    </div>
    <div class="card-meta">
        <i class="fas fa-hashtag"></i> <?= htmlspecialchars($r['report_code']) ?>
        · <i class="fas fa-tag"></i> <?= htmlspecialchars($r['report_type']) ?>
        · <i class="fas fa-user"></i> Filed by #<?= (int)$r['filed_by'] ?>
        <?php if ($r['unit_name']): ?> · <i class="fas fa-users"></i> <?= htmlspecialchars($r['unit_name']) ?><?php endif; ?>
        · <i class="fas fa-clock"></i> <?= htmlspecialchars($r['created_at']) ?>
        <?php if ($r['reviewed_by']): ?> · Reviewed by #<?= (int)$r['reviewed_by'] ?> at <?= htmlspecialchars($r['reviewed_at']) ?><?php endif; ?>
    </div>
    <div class="card-body"><?= htmlspecialchars($r['content']) ?></div>
</div>
<?php endforeach; endif; ?>

<?php if ($userRankTier >= 4): ?>
<div class="form-section">
<h3><i class="fas fa-pen"></i> File New Report (NCO+)</h3>
<form method="POST">
<input type="hidden" name="csrf" value="<?= $csrf ?>">
<input type="hidden" name="action" value="file_report">
<div class="fg"><label>Title</label><input type="text" name="title" required maxlength="200"></div>
<div class="row2">
<div class="fg"><label>Report Type</label><select name="report_type"><option value="sitrep">SITREP</option><option value="after_action">After Action</option><option value="intelligence">Intelligence</option><option value="logistics">Logistics</option><option value="personnel">Personnel</option></select></div>
<div class="fg"><label>Classification</label><select name="classification">
<?php foreach ($classLevels as $cl => $lv): if ($lv <= $userRankTier): ?>
<option value="<?= $cl ?>"><?= ucfirst(str_replace('_', ' ', $cl)) ?></option>
<?php endif; endforeach; ?>
</select></div>
</div>
<div class="fg"><label>Content</label><textarea name="content" required></textarea></div>
<div class="fg"><label>Unit</label><select name="unit_id"><option value="">— No Unit —</option>
<?php foreach ($allUnits as $au): ?><option value="<?= (int)$au['id'] ?>"><?= htmlspecialchars($au['unit_name']) ?> (<?= htmlspecialchars($au['unit_code']) ?>)</option><?php endforeach; ?>
</select></div>
<button type="submit" class="btn btn-green"><i class="fas fa-file-upload"></i> File Report</button>
</form>
</div>
<?php endif; ?>
</div>

<!-- CHANNELS PANEL -->
<div id="panel-channels" class="panel">
<h2 style="font-size:18px;margin-bottom:14px"><i class="fas fa-broadcast-tower" style="color:#3b82f6"></i> Communication Channels</h2>
<?php if (empty($channels)): ?>
<div class="empty"><i class="fas fa-satellite-dish"></i><br>No channels available at your rank tier.</div>
<?php else: ?>
<div class="grid2">
<?php
$typeIcons = ['broadcast' => 'bullhorn', 'general' => 'comments', 'operations' => 'crosshairs', 'command' => 'star', 'restricted' => 'lock', 'intelligence' => 'eye', 'emergency' => 'exclamation-triangle'];
foreach ($channels as $ch):
    $icon = $typeIcons[$ch['channel_type']] ?? 'hashtag';
    $isMember = !empty($ch['my_membership']);
?>
<div class="ch-card">
    <div class="ch-info">
        <div class="ch-name">
            <i class="fas fa-<?= $icon ?>" style="color:#3b82f6;margin-right:6px"></i>
            <?= htmlspecialchars($ch['channel_name']) ?>
            <?php if ($ch['is_encrypted']): ?><i class="fas fa-shield-alt" style="color:#059669;margin-left:4px" title="Encrypted"></i><?php endif; ?>
        </div>
        <div class="ch-meta">
            <?= htmlspecialchars($ch['channel_code']) ?> · <?= htmlspecialchars($ch['channel_type']) ?>
            · <?= (int)$ch['member_count'] ?> members · Tier <?= (int)$ch['min_rank_tier'] ?>+
        </div>
    </div>
    <?php if ($isMember): ?>
        <span class="badge" style="background:#059669"><i class="fas fa-check"></i> Joined</span>
    <?php else: ?>
        <form method="POST" style="margin:0">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="join_channel">
            <input type="hidden" name="channel_id" value="<?= (int)$ch['id'] ?>">
            <button type="submit" class="btn btn-blue btn-sm"><i class="fas fa-sign-in-alt"></i> Join</button>
        </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- HIERARCHY PANEL -->
<div id="panel-hierarchy" class="panel">
<h2 style="font-size:18px;margin-bottom:14px"><i class="fas fa-sitemap" style="color:#3b82f6"></i> Unit Hierarchy</h2>
<?php if (empty($unitTree)): ?>
<div class="empty"><i class="fas fa-project-diagram"></i><br>No active units configured.</div>
<?php else: ?>
<div class="tree-wrap"><?= renderTree($unitTree) ?></div>
<?php endif; ?>
</div>

</div><!-- .wrap -->

<script>
function showTab(name){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
    document.getElementById('panel-'+name).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
</body>
</html>
