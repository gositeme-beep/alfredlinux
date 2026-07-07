<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(3);

if (empty($_SESSION['csrf_intel'])) $_SESSION['csrf_intel'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_intel'];

$msg = '';
$msgType = '';

// Classification rank mapping
$classRankMap = ['unclassified' => 1, 'confidential' => 4, 'secret' => 7, 'top_secret' => 9];

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'file_report' && $userRankTier >= 5) {
        $title = trim($_POST['title'] ?? '');
        $type = $_POST['report_type'] ?? '';
        $class = $_POST['classification'] ?? '';
        $summary = trim($_POST['summary'] ?? '');
        $full = trim($_POST['full_report'] ?? '');
        $source = trim($_POST['source'] ?? '');
        $reliability = $_POST['reliability'] ?? 'C';
        $validTypes = ['sigint','humint','osint','cyber','techint'];
        $validClass = ['unclassified','confidential','secret','top_secret'];
        $validRel = ['A','B','C','D','E','F'];
        if ($title && in_array($type, $validTypes) && in_array($class, $validClass) && $summary && in_array($reliability, $validRel)) {
            $code = 'IR-' . strtoupper(bin2hex(random_bytes(4)));
            $minRank = $classRankMap[$class];
            $stmt = $db->prepare("INSERT INTO intel_reports (report_code, title, report_type, classification, summary, full_report, source, reliability, min_rank_view, filed_by, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_review', NOW(), NOW())");
            $stmt->bind_param('ssssssssiis', $code, $title, $type, $class, $summary, $full, $source, $reliability, $minRank, $clientId);
            if ($stmt->execute()) { $msg = "Report $code filed successfully."; $msgType = 'success'; }
            else { $msg = 'Database error filing report.'; $msgType = 'error'; }
            $stmt->close();
        } else { $msg = 'Missing or invalid fields.'; $msgType = 'error'; }
    }

    if ($action === 'report_threat' && $userRankTier >= 5) {
        $name = trim($_POST['threat_name'] ?? '');
        $type = $_POST['threat_type'] ?? '';
        $sev = $_POST['severity'] ?? '';
        $desc = trim($_POST['description'] ?? '');
        $indicators = trim($_POST['indicators'] ?? '');
        $counter = trim($_POST['countermeasures'] ?? '');
        $validTT = ['cyber','physical','social','economic','technical'];
        $validSev = ['low','moderate','elevated','high','critical'];
        if ($name && in_array($type, $validTT) && in_array($sev, $validSev) && $desc) {
            $tcode = 'TH-' . strtoupper(bin2hex(random_bytes(4)));
            $indJson = json_encode(array_filter(array_map('trim', explode("\n", $indicators))));
            $stmt = $db->prepare("INSERT INTO intel_threats (threat_code, threat_name, threat_type, severity, description, indicators, countermeasures, status, reported_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())");
            $stmt->bind_param('sssssssi', $tcode, $name, $type, $sev, $desc, $indJson, $counter, $clientId);
            if ($stmt->execute()) { $msg = "Threat $tcode reported."; $msgType = 'success'; }
            else { $msg = 'Database error reporting threat.'; $msgType = 'error'; }
            $stmt->close();
        } else { $msg = 'Missing or invalid threat fields.'; $msgType = 'error'; }
    }

    if ($action === 'review_report' && $userRankTier >= 7) {
        $rid = (int)($_POST['report_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if ($rid && in_array($newStatus, ['approved','rejected','archived'])) {
            $stmt = $db->prepare("UPDATE intel_reports SET status = ?, reviewed_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('sii', $newStatus, $clientId, $rid);
            $stmt->execute(); $stmt->close();
            $msg = "Report status updated to $newStatus."; $msgType = 'success';
        }
    }

    if ($action === 'update_threat' && $userRankTier >= 7) {
        $tid = (int)($_POST['threat_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if ($tid && in_array($newStatus, ['active','monitoring','mitigated','resolved'])) {
            $stmt = $db->prepare("UPDATE intel_threats SET status = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('sii', $newStatus, $clientId, $tid);
            $stmt->execute(); $stmt->close();
            $msg = "Threat status updated to $newStatus."; $msgType = 'success';
        }
    }

    $_SESSION['csrf_intel'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_intel'];
}

// Dashboard stats
$totalReports = $db->query("SELECT COUNT(*) as c FROM intel_reports WHERE min_rank_view <= $userRankTier")->fetch_assoc()['c'] ?? 0;
$activeThreats = $db->query("SELECT COUNT(*) as c FROM intel_threats WHERE status IN ('active','monitoring')")->fetch_assoc()['c'] ?? 0;

$byClass = [];
foreach (['unclassified','confidential','secret','top_secret'] as $cl) {
    $minR = $classRankMap[$cl];
    if ($userRankTier >= $minR) {
        $r = $db->query("SELECT COUNT(*) as c FROM intel_reports WHERE classification = '$cl'");
        $byClass[$cl] = $r ? ($r->fetch_assoc()['c'] ?? 0) : 0;
    }
}

$bySev = [];
foreach (['low','moderate','elevated','high','critical'] as $sv) {
    $r = $db->query("SELECT COUNT(*) as c FROM intel_threats WHERE severity = '$sv' AND status IN ('active','monitoring')");
    $bySev[$sv] = $r ? ($r->fetch_assoc()['c'] ?? 0) : 0;
}

// Report detail view
$detailReport = null;
if (isset($_GET['report'])) {
    $rc = $db->real_escape_string($_GET['report']);
    $detailReport = $db->query("SELECT * FROM intel_reports WHERE report_code = '$rc' AND min_rank_view <= $userRankTier")->fetch_assoc();
}

// Reports list
$filterType = $_GET['type'] ?? '';
$filterClass = $_GET['class'] ?? '';
$where = "WHERE min_rank_view <= $userRankTier";
if ($filterType && in_array($filterType, ['sigint','humint','osint','cyber','techint'])) $where .= " AND report_type = '" . $db->real_escape_string($filterType) . "'";
if ($filterClass && in_array($filterClass, ['unclassified','confidential','secret','top_secret']) && $userRankTier >= $classRankMap[$filterClass]) $where .= " AND classification = '" . $db->real_escape_string($filterClass) . "'";
$reports = $db->query("SELECT * FROM intel_reports $where ORDER BY created_at DESC LIMIT 50");

// Threats
$threats = $db->query("SELECT * FROM intel_threats WHERE status IN ('active','monitoring') ORDER BY FIELD(severity,'critical','high','elevated','moderate','low'), created_at DESC LIMIT 50");

// Sources stats
$srcStats = $db->query("SELECT status, COUNT(*) as c FROM intel_sources GROUP BY status");
$sourcesByStatus = [];
if ($srcStats) while ($row = $srcStats->fetch_assoc()) $sourcesByStatus[$row['status']] = $row['c'];
$totalSources = array_sum($sourcesByStatus);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Intelligence System — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh}
.top-bar{background:#1e293b;border-bottom:1px solid #334155;padding:12px 24px;display:flex;align-items:center;justify-content:space-between}
.top-bar h1{font-size:1.25rem;color:#3b82f6;display:flex;align-items:center;gap:8px}
.top-bar .user-info{font-size:.85rem;color:#94a3b8}
.container{max-width:1400px;margin:0 auto;padding:20px}
.msg{padding:10px 16px;border-radius:6px;margin-bottom:16px;font-size:.9rem}
.msg.success{background:#064e3b;border:1px solid #059669;color:#6ee7b7}
.msg.error{background:#450a0a;border:1px solid #dc2626;color:#fca5a5}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:16px;text-align:center}
.stat-card .val{font-size:2rem;font-weight:700;color:#3b82f6}
.stat-card .lbl{font-size:.8rem;color:#94a3b8;margin-top:4px;text-transform:uppercase;letter-spacing:.5px}
.tabs{display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap}
.tab{padding:8px 18px;background:#1e293b;border:1px solid #334155;border-radius:6px 6px 0 0;cursor:pointer;color:#94a3b8;font-size:.85rem;transition:all .2s}
.tab:hover,.tab.active{background:#334155;color:#e2e8f0;border-color:#3b82f6}
.panel{display:none}.panel.active{display:block}
.filters{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.filters select{background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:6px;padding:6px 10px;font-size:.85rem}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px}
.card{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:16px;transition:border-color .2s}
.card:hover{border-color:#3b82f6}
.card-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px}
.card-code{font-size:.75rem;color:#64748b;font-family:monospace}
.card-title{font-size:1rem;font-weight:600;color:#e2e8f0;margin-bottom:6px}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.badge-sigint{background:#1e3a5f;color:#60a5fa}.badge-humint{background:#3b1f51;color:#c084fc}.badge-osint{background:#1a3a2a;color:#6ee7b7}
.badge-cyber{background:#3b1919;color:#fca5a5}.badge-techint{background:#3b3419;color:#fcd34d}
.badge-unclass{background:#1e3a5f;color:#93c5fd}.badge-conf{background:#3b3419;color:#fcd34d}
.badge-secret{background:#3b1919;color:#f87171}.badge-ts{background:#4c0519;color:#fda4af;animation:pulse-ts 2s infinite}
@keyframes pulse-ts{0%,100%{opacity:1}50%{opacity:.6}}
.badge-row{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px}
.grade{font-weight:700;font-size:.9rem}.grade-A{color:#22c55e}.grade-B{color:#4ade80}.grade-C{color:#facc15}
.grade-D{color:#f97316}.grade-E{color:#ef4444}.grade-F{color:#dc2626}
.status-badge{padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:600}
.status-draft{background:#334155;color:#94a3b8}.status-pending_review{background:#3b3419;color:#fcd34d}
.status-approved{background:#064e3b;color:#6ee7b7}.status-rejected{background:#450a0a;color:#fca5a5}
.status-archived{background:#1e293b;color:#64748b}
.sev-bar{height:4px;border-radius:2px;margin-top:8px}
.sev-low{background:#059669}.sev-moderate{background:#2563EB}.sev-elevated{background:#D97706}
.sev-high{background:#DC2626}.sev-critical{background:#7C3AED;animation:pulse-sev 1.5s infinite}
@keyframes pulse-sev{0%,100%{opacity:1}50%{opacity:.5}}
.threat-card .sev-label{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.sev-label.critical{color:#7C3AED}.sev-label.high{color:#DC2626}.sev-label.elevated{color:#D97706}
.sev-label.moderate{color:#2563EB}.sev-label.low{color:#059669}
.detail-box{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:24px;margin-bottom:24px}
.detail-box h2{font-size:1.3rem;margin-bottom:12px;color:#e2e8f0}
.detail-meta{display:flex;gap:16px;flex-wrap:wrap;font-size:.85rem;color:#94a3b8;margin-bottom:16px}
.detail-body{white-space:pre-wrap;line-height:1.6;color:#cbd5e1;font-size:.9rem;background:#0f172a;border:1px solid #334155;border-radius:6px;padding:16px;margin-top:12px}
.form-section{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:20px;margin-bottom:24px}
.form-section h3{font-size:1rem;color:#3b82f6;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{display:flex;flex-direction:column;gap:4px}
.form-group.full{grid-column:1/-1}
.form-group label{font-size:.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.3px}
.form-group input,.form-group select,.form-group textarea{background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:6px;padding:8px 12px;font-size:.9rem;font-family:inherit}
.form-group textarea{min-height:80px;resize:vertical}
.btn{padding:8px 20px;border:none;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-sm{padding:4px 12px;font-size:.75rem}
.btn-success{background:#059669;color:#fff}.btn-danger{background:#dc2626;color:#fff}
.review-row{display:flex;gap:8px;align-items:center;margin-top:12px;flex-wrap:wrap}
.sources-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.source-stat{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:14px;text-align:center}
.source-stat .sv{font-size:1.5rem;font-weight:700;color:#3b82f6}.source-stat .sl{font-size:.75rem;color:#94a3b8;text-transform:uppercase;margin-top:4px}
a{color:#3b82f6;text-decoration:none}a:hover{text-decoration:underline}
.back-link{display:inline-flex;align-items:center;gap:6px;font-size:.85rem;margin-bottom:16px;color:#94a3b8}
.back-link:hover{color:#e2e8f0}
@media(max-width:640px){.form-grid{grid-template-columns:1fr}.grid{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="top-bar">
    <h1><i class="fas fa-satellite-dish"></i> Intelligence System</h1>
    <div class="user-info"><i class="fas fa-user-shield"></i> <?=htmlspecialchars($userName)?> — Tier <?=$userRankTier?></div>
</div>
<div class="container">

<?php if ($msg): ?>
<div class="msg <?=$msgType?>"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>

<?php if ($detailReport): ?>
<!-- REPORT DETAIL VIEW -->
<a href="intelligence.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Intel Dashboard</a>
<div class="detail-box">
    <div class="badge-row">
        <span class="badge badge-<?=$detailReport['report_type']?>"><?=strtoupper($detailReport['report_type'])?></span>
        <span class="badge badge-<?=str_replace('_','',$detailReport['classification'])?>"><?=strtoupper(str_replace('_',' ',$detailReport['classification']))?></span>
        <span class="status-badge status-<?=$detailReport['status']?>"><?=ucfirst(str_replace('_',' ',$detailReport['status']))?></span>
    </div>
    <h2><?=htmlspecialchars($detailReport['title'])?></h2>
    <div class="detail-meta">
        <span><i class="fas fa-hashtag"></i> <?=htmlspecialchars($detailReport['report_code'])?></span>
        <span><i class="fas fa-star"></i> Reliability: <span class="grade grade-<?=$detailReport['reliability']?>"><?=$detailReport['reliability']?></span></span>
        <span><i class="fas fa-calendar"></i> <?=date('Y-m-d H:i', strtotime($detailReport['created_at']))?></span>
        <?php if ($detailReport['source']): ?><span><i class="fas fa-globe"></i> <?=htmlspecialchars($detailReport['source'])?></span><?php endif; ?>
        <?php if ($detailReport['reviewed_by']): ?><span><i class="fas fa-check-circle"></i> Reviewed by #<?=(int)$detailReport['reviewed_by']?></span><?php endif; ?>
    </div>
    <h4 style="color:#94a3b8;margin-bottom:6px">Summary</h4>
    <p style="color:#cbd5e1;line-height:1.6;margin-bottom:16px"><?=nl2br(htmlspecialchars($detailReport['summary']))?></p>
    <?php if ($detailReport['full_report']): ?>
    <h4 style="color:#94a3b8;margin-bottom:6px">Full Report</h4>
    <div class="detail-body"><?=htmlspecialchars($detailReport['full_report'])?></div>
    <?php endif; ?>
    <?php if ($userRankTier >= 7 && in_array($detailReport['status'], ['pending_review','draft'])): ?>
    <div class="review-row">
        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="review_report"><input type="hidden" name="report_id" value="<?=(int)$detailReport['id']?>"><input type="hidden" name="new_status" value="approved"><button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</button></form>
        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="review_report"><input type="hidden" name="report_id" value="<?=(int)$detailReport['id']?>"><input type="hidden" name="new_status" value="rejected"><button class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Reject</button></form>
        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="review_report"><input type="hidden" name="report_id" value="<?=(int)$detailReport['id']?>"><input type="hidden" name="new_status" value="archived"><button class="btn btn-sm" style="background:#334155;color:#94a3b8"><i class="fas fa-archive"></i> Archive</button></form>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- DASHBOARD & TABS -->
<div class="stats-row">
    <div class="stat-card"><div class="val"><?=$totalReports?></div><div class="lbl">Intel Reports</div></div>
    <div class="stat-card"><div class="val"><?=$activeThreats?></div><div class="lbl">Active Threats</div></div>
    <?php foreach ($byClass as $cl => $cnt): ?>
    <div class="stat-card"><div class="val"><?=$cnt?></div><div class="lbl"><?=ucfirst(str_replace('_',' ',$cl))?></div></div>
    <?php endforeach; ?>
    <div class="stat-card"><div class="val"><?=$totalSources?></div><div class="lbl">Intel Sources</div></div>
</div>

<div class="tabs">
    <div class="tab active" onclick="switchTab('reports')"><i class="fas fa-file-alt"></i> Reports</div>
    <div class="tab" onclick="switchTab('threats')"><i class="fas fa-exclamation-triangle"></i> Threat Board</div>
    <?php if ($userRankTier >= 5): ?><div class="tab" onclick="switchTab('file')"><i class="fas fa-pen"></i> File Report</div>
    <div class="tab" onclick="switchTab('threat-file')"><i class="fas fa-shield-alt"></i> Report Threat</div><?php endif; ?>
    <div class="tab" onclick="switchTab('sources')"><i class="fas fa-database"></i> Sources</div>
</div>

<!-- REPORTS PANEL -->
<div id="panel-reports" class="panel active">
<div class="filters">
    <select onchange="applyFilter('type',this.value)">
        <option value="">All Types</option>
        <?php foreach (['sigint'=>'SIGINT','humint'=>'HUMINT','osint'=>'OSINT','cyber'=>'CYBER','techint'=>'TECHINT'] as $k=>$v): ?>
        <option value="<?=$k?>" <?=$filterType===$k?'selected':''?>><?=$v?></option>
        <?php endforeach; ?>
    </select>
    <select onchange="applyFilter('class',this.value)">
        <option value="">All Classifications</option>
        <?php foreach ($classRankMap as $cl => $mr): if ($userRankTier >= $mr): ?>
        <option value="<?=$cl?>" <?=$filterClass===$cl?'selected':''?>><?=strtoupper(str_replace('_',' ',$cl))?></option>
        <?php endif; endforeach; ?>
    </select>
</div>
<div class="grid">
<?php if ($reports && $reports->num_rows > 0): while ($r = $reports->fetch_assoc()): ?>
<a href="?report=<?=urlencode($r['report_code'])?>" class="card" style="text-decoration:none;color:inherit">
    <div class="card-head">
        <span class="card-code"><?=htmlspecialchars($r['report_code'])?></span>
        <span class="status-badge status-<?=$r['status']?>"><?=ucfirst(str_replace('_',' ',$r['status']))?></span>
    </div>
    <div class="card-title"><?=htmlspecialchars($r['title'])?></div>
    <div class="badge-row">
        <span class="badge badge-<?=$r['report_type']?>"><?=strtoupper($r['report_type'])?></span>
        <span class="badge badge-<?=str_replace('_','',$r['classification'])?>"><?=strtoupper(str_replace('_',' ',$r['classification']))?></span>
        <span class="grade grade-<?=$r['reliability']?>">Grade <?=$r['reliability']?></span>
    </div>
    <div style="font-size:.8rem;color:#64748b;margin-top:6px"><?=date('Y-m-d H:i', strtotime($r['created_at']))?></div>
</a>
<?php endwhile; else: ?>
<div style="color:#64748b;grid-column:1/-1;text-align:center;padding:40px">No intel reports match your filters or clearance level.</div>
<?php endif; ?>
</div>
</div>

<!-- THREAT BOARD PANEL -->
<div id="panel-threats" class="panel">
<div class="grid">
<?php if ($threats && $threats->num_rows > 0): while ($t = $threats->fetch_assoc()):
    $indicators = json_decode($t['indicators'] ?? '[]', true);
    $indCount = is_array($indicators) ? count($indicators) : 0;
?>
<div class="card threat-card">
    <div class="card-head">
        <span class="card-code"><?=htmlspecialchars($t['threat_code'])?></span>
        <span class="sev-label <?=$t['severity']?>"><?=strtoupper($t['severity'])?></span>
    </div>
    <div class="card-title"><?=htmlspecialchars($t['threat_name'])?></div>
    <div class="badge-row">
        <span class="badge badge-<?=$t['threat_type']?>"><?=strtoupper($t['threat_type'])?></span>
        <span style="font-size:.75rem;color:#94a3b8"><i class="fas fa-crosshairs"></i> <?=$indCount?> indicators</span>
        <span class="status-badge" style="background:#1e3a5f;color:#60a5fa"><?=ucfirst($t['status'])?></span>
    </div>
    <p style="font-size:.85rem;color:#94a3b8;margin-top:6px;line-height:1.5"><?=htmlspecialchars(mb_strimwidth($t['description'], 0, 150, '...'))?></p>
    <?php if ($t['countermeasures']): ?>
    <div style="font-size:.8rem;color:#6ee7b7;margin-top:8px"><i class="fas fa-shield-alt"></i> <?=htmlspecialchars(mb_strimwidth($t['countermeasures'], 0, 100, '...'))?></div>
    <?php endif; ?>
    <div class="sev-bar sev-<?=$t['severity']?>"></div>
    <?php if ($userRankTier >= 7): ?>
    <div class="review-row">
        <?php foreach (['monitoring','mitigated','resolved'] as $ns): if ($ns !== $t['status']): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="update_threat"><input type="hidden" name="threat_id" value="<?=(int)$t['id']?>"><input type="hidden" name="new_status" value="<?=$ns?>"><button class="btn btn-sm" style="background:#334155;color:#94a3b8"><?=ucfirst($ns)?></button></form>
        <?php endif; endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endwhile; else: ?>
<div style="color:#64748b;grid-column:1/-1;text-align:center;padding:40px">No active threats on record.</div>
<?php endif; ?>
</div>
</div>

<!-- FILE REPORT PANEL -->
<?php if ($userRankTier >= 5): ?>
<div id="panel-file" class="panel">
<div class="form-section">
    <h3><i class="fas fa-file-signature"></i> File Intel Report</h3>
    <form method="POST">
    <input type="hidden" name="csrf" value="<?=$csrf?>">
    <input type="hidden" name="action" value="file_report">
    <div class="form-grid">
        <div class="form-group"><label>Title</label><input type="text" name="title" required maxlength="255"></div>
        <div class="form-group"><label>Report Type</label>
            <select name="report_type" required><option value="">Select…</option><option value="sigint">SIGINT</option><option value="humint">HUMINT</option><option value="osint">OSINT</option><option value="cyber">CYBER</option><option value="techint">TECHINT</option></select>
        </div>
        <div class="form-group"><label>Classification</label>
            <select name="classification" required><option value="">Select…</option><option value="unclassified">Unclassified</option><?php if($userRankTier>=4):?><option value="confidential">Confidential</option><?php endif;?><?php if($userRankTier>=7):?><option value="secret">Secret</option><?php endif;?><?php if($userRankTier>=9):?><option value="top_secret">Top Secret</option><?php endif;?></select>
        </div>
        <div class="form-group"><label>Reliability</label>
            <select name="reliability"><option value="A">A — Completely Reliable</option><option value="B">B — Usually Reliable</option><option value="C" selected>C — Fairly Reliable</option><option value="D">D — Not Usually Reliable</option><option value="E">E — Unreliable</option><option value="F">F — Cannot Be Judged</option></select>
        </div>
        <div class="form-group full"><label>Source</label><input type="text" name="source" maxlength="255" placeholder="Origin of intelligence…"></div>
        <div class="form-group full"><label>Summary</label><textarea name="summary" required rows="3" placeholder="Brief summary…"></textarea></div>
        <div class="form-group full"><label>Full Report</label><textarea name="full_report" rows="8" placeholder="Detailed report body…"></textarea></div>
    </div>
    <div style="margin-top:16px"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Report</button></div>
    </form>
</div>
</div>

<!-- REPORT THREAT PANEL -->
<div id="panel-threat-file" class="panel">
<div class="form-section">
    <h3><i class="fas fa-radiation-alt"></i> Report Threat</h3>
    <form method="POST">
    <input type="hidden" name="csrf" value="<?=$csrf?>">
    <input type="hidden" name="action" value="report_threat">
    <div class="form-grid">
        <div class="form-group"><label>Threat Name</label><input type="text" name="threat_name" required maxlength="255"></div>
        <div class="form-group"><label>Threat Type</label>
            <select name="threat_type" required><option value="">Select…</option><option value="cyber">Cyber</option><option value="physical">Physical</option><option value="social">Social</option><option value="economic">Economic</option><option value="technical">Technical</option></select>
        </div>
        <div class="form-group"><label>Severity</label>
            <select name="severity" required><option value="">Select…</option><option value="low">Low</option><option value="moderate">Moderate</option><option value="elevated">Elevated</option><option value="high">High</option><option value="critical">Critical</option></select>
        </div>
        <div class="form-group full"><label>Description</label><textarea name="description" required rows="4" placeholder="Describe the threat…"></textarea></div>
        <div class="form-group full"><label>Indicators (one per line)</label><textarea name="indicators" rows="4" placeholder="IP addresses, domains, signatures…"></textarea></div>
        <div class="form-group full"><label>Countermeasures</label><textarea name="countermeasures" rows="4" placeholder="Recommended countermeasures…"></textarea></div>
    </div>
    <div style="margin-top:16px"><button type="submit" class="btn btn-primary"><i class="fas fa-shield-alt"></i> Submit Threat</button></div>
    </form>
</div>
</div>
<?php endif; ?>

<!-- SOURCES PANEL -->
<div id="panel-sources" class="panel">
<h3 style="color:#3b82f6;margin-bottom:16px;display:flex;align-items:center;gap:8px"><i class="fas fa-database"></i> Intel Sources Overview</h3>
<div class="sources-grid">
    <div class="source-stat"><div class="sv"><?=$totalSources?></div><div class="sl">Total Sources</div></div>
    <?php foreach ($sourcesByStatus as $st => $cnt): ?>
    <div class="source-stat"><div class="sv"><?=$cnt?></div><div class="sl"><?=htmlspecialchars(ucfirst($st))?></div></div>
    <?php endforeach; ?>
    <?php if (!$sourcesByStatus): ?>
    <div style="color:#64748b;grid-column:1/-1;text-align:center;padding:40px">No intel sources configured yet.</div>
    <?php endif; ?>
</div>
</div>

<?php endif; /* end !detailReport */ ?>
</div>

<script>
function switchTab(id){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
    document.getElementById('panel-'+id).classList.add('active');
    event.currentTarget.classList.add('active');
}
function applyFilter(key,val){
    const u=new URL(window.location);
    if(val)u.searchParams.set(key,val);else u.searchParams.delete(key);
    window.location=u;
}
</script>
</body>
</html>
