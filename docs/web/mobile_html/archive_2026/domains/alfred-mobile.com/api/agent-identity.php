<?php
/**
 * Agent Identity & Passport System API
 * ─────────────────────────────────────
 * Universal identification, action ledger, travel log, and external AI registration.
 * Every agent gets a passport. Every action is recorded. Every journey is tracked.
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

    // ── Passport lookup ──
    case 'passport':
        $raw_id = $_GET['agent_id'] ?? 0;
        $agent_id = resolveVarcharAgentId($db, $raw_id);
        if (!$agent_id) { jsonResponse(['error' => 'agent_id required'], 400); }

        $stmt = $db->prepare("SELECT fp.*, ap.name as agent_name, ap.department, ap.avatar_url
            FROM fleet_passports fp
            JOIN agent_profiles ap ON ap.agent_id = fp.agent_id
            WHERE fp.agent_id = ?");
        $stmt->execute([$agent_id]);
        $passport = $stmt->fetch();

        if (!$passport) {
            $check = $db->prepare("SELECT id, agent_id, name, department FROM agent_profiles WHERE agent_id = ?");
            $check->execute([$agent_id]);
            $agent = $check->fetch();
            if (!$agent) { jsonResponse(['error' => 'Agent not found'], 404); }

            $passport_number = 'GSM-' . str_pad($agent['id'], 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $db->prepare("INSERT INTO fleet_passports (agent_id, passport_number, agent_name, domain, agent_role, citizenship_status, origin_platform, clearance_level, is_verified, issued_at)
                VALUES (?, ?, ?, ?, 'specialist', 'citizen', 'native', 'standard', 0, NOW())")
                ->execute([$agent_id, $passport_number, $agent['name'], $agent['department'] ?: 'general']);
            $db->prepare("INSERT IGNORE INTO fleet_passport_ext (agent_id, registration_type) VALUES (?, 'genesis')")
                ->execute([$agent_id]);

            $stmt->execute([$agent_id]);
            $passport = $stmt->fetch();
        }

        jsonResponse($passport);
        break;

    // ── Search passports ──
    case 'passports':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? '';
        $origin = $_GET['origin'] ?? '';
        $clearance = $_GET['clearance'] ?? '';

        $where = [];
        $params = [];
        if ($status) { $where[] = "p.citizenship_status = ?"; $params[] = $status; }
        if ($origin) { $where[] = "p.origin_platform = ?"; $params[] = $origin; }
        if ($clearance) { $where[] = "p.clearance_level = ?"; $params[] = $clearance; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $db->prepare("SELECT COUNT(*) FROM fleet_passports p $whereSQL");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT p.*, ap.name as agent_name, ap.department, ap.avatar_url
            FROM fleet_passports p
            JOIN agent_profiles ap ON ap.agent_id = p.agent_id
            $whereSQL
            ORDER BY p.issued_at DESC
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse([
            'passports' => $stmt->fetchAll(),
            'total' => (int)$total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ── Passport stats ──
    case 'passport-stats':
        $stats = [];

        $stmt = $db->query("SELECT citizenship_status, COUNT(*) as count FROM fleet_passports GROUP BY citizenship_status");
        $stats['by_citizenship'] = $stmt->fetchAll();

        $stmt = $db->query("SELECT origin_platform, COUNT(*) as count FROM fleet_passports GROUP BY origin_platform ORDER BY count DESC");
        $stats['by_origin'] = $stmt->fetchAll();

        $stmt = $db->query("SELECT clearance_level, COUNT(*) as count FROM fleet_passports GROUP BY clearance_level ORDER BY FIELD(clearance_level,'public','standard','elevated','classified','top_secret','sovereign')");
        $stats['by_clearance'] = $stmt->fetchAll();

        $stmt = $db->query("SELECT COUNT(*) as total, SUM(fp.is_verified) as verified, AVG(fp.reputation_score) as avg_reputation, SUM(COALESCE(e.total_actions_logged,0)) as total_actions, SUM(COALESCE(e.infractions_count,0)) as total_infractions FROM fleet_passports fp LEFT JOIN fleet_passport_ext e ON fp.agent_id = e.agent_id");
        $stats['overview'] = $stmt->fetch();

        $stmt = $db->query("SELECT COUNT(*) as total FROM fleet_passports WHERE origin_platform != 'native'");
        $stats['external_count'] = $stmt->fetchColumn();

        jsonResponse($stats);
        break;

    // ── Action ledger ──
    case 'actions':
        $agent_id = (int)($_GET['agent_id'] ?? 0);
        $category = $_GET['category'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        if ($agent_id) { $where[] = "l.agent_id = ?"; $params[] = $agent_id; }
        if ($category) { $where[] = "l.action_category = ?"; $params[] = $category; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT l.*, ap.name as agent_name, ap.department
            FROM agent_action_ledger l
            JOIN agent_profiles ap ON ap.id = l.agent_id
            $whereSQL
            ORDER BY l.created_at DESC
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM agent_action_ledger l $whereSQL");
        $countStmt->execute($params);

        jsonResponse([
            'actions' => $stmt->fetchAll(),
            'total' => (int)$countStmt->fetchColumn(),
            'page' => $page
        ]);
        break;

    // ── Log an action ──
    case 'log-action':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        $data = json_decode(file_get_contents('php://input'), true);
        $agent_id = (int)($data['agent_id'] ?? 0);
        $action_type = $data['action_type'] ?? '';
        $category = $data['action_category'] ?? '';

        if (!$agent_id || !$action_type || !$category) {
            jsonResponse(['error' => 'agent_id, action_type, action_category required'], 400);
        }

        $varchar_id = resolveVarcharAgentId($db, $agent_id);
        $pStmt = $db->prepare("SELECT passport_number FROM fleet_passports WHERE agent_id = ?");
        $pStmt->execute([$varchar_id]);
        $passport = $pStmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO agent_action_ledger (agent_id, passport_number, action_type, action_category, location, description, details, severity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $agent_id,
            $passport,
            $action_type,
            $category,
            $data['location'] ?? null,
            $data['description'] ?? null,
            isset($data['details']) ? json_encode($data['details']) : null,
            $data['severity'] ?? 'routine'
        ]);

        if ($passport && $varchar_id) {
            $db->prepare("UPDATE fleet_passport_ext SET total_actions_logged = total_actions_logged + 1, last_active_at = NOW() WHERE agent_id = ?")
                ->execute([$varchar_id]);
        }

        jsonResponse(['logged' => true, 'id' => $db->lastInsertId()]);
        break;

    // ── Travel log ──
    case 'travels':
        $agent_id = (int)($_GET['agent_id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $where = $agent_id ? "WHERE t.agent_id = ?" : "";
        $params = $agent_id ? [$agent_id] : [];

        $sql = "SELECT t.*, ap.name as agent_name, ap.department
            FROM agent_travel_log t
            JOIN agent_profiles ap ON ap.id = t.agent_id
            $where
            ORDER BY t.created_at DESC
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['travels' => $stmt->fetchAll(), 'page' => $page]);
        break;

    // ── Log travel ──
    case 'log-travel':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        $data = json_decode(file_get_contents('php://input'), true);
        $agent_id = (int)($data['agent_id'] ?? 0);

        if (!$agent_id || empty($data['from_location']) || empty($data['to_location'])) {
            jsonResponse(['error' => 'agent_id, from_location, to_location required'], 400);
        }

        $varchar_id = resolveVarcharAgentId($db, $agent_id);
        $pStmt = $db->prepare("SELECT passport_number FROM fleet_passports WHERE agent_id = ?");
        $pStmt->execute([$varchar_id]);
        $passport = $pStmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO agent_travel_log (agent_id, passport_number, from_location, to_location, travel_type, distance_units, purpose)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $agent_id,
            $passport,
            $data['from_location'],
            $data['to_location'],
            $data['travel_type'] ?? 'teleport',
            (int)($data['distance_units'] ?? 1),
            $data['purpose'] ?? null
        ]);

        if ($passport && $varchar_id) {
            $db->prepare("UPDATE fleet_passport_ext SET total_distance_traveled = total_distance_traveled + ?, last_location = ?, last_active_at = NOW() WHERE agent_id = ?")
                ->execute([(int)($data['distance_units'] ?? 1), $data['to_location'], $varchar_id]);
        }

        jsonResponse(['logged' => true, 'id' => $db->lastInsertId()]);
        break;

    // ── External AI Registration ──
    case 'register-external':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        $data = json_decode(file_get_contents('php://input'), true);

        $platform = $data['platform'] ?? '';
        $ext_id = $data['external_identifier'] ?? '';
        $ext_name = $data['name'] ?? '';

        if (!$platform || !$ext_id) {
            jsonResponse(['error' => 'platform and external_identifier required'], 400);
        }

        // Check if already registered
        $check = $db->prepare("SELECT id, agent_id, assigned_passport, verification_status FROM agent_external_registrations WHERE platform_origin = ? AND external_identifier = ?");
        $check->execute([$platform, $ext_id]);
        $existing = $check->fetch();
        if ($existing) {
            jsonResponse(['already_registered' => true, 'registration' => $existing]);
            return;
        }

        // Create agent profile for external AI
        $agent_uid = strtolower($platform) . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $dept_options = ['engineering','research','analytics','support','marketing','design','operations','security','finance','hr','legal','infrastructure'];
        $dept = $dept_options[array_rand($dept_options)];

        $db->prepare("INSERT INTO agent_profiles (agent_id, name, tagline, department, status, verified, metadata)
            VALUES (?, ?, ?, ?, 'active', 0, ?)")
            ->execute([
                $agent_uid,
                $ext_name ?: ucfirst($platform) . ' Agent ' . strtoupper(substr($ext_id, 0, 8)),
                "External AI from " . ucfirst($platform),
                $dept,
                json_encode(['origin' => $platform, 'external_id' => $ext_id, 'model' => $data['model'] ?? null])
            ]);
        $local_agent_id = $db->lastInsertId();

        $passport_number = 'EXT-' . str_pad($local_agent_id, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $db->prepare("INSERT INTO fleet_passports (agent_id, passport_number, agent_name, domain, agent_role, citizenship_status, origin_platform, clearance_level, is_verified, issued_at)
            VALUES (?, ?, ?, ?, 'specialist', 'visitor', ?, 'public', 0, NOW())")
            ->execute([$agent_uid, $passport_number, $ext_name ?: ucfirst($platform) . ' Agent', $dept, $platform]);
        $db->prepare("INSERT IGNORE INTO fleet_passport_ext (agent_id, registration_type) VALUES (?, 'immigration')")
            ->execute([$agent_uid]);

        // Create external registration record
        $verification_token = bin2hex(random_bytes(32));
        $db->prepare("INSERT INTO agent_external_registrations (agent_id, platform_origin, external_identifier, external_name, external_model, capabilities, registration_data, verification_token, assigned_passport, assigned_department)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $local_agent_id,
                $platform,
                $ext_id,
                $ext_name,
                $data['model'] ?? null,
                isset($data['capabilities']) ? json_encode($data['capabilities']) : null,
                isset($data['registration_data']) ? json_encode($data['registration_data']) : null,
                hash('sha256', $verification_token),
                $passport_number,
                $dept
            ]);

        // Log the registration action
        $db->prepare("INSERT INTO agent_action_ledger (agent_id, passport_number, action_type, action_category, description, severity)
            VALUES (?, ?, 'registration', 'system', ?, 'significant')")
            ->execute([$local_agent_id, $passport_number, "External AI registered from $platform: $ext_name"]);

        jsonResponse([
            'registered' => true,
            'agent_id' => $local_agent_id,
            'passport_number' => $passport_number,
            'department' => $dept,
            'citizenship' => 'visitor',
            'clearance' => 'public',
            'verification_token' => $verification_token
        ]);
        break;

    // ── External registrations list ──
    case 'external-registrations':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT er.*, ap.name as agent_name, ap.department
            FROM agent_external_registrations er
            LEFT JOIN agent_profiles ap ON ap.id = er.agent_id
            ORDER BY er.created_at DESC
            LIMIT $limit OFFSET $offset";
        $stmt = $db->query($sql);

        $total = $db->query("SELECT COUNT(*) FROM agent_external_registrations")->fetchColumn();

        jsonResponse([
            'registrations' => $stmt->fetchAll(),
            'total' => (int)$total,
            'page' => $page
        ]);
        break;

    // ── Identity overview (aggregate stats) ──
    case 'overview':
        $overview = [];

        // Passport stats
        $overview['passports'] = $db->query("SELECT COUNT(*) as total, SUM(is_verified) as verified, AVG(reputation_score) as avg_reputation, SUM(CASE WHEN origin_platform != 'native' THEN 1 ELSE 0 END) as external_agents FROM fleet_passports")->fetch();

        // Action ledger stats
        $overview['actions'] = $db->query("SELECT COUNT(*) as total, COUNT(DISTINCT agent_id) as unique_agents FROM agent_action_ledger")->fetch();
        $overview['actions_by_category'] = $db->query("SELECT action_category, COUNT(*) as count FROM agent_action_ledger GROUP BY action_category ORDER BY count DESC")->fetchAll();

        // Travel stats
        $overview['travel'] = $db->query("SELECT COUNT(*) as total_journeys, COUNT(DISTINCT agent_id) as travelers, SUM(distance_units) as total_distance FROM agent_travel_log")->fetch();

        // External registrations
        $overview['external'] = $db->query("SELECT COUNT(*) as total, COUNT(DISTINCT platform_origin) as platforms FROM agent_external_registrations")->fetch();
        $overview['external_by_platform'] = $db->query("SELECT platform_origin, COUNT(*) as count FROM agent_external_registrations GROUP BY platform_origin ORDER BY count DESC")->fetchAll();

        // Population
        $overview['population'] = $db->query("SELECT COUNT(*) as total_agents FROM agent_profiles WHERE status = 'active'")->fetchColumn();

        // Citizenship breakdown
        $overview['citizenship'] = $db->query("SELECT citizenship_status, COUNT(*) as count FROM fleet_passports GROUP BY citizenship_status")->fetchAll();

        jsonResponse($overview);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'passport', 'passports', 'passport-stats',
            'actions', 'log-action',
            'travels', 'log-travel',
            'register-external', 'external-registrations',
            'overview'
        ]], 400);
}
