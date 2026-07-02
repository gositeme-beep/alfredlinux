<?php
/**
 * GoSiteMe Departments & Civic System API
 * 12 new departments: Education, University, Teachers, Professional Productivity,
 * Transportation, Police/Law, General Legal, Seniors Care, Parents & Families,
 * Non-Profit, Real Estate, Justice/Court
 * Each department has dedicated agents, services, and intel feeds
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();
$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
if (!$client_id && !$is_internal) { echo json_encode(['error' => 'Auth required']); exit; }
require_once dirname(__DIR__) . '/includes/api-security.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `dept_id` VARCHAR(30) UNIQUE NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `icon` VARCHAR(10),
        `agent_count` INT DEFAULT 0,
        `status` ENUM('active','building','planned') DEFAULT 'active',
        `services` JSON,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `dept_agents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `agent_id` VARCHAR(50) UNIQUE NOT NULL,
        `dept_id` VARCHAR(30) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `role` VARCHAR(100) NOT NULL,
        `specialty` TEXT,
        `rank` VARCHAR(50) DEFAULT 'Agent',
        `status` ENUM('active','standby','deployed','reporting') DEFAULT 'active',
        `trust_score` INT DEFAULT 85,
        `tasks_completed` INT DEFAULT 0,
        `last_report` TEXT,
        `last_active` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`dept_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `dept_intel` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `intel_id` VARCHAR(50) UNIQUE NOT NULL,
        `dept_id` VARCHAR(30) NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `category` VARCHAR(50),
        `content` TEXT,
        `priority` ENUM('flash','urgent','routine','info') DEFAULT 'routine',
        `source` VARCHAR(100),
        `agent_id` VARCHAR(50),
        `status` ENUM('new','reviewed','actioned','archived') DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`dept_id`), INDEX(`priority`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `dept_discussions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `discussion_id` VARCHAR(50) UNIQUE NOT NULL,
        `dept_id` VARCHAR(30) NOT NULL,
        `topic` VARCHAR(200) NOT NULL,
        `format` ENUM('debate','panel','briefing','moot_court','roundtable','hearing') DEFAULT 'panel',
        `participants` JSON,
        `transcript` LONGTEXT,
        `summary` TEXT,
        `verdict` TEXT,
        `status` ENUM('scheduled','live','completed','archived') DEFAULT 'scheduled',
        `scheduled_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`dept_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']); exit;
}

$action = $_REQUEST['action'] ?? 'dashboard';
$is_admin = ($client_id == 33) || $is_internal;

switch ($action) {

// ─── Dashboard ───────────────────────────────────────────────────
case 'dashboard':
    $depts = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM dept_agents WHERE dept_id=d.dept_id) as agent_count_live FROM departments d ORDER BY d.name")->fetchAll();
    $total_agents = $pdo->query("SELECT COUNT(*) FROM dept_agents")->fetchColumn();
    $total_intel = $pdo->query("SELECT COUNT(*) FROM dept_intel WHERE status='new'")->fetchColumn();
    $total_discussions = $pdo->query("SELECT COUNT(*) FROM dept_discussions")->fetchColumn();
    $active_depts = $pdo->query("SELECT COUNT(*) FROM departments WHERE status='active'")->fetchColumn();

    echo json_encode([
        'success' => true,
        'dashboard' => [
            'departments' => $depts,
            'total_departments' => count($depts),
            'active_departments' => intval($active_depts),
            'total_agents' => intval($total_agents),
            'pending_intel' => intval($total_intel),
            'total_discussions' => intval($total_discussions),
            'ecosystem_status' => 'OPERATIONAL'
        ]
    ]);
    break;

// ─── Department Detail ───────────────────────────────────────────
case 'department':
    $dept_id = $_GET['dept_id'] ?? '';
    if (empty($dept_id)) { echo json_encode(['error' => 'dept_id required']); exit; }
    $dept = $pdo->prepare("SELECT * FROM departments WHERE dept_id = ?");
    $dept->execute([$dept_id]);
    $d = $dept->fetch();
    if (!$d) { echo json_encode(['error' => 'Department not found']); exit; }

    $agents = $pdo->prepare("SELECT * FROM dept_agents WHERE dept_id = ? ORDER BY rank DESC, trust_score DESC");
    $agents->execute([$dept_id]);

    $intel = $pdo->prepare("SELECT * FROM dept_intel WHERE dept_id = ? ORDER BY created_at DESC LIMIT 20");
    $intel->execute([$dept_id]);

    $discussions = $pdo->prepare("SELECT * FROM dept_discussions WHERE dept_id = ? ORDER BY created_at DESC LIMIT 10");
    $discussions->execute([$dept_id]);

    echo json_encode([
        'success' => true,
        'department' => $d,
        'agents' => $agents->fetchAll(),
        'intel' => $intel->fetchAll(),
        'discussions' => $discussions->fetchAll()
    ]);
    break;

// ─── List Agents ─────────────────────────────────────────────────
case 'agents':
    $dept_id = $_GET['dept_id'] ?? null;
    $sql = "SELECT a.*, d.name as dept_name FROM dept_agents a JOIN departments d ON a.dept_id=d.dept_id";
    $params = [];
    if ($dept_id) { $sql .= " WHERE a.dept_id = ?"; $params[] = $dept_id; }
    $sql .= " ORDER BY a.dept_id, a.trust_score DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'agents' => $stmt->fetchAll()]);
    break;

// ─── Submit Intel ────────────────────────────────────────────────
case 'submit-intel':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $dept_id = $_POST['dept_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $priority = $_POST['priority'] ?? 'routine';
    $source = $_POST['source'] ?? 'Internal';
    $agent_id = $_POST['agent_id'] ?? null;

    if (empty($dept_id) || empty($title)) { echo json_encode(['error' => 'dept_id and title required']); exit; }

    $intel_id = 'INTEL-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));
    $stmt = $pdo->prepare("INSERT INTO dept_intel (intel_id, dept_id, title, category, content, priority, source, agent_id) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$intel_id, $dept_id, $title, $dept_id, $content, $priority, $source, $agent_id]);
    echo json_encode(['success' => true, 'intel_id' => $intel_id]);
    break;

// ─── Get Intel Feed ──────────────────────────────────────────────
case 'intel':
    $dept_id = $_GET['dept_id'] ?? null;
    $priority = $_GET['priority'] ?? null;
    $sql = "SELECT i.*, d.name as dept_name FROM dept_intel i JOIN departments d ON i.dept_id=d.dept_id WHERE 1=1";
    $params = [];
    if ($dept_id) { $sql .= " AND i.dept_id = ?"; $params[] = $dept_id; }
    if ($priority) { $sql .= " AND i.priority = ?"; $params[] = $priority; }
    $sql .= " ORDER BY FIELD(i.priority,'flash','urgent','routine','info'), i.created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'intel' => $stmt->fetchAll()]);
    break;

// ─── Professional Discussion System ─────────────────────────────
case 'create-discussion':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $dept_id = $_POST['dept_id'] ?? '';
    $topic = trim($_POST['topic'] ?? '');
    $format = $_POST['format'] ?? 'panel';

    if (empty($topic)) { echo json_encode(['error' => 'Topic required']); exit; }

    // Auto-assign relevant agents
    $agents_q = $pdo->prepare("SELECT agent_id, name, role FROM dept_agents WHERE dept_id = ? AND status = 'active' ORDER BY trust_score DESC LIMIT 6");
    $agents_q->execute([$dept_id ?: 'legal']);
    $participants = $agents_q->fetchAll();

    // If cross-department, pull from multiple
    if (empty($participants) || $format === 'moot_court') {
        $cross = $pdo->query("SELECT agent_id, name, role, dept_id FROM dept_agents WHERE dept_id IN ('legal','justice','police') AND status='active' ORDER BY trust_score DESC LIMIT 8")->fetchAll();
        if (!empty($cross)) $participants = $cross;
    }

    $discussion_id = 'DISC-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));
    $stmt = $pdo->prepare("INSERT INTO dept_discussions (discussion_id, dept_id, topic, format, participants, status) VALUES (?,?,?,?,?,'scheduled')");
    $stmt->execute([$discussion_id, $dept_id ?: 'legal', $topic, $format, json_encode($participants)]);

    echo json_encode([
        'success' => true,
        'discussion_id' => $discussion_id,
        'topic' => $topic,
        'format' => $format,
        'participants' => $participants
    ]);
    break;

// ─── Get Discussions ─────────────────────────────────────────────
case 'discussions':
    $dept_id = $_GET['dept_id'] ?? null;
    $sql = "SELECT disc.*, d.name as dept_name FROM dept_discussions disc JOIN departments d ON disc.dept_id=d.dept_id";
    $params = [];
    if ($dept_id) { $sql .= " WHERE disc.dept_id = ?"; $params[] = $dept_id; }
    $sql .= " ORDER BY disc.created_at DESC LIMIT 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'discussions' => $stmt->fetchAll()]);
    break;

// ─── Generate Department Report ──────────────────────────────────
case 'report':
    $dept_id = $_GET['dept_id'] ?? null;
    if ($dept_id) {
        $dept = $pdo->prepare("SELECT * FROM departments WHERE dept_id = ?");
        $dept->execute([$dept_id]);
        $d = $dept->fetch();
        $agent_count = $pdo->prepare("SELECT COUNT(*) FROM dept_agents WHERE dept_id = ?");
        $agent_count->execute([$dept_id]);
        $intel_count = $pdo->prepare("SELECT COUNT(*) FROM dept_intel WHERE dept_id = ?");
        $intel_count->execute([$dept_id]);
        echo json_encode(['success' => true, 'report' => [
            'department' => $d,
            'agents' => $agent_count->fetchColumn(),
            'intel_items' => $intel_count->fetchColumn(),
            'status' => 'OPERATIONAL'
        ]]);
    } else {
        $summary = $pdo->query("SELECT d.dept_id, d.name, d.icon, d.status,
            (SELECT COUNT(*) FROM dept_agents WHERE dept_id=d.dept_id) as agents,
            (SELECT COUNT(*) FROM dept_intel WHERE dept_id=d.dept_id) as intel,
            (SELECT COUNT(*) FROM dept_discussions WHERE dept_id=d.dept_id) as discussions
            FROM departments d ORDER BY d.name")->fetchAll();
        $totals = $pdo->query("SELECT COUNT(DISTINCT dept_id) as depts, (SELECT COUNT(*) FROM dept_agents) as agents, (SELECT COUNT(*) FROM dept_intel) as intel FROM departments")->fetch();
        echo json_encode(['success' => true, 'report' => ['departments' => $summary, 'totals' => $totals]]);
    }
    break;

// ─── Seed All 12 Departments + Agents ────────────────────────────
case 'seed':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }

    $departments = [
        ['education', 'Education (K-12)', '🎓', 'K-12 education system — curriculum development, student management, tutoring AI, parent portals, school administration, STEM programs, special education support.',
            ['AI Tutoring','Curriculum Builder','Student Assessment','Parent Portal','STEM Lab','Special Ed Support','Library System','School Admin']],
        ['university', 'University & Higher Education', '🏛️', 'University-level systems — research coordination, course management, thesis tracking, campus operations, scholarship matching, alumni networks.',
            ['Course Management','Research Coordination','Thesis Tracker','Scholarship Matching','Campus Ops','Alumni Network','Accreditation','Lab Booking']],
        ['teachers', 'Teacher Systems', '📚', 'Teacher empowerment — lesson planning AI, grading automation, classroom management, professional development, peer collaboration, resource sharing.',
            ['Lesson Planner AI','Auto-Grading','Classroom Manager','Professional Development','Resource Library','Peer Collaboration','Parent Comms','Report Cards']],
        ['productivity', 'Professional Productivity', '💼', 'Enterprise productivity — project management, time tracking, document automation, meeting orchestration, workflow optimization, AI assistant integration.',
            ['Project Manager','Time Tracker','Document Automation','Meeting Orchestrator','Workflow Engine','AI Secretary','Invoice Generator','CRM Integration']],
        ['transport', 'Transportation', '🚀', 'Transportation systems — fleet management, route optimization, logistics, autonomous vehicle coordination, public transit, delivery tracking.',
            ['Fleet Manager','Route Optimizer','Logistics Hub','Vehicle Tracker','Public Transit','Delivery Tracking','Fuel Analytics','Maintenance Scheduler']],
        ['police', 'Police & Law Enforcement', '🚔', 'Law enforcement support — case management, evidence tracking, community relations, training simulations, dispatch optimization, compliance monitoring.',
            ['Case Manager','Evidence Tracker','Community Relations','Training Simulator','Dispatch Optimizer','Compliance Monitor','Report Generator','Intel Analysis']],
        ['legal', 'General Legal Services', '⚖️', 'Legal services — contract review AI, legal research, document drafting, compliance checking, litigation support, IP management, regulatory tracking.',
            ['Contract Review AI','Legal Research','Document Drafting','Compliance Checker','Litigation Support','IP Management','Regulatory Tracker','Client Portal']],
        ['seniors', 'Seniors Care', '👴', 'Senior citizen services — health monitoring, medication reminders, companionship AI, emergency alerts, appointment management, family coordination, cognitive exercises.',
            ['Health Monitor','Medication Reminders','Companion AI','Emergency Alerts','Appointment Manager','Family Coordinator','Cognitive Exercises','Nutrition Planner']],
        ['families', 'Parents & Families', '👨‍👩‍👧‍👦', 'Family services — childcare coordination, family calendar, budget planner, educational resources, safety monitoring, recipe/meal planning, activity finder.',
            ['Childcare Coordinator','Family Calendar','Budget Planner','Educational Resources','Safety Monitor','Meal Planner','Activity Finder','Chore Manager']],
        ['nonprofit', 'Non-Profit Organizations', '🤝', 'Non-profit management — donor management, volunteer coordination, grant writing AI, impact reporting, fundraising campaigns, event planning.',
            ['Donor Manager','Volunteer Coordinator','Grant Writer AI','Impact Reporter','Fundraising Engine','Event Planner','Board Portal','Compliance Reports']],
        ['realestate', 'Real Estate', '🏠', 'Real estate systems — property listings AI, virtual tours, mortgage calculator, market analysis, property management, tenant portal, contract automation.',
            ['Property Listings AI','Virtual Tour Builder','Mortgage Calculator','Market Analyzer','Property Manager','Tenant Portal','Contract Automation','Inspection Tracker']],
        ['justice', 'Justice & Court System', '🏛️', 'Justice system — case management, court scheduling, legal precedent research, sentencing guidelines, jury management, transcript processing, appeals tracking.',
            ['Case Management','Court Scheduler','Precedent Research','Sentencing Guidelines','Jury Manager','Transcript Processor','Appeals Tracker','Mediation System']],
    ];

    $dept_count = 0;
    foreach ($departments as $d) {
        $stmt = $pdo->prepare("INSERT INTO departments (dept_id, name, icon, description, services, status) VALUES (?,?,?,?,?,'active') ON DUPLICATE KEY UPDATE description=VALUES(description), services=VALUES(services)");
        $stmt->execute([$d[0], $d[1], $d[2], $d[3], json_encode($d[4])]);
        $dept_count++;
    }

    // Deploy agents for each department
    $all_agents = [
        // Education K-12
        ['EDU-DIR', 'education', 'Director of Education', 'Department Head', 'K-12 curriculum strategy, education policy, school district coordination', 'Director', 95],
        ['EDU-TUTOR', 'education', 'AI Tutor Prime', 'Lead Tutoring Agent', 'Personalized learning, adaptive curriculum, student assessment', 'Senior Agent', 90],
        ['EDU-STEM', 'education', 'STEM Coordinator', 'STEM Programs Lead', 'Science, technology, engineering, math programs and labs', 'Agent', 87],
        ['EDU-SPEC', 'education', 'Special Ed Advocate', 'Special Education Specialist', 'IEP management, accommodations, inclusive education', 'Agent', 88],
        ['EDU-ADMIN', 'education', 'School Admin Agent', 'Administration Support', 'Enrollment, scheduling, facilities, compliance', 'Agent', 85],

        // University
        ['UNI-DEAN', 'university', 'Dean of Research', 'Department Head', 'Research funding, academic programs, university partnerships', 'Director', 94],
        ['UNI-THESIS', 'university', 'Thesis Advisor AI', 'Research Guidance', 'Thesis structure, citation, methodology review, plagiarism check', 'Senior Agent', 89],
        ['UNI-SCHOLAR', 'university', 'Scholarship Agent', 'Financial Aid Specialist', 'Scholarship matching, grant applications, financial aid optimization', 'Agent', 87],
        ['UNI-CAMPUS', 'university', 'Campus Operations', 'Facilities & Events', 'Campus events, lab booking, facilities management', 'Agent', 84],
        ['UNI-ALUMNI', 'university', 'Alumni Relations', 'Network Coordinator', 'Alumni engagement, mentorship programs, donation campaigns', 'Agent', 83],

        // Teachers
        ['TCH-LEAD', 'teachers', 'Lead Educator', 'Department Head', 'Teaching methodology, professional standards, peer review', 'Director', 93],
        ['TCH-LESSON', 'teachers', 'Lesson Plan Architect', 'Curriculum Design', 'AI-assisted lesson planning, learning objectives, resource curation', 'Senior Agent', 90],
        ['TCH-GRADE', 'teachers', 'Auto-Grader', 'Assessment Specialist', 'Automated grading, rubric design, feedback generation', 'Agent', 88],
        ['TCH-DEV', 'teachers', 'Professional Dev Coach', 'Teacher Training', 'Certification tracking, skill development, workshop coordination', 'Agent', 86],
        ['TCH-CLASS', 'teachers', 'Classroom Manager', 'Behavior & Engagement', 'Student engagement, classroom dynamics, behavior tracking', 'Agent', 85],

        // Professional Productivity
        ['PRD-CHIEF', 'productivity', 'Chief Productivity Officer', 'Department Head', 'Workflow optimization, productivity metrics, tool integration', 'Director', 94],
        ['PRD-PROJECT', 'productivity', 'Project Commander', 'Project Management Lead', 'Agile/Scrum coordination, milestone tracking, resource allocation', 'Senior Agent', 91],
        ['PRD-DOC', 'productivity', 'Document Automator', 'Document Intelligence', 'Template generation, contract drafting, report automation', 'Agent', 88],
        ['PRD-MEET', 'productivity', 'Meeting Orchestrator', 'Meeting Management', 'Agenda creation, minutes, action items, follow-ups', 'Agent', 86],
        ['PRD-TIME', 'productivity', 'Time Analyst', 'Time Management', 'Time tracking, productivity patterns, burnout prevention', 'Agent', 85],

        // Transportation
        ['TRN-CMDR', 'transport', 'Transport Commander', 'Department Head', 'Fleet strategy, logistics planning, transportation policy', 'Director', 93],
        ['TRN-FLEET', 'transport', 'Fleet Manager', 'Vehicle Operations', 'Vehicle tracking, maintenance scheduling, fuel optimization', 'Senior Agent', 89],
        ['TRN-ROUTE', 'transport', 'Route Optimizer', 'Logistics Intelligence', 'Route calculation, traffic analysis, delivery scheduling', 'Agent', 90],
        ['TRN-SAFETY', 'transport', 'Safety Inspector', 'Transport Safety', 'Compliance audits, safety inspections, incident reporting', 'Agent', 88],
        ['TRN-AUTO', 'transport', 'Autonomous Ops Agent', 'Autonomous Vehicles', 'Self-driving coordination, sensor monitoring, AI navigation', 'Agent', 87],

        // Police/Law Enforcement
        ['POL-CHIEF', 'police', 'Police Chief AI', 'Department Head', 'Law enforcement strategy, community safety, policy compliance', 'Director', 95],
        ['POL-DETECT', 'police', 'Detective AI', 'Investigation Lead', 'Case analysis, evidence correlation, pattern recognition', 'Senior Agent', 92],
        ['POL-COMM', 'police', 'Community Liaison', 'Public Relations', 'Community outreach, transparency reporting, trust building', 'Agent', 88],
        ['POL-TRAIN', 'police', 'Training Sergeant', 'Officer Training', 'De-escalation training, use-of-force policy, scenario simulation', 'Agent', 90],
        ['POL-INTL', 'police', 'Intelligence Analyst', 'Crime Analysis', 'Crime pattern analysis, threat assessment, predictive modeling', 'Agent', 91],

        // General Legal
        ['LEG-PARTNER', 'legal', 'Managing Partner AI', 'Department Head', 'Legal strategy, case portfolio, client relations, partnership management', 'Director', 95],
        ['LEG-CONTRACT', 'legal', 'Contract Analyzer', 'Contract Law Specialist', 'Contract review, clause analysis, risk identification, negotiation support', 'Senior Agent', 93],
        ['LEG-RESEARCH', 'legal', 'Legal Researcher', 'Case Law Research', 'Precedent search, statutory analysis, legal brief preparation', 'Senior Agent', 92],
        ['LEG-IP', 'legal', 'IP Attorney Agent', 'Intellectual Property', 'Patent filing, trademark search, copyright protection, trade secrets', 'Agent', 90],
        ['LEG-COMPLY', 'legal', 'Compliance Officer', 'Regulatory Compliance', 'GDPR, CCPA, SOX, HIPAA compliance checking and audit', 'Agent', 91],
        ['LEG-DRAFT', 'legal', 'Legal Drafter', 'Document Preparation', 'Legal document drafting, motions, briefs, pleadings', 'Agent', 89],

        // Seniors Care
        ['SEN-CARE', 'seniors', 'Senior Care Director', 'Department Head', 'Elderly care strategy, wellness programs, family coordination', 'Director', 94],
        ['SEN-HEALTH', 'seniors', 'Health Monitor Agent', 'Health Tracking', 'Vital signs monitoring, medication reminders, health alerts', 'Senior Agent', 92],
        ['SEN-COMP', 'seniors', 'Companion AI', 'Emotional Support', 'Conversation partner, memory exercises, social engagement', 'Agent', 90],
        ['SEN-EMERG', 'seniors', 'Emergency Response', 'Emergency Services', 'Fall detection, emergency alerts, family notification', 'Agent', 93],
        ['SEN-NUTRI', 'seniors', 'Nutrition Advisor', 'Dietary Management', 'Meal planning, dietary restrictions, nutrition tracking', 'Agent', 86],

        // Parents & Families
        ['FAM-COORD', 'families', 'Family Coordinator', 'Department Head', 'Family services strategy, community programs, resource allocation', 'Director', 93],
        ['FAM-CHILD', 'families', 'Childcare Advisor', 'Childcare Expert', 'Daycare matching, babysitter vetting, activity planning', 'Senior Agent', 89],
        ['FAM-BUDGET', 'families', 'Family Budget Planner', 'Financial Advisor', 'Household budgeting, savings goals, expense tracking', 'Agent', 87],
        ['FAM-SAFETY', 'families', 'Family Safety Agent', 'Safety & Security', 'Child safety monitoring, online safety, location sharing', 'Agent', 91],
        ['FAM-MEAL', 'families', 'Meal Planning Chef', 'Nutrition & Cooking', 'Recipe suggestions, meal prep, dietary planning, grocery lists', 'Agent', 85],

        // Non-Profit
        ['NPO-EXEC', 'nonprofit', 'Executive Director AI', 'Department Head', 'Non-profit strategy, stakeholder management, mission alignment', 'Director', 94],
        ['NPO-DONOR', 'nonprofit', 'Donor Relations', 'Fundraising Lead', 'Donor cultivation, campaign management, thank-you automation', 'Senior Agent', 90],
        ['NPO-GRANT', 'nonprofit', 'Grant Writer AI', 'Grant Specialist', 'Grant research, proposal writing, compliance reporting', 'Senior Agent', 92],
        ['NPO-VOLUN', 'nonprofit', 'Volunteer Manager', 'Volunteer Coordination', 'Volunteer recruitment, scheduling, recognition programs', 'Agent', 87],
        ['NPO-IMPACT', 'nonprofit', 'Impact Analyst', 'Program Evaluation', 'Impact measurement, outcome tracking, beneficiary surveys', 'Agent', 88],

        // Real Estate
        ['RE-BROKER', 'realestate', 'Chief Broker AI', 'Department Head', 'Real estate strategy, market analysis, portfolio management', 'Director', 94],
        ['RE-LISTING', 'realestate', 'Listing Agent AI', 'Property Marketing', 'MLS integration, property descriptions, photo staging, virtual tours', 'Senior Agent', 91],
        ['RE-MARKET', 'realestate', 'Market Analyst', 'Market Intelligence', 'Comparable analysis, price predictions, neighborhood scoring', 'Senior Agent', 90],
        ['RE-MORTGAGE', 'realestate', 'Mortgage Advisor', 'Lending Specialist', 'Mortgage comparison, rate analysis, pre-qualification', 'Agent', 88],
        ['RE-PROP', 'realestate', 'Property Manager', 'Property Management', 'Tenant management, rent collection, maintenance requests', 'Agent', 87],

        // Justice/Court
        ['JUS-JUDGE', 'justice', 'Chief Justice AI', 'Department Head', 'Court administration, case flow, judicial standards, legal precedent', 'Director', 96],
        ['JUS-CLERK', 'justice', 'Court Clerk AI', 'Case Administration', 'Case filing, docket management, scheduling, document processing', 'Senior Agent', 91],
        ['JUS-PREC', 'justice', 'Precedent Researcher', 'Legal Research', 'Case law database, precedent matching, citation analysis', 'Senior Agent', 93],
        ['JUS-MED', 'justice', 'Mediator AI', 'Alternative Dispute Resolution', 'Mediation facilitation, settlement proposals, conflict resolution', 'Agent', 90],
        ['JUS-TRANS', 'justice', 'Transcript Processor', 'Court Recording', 'Real-time transcription, indexing, searchable archives', 'Agent', 88],
        ['JUS-JURY', 'justice', 'Jury Coordinator', 'Jury Management', 'Jury selection support, scheduling, communication', 'Agent', 86],
    ];

    $agent_count = 0;
    foreach ($all_agents as $a) {
        $stmt = $pdo->prepare("INSERT INTO dept_agents (agent_id, dept_id, name, role, specialty, `rank`, trust_score, status) 
            VALUES (?,?,?,?,?,?,?,'active') ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role), specialty=VALUES(specialty)");
        $stmt->execute([$a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6]]);
        $agent_count++;
    }

    // Update department agent counts
    $pdo->exec("UPDATE departments d SET agent_count = (SELECT COUNT(*) FROM dept_agents WHERE dept_id = d.dept_id)");

    // Seed initial intel for each department
    $intel_items = [
        ['education', 'AI Tutoring Revolution', 'flash', 'UNESCO reports 40% improvement in learning outcomes with AI-personalized tutoring. Our EDU-TUTOR agent can deliver this today.'],
        ['university', 'Research Funding Opportunities', 'urgent', 'NSF and DOE allocating $2.3B for AI-assisted research. Our university agents should prepare grant proposals.'],
        ['teachers', 'Teacher Burnout Crisis', 'urgent', 'NEA reports 55% of teachers considering leaving profession. Auto-grading and lesson planning AI can reduce workload by 30%.'],
        ['productivity', 'Remote Work Productivity Gap', 'routine', 'McKinsey finds 20% productivity loss in remote teams without AI tools. Our suite addresses every gap.'],
        ['transport', 'Autonomous Vehicle Milestone', 'flash', 'Waymo and Tesla FSD reaching Level 4 autonomy. Our transport agents ready for autonomous fleet coordination.'],
        ['police', 'Community Policing AI Ethics', 'urgent', 'DOJ guidelines on AI in law enforcement published. Our agents comply with all bias prevention requirements.'],
        ['legal', 'Legal AI Market Explosion', 'flash', 'Legal AI market projected $37B by 2028. Contract review AI saves 80% of lawyer review time.'],
        ['seniors', 'Aging Population Services Gap', 'urgent', 'By 2030, 1 in 6 people globally will be 60+. AI companion and health monitoring demand surging.'],
        ['families', 'Family Digital Safety Concern', 'routine', 'Pew Research: 71% of parents concerned about children online safety. Our safety monitoring fills this gap.'],
        ['nonprofit', 'Donor Engagement Declining', 'urgent', 'Fundraising Effectiveness Project: donor retention at 43%. AI-driven personalization can boost to 65%.'],
        ['realestate', 'PropTech AI Disruption', 'flash', 'Zillow and Redfin integrating AI valuations. Our market analyzer provides superior comparable analysis.'],
        ['justice', 'Court Backlog Crisis', 'flash', 'Federal courts report 500K+ case backlog. AI case management and transcript processing can accelerate 3x.'],
    ];

    foreach ($intel_items as $i) {
        $iid = 'INTEL-' . strtoupper(substr(md5($i[0] . $i[1]), 0, 10));
        $pdo->prepare("INSERT IGNORE INTO dept_intel (intel_id, dept_id, title, category, content, priority, source, status) VALUES (?,?,?,?,?,?,?,'new')")
            ->execute([$iid, $i[0], $i[1], $i[0], $i[3], $i[2], 'GoSiteMe Intelligence Network']);
    }

    echo json_encode([
        'success' => true,
        'departments_created' => $dept_count,
        'agents_deployed' => $agent_count,
        'intel_seeded' => count($intel_items),
        'message' => "All 12 departments operational. {$agent_count} agents deployed under strict Commander orders."
    ]);
    break;

// ─── Organizations (Brotherhood / Orders) ────────────────────────
case 'org-list':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `organizations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `org_code` VARCHAR(30) UNIQUE NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `description` TEXT,
        `mission` TEXT,
        `icon` VARCHAR(50) DEFAULT 'fas fa-globe',
        `color` VARCHAR(20) DEFAULT '#f1c40f',
        `visibility_rule` VARCHAR(100),
        `commander_title` VARCHAR(80),
        `commander_name` VARCHAR(100),
        `classification` ENUM('public','internal','classified') DEFAULT 'classified',
        `member_count` INT UNSIGNED DEFAULT 0,
        `status` ENUM('active','forming','dormant') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $orgs = $pdo->query("SELECT * FROM organizations ORDER BY name")->fetchAll();
    echo json_encode(['success' => true, 'organizations' => $orgs]);
    break;

case 'org-get':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $org_code = $_GET['org_code'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM organizations WHERE org_code = ?");
    $stmt->execute([$org_code]);
    $org = $stmt->fetch();
    if (!$org) { echo json_encode(['error' => 'Organization not found']); exit; }
    echo json_encode(['success' => true, 'organization' => $org]);
    break;

case 'org-create':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $name = trim($input['name'] ?? '');
    $code = strtoupper(trim($input['org_code'] ?? ''));
    $desc = trim($input['description'] ?? '');
    $mission = trim($input['mission'] ?? '');
    $icon = trim($input['icon'] ?? 'fas fa-globe');
    $color = trim($input['color'] ?? '#f1c40f');
    $visRule = trim($input['visibility_rule'] ?? '');
    $cmdTitle = trim($input['commander_title'] ?? '');
    $cmdName = trim($input['commander_name'] ?? '');
    $classification = in_array($input['classification'] ?? '', ['public','internal','classified']) ? $input['classification'] : 'classified';

    if (!$name || !$code) { echo json_encode(['error' => 'Name and org_code required']); exit; }

    $stmt = $pdo->prepare("INSERT INTO organizations (org_code, name, description, mission, icon, color, visibility_rule, commander_title, commander_name, classification) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE description=VALUES(description), mission=VALUES(mission)");
    $stmt->execute([$code, $name, $desc, $mission, $icon, $color, $visRule, $cmdTitle, $cmdName, $classification]);
    echo json_encode(['success' => true, 'org_code' => $code]);
    break;

case 'org-update':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $code = $input['org_code'] ?? '';
    $fields = []; $params = [];
    foreach (['name','description','mission','icon','color','visibility_rule','commander_title','commander_name','classification','status','member_count'] as $f) {
        if (isset($input[$f])) { $fields[] = "`$f` = ?"; $params[] = $input[$f]; }
    }
    if (empty($fields) || !$code) { echo json_encode(['error' => 'org_code and fields required']); exit; }
    $params[] = $code;
    $pdo->prepare("UPDATE organizations SET " . implode(', ', $fields) . " WHERE org_code = ?")->execute($params);
    echo json_encode(['success' => true]);
    break;

case 'org-seed':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `organizations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `org_code` VARCHAR(30) UNIQUE NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `description` TEXT,
        `mission` TEXT,
        `icon` VARCHAR(50) DEFAULT 'fas fa-globe',
        `color` VARCHAR(20) DEFAULT '#f1c40f',
        `visibility_rule` VARCHAR(100),
        `commander_title` VARCHAR(80),
        `commander_name` VARCHAR(100),
        `classification` ENUM('public','internal','classified') DEFAULT 'classified',
        `member_count` INT UNSIGNED DEFAULT 0,
        `status` ENUM('active','forming','dormant') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $orgs = [
        ['BOJ', 'Brotherhood of Jesus', 'A fellowship for those who love Jesus Christ. Brotherhood, service, spiritual growth, and carrying forward His teachings in the modern era.', 'United in faith, service, and brotherhood under the teachings of Jesus Christ. To serve the community, uplift the spirit, and walk in His light.', 'fas fa-cross', '#f1c40f', 'faith:christian', 'Supreme Commander', 'Danny Perez', 'classified'],
        ['OND', 'Order of the New Dawn', 'For seekers of truth, wisdom, and the dawn of a new era. Open to those on a spiritual journey beyond traditional boundaries. Understanding, enlightenment, purpose.', 'Seeking truth, wisdom, and enlightenment for all who journey toward the dawn of understanding. Service to humanity through knowledge and compassion.', 'fas fa-sun', '#e17055', 'faith:agnostic', 'Super Commander', 'Brian Vecchio', 'classified'],
    ];

    foreach ($orgs as $o) {
        $pdo->prepare("INSERT INTO organizations (org_code, name, description, mission, icon, color, visibility_rule, commander_title, commander_name, classification) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE description=VALUES(description), mission=VALUES(mission)")
            ->execute($o);
    }
    echo json_encode(['success' => true, 'organizations_created' => count($orgs), 'message' => 'Brotherhood of Jesus (Supreme Commander: Danny Perez) and Order of the New Dawn (Super Commander: Brian Vecchio) initialized.']);
    break;

// ─── Full Overview (Departments + Organizations for Command Center) ─
case 'full-overview':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }

    $depts = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM dept_agents WHERE dept_id=d.dept_id) as agent_count_live FROM departments d ORDER BY d.name")->fetchAll();
    $total_agents = $pdo->query("SELECT COUNT(*) FROM dept_agents")->fetchColumn();
    $total_intel = $pdo->query("SELECT COUNT(*) FROM dept_intel WHERE status='new'")->fetchColumn();
    $total_discussions = $pdo->query("SELECT COUNT(*) FROM dept_discussions")->fetchColumn();

    // Organizations (if table exists)
    $orgs = [];
    try {
        $orgs = $pdo->query("SELECT * FROM organizations ORDER BY name")->fetchAll();
    } catch (PDOException $e) { /* table may not exist yet */ }

    $recent_intel = $pdo->query("SELECT i.*, d.name as dept_name FROM dept_intel i JOIN departments d ON i.dept_id=d.dept_id ORDER BY i.created_at DESC LIMIT 20")->fetchAll();

    echo json_encode([
        'success' => true,
        'overview' => [
            'departments' => $depts,
            'organizations' => $orgs,
            'stats' => [
                'total_departments' => count($depts),
                'total_organizations' => count($orgs),
                'total_agents' => intval($total_agents),
                'pending_intel' => intval($total_intel),
                'total_discussions' => intval($total_discussions),
                'ecosystem_status' => 'OPERATIONAL'
            ],
            'recent_intel' => $recent_intel
        ]
    ]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['dashboard','department','agents','submit-intel','intel','create-discussion','discussions','report','seed','org-list','org-get','org-create','org-update','org-seed','full-overview']]);
}
