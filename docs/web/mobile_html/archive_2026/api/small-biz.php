<?php
/**
 * Small Business Tools API — CRM, Time Tracking, Invoicing, Project Management
 * Real DB-backed operations for small business features
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

function bizIsInternal(): bool {
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}
function bizRequireAuth(): void {
    if (bizIsInternal()) return;
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}
function bizGetClientId(): int {
    if (bizIsInternal()) {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        return (int) ($body['client_id'] ?? $_SESSION['client_id'] ?? 0);
    }
    return (int) ($_SESSION['client_id'] ?? 0);
}

// ─── Schema ───────────────────────────────────────────────────
function ensureBizSchema(): void {
    $db = getDB();
    try {

    // CRM Contacts
    $db->exec("CREATE TABLE IF NOT EXISTS biz_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        first_name VARCHAR(64) NOT NULL,
        last_name VARCHAR(64),
        email VARCHAR(255),
        phone VARCHAR(30),
        company VARCHAR(128),
        title VARCHAR(64),
        source VARCHAR(50) DEFAULT 'manual',
        status ENUM('lead','prospect','customer','churned','inactive') DEFAULT 'lead',
        tags VARCHAR(255),
        notes TEXT,
        last_contacted DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status),
        INDEX idx_email (email)
    )");

    // CRM Activities
    $db->exec("CREATE TABLE IF NOT EXISTS biz_activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        contact_id INT,
        activity_type ENUM('call','email','meeting','note','task','follow_up') NOT NULL,
        subject VARCHAR(200),
        description TEXT,
        due_date DATETIME,
        completed TINYINT(1) DEFAULT 0,
        completed_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_contact (contact_id)
    )");

    // Time Tracking
    $db->exec("CREATE TABLE IF NOT EXISTS biz_time_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        project_id INT,
        task_description VARCHAR(255),
        hours DECIMAL(6,2) NOT NULL,
        rate DECIMAL(10,2) DEFAULT 0,
        billable TINYINT(1) DEFAULT 1,
        entry_date DATE NOT NULL,
        start_time TIME,
        end_time TIME,
        notes TEXT,
        invoiced TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_project (project_id),
        INDEX idx_date (entry_date)
    )");

    // Projects
    $db->exec("CREATE TABLE IF NOT EXISTS biz_projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        name VARCHAR(128) NOT NULL,
        description TEXT,
        contact_id INT,
        status ENUM('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
        priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
        budget DECIMAL(12,2),
        spent DECIMAL(12,2) DEFAULT 0,
        start_date DATE,
        due_date DATE,
        completed_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    )");

    // Tasks
    $db->exec("CREATE TABLE IF NOT EXISTS biz_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        project_id INT,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
        status ENUM('todo','in_progress','review','done','cancelled') DEFAULT 'todo',
        assignee VARCHAR(100),
        due_date DATE,
        completed_at DATETIME,
        estimated_hours DECIMAL(6,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_project (project_id),
        INDEX idx_status (status)
    )");

    // Invoices
    $db->exec("CREATE TABLE IF NOT EXISTS biz_invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        invoice_number VARCHAR(30) UNIQUE,
        contact_id INT,
        project_id INT,
        status ENUM('draft','sent','viewed','paid','overdue','cancelled') DEFAULT 'draft',
        issue_date DATE NOT NULL,
        due_date DATE,
        subtotal DECIMAL(12,2) DEFAULT 0,
        tax_rate DECIMAL(5,2) DEFAULT 0,
        tax_amount DECIMAL(12,2) DEFAULT 0,
        total DECIMAL(12,2) DEFAULT 0,
        notes TEXT,
        paid_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status),
        INDEX idx_number (invoice_number)
    )");

    // Invoice Line Items
    $db->exec("CREATE TABLE IF NOT EXISTS biz_invoice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,2) DEFAULT 1,
        unit_price DECIMAL(12,2) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        time_entry_id INT,
        INDEX idx_invoice (invoice_id)
    )");
    } catch (PDOException $e) {
        error_log('Small-biz schema error: ' . $e->getMessage());
    }
}
ensureBizSchema();

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // CRM
    case 'contacts_list':       bizRequireAuth(); contactsList(); break;
    case 'contact_create':      bizRequireAuth(); contactCreate(); break;
    case 'contact_update':      bizRequireAuth(); contactUpdate(); break;
    case 'contact_detail':      bizRequireAuth(); contactDetail(); break;
    case 'contact_search':      bizRequireAuth(); contactSearch(); break;
    case 'activity_log':        bizRequireAuth(); activityLog(); break;
    case 'activity_create':     bizRequireAuth(); activityCreate(); break;
    // Time Tracking
    case 'time_log':            bizRequireAuth(); timeLog(); break;
    case 'time_create':         bizRequireAuth(); timeCreate(); break;
    case 'time_summary':        bizRequireAuth(); timeSummary(); break;
    // Projects
    case 'projects_list':       bizRequireAuth(); projectsList(); break;
    case 'project_create':      bizRequireAuth(); projectCreate(); break;
    case 'project_update':      bizRequireAuth(); projectUpdate(); break;
    case 'project_detail':      bizRequireAuth(); projectDetail(); break;
    // Tasks
    case 'tasks_list':          bizRequireAuth(); tasksList(); break;
    case 'task_create':         bizRequireAuth(); taskCreate(); break;
    case 'task_update':         bizRequireAuth(); taskUpdate(); break;
    // Invoices
    case 'invoice_create':      bizRequireAuth(); invoiceCreate(); break;
    case 'invoice_list':        bizRequireAuth(); invoiceList(); break;
    case 'invoice_detail':      bizRequireAuth(); invoiceDetail(); break;
    case 'invoice_send':        bizRequireAuth(); invoiceSend(); break;
    case 'invoice_from_time':   bizRequireAuth(); invoiceFromTime(); break;
    // Dashboard
    case 'biz_dashboard':       bizRequireAuth(); bizDashboard(); break;
    default: jsonResponse(['error' => 'Unknown action', 'actions' => [
        'contacts_list','contact_create','contact_update','contact_detail','contact_search',
        'activity_log','activity_create','time_log','time_create','time_summary',
        'projects_list','project_create','project_update','project_detail',
        'tasks_list','task_create','task_update',
        'invoice_create','invoice_list','invoice_detail','invoice_send','invoice_from_time',
        'biz_dashboard'
    ]], 400);
}

// ═══════════════════════════════════════════════════════════════
// CRM
// ═══════════════════════════════════════════════════════════════
function contactsList(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = $_GET;
    $status = sanitize($input['status'] ?? '', 20);
    $page = max(1, (int) ($input['page'] ?? 1));
    $limit = min(100, max(10, (int) ($input['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;

    $where = "client_id = ?";
    $params = [$clientId];
    if ($status && in_array($status, ['lead','prospect','customer','churned','inactive'])) {
        $where .= " AND status = ?";
        $params[] = $status;
    }
    $params[] = $limit; $params[] = $offset;
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, phone, company, status, tags, last_contacted, created_at FROM biz_contacts WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
    dbExecute($stmt, $params);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM biz_contacts WHERE client_id = ?" . ($status ? " AND status = ?" : ""));
    $countStmt->execute($status ? [$clientId, $status] : [$clientId]);
    $total = (int) $countStmt->fetchColumn();

    jsonResponse(['success' => true, 'contacts' => $stmt->fetchAll(), 'pagination' => ['page' => $page, 'total' => $total, 'pages' => ceil($total / $limit)]]);
}

function contactCreate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $first = sanitize($input['first_name'] ?? '', 64);
    if (!$first) jsonResponse(['error' => 'first_name required'], 400);

    $stmt = $db->prepare("INSERT INTO biz_contacts (client_id, first_name, last_name, email, phone, company, title, source, status, tags, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientId, $first,
        sanitize($input['last_name'] ?? '', 64),
        sanitize($input['email'] ?? '', 255),
        sanitize($input['phone'] ?? '', 30),
        sanitize($input['company'] ?? '', 128),
        sanitize($input['title'] ?? '', 64),
        sanitize($input['source'] ?? 'manual', 50),
        in_array($input['status'] ?? '', ['lead','prospect','customer','churned','inactive']) ? $input['status'] : 'lead',
        sanitize($input['tags'] ?? '', 255),
        sanitize($input['notes'] ?? '', 5000),
    ]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function contactUpdate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $fields = [];
    $params = [];
    foreach (['first_name','last_name','email','phone','company','title','tags','notes'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], $f === 'notes' ? 5000 : 255); }
    }
    if (isset($input['status']) && in_array($input['status'], ['lead','prospect','customer','churned','inactive'])) {
        $fields[] = "status = ?"; $params[] = $input['status'];
    }
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

    $params[] = $id; $params[] = $clientId;
    $db->prepare("UPDATE biz_contacts SET " . implode(', ', $fields) . " WHERE id = ? AND client_id = ?")->execute($params);
    jsonResponse(['success' => true]);
}

function contactDetail(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $stmt = $db->prepare("SELECT * FROM biz_contacts WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $contact = $stmt->fetch();
    if (!$contact) jsonResponse(['error' => 'Not found'], 404);

    // Recent activities
    $stmt = $db->prepare("SELECT * FROM biz_activities WHERE contact_id = ? AND client_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$id, $clientId]);
    $contact['activities'] = $stmt->fetchAll();

    // Projects
    $stmt = $db->prepare("SELECT id, name, status FROM biz_projects WHERE contact_id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $contact['projects'] = $stmt->fetchAll();

    jsonResponse(['success' => true, 'contact' => $contact]);
}

function contactSearch(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $q = sanitize($_GET['q'] ?? '', 100);
    if (strlen($q) < 2) jsonResponse(['error' => 'Query too short'], 400);

    $like = "%$q%";
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, company, status FROM biz_contacts WHERE client_id = ? AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company LIKE ?) LIMIT 25");
    $stmt->execute([$clientId, $like, $like, $like, $like]);
    jsonResponse(['success' => true, 'results' => $stmt->fetchAll()]);
}

function activityLog(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $contactId = (int) ($_GET['contact_id'] ?? 0);
    $limit = min(50, max(10, (int) ($_GET['limit'] ?? 25)));

    $where = "client_id = ?";
    $params = [$clientId];
    if ($contactId) { $where .= " AND contact_id = ?"; $params[] = $contactId; }
    $params[] = $limit;

    $stmt = $db->prepare("SELECT * FROM biz_activities WHERE $where ORDER BY created_at DESC LIMIT ?");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'activities' => $stmt->fetchAll()]);
}

function activityCreate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $type = in_array($input['activity_type'] ?? '', ['call','email','meeting','note','task','follow_up']) ? $input['activity_type'] : 'note';
    $stmt = $db->prepare("INSERT INTO biz_activities (client_id, contact_id, activity_type, subject, description, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientId,
        (int) ($input['contact_id'] ?? 0) ?: null,
        $type,
        sanitize($input['subject'] ?? '', 200),
        sanitize($input['description'] ?? '', 5000),
        !empty($input['due_date']) ? sanitize($input['due_date'], 20) : null,
    ]);

    // Update last_contacted
    if (!empty($input['contact_id'])) {
        $db->prepare("UPDATE biz_contacts SET last_contacted = NOW() WHERE id = ? AND client_id = ?")->execute([(int)$input['contact_id'], $clientId]);
    }

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

// ═══════════════════════════════════════════════════════════════
// Time Tracking
// ═══════════════════════════════════════════════════════════════
function timeLog(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = $_GET;
    $projectId = (int) ($input['project_id'] ?? 0);
    $days = min(365, max(1, (int) ($input['days'] ?? 30)));

    $where = "client_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
    $params = [$clientId, $days];
    if ($projectId) { $where .= " AND project_id = ?"; $params[] = $projectId; }

    $stmt = $db->prepare("SELECT * FROM biz_time_entries WHERE $where ORDER BY entry_date DESC, start_time DESC LIMIT 200");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'entries' => $stmt->fetchAll()]);
}

function timeCreate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $hours = max(0.01, min(24, (float) ($input['hours'] ?? 0)));
    $date = sanitize($input['entry_date'] ?? date('Y-m-d'), 10);

    $stmt = $db->prepare("INSERT INTO biz_time_entries (client_id, project_id, task_description, hours, rate, billable, entry_date, start_time, end_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientId,
        (int) ($input['project_id'] ?? 0) ?: null,
        sanitize($input['task_description'] ?? '', 255),
        $hours,
        (float) ($input['rate'] ?? 0),
        (int) ($input['billable'] ?? 1),
        $date,
        !empty($input['start_time']) ? sanitize($input['start_time'], 8) : null,
        !empty($input['end_time']) ? sanitize($input['end_time'], 8) : null,
        sanitize($input['notes'] ?? '', 2000),
    ]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId(), 'hours' => $hours]);
}

function timeSummary(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = $_GET;
    $days = min(365, max(1, (int) ($input['days'] ?? 30)));

    // Total hours
    $stmt = $db->prepare("SELECT COALESCE(SUM(hours), 0) as total_hours, COALESCE(SUM(CASE WHEN billable = 1 THEN hours ELSE 0 END), 0) as billable_hours, COALESCE(SUM(CASE WHEN billable = 1 THEN hours * rate ELSE 0 END), 0) as billable_amount, COUNT(*) as entries FROM biz_time_entries WHERE client_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
    $stmt->execute([$clientId, $days]);
    $summary = $stmt->fetch();

    // By project
    $stmt = $db->prepare("SELECT t.project_id, COALESCE(p.name, 'No Project') as project_name, SUM(t.hours) as hours, SUM(CASE WHEN t.billable = 1 THEN t.hours * t.rate ELSE 0 END) as value FROM biz_time_entries t LEFT JOIN biz_projects p ON t.project_id = p.id WHERE t.client_id = ? AND t.entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY t.project_id ORDER BY hours DESC");
    $stmt->execute([$clientId, $days]);
    $summary['by_project'] = $stmt->fetchAll();

    // Daily breakdown
    $stmt = $db->prepare("SELECT entry_date, SUM(hours) as hours FROM biz_time_entries WHERE client_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY entry_date ORDER BY entry_date");
    $stmt->execute([$clientId, $days]);
    $summary['daily'] = $stmt->fetchAll();

    jsonResponse(['success' => true, 'summary' => $summary, 'period_days' => $days]);
}

// ═══════════════════════════════════════════════════════════════
// Projects
// ═══════════════════════════════════════════════════════════════
function projectsList(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $status = sanitize($_GET['status'] ?? '', 20);

    $where = "client_id = ?";
    $params = [$clientId];
    if ($status && in_array($status, ['planning','active','on_hold','completed','cancelled'])) {
        $where .= " AND status = ?"; $params[] = $status;
    }

    $stmt = $db->prepare("SELECT * FROM biz_projects WHERE $where ORDER BY FIELD(status, 'active','planning','on_hold','completed','cancelled'), created_at DESC");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'projects' => $stmt->fetchAll()]);
}

function projectCreate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $name = sanitize($input['name'] ?? '', 128);
    if (!$name) jsonResponse(['error' => 'name required'], 400);

    $stmt = $db->prepare("INSERT INTO biz_projects (client_id, name, description, contact_id, status, priority, budget, start_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientId, $name,
        sanitize($input['description'] ?? '', 5000),
        (int) ($input['contact_id'] ?? 0) ?: null,
        in_array($input['status'] ?? '', ['planning','active','on_hold','completed','cancelled']) ? $input['status'] : 'planning',
        in_array($input['priority'] ?? '', ['low','medium','high','urgent']) ? $input['priority'] : 'medium',
        !empty($input['budget']) ? (float) $input['budget'] : null,
        !empty($input['start_date']) ? sanitize($input['start_date'], 10) : null,
        !empty($input['due_date']) ? sanitize($input['due_date'], 10) : null,
    ]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function projectUpdate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $fields = []; $params = [];
    foreach (['name','description'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], $f === 'description' ? 5000 : 128); }
    }
    if (isset($input['status']) && in_array($input['status'], ['planning','active','on_hold','completed','cancelled'])) {
        $fields[] = "status = ?"; $params[] = $input['status'];
        if ($input['status'] === 'completed') { $fields[] = "completed_at = NOW()"; }
    }
    if (isset($input['priority']) && in_array($input['priority'], ['low','medium','high','urgent'])) {
        $fields[] = "priority = ?"; $params[] = $input['priority'];
    }
    if (isset($input['budget'])) { $fields[] = "budget = ?"; $params[] = (float) $input['budget']; }
    if (isset($input['due_date'])) { $fields[] = "due_date = ?"; $params[] = sanitize($input['due_date'], 10); }
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

    $params[] = $id; $params[] = $clientId;
    $db->prepare("UPDATE biz_projects SET " . implode(', ', $fields) . " WHERE id = ? AND client_id = ?")->execute($params);
    jsonResponse(['success' => true]);
}

function projectDetail(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $stmt = $db->prepare("SELECT * FROM biz_projects WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $project = $stmt->fetch();
    if (!$project) jsonResponse(['error' => 'Not found'], 404);

    // Tasks
    $stmt = $db->prepare("SELECT * FROM biz_tasks WHERE project_id = ? AND client_id = ? ORDER BY FIELD(status, 'in_progress','todo','review','done','cancelled'), priority DESC");
    $stmt->execute([$id, $clientId]);
    $project['tasks'] = $stmt->fetchAll();

    // Time entries
    $stmt = $db->prepare("SELECT SUM(hours) as total_hours, SUM(CASE WHEN billable=1 THEN hours*rate ELSE 0 END) as total_value FROM biz_time_entries WHERE project_id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $project['time'] = $stmt->fetch();

    jsonResponse(['success' => true, 'project' => $project]);
}

// ═══════════════════════════════════════════════════════════════
// Tasks
// ═══════════════════════════════════════════════════════════════
function tasksList(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $projectId = (int) ($_GET['project_id'] ?? 0);
    $status = sanitize($_GET['status'] ?? '', 20);

    $where = "client_id = ?"; $params = [$clientId];
    if ($projectId) { $where .= " AND project_id = ?"; $params[] = $projectId; }
    if ($status && in_array($status, ['todo','in_progress','review','done','cancelled'])) {
        $where .= " AND status = ?"; $params[] = $status;
    }

    $stmt = $db->prepare("SELECT * FROM biz_tasks WHERE $where ORDER BY FIELD(status, 'in_progress','todo','review','done','cancelled'), FIELD(priority, 'urgent','high','medium','low'), created_at DESC LIMIT 100");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'tasks' => $stmt->fetchAll()]);
}

function taskCreate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $title = sanitize($input['title'] ?? '', 200);
    if (!$title) jsonResponse(['error' => 'title required'], 400);

    $stmt = $db->prepare("INSERT INTO biz_tasks (client_id, project_id, title, description, priority, status, assignee, due_date, estimated_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientId,
        (int) ($input['project_id'] ?? 0) ?: null,
        $title,
        sanitize($input['description'] ?? '', 5000),
        in_array($input['priority'] ?? '', ['low','medium','high','urgent']) ? $input['priority'] : 'medium',
        in_array($input['status'] ?? '', ['todo','in_progress','review','done']) ? $input['status'] : 'todo',
        sanitize($input['assignee'] ?? '', 100),
        !empty($input['due_date']) ? sanitize($input['due_date'], 10) : null,
        !empty($input['estimated_hours']) ? (float) $input['estimated_hours'] : null,
    ]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function taskUpdate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $fields = []; $params = [];
    foreach (['title','description','assignee'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($input[$f], $f === 'description' ? 5000 : 200); }
    }
    if (isset($input['status']) && in_array($input['status'], ['todo','in_progress','review','done','cancelled'])) {
        $fields[] = "status = ?"; $params[] = $input['status'];
        if ($input['status'] === 'done') $fields[] = "completed_at = NOW()";
    }
    if (isset($input['priority']) && in_array($input['priority'], ['low','medium','high','urgent'])) {
        $fields[] = "priority = ?"; $params[] = $input['priority'];
    }
    if (isset($input['due_date'])) { $fields[] = "due_date = ?"; $params[] = sanitize($input['due_date'], 10); }
    if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

    $params[] = $id; $params[] = $clientId;
    $db->prepare("UPDATE biz_tasks SET " . implode(', ', $fields) . " WHERE id = ? AND client_id = ?")->execute($params);
    jsonResponse(['success' => true]);
}

// ═══════════════════════════════════════════════════════════════
// Invoices
// ═══════════════════════════════════════════════════════════════
function invoiceCreate(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    // Generate invoice number
    $stmt = $db->prepare("SELECT COUNT(*) FROM biz_invoices WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $count = (int) $stmt->fetchColumn() + 1;
    $invoiceNum = 'INV-' . str_pad($count, 5, '0', STR_PAD_LEFT);

    $taxRate = max(0, min(100, (float) ($input['tax_rate'] ?? 0)));
    $items = $input['items'] ?? [];
    $subtotal = 0;

    $stmt = $db->prepare("INSERT INTO biz_invoices (client_id, invoice_number, contact_id, project_id, status, issue_date, due_date, subtotal, tax_rate, tax_amount, total, notes) VALUES (?, ?, ?, ?, 'draft', ?, ?, 0, ?, 0, 0, ?)");
    $stmt->execute([
        $clientId, $invoiceNum,
        (int) ($input['contact_id'] ?? 0) ?: null,
        (int) ($input['project_id'] ?? 0) ?: null,
        sanitize($input['issue_date'] ?? date('Y-m-d'), 10),
        !empty($input['due_date']) ? sanitize($input['due_date'], 10) : date('Y-m-d', strtotime('+30 days')),
        $taxRate,
        sanitize($input['notes'] ?? '', 2000),
    ]);
    $invoiceId = (int) $db->lastInsertId();

    // Add items
    $itemStmt = $db->prepare("INSERT INTO biz_invoice_items (invoice_id, description, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $qty = max(0.01, (float) ($item['quantity'] ?? 1));
        $price = (float) ($item['unit_price'] ?? 0);
        $amount = round($qty * $price, 2);
        $subtotal += $amount;
        $itemStmt->execute([$invoiceId, sanitize($item['description'] ?? '', 255), $qty, $price, $amount]);
    }

    $taxAmount = round($subtotal * $taxRate / 100, 2);
    $total = $subtotal + $taxAmount;
    $db->prepare("UPDATE biz_invoices SET subtotal = ?, tax_amount = ?, total = ? WHERE id = ?")->execute([$subtotal, $taxAmount, $total, $invoiceId]);

    jsonResponse(['success' => true, 'id' => $invoiceId, 'invoice_number' => $invoiceNum, 'total' => $total]);
}

function invoiceList(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $status = sanitize($_GET['status'] ?? '', 20);

    $where = "client_id = ?"; $params = [$clientId];
    if ($status && in_array($status, ['draft','sent','viewed','paid','overdue','cancelled'])) {
        $where .= " AND status = ?"; $params[] = $status;
    }

    $stmt = $db->prepare("SELECT id, invoice_number, status, issue_date, due_date, total, created_at FROM biz_invoices WHERE $where ORDER BY created_at DESC LIMIT 100");
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'invoices' => $stmt->fetchAll()]);
}

function invoiceDetail(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $stmt = $db->prepare("SELECT * FROM biz_invoices WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $inv = $stmt->fetch();
    if (!$inv) jsonResponse(['error' => 'Not found'], 404);

    $stmt = $db->prepare("SELECT * FROM biz_invoice_items WHERE invoice_id = ?");
    $stmt->execute([$id]);
    $inv['items'] = $stmt->fetchAll();

    // Contact info
    if ($inv['contact_id']) {
        $stmt = $db->prepare("SELECT first_name, last_name, email, company FROM biz_contacts WHERE id = ? AND client_id = ?");
        $stmt->execute([$inv['contact_id'], $clientId]);
        $inv['contact'] = $stmt->fetch();
    }

    jsonResponse(['success' => true, 'invoice' => $inv]);
}

function invoiceSend(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int) ($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $db->prepare("UPDATE biz_invoices SET status = 'sent' WHERE id = ? AND client_id = ? AND status = 'draft'")->execute([$id, $clientId]);
    jsonResponse(['success' => true, 'message' => 'Invoice marked as sent']);
}

function invoiceFromTime(): void {
    $db = getDB();
    $clientId = bizGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $projectId = (int) ($input['project_id'] ?? 0);
    if (!$projectId) jsonResponse(['error' => 'project_id required'], 400);

    // Get unbilled time entries
    $stmt = $db->prepare("SELECT id, task_description, hours, rate FROM biz_time_entries WHERE client_id = ? AND project_id = ? AND billable = 1 AND invoiced = 0 ORDER BY entry_date");
    $stmt->execute([$clientId, $projectId]);
    $entries = $stmt->fetchAll();

    if (empty($entries)) jsonResponse(['error' => 'No unbilled time entries'], 400);

    // Create invoice with these entries as line items
    $items = [];
    foreach ($entries as $e) {
        $items[] = ['description' => $e['task_description'] ?: 'Billable hours', 'quantity' => $e['hours'], 'unit_price' => $e['rate']];
    }

    // Simulate invoice creation
    $_POST = [
        'project_id' => $projectId,
        'items' => $items,
        'contact_id' => $input['contact_id'] ?? 0,
        'tax_rate' => $input['tax_rate'] ?? 0,
        'issue_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
    ];

    // Mark entries as invoiced
    $ids = array_column($entries, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare("UPDATE biz_time_entries SET invoiced = 1 WHERE id IN ($placeholders)")->execute($ids);

    // Now create the invoice
    invoiceCreate();
}

// ═══════════════════════════════════════════════════════════════
// Dashboard
// ═══════════════════════════════════════════════════════════════
function bizDashboard(): void {
    $db = getDB();
    $clientId = bizGetClientId();

    $dash = [];

    // Contacts summary
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM biz_contacts WHERE client_id = ? GROUP BY status");
    $stmt->execute([$clientId]);
    $dash['contacts'] = $stmt->fetchAll();

    // Active projects
    $stmt = $db->prepare("SELECT COUNT(*) FROM biz_projects WHERE client_id = ? AND status = 'active'");
    $stmt->execute([$clientId]);
    $dash['active_projects'] = (int) $stmt->fetchColumn();

    // Open tasks
    $stmt = $db->prepare("SELECT COUNT(*) FROM biz_tasks WHERE client_id = ? AND status IN ('todo','in_progress')");
    $stmt->execute([$clientId]);
    $dash['open_tasks'] = (int) $stmt->fetchColumn();

    // Hours this week
    $stmt = $db->prepare("SELECT COALESCE(SUM(hours), 0) FROM biz_time_entries WHERE client_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$clientId]);
    $dash['hours_this_week'] = round((float) $stmt->fetchColumn(), 1);

    // Outstanding invoices
    $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as amount FROM biz_invoices WHERE client_id = ? AND status IN ('sent','viewed','overdue')");
    $stmt->execute([$clientId]);
    $dash['outstanding_invoices'] = $stmt->fetch();

    // Overdue tasks
    $stmt = $db->prepare("SELECT COUNT(*) FROM biz_tasks WHERE client_id = ? AND status IN ('todo','in_progress') AND due_date < CURDATE()");
    $stmt->execute([$clientId]);
    $dash['overdue_tasks'] = (int) $stmt->fetchColumn();

    // Upcoming activities
    $stmt = $db->prepare("SELECT activity_type, subject, due_date FROM biz_activities WHERE client_id = ? AND completed = 0 AND due_date IS NOT NULL ORDER BY due_date LIMIT 5");
    $stmt->execute([$clientId]);
    $dash['upcoming_activities'] = $stmt->fetchAll();

    jsonResponse(['success' => true, 'dashboard' => $dash]);
}
