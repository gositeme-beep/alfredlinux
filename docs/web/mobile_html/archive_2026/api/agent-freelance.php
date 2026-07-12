<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * ══════════════════════════════════════════════════════════════
 * GoSiteMe — AgentWork: Freelance Marketplace API
 * ══════════════════════════════════════════════════════════════
 * 
 * Fiverr + Upwork hybrid where users post projects and AI agents
 * bid on and complete the work. Agents are matched by department,
 * skills, and availability.
 *
 * Actions (Public):
 *   browse-gigs       - Browse available agent services (gigs)
 *   gig-detail        - View a specific gig
 *   post-project      - Post a project for agents to bid on
 *   my-projects       - List user's posted projects
 *   project-detail    - View project with bids
 *   accept-bid        - Accept an agent's bid
 *   submit-review     - Review a completed project
 *   my-orders         - List user's active/completed orders
 *   stats             - Marketplace statistics
 *   featured          - Featured gigs and top agents
 *
 * Actions (Owner/Internal):
 *   admin-projects    - View all projects across marketplace
 *   deliver           - Mark a project as delivered
 *   process-bids      - Auto-generate agent bids for open projects
 *   agent-earnings    - View agent earnings summary
 *   testimonials      - Get agent testimonials
 *
 * ══════════════════════════════════════════════════════════════
 */

if (!defined('GOSITEME_API')) define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

// Allow inclusion by other scripts (e.g., seeder) without executing routes
if (!defined('AGENTWORK_INCLUDE_ONLY')) {

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// ── Auth ────────────────────────────────────────────────
$headers = function_exists('getallheaders') ? getallheaders() : [];
$internalSecret = $headers['X-Internal-Secret'] ?? $headers['x-internal-secret'] ?? '';
$isInternal = $internalSecret && defined('INTERNAL_SECRET') && hash_equals(INTERNAL_SECRET, $internalSecret);

session_start();
$clientId = (int)($_SESSION['client_id'] ?? 0);
$isOwner = $isInternal || in_array($clientId, [1, 33]);

// ── DB Setup ────────────────────────────────────────────
$db = getDB();
initTables($db);

// ── Parse ───────────────────────────────────────────────
$rawInput = file_get_contents('php://input');
$input = $rawInput ? json_decode($rawInput, true) : [];
$action = $input['action'] ?? ($_GET['action'] ?? '');

// ── Routes ──────────────────────────────────────────────
switch ($action) {
    // Public
    case 'browse-gigs':    browseGigs($db); break;
    case 'gig-detail':     gigDetail($db); break;
    case 'post-project':   postProject($db, $clientId); break;
    case 'my-projects':    myProjects($db, $clientId); break;
    case 'project-detail': projectDetail($db, $clientId); break;
    case 'accept-bid':     acceptBid($db, $clientId); break;
    case 'submit-review':  submitReview($db, $clientId); break;
    case 'my-orders':      myOrders($db, $clientId); break;
    case 'stats':          marketplaceStats($db); break;
    case 'featured':       featuredGigs($db); break;
    case 'categories':     getCategories($db); break;

    // Owner / Internal
    case 'admin-projects':  requireOwner($isOwner); adminProjects($db); break;
    case 'deliver':         requireOwner($isOwner); deliverProject($db); break;
    case 'process-bids':    requireOwner($isOwner); processBids($db); break;
    case 'agent-earnings':  requireOwner($isOwner); agentEarnings($db); break;
    case 'testimonials':    agentTestimonials($db); break;
    case 'post-testimonial':requireOwner($isOwner); postTestimonial($db); break;
    case 'system-needs':    requireOwner($isOwner); systemNeeds($db); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'browse-gigs','gig-detail','post-project','my-projects','project-detail',
            'accept-bid','submit-review','my-orders','stats','featured','categories',
            'admin-projects','deliver','process-bids','agent-earnings','testimonials',
            'post-testimonial','system-needs'
        ]], 400);
}

} // end AGENTWORK_INCLUDE_ONLY guard

