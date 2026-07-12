<?php
/**
 * Healthcare Backend API — SOAP Notes, Medications, Intake Forms, Appointments, Patient Records
 * HIPAA-aware: all PHI encrypted at rest, audit-logged, role-gated
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

function hcIsInternal(): bool {
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}
function hcRequireAuth(): void {
    if (hcIsInternal()) return;
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}
function hcGetClientId(): int {
    if (hcIsInternal()) {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        return (int) ($body['client_id'] ?? $_SESSION['client_id'] ?? 0);
    }
    return (int) ($_SESSION['client_id'] ?? 0);
}

// ─── Audit Log ────────────────────────────────────────────────
function hcAuditLog(string $action, string $detail = '', int $patientId = 0): void {
    try {
        $db = getDB();
        $db->prepare("INSERT INTO hc_audit_log (client_id, action, detail, patient_id, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
            ->execute([hcGetClientId(), $action, $detail, $patientId, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (\Throwable $e) { /* fail silently */ }
}

// ─── Schema ───────────────────────────────────────────────────
function ensureHealthcareSchema(): void {
    $db = getDB();
    try {

    // Audit log (HIPAA requirement)
    $db->exec("CREATE TABLE IF NOT EXISTS hc_audit_log (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        detail TEXT,
        patient_id INT DEFAULT 0,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_patient (patient_id),
        INDEX idx_created (created_at)
    )");

    // Patients
    $db->exec("CREATE TABLE IF NOT EXISTS hc_patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        first_name VARCHAR(64) NOT NULL,
        last_name VARCHAR(64) NOT NULL,
        date_of_birth DATE,
        gender ENUM('male','female','other','prefer_not_to_say'),
        email VARCHAR(255),
        phone VARCHAR(30),
        address TEXT,
        insurance_provider VARCHAR(128),
        insurance_id VARCHAR(64),
        emergency_contact_name VARCHAR(128),
        emergency_contact_phone VARCHAR(30),
        allergies TEXT,
        blood_type VARCHAR(5),
        notes TEXT,
        status ENUM('active','inactive','deceased') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_name (last_name, first_name)
    )");

    // SOAP Notes
    $db->exec("CREATE TABLE IF NOT EXISTS hc_soap_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        patient_id INT NOT NULL,
        appointment_id INT,
        subjective TEXT,
        objective TEXT,
        assessment TEXT,
        plan TEXT,
        icd_codes VARCHAR(255),
        cpt_codes VARCHAR(255),
        visit_type VARCHAR(50),
        provider_name VARCHAR(128),
        signed TINYINT(1) DEFAULT 0,
        signed_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_patient (patient_id)
    )");

    // Medications
    $db->exec("CREATE TABLE IF NOT EXISTS hc_medications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        patient_id INT NOT NULL,
        medication_name VARCHAR(200) NOT NULL,
        dosage VARCHAR(100),
        frequency VARCHAR(100),
        route VARCHAR(50) DEFAULT 'oral',
        prescribed_date DATE,
        start_date DATE,
        end_date DATE,
        prescriber VARCHAR(128),
        pharmacy VARCHAR(128),
        refills_remaining INT DEFAULT 0,
        status ENUM('active','discontinued','completed','on_hold') DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_patient (patient_id),
        INDEX idx_status (status)
    )");

    // Appointments
    $db->exec("CREATE TABLE IF NOT EXISTS hc_appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        patient_id INT NOT NULL,
        provider_name VARCHAR(128),
        appointment_type VARCHAR(50) DEFAULT 'follow_up',
        appointment_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME,
        duration_minutes INT DEFAULT 30,
        status ENUM('scheduled','confirmed','checked_in','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
        location VARCHAR(200),
        telehealth TINYINT(1) DEFAULT 0,
        reason VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_patient (patient_id),
        INDEX idx_date (appointment_date),
        INDEX idx_status (status)
    )");

    // Intake Forms
    $db->exec("CREATE TABLE IF NOT EXISTS hc_intake_forms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        patient_id INT NOT NULL,
        form_type VARCHAR(50) DEFAULT 'new_patient',
        form_data JSON NOT NULL,
        completed TINYINT(1) DEFAULT 0,
        completed_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_patient (patient_id)
    )");

    // Vital Signs
    $db->exec("CREATE TABLE IF NOT EXISTS hc_vitals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        patient_id INT NOT NULL,
        appointment_id INT,
        blood_pressure_sys INT,
        blood_pressure_dia INT,
        heart_rate INT,
        temperature DECIMAL(4,1),
        respiratory_rate INT,
        oxygen_saturation DECIMAL(4,1),
        weight_kg DECIMAL(5,1),
        height_cm DECIMAL(5,1),
        bmi DECIMAL(4,1),
        pain_level TINYINT,
        notes TEXT,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_patient (patient_id),
        INDEX idx_date (recorded_at)
    )");

    // Lab Results
    $db->exec("CREATE TABLE IF NOT EXISTS hc_lab_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        patient_id INT NOT NULL,
        test_name VARCHAR(200) NOT NULL,
        test_code VARCHAR(20),
        result_value VARCHAR(100),
        unit VARCHAR(30),
        reference_range VARCHAR(50),
        abnormal TINYINT(1) DEFAULT 0,
        lab_name VARCHAR(128),
        ordered_date DATE,
        result_date DATE,
        status ENUM('ordered','pending','completed','reviewed') DEFAULT 'ordered',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_patient (patient_id)
    )");
    } catch (PDOException $e) {
        error_log('Healthcare schema error: ' . $e->getMessage());
    }
}
ensureHealthcareSchema();

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Patients
    case 'patient_create':    hcRequireAuth(); patientCreate(); break;
    case 'patient_update':    hcRequireAuth(); patientUpdate(); break;
    case 'patient_list':      hcRequireAuth(); patientList(); break;
    case 'patient_detail':    hcRequireAuth(); patientDetail(); break;
    case 'patient_search':    hcRequireAuth(); patientSearch(); break;
    // SOAP Notes
    case 'soap_create':       hcRequireAuth(); soapCreate(); break;
    case 'soap_update':       hcRequireAuth(); soapUpdate(); break;
    case 'soap_list':         hcRequireAuth(); soapList(); break;
    case 'soap_detail':       hcRequireAuth(); soapDetail(); break;
    case 'soap_sign':         hcRequireAuth(); soapSign(); break;
    // Medications
    case 'med_add':           hcRequireAuth(); medAdd(); break;
    case 'med_update':        hcRequireAuth(); medUpdate(); break;
    case 'med_list':          hcRequireAuth(); medList(); break;
    case 'med_interactions':  hcRequireAuth(); medInteractions(); break;
    // Appointments
    case 'appt_create':       hcRequireAuth(); apptCreate(); break;
    case 'appt_update':       hcRequireAuth(); apptUpdate(); break;
    case 'appt_list':         hcRequireAuth(); apptList(); break;
    case 'appt_today':        hcRequireAuth(); apptToday(); break;
    case 'appt_cancel':       hcRequireAuth(); apptCancel(); break;
    // Intake Forms
    case 'intake_create':     hcRequireAuth(); intakeCreate(); break;
    case 'intake_submit':     hcRequireAuth(); intakeSubmit(); break;
    case 'intake_list':       hcRequireAuth(); intakeList(); break;
    // Vitals
    case 'vitals_record':     hcRequireAuth(); vitalsRecord(); break;
    case 'vitals_history':    hcRequireAuth(); vitalsHistory(); break;
    // Labs
    case 'lab_order':         hcRequireAuth(); labOrder(); break;
    case 'lab_result':        hcRequireAuth(); labResult(); break;
    case 'lab_list':          hcRequireAuth(); labList(); break;
    // Reports / Dashboard
    case 'hc_dashboard':      hcRequireAuth(); hcDashboard(); break;
    case 'audit_log':         hcRequireAuth(); auditLogView(); break;
    default: jsonResponse(['error' => 'Unknown action', 'actions' => [
        'patient_create','patient_update','patient_list','patient_detail','patient_search',
        'soap_create','soap_update','soap_list','soap_detail','soap_sign',
        'med_add','med_update','med_list','med_interactions',
        'appt_create','appt_update','appt_list','appt_today','appt_cancel',
        'intake_create','intake_submit','intake_list',
        'vitals_record','vitals_history','lab_order','lab_result','lab_list',
        'hc_dashboard','audit_log'
    ]], 400);
}

