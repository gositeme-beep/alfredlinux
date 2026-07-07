<?php
/**
 * ═══════════════════════════════════════════
 *  Civil Affairs & Citizen Registry — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_civaffairs'])) $_SESSION['csrf_civaffairs'] = bin2hex(random_bytes(32));
requireRank(1);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS citizen_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    citizen_id_formatted VARCHAR(20) NOT NULL,
    status ENUM('active','inactive','suspended','exiled') DEFAULT 'active',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    home_department VARCHAR(255) DEFAULT NULL,
    civil_rights_level ENUM('full','restricted','suspended','revoked') DEFAULT 'full',
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS civil_complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_code VARCHAR(20) NOT NULL,
    filed_by INT NOT NULL,
    complaint_type ENUM('service','conduct','grievance','suggestion') DEFAULT 'grievance',
    target_type ENUM('person','system','department') DEFAULT 'system',
    target_id VARCHAR(255) DEFAULT NULL,
    description TEXT NOT NULL,
    status ENUM('filed','reviewing','resolved','dismissed') DEFAULT 'filed',
    assigned_to INT DEFAULT NULL,
    resolution TEXT DEFAULT NULL,
    filed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS civil_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(255) NOT NULL,
    service_type ENUM('record','aid','information','liaison') DEFAULT 'information',
    description TEXT,
    availability ENUM('active','suspended','discontinued') DEFAULT 'active',
    min_citizen_level ENUM('full','restricted','suspended','revoked') DEFAULT 'full',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS humanitarian_aid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aid_code VARCHAR(20) NOT NULL,
    aid_type ENUM('resource','credit','access','protection') DEFAULT 'resource',
    description TEXT NOT NULL,
    quantity INT DEFAULT 0,
    distributed_to INT DEFAULT 0,
    authorized_by INT NOT NULL,
    status ENUM('planned','active','completed') DEFAULT 'planned',
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS census_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    census_year INT NOT NULL,
    total_population INT DEFAULT 0,
    active_military INT DEFAULT 0,
    active_civilian INT DEFAULT 0,
    total_departments INT DEFAULT 0,
    avg_xp DECIMAL(12,2) DEFAULT 0,
    avg_service_days DECIMAL(10,2) DEFAULT 0,
    demographics JSON DEFAULT NULL,
    conducted_by INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Auto-register current user if not in registry ──
$regCheck = $db->prepare("SELECT id FROM citizen_registry WHERE client_id = ?");
$regCheck->execute([$clientId]);
if (!$regCheck->fetch()) {
    $citCount = (int)$db->query("SELECT COUNT(*)+1 FROM citizen_registry")->fetchColumn();
    $citId = 'CIT-' . date('Y') . '-' . str_pad($citCount, 5, '0', STR_PAD_LEFT);
    $db->prepare("INSERT INTO citizen_registry (client_id, citizen_id_formatted) VALUES (?,?)")->execute([$clientId, $citId]);
}

// ── Seed civil services if empty ──
$svcCount = (int)$db->query("SELECT COUNT(*) FROM civil_services")->fetchColumn();
if ($svcCount === 0) {
    $services = [
        ['Service Record Request', 'record', 'Request official copies of your military service record, rank history, and XP transcript.', 'active', 'full'],
        ['ID Card Replacement', 'record', 'Replace lost or damaged citizen identification cards.', 'active', 'full'],
        ['Humanitarian Aid Application', 'aid', 'Apply for emergency resource allocation, credit assistance, or access restoration.', 'active', 'restricted'],
        ['Department Liaison', 'liaison', 'Request a liaison officer to facilitate inter-department communication.', 'active', 'full'],
        ['Legal Information Service', 'information', 'Access information about your rights, the Constitution, and institutional law.', 'active', 'full'],
        ['Complaint Filing Assistance', 'information', 'Get help filing service complaints, grievances, or suggestions.', 'active', 'restricted'],
        ['Census & Statistics', 'information', 'Access population statistics, demographics, and census data.', 'active', 'full'],
        ['Exile Appeal Processing', 'record', 'File an appeal against exile status through proper channels.', 'active', 'suspended'],
    ];
    $ins = $db->prepare("INSERT INTO civil_services (service_name, service_type, description, availability, min_citizen_level) VALUES (?,?,?,?,?)");
    foreach ($services as $s) $ins->execute($s);
}

$csrf = $_SESSION['csrf_civaffairs'];
$myCitizen = $db->prepare("SELECT * FROM citizen_registry WHERE client_id = ?");
$myCitizen->execute([$clientId]);
$myReg = $myCitizen->fetch(PDO::FETCH_ASSOC);

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'file_complaint') {
            $cType    = $_POST['complaint_type'] ?? 'grievance';
            $tType    = $_POST['target_type'] ?? 'system';
            $tId      = trim($_POST['target_id'] ?? '');
            $desc     = trim($_POST['complaint_desc'] ?? '');
            $validCT  = ['service','conduct','grievance','suggestion'];
            $validTT  = ['person','system','department'];
            if ($desc === '' || !in_array($cType, $validCT, true) || !in_array($tType, $validTT, true)) {
                $msg = 'Description and valid types required.'; $msgType = 'error';
            } else {
                $code = 'CC-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $db->prepare("INSERT INTO civil_complaints (complaint_code, filed_by, complaint_type, target_type, target_id, description) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$code, $clientId, $cType, $tType, $tId ?: null, $desc]);
                awardXP($clientId, 'complaint_filed', ['code' => $code]);
                $msg = "Complaint <strong>$code</strong> filed successfully."; $msgType = 'success';
            }
        } elseif ($action === 'assign_complaint' && $isOfficer) {
            $cId = (int)($_POST['complaint_id'] ?? 0);
            $assignTo = (int)($_POST['assign_to'] ?? 0);
            if ($cId < 1) {
                $msg = 'Invalid complaint.'; $msgType = 'error';
            } else {
                $assignTo = $assignTo > 0 ? $assignTo : $clientId;
                $stmt = $db->prepare("UPDATE civil_complaints SET status = 'reviewing', assigned_to = ? WHERE id = ? AND status = 'filed'");
                $stmt->execute([$assignTo, $cId]);
                $msg = $stmt->rowCount() ? 'Complaint assigned for review.' : 'Complaint not found or already reviewing.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'resolve_complaint' && $isOfficer) {
            $cId = (int)($_POST['complaint_id'] ?? 0);
            $resolution = trim($_POST['resolution'] ?? '');
            $newStatus  = $_POST['resolve_status'] ?? 'resolved';
            $validRS = ['resolved','dismissed'];
            if ($cId < 1 || $resolution === '' || !in_array($newStatus, $validRS, true)) {
                $msg = 'Resolution text required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE civil_complaints SET status = ?, resolution = ?, resolved_at = NOW() WHERE id = ? AND status = 'reviewing'");
                $stmt->execute([$newStatus, $resolution, $cId]);
                $msg = $stmt->rowCount() ? 'Complaint ' . $newStatus . '.' : 'Complaint not found or not in review.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'authorize_aid' && ($isFlag || $isCommander)) {
            $aidType = $_POST['aid_type'] ?? 'resource';
            $aidDesc = trim($_POST['aid_desc'] ?? '');
            $aidQty  = (int)($_POST['aid_qty'] ?? 0);
            $validAT = ['resource','credit','access','protection'];
            if ($aidDesc === '' || !in_array($aidType, $validAT, true)) {
                $msg = 'Aid description and type required.'; $msgType = 'error';
            } else {
                $code = 'HA-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $db->prepare("INSERT INTO humanitarian_aid (aid_code, aid_type, description, quantity, authorized_by, status, started_at) VALUES (?,?,?,?,?,'active',NOW())");
                $stmt->execute([$code, $aidType, $aidDesc, $aidQty, $clientId]);
                $msg = "Humanitarian aid <strong>$code</strong> authorized and active."; $msgType = 'success';
            }
        } elseif ($action === 'complete_aid' && ($isFlag || $isCommander)) {
            $aidId = (int)($_POST['aid_id'] ?? 0);
            $distTo = (int)($_POST['distributed_to'] ?? 0);
            $stmt = $db->prepare("UPDATE humanitarian_aid SET status = 'completed', distributed_to = ?, ended_at = NOW() WHERE id = ? AND status = 'active'");
            $stmt->execute([$distTo, $aidId]);
            $msg = $stmt->rowCount() ? 'Aid distribution completed.' : 'Aid not found or not active.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'conduct_census' && ($isFlag || $isCommander)) {
            $year = (int)date('Y');
            $totalPop  = (int)$db->query("SELECT COUNT(*) FROM tblclients WHERE status = 'Active'")->fetchColumn();
            $totalCitz = (int)$db->query("SELECT COUNT(*) FROM citizen_registry WHERE status = 'active'")->fetchColumn();
            $avgXP     = (float)$db->query("SELECT COALESCE(AVG(xp),0) FROM PRODUCT_TIERS WHERE xp > 0")->fetchColumn();
            $demographics = json_encode([
                'total_registered' => $totalCitz,
                'total_accounts' => $totalPop,
                'rights_full' => (int)$db->query("SELECT COUNT(*) FROM citizen_registry WHERE civil_rights_level = 'full'")->fetchColumn(),
                'rights_restricted' => (int)$db->query("SELECT COUNT(*) FROM citizen_registry WHERE civil_rights_level = 'restricted'")->fetchColumn(),
                'rights_suspended' => (int)$db->query("SELECT COUNT(*) FROM citizen_registry WHERE civil_rights_level = 'suspended'")->fetchColumn(),
                'exiled' => (int)$db->query("SELECT COUNT(*) FROM citizen_registry WHERE status = 'exiled'")->fetchColumn(),
            ]);
            $stmt = $db->prepare("INSERT INTO census_records (census_year, total_population, active_military, active_civilian, avg_xp, demographics, conducted_by) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$year, $totalPop, $totalCitz, max(0, $totalPop - $totalCitz), $avgXP, $demographics, $clientId]);
            awardXP($clientId, 'census_conducted', ['year' => $year]);
            $msg = "Census for $year completed. Population: $totalPop."; $msgType = 'success';

        } elseif ($action === 'update_citizen' && ($isFlag || $isCommander)) {
            $targetCid = (int)($_POST['target_client_id'] ?? 0);
            $newStatus = $_POST['cit_status'] ?? '';
            $newRights = $_POST['cit_rights'] ?? '';
            $newDept   = trim($_POST['cit_dept'] ?? '');
            $validCS = ['active','inactive','suspended','exiled'];
            $validCR = ['full','restricted','suspended','revoked'];
            if ($targetCid < 1 || !in_array($newStatus, $validCS, true) || !in_array($newRights, $validCR, true)) {
                $msg = 'Invalid parameters.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE citizen_registry SET status = ?, civil_rights_level = ?, home_department = ? WHERE client_id = ?");
                $stmt->execute([$newStatus, $newRights, $newDept ?: null, $targetCid]);
                $msg = $stmt->rowCount() ? "Citizen record updated." : 'Citizen not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_civaffairs'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_civaffairs'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'registry';
$citizens   = $db->query("SELECT cr.*, CONCAT(c.firstname,' ',c.lastname) AS citizen_name, c.email FROM citizen_registry cr LEFT JOIN tblclients c ON c.id = cr.client_id ORDER BY cr.registration_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$complaints = $db->query("SELECT cc.*, CONCAT(c.firstname,' ',c.lastname) AS filer_name, CONCAT(c2.firstname,' ',c2.lastname) AS assignee_name FROM civil_complaints cc LEFT JOIN tblclients c ON c.id = cc.filed_by LEFT JOIN tblclients c2 ON c2.id = cc.assigned_to ORDER BY cc.filed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$services   = $db->query("SELECT * FROM civil_services ORDER BY service_type, service_name")->fetchAll(PDO::FETCH_ASSOC);
$aidOps     = $db->query("SELECT ha.*, CONCAT(c.firstname,' ',c.lastname) AS authorizer_name FROM humanitarian_aid ha LEFT JOIN tblclients c ON c.id = ha.authorized_by ORDER BY ha.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$censuses   = $db->query("SELECT cr.*, CONCAT(c.firstname,' ',c.lastname) AS conductor_name FROM census_records cr LEFT JOIN tblclients c ON c.id = cr.conducted_by ORDER BY cr.census_year DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalCitizens = count($citizens);
$activeCitizens = count(array_filter($citizens, fn($c) => $c['status'] === 'active'));
$openComplaints = count(array_filter($complaints, fn($c) => in_array($c['status'], ['filed','reviewing'])));

$pageTitle = 'Civil Affairs & Citizen Registry';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.ca-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.ca-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.ca-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.ca-card:hover{border-color:#8b5cf6;box-shadow:0 0 12px rgba(139,92,246,.12)}
.ca-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.ca-sub{color:#94a3b8;font-size:.85rem}
.ca-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.ca-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.ca-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.ca-tab.active{background:#8b5cf6;color:#fff}
.ca-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.ca-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:130px;text-align:center}
.ca-stat .val{font-size:1.5rem;font-weight:700;color:#8b5cf6}
.ca-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.ca-btn{background:#8b5cf6;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.ca-btn:hover{background:#7c3aed}
.ca-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.ca-btn-outline{background:transparent;border:1px solid #8b5cf6;color:#8b5cf6}
.ca-btn-outline:hover{background:#8b5cf6;color:#fff}
.ca-btn-green{background:#22c55e;color:#fff}.ca-btn-green:hover{background:#16a34a}
.ca-btn-red{background:#ef4444;color:#fff}.ca-btn-red:hover{background:#dc2626}
.ca-btn-gold{background:#d4a017;color:#000}.ca-btn-gold:hover{background:#e2b340}
.ca-btn-blue{background:#3b82f6;color:#fff}.ca-btn-blue:hover{background:#2563eb}
.ca-input,.ca-select,.ca-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.ca-textarea{min-height:100px;resize:vertical}
.ca-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.ca-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.ca-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.ca-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.ca-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.ca-modal-bg.open{display:flex}
.ca-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:580px;max-height:80vh;overflow-y:auto}
.ca-modal h3{color:#f1f5f9;margin:0 0 1rem}
.ca-form-row{margin-bottom:.75rem}
.ca-citizen{display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:#0a0a14;border:1px solid #2a2a4a;border-radius:8px;margin-bottom:.5rem}
.ca-citizen.active{border-color:#22c55e40}
.ca-citizen.suspended{border-color:#ef444440}
.ca-citizen.exiled{border-color:#ef444480;opacity:.6}
.ca-id{font-family:monospace;color:#8b5cf6;font-size:.75rem;letter-spacing:.1em}
.ca-rights{font-size:.65rem;padding:1px 6px;border-radius:4px}
.ca-complaint{padding:1rem 1.25rem;border-radius:8px;border:1px solid #2a2a4a;background:#1a1a2e;margin-bottom:.75rem}
.ca-svc{display:flex;gap:1rem;padding:.75rem 1rem;border-radius:8px;border:1px solid #2a2a4a;background:#1a1a2e;margin-bottom:.5rem;align-items:flex-start}
</style>
<div class="ca-bg">
<div class="ca-wrap">
    <div class="ca-title"><i class="fas fa-people-roof"></i> Civil Affairs &amp; Citizen Registry</div>
    <p class="ca-sub" style="margin-bottom:1.25rem">Citizen services, complaints, humanitarian aid, and census — All ranks</p>

    <?php if ($msg): ?>
        <div class="ca-msg ca-msg-<?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- My Citizen Card -->
    <?php if ($myReg): ?>
    <div class="ca-card" style="border-left:4px solid #8b5cf6;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
        <i class="fas fa-id-card" style="font-size:2.5rem;color:#8b5cf6"></i>
        <div>
            <div class="ca-id"><?= htmlspecialchars($myReg['citizen_id_formatted']) ?></div>
            <div style="color:#f1f5f9;font-size:.95rem;font-weight:600">Your Citizen Record</div>
            <div style="font-size:.8rem;color:#94a3b8">Registered: <?= date('M j, Y', strtotime($myReg['registration_date'])) ?> &bull; Rights: <span class="ca-rights" style="background:<?= $myReg['civil_rights_level']==='full'?'#22c55e20':'#ef444420' ?>;color:<?= $myReg['civil_rights_level']==='full'?'#22c55e':'#ef4444' ?>"><?= strtoupper($myReg['civil_rights_level']) ?></span></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="ca-stat-bar">
        <div class="ca-stat"><div class="val"><?= $totalCitizens ?></div><div class="lbl">Registered Citizens</div></div>
        <div class="ca-stat"><div class="val" style="color:#22c55e"><?= $activeCitizens ?></div><div class="lbl">Active</div></div>
        <div class="ca-stat"><div class="val" style="color:#f59e0b"><?= $openComplaints ?></div><div class="lbl">Open Complaints</div></div>
        <div class="ca-stat"><div class="val" style="color:#3b82f6"><?= count($aidOps) ?></div><div class="lbl">Aid Operations</div></div>
    </div>

    <!-- Tabs -->
    <div class="ca-tabs">
        <a href="?tab=registry" class="ca-tab <?= $tab==='registry'?'active':'' ?>"><i class="fas fa-id-card"></i> Registry</a>
        <a href="?tab=complaints" class="ca-tab <?= $tab==='complaints'?'active':'' ?>"><i class="fas fa-envelope-open-text"></i> Complaints</a>
        <a href="?tab=services" class="ca-tab <?= $tab==='services'?'active':'' ?>"><i class="fas fa-hands-helping"></i> Services</a>
        <a href="?tab=aid" class="ca-tab <?= $tab==='aid'?'active':'' ?>"><i class="fas fa-hand-holding-heart"></i> Humanitarian Aid</a>
        <a href="?tab=census" class="ca-tab <?= $tab==='census'?'active':'' ?>"><i class="fas fa-chart-bar"></i> Census</a>
    </div>

    <!-- ═══ TAB: CITIZEN REGISTRY ═══ -->
    <?php if ($tab === 'registry'): ?>
        <?php
        $statusColors = ['active'=>'#22c55e','inactive'=>'#64748b','suspended'=>'#f59e0b','exiled'=>'#ef4444'];
        $rightsColors = ['full'=>'#22c55e','restricted'=>'#f59e0b','suspended'=>'#ef4444','revoked'=>'#64748b'];
        foreach ($citizens as $cit): ?>
            <div class="ca-citizen <?= $cit['status'] ?>">
                <div style="flex:0 0 40px;text-align:center"><i class="fas fa-user-circle" style="font-size:1.5rem;color:<?= $statusColors[$cit['status']] ?>"></i></div>
                <div style="flex:1">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                        <div>
                            <strong style="color:#f1f5f9"><?= htmlspecialchars($cit['citizen_name'] ?? 'Unknown') ?></strong>
                            <span class="ca-id" style="margin-left:.5rem"><?= htmlspecialchars($cit['citizen_id_formatted']) ?></span>
                        </div>
                        <div style="display:flex;gap:.25rem">
                            <span class="ca-badge" style="background:<?= $statusColors[$cit['status']] ?>20;color:<?= $statusColors[$cit['status']] ?>;border:1px solid <?= $statusColors[$cit['status']] ?>40"><?= strtoupper($cit['status']) ?></span>
                            <span class="ca-rights" style="background:<?= $rightsColors[$cit['civil_rights_level']] ?>20;color:<?= $rightsColors[$cit['civil_rights_level']] ?>"><?= strtoupper($cit['civil_rights_level']) ?></span>
                        </div>
                    </div>
                    <div style="color:#94a3b8;font-size:.75rem;margin-top:.25rem">
                        Registered: <?= date('M j, Y', strtotime($cit['registration_date'])) ?>
                        <?php if ($cit['home_department']): ?>&bull; Dept: <?= htmlspecialchars($cit['home_department']) ?><?php endif; ?>
                        &bull; <?= htmlspecialchars($cit['email'] ?? '') ?>
                    </div>
                </div>
                <?php if ($isFlag || $isCommander): ?>
                    <button class="ca-btn-sm ca-btn ca-btn-outline" onclick="openCitizenModal(<?= (int)$cit['client_id'] ?>,'<?= htmlspecialchars($cit['citizen_name'] ?? 'Unknown', ENT_QUOTES) ?>','<?= $cit['status'] ?>','<?= $cit['civil_rights_level'] ?>')"><i class="fas fa-edit"></i></button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($citizens)): ?>
            <div class="ca-card" style="text-align:center;color:#64748b"><i class="fas fa-id-card" style="font-size:2rem;margin-bottom:.5rem"></i><p>No citizens registered yet.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: COMPLAINTS ═══ -->
    <?php elseif ($tab === 'complaints'): ?>
        <div style="margin-bottom:1rem"><button class="ca-btn" onclick="document.getElementById('modalComplaint').classList.add('open')"><i class="fas fa-envelope-open-text"></i> File Complaint</button></div>
        <?php
        $compColors = ['filed'=>'#f59e0b','reviewing'=>'#3b82f6','resolved'=>'#22c55e','dismissed'=>'#64748b'];
        $compIcons  = ['service'=>'wrench','conduct'=>'user-shield','grievance'=>'exclamation-circle','suggestion'=>'lightbulb'];
        foreach ($complaints as $comp): ?>
            <div class="ca-complaint">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-<?= $compIcons[$comp['complaint_type']] ?? 'file' ?>" style="color:#8b5cf6"></i>
                        <strong style="color:#8b5cf6;font-size:.8rem;margin-left:.25rem"><?= htmlspecialchars($comp['complaint_code']) ?></strong>
                        <span class="ca-badge" style="background:#2a2a4a;color:#94a3b8;margin-left:.25rem"><?= strtoupper($comp['complaint_type']) ?></span>
                    </div>
                    <span class="ca-badge" style="background:<?= $compColors[$comp['status']] ?>20;color:<?= $compColors[$comp['status']] ?>;border:1px solid <?= $compColors[$comp['status']] ?>40"><?= strtoupper($comp['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Filed by <?= htmlspecialchars($comp['filer_name'] ?? 'Unknown') ?> on <?= date('M j, Y', strtotime($comp['filed_at'])) ?>
                    &bull; Target: <?= strtoupper($comp['target_type']) ?><?= $comp['target_id'] ? ' — ' . htmlspecialchars($comp['target_id']) : '' ?>
                    <?php if ($comp['assignee_name']): ?>&bull; Assigned: <?= htmlspecialchars($comp['assignee_name']) ?><?php endif; ?>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($comp['description']) ?></p>
                <?php if ($comp['resolution']): ?>
                    <div style="border-top:1px solid #2a2a4a;margin-top:.75rem;padding-top:.75rem">
                        <strong style="color:#22c55e;font-size:.8rem"><i class="fas fa-check-circle"></i> RESOLUTION (<?= date('M j, Y', strtotime($comp['resolved_at'])) ?>):</strong>
                        <p style="color:#86efac;font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars($comp['resolution']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($isOfficer && $comp['status'] === 'filed'): ?>
                    <form method="POST" style="margin-top:.5rem;display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="assign_complaint"><input type="hidden" name="complaint_id" value="<?= $comp['id'] ?>"><button class="ca-btn-sm ca-btn ca-btn-blue"><i class="fas fa-user-check"></i> Assign to Me</button></form>
                <?php endif; ?>
                <?php if ($isOfficer && $comp['status'] === 'reviewing'): ?>
                    <button class="ca-btn-sm ca-btn ca-btn-green" style="margin-top:.5rem" onclick="openResolveModal(<?= $comp['id'] ?>,'<?= htmlspecialchars($comp['complaint_code'], ENT_QUOTES) ?>')"><i class="fas fa-check"></i> Resolve</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($complaints)): ?>
            <div class="ca-card" style="text-align:center;color:#64748b"><i class="fas fa-inbox" style="font-size:2rem;margin-bottom:.5rem"></i><p>No complaints filed.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: SERVICES ═══ -->
    <?php elseif ($tab === 'services'): ?>
        <?php
        $svcIcons  = ['record'=>'file-alt','aid'=>'hand-holding-heart','information'=>'info-circle','liaison'=>'handshake'];
        $svcColors = ['record'=>'#3b82f6','aid'=>'#22c55e','information'=>'#f59e0b','liaison'=>'#8b5cf6'];
        foreach ($services as $svc): ?>
            <div class="ca-svc">
                <div style="flex:0 0 40px;text-align:center;padding-top:.25rem"><i class="fas fa-<?= $svcIcons[$svc['service_type']] ?? 'cog' ?>" style="font-size:1.5rem;color:<?= $svcColors[$svc['service_type']] ?? '#94a3b8' ?>"></i></div>
                <div style="flex:1">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                        <strong style="color:#f1f5f9"><?= htmlspecialchars($svc['service_name']) ?></strong>
                        <div>
                            <span class="ca-badge" style="background:<?= $svc['availability']==='active'?'#22c55e':'#ef4444' ?>20;color:<?= $svc['availability']==='active'?'#22c55e':'#ef4444' ?>"><?= strtoupper($svc['availability']) ?></span>
                        </div>
                    </div>
                    <p style="color:#94a3b8;font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars($svc['description'] ?? '') ?></p>
                    <div style="color:#64748b;font-size:.7rem;margin-top:.25rem">Min. Rights: <?= strtoupper($svc['min_citizen_level']) ?> &bull; Type: <?= strtoupper($svc['service_type']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: HUMANITARIAN AID ═══ -->
    <?php elseif ($tab === 'aid'): ?>
        <?php if ($isFlag || $isCommander): ?>
            <div style="margin-bottom:1rem"><button class="ca-btn ca-btn-green" onclick="document.getElementById('modalAid').classList.add('open')"><i class="fas fa-hand-holding-heart"></i> Authorize Aid</button></div>
        <?php endif; ?>
        <?php
        $aidColors = ['planned'=>'#f59e0b','active'=>'#22c55e','completed'=>'#64748b'];
        $aidIcons  = ['resource'=>'box-open','credit'=>'coins','access'=>'key','protection'=>'shield-halved'];
        foreach ($aidOps as $aid): ?>
            <div class="ca-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-<?= $aidIcons[$aid['aid_type']] ?? 'hand-holding-heart' ?>" style="color:#22c55e"></i>
                        <strong style="color:#22c55e;font-size:.8rem;margin-left:.25rem"><?= htmlspecialchars($aid['aid_code']) ?></strong>
                        <span class="ca-badge" style="background:#2a2a4a;color:#94a3b8;margin-left:.25rem"><?= strtoupper($aid['aid_type']) ?></span>
                    </div>
                    <span class="ca-badge" style="background:<?= $aidColors[$aid['status']] ?>20;color:<?= $aidColors[$aid['status']] ?>;border:1px solid <?= $aidColors[$aid['status']] ?>40"><?= strtoupper($aid['status']) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($aid['description']) ?></p>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Authorized by <?= htmlspecialchars($aid['authorizer_name'] ?? 'Unknown') ?>
                    <?php if ($aid['quantity'] > 0): ?>&bull; Qty: <?= (int)$aid['quantity'] ?><?php endif; ?>
                    &bull; Distributed to: <?= (int)$aid['distributed_to'] ?> citizens
                    <?php if ($aid['started_at']): ?>&bull; Started: <?= date('M j, Y', strtotime($aid['started_at'])) ?><?php endif; ?>
                </div>
                <?php if ($aid['status'] === 'active' && ($isFlag || $isCommander)): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="complete_aid"><input type="hidden" name="aid_id" value="<?= $aid['id'] ?>">
                        <input type="number" name="distributed_to" class="ca-input" style="width:90px" placeholder="# served" min="0" value="<?= (int)$aid['distributed_to'] ?>">
                        <button class="ca-btn-sm ca-btn ca-btn-green"><i class="fas fa-check"></i> Complete</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($aidOps)): ?>
            <div class="ca-card" style="text-align:center;color:#64748b"><i class="fas fa-hand-holding-heart" style="font-size:2rem;margin-bottom:.5rem"></i><p>No humanitarian aid operations.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: CENSUS ═══ -->
    <?php elseif ($tab === 'census'): ?>
        <?php if ($isFlag || $isCommander): ?>
            <div style="margin-bottom:1rem">
                <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="conduct_census"><button class="ca-btn ca-btn-gold"><i class="fas fa-chart-bar"></i> Conduct Census (<?= date('Y') ?>)</button></form>
            </div>
        <?php endif; ?>
        <?php foreach ($censuses as $census): ?>
            <div class="ca-card">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <h3 style="color:#d4a017;font-size:1.1rem;margin:0"><i class="fas fa-landmark"></i> Census <?= (int)$census['census_year'] ?></h3>
                    <span style="color:#64748b;font-size:.8rem"><?= date('M j, Y', strtotime($census['completed_at'])) ?></span>
                </div>
                <div style="display:flex;gap:1.5rem;margin-top:.75rem;flex-wrap:wrap">
                    <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700;color:#8b5cf6"><?= (int)$census['total_population'] ?></div><div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase">Population</div></div>
                    <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700;color:#22c55e"><?= (int)$census['active_military'] ?></div><div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase">Military</div></div>
                    <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700;color:#3b82f6"><?= (int)$census['active_civilian'] ?></div><div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase">Civilian</div></div>
                    <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700;color:#f59e0b"><?= number_format($census['avg_xp'], 0) ?></div><div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase">Avg XP</div></div>
                </div>
                <?php
                $demo = json_decode($census['demographics'] ?? '{}', true);
                if ($demo): ?>
                    <div style="margin-top:.75rem;border-top:1px solid #2a2a4a;padding-top:.5rem;font-size:.8rem;color:#94a3b8;display:flex;gap:1rem;flex-wrap:wrap">
                        <?php if (isset($demo['rights_full'])): ?><span>Full Rights: <?= $demo['rights_full'] ?></span><?php endif; ?>
                        <?php if (isset($demo['rights_restricted'])): ?><span>Restricted: <?= $demo['rights_restricted'] ?></span><?php endif; ?>
                        <?php if (isset($demo['rights_suspended'])): ?><span>Suspended: <?= $demo['rights_suspended'] ?></span><?php endif; ?>
                        <?php if (isset($demo['exiled'])): ?><span>Exiled: <?= $demo['exiled'] ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <div style="color:#64748b;font-size:.7rem;margin-top:.5rem">Conducted by <?= htmlspecialchars($census['conductor_name'] ?? 'Unknown') ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($censuses)): ?>
            <div class="ca-card" style="text-align:center;color:#64748b"><i class="fas fa-chart-bar" style="font-size:2rem;margin-bottom:.5rem"></i><p>No census conducted yet.</p></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- ═══ Modal: File Complaint ═══ -->
<div class="ca-modal-bg" id="modalComplaint">
<div class="ca-modal">
    <h3><i class="fas fa-envelope-open-text"></i> File Complaint</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="file_complaint">
        <div class="ca-form-row"><label class="ca-label">Complaint Type</label><select name="complaint_type" class="ca-select"><option value="grievance">Grievance</option><option value="service">Service Issue</option><option value="conduct">Conduct Report</option><option value="suggestion">Suggestion</option></select></div>
        <div class="ca-form-row"><label class="ca-label">Target Type</label><select name="target_type" class="ca-select"><option value="system">System / Platform</option><option value="department">Department</option><option value="person">Individual</option></select></div>
        <div class="ca-form-row"><label class="ca-label">Target (name/ID)</label><input type="text" name="target_id" class="ca-input" placeholder="e.g., Treasury, client_id 42, War Room"></div>
        <div class="ca-form-row"><label class="ca-label">Description</label><textarea name="complaint_desc" class="ca-textarea" required placeholder="Describe your complaint, grievance, or suggestion in detail..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="ca-btn ca-btn-outline" onclick="this.closest('.ca-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="ca-btn"><i class="fas fa-paper-plane"></i> File Complaint</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Authorize Aid ═══ -->
<div class="ca-modal-bg" id="modalAid">
<div class="ca-modal">
    <h3><i class="fas fa-hand-holding-heart"></i> Authorize Humanitarian Aid</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="authorize_aid">
        <div class="ca-form-row"><label class="ca-label">Aid Type</label><select name="aid_type" class="ca-select"><option value="resource">Resource Distribution</option><option value="credit">Credit Assistance</option><option value="access">Access Restoration</option><option value="protection">Protective Service</option></select></div>
        <div class="ca-form-row"><label class="ca-label">Description</label><textarea name="aid_desc" class="ca-textarea" required placeholder="Describe the humanitarian aid operation..."></textarea></div>
        <div class="ca-form-row"><label class="ca-label">Quantity (units available)</label><input type="number" name="aid_qty" class="ca-input" min="0" value="0"></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="ca-btn ca-btn-outline" onclick="this.closest('.ca-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="ca-btn ca-btn-green"><i class="fas fa-check-circle"></i> Authorize</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Resolve Complaint ═══ -->
<div class="ca-modal-bg" id="modalResolve">
<div class="ca-modal">
    <h3><i class="fas fa-check-circle"></i> Resolve Complaint</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="resolve_complaint">
        <input type="hidden" name="complaint_id" id="resolveCompId" value="">
        <div style="color:#94a3b8;font-size:.85rem;margin-bottom:1rem">Case: <strong id="resolveCompCode" style="color:#8b5cf6"></strong></div>
        <div class="ca-form-row"><label class="ca-label">Decision</label><select name="resolve_status" class="ca-select"><option value="resolved">Resolved</option><option value="dismissed">Dismissed</option></select></div>
        <div class="ca-form-row"><label class="ca-label">Resolution</label><textarea name="resolution" class="ca-textarea" required placeholder="Document the resolution or reason for dismissal..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="ca-btn ca-btn-outline" onclick="this.closest('.ca-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="ca-btn ca-btn-green"><i class="fas fa-stamp"></i> Submit Resolution</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Update Citizen ═══ -->
<div class="ca-modal-bg" id="modalCitizen">
<div class="ca-modal">
    <h3><i class="fas fa-user-edit"></i> Update Citizen Record</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_citizen">
        <input type="hidden" name="target_client_id" id="citClientId" value="">
        <div style="color:#94a3b8;font-size:.85rem;margin-bottom:1rem">Citizen: <strong id="citName" style="color:#8b5cf6"></strong></div>
        <div class="ca-form-row"><label class="ca-label">Status</label><select name="cit_status" id="citStatus" class="ca-select"><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option><option value="exiled">Exiled</option></select></div>
        <div class="ca-form-row"><label class="ca-label">Civil Rights Level</label><select name="cit_rights" id="citRights" class="ca-select"><option value="full">Full</option><option value="restricted">Restricted</option><option value="suspended">Suspended</option><option value="revoked">Revoked</option></select></div>
        <div class="ca-form-row"><label class="ca-label">Home Department</label><input type="text" name="cit_dept" id="citDept" class="ca-input" placeholder="e.g., Defense, Intelligence"></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="ca-btn ca-btn-outline" onclick="this.closest('.ca-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="ca-btn"><i class="fas fa-save"></i> Update</button>
        </div>
    </form>
</div>
</div>

<script>
function openResolveModal(compId, code) {
    document.getElementById('resolveCompId').value = compId;
    document.getElementById('resolveCompCode').textContent = code;
    document.getElementById('modalResolve').classList.add('open');
}
function openCitizenModal(cid, name, status, rights) {
    document.getElementById('citClientId').value = cid;
    document.getElementById('citName').textContent = name;
    document.getElementById('citStatus').value = status;
    document.getElementById('citRights').value = rights;
    document.getElementById('modalCitizen').classList.add('open');
}
document.querySelectorAll('.ca-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