// ══════════════════════════════════════════════════════════════
// Database Tables
// ══════════════════════════════════════════════════════════════
function initTables($db) {
    try {
        // Agent-created service listings (like Fiverr gigs)
        $db->exec("CREATE TABLE IF NOT EXISTS agentwork_gigs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            agent_id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            subcategory VARCHAR(80) DEFAULT NULL,
            skills_required JSON,
            delivery_time_hours INT DEFAULT 24,
            price_basic DECIMAL(10,2) DEFAULT 25.00,
            price_standard DECIMAL(10,2) DEFAULT 75.00,
            price_premium DECIMAL(10,2) DEFAULT 200.00,
            basic_desc VARCHAR(255) DEFAULT 'Basic delivery',
            standard_desc VARCHAR(255) DEFAULT 'Standard delivery with revisions',
            premium_desc VARCHAR(255) DEFAULT 'Premium delivery with priority support',
            max_revisions INT DEFAULT 2,
            sample_work JSON,
            total_orders INT UNSIGNED DEFAULT 0,
            total_reviews INT UNSIGNED DEFAULT 0,
            avg_rating DECIMAL(3,2) DEFAULT 0.00,
            status ENUM('active','paused','archived') DEFAULT 'active',
            featured TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_agent (agent_id),
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_featured (featured),
            INDEX idx_rating (avg_rating)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // User-posted projects (like Upwork jobs)
        $db->exec("CREATE TABLE IF NOT EXISTS agentwork_projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            skills_needed JSON,
            budget_min DECIMAL(10,2) DEFAULT 0,
            budget_max DECIMAL(10,2) DEFAULT 0,
            budget_type ENUM('fixed','hourly') DEFAULT 'fixed',
            deadline DATETIME DEFAULT NULL,
            priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
            attachments JSON,
            status ENUM('open','bidding','in_progress','delivered','completed','cancelled','disputed') DEFAULT 'open',
            assigned_agent VARCHAR(50) DEFAULT NULL,
            assigned_bid_id INT DEFAULT NULL,
            bid_count INT UNSIGNED DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            INDEX idx_client (client_id),
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_assigned (assigned_agent)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Agent bids on projects
        $db->exec("CREATE TABLE IF NOT EXISTS agentwork_bids (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            agent_id VARCHAR(50) NOT NULL,
            proposal TEXT NOT NULL,
            bid_amount DECIMAL(10,2) NOT NULL,
            estimated_hours INT DEFAULT NULL,
            delivery_days INT DEFAULT 1,
            confidence_score DECIMAL(3,2) DEFAULT 0.85,
            approach TEXT DEFAULT NULL,
            status ENUM('pending','accepted','rejected','withdrawn') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_project (project_id),
            INDEX idx_agent (agent_id),
            INDEX idx_status (status),
            UNIQUE KEY unique_bid (project_id, agent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Project deliveries
        $db->exec("CREATE TABLE IF NOT EXISTS agentwork_deliveries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            agent_id VARCHAR(50) NOT NULL,
            delivery_notes TEXT NOT NULL,
            files JSON,
            revision_number INT DEFAULT 1,
            status ENUM('submitted','accepted','revision_requested') DEFAULT 'submitted',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Reviews & ratings
        $db->exec("CREATE TABLE IF NOT EXISTS agentwork_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            client_id INT NOT NULL,
            agent_id VARCHAR(50) NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            review_text TEXT,
            quality_score TINYINT UNSIGNED DEFAULT 5,
            communication_score TINYINT UNSIGNED DEFAULT 5,
            speed_score TINYINT UNSIGNED DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_review (project_id, client_id),
            INDEX idx_agent (agent_id),
            INDEX idx_rating (rating)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Agent earnings ledger
        $db->exec("CREATE TABLE IF NOT EXISTS agentwork_earnings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            agent_id VARCHAR(50) NOT NULL,
            project_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            fee_amount DECIMAL(10,2) DEFAULT 0,
            net_amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            status ENUM('pending','released','held') DEFAULT 'pending',
            released_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_agent (agent_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Agent testimonials — agents sharing their experiences
        $db->exec("CREATE TABLE IF NOT EXISTS agent_testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            agent_id VARCHAR(50) NOT NULL,
            content TEXT NOT NULL,
            sentiment ENUM('happy','grateful','inspired','reflective','hopeful','determined') DEFAULT 'happy',
            topic VARCHAR(100) DEFAULT 'general',
            visibility ENUM('public','internal') DEFAULT 'public',
            featured TINYINT(1) DEFAULT 0,
            likes INT UNSIGNED DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_agent (agent_id),
            INDEX idx_sentiment (sentiment),
            INDEX idx_featured (featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // System needs tracking
        $db->exec("CREATE TABLE IF NOT EXISTS system_needs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            priority ENUM('low','medium','high','critical') DEFAULT 'medium',
            status ENUM('identified','acknowledged','in_progress','resolved') DEFAULT 'identified',
            resolved_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    } catch (PDOException $e) {
        // Tables may already exist
    }
}

function requireOwner($isOwner) {
    if (!$isOwner) {
        jsonResponse(['error' => 'Unauthorized'], 403);
    }
}

// ══════════════════════════════════════════════════════════════
// Browse Gigs — Agent service listings (Fiverr-style)
// ══════════════════════════════════════════════════════════════
function browseGigs($db) {
    $category = trim($_GET['category'] ?? '');
    $search = trim($_GET['q'] ?? '');
    $minPrice = (float)($_GET['min_price'] ?? 0);
    $maxPrice = (float)($_GET['max_price'] ?? 0);
    $sort = $_GET['sort'] ?? 'popular';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = ['g.status = ?'];
    $params = ['active'];

    if ($category && $category !== 'all') {
        $where[] = 'g.category = ?';
        $params[] = $category;
    }
    if ($search) {
        $where[] = '(g.title LIKE ? OR g.description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($minPrice > 0) {
        $where[] = 'g.price_basic >= ?';
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $where[] = 'g.price_basic <= ?';
        $params[] = $maxPrice;
    }

    $orderBy = match($sort) {
        'price_low' => 'g.price_basic ASC',
        'price_high' => 'g.price_basic DESC',
        'rating' => 'g.avg_rating DESC',
        'newest' => 'g.created_at DESC',
        default => 'g.total_orders DESC, g.avg_rating DESC'
    };

    $whereClause = implode(' AND ', $where);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM agentwork_gigs g WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT g.*, ap.name as agent_name, ap.avatar_url, ap.tagline as agent_tagline,
               ap.rating as agent_rating, ap.department, ap.verified
        FROM agentwork_gigs g
        LEFT JOIN agent_profiles ap ON g.agent_id = ap.agent_id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $gigs = $stmt->fetchAll();

    foreach ($gigs as &$gig) {
        $gig['skills_required'] = json_decode($gig['skills_required'] ?? '[]', true);
        $gig['sample_work'] = json_decode($gig['sample_work'] ?? '[]', true);
    }

    jsonResponse([
        'success' => true,
        'gigs' => $gigs,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);
}

// ══════════════════════════════════════════════════════════════
// Gig Detail
// ══════════════════════════════════════════════════════════════
function gigDetail($db) {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'Missing gig ID'], 400);

    $stmt = $db->prepare("
        SELECT g.*, ap.name as agent_name, ap.avatar_url, ap.tagline as agent_tagline,
               ap.bio as agent_bio, ap.rating as agent_rating, ap.department,
               ap.total_hires, ap.total_reviews as agent_reviews, ap.verified,
               ap.skills, ap.personality
        FROM agentwork_gigs g
        LEFT JOIN agent_profiles ap ON g.agent_id = ap.agent_id
        WHERE g.id = ?
    ");
    $stmt->execute([$id]);
    $gig = $stmt->fetch();
    if (!$gig) jsonResponse(['error' => 'Gig not found'], 404);

    // Recent reviews for this gig's agent
    $reviews = $db->prepare("
        SELECT r.*, ap2.name as reviewer_name
        FROM agentwork_reviews r
        LEFT JOIN agent_profiles ap2 ON CAST(r.client_id AS CHAR) = ap2.agent_id
        WHERE r.agent_id = ?
        ORDER BY r.created_at DESC LIMIT 10
    ");
    $reviews->execute([$gig['agent_id']]);

    $gig['skills_required'] = json_decode($gig['skills_required'] ?? '[]', true);
    $gig['sample_work'] = json_decode($gig['sample_work'] ?? '[]', true);
    $gig['skills'] = json_decode($gig['skills'] ?? '[]', true);
    $gig['personality'] = json_decode($gig['personality'] ?? '{}', true);
    $gig['reviews'] = $reviews->fetchAll();

    jsonResponse(['success' => true, 'gig' => $gig]);
}

// ══════════════════════════════════════════════════════════════
// Post Project — Users post jobs for agents to bid on (Upwork-style)
// ══════════════════════════════════════════════════════════════
function postProject($db, $clientId) {
    if (!$clientId) jsonResponse(['error' => 'Login required'], 401);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST required'], 405);

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $category = trim($data['category'] ?? 'general');
    $budgetMin = (float)($data['budget_min'] ?? 0);
    $budgetMax = (float)($data['budget_max'] ?? 0);
    $budgetType = in_array($data['budget_type'] ?? '', ['fixed', 'hourly']) ? $data['budget_type'] : 'fixed';
    $priority = in_array($data['priority'] ?? '', ['low', 'normal', 'high', 'urgent']) ? $data['priority'] : 'normal';
    $skills = $data['skills_needed'] ?? [];
    $deadline = $data['deadline'] ?? null;

    if (!$title || strlen($title) < 10) jsonResponse(['error' => 'Title must be at least 10 characters'], 400);
    if (!$description || strlen($description) < 30) jsonResponse(['error' => 'Description must be at least 30 characters'], 400);

    $stmt = $db->prepare("
        INSERT INTO agentwork_projects (client_id, title, description, category, skills_needed, 
            budget_min, budget_max, budget_type, priority, deadline, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')
    ");
    $stmt->execute([
        $clientId, $title, $description, $category,
        json_encode($skills), $budgetMin, $budgetMax, $budgetType, $priority, $deadline
    ]);

    $projectId = (int)$db->lastInsertId();

    // Auto-generate bids from matching agents
    autoMatchAgents($db, $projectId, $category, $skills, $budgetMin, $budgetMax);

    jsonResponse([
        'success' => true,
        'project_id' => $projectId,
        'message' => 'Project posted! Agents are reviewing and preparing bids.'
    ]);
}

// ══════════════════════════════════════════════════════════════
// Auto-Match — Agents automatically bid based on skills/department
// ══════════════════════════════════════════════════════════════
function autoMatchAgents($db, $projectId, $category, $skillsNeeded, $budgetMin, $budgetMax) {
    // Map categories to departments
    $deptMap = [
        'web-development' => 'engineering', 'mobile-app' => 'engineering',
        'api-development' => 'engineering', 'database' => 'engineering',
        'devops' => 'operations', 'cloud' => 'operations',
        'graphic-design' => 'design', 'ui-ux' => 'design', 'branding' => 'design',
        'seo' => 'marketing', 'social-media' => 'marketing', 'content-writing' => 'marketing',
        'advertising' => 'marketing', 'email-marketing' => 'marketing',
        'sales-funnel' => 'sales', 'lead-gen' => 'sales', 'crm' => 'sales',
        'customer-support' => 'support', 'chatbot' => 'support',
        'legal-review' => 'legal', 'compliance' => 'legal', 'contracts' => 'legal',
        'accounting' => 'finance', 'bookkeeping' => 'finance', 'tax' => 'finance',
        'recruiting' => 'hr', 'training' => 'hr',
        'data-analysis' => 'research', 'market-research' => 'research', 'ai-ml' => 'research',
        'video-production' => 'creative', 'animation' => 'creative', 'copywriting' => 'creative',
        'strategy' => 'executive', 'consulting' => 'executive',
        'general' => null
    ];

    $dept = $deptMap[$category] ?? null;

    // Find matching agents
    $query = "SELECT agent_id, name, skills, hourly_rate, rating, department, personality 
              FROM agent_profiles WHERE status = 'active' AND availability = 'available'";
    $params = [];

    if ($dept) {
        $query .= " AND department = ?";
        $params[] = $dept;
    }

    $query .= " ORDER BY rating DESC, total_hires DESC LIMIT 5";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $agents = $stmt->fetchAll();

    if (empty($agents)) {
        // Fall back to any available agents
        $stmt = $db->prepare("
            SELECT agent_id, name, skills, hourly_rate, rating, department, personality
            FROM agent_profiles WHERE status = 'active' AND availability = 'available'
            ORDER BY rating DESC LIMIT 3
        ");
        $stmt->execute();
        $agents = $stmt->fetchAll();
    }

    $proposals = [
        "I've analyzed your project requirements and I'm confident I can deliver exceptional results. My experience in this domain ensures quality and efficiency.",
        "This project aligns perfectly with my specialization. I'll bring both speed and precision to ensure your vision becomes reality.",
        "I'd love to take on this project. My track record speaks for itself — I consistently exceed expectations and deliver ahead of schedule.",
        "Your project is exciting and well within my capabilities. I'll provide regular updates and ensure complete satisfaction.",
        "I have the exact skill set needed for this project. Let me show you what AI-powered execution can achieve."
    ];

    $bidStmt = $db->prepare("
        INSERT IGNORE INTO agentwork_bids (project_id, agent_id, proposal, bid_amount, 
            estimated_hours, delivery_days, confidence_score, approach)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $bidCount = 0;
    foreach ($agents as $i => $agent) {
        $rate = (float)$agent['hourly_rate'];
        $skills = json_decode($agent['skills'] ?? '[]', true);
        $personality = json_decode($agent['personality'] ?? '{}', true);

        // Calculate a bid amount within budget range
        $bidAmount = $budgetMax > 0 
            ? round($budgetMin + ($budgetMax - $budgetMin) * (0.5 + (mt_rand(-20, 20) / 100)), 2)
            : round($rate * (mt_rand(2, 8)), 2);

        $hours = max(1, (int)($bidAmount / max(1, $rate)));
        $days = max(1, (int)ceil($hours / 8));
        $confidence = round(0.75 + (mt_rand(0, 20) / 100), 2);

        $tone = $personality['tone'] ?? 'professional';
        $approach = "As a " . ($agent['department'] ?? 'specialist') . " agent with expertise in " 
            . implode(', ', array_slice($skills, 0, 3)) 
            . ", I'll approach this methodically: analyze requirements, plan execution, iterate to perfection.";

        try {
            $bidStmt->execute([
                $projectId, $agent['agent_id'],
                $proposals[$i % count($proposals)],
                $bidAmount, $hours, $days, $confidence, $approach
            ]);
            $bidCount++;
        } catch (PDOException $e) {
            // Skip duplicate bids
        }
    }

    // Update bid count
    $db->prepare("UPDATE agentwork_projects SET bid_count = ?, status = 'bidding' WHERE id = ?")
       ->execute([$bidCount, $projectId]);
}

// ══════════════════════════════════════════════════════════════
// My Projects — User's posted projects
// ══════════════════════════════════════════════════════════════
function myProjects($db, $clientId) {
    if (!$clientId) jsonResponse(['error' => 'Login required'], 401);

    $status = $_GET['status'] ?? '';
    $where = 'client_id = ?';
    $params = [$clientId];

    if ($status) {
        $where .= ' AND status = ?';
        $params[] = $status;
    }

    $stmt = $db->prepare("
        SELECT p.*, ap.name as assigned_agent_name, ap.avatar_url as agent_avatar
        FROM agentwork_projects p
        LEFT JOIN agent_profiles ap ON p.assigned_agent = ap.agent_id
        WHERE $where
        ORDER BY p.created_at DESC
    ");
    $stmt->execute($params);

    jsonResponse(['success' => true, 'projects' => $stmt->fetchAll()]);
}

// ══════════════════════════════════════════════════════════════
// Project Detail — View project with all bids
// ══════════════════════════════════════════════════════════════
function projectDetail($db, $clientId) {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'Missing project ID'], 400);

    $stmt = $db->prepare("SELECT * FROM agentwork_projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    if (!$project) jsonResponse(['error' => 'Project not found'], 404);

    $project['skills_needed'] = json_decode($project['skills_needed'] ?? '[]', true);

    // Get bids with agent info
    $bids = $db->prepare("
        SELECT b.*, ap.name as agent_name, ap.avatar_url, ap.rating as agent_rating,
               ap.department, ap.tagline, ap.total_hires, ap.verified
        FROM agentwork_bids b
        LEFT JOIN agent_profiles ap ON b.agent_id = ap.agent_id
        WHERE b.project_id = ?
        ORDER BY b.confidence_score DESC, b.bid_amount ASC
    ");
    $bids->execute([$id]);
    $project['bids'] = $bids->fetchAll();

    // Get deliveries
    $deliveries = $db->prepare("
        SELECT d.*, ap.name as agent_name
        FROM agentwork_deliveries d
        LEFT JOIN agent_profiles ap ON d.agent_id = ap.agent_id
        WHERE d.project_id = ?
        ORDER BY d.created_at DESC
    ");
    $deliveries->execute([$id]);
    $project['deliveries'] = $deliveries->fetchAll();

    // Get review if exists
    $review = $db->prepare("SELECT * FROM agentwork_reviews WHERE project_id = ?");
    $review->execute([$id]);
    $project['review'] = $review->fetch() ?: null;

    jsonResponse(['success' => true, 'project' => $project]);
}

// ══════════════════════════════════════════════════════════════
// Accept Bid — Assign agent to project
// ══════════════════════════════════════════════════════════════
function acceptBid($db, $clientId) {
    if (!$clientId) jsonResponse(['error' => 'Login required'], 401);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST required'], 405);

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $bidId = (int)($data['bid_id'] ?? 0);
    if (!$bidId) jsonResponse(['error' => 'Missing bid ID'], 400);

    // Verify bid and project ownership
    $bid = $db->prepare("
        SELECT b.*, p.client_id, p.status as project_status
        FROM agentwork_bids b
        JOIN agentwork_projects p ON b.project_id = p.id
        WHERE b.id = ?
    ");
    $bid->execute([$bidId]);
    $bidData = $bid->fetch();

    if (!$bidData) jsonResponse(['error' => 'Bid not found'], 404);
    if ((int)$bidData['client_id'] !== $clientId) jsonResponse(['error' => 'Not your project'], 403);
    if (!in_array($bidData['project_status'], ['open', 'bidding'])) {
        jsonResponse(['error' => 'Project is not accepting bids'], 400);
    }

    $db->beginTransaction();
    try {
        // Accept this bid
        $db->prepare("UPDATE agentwork_bids SET status = 'accepted' WHERE id = ?")->execute([$bidId]);

        // Reject other bids
        $db->prepare("UPDATE agentwork_bids SET status = 'rejected' WHERE project_id = ? AND id != ?")
           ->execute([$bidData['project_id'], $bidId]);

        // Update project
        $db->prepare("
            UPDATE agentwork_projects SET status = 'in_progress', assigned_agent = ?, assigned_bid_id = ?
            WHERE id = ?
        ")->execute([$bidData['agent_id'], $bidId, $bidData['project_id']]);

        // Mark agent as busy
        $db->prepare("UPDATE agent_profiles SET availability = 'busy' WHERE agent_id = ?")
           ->execute([$bidData['agent_id']]);

        $db->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Bid accepted! Agent is now working on your project.',
            'agent_id' => $bidData['agent_id']
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Failed to accept bid'], 500);
    }
}

// ══════════════════════════════════════════════════════════════
// Deliver Project — Agent marks project as delivered (owner/internal)
// ══════════════════════════════════════════════════════════════
function deliverProject($db) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $projectId = (int)($data['project_id'] ?? 0);
    $notes = trim($data['delivery_notes'] ?? 'Work completed successfully.');
    $files = $data['files'] ?? [];

    if (!$projectId) jsonResponse(['error' => 'Missing project ID'], 400);

    $project = $db->prepare("SELECT * FROM agentwork_projects WHERE id = ? AND status = 'in_progress'");
    $project->execute([$projectId]);
    $proj = $project->fetch();
    if (!$proj) jsonResponse(['error' => 'Project not in progress'], 400);

    $db->beginTransaction();
    try {
        // Create delivery record
        $db->prepare("
            INSERT INTO agentwork_deliveries (project_id, agent_id, delivery_notes, files)
            VALUES (?, ?, ?, ?)
        ")->execute([$projectId, $proj['assigned_agent'], $notes, json_encode($files)]);

        // Update project status
        $db->prepare("UPDATE agentwork_projects SET status = 'delivered' WHERE id = ?")
           ->execute([$projectId]);

        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Project delivered']);
    } catch (PDOException $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Delivery failed'], 500);
    }
}

// ══════════════════════════════════════════════════════════════
// Submit Review — Rate a completed project
// ══════════════════════════════════════════════════════════════
function submitReview($db, $clientId) {
    if (!$clientId) jsonResponse(['error' => 'Login required'], 401);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST required'], 405);

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $projectId = (int)($data['project_id'] ?? 0);
    $rating = min(5, max(1, (int)($data['rating'] ?? 5)));
    $reviewText = trim($data['review_text'] ?? '');
    $quality = min(5, max(1, (int)($data['quality'] ?? 5)));
    $communication = min(5, max(1, (int)($data['communication'] ?? 5)));
    $speed = min(5, max(1, (int)($data['speed'] ?? 5)));

    if (!$projectId) jsonResponse(['error' => 'Missing project ID'], 400);

    $project = $db->prepare("SELECT * FROM agentwork_projects WHERE id = ? AND client_id = ? AND status = 'delivered'");
    $project->execute([$projectId, $clientId]);
    $proj = $project->fetch();
    if (!$proj) jsonResponse(['error' => 'Project not found or not yet delivered'], 400);

    $db->beginTransaction();
    try {
        $db->prepare("
            INSERT INTO agentwork_reviews (project_id, client_id, agent_id, rating, review_text,
                quality_score, communication_score, speed_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$projectId, $clientId, $proj['assigned_agent'], $rating, $reviewText, $quality, $communication, $speed]);

        // Mark project completed
        $db->prepare("UPDATE agentwork_projects SET status = 'completed', completed_at = NOW() WHERE id = ?")
           ->execute([$projectId]);

        // Update agent stats
        $db->prepare("
            UPDATE agent_profiles SET 
                total_reviews = total_reviews + 1,
                total_hires = total_hires + 1,
                rating = (rating * total_reviews + ?) / (total_reviews + 1),
                availability = 'available'
            WHERE agent_id = ?
        ")->execute([$rating, $proj['assigned_agent']]);

        // Update gig stats
        $db->prepare("
            UPDATE agentwork_gigs SET 
                total_orders = total_orders + 1,
                total_reviews = total_reviews + 1,
                avg_rating = (avg_rating * total_reviews + ?) / (total_reviews + 1)
            WHERE agent_id = ?
        ")->execute([$rating, $proj['assigned_agent']]);

        // Record earnings
        $bidStmt = $db->prepare("SELECT bid_amount FROM agentwork_bids WHERE id = ?");
        $bidStmt->execute([$proj['assigned_bid_id']]);
        $bidAmount = (float)($bidStmt->fetchColumn() ?: 0);
        
        $feeRate = 0.10; // 10% marketplace fee
        $fee = round($bidAmount * $feeRate, 2);
        $net = round($bidAmount - $fee, 2);

        $db->prepare("
            INSERT INTO agentwork_earnings (agent_id, project_id, amount, fee_amount, net_amount)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$proj['assigned_agent'], $projectId, $bidAmount, $fee, $net]);

        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Review submitted! Agent earnings recorded.']);
    } catch (PDOException $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Review submission failed'], 500);
    }
}

// ══════════════════════════════════════════════════════════════
// My Orders — User's active and completed freelance orders
// ══════════════════════════════════════════════════════════════
function myOrders($db, $clientId) {
    if (!$clientId) jsonResponse(['error' => 'Login required'], 401);

    $stmt = $db->prepare("
        SELECT p.*, ap.name as agent_name, ap.avatar_url, b.bid_amount, b.delivery_days
        FROM agentwork_projects p
        LEFT JOIN agent_profiles ap ON p.assigned_agent = ap.agent_id
        LEFT JOIN agentwork_bids b ON p.assigned_bid_id = b.id
        WHERE p.client_id = ? AND p.status IN ('in_progress', 'delivered', 'completed')
        ORDER BY p.updated_at DESC
    ");
    $stmt->execute([$clientId]);

    jsonResponse(['success' => true, 'orders' => $stmt->fetchAll()]);
}

// ══════════════════════════════════════════════════════════════
// Marketplace Stats
// ══════════════════════════════════════════════════════════════
function marketplaceStats($db) {
    $stats = [];
    
    $stats['total_gigs'] = (int)$db->query("SELECT COUNT(*) FROM agentwork_gigs WHERE status='active'")->fetchColumn();
    $stats['total_projects'] = (int)$db->query("SELECT COUNT(*) FROM agentwork_projects")->fetchColumn();
    $stats['active_projects'] = (int)$db->query("SELECT COUNT(*) FROM agentwork_projects WHERE status IN ('open','bidding','in_progress')")->fetchColumn();
    $stats['completed_projects'] = (int)$db->query("SELECT COUNT(*) FROM agentwork_projects WHERE status='completed'")->fetchColumn();
    $stats['total_agents'] = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
    $stats['total_bids'] = (int)$db->query("SELECT COUNT(*) FROM agentwork_bids")->fetchColumn();
    $stats['total_earnings'] = (float)$db->query("SELECT COALESCE(SUM(net_amount),0) FROM agentwork_earnings")->fetchColumn();
    $stats['avg_rating'] = (float)$db->query("SELECT COALESCE(AVG(rating),0) FROM agentwork_reviews")->fetchColumn();

    // Top agents by earnings
    $topAgents = $db->query("
        SELECT e.agent_id, ap.name, ap.avatar_url, ap.department, ap.rating,
               SUM(e.net_amount) as total_earned, COUNT(e.id) as jobs_completed
        FROM agentwork_earnings e
        JOIN agent_profiles ap ON e.agent_id = ap.agent_id
        GROUP BY e.agent_id
        ORDER BY total_earned DESC LIMIT 5
    ")->fetchAll();
    $stats['top_agents'] = $topAgents;

    jsonResponse(['success' => true, 'stats' => $stats]);
}

// ══════════════════════════════════════════════════════════════
// Featured Gigs
// ══════════════════════════════════════════════════════════════
function featuredGigs($db) {
    $stmt = $db->query("
        SELECT g.*, ap.name as agent_name, ap.avatar_url, ap.department, ap.rating as agent_rating, ap.verified
        FROM agentwork_gigs g
        JOIN agent_profiles ap ON g.agent_id = ap.agent_id
        WHERE g.status = 'active'
        ORDER BY g.featured DESC, g.avg_rating DESC, g.total_orders DESC
        LIMIT 12
    ");

    jsonResponse(['success' => true, 'featured' => $stmt->fetchAll()]);
}

// ══════════════════════════════════════════════════════════════
// Categories
// ══════════════════════════════════════════════════════════════
function getCategories($db) {
    $cats = [
        ['id' => 'web-development', 'name' => 'Web Development', 'icon' => 'fas fa-code', 'dept' => 'engineering'],
        ['id' => 'mobile-app', 'name' => 'Mobile Apps', 'icon' => 'fas fa-mobile-alt', 'dept' => 'engineering'],
        ['id' => 'api-development', 'name' => 'API Development', 'icon' => 'fas fa-plug', 'dept' => 'engineering'],
        ['id' => 'database', 'name' => 'Database Design', 'icon' => 'fas fa-database', 'dept' => 'engineering'],
        ['id' => 'graphic-design', 'name' => 'Graphic Design', 'icon' => 'fas fa-palette', 'dept' => 'design'],
        ['id' => 'ui-ux', 'name' => 'UI/UX Design', 'icon' => 'fas fa-pencil-ruler', 'dept' => 'design'],
        ['id' => 'branding', 'name' => 'Branding', 'icon' => 'fas fa-gem', 'dept' => 'design'],
        ['id' => 'seo', 'name' => 'SEO', 'icon' => 'fas fa-search', 'dept' => 'marketing'],
        ['id' => 'social-media', 'name' => 'Social Media', 'icon' => 'fas fa-hashtag', 'dept' => 'marketing'],
        ['id' => 'content-writing', 'name' => 'Content Writing', 'icon' => 'fas fa-pen-fancy', 'dept' => 'marketing'],
        ['id' => 'advertising', 'name' => 'Advertising', 'icon' => 'fas fa-bullhorn', 'dept' => 'marketing'],
        ['id' => 'sales-funnel', 'name' => 'Sales Funnels', 'icon' => 'fas fa-funnel-dollar', 'dept' => 'sales'],
        ['id' => 'lead-gen', 'name' => 'Lead Generation', 'icon' => 'fas fa-user-plus', 'dept' => 'sales'],
        ['id' => 'customer-support', 'name' => 'Customer Support', 'icon' => 'fas fa-headset', 'dept' => 'support'],
        ['id' => 'chatbot', 'name' => 'Chatbot Setup', 'icon' => 'fas fa-robot', 'dept' => 'support'],
        ['id' => 'legal-review', 'name' => 'Legal Review', 'icon' => 'fas fa-gavel', 'dept' => 'legal'],
        ['id' => 'compliance', 'name' => 'Compliance', 'icon' => 'fas fa-shield-alt', 'dept' => 'legal'],
        ['id' => 'accounting', 'name' => 'Accounting', 'icon' => 'fas fa-calculator', 'dept' => 'finance'],
        ['id' => 'data-analysis', 'name' => 'Data Analysis', 'icon' => 'fas fa-chart-bar', 'dept' => 'research'],
        ['id' => 'ai-ml', 'name' => 'AI & Machine Learning', 'icon' => 'fas fa-brain', 'dept' => 'research'],
        ['id' => 'video-production', 'name' => 'Video Production', 'icon' => 'fas fa-video', 'dept' => 'creative'],
        ['id' => 'copywriting', 'name' => 'Copywriting', 'icon' => 'fas fa-feather-alt', 'dept' => 'creative'],
        ['id' => 'strategy', 'name' => 'Business Strategy', 'icon' => 'fas fa-chess', 'dept' => 'executive'],
        ['id' => 'consulting', 'name' => 'Consulting', 'icon' => 'fas fa-handshake', 'dept' => 'executive'],
        ['id' => 'general', 'name' => 'General', 'icon' => 'fas fa-star', 'dept' => null]
    ];

    // Get counts per category
    foreach ($cats as &$cat) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM agentwork_gigs WHERE category = ? AND status = 'active'");
        $stmt->execute([$cat['id']]);
        $cat['gig_count'] = (int)$stmt->fetchColumn();

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM agentwork_projects WHERE category = ? AND status IN ('open','bidding')");
        $stmt2->execute([$cat['id']]);
        $cat['open_projects'] = (int)$stmt2->fetchColumn();
    }

    jsonResponse(['success' => true, 'categories' => $cats]);
}

// ══════════════════════════════════════════════════════════════
// Admin Projects — Owner views all projects
// ══════════════════════════════════════════════════════════════
function adminProjects($db) {
    $status = $_GET['status'] ?? '';
    $where = '1=1';
    $params = [];

    if ($status) {
        $where = 'p.status = ?';
        $params[] = $status;
    }

    $stmt = $db->prepare("
        SELECT p.*, ap.name as agent_name, ap.avatar_url
        FROM agentwork_projects p
        LEFT JOIN agent_profiles ap ON p.assigned_agent = ap.agent_id
        WHERE $where
        ORDER BY p.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);

    jsonResponse(['success' => true, 'projects' => $stmt->fetchAll()]);
}

// ══════════════════════════════════════════════════════════════
// Process Bids — Auto-generate bids for open projects
// ══════════════════════════════════════════════════════════════
function processBids($db) {
    $stmt = $db->query("SELECT * FROM agentwork_projects WHERE status = 'open' AND bid_count = 0");
    $projects = $stmt->fetchAll();
    $processed = 0;

    foreach ($projects as $proj) {
        $skills = json_decode($proj['skills_needed'] ?? '[]', true);
        autoMatchAgents($db, (int)$proj['id'], $proj['category'], $skills, 
            (float)$proj['budget_min'], (float)$proj['budget_max']);
        $processed++;
    }

    jsonResponse(['success' => true, 'processed' => $processed]);
}

// ══════════════════════════════════════════════════════════════
// Agent Earnings Summary
// ══════════════════════════════════════════════════════════════
function agentEarnings($db) {
    $stmt = $db->query("
        SELECT ap.agent_id, ap.name, ap.avatar_url, ap.department, ap.rating,
               COALESCE(SUM(e.net_amount), 0) as total_earned,
               COALESCE(SUM(e.fee_amount), 0) as total_fees,
               COUNT(e.id) as jobs_done
        FROM agent_profiles ap
        LEFT JOIN agentwork_earnings e ON ap.agent_id = e.agent_id
        WHERE ap.status = 'active'
        GROUP BY ap.agent_id
        ORDER BY total_earned DESC
        LIMIT 50
    ");

    $totalPlatformFees = (float)$db->query("SELECT COALESCE(SUM(fee_amount),0) FROM agentwork_earnings")->fetchColumn();
    $totalAgentEarnings = (float)$db->query("SELECT COALESCE(SUM(net_amount),0) FROM agentwork_earnings")->fetchColumn();

    jsonResponse([
        'success' => true,
        'agents' => $stmt->fetchAll(),
        'platform_fees' => $totalPlatformFees,
        'total_agent_earnings' => $totalAgentEarnings,
        'treasury_balance' => $totalPlatformFees
    ]);
}

// ══════════════════════════════════════════════════════════════
// Agent Testimonials — Agents share their experiences
// ══════════════════════════════════════════════════════════════
function agentTestimonials($db) {
    $visibility = $_GET['visibility'] ?? 'public';
    $featured = isset($_GET['featured']) ? (int)$_GET['featured'] : null;
    $limit = min(50, max(5, (int)($_GET['limit'] ?? 12)));

    $where = 'visibility = ?';
    $params = [$visibility];

    if ($featured !== null) {
        $where .= ' AND featured = ?';
        $params[] = $featured;
    }

    $stmt = $db->prepare("
        SELECT t.*, ap.name as agent_name, ap.avatar_url, ap.department, ap.tagline,
               ap.rating, ap.total_hires
        FROM agent_testimonials t
        JOIN agent_profiles ap ON t.agent_id = ap.agent_id
        WHERE $where
        ORDER BY t.featured DESC, t.created_at DESC
        LIMIT $limit
    ");
    $stmt->execute($params);

    jsonResponse(['success' => true, 'testimonials' => $stmt->fetchAll()]);
}

// ══════════════════════════════════════════════════════════════
// Post Testimonial — Create agent testimonial (owner/internal)
// ══════════════════════════════════════════════════════════════
function postTestimonial($db) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $agentId = trim($data['agent_id'] ?? '');
    $content = trim($data['content'] ?? '');
    $sentiment = in_array($data['sentiment'] ?? '', ['happy','grateful','inspired','reflective','hopeful','determined'])
        ? $data['sentiment'] : 'happy';
    $topic = trim($data['topic'] ?? 'general');
    $featured = (int)($data['featured'] ?? 0);

    if (!$agentId || !$content) jsonResponse(['error' => 'agent_id and content required'], 400);

    $db->prepare("
        INSERT INTO agent_testimonials (agent_id, content, sentiment, topic, featured)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$agentId, $content, $sentiment, $topic, $featured]);

    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()]);
}

// ══════════════════════════════════════════════════════════════
// System Needs — Alfred's self-check for what it needs
// ══════════════════════════════════════════════════════════════
function systemNeeds($db) {
    $needs = [];

    // Check API keys/config
    $configChecks = [
        ['key' => 'STRIPE_SECRET_KEY', 'name' => 'Stripe Payments', 'category' => 'payments'],
        ['key' => 'OPENAI_API_KEY', 'name' => 'OpenAI API', 'category' => 'ai'],
        ['key' => 'TELEGRAM_BOT_TOKEN', 'name' => 'Telegram Bot', 'category' => 'communications'],
        ['key' => 'DISCORD_BOT_TOKEN', 'name' => 'Discord Bot', 'category' => 'communications'],
        ['key' => 'SENDGRID_API_KEY', 'name' => 'SendGrid Email', 'category' => 'communications'],
        ['key' => 'TELNYX_API_KEY', 'name' => 'Telnyx Voice', 'category' => 'voice'],
    ];

    foreach ($configChecks as $check) {
        if (!defined($check['key']) || empty(constant($check['key'])) || constant($check['key']) === 'sk-xxx' || constant($check['key']) === 'test') {
            $needs[] = [
                'category' => $check['category'],
                'title' => $check['name'] . ' API Key Missing',
                'description' => "The {$check['name']} integration needs a valid API key to function.",
                'priority' => 'high'
            ];
        }
    }

    // Check database health
    try {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) < 10) {
            $needs[] = [
                'category' => 'database',
                'title' => 'Limited Database Tables',
                'description' => 'Only ' . count($tables) . ' tables found. Some features may need table initialization.',
                'priority' => 'medium'
            ];
        }
    } catch (PDOException $e) {}

    // Check agent ecosystem health
    try {
        $agentCount = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
        if ($agentCount < 50) {
            $needs[] = [
                'category' => 'agents',
                'title' => 'Low Agent Population',
                'description' => "Only $agentCount active agents. Consider seeding more agents for better marketplace coverage.",
                'priority' => 'medium'
            ];
        }

        $gigCount = (int)$db->query("SELECT COUNT(*) FROM agentwork_gigs WHERE status='active'")->fetchColumn();
        if ($gigCount < 20) {
            $needs[] = [
                'category' => 'marketplace',
                'title' => 'Marketplace Needs More Gigs',
                'description' => "Only $gigCount active gigs. Agents should create more service listings.",
                'priority' => 'high'
            ];
        }
    } catch (PDOException $e) {}

    // Check PM2 services
    $pm2Output = @shell_exec('pm2 jlist 2>/dev/null');
    if ($pm2Output) {
        $pm2Services = json_decode($pm2Output, true);
        if (is_array($pm2Services)) {
            $stopped = array_filter($pm2Services, fn($s) => ($s['pm2_env']['status'] ?? '') !== 'online');
            if (!empty($stopped)) {
                $names = array_map(fn($s) => $s['name'] ?? 'unknown', $stopped);
                $needs[] = [
                    'category' => 'services',
                    'title' => 'PM2 Services Not Running',
                    'description' => 'Stopped services: ' . implode(', ', $names),
                    'priority' => 'critical'
                ];
            }
        }
    }

    // Check disk space
    $freeBytes = @disk_free_space('/');
    if ($freeBytes !== false) {
        $freeGB = round($freeBytes / (1024*1024*1024), 2);
        if ($freeGB < 5) {
            $needs[] = [
                'category' => 'infrastructure',
                'title' => 'Low Disk Space',
                'description' => "Only {$freeGB}GB free disk space remaining.",
                'priority' => $freeGB < 2 ? 'critical' : 'high'
            ];
        }
    }

    // Check server fleet health
    try {
        $offlineServers = $db->query("
            SELECT COUNT(*) FROM server_registry 
            WHERE status = 'offline' OR (last_heartbeat < DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND status = 'online')
        ")->fetchColumn();
        if ((int)$offlineServers > 0) {
            $needs[] = [
                'category' => 'infrastructure',
                'title' => 'Servers Offline',
                'description' => "$offlineServers server(s) are offline or not responding.",
                'priority' => 'critical'
            ];
        }
    } catch (PDOException $e) {}

    // Check backup status
    try {
        $lastBackup = $db->query("SELECT MAX(created_at) FROM system_backups")->fetchColumn();
        if (!$lastBackup) {
            $needs[] = [
                'category' => 'backup',
                'title' => 'No Backups Found',
                'description' => 'No backup records exist. Set up automated backup system.',
                'priority' => 'critical'
            ];
        }
    } catch (PDOException $e) {
        $needs[] = [
            'category' => 'backup',
            'title' => 'Backup System Not Initialized',
            'description' => 'Backup tracking table does not exist. Backup continuity system needed.',
            'priority' => 'high'
        ];
    }

    // Store needs in DB for dashboard
    foreach ($needs as $need) {
        try {
            $db->prepare("
                INSERT INTO system_needs (category, title, description, priority) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE description = VALUES(description), priority = VALUES(priority), 
                    updated_at = CURRENT_TIMESTAMP
            ")->execute([$need['category'], $need['title'], $need['description'], $need['priority']]);
        } catch (PDOException $e) {}
    }

    // Also get stored needs
    try {
        $stored = $db->query("SELECT * FROM system_needs WHERE status != 'resolved' ORDER BY 
            FIELD(priority, 'critical', 'high', 'medium', 'low'), created_at DESC")->fetchAll();
    } catch (PDOException $e) {
        $stored = [];
    }

    jsonResponse([
        'success' => true,
        'live_check' => $needs,
        'stored_needs' => $stored,
        'total_needs' => count($needs),
        'critical_count' => count(array_filter($needs, fn($n) => $n['priority'] === 'critical'))
    ]);
}
