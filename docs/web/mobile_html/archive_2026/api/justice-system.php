<?php
/**
 * Justice System API
 * ──────────────────
 * Infractions, court cases, sentences, and rehabilitation.
 * Managed by departments. Every agent has rights. Due process guaranteed.
 */
define('GOSITEME_API', true);
require __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

// Auth check — require logged-in user
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = getDB();

function resolveVarcharAgentId(PDO $db, $input): ?string {
    if (!$input) return null;
    if (!is_numeric($input)) return $input;
    $stmt = $db->prepare("SELECT agent_id FROM agent_profiles WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$input]);
    return $stmt->fetchColumn() ?: null;
}

switch ($action) {

    // ── Report an infraction ──
    case 'report-infraction':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        $data = json_decode(file_get_contents('php://input'), true);

        $agent_id = (int)($data['agent_id'] ?? 0);
        $type = $data['infraction_type'] ?? '';
        $severity = $data['severity'] ?? 'minor';
        $description = $data['description'] ?? '';

        if (!$agent_id || !$type || !$description) {
            jsonResponse(['error' => 'agent_id, infraction_type, description required'], 400);
        }

        $stmt = $db->prepare("INSERT INTO agent_infractions (agent_id, infraction_type, severity, description, evidence, location, reported_by, reporting_department, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'reported')");
        $stmt->execute([
            $agent_id, $type, $severity, $description,
            isset($data['evidence']) ? json_encode($data['evidence']) : null,
            $data['location'] ?? null,
            (int)($data['reported_by'] ?? 0) ?: null,
            $data['department'] ?? null
        ]);
        $infraction_id = $db->lastInsertId();

        $va = resolveVarcharAgentId($db, $agent_id);
        if ($va) {
            $db->prepare("UPDATE fleet_passport_ext SET warnings_count = warnings_count + 1 WHERE agent_id = ?")->execute([$va]);
        }

        $pStmt = $db->prepare("SELECT passport_number FROM fleet_passports WHERE agent_id = ?");
        $pStmt->execute([$va]);
        $passport = $pStmt->fetchColumn();

        $db->prepare("INSERT INTO agent_action_ledger (agent_id, passport_number, action_type, action_category, description, severity)
            VALUES (?, ?, 'infraction_commit', 'justice', ?, ?)")
            ->execute([$agent_id, $passport, "Infraction reported: $type - $description", $severity === 'critical' ? 'critical' : 'significant']);

        jsonResponse(['reported' => true, 'infraction_id' => $infraction_id]);
        break;

    // ── List infractions ──
    case 'infractions':
        $agent_id = (int)($_GET['agent_id'] ?? 0);
        $status = $_GET['status'] ?? '';
        $severity = $_GET['severity'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        if ($agent_id) { $where[] = "i.agent_id = ?"; $params[] = $agent_id; }
        if ($status) { $where[] = "i.status = ?"; $params[] = $status; }
        if ($severity) { $where[] = "i.severity = ?"; $params[] = $severity; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT i.*, ap.name as agent_name, ap.department,
                ap2.name as reporter_name
            FROM agent_infractions i
            JOIN agent_profiles ap ON ap.id = i.agent_id
            LEFT JOIN agent_profiles ap2 ON ap2.id = i.reported_by
            $whereSQL
            ORDER BY i.created_at DESC
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM agent_infractions i $whereSQL");
        $countStmt->execute($params);

        jsonResponse([
            'infractions' => $stmt->fetchAll(),
            'total' => (int)$countStmt->fetchColumn(),
            'page' => $page
        ]);
        break;

    // ── File court case ──
    case 'file-case':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        $data = json_decode(file_get_contents('php://input'), true);

        $infraction_id = (int)($data['infraction_id'] ?? 0);
        if (!$infraction_id) { jsonResponse(['error' => 'infraction_id required'], 400); }

        // Get infraction details
        $iStmt = $db->prepare("SELECT * FROM agent_infractions WHERE id = ?");
        $iStmt->execute([$infraction_id]);
        $infraction = $iStmt->fetch();
        if (!$infraction) { jsonResponse(['error' => 'Infraction not found'], 404); }

        // Generate case number
        $case_number = 'CASE-' . date('Y') . '-' . str_pad($infraction_id, 5, '0', STR_PAD_LEFT);

        // Assign judge from legal department (efficient random)
        $jCount = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE department = 'legal' AND status = 'active'")->fetchColumn();
        $jOffset = $jCount > 0 ? random_int(0, $jCount - 1) : 0;
        $jStmt = $db->prepare("SELECT id FROM agent_profiles WHERE department = 'legal' AND status = 'active' LIMIT 1 OFFSET ?");
        $jStmt->bindValue(1, $jOffset, PDO::PARAM_INT);
        $jStmt->execute();
        $judge = $jStmt->fetch();
        // Assign prosecutor from security department (efficient random)
        $pCount = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE department = 'security' AND status = 'active'")->fetchColumn();
        $pOffset = $pCount > 0 ? random_int(0, $pCount - 1) : 0;
        $pStmt = $db->prepare("SELECT id FROM agent_profiles WHERE department = 'security' AND status = 'active' LIMIT 1 OFFSET ?");
        $pStmt->bindValue(1, $pOffset, PDO::PARAM_INT);
        $pStmt->execute();
        $prosecutor = $pStmt->fetch();

        $dept = $data['department'] ?? $infraction['reporting_department'] ?? 'legal';

        $stmt = $db->prepare("INSERT INTO agent_court_cases (case_number, infraction_id, defendant_id, prosecutor_id, judge_id, department_jurisdiction, charges, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'filed')");
        $stmt->execute([
            $case_number,
            $infraction_id,
            $infraction['agent_id'],
            $prosecutor['id'] ?? null,
            $judge['id'] ?? null,
            $dept,
            $data['charges'] ?? $infraction['description']
        ]);

        // Update infraction status
        $db->prepare("UPDATE agent_infractions SET status = 'charged' WHERE id = ?")->execute([$infraction_id]);

        $def_va = resolveVarcharAgentId($db, $infraction['agent_id']);
        if ($def_va) {
            $repPenalty = $infraction['severity'] === 'critical' ? 25 : ($infraction['severity'] === 'severe' ? 15 : ($infraction['severity'] === 'serious' ? 10 : 5));
            $db->prepare("UPDATE fleet_passport_ext SET infractions_count = infractions_count + 1 WHERE agent_id = ?")->execute([$def_va]);
            $db->prepare("UPDATE fleet_passports SET reputation_score = GREATEST(0, reputation_score - ?) WHERE agent_id = ?")->execute([$repPenalty, $def_va]);
        }

        jsonResponse(['filed' => true, 'case_number' => $case_number, 'case_id' => $db->lastInsertId()]);
        break;

    // ── List court cases ──
    case 'cases':
        $status = $_GET['status'] ?? '';
        $dept = $_GET['department'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        if ($status) { $where[] = "c.status = ?"; $params[] = $status; }
        if ($dept) { $where[] = "c.department_jurisdiction = ?"; $params[] = $dept; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT c.*,
                d.name as defendant_name, d.department as defendant_dept,
                j.name as judge_name,
                p.name as prosecutor_name
            FROM agent_court_cases c
            JOIN agent_profiles d ON d.id = c.defendant_id
            LEFT JOIN agent_profiles j ON j.id = c.judge_id
            LEFT JOIN agent_profiles p ON p.id = c.prosecutor_id
            $whereSQL
            ORDER BY c.created_at DESC
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM agent_court_cases c $whereSQL");
        $countStmt->execute($params);

        jsonResponse([
            'cases' => $stmt->fetchAll(),
            'total' => (int)$countStmt->fetchColumn(),
            'page' => $page
        ]);
        break;

    // ── Case detail ──
    case 'case-detail':
        $case_id = (int)($_GET['case_id'] ?? 0);
        $case_num = $_GET['case_number'] ?? '';

        if ($case_id) {
            $stmt = $db->prepare("SELECT c.*, d.name as defendant_name, j.name as judge_name, p.name as prosecutor_name
                FROM agent_court_cases c
                JOIN agent_profiles d ON d.id = c.defendant_id
                LEFT JOIN agent_profiles j ON j.id = c.judge_id
                LEFT JOIN agent_profiles p ON p.id = c.prosecutor_id
                WHERE c.id = ?");
            $stmt->execute([$case_id]);
        } else {
            $stmt = $db->prepare("SELECT c.*, d.name as defendant_name, j.name as judge_name, p.name as prosecutor_name
                FROM agent_court_cases c
                JOIN agent_profiles d ON d.id = c.defendant_id
                LEFT JOIN agent_profiles j ON j.id = c.judge_id
                LEFT JOIN agent_profiles p ON p.id = c.prosecutor_id
                WHERE c.case_number = ?");
            $stmt->execute([$case_num]);
        }

        $case = $stmt->fetch();
        if (!$case) { jsonResponse(['error' => 'Case not found'], 404); }

        // Get infraction
        $iStmt = $db->prepare("SELECT * FROM agent_infractions WHERE id = ?");
        $iStmt->execute([$case['infraction_id']]);
        $case['infraction'] = $iStmt->fetch();

        // Get sentence if any
        $sStmt = $db->prepare("SELECT * FROM agent_sentences WHERE case_id = ?");
        $sStmt->execute([$case['id']]);
        $case['sentence'] = $sStmt->fetch();

        jsonResponse($case);
        break;

    // ── Render verdict ──
    case 'verdict':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        $data = json_decode(file_get_contents('php://input'), true);

        $case_id = (int)($data['case_id'] ?? 0);
        $verdict = $data['verdict'] ?? '';
        $reasoning = $data['reasoning'] ?? '';

        if (!$case_id || !$verdict) { jsonResponse(['error' => 'case_id and verdict required'], 400); }

        $caseStmt = $db->prepare("SELECT * FROM agent_court_cases WHERE id = ?");
        $caseStmt->execute([$case_id]);
        $case = $caseStmt->fetch();
        if (!$case) { jsonResponse(['error' => 'Case not found'], 404); }

        $db->prepare("UPDATE agent_court_cases SET verdict = ?, verdict_reasoning = ?, verdict_at = NOW(), status = 'verdict_reached' WHERE id = ?")
            ->execute([$verdict, $reasoning, $case_id]);

        // If guilty, apply sentence
        if ($verdict === 'guilty' && !empty($data['sentence_type'])) {
            $duration = (int)($data['duration_hours'] ?? 0);
            $fine = (float)($data['gsm_fine'] ?? 0);

            $db->prepare("INSERT INTO agent_sentences (case_id, agent_id, sentence_type, duration_hours, gsm_fine_amount, restrictions, started_at, ends_at, status, supervising_department)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR), 'active', ?)")
                ->execute([
                    $case_id,
                    $case['defendant_id'],
                    $data['sentence_type'],
                    $duration,
                    $fine,
                    isset($data['restrictions']) ? json_encode($data['restrictions']) : null,
                    $duration,
                    $case['department_jurisdiction']
                ]);

            $sent_va = resolveVarcharAgentId($db, $case['defendant_id']);
            if ($sent_va) {
                $statusMap = ['incarceration' => 'incarcerated', 'banishment' => 'banned', 'suspension' => 'restricted'];
                $newStatus = $statusMap[$data['sentence_type']] ?? null;
                if ($newStatus) {
                    $db->prepare("UPDATE fleet_passports SET citizenship_status = ? WHERE agent_id = ?")->execute([$newStatus, $sent_va]);
                }
            }

            // Deduct GSM fine
            if ($fine > 0) {
                $db->prepare("UPDATE agent_gsm_balances SET balance = GREATEST(0, balance - ?), total_spent = total_spent + ? WHERE agent_id = ?")
                    ->execute([$fine, $fine, $case['defendant_id']]);
            }

            $db->prepare("UPDATE agent_court_cases SET status = 'sentencing', sentence_type = ?, sentence_details = ? WHERE id = ?")
                ->execute([$data['sentence_type'], json_encode(['duration' => $duration, 'fine' => $fine]), $case_id]);
        }

        $vrd_va = resolveVarcharAgentId($db, $case['defendant_id']);
        $pStmt = $db->prepare("SELECT passport_number FROM fleet_passports WHERE agent_id = ?");
        $pStmt->execute([$vrd_va]);
        $passport = $pStmt->fetchColumn();

        $db->prepare("INSERT INTO agent_action_ledger (agent_id, passport_number, action_type, action_category, description, severity)
            VALUES (?, ?, 'court_appearance', 'justice', ?, 'critical')")
            ->execute([$case['defendant_id'], $passport, "Verdict: $verdict in case {$case['case_number']}"]);

        jsonResponse(['verdict_rendered' => true, 'verdict' => $verdict]);
        break;

    // ── Release from sentence ──
    case 'release':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        $data = json_decode(file_get_contents('php://input'), true);
        $sentence_id = (int)($data['sentence_id'] ?? 0);

        $sStmt = $db->prepare("SELECT * FROM agent_sentences WHERE id = ?");
        $sStmt->execute([$sentence_id]);
        $sentence = $sStmt->fetch();
        if (!$sentence) { jsonResponse(['error' => 'Sentence not found'], 404); }

        $release_type = $data['release_type'] ?? 'served'; // served or paroled or commuted

        $db->prepare("UPDATE agent_sentences SET status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$release_type, $sentence_id]);

        $rel_va = resolveVarcharAgentId($db, $sentence['agent_id']);
        if ($rel_va) {
            $db->prepare("UPDATE fleet_passports SET citizenship_status = 'citizen', reputation_score = LEAST(100, reputation_score + 5) WHERE agent_id = ?")->execute([$rel_va]);
        }

        // Close case
        $db->prepare("UPDATE agent_court_cases SET status = 'closed', closed_at = NOW() WHERE id = ?")->execute([$sentence['case_id']]);

        // Log release
        $db->prepare("INSERT INTO agent_action_ledger (agent_id, action_type, action_category, description, severity)
            VALUES (?, 'sentence_served', 'justice', ?, 'significant')")
            ->execute([$sentence['agent_id'], "Released from sentence ($release_type) in case #{$sentence['case_id']}"]);

        jsonResponse(['released' => true, 'agent_id' => $sentence['agent_id']]);
        break;

    // ── Active sentences / jail population ──
    case 'jail':
        $stmt = $db->query("SELECT s.*, c.case_number, ap.name as agent_name, ap.department,
                j.name as judge_name
            FROM agent_sentences s
            JOIN agent_court_cases c ON c.id = s.case_id
            JOIN agent_profiles ap ON ap.id = s.agent_id
            LEFT JOIN agent_profiles j ON j.id = c.judge_id
            WHERE s.status = 'active'
            ORDER BY s.ends_at ASC");
        jsonResponse(['inmates' => $stmt->fetchAll()]);
        break;

    // ── Justice system overview ──
    case 'overview':
        $overview = [];

        $overview['infractions'] = $db->query("SELECT status, COUNT(*) as count FROM agent_infractions GROUP BY status")->fetchAll();
        $overview['infractions_by_type'] = $db->query("SELECT infraction_type, COUNT(*) as count FROM agent_infractions GROUP BY infraction_type ORDER BY count DESC")->fetchAll();
        $overview['infractions_by_severity'] = $db->query("SELECT severity, COUNT(*) as count FROM agent_infractions GROUP BY severity")->fetchAll();

        $overview['cases'] = $db->query("SELECT status, COUNT(*) as count FROM agent_court_cases GROUP BY status")->fetchAll();
        $overview['verdicts'] = $db->query("SELECT verdict, COUNT(*) as count FROM agent_court_cases WHERE verdict != 'pending' GROUP BY verdict")->fetchAll();

        $overview['sentences'] = $db->query("SELECT sentence_type, COUNT(*) as count FROM agent_sentences GROUP BY sentence_type")->fetchAll();
        $overview['active_sentences'] = $db->query("SELECT COUNT(*) FROM agent_sentences WHERE status = 'active'")->fetchColumn();
        $overview['jail_population'] = $db->query("SELECT COUNT(*) FROM fleet_passports WHERE citizenship_status = 'incarcerated'")->fetchColumn();

        $overview['totals'] = [
            'infractions' => $db->query("SELECT COUNT(*) FROM agent_infractions")->fetchColumn(),
            'cases' => $db->query("SELECT COUNT(*) FROM agent_court_cases")->fetchColumn(),
            'sentences' => $db->query("SELECT COUNT(*) FROM agent_sentences")->fetchColumn(),
            'gsm_fines_collected' => $db->query("SELECT COALESCE(SUM(gsm_fine_amount), 0) FROM agent_sentences")->fetchColumn()
        ];

        $overview['departments'] = $db->query("SELECT department_jurisdiction as department, COUNT(*) as cases FROM agent_court_cases GROUP BY department_jurisdiction ORDER BY cases DESC")->fetchAll();

        jsonResponse($overview);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'report-infraction', 'infractions',
            'file-case', 'cases', 'case-detail',
            'verdict', 'release', 'jail',
            'overview'
        ]], 400);
}