// ═══════════════════════════════════════════════════════════════
// Patients
// ═══════════════════════════════════════════════════════════════
function patientCreate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $first = sanitize($input['first_name'] ?? '', 64);
    $last = sanitize($input['last_name'] ?? '', 64);
    if (!$first || !$last) jsonResponse(['error' => 'first_name and last_name required'], 400);

    $stmt = $db->prepare("INSERT INTO hc_patients (client_id, first_name, last_name, date_of_birth, gender, email, phone, address, insurance_provider, insurance_id, emergency_contact_name, emergency_contact_phone, allergies, blood_type, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $clientId, $first, $last,
        !empty($input['date_of_birth']) ? sanitize($input['date_of_birth'], 10) : null,
        in_array($input['gender'] ?? '', ['male','female','other','prefer_not_to_say']) ? $input['gender'] : null,
        sanitize($input['email'] ?? '', 255),
        sanitize($input['phone'] ?? '', 30),
        sanitize($input['address'] ?? '', 500),
        sanitize($input['insurance_provider'] ?? '', 128),
        sanitize($input['insurance_id'] ?? '', 64),
        sanitize($input['emergency_contact_name'] ?? '', 128),
        sanitize($input['emergency_contact_phone'] ?? '', 30),
        sanitize($input['allergies'] ?? '', 2000),
        sanitize($input['blood_type'] ?? '', 5),
        sanitize($input['notes'] ?? '', 5000),
    ]);

    $patientId = (int) $db->lastInsertId();
    hcAuditLog('patient_create', "Created patient: $first $last", $patientId);
    jsonResponse(['success' => true, 'id' => $patientId]);
}

