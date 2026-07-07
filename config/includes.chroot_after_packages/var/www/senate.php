<?php
/**
 * ═══════════════════════════════════════════
 *  Senate & Legislative Assembly — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_senate'])) $_SESSION['csrf_senate'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS senate_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seat_type ENUM('department','at_large') DEFAULT 'at_large',
    department_name VARCHAR(255) DEFAULT NULL,
    client_id INT DEFAULT NULL,
    title VARCHAR(255) DEFAULT 'Senator',
    status ENUM('active','vacant','suspended') DEFAULT 'vacant',
    appointed_by INT DEFAULT NULL,
    seated_at TIMESTAMP NULL,
    term_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS senate_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_type ENUM('regular','emergency','special') DEFAULT 'regular',
    title VARCHAR(255) NOT NULL,
    called_by INT NOT NULL,
    status ENUM('scheduled','in_session','adjourned','cancelled') DEFAULT 'scheduled',
    scheduled_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS senate_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_code VARCHAR(30) NOT NULL,
    bill_type ENUM('statute','resolution','ordinance','declaration','emergency_act') DEFAULT 'statute',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    sponsor_id INT NOT NULL,
    co_sponsors JSON DEFAULT NULL,
    status ENUM('drafted','introduced','committee','debate','vote','passed','rejected','ratified','vetoed') DEFAULT 'drafted',
    committee_id INT DEFAULT NULL,
    debate_start TIMESTAMP NULL,
    debate_end TIMESTAMP NULL,
    vote_start TIMESTAMP NULL,
    vote_end TIMESTAMP NULL,
    votes_yea INT DEFAULT 0,
    votes_nay INT DEFAULT 0,
    votes_abstain INT DEFAULT 0,
    ratified_at TIMESTAMP NULL,
    vetoed_at TIMESTAMP NULL,
    veto_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS senate_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    senator_id INT NOT NULL,
    vote ENUM('yea','nay','abstain') NOT NULL,
    cast_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bill_vote (bill_id, senator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS senate_committees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    chair_id INT DEFAULT NULL,
    members JSON DEFAULT NULL,
    jurisdiction TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed committees if empty ──
$cmtCount = (int)$db->query("SELECT COUNT(*) FROM senate_committees")->fetchColumn();
if ($cmtCount === 0) {
    $committees = [
        ['Armed Services', 'Oversees military operations, defense policy, and service branch matters.', null, null, 'Military affairs, defense spending, service operations, war declarations'],
        ['Appropriations', 'Controls allocation of treasury funds to departments and programs.', null, null, 'Budget approvals, spending bills, emergency funding, departmental allocations'],
        ['Judiciary', 'Reviews legislation affecting constitutional rights, military justice, and legal frameworks.', null, null, 'Constitutional amendments, JAG oversight, military law, rights protection'],
        ['Intelligence', 'Oversight of intelligence operations, SIGINT, and counter-intelligence.', null, null, 'Intelligence budgets, covert operations oversight, OPSEC policy'],
        ['Commerce & Infrastructure', 'Manages economic policy, trade, energy, and infrastructure investment.', null, null, 'Economic indicators, trade policy, infrastructure spending, energy regulation'],
    ];
    $ins = $db->prepare("INSERT INTO senate_committees (name, description, chair_id, members, jurisdiction) VALUES (?,?,?,?,?)");
    foreach ($committees as $c) $ins->execute($c);
}

$csrf = $_SESSION['csrf_senate'];

// ── Check if current user is a seated senator ──
$seatCheck = $db->prepare("SELECT id FROM senate_seats WHERE client_id = ? AND status = 'active'");
$seatCheck->execute([$clientId]);
$isSenator = (bool)$seatCheck->fetch();

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'draft_bill' && ($isSenator || $isCommander)) {
            $bType   = $_POST['bill_type'] ?? 'statute';
            $bTitle  = trim($_POST['bill_title'] ?? '');
            $bContent = trim($_POST['bill_content'] ?? '');
            $validTypes = ['statute','resolution','ordinance','declaration','emergency_act'];
            if ($bTitle === '' || $bContent === '' || !in_array($bType, $validTypes, true)) {
                $msg = 'Title, content, and valid bill type required.'; $msgType = 'error';
            } else {
                $year = date('Y');
                $cnt  = (int)$db->query("SELECT COUNT(*)+1 FROM senate_bills WHERE bill_code LIKE 'SB-$year-%'")->fetchColumn();
                $code = "SB-$year-" . str_pad($cnt, 4, '0', STR_PAD_LEFT);
                $stmt = $db->prepare("INSERT INTO senate_bills (bill_code, bill_type, title, content, sponsor_id, status) VALUES (?,?,?,?,?,'drafted')");
                $stmt->execute([$code, $bType, $bTitle, $bContent, $clientId]);
                awardXP($clientId, 'bill_drafted', ['code' => $code]);
                $msg = "Bill <strong>$code</strong> drafted successfully."; $msgType = 'success';
            }
        } elseif ($action === 'introduce_bill' && ($isSenator || $isCommander)) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $stmt = $db->prepare("UPDATE senate_bills SET status = 'introduced' WHERE id = ? AND sponsor_id = ? AND status = 'drafted'");
            $stmt->execute([$billId, $clientId]);
            if (!$stmt->rowCount() && $isCommander) {
                $stmt = $db->prepare("UPDATE senate_bills SET status = 'introduced' WHERE id = ? AND status = 'drafted'");
                $stmt->execute([$billId]);
            }
            $msg = $stmt->rowCount() ? 'Bill introduced to the Senate floor.' : 'Bill not found or already introduced.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'assign_committee' && ($isFlag || $isCommander)) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $cmtId  = (int)($_POST['committee_id'] ?? 0);
            if ($billId < 1 || $cmtId < 1) {
                $msg = 'Invalid bill or committee.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE senate_bills SET status = 'committee', committee_id = ? WHERE id = ? AND status = 'introduced'");
                $stmt->execute([$cmtId, $billId]);
                $msg = $stmt->rowCount() ? 'Bill assigned to committee for review.' : 'Bill not found or not introduced.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'advance_bill' && ($isFlag || $isCommander)) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $debateHours = 48;
            $bill = $db->prepare("SELECT * FROM senate_bills WHERE id = ? AND status = 'committee'");
            $bill->execute([$billId]);
            $billRow = $bill->fetch(PDO::FETCH_ASSOC);
            if (!$billRow) {
                $msg = 'Bill not in committee.'; $msgType = 'error';
            } else {
                if ($billRow['bill_type'] === 'emergency_act') $debateHours = 24;
                $debateStart = date('Y-m-d H:i:s');
                $debateEnd   = date('Y-m-d H:i:s', strtotime("+$debateHours hours"));
                $db->prepare("UPDATE senate_bills SET status = 'debate', debate_start = ?, debate_end = ? WHERE id = ?")->execute([$debateStart, $debateEnd, $billId]);
                $msg = "Bill advanced to floor debate. {$debateHours}-hour debate period begins."; $msgType = 'success';
            }
        } elseif ($action === 'move_to_vote' && ($isFlag || $isCommander)) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $voteStart = date('Y-m-d H:i:s');
            $voteEnd   = date('Y-m-d H:i:s', strtotime('+72 hours'));
            $stmt = $db->prepare("UPDATE senate_bills SET status = 'vote', vote_start = ?, vote_end = ? WHERE id = ? AND status = 'debate'");
            $stmt->execute([$voteStart, $voteEnd, $billId]);
            $msg = $stmt->rowCount() ? 'Bill moved to vote. 72-hour voting window open.' : 'Bill not in debate.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'cast_vote' && ($isSenator || $isCommander)) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $vote   = $_POST['vote'] ?? '';
            $validV = ['yea','nay','abstain'];
            if ($billId < 1 || !in_array($vote, $validV, true)) {
                $msg = 'Invalid vote.'; $msgType = 'error';
            } else {
                $bill = $db->prepare("SELECT * FROM senate_bills WHERE id = ? AND status = 'vote'");
                $bill->execute([$billId]);
                if (!$bill->fetch()) {
                    $msg = 'Bill not in voting phase.'; $msgType = 'error';
                } else {
                    $existing = $db->prepare("SELECT id FROM senate_votes WHERE bill_id = ? AND senator_id = ?");
                    $existing->execute([$billId, $clientId]);
                    if ($existing->fetch()) {
                        $msg = 'You already voted on this bill.'; $msgType = 'error';
                    } else {
                        $db->prepare("INSERT INTO senate_votes (bill_id, senator_id, vote) VALUES (?,?,?)")->execute([$billId, $clientId, $vote]);
                        $col = "votes_$vote";
                        $db->prepare("UPDATE senate_bills SET $col = $col + 1 WHERE id = ?")->execute([$billId]);
                        awardXP($clientId, 'senate_vote', ['bill_id' => $billId]);
                        $msg = "Vote recorded: <strong>" . strtoupper($vote) . "</strong>."; $msgType = 'success';

                        // Auto-tally: check if passed (simple majority of seated senators)
                        $totalSeated = (int)$db->query("SELECT COUNT(*) FROM senate_seats WHERE status = 'active'")->fetchColumn();
                        $quorum = max(1, (int)ceil($totalSeated * 0.6));
                        $bInfo = $db->prepare("SELECT votes_yea, votes_nay, votes_abstain FROM senate_bills WHERE id = ?");
                        $bInfo->execute([$billId]);
                        $bData = $bInfo->fetch(PDO::FETCH_ASSOC);
                        $totalVotes = $bData['votes_yea'] + $bData['votes_nay'] + $bData['votes_abstain'];
                        if ($totalVotes >= $quorum) {
                            if ($bData['votes_yea'] > $bData['votes_nay']) {
                                $db->prepare("UPDATE senate_bills SET status = 'passed' WHERE id = ?")->execute([$billId]);
                                $msg .= ' Bill PASSED — awaiting Commander ratification.';
                            } elseif ($bData['votes_nay'] >= $bData['votes_yea']) {
                                $db->prepare("UPDATE senate_bills SET status = 'rejected' WHERE id = ?")->execute([$billId]);
                                $msg .= ' Bill REJECTED by the Senate.';
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'ratify_bill' && $isCommander) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $stmt = $db->prepare("UPDATE senate_bills SET status = 'ratified', ratified_at = NOW() WHERE id = ? AND status = 'passed'");
            $stmt->execute([$billId]);
            $msg = $stmt->rowCount() ? 'Bill ratified into law by the Commander.' : 'Bill not found or not passed.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'veto_bill' && $isCommander) {
            $billId = (int)($_POST['bill_id'] ?? 0);
            $reason = trim($_POST['veto_reason'] ?? '');
            $stmt = $db->prepare("UPDATE senate_bills SET status = 'vetoed', vetoed_at = NOW(), veto_reason = ? WHERE id = ? AND status = 'passed'");
            $stmt->execute([$reason, $billId]);
            $msg = $stmt->rowCount() ? 'Bill VETOED by the Commander.' : 'Bill not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'call_session' && ($isFlag || $isCommander)) {
            $sType   = $_POST['session_type'] ?? 'regular';
            $sTitle  = trim($_POST['session_title'] ?? '');
            $sDate   = $_POST['session_date'] ?? '';
            $validST = ['regular','emergency','special'];
            if ($sTitle === '' || !in_array($sType, $validST, true)) {
                $msg = 'Session title and type required.'; $msgType = 'error';
            } else {
                $schedAt = !empty($sDate) ? $sDate : date('Y-m-d H:i:s');
                $stmt = $db->prepare("INSERT INTO senate_sessions (session_type, title, called_by, scheduled_at) VALUES (?,?,?,?)");
                $stmt->execute([$sType, $sTitle, $clientId, $schedAt]);
                awardXP($clientId, 'session_called', ['title' => $sTitle]);
                $msg = "Senate session <strong>" . htmlspecialchars($sTitle) . "</strong> called."; $msgType = 'success';
            }
        } elseif ($action === 'start_session' && ($isFlag || $isCommander)) {
            $sessId = (int)($_POST['session_id'] ?? 0);
            $stmt = $db->prepare("UPDATE senate_sessions SET status = 'in_session', started_at = NOW() WHERE id = ? AND status = 'scheduled'");
            $stmt->execute([$sessId]);
            $msg = $stmt->rowCount() ? 'Session opened. The Senate is now in session.' : 'Session not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'adjourn_session' && ($isFlag || $isCommander)) {
            $sessId = (int)($_POST['session_id'] ?? 0);
            $notes  = trim($_POST['session_notes'] ?? '');
            $stmt = $db->prepare("UPDATE senate_sessions SET status = 'adjourned', ended_at = NOW(), notes = ? WHERE id = ? AND status = 'in_session'");
            $stmt->execute([$notes, $sessId]);
            $msg = $stmt->rowCount() ? 'Session adjourned.' : 'Session not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'appoint_senator' && $isCommander) {
            $seatId  = (int)($_POST['seat_id'] ?? 0);
            $appointed = (int)($_POST['appoint_client_id'] ?? 0);
            $seatTitle = trim($_POST['seat_title'] ?? 'Senator');
            if ($seatId < 1 || $appointed < 1) {
                $msg = 'Seat and client ID required.'; $msgType = 'error';
            } else {
                $termExp = date('Y-m-d H:i:s', strtotime('+1 year'));
                $stmt = $db->prepare("UPDATE senate_seats SET client_id = ?, title = ?, status = 'active', appointed_by = ?, seated_at = NOW(), term_expires = ? WHERE id = ? AND status = 'vacant'");
                $stmt->execute([$appointed, $seatTitle, $clientId, $termExp, $seatId]);
                $msg = $stmt->rowCount() ? 'Senator appointed and seated.' : 'Seat not found or not vacant.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'create_seat' && $isCommander) {
            $seatType = $_POST['seat_type_new'] ?? 'at_large';
            $deptName = trim($_POST['dept_name'] ?? '');
            $validSeat = ['department','at_large'];
            if (!in_array($seatType, $validSeat, true)) {
                $msg = 'Invalid seat type.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO senate_seats (seat_type, department_name) VALUES (?,?)");
                $stmt->execute([$seatType, $deptName ?: null]);
                $msg = "New senate seat created" . ($deptName ? " for $deptName" : "") . "."; $msgType = 'success';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_senate'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_senate'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'floor';
$seats   = $db->query("SELECT ss.*, CONCAT(c.firstname,' ',c.lastname) AS senator_name FROM senate_seats ss LEFT JOIN tblclients c ON c.id = ss.client_id ORDER BY ss.seat_type, ss.id")->fetchAll(PDO::FETCH_ASSOC);
$bills   = $db->query("SELECT sb.*, CONCAT(c.firstname,' ',c.lastname) AS sponsor_name FROM senate_bills sb LEFT JOIN tblclients c ON c.id = sb.sponsor_id ORDER BY sb.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$sessions = $db->query("SELECT ss.*, CONCAT(c.firstname,' ',c.lastname) AS caller_name FROM senate_sessions ss LEFT JOIN tblclients c ON c.id = ss.called_by ORDER BY ss.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$committees = $db->query("SELECT sc.*, CONCAT(c.firstname,' ',c.lastname) AS chair_name FROM senate_committees sc LEFT JOIN tblclients c ON c.id = sc.chair_id ORDER BY sc.name")->fetchAll(PDO::FETCH_ASSOC);
$myVotes = [];
$v = $db->prepare("SELECT bill_id, vote FROM senate_votes WHERE senator_id = ?");
$v->execute([$clientId]);
foreach ($v->fetchAll(PDO::FETCH_ASSOC) as $row) $myVotes[$row['bill_id']] = $row['vote'];
$totalSeats = count($seats);
$filledSeats = count(array_filter($seats, fn($s) => $s['status'] === 'active'));
$activeSessions = count(array_filter($sessions, fn($s) => $s['status'] === 'in_session'));

$pageTitle = 'Senate & Legislative Assembly';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.sn-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.sn-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.sn-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.sn-card:hover{border-color:#3b82f6;box-shadow:0 0 12px rgba(59,130,246,.12)}
.sn-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.sn-sub{color:#94a3b8;font-size:.85rem}
.sn-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.sn-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.sn-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.sn-tab.active{background:#3b82f6;color:#fff}
.sn-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.sn-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:130px;text-align:center}
.sn-stat .val{font-size:1.5rem;font-weight:700;color:#3b82f6}
.sn-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.sn-btn{background:#3b82f6;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.sn-btn:hover{background:#2563eb}
.sn-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.sn-btn-outline{background:transparent;border:1px solid #3b82f6;color:#3b82f6}
.sn-btn-outline:hover{background:#3b82f6;color:#fff}
.sn-btn-gold{background:#d4a017;color:#000}.sn-btn-gold:hover{background:#e2b340}
.sn-btn-green{background:#22c55e;color:#fff}.sn-btn-green:hover{background:#16a34a}
.sn-btn-red{background:#ef4444;color:#fff}.sn-btn-red:hover{background:#dc2626}
.sn-input,.sn-select,.sn-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.sn-textarea{min-height:120px;resize:vertical}
.sn-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.sn-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.sn-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.sn-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.sn-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.sn-modal-bg.open{display:flex}
.sn-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.sn-modal h3{color:#f1f5f9;margin:0 0 1rem}
.sn-form-row{margin-bottom:.75rem}
.sn-seat{display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:#0a0a14;border:1px solid #2a2a4a;border-radius:8px;margin-bottom:.5rem}
.sn-seat.active{border-color:#22c55e40}
.sn-seat.vacant{border-color:#f59e0b40;opacity:.7}
.sn-bill{padding:1rem 1.25rem;border-radius:8px;border:1px solid #2a2a4a;background:#1a1a2e;margin-bottom:.75rem}
.sn-pipeline{display:flex;gap:.25rem;margin-top:.5rem;flex-wrap:wrap}
.sn-pipeline .step{padding:2px 8px;font-size:.65rem;border-radius:4px;background:#2a2a4a;color:#64748b}
.sn-pipeline .step.active{background:#3b82f6;color:#fff}
.sn-pipeline .step.done{background:#22c55e30;color:#22c55e}
</style>
<div class="sn-bg">
<div class="sn-wrap">
    <div class="sn-title"><i class="fas fa-landmark-dome"></i> Senate &amp; Legislative Assembly</div>
    <p class="sn-sub" style="margin-bottom:1.25rem">The legislative body of the GoSiteMe sovereign state — Officer rank and above</p>

    <?php if ($msg): ?>
        <div class="sn-msg sn-msg-<?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="sn-stat-bar">
        <div class="sn-stat"><div class="val"><?= $filledSeats ?>/<?= $totalSeats ?></div><div class="lbl">Seated Senators</div></div>
        <div class="sn-stat"><div class="val"><?= count($bills) ?></div><div class="lbl">Total Bills</div></div>
        <div class="sn-stat"><div class="val"><?= $activeSessions ?></div><div class="lbl">Active Sessions</div></div>
        <div class="sn-stat"><div class="val"><?= count($committees) ?></div><div class="lbl">Committees</div></div>
    </div>

    <!-- Tabs -->
    <div class="sn-tabs">
        <a href="?tab=floor" class="sn-tab <?= $tab==='floor'?'active':'' ?>"><i class="fas fa-scroll"></i> Senate Floor</a>
        <a href="?tab=bills" class="sn-tab <?= $tab==='bills'?'active':'' ?>"><i class="fas fa-file-alt"></i> Bills</a>
        <a href="?tab=seats" class="sn-tab <?= $tab==='seats'?'active':'' ?>"><i class="fas fa-chair"></i> Seats</a>
        <a href="?tab=committees" class="sn-tab <?= $tab==='committees'?'active':'' ?>"><i class="fas fa-users-rectangle"></i> Committees</a>
        <a href="?tab=sessions" class="sn-tab <?= $tab==='sessions'?'active':'' ?>"><i class="fas fa-calendar"></i> Sessions</a>
    </div>

    <!-- ═══ TAB: SENATE FLOOR ═══ -->
    <?php if ($tab === 'floor'): ?>
        <div class="sn-card" style="border-left:4px solid #3b82f6">
            <h3 style="color:#3b82f6;margin-bottom:.5rem"><i class="fas fa-landmark-dome"></i> Senate Floor Status</h3>
            <?php
            $activeSession = null;
            foreach ($sessions as $s) { if ($s['status'] === 'in_session') { $activeSession = $s; break; } }
            if ($activeSession): ?>
                <div style="color:#22c55e;font-size:.9rem"><i class="fas fa-circle" style="font-size:.5rem;vertical-align:middle"></i> <strong>IN SESSION:</strong> <?= htmlspecialchars($activeSession['title']) ?></div>
                <div style="color:#64748b;font-size:.8rem;margin-top:.25rem">Called by <?= htmlspecialchars($activeSession['caller_name'] ?? 'Unknown') ?> &bull; Started <?= date('M j, Y H:i', strtotime($activeSession['started_at'])) ?></div>
                <?php if ($isFlag || $isCommander): ?>
                    <form method="POST" style="margin-top:.5rem;display:inline">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="adjourn_session"><input type="hidden" name="session_id" value="<?= $activeSession['id'] ?>">
                        <input type="text" name="session_notes" class="sn-input" style="width:60%;display:inline-block" placeholder="Session notes...">
                        <button class="sn-btn sn-btn-sm sn-btn-red"><i class="fas fa-gavel"></i> Adjourn</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <div style="color:#64748b;font-size:.9rem"><i class="fas fa-moon" style="font-size:.5rem;vertical-align:middle"></i> Senate is not in session.</div>
                <?php if ($isFlag || $isCommander): ?>
                    <button class="sn-btn sn-btn-sm" style="margin-top:.5rem" onclick="document.getElementById('modalSession').classList.add('open')"><i class="fas fa-gavel"></i> Call Session</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Active Bills on Floor -->
        <h3 style="color:#f1f5f9;font-size:1.1rem;margin:1.25rem 0 .75rem"><i class="fas fa-fire"></i> Active Legislation</h3>
        <?php
        $activeBills = array_filter($bills, fn($b) => in_array($b['status'], ['introduced','committee','debate','vote','passed']));
        $statusSteps = ['drafted','introduced','committee','debate','vote','passed','ratified'];
        foreach ($activeBills as $bill):
            $statusColors = ['introduced'=>'#3b82f6','committee'=>'#8b5cf6','debate'=>'#f59e0b','vote'=>'#06b6d4','passed'=>'#22c55e','ratified'=>'#d4a017','vetoed'=>'#ef4444','rejected'=>'#64748b'];
        ?>
            <div class="sn-bill">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><strong style="color:#3b82f6;font-size:.8rem"><?= htmlspecialchars($bill['bill_code']) ?></strong> <span style="color:#f1f5f9"><?= htmlspecialchars($bill['title']) ?></span></div>
                    <span class="sn-badge" style="background:<?= $statusColors[$bill['status']] ?? '#64748b' ?>20;color:<?= $statusColors[$bill['status']] ?? '#64748b' ?>;border:1px solid <?= $statusColors[$bill['status']] ?? '#64748b' ?>40"><?= strtoupper($bill['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.75rem;margin-top:.25rem">
                    <span class="sn-badge" style="background:#2a2a4a;color:#94a3b8"><?= strtoupper($bill['bill_type']) ?></span>
                    Sponsor: <?= htmlspecialchars($bill['sponsor_name'] ?? 'Unknown') ?> &bull; <?= date('M j, Y', strtotime($bill['created_at'])) ?>
                </div>
                <div class="sn-pipeline">
                    <?php foreach ($statusSteps as $step):
                        $idx = array_search($step, $statusSteps);
                        $curIdx = array_search($bill['status'], $statusSteps);
                        $cls = ($step === $bill['status']) ? 'active' : ($idx < $curIdx ? 'done' : '');
                    ?>
                        <span class="step <?= $cls ?>"><?= ucfirst($step) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($bill['status'] === 'vote'): ?>
                    <div style="display:flex;gap:1rem;margin-top:.5rem;font-size:.8rem">
                        <span style="color:#22c55e"><i class="fas fa-check"></i> <?= (int)$bill['votes_yea'] ?> Yea</span>
                        <span style="color:#ef4444"><i class="fas fa-times"></i> <?= (int)$bill['votes_nay'] ?> Nay</span>
                        <span style="color:#94a3b8"><i class="fas fa-minus"></i> <?= (int)$bill['votes_abstain'] ?> Abstain</span>
                    </div>
                    <?php if (($isSenator || $isCommander) && !isset($myVotes[$bill['id']])): ?>
                        <div style="margin-top:.5rem;display:flex;gap:.5rem">
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="cast_vote"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><input type="hidden" name="vote" value="yea"><button class="sn-btn-sm sn-btn sn-btn-green">Vote YEA</button></form>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="cast_vote"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><input type="hidden" name="vote" value="nay"><button class="sn-btn-sm sn-btn sn-btn-red">Vote NAY</button></form>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="cast_vote"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><input type="hidden" name="vote" value="abstain"><button class="sn-btn-sm sn-btn sn-btn-outline">ABSTAIN</button></form>
                        </div>
                    <?php elseif (isset($myVotes[$bill['id']])): ?>
                        <div style="color:#64748b;font-size:.8rem;margin-top:.35rem"><i class="fas fa-check-circle"></i> You voted: <strong><?= strtoupper($myVotes[$bill['id']]) ?></strong></div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Action buttons for bill progression -->
                <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <?php if ($bill['status'] === 'introduced' && ($isFlag || $isCommander)): ?>
                        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="assign_committee"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                            <select name="committee_id" class="sn-select" style="width:auto;display:inline-block">
                                <?php foreach ($committees as $cm): ?><option value="<?= $cm['id'] ?>"><?= htmlspecialchars($cm['name']) ?></option><?php endforeach; ?>
                            </select>
                            <button class="sn-btn-sm sn-btn" style="background:#8b5cf6"><i class="fas fa-building-columns"></i> Assign Committee</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($bill['status'] === 'committee' && ($isFlag || $isCommander)): ?>
                        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="advance_bill"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><button class="sn-btn-sm sn-btn" style="background:#f59e0b;color:#000"><i class="fas fa-arrow-right"></i> Advance to Debate</button></form>
                    <?php endif; ?>
                    <?php if ($bill['status'] === 'debate' && ($isFlag || $isCommander)): ?>
                        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="move_to_vote"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><button class="sn-btn-sm sn-btn" style="background:#06b6d4"><i class="fas fa-vote-yea"></i> Move to Vote</button></form>
                    <?php endif; ?>
                    <?php if ($bill['status'] === 'passed' && $isCommander): ?>
                        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="ratify_bill"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><button class="sn-btn-sm sn-btn sn-btn-gold"><i class="fas fa-stamp"></i> Ratify</button></form>
                        <button class="sn-btn-sm sn-btn sn-btn-red" onclick="openVetoModal(<?= $bill['id'] ?>,'<?= htmlspecialchars($bill['bill_code'], ENT_QUOTES) ?>')"><i class="fas fa-ban"></i> Veto</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($activeBills)): ?>
            <div class="sn-card" style="text-align:center;color:#64748b"><i class="fas fa-inbox" style="font-size:2rem;margin-bottom:.5rem"></i><p>No active legislation on the floor.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: BILLS ═══ -->
    <?php elseif ($tab === 'bills'): ?>
        <?php if ($isSenator || $isCommander): ?>
            <div style="margin-bottom:1rem"><button class="sn-btn" onclick="document.getElementById('modalBill').classList.add('open')"><i class="fas fa-file-circle-plus"></i> Draft New Bill</button></div>
        <?php endif; ?>
        <?php
        $statusColors = ['drafted'=>'#64748b','introduced'=>'#3b82f6','committee'=>'#8b5cf6','debate'=>'#f59e0b','vote'=>'#06b6d4','passed'=>'#22c55e','rejected'=>'#ef4444','ratified'=>'#d4a017','vetoed'=>'#ef4444'];
        foreach ($bills as $bill): ?>
            <div class="sn-bill">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><strong style="color:#3b82f6;font-size:.8rem"><?= htmlspecialchars($bill['bill_code']) ?></strong> <span style="color:#f1f5f9"><?= htmlspecialchars($bill['title']) ?></span></div>
                    <span class="sn-badge" style="background:<?= $statusColors[$bill['status']] ?? '#64748b' ?>20;color:<?= $statusColors[$bill['status']] ?? '#64748b' ?>;border:1px solid <?= $statusColors[$bill['status']] ?? '#64748b' ?>40"><?= strtoupper($bill['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.75rem;margin-top:.25rem">
                    <span class="sn-badge" style="background:#2a2a4a;color:#94a3b8"><?= strtoupper($bill['bill_type']) ?></span>
                    Sponsor: <?= htmlspecialchars($bill['sponsor_name'] ?? 'Unknown') ?> &bull; <?= date('M j, Y', strtotime($bill['created_at'])) ?>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem;white-space:pre-wrap"><?= htmlspecialchars(mb_substr($bill['content'], 0, 400)) ?><?= mb_strlen($bill['content']) > 400 ? '...' : '' ?></p>
                <?php if ($bill['status'] === 'drafted' && ($bill['sponsor_id'] == $clientId || $isCommander)): ?>
                    <form method="POST" style="margin-top:.5rem;display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="introduce_bill"><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><button class="sn-btn-sm sn-btn"><i class="fas fa-paper-plane"></i> Introduce to Floor</button></form>
                <?php endif; ?>
                <?php if ($bill['veto_reason']): ?>
                    <div style="margin-top:.5rem;padding:.5rem;background:rgba(239,68,68,.08);border-radius:6px;border:1px solid #ef444440;font-size:.8rem;color:#fca5a5"><strong>Veto Reason:</strong> <?= htmlspecialchars($bill['veto_reason']) ?></div>
                <?php endif; ?>
                <div style="display:flex;gap:1rem;margin-top:.5rem;font-size:.8rem">
                    <span style="color:#22c55e"><i class="fas fa-check"></i> <?= (int)$bill['votes_yea'] ?></span>
                    <span style="color:#ef4444"><i class="fas fa-times"></i> <?= (int)$bill['votes_nay'] ?></span>
                    <span style="color:#94a3b8"><i class="fas fa-minus"></i> <?= (int)$bill['votes_abstain'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($bills)): ?>
            <div class="sn-card" style="text-align:center;color:#64748b"><i class="fas fa-file-alt" style="font-size:2rem;margin-bottom:.5rem"></i><p>No legislation has been drafted.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: SEATS ═══ -->
    <?php elseif ($tab === 'seats'): ?>
        <?php if ($isCommander): ?>
            <div style="margin-bottom:1rem"><button class="sn-btn sn-btn-gold" onclick="document.getElementById('modalSeat').classList.add('open')"><i class="fas fa-plus"></i> Create Seat</button></div>
        <?php endif; ?>
        <h3 style="color:#f1f5f9;font-size:1rem;margin-bottom:.75rem">Senate Seats (<?= $filledSeats ?>/<?= $totalSeats ?> filled)</h3>
        <?php foreach ($seats as $seat): ?>
            <div class="sn-seat <?= $seat['status'] ?>">
                <div style="flex:0 0 40px;text-align:center">
                    <?php if ($seat['status'] === 'active'): ?>
                        <i class="fas fa-user-tie" style="font-size:1.5rem;color:#22c55e"></i>
                    <?php else: ?>
                        <i class="fas fa-chair" style="font-size:1.5rem;color:#f59e0b50"></i>
                    <?php endif; ?>
                </div>
                <div style="flex:1">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <strong style="color:#f1f5f9"><?= $seat['status'] === 'active' ? htmlspecialchars($seat['senator_name'] ?? 'Unknown') : 'VACANT' ?></strong>
                        <span class="sn-badge" style="background:<?= $seat['seat_type']==='department'?'#8b5cf620':'#3b82f620' ?>;color:<?= $seat['seat_type']==='department'?'#8b5cf6':'#3b82f6' ?>;border:1px solid <?= $seat['seat_type']==='department'?'#8b5cf640':'#3b82f640' ?>"><?= strtoupper($seat['seat_type']) ?></span>
                    </div>
                    <div style="color:#94a3b8;font-size:.8rem">
                        <?= $seat['title'] ?? 'Senator' ?>
                        <?php if ($seat['department_name']): ?> &bull; <?= htmlspecialchars($seat['department_name']) ?><?php endif; ?>
                        <?php if ($seat['term_expires']): ?> &bull; Term expires: <?= date('M j, Y', strtotime($seat['term_expires'])) ?><?php endif; ?>
                    </div>
                </div>
                <?php if ($seat['status'] === 'vacant' && $isCommander): ?>
                    <form method="POST" style="display:flex;gap:.25rem;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="appoint_senator"><input type="hidden" name="seat_id" value="<?= $seat['id'] ?>">
                        <input type="number" name="appoint_client_id" class="sn-input" style="width:80px" placeholder="CID" required>
                        <button class="sn-btn-sm sn-btn sn-btn-gold"><i class="fas fa-user-plus"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($seats)): ?>
            <div class="sn-card" style="text-align:center;color:#64748b"><i class="fas fa-chair" style="font-size:2rem;margin-bottom:.5rem"></i><p>No senate seats created.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: COMMITTEES ═══ -->
    <?php elseif ($tab === 'committees'): ?>
        <?php foreach ($committees as $cm): ?>
            <div class="sn-card">
                <h3 style="color:#8b5cf6;font-size:1rem;margin-bottom:.25rem"><i class="fas fa-building-columns"></i> <?= htmlspecialchars($cm['name']) ?></h3>
                <p style="color:#94a3b8;font-size:.85rem;margin-bottom:.5rem"><?= htmlspecialchars($cm['description'] ?? '') ?></p>
                <div style="font-size:.8rem;color:#64748b"><strong>Chair:</strong> <?= htmlspecialchars($cm['chair_name'] ?? 'Vacant') ?></div>
                <?php if ($cm['jurisdiction']): ?>
                    <div style="font-size:.8rem;color:#64748b;margin-top:.25rem"><strong>Jurisdiction:</strong> <?= htmlspecialchars($cm['jurisdiction']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: SESSIONS ═══ -->
    <?php elseif ($tab === 'sessions'): ?>
        <?php if ($isFlag || $isCommander): ?>
            <div style="margin-bottom:1rem"><button class="sn-btn" onclick="document.getElementById('modalSession').classList.add('open')"><i class="fas fa-gavel"></i> Call Session</button></div>
        <?php endif; ?>
        <?php
        $sessColors = ['scheduled'=>'#f59e0b','in_session'=>'#22c55e','adjourned'=>'#64748b','cancelled'=>'#ef4444'];
        foreach ($sessions as $sess): ?>
            <div class="sn-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#f1f5f9"><?= htmlspecialchars($sess['title']) ?></strong>
                        <span class="sn-badge" style="background:#2a2a4a;color:#94a3b8;margin-left:.5rem"><?= strtoupper($sess['session_type']) ?></span>
                    </div>
                    <span class="sn-badge" style="background:<?= $sessColors[$sess['status']] ?>20;color:<?= $sessColors[$sess['status']] ?>;border:1px solid <?= $sessColors[$sess['status']] ?>40"><?= strtoupper($sess['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Called by <?= htmlspecialchars($sess['caller_name'] ?? 'Unknown') ?>
                    &bull; Scheduled: <?= date('M j, Y H:i', strtotime($sess['scheduled_at'])) ?>
                    <?php if ($sess['started_at']): ?>&bull; Started: <?= date('M j H:i', strtotime($sess['started_at'])) ?><?php endif; ?>
                    <?php if ($sess['ended_at']): ?>&bull; Adjourned: <?= date('M j H:i', strtotime($sess['ended_at'])) ?><?php endif; ?>
                </div>
                <?php if ($sess['notes']): ?><p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($sess['notes']) ?></p><?php endif; ?>
                <?php if ($sess['status'] === 'scheduled' && ($isFlag || $isCommander)): ?>
                    <form method="POST" style="margin-top:.5rem;display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="start_session"><input type="hidden" name="session_id" value="<?= $sess['id'] ?>"><button class="sn-btn-sm sn-btn sn-btn-green"><i class="fas fa-play"></i> Open Session</button></form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?>
            <div class="sn-card" style="text-align:center;color:#64748b"><i class="fas fa-calendar" style="font-size:2rem;margin-bottom:.5rem"></i><p>No sessions called.</p></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- ═══ Modal: Draft Bill ═══ -->
<div class="sn-modal-bg" id="modalBill">
<div class="sn-modal">
    <h3><i class="fas fa-file-circle-plus"></i> Draft New Legislation</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="draft_bill">
        <div class="sn-form-row"><label class="sn-label">Bill Type</label>
            <select name="bill_type" class="sn-select">
                <option value="statute">Statute</option>
                <option value="resolution">Resolution</option>
                <option value="ordinance">Ordinance</option>
                <option value="declaration">Declaration</option>
                <option value="emergency_act">Emergency Act (24h debate)</option>
            </select>
        </div>
        <div class="sn-form-row"><label class="sn-label">Bill Title</label><input type="text" name="bill_title" class="sn-input" required></div>
        <div class="sn-form-row"><label class="sn-label">Bill Content</label><textarea name="bill_content" class="sn-textarea" required placeholder="Full text of the proposed legislation..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="sn-btn sn-btn-outline" onclick="this.closest('.sn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="sn-btn"><i class="fas fa-file-circle-plus"></i> Draft Bill</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Call Session ═══ -->
<div class="sn-modal-bg" id="modalSession">
<div class="sn-modal">
    <h3><i class="fas fa-gavel"></i> Call Senate Session</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="call_session">
        <div class="sn-form-row"><label class="sn-label">Session Type</label>
            <select name="session_type" class="sn-select">
                <option value="regular">Regular Session</option>
                <option value="emergency">Emergency Session</option>
                <option value="special">Special Session</option>
            </select>
        </div>
        <div class="sn-form-row"><label class="sn-label">Session Title</label><input type="text" name="session_title" class="sn-input" required placeholder="e.g., 1st Regular Session — April 2026"></div>
        <div class="sn-form-row"><label class="sn-label">Schedule Date/Time</label><input type="datetime-local" name="session_date" class="sn-input"></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="sn-btn sn-btn-outline" onclick="this.closest('.sn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="sn-btn"><i class="fas fa-gavel"></i> Call Session</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Create Seat ═══ -->
<div class="sn-modal-bg" id="modalSeat">
<div class="sn-modal">
    <h3><i class="fas fa-chair"></i> Create Senate Seat</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="create_seat">
        <div class="sn-form-row"><label class="sn-label">Seat Type</label>
            <select name="seat_type_new" class="sn-select">
                <option value="at_large">At-Large</option>
                <option value="department">Department Representative</option>
            </select>
        </div>
        <div class="sn-form-row"><label class="sn-label">Department Name (if department seat)</label><input type="text" name="dept_name" class="sn-input" placeholder="e.g., Treasury, Defense, Intelligence"></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="sn-btn sn-btn-outline" onclick="this.closest('.sn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="sn-btn sn-btn-gold"><i class="fas fa-plus"></i> Create Seat</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Veto ═══ -->
<div class="sn-modal-bg" id="modalVeto">
<div class="sn-modal">
    <h3><i class="fas fa-ban"></i> Veto Bill</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="veto_bill">
        <input type="hidden" name="bill_id" id="vetoBillId" value="">
        <div style="color:#94a3b8;font-size:.85rem;margin-bottom:1rem">Bill: <strong id="vetoBillCode" style="color:#ef4444"></strong></div>
        <div class="sn-form-row"><label class="sn-label">Veto Reason</label><textarea name="veto_reason" class="sn-textarea" required placeholder="State the Commander's reason for vetoing this legislation."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="sn-btn sn-btn-outline" onclick="this.closest('.sn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="sn-btn sn-btn-red"><i class="fas fa-ban"></i> Veto</button>
        </div>
    </form>
</div>
</div>

<script>
function openVetoModal(billId, code) {
    document.getElementById('vetoBillId').value = billId;
    document.getElementById('vetoBillCode').textContent = code;
    document.getElementById('modalVeto').classList.add('open');
}
document.querySelectorAll('.sn-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
