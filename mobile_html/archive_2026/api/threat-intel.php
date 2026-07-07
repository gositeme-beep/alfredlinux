<?php
/**
 * Threat Intelligence & Accountability API
 * ═════════════════════════════════════════
 * Phase 4.5 — Justice & Threat Intelligence Database
 *
 * Endpoints:
 *   GET  ?action=overview          System-wide threat + justice stats
 *   GET  ?action=threats           List threats (filterable)
 *   GET  ?action=threat_detail     Single threat with IOCs
 *   POST ?action=report_threat     Log a new threat
 *   POST ?action=update_threat     Update threat status/response
 *   GET  ?action=indicators        List IOCs (filterable)
 *   POST ?action=add_indicator     Add IOC to a threat
 *   GET  ?action=ledger            Accountability ledger (filterable)
 *   POST ?action=log_action        Record an accountability action
 *   GET  ?action=blocked           Currently blocked IPs/actors
 *   POST ?action=block_actor       Block an IP/actor
 *   POST ?action=unblock_actor     Unblock an IP/actor
 */
define('GOSITEME_API', true);
require __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

// Auth check
if (session_status() === PHP_SESSION_NONE) session_start();
$isInternal = !empty($_SERVER['HTTP_X_INTERNAL_SECRET'])
    && hash_equals('3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d', $_SERVER['HTTP_X_INTERNAL_SECRET']);

if (!$isInternal && (empty($_SESSION['logged_in']) || empty($_SESSION['client_id']))) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = getDB();
$clientId = (int)($_SESSION['client_id'] ?? 0);
$isAdmin = $clientId === 33 || !empty($_SESSION['is_admin']);