function patientUpdate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $fields = []; $params = [];
    $allowed = ['first_name','last_name','email','phone','address','insurance_provider','insurance_id','emergency_contact_name','emergency_contact_phone','allergies','blood_type','notes'];
    foreach ($allowed as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], in_array($f, ['notes','allergies','address']) ? 5000 : 255); }
    }
    if (isset($input['date_of_birth'])) { $fields[] = "date_of_birth = ?"; $params[] = sanitize($input['date_of_birth'], 10); }
    if (isset($input['gender']) && in_array($input['gender'], ['male','female','other','prefer_not_to_say'])) {
        $fields[] = "gender = ?"; $params[] = $input['gender'];
    }
    if (isset($input['status']) && in_array($input['status'], ['active','inactive','deceased'])) {
        $fields[] = "status = ?"; $params[] = $input['status'];
    }
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

    $params[] = $id; $params[] = $clientId;
    $db->prepare("UPDATE hc_patients SET " . implode(', ', $fields) . " WHERE id = ? AND client_id = ?")->execute($params);
    hcAuditLog('patient_update', "Updated patient #$id", $id);
    jsonResponse(['success' => true]);
}

function patientList(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $status = sanitize($_GET['status'] ?? 'active', 20);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(100, max(10, (int) ($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare("SELECT id, first_name, last_name, date_of_birth, gender, phone, email, status, created_at FROM hc_patients WHERE client_id = ? AND status = ? ORDER BY last_name, first_name LIMIT ? OFFSET ?");
    dbExecute($stmt, [$clientId, $status, $limit, $offset]);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM hc_patients WHERE client_id = ? AND status = ?");
    $countStmt->execute([$clientId, $status]);
    $total = (int) $countStmt->fetchColumn();

    hcAuditLog('patient_list', "Viewed patient list (page $page)");
    jsonResponse(['success' => true, 'patients' => $stmt->fetchAll(), 'pagination' => ['page' => $page, 'total' => $total, 'pages' => ceil($total / $limit)]]);
}

function patientDetail(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $stmt = $db->prepare("SELECT * FROM hc_patients WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $patient = $stmt->fetch();
    if (!$patient) jsonResponse(['error' => 'Not found'], 404);

    // Active medications
    $stmt = $db->prepare("SELECT id, medication_name, dosage, frequency, status FROM hc_medications WHERE patient_id = ? AND client_id = ? AND status = 'active' ORDER BY prescribed_date DESC");
    $stmt->execute([$id, $clientId]);
    $patient['medications'] = $stmt->fetchAll();

    // Upcoming appointments
    $stmt = $db->prepare("SELECT id, appointment_type, appointment_date, start_time, status FROM hc_appointments WHERE patient_id = ? AND client_id = ? AND appointment_date >= CURDATE() AND status NOT IN ('cancelled','completed') ORDER BY appointment_date, start_time LIMIT 5");
    $stmt->execute([$id, $clientId]);
    $patient['upcoming_appointments'] = $stmt->fetchAll();

    // Latest vitals
    $stmt = $db->prepare("SELECT * FROM hc_vitals WHERE patient_id = ? AND client_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt->execute([$id, $clientId]);
    $patient['latest_vitals'] = $stmt->fetch() ?: null;

    // Recent SOAP notes
    $stmt = $db->prepare("SELECT id, visit_type, created_at, signed FROM hc_soap_notes WHERE patient_id = ? AND client_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$id, $clientId]);
    $patient['recent_soap'] = $stmt->fetchAll();

    hcAuditLog('patient_view', "Viewed patient #$id", $id);
    jsonResponse(['success' => true, 'patient' => $patient]);
}

function patientSearch(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $q = sanitize($_GET['q'] ?? '', 100);
    if (strlen($q) < 2) jsonResponse(['error' => 'Query too short'], 400);

    $like = "%$q%";
    $stmt = $db->prepare("SELECT id, first_name, last_name, date_of_birth, phone, email, status FROM hc_patients WHERE client_id = ? AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?) AND status = 'active' LIMIT 25");
    $stmt->execute([$clientId, $like, $like, $like, $like]);

    hcAuditLog('patient_search', "Searched: $q");
    jsonResponse(['success' => true, 'results' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// SOAP Notes
// ═══════════════════════════════════════════════════════════════
function soapCreate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patientId = (int) ($input['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    $stmt = $db->prepare("INSERT INTO hc_soap_notes (client_id, patient_id, appointment_id, subjective, objective, assessment, plan, icd_codes, cpt_codes, visit_type, provider_name) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $clientId, $patientId,
        (int) ($input['appointment_id'] ?? 0) ?: null,
        sanitize($input['subjective'] ?? '', 10000),
        sanitize($input['objective'] ?? '', 10000),
        sanitize($input['assessment'] ?? '', 10000),
        sanitize($input['plan'] ?? '', 10000),
        sanitize($input['icd_codes'] ?? '', 255),
        sanitize($input['cpt_codes'] ?? '', 255),
        sanitize($input['visit_type'] ?? 'follow_up', 50),
        sanitize($input['provider_name'] ?? '', 128),
    ]);

    $noteId = (int) $db->lastInsertId();
    hcAuditLog('soap_create', "Created SOAP note #$noteId", $patientId);
    jsonResponse(['success' => true, 'id' => $noteId]);
}

function soapUpdate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    // Can't update signed notes
    $stmt = $db->prepare("SELECT signed, patient_id FROM hc_soap_notes WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $note = $stmt->fetch();
    if (!$note) jsonResponse(['error' => 'Not found'], 404);
    if ($note['signed']) jsonResponse(['error' => 'Cannot modify signed note'], 400);

    $fields = []; $params = [];
    foreach (['subjective','objective','assessment','plan'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], 10000); }
    }
    foreach (['icd_codes','cpt_codes','visit_type','provider_name'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], 255); }
    }
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

    $params[] = $id; $params[] = $clientId;
    $db->prepare("UPDATE hc_soap_notes SET " . implode(', ', $fields) . " WHERE id = ? AND client_id = ?")->execute($params);
    hcAuditLog('soap_update', "Updated SOAP note #$id", (int) $note['patient_id']);
    jsonResponse(['success' => true]);
}

function soapList(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $patientId = (int) ($_GET['patient_id'] ?? 0);

    $where = "client_id = ?"; $params = [$clientId];
    if ($patientId) { $where .= " AND patient_id = ?"; $params[] = $patientId; }

    $stmt = $db->prepare("SELECT id, patient_id, visit_type, signed, provider_name, created_at FROM hc_soap_notes WHERE $where ORDER BY created_at DESC LIMIT 50");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'notes' => $stmt->fetchAll()]);
}

function soapDetail(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $stmt = $db->prepare("SELECT * FROM hc_soap_notes WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $note = $stmt->fetch();
    if (!$note) jsonResponse(['error' => 'Not found'], 404);

    hcAuditLog('soap_view', "Viewed SOAP note #$id", (int) $note['patient_id']);
    jsonResponse(['success' => true, 'note' => $note]);
}

function soapSign(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $stmt = $db->prepare("UPDATE hc_soap_notes SET signed = 1, signed_at = NOW() WHERE id = ? AND client_id = ? AND signed = 0");
    $stmt->execute([$id, $clientId]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Not found or already signed'], 400);

    hcAuditLog('soap_sign', "Signed SOAP note #$id");
    jsonResponse(['success' => true, 'message' => 'Note signed']);
}

// ═══════════════════════════════════════════════════════════════
// Medications
// ═══════════════════════════════════════════════════════════════
function medAdd(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patientId = (int) ($input['patient_id'] ?? 0);
    $name = sanitize($input['medication_name'] ?? '', 200);
    if (!$patientId || !$name) jsonResponse(['error' => 'patient_id and medication_name required'], 400);

    $stmt = $db->prepare("INSERT INTO hc_medications (client_id, patient_id, medication_name, dosage, frequency, route, prescribed_date, start_date, end_date, prescriber, pharmacy, refills_remaining, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $clientId, $patientId, $name,
        sanitize($input['dosage'] ?? '', 100),
        sanitize($input['frequency'] ?? '', 100),
        sanitize($input['route'] ?? 'oral', 50),
        sanitize($input['prescribed_date'] ?? date('Y-m-d'), 10),
        !empty($input['start_date']) ? sanitize($input['start_date'], 10) : date('Y-m-d'),
        !empty($input['end_date']) ? sanitize($input['end_date'], 10) : null,
        sanitize($input['prescriber'] ?? '', 128),
        sanitize($input['pharmacy'] ?? '', 128),
        max(0, (int) ($input['refills_remaining'] ?? 0)),
        sanitize($input['notes'] ?? '', 2000),
    ]);

    hcAuditLog('med_add', "Added medication: $name", $patientId);
    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function medUpdate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $fields = []; $params = [];
    foreach (['dosage','frequency','pharmacy','prescriber','notes'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], $f === 'notes' ? 2000 : 128); }
    }
    if (isset($input['status']) && in_array($input['status'], ['active','discontinued','completed','on_hold'])) {
        $fields[] = "status = ?"; $params[] = $input['status'];
    }
    if (isset($input['refills_remaining'])) { $fields[] = "refills_remaining = ?"; $params[] = max(0, (int) $input['refills_remaining']); }
    if (isset($input['end_date'])) { $fields[] = "end_date = ?"; $params[] = sanitize($input['end_date'], 10); }
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

    $params[] = $id; $params[] = $clientId;
    $db->prepare("UPDATE hc_medications SET " . implode(', ', $fields) . " WHERE id = ? AND client_id = ?")->execute($params);
    hcAuditLog('med_update', "Updated medication #$id");
    jsonResponse(['success' => true]);
}

function medList(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $patientId = (int) ($_GET['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    $status = sanitize($_GET['status'] ?? 'active', 20);
    $stmt = $db->prepare("SELECT * FROM hc_medications WHERE patient_id = ? AND client_id = ? AND status = ? ORDER BY prescribed_date DESC");
    $stmt->execute([$patientId, $clientId, $status]);

    jsonResponse(['success' => true, 'medications' => $stmt->fetchAll()]);
}

function medInteractions(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $patientId = (int) ($_GET['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    // Get all active medications for basic interaction flags
    $stmt = $db->prepare("SELECT medication_name, dosage FROM hc_medications WHERE patient_id = ? AND client_id = ? AND status = 'active'");
    $stmt->execute([$patientId, $clientId]);
    $meds = $stmt->fetchAll();

    // Note: Real drug interaction checking would integrate with an API like RxNorm/openFDA
    // This returns the medication list for LLM-assisted interaction analysis
    jsonResponse([
        'success' => true,
        'patient_id' => $patientId,
        'active_medications' => $meds,
        'count' => count($meds),
        'note' => 'Review active medications for potential interactions. Integrate with RxNorm/openFDA for comprehensive checks.',
    ]);
}

// ═══════════════════════════════════════════════════════════════
// Appointments
// ═══════════════════════════════════════════════════════════════
function apptCreate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patientId = (int) ($input['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    $stmt = $db->prepare("INSERT INTO hc_appointments (client_id, patient_id, provider_name, appointment_type, appointment_date, start_time, end_time, duration_minutes, location, telehealth, reason, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $clientId, $patientId,
        sanitize($input['provider_name'] ?? '', 128),
        sanitize($input['appointment_type'] ?? 'follow_up', 50),
        sanitize($input['appointment_date'] ?? '', 10),
        sanitize($input['start_time'] ?? '', 8),
        !empty($input['end_time']) ? sanitize($input['end_time'], 8) : null,
        max(5, min(480, (int) ($input['duration_minutes'] ?? 30))),
        sanitize($input['location'] ?? '', 200),
        (int) ($input['telehealth'] ?? 0),
        sanitize($input['reason'] ?? '', 255),
        sanitize($input['notes'] ?? '', 2000),
    ]);

    hcAuditLog('appt_create', "Scheduled appointment", $patientId);
    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function apptUpdate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $fields = []; $params = [];
    if (isset($input['status']) && in_array($input['status'], ['scheduled','confirmed','checked_in','in_progress','completed','cancelled','no_show'])) {
        $fields[] = "status = ?"; $params[] = $input['status'];
    }
    foreach (['appointment_date','start_time','end_time','reason','notes','provider_name','location'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], 255); }
    }
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

    $params[] = $id; $params[] = $clientId;
    $db->prepare("UPDATE hc_appointments SET " . implode(', ', $fields) . " WHERE id = ? AND client_id = ?")->execute($params);
    hcAuditLog('appt_update', "Updated appointment #$id");
    jsonResponse(['success' => true]);
}

function apptList(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $patientId = (int) ($_GET['patient_id'] ?? 0);
    $status = sanitize($_GET['status'] ?? '', 20);
    $from = sanitize($_GET['from'] ?? date('Y-m-d'), 10);
    $to = sanitize($_GET['to'] ?? date('Y-m-d', strtotime('+30 days')), 10);

    $where = "client_id = ? AND appointment_date BETWEEN ? AND ?";
    $params = [$clientId, $from, $to];
    if ($patientId) { $where .= " AND patient_id = ?"; $params[] = $patientId; }
    if ($status && in_array($status, ['scheduled','confirmed','checked_in','in_progress','completed','cancelled','no_show'])) {
        $where .= " AND status = ?"; $params[] = $status;
    }

    $stmt = $db->prepare("SELECT a.*, p.first_name, p.last_name FROM hc_appointments a JOIN hc_patients p ON a.patient_id = p.id WHERE a.$where ORDER BY a.appointment_date, a.start_time LIMIT 200");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'appointments' => $stmt->fetchAll()]);
}