// Helper: generate sequential ID
function generateThreatId($db) {
    $year = date('Y');
    $last = $db->query("SELECT threat_id FROM threat_intelligence WHERE threat_id LIKE 'THR-{$year}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    $num = $last ? ((int)substr($last, -5) + 1) : 1;
    return 'THR-' . $year . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
}

function generateLedgerId($db) {
    $year = date('Y');
    $last = $db->query("SELECT ledger_id FROM accountability_ledger WHERE ledger_id LIKE 'ACT-{$year}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    $num = $last ? ((int)substr($last, -5) + 1) : 1;
    return 'ACT-' . $year . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
}

function jsonOut($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {

// ══════════════════════════════════════════
// OVERVIEW — combined threat + justice stats
// ══════════════════════════════════════════
case 'overview':
    $o = [];

    // Threat stats
    $o['threats'] = [
        'total'       => (int)$db->query("SELECT COUNT(*) FROM threat_intelligence")->fetchColumn(),
        'active'      => (int)$db->query("SELECT COUNT(*) FROM threat_intelligence WHERE status IN ('detected','investigating','confirmed')")->fetchColumn(),
        'mitigated'   => (int)$db->query("SELECT COUNT(*) FROM threat_intelligence WHERE status='mitigated'")->fetchColumn(),
        'resolved'    => (int)$db->query("SELECT COUNT(*) FROM threat_intelligence WHERE status='resolved'")->fetchColumn(),
        'critical'    => (int)$db->query("SELECT COUNT(*) FROM threat_intelligence WHERE severity='critical' AND status NOT IN ('resolved','false_positive')")->fetchColumn(),
    ];

    $o['threats_by_type'] = $db->query("SELECT threat_type, COUNT(*) as cnt FROM threat_intelligence GROUP BY threat_type ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
    $o['threats_by_severity'] = $db->query("SELECT severity, COUNT(*) as cnt FROM threat_intelligence GROUP BY severity ORDER BY FIELD(severity,'critical','high','medium','low','info')")->fetchAll(PDO::FETCH_ASSOC);

    // IOC stats
    $o['indicators'] = [
        'total'  => (int)$db->query("SELECT COUNT(*) FROM threat_indicators")->fetchColumn(),
        'active' => (int)$db->query("SELECT COUNT(*) FROM threat_indicators WHERE is_active=1")->fetchColumn(),
    ];

    // Accountability stats
    $o['accountability'] = [
        'total'   => (int)$db->query("SELECT COUNT(*) FROM accountability_ledger")->fetchColumn(),
        'today'   => (int)$db->query("SELECT COUNT(*) FROM accountability_ledger WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    ];
    $o['accountability_by_type'] = $db->query("SELECT action_type, COUNT(*) as cnt FROM accountability_ledger GROUP BY action_type ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Blocked actors
    $o['blocked_actors'] = (int)$db->query("SELECT COUNT(*) FROM threat_intelligence WHERE response_action='blocked' AND (blocked_until IS NULL OR blocked_until > NOW())")->fetchColumn();

    // Justice system bridge
    $o['justice'] = [
        'infractions' => (int)$db->query("SELECT COUNT(*) FROM agent_infractions")->fetchColumn(),
        'open_cases'  => (int)$db->query("SELECT COUNT(*) FROM agent_court_cases WHERE status NOT IN ('closed','dismissed')")->fetchColumn(),
        'inmates'     => (int)$db->query("SELECT COUNT(*) FROM fleet_passports WHERE citizenship_status='incarcerated'")->fetchColumn(),
        'sentences'   => (int)$db->query("SELECT COUNT(*) FROM agent_sentences WHERE status='active'")->fetchColumn(),
    ];

    // Recent activity
    $o['recent_threats'] = $db->query("SELECT id, threat_id, threat_type, severity, status, title, source_ip, detected_at
        FROM threat_intelligence ORDER BY detected_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    $o['recent_actions'] = $db->query("SELECT id, ledger_id, action_type, severity, actor_name, title, outcome, created_at
        FROM accountability_ledger ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    jsonOut($o);
    break;

// ══════════════════════════════════════════
// LIST THREATS
// ══════════════════════════════════════════
case 'threats':
    $where = [];
    $params = [];

    if (!empty($_GET['type']))     { $where[] = "threat_type = ?";     $params[] = $_GET['type']; }
    if (!empty($_GET['severity'])) { $where[] = "severity = ?";        $params[] = $_GET['severity']; }
    if (!empty($_GET['status']))   { $where[] = "status = ?";          $params[] = $_GET['status']; }
    if (!empty($_GET['ip']))       { $where[] = "source_ip = ?";       $params[] = $_GET['ip']; }
    if (!empty($_GET['search']))   { $where[] = "(title LIKE ? OR description LIKE ?)"; $s = '%'.$_GET['search'].'%'; $params[] = $s; $params[] = $s; }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $total = (int)$db->prepare("SELECT COUNT(*) FROM threat_intelligence $whereSQL");
    $countStmt = $db->prepare("SELECT COUNT(*) FROM threat_intelligence $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT * FROM threat_intelligence $whereSQL ORDER BY detected_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonOut(['threats' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page]);
    break;

// ══════════════════════════════════════════
// THREAT DETAIL
// ══════════════════════════════════════════
case 'threat_detail':
    $id = (int)($_GET['id'] ?? 0);
    $tid = $_GET['threat_id'] ?? '';
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM threat_intelligence WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($tid) {
        $stmt = $db->prepare("SELECT * FROM threat_intelligence WHERE threat_id = ?");
        $stmt->execute([$tid]);
    } else {
        jsonOut(['error' => 'id or threat_id required'], 400);
    }

    $threat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$threat) { jsonOut(['error' => 'Threat not found'], 404); }

    // Get IOCs
    $iocStmt = $db->prepare("SELECT * FROM threat_indicators WHERE threat_id = ? ORDER BY last_seen DESC");
    $iocStmt->execute([$threat['id']]);
    $threat['indicators'] = $iocStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get related accountability entries
    $actStmt = $db->prepare("SELECT * FROM accountability_ledger WHERE threat_id = ? ORDER BY created_at DESC");
    $actStmt->execute([$threat['id']]);
    $threat['actions'] = $actStmt->fetchAll(PDO::FETCH_ASSOC);

    jsonOut($threat);
    break;

// ══════════════════════════════════════════
// REPORT THREAT
// ══════════════════════════════════════════
case 'report_threat':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['error' => 'POST required'], 405); }
    $data = json_decode(file_get_contents('php://input'), true);

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $type = $data['threat_type'] ?? 'unknown';
    $severity = $data['severity'] ?? 'medium';

    if (!$title || !$description) { jsonOut(['error' => 'title and description required'], 400); }

    $threatId = generateThreatId($db);

    $stmt = $db->prepare("INSERT INTO threat_intelligence
        (threat_id, threat_type, severity, status, source_type, source_ip, source_country, source_ua,
         title, description, attack_vector, target_resource, evidence, tags,
         response_action, assigned_agent, created_by)
        VALUES (?, ?, ?, 'detected', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $threatId, $type, $severity,
        $data['source_type'] ?? 'human_report',
        $data['source_ip'] ?? null,
        $data['source_country'] ?? null,
        $data['source_ua'] ?? null,
        $title, $description,
        $data['attack_vector'] ?? null,
        $data['target_resource'] ?? null,
        isset($data['evidence']) ? json_encode($data['evidence']) : null,
        isset($data['tags']) ? json_encode($data['tags']) : null,
        $data['response_action'] ?? 'none',
        $data['assigned_agent'] ?? null,
        $clientId ?: null
    ]);

    $newId = (int)$db->lastInsertId();

    // Auto-log to accountability ledger
    $ledgerId = generateLedgerId($db);
    $db->prepare("INSERT INTO accountability_ledger
        (ledger_id, action_type, severity, actor_type, actor_id, actor_name,
         subject_type, subject_id, title, description, outcome, threat_id, created_by)
        VALUES (?, 'investigation_opened', ?, 'system', ?, ?, 'threat', ?, ?, ?, 'pending', ?, ?)")
        ->execute([
            $ledgerId, $severity,
            $isInternal ? 'security_monitor' : (string)$clientId,
            $isInternal ? 'Security Monitor' : 'Commander',
            $threatId, "Threat detected: $title", "Investigation opened for $threatId", $newId, $clientId ?: null
        ]);

    jsonOut(['reported' => true, 'threat_id' => $threatId, 'id' => $newId]);
    break;

// ══════════════════════════════════════════
// UPDATE THREAT
// ══════════════════════════════════════════
case 'update_threat':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['error' => 'POST required'], 405); }
    if (!$isAdmin && !$isInternal) { jsonOut(['error' => 'Admin access required'], 403); }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) { jsonOut(['error' => 'id required'], 400); }

    $allowed = ['status', 'severity', 'response_action', 'assigned_agent', 'resolution_notes', 'blocked_until'];
    $sets = [];
    $params = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $sets[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    if (empty($sets)) { jsonOut(['error' => 'No updatable fields provided'], 400); }

    // Set confirmed_at / resolved_at timestamps
    if (isset($data['status'])) {
        if ($data['status'] === 'confirmed') { $sets[] = "confirmed_at = NOW()"; }
        if ($data['status'] === 'resolved') { $sets[] = "resolved_at = NOW()"; $sets[] = "resolved_by = ?"; $params[] = $isInternal ? 'system' : (string)$clientId; }
    }

    $params[] = $id;
    $db->prepare("UPDATE threat_intelligence SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);

    // Log to ledger
    $statusText = $data['status'] ?? 'updated';
    $actionType = match($statusText) {
        'mitigated' => 'threat_mitigated',
        'resolved'  => 'threat_mitigated',
        'confirmed' => 'investigation_opened',
        default     => 'threat_blocked'
    };
    if (isset($data['response_action']) && $data['response_action'] === 'blocked') {
        $actionType = 'threat_blocked';
    }

    $threatRow = $db->prepare("SELECT threat_id, title FROM threat_intelligence WHERE id = ?");
    $threatRow->execute([$id]);
    $t = $threatRow->fetch(PDO::FETCH_ASSOC);

    if ($t) {
        $lid = generateLedgerId($db);
        $db->prepare("INSERT INTO accountability_ledger
            (ledger_id, action_type, severity, actor_type, actor_id, actor_name,
             subject_type, subject_id, title, description, outcome, threat_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?, 'threat', ?, ?, ?, 'success', ?, ?)")
            ->execute([
                $lid, $actionType, $data['severity'] ?? 'medium',
                $isInternal ? 'system' : 'human', $isInternal ? 'security_monitor' : (string)$clientId,
                $isInternal ? 'Security Monitor' : 'Commander',
                $t['threat_id'], "Threat $statusText: " . $t['title'],
                "Status changed to $statusText" . (isset($data['resolution_notes']) ? ": " . $data['resolution_notes'] : ''),
                $id, $clientId ?: null
            ]);
    }

    jsonOut(['updated' => true]);
    break;

// ══════════════════════════════════════════
// LIST IOCs
// ══════════════════════════════════════════
case 'indicators':
    $where = [];
    $params = [];
    if (!empty($_GET['threat_id'])) { $where[] = "i.threat_id = ?"; $params[] = (int)$_GET['threat_id']; }
    if (!empty($_GET['type']))      { $where[] = "i.indicator_type = ?"; $params[] = $_GET['type']; }
    if (!empty($_GET['active']))    { $where[] = "i.is_active = ?"; $params[] = (int)$_GET['active']; }
    if (!empty($_GET['search']))    { $where[] = "i.indicator_value LIKE ?"; $params[] = '%'.$_GET['search'].'%'; }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM threat_indicators i $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT i.*, t.threat_id as threat_ref, t.title as threat_title, t.severity as threat_severity
        FROM threat_indicators i
        JOIN threat_intelligence t ON t.id = i.threat_id
        $whereSQL ORDER BY i.last_seen DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonOut(['indicators' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page]);
    break;

// ══════════════════════════════════════════
// ADD IOC
// ══════════════════════════════════════════
case 'add_indicator':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['error' => 'POST required'], 405); }
    $data = json_decode(file_get_contents('php://input'), true);

    $threatDbId = (int)($data['threat_id'] ?? 0);
    $type = $data['indicator_type'] ?? '';
    $value = trim($data['indicator_value'] ?? '');

    if (!$threatDbId || !$type || !$value) { jsonOut(['error' => 'threat_id, indicator_type, indicator_value required'], 400); }

    // Upsert: if same IOC exists for this threat, update times_seen
    $existing = $db->prepare("SELECT id, times_seen FROM threat_indicators WHERE threat_id = ? AND indicator_type = ? AND indicator_value = ?");
    $existing->execute([$threatDbId, $type, $value]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $db->prepare("UPDATE threat_indicators SET times_seen = times_seen + 1, last_seen = NOW(), confidence = LEAST(100, confidence + 5) WHERE id = ?")
            ->execute([$row['id']]);
        jsonOut(['updated' => true, 'id' => (int)$row['id'], 'times_seen' => $row['times_seen'] + 1]);
    } else {
        $db->prepare("INSERT INTO threat_indicators (threat_id, indicator_type, indicator_value, confidence, notes)
            VALUES (?, ?, ?, ?, ?)")
            ->execute([$threatDbId, $type, $value, (int)($data['confidence'] ?? 50), $data['notes'] ?? null]);
        jsonOut(['added' => true, 'id' => (int)$db->lastInsertId()]);
    }
    break;

// ══════════════════════════════════════════
// ACCOUNTABILITY LEDGER
// ══════════════════════════════════════════
case 'ledger':
    $where = [];
    $params = [];
    if (!empty($_GET['action_type']))  { $where[] = "action_type = ?";  $params[] = $_GET['action_type']; }
    if (!empty($_GET['severity']))     { $where[] = "severity = ?";     $params[] = $_GET['severity']; }
    if (!empty($_GET['actor_type']))   { $where[] = "actor_type = ?";   $params[] = $_GET['actor_type']; }
    if (!empty($_GET['subject_type'])) { $where[] = "subject_type = ?"; $params[] = $_GET['subject_type']; }
    if (!empty($_GET['search']))       { $where[] = "(title LIKE ? OR description LIKE ?)"; $s = '%'.$_GET['search'].'%'; $params[] = $s; $params[] = $s; }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM accountability_ledger $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT * FROM accountability_ledger $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonOut(['ledger' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page]);
    break;

// ══════════════════════════════════════════
// LOG ACTION
// ══════════════════════════════════════════
case 'log_action':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['error' => 'POST required'], 405); }
    $data = json_decode(file_get_contents('php://input'), true);

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $actionType = $data['action_type'] ?? '';

    if (!$title || !$description || !$actionType) {
        jsonOut(['error' => 'title, description, action_type required'], 400);
    }

    $ledgerId = generateLedgerId($db);
    $stmt = $db->prepare("INSERT INTO accountability_ledger
        (ledger_id, action_type, severity, actor_type, actor_id, actor_name,
         subject_type, subject_id, title, description, evidence, outcome,
         impact_score, threat_id, case_id, infraction_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $ledgerId, $actionType,
        $data['severity'] ?? 'medium',
        $data['actor_type'] ?? 'system',
        $data['actor_id'] ?? null,
        $data['actor_name'] ?? null,
        $data['subject_type'] ?? 'system',
        $data['subject_id'] ?? null,
        $title, $description,
        isset($data['evidence']) ? json_encode($data['evidence']) : null,
        $data['outcome'] ?? 'success',
        (int)($data['impact_score'] ?? 0),
        isset($data['threat_id']) ? (int)$data['threat_id'] : null,
        isset($data['case_id']) ? (int)$data['case_id'] : null,
        isset($data['infraction_id']) ? (int)$data['infraction_id'] : null,
        $clientId ?: null
    ]);

    jsonOut(['logged' => true, 'ledger_id' => $ledgerId, 'id' => (int)$db->lastInsertId()]);
    break;

// ══════════════════════════════════════════
// BLOCKED ACTORS
// ══════════════════════════════════════════
case 'blocked':
    $stmt = $db->query("SELECT id, threat_id, threat_type, severity, source_ip, source_country,
            title, response_action, blocked_until, detected_at
        FROM threat_intelligence
        WHERE response_action = 'blocked' AND (blocked_until IS NULL OR blocked_until > NOW())
        ORDER BY detected_at DESC");
    jsonOut(['blocked' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    break;

// ══════════════════════════════════════════
// BLOCK ACTOR
// ══════════════════════════════════════════
case 'block_actor':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['error' => 'POST required'], 405); }
    if (!$isAdmin && !$isInternal) { jsonOut(['error' => 'Admin access required'], 403); }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) { jsonOut(['error' => 'id required'], 400); }

    $duration = (int)($data['duration_hours'] ?? 0);
    $blockedUntil = $duration > 0 ? date('Y-m-d H:i:s', time() + $duration * 3600) : null;

    $db->prepare("UPDATE threat_intelligence SET response_action = 'blocked', blocked_until = ? WHERE id = ?")
        ->execute([$blockedUntil, $id]);

    // Log it
    $threatRow = $db->prepare("SELECT threat_id, title, source_ip FROM threat_intelligence WHERE id = ?");
    $threatRow->execute([$id]);
    $t = $threatRow->fetch(PDO::FETCH_ASSOC);

    if ($t) {
        $lid = generateLedgerId($db);
        $db->prepare("INSERT INTO accountability_ledger
            (ledger_id, action_type, severity, actor_type, actor_id, actor_name,
             subject_type, subject_id, title, description, outcome, threat_id, created_by)
            VALUES (?, 'threat_blocked', 'high', ?, ?, ?, 'threat', ?, ?, ?, 'success', ?, ?)")
            ->execute([
                $lid, $isInternal ? 'system' : 'human',
                $isInternal ? 'security_monitor' : (string)$clientId,
                $isInternal ? 'Security Monitor' : 'Commander',
                $t['threat_id'], "Blocked: " . $t['title'],
                "IP " . ($t['source_ip'] ?? 'unknown') . " blocked" . ($duration ? " for {$duration}h" : " indefinitely"),
                $id, $clientId ?: null
            ]);
    }

    jsonOut(['blocked' => true, 'blocked_until' => $blockedUntil]);
    break;

// ══════════════════════════════════════════
// UNBLOCK ACTOR
// ══════════════════════════════════════════
case 'unblock_actor':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['error' => 'POST required'], 405); }
    if (!$isAdmin && !$isInternal) { jsonOut(['error' => 'Admin access required'], 403); }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) { jsonOut(['error' => 'id required'], 400); }

    $db->prepare("UPDATE threat_intelligence SET response_action = 'monitored', blocked_until = NULL WHERE id = ?")
        ->execute([$id]);

    jsonOut(['unblocked' => true]);
    break;

default:
    jsonOut(['error' => 'Unknown action', 'available' => [
        'overview', 'threats', 'threat_detail',
        'report_threat', 'update_threat',
        'indicators', 'add_indicator',
        'ledger', 'log_action',
        'blocked', 'block_actor', 'unblock_actor'
    ]], 400);
}