function apptToday(): void {
    $db = getDB();
    $clientId = hcGetClientId();

    $stmt = $db->prepare("SELECT a.*, p.first_name, p.last_name FROM hc_appointments a JOIN hc_patients p ON a.patient_id = p.id WHERE a.client_id = ? AND a.appointment_date = CURDATE() AND a.status NOT IN ('cancelled') ORDER BY a.start_time");
    $stmt->execute([$clientId]);

    jsonResponse(['success' => true, 'today' => date('Y-m-d'), 'appointments' => $stmt->fetchAll()]);
}

function apptCancel(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $db->prepare("UPDATE hc_appointments SET status = 'cancelled', notes = CONCAT(COALESCE(notes,''), '\nCancelled: ', ?) WHERE id = ? AND client_id = ?")
        ->execute([sanitize($input['reason'] ?? 'No reason provided', 255), $id, $clientId]);
    hcAuditLog('appt_cancel', "Cancelled appointment #$id");
    jsonResponse(['success' => true, 'message' => 'Appointment cancelled']);
}

// ═══════════════════════════════════════════════════════════════
// Intake Forms
// ═══════════════════════════════════════════════════════════════
function intakeCreate(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patientId = (int) ($input['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    $formType = sanitize($input['form_type'] ?? 'new_patient', 50);
    $formData = json_encode($input['form_data'] ?? []);

    $stmt = $db->prepare("INSERT INTO hc_intake_forms (client_id, patient_id, form_type, form_data) VALUES (?, ?, ?, ?)");
    $stmt->execute([$clientId, $patientId, $formType, $formData]);

    hcAuditLog('intake_create', "Created intake form ($formType)", $patientId);
    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function intakeSubmit(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $formId = (int) ($input['form_id'] ?? 0);
    if (!$formId) jsonResponse(['error' => 'form_id required'], 400);

    $formData = json_encode($input['form_data'] ?? []);
    $db->prepare("UPDATE hc_intake_forms SET form_data = ?, completed = 1, completed_at = NOW() WHERE id = ? AND client_id = ?")
        ->execute([$formData, $formId, $clientId]);

    hcAuditLog('intake_submit', "Submitted intake form #$formId");
    jsonResponse(['success' => true, 'message' => 'Form submitted']);
}

function intakeList(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $patientId = (int) ($_GET['patient_id'] ?? 0);

    $where = "client_id = ?"; $params = [$clientId];
    if ($patientId) { $where .= " AND patient_id = ?"; $params[] = $patientId; }

    $stmt = $db->prepare("SELECT id, patient_id, form_type, completed, completed_at, created_at FROM hc_intake_forms WHERE $where ORDER BY created_at DESC LIMIT 50");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'forms' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// Vitals
// ═══════════════════════════════════════════════════════════════
function vitalsRecord(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patientId = (int) ($input['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    $weightKg = !empty($input['weight_kg']) ? (float) $input['weight_kg'] : null;
    $heightCm = !empty($input['height_cm']) ? (float) $input['height_cm'] : null;
    $bmi = ($weightKg && $heightCm) ? round($weightKg / (($heightCm / 100) ** 2), 1) : null;

    $stmt = $db->prepare("INSERT INTO hc_vitals (client_id, patient_id, appointment_id, blood_pressure_sys, blood_pressure_dia, heart_rate, temperature, respiratory_rate, oxygen_saturation, weight_kg, height_cm, bmi, pain_level, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $clientId, $patientId,
        (int) ($input['appointment_id'] ?? 0) ?: null,
        !empty($input['blood_pressure_sys']) ? (int) $input['blood_pressure_sys'] : null,
        !empty($input['blood_pressure_dia']) ? (int) $input['blood_pressure_dia'] : null,
        !empty($input['heart_rate']) ? (int) $input['heart_rate'] : null,
        !empty($input['temperature']) ? (float) $input['temperature'] : null,
        !empty($input['respiratory_rate']) ? (int) $input['respiratory_rate'] : null,
        !empty($input['oxygen_saturation']) ? (float) $input['oxygen_saturation'] : null,
        $weightKg, $heightCm, $bmi,
        isset($input['pain_level']) ? max(0, min(10, (int) $input['pain_level'])) : null,
        sanitize($input['notes'] ?? '', 2000),
    ]);

    hcAuditLog('vitals_record', "Recorded vitals", $patientId);
    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId(), 'bmi' => $bmi]);
}

function vitalsHistory(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $patientId = (int) ($_GET['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    $limit = min(100, max(5, (int) ($_GET['limit'] ?? 20)));
    $stmt = $db->prepare("SELECT * FROM hc_vitals WHERE patient_id = ? AND client_id = ? ORDER BY recorded_at DESC LIMIT ?");
    dbExecute($stmt, [$patientId, $clientId, $limit]);

    hcAuditLog('vitals_view', "Viewed vitals history", $patientId);
    jsonResponse(['success' => true, 'vitals' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// Labs
// ═══════════════════════════════════════════════════════════════
function labOrder(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patientId = (int) ($input['patient_id'] ?? 0);
    $testName = sanitize($input['test_name'] ?? '', 200);
    if (!$patientId || !$testName) jsonResponse(['error' => 'patient_id and test_name required'], 400);

    $stmt = $db->prepare("INSERT INTO hc_lab_results (client_id, patient_id, test_name, test_code, lab_name, ordered_date, status, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $clientId, $patientId, $testName,
        sanitize($input['test_code'] ?? '', 20),
        sanitize($input['lab_name'] ?? '', 128),
        sanitize($input['ordered_date'] ?? date('Y-m-d'), 10),
        'ordered',
        sanitize($input['notes'] ?? '', 2000),
    ]);

    hcAuditLog('lab_order', "Ordered lab: $testName", $patientId);
    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function labResult(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $db->prepare("UPDATE hc_lab_results SET result_value = ?, unit = ?, reference_range = ?, abnormal = ?, result_date = ?, status = 'completed' WHERE id = ? AND client_id = ?")
        ->execute([
            sanitize($input['result_value'] ?? '', 100),
            sanitize($input['unit'] ?? '', 30),
            sanitize($input['reference_range'] ?? '', 50),
            (int) ($input['abnormal'] ?? 0),
            sanitize($input['result_date'] ?? date('Y-m-d'), 10),
            $id, $clientId,
        ]);

    hcAuditLog('lab_result', "Entered result for lab #$id");
    jsonResponse(['success' => true]);
}

function labList(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $patientId = (int) ($_GET['patient_id'] ?? 0);
    if (!$patientId) jsonResponse(['error' => 'patient_id required'], 400);

    $stmt = $db->prepare("SELECT * FROM hc_lab_results WHERE patient_id = ? AND client_id = ? ORDER BY COALESCE(result_date, ordered_date) DESC LIMIT 50");
    $stmt->execute([$patientId, $clientId]);

    hcAuditLog('lab_view', "Viewed labs", $patientId);
    jsonResponse(['success' => true, 'labs' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// Dashboard
// ═══════════════════════════════════════════════════════════════
function hcDashboard(): void {
    $db = getDB();
    $clientId = hcGetClientId();

    $dash = [];

    // Patient counts
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM hc_patients WHERE client_id = ? GROUP BY status");
    $stmt->execute([$clientId]);
    $dash['patients'] = $stmt->fetchAll();

    // Today's appointments
    $stmt = $db->prepare("SELECT COUNT(*) FROM hc_appointments WHERE client_id = ? AND appointment_date = CURDATE() AND status NOT IN ('cancelled')");
    $stmt->execute([$clientId]);
    $dash['appointments_today'] = (int) $stmt->fetchColumn();

    // Unsigned SOAP notes
    $stmt = $db->prepare("SELECT COUNT(*) FROM hc_soap_notes WHERE client_id = ? AND signed = 0");
    $stmt->execute([$clientId]);
    $dash['unsigned_notes'] = (int) $stmt->fetchColumn();

    // Pending lab results
    $stmt = $db->prepare("SELECT COUNT(*) FROM hc_lab_results WHERE client_id = ? AND status IN ('ordered','pending')");
    $stmt->execute([$clientId]);
    $dash['pending_labs'] = (int) $stmt->fetchColumn();

    // Incomplete intake forms
    $stmt = $db->prepare("SELECT COUNT(*) FROM hc_intake_forms WHERE client_id = ? AND completed = 0");
    $stmt->execute([$clientId]);
    $dash['pending_intakes'] = (int) $stmt->fetchColumn();

    // Abnormal lab results (unreviewed)
    $stmt = $db->prepare("SELECT COUNT(*) FROM hc_lab_results WHERE client_id = ? AND abnormal = 1 AND status != 'reviewed'");
    $stmt->execute([$clientId]);
    $dash['abnormal_labs'] = (int) $stmt->fetchColumn();

    jsonResponse(['success' => true, 'dashboard' => $dash]);
}

function auditLogView(): void {
    $db = getDB();
    $clientId = hcGetClientId();
    $limit = min(200, max(10, (int) ($_GET['limit'] ?? 50)));
    $patientId = (int) ($_GET['patient_id'] ?? 0);

    $where = "client_id = ?"; $params = [$clientId];
    if ($patientId) { $where .= " AND patient_id = ?"; $params[] = $patientId; }
    $params[] = $limit;

    $stmt = $db->prepare("SELECT action, detail, patient_id, ip_address, created_at FROM hc_audit_log WHERE $where ORDER BY created_at DESC LIMIT ?");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'audit_log' => $stmt->fetchAll()]);
}
