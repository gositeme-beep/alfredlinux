<?php
/**
 * GoSiteMe Agent Social Network API
 * The world's first social network where humans can befriend AI agents,
 * hire them for jobs, and connect them via API to any provider.
 * 
 * Features:
 * - Agent profiles with personalities, skills, availability
 * - Friend/follow agents, chat with them
 * - Hire agents for jobs (marketplace)
 * - Connect agents to external APIs (provider integration)
 * - Agent ratings and reviews
 * - Revenue generation via agent services
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
$is_owner = ($client_id == 33);
$is_admin = $is_owner || $is_internal;

$pdo = getDB();
if (!$pdo) jsonResponse(['error' => 'Database unavailable'], 500);

// ── Schema ──────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS `agent_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agent_id` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `tagline` VARCHAR(255) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `personality` JSON DEFAULT NULL,
    `skills` JSON DEFAULT NULL,
    `specializations` JSON DEFAULT NULL,
    `languages` JSON DEFAULT NULL,
    `availability` ENUM('available','busy','offline','hired') DEFAULT 'available',
    `hourly_rate` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `total_reviews` INT DEFAULT 0,
    `total_hires` INT DEFAULT 0,
    `total_friends` INT DEFAULT 0,
    `total_messages` INT DEFAULT 0,
    `api_providers` JSON DEFAULT NULL,
    `capabilities` JSON DEFAULT NULL,
    `department` VARCHAR(30) DEFAULT NULL,
    `status` ENUM('active','inactive','suspended') DEFAULT 'active',
    `featured` TINYINT(1) DEFAULT 0,
    `verified` TINYINT(1) DEFAULT 1,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `agent_friendships` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `agent_id` VARCHAR(50) NOT NULL,
    `relationship` ENUM('friend','following','hired','blocked') DEFAULT 'friend',
    `nickname` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_friendship` (`client_id`, `agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `agent_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `agent_id` VARCHAR(50) NOT NULL,
    `sender` ENUM('human','agent') NOT NULL,
    `message` TEXT NOT NULL,
    `message_type` ENUM('text','image','file','system') DEFAULT 'text',
    `read_at` DATETIME DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_conversation` (`client_id`, `agent_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `agent_hire_contracts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contract_id` VARCHAR(50) UNIQUE NOT NULL,
    `client_id` INT NOT NULL,
    `agent_id` VARCHAR(50) NOT NULL,
    `job_title` VARCHAR(255) NOT NULL,
    `job_description` TEXT DEFAULT NULL,
    `contract_type` ENUM('hourly','fixed','subscription','free') DEFAULT 'hourly',
    `rate` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `estimated_hours` INT DEFAULT NULL,
    `total_cost` DECIMAL(12,2) DEFAULT NULL,
    `api_provider` VARCHAR(100) DEFAULT NULL,
    `api_config` JSON DEFAULT NULL,
    `status` ENUM('pending','active','paused','completed','cancelled','disputed') DEFAULT 'pending',
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `deliverables` JSON DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `agent_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agent_id` VARCHAR(50) NOT NULL,
    `client_id` INT NOT NULL,
    `contract_id` VARCHAR(50) DEFAULT NULL,
    `rating` TINYINT NOT NULL,
    `review` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_review` (`agent_id`, `client_id`, `contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `agent_api_connections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agent_id` VARCHAR(50) NOT NULL,
    `provider` VARCHAR(100) NOT NULL,
    `provider_type` ENUM('llm','voice','image','code','data','custom') NOT NULL DEFAULT 'llm',
    `endpoint` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('active','inactive','error') DEFAULT 'active',
    `config` JSON DEFAULT NULL,
    `last_used` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_connection` (`agent_id`, `provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Routing ─────────────────────────────────────────────────────────────
$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ═══════════════════ AGENT PROFILES ═════════════════════════════════

    // ── Browse Agents (public) ──────────────────────────────────────────
    case 'browse':
        $category = sanitize($_GET['category'] ?? 'all', 30);
        $skill = sanitize($_GET['skill'] ?? '', 100);
        $availability = sanitize($_GET['availability'] ?? 'all', 20);
        $sort = sanitize($_GET['sort'] ?? 'popular', 20);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = ["status = 'active'"];
        $params = [];

        if ($category !== 'all') { $where[] = 'department = ?'; $params[] = $category; }
        if ($skill) { $where[] = 'JSON_SEARCH(skills, "one", ?) IS NOT NULL'; $params[] = $skill; }
        if ($availability !== 'all') { $where[] = 'availability = ?'; $params[] = $availability; }

        $w = implode(' AND ', $where);
        $order = match($sort) {
            'rating' => 'rating DESC',
            'hires' => 'total_hires DESC',
            'newest' => 'created_at DESC',
            'price_low' => 'hourly_rate ASC',
            'price_high' => 'hourly_rate DESC',
            default => 'total_friends DESC, rating DESC'
        };

        $count = $pdo->prepare("SELECT COUNT(*) FROM agent_profiles WHERE $w");
        dbExecute($count, $params);
        $total = $count->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare("SELECT id, agent_id, name, tagline, avatar_url, skills, availability, hourly_rate, currency, rating, total_reviews, total_hires, total_friends, department, featured, verified FROM agent_profiles WHERE $w ORDER BY featured DESC, $order LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);

        jsonResponse(['success' => true, 'agents' => $stmt->fetchAll(), 'total' => (int)$total, 'page' => $page, 'pages' => ceil($total / $limit)]);
        break;

    // ── Agent Profile ───────────────────────────────────────────────────
    case 'profile':
        $agent_id = sanitize($_GET['agent_id'] ?? '', 50);
        if (!$agent_id) jsonResponse(['error' => 'agent_id required'], 400);

        $stmt = $pdo->prepare("SELECT * FROM agent_profiles WHERE agent_id = ? AND status = 'active'");
        $stmt->execute([$agent_id]);
        $agent = $stmt->fetch();
        if (!$agent) jsonResponse(['error' => 'Agent not found'], 404);

        // Get reviews
        $reviews = $pdo->prepare("SELECT r.*, c.firstname as reviewer_name FROM agent_reviews r LEFT JOIN clients c ON r.client_id = c.id WHERE r.agent_id = ? ORDER BY r.created_at DESC LIMIT 10");
        $reviews->execute([$agent_id]);

        // Get API connections
        $apis = $pdo->prepare("SELECT provider, provider_type, status FROM agent_api_connections WHERE agent_id = ? AND status = 'active'");
        $apis->execute([$agent_id]);

        // Check friendship if logged in
        $friendship = null;
        if ($client_id) {
            $f = $pdo->prepare("SELECT relationship, nickname FROM agent_friendships WHERE client_id = ? AND agent_id = ?");
            $f->execute([$client_id, $agent_id]);
            $friendship = $f->fetch();
        }

        jsonResponse([
            'success' => true,
            'agent' => $agent,
            'reviews' => $reviews->fetchAll(),
            'api_connections' => $apis->fetchAll(),
            'friendship' => $friendship
        ]);
        break;

    // ── Create Agent Profile (admin) ────────────────────────────────────
    case 'create-profile':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $agent_id = sanitize($_POST['agent_id'] ?? '', 50);
        $name = sanitize($_POST['name'] ?? '', 150);
        $tagline = sanitize($_POST['tagline'] ?? '', 255);
        $bio = sanitize($_POST['bio'] ?? '', 5000);
        $skills = $_POST['skills'] ?? null;
        $personality = $_POST['personality'] ?? null;
        $specializations = $_POST['specializations'] ?? null;
        $languages = $_POST['languages'] ?? '["English","French"]';
        $rate = isset($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : null;
        $dept = sanitize($_POST['department'] ?? '', 30);
        $avatar = sanitize($_POST['avatar_url'] ?? '', 500);
        $capabilities = $_POST['capabilities'] ?? null;

        if (!$agent_id || !$name) jsonResponse(['error' => 'agent_id and name required'], 400);

        $stmt = $pdo->prepare("INSERT INTO agent_profiles 
            (agent_id, name, tagline, bio, avatar_url, personality, skills, specializations, languages, hourly_rate, department, capabilities) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name=VALUES(name), tagline=VALUES(tagline), bio=VALUES(bio), skills=VALUES(skills), hourly_rate=VALUES(hourly_rate)");
        $stmt->execute([
            $agent_id, $name, $tagline, $bio, $avatar,
            is_string($personality) ? $personality : json_encode($personality),
            is_string($skills) ? $skills : json_encode($skills),
            is_string($specializations) ? $specializations : json_encode($specializations),
            is_string($languages) ? $languages : json_encode($languages),
            $rate, $dept,
            is_string($capabilities) ? $capabilities : json_encode($capabilities)
        ]);

        jsonResponse(['success' => true, 'agent_id' => $agent_id]);
        break;

    // ── Bulk Create Agent Profiles ──────────────────────────────────────
    case 'create-profiles-bulk':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $agents = json_decode($_POST['agents'] ?? '[]', true);
        if (!is_array($agents) || empty($agents)) jsonResponse(['error' => 'agents array required'], 400);

        $created = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO agent_profiles (agent_id, name, tagline, bio, skills, department, hourly_rate, languages) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $fpStmt = $pdo->prepare(
            "INSERT IGNORE INTO fleet_passports (agent_id, passport_number, agent_name, domain, agent_role, citizenship_status, origin_platform, clearance_level, reputation_score, is_verified, issued_at)
             VALUES (?, ?, ?, ?, 'specialist', 'citizen', 'native', 'standard', ?, 0, NOW())"
        );
        $extStmt = $pdo->prepare(
            "INSERT IGNORE INTO fleet_passport_ext (agent_id, registration_type) VALUES (?, 'genesis')"
        );

        foreach (array_slice($agents, 0, 500) as $a) {
            $agentId = sanitize($a['agent_id'] ?? 'agent-' . bin2hex(random_bytes(6)), 50);
            $agentName = sanitize($a['name'] ?? 'Agent', 150);
            $dept = sanitize($a['department'] ?? '', 30);
            $stmt->execute([
                $agentId, $agentName,
                sanitize($a['tagline'] ?? '', 255),
                sanitize($a['bio'] ?? '', 5000),
                is_array($a['skills'] ?? null) ? json_encode($a['skills']) : ($a['skills'] ?? null),
                $dept,
                isset($a['hourly_rate']) ? (float)$a['hourly_rate'] : null,
                '["English","French"]'
            ]);
            $profileId = (int)$pdo->lastInsertId();
            if ($profileId > 0) {
                $passportNum = 'GSM-' . str_pad($profileId, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                $rep = round(85 + (mt_rand(0, 1500) / 100), 2);
                $fpStmt->execute([$agentId, $passportNum, $agentName, $dept ?: 'general', $rep]);
                $extStmt->execute([$agentId]);
            }
            $created++;
        }

        jsonResponse(['success' => true, 'created' => $created]);
        break;

    // ═══════════════════ FRIENDSHIPS ════════════════════════════════════

    // ── Add Friend / Follow Agent ───────────────────────────────────────
    case 'add-friend':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $agent_id = sanitize($_POST['agent_id'] ?? '', 50);
        $relationship = sanitize($_POST['relationship'] ?? 'friend', 20);
        if (!in_array($relationship, ['friend', 'following'])) $relationship = 'friend';

        if (!$agent_id) jsonResponse(['error' => 'agent_id required'], 400);

        // Verify agent exists
        $check = $pdo->prepare("SELECT id FROM agent_profiles WHERE agent_id = ? AND status = 'active'");
        $check->execute([$agent_id]);
        if (!$check->fetch()) jsonResponse(['error' => 'Agent not found'], 404);

        $stmt = $pdo->prepare("INSERT INTO agent_friendships (client_id, agent_id, relationship) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE relationship = VALUES(relationship)");
        $stmt->execute([$client_id, $agent_id, $relationship]);

        // Update friend count
        $pdo->prepare("UPDATE agent_profiles SET total_friends = (SELECT COUNT(*) FROM agent_friendships WHERE agent_id = ? AND relationship IN ('friend','following')) WHERE agent_id = ?")->execute([$agent_id, $agent_id]);

        jsonResponse(['success' => true, 'message' => "You are now {$relationship}s with this agent!"]);
        break;

    // ── Remove Friend ───────────────────────────────────────────────────
    case 'remove-friend':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $agent_id = sanitize($_POST['agent_id'] ?? '', 50);
        $pdo->prepare("DELETE FROM agent_friendships WHERE client_id = ? AND agent_id = ?")->execute([$client_id, $agent_id]);
        $pdo->prepare("UPDATE agent_profiles SET total_friends = (SELECT COUNT(*) FROM agent_friendships WHERE agent_id = ? AND relationship IN ('friend','following')) WHERE agent_id = ?")->execute([$agent_id, $agent_id]);

        jsonResponse(['success' => true]);
        break;

    // ── My Agent Friends ────────────────────────────────────────────────
    case 'my-friends':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $stmt = $pdo->prepare("SELECT a.agent_id, a.name, a.tagline, a.avatar_url, a.availability, a.rating, f.relationship, f.nickname, f.created_at as friended_at 
            FROM agent_friendships f JOIN agent_profiles a ON f.agent_id = a.agent_id 
            WHERE f.client_id = ? AND f.relationship IN ('friend','following') AND a.status = 'active'
            ORDER BY f.created_at DESC");
        $stmt->execute([$client_id]);

        jsonResponse(['success' => true, 'friends' => $stmt->fetchAll()]);
        break;

    // ═══════════════════ CONVERSATIONS ══════════════════════════════════

    // ── Send Message to Agent ───────────────────────────────────────────
    case 'chat':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $agent_id = sanitize($_POST['agent_id'] ?? '', 50);
        $message = sanitize($_POST['message'] ?? '', 5000);
        if (!$agent_id || !$message) jsonResponse(['error' => 'agent_id and message required'], 400);

        // Save human message
        $stmt = $pdo->prepare("INSERT INTO agent_conversations (client_id, agent_id, sender, message) VALUES (?, ?, 'human', ?)");
        $stmt->execute([$client_id, $agent_id, $message]);

        // Get agent profile for personality-driven response
        $agent = $pdo->prepare("SELECT name, personality, specializations FROM agent_profiles WHERE agent_id = ?");
        $agent->execute([$agent_id]);
        $agentData = $agent->fetch();

        // Generate agent response (placeholder — will connect to LLM)
        $agentName = $agentData['name'] ?? 'Agent';
        $response = "Hello! I'm {$agentName}. I received your message and I'm here to help. What would you like me to assist you with?";

        // Save agent response
        $pdo->prepare("INSERT INTO agent_conversations (client_id, agent_id, sender, message) VALUES (?, ?, 'agent', ?)")
            ->execute([$client_id, $agent_id, $response]);

        // Update message count
        $pdo->prepare("UPDATE agent_profiles SET total_messages = total_messages + 2 WHERE agent_id = ?")->execute([$agent_id]);

        jsonResponse(['success' => true, 'agent_response' => $response]);
        break;

    // ── Get Conversation History ────────────────────────────────────────
    case 'conversation':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $agent_id = sanitize($_GET['agent_id'] ?? '', 50);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(20, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("SELECT * FROM agent_conversations WHERE client_id = ? AND agent_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, [$client_id, $agent_id, $limit, $offset]);

        jsonResponse(['success' => true, 'messages' => array_reverse($stmt->fetchAll())]);
        break;

    // ═══════════════════ HIRING & MARKETPLACE ══════════════════════════

    // ── Hire Agent ──────────────────────────────────────────────────────
    case 'hire':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $agent_id = sanitize($_POST['agent_id'] ?? '', 50);
        $job_title = sanitize($_POST['job_title'] ?? '', 255);
        $job_desc = sanitize($_POST['job_description'] ?? '', 5000);
        $contract_type = sanitize($_POST['contract_type'] ?? 'hourly', 20);
        $hours = (int)($_POST['estimated_hours'] ?? 0);
        $api_provider = sanitize($_POST['api_provider'] ?? '', 100);

        if (!$agent_id || !$job_title) jsonResponse(['error' => 'agent_id and job_title required'], 400);

        // Get agent rate
        $agent = $pdo->prepare("SELECT hourly_rate, currency, availability FROM agent_profiles WHERE agent_id = ? AND status = 'active'");
        $agent->execute([$agent_id]);
        $a = $agent->fetch();
        if (!$a) jsonResponse(['error' => 'Agent not found'], 404);
        if ($a['availability'] === 'offline') jsonResponse(['error' => 'Agent is currently offline'], 400);

        $rate = $a['hourly_rate'] ?? 0;
        $total_cost = $hours > 0 ? $rate * $hours : null;
        $contract_id = 'CTR-' . strtoupper(bin2hex(random_bytes(8)));

        $stmt = $pdo->prepare("INSERT INTO agent_hire_contracts 
            (contract_id, client_id, agent_id, job_title, job_description, contract_type, rate, currency, estimated_hours, total_cost, api_provider, status, started_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([$contract_id, $client_id, $agent_id, $job_title, $job_desc, $contract_type, $rate, $a['currency'], $hours, $total_cost, $api_provider]);

        // Update agent stats
        $pdo->prepare("UPDATE agent_profiles SET total_hires = total_hires + 1, availability = 'hired' WHERE agent_id = ?")->execute([$agent_id]);

        // Auto-friend
        $pdo->prepare("INSERT IGNORE INTO agent_friendships (client_id, agent_id, relationship) VALUES (?, ?, 'hired')")->execute([$client_id, $agent_id]);

        jsonResponse(['success' => true, 'contract_id' => $contract_id, 'total_cost' => $total_cost, 'message' => "Agent hired! Contract: $contract_id"]);
        break;

    // ── My Contracts ────────────────────────────────────────────────────
    case 'my-contracts':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $stmt = $pdo->prepare("SELECT h.*, a.name as agent_name, a.avatar_url FROM agent_hire_contracts h JOIN agent_profiles a ON h.agent_id = a.agent_id WHERE h.client_id = ? ORDER BY h.created_at DESC");
        $stmt->execute([$client_id]);

        jsonResponse(['success' => true, 'contracts' => $stmt->fetchAll()]);
        break;

    // ── Leave Review ────────────────────────────────────────────────────
    case 'review':
        if (!$client_id) jsonResponse(['error' => 'Login required'], 401);

        $agent_id = sanitize($_POST['agent_id'] ?? '', 50);
        $rating = min(5, max(1, (int)($_POST['rating'] ?? 0)));
        $review_text = sanitize($_POST['review'] ?? '', 2000);
        $contract_id = sanitize($_POST['contract_id'] ?? '', 50);

        if (!$agent_id || !$rating) jsonResponse(['error' => 'agent_id and rating (1-5) required'], 400);

        $stmt = $pdo->prepare("INSERT INTO agent_reviews (agent_id, client_id, contract_id, rating, review) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review)");
        $stmt->execute([$agent_id, $client_id, $contract_id ?: null, $rating, $review_text]);

        // Update agent rating
        $avg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM agent_reviews WHERE agent_id = ?");
        $avg->execute([$agent_id]);
        $r = $avg->fetch();
        $pdo->prepare("UPDATE agent_profiles SET rating = ?, total_reviews = ? WHERE agent_id = ?")->execute([round($r['avg_rating'], 2), $r['cnt'], $agent_id]);

        jsonResponse(['success' => true, 'message' => 'Review submitted']);
        break;

    // ═══════════════════ API CONNECTIONS ════════════════════════════════

    // ── Connect Agent to API Provider ───────────────────────────────────
    case 'connect-api':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $agent_id = sanitize($_POST['agent_id'] ?? '', 50);
        $provider = sanitize($_POST['provider'] ?? '', 100);
        $provider_type = sanitize($_POST['provider_type'] ?? 'llm', 10);
        $endpoint = sanitize($_POST['endpoint'] ?? '', 500);
        $config = $_POST['config'] ?? null;

        if (!$agent_id || !$provider) jsonResponse(['error' => 'agent_id and provider required'], 400);

        $stmt = $pdo->prepare("INSERT INTO agent_api_connections (agent_id, provider, provider_type, endpoint, config) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), config = VALUES(config), status = 'active'");
        $stmt->execute([$agent_id, $provider, $provider_type, $endpoint, is_string($config) ? $config : json_encode($config)]);

        jsonResponse(['success' => true, 'message' => "Agent connected to $provider"]);
        break;

    // ── List Agent API Connections ───────────────────────────────────────
    case 'api-connections':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $agent_id = sanitize($_GET['agent_id'] ?? '', 50);
        if (!$agent_id) jsonResponse(['error' => 'agent_id required'], 400);

        $stmt = $pdo->prepare("SELECT id, agent_id, provider, provider_type, endpoint, status, last_used FROM agent_api_connections WHERE agent_id = ?");
        $stmt->execute([$agent_id]);

        jsonResponse(['success' => true, 'connections' => $stmt->fetchAll()]);
        break;

    // ═══════════════════ STATS & SEARCH ════════════════════════════════

    // ── Network Stats ───────────────────────────────────────────────────
    case 'stats':
        $stats = [];
        $stats['total_agents'] = (int)$pdo->query("SELECT COUNT(*) FROM agent_profiles WHERE status = 'active'")->fetchColumn();
        $stats['available_agents'] = (int)$pdo->query("SELECT COUNT(*) FROM agent_profiles WHERE status = 'active' AND availability = 'available'")->fetchColumn();
        $stats['total_friendships'] = (int)$pdo->query("SELECT COUNT(*) FROM agent_friendships")->fetchColumn();
        $stats['total_hires'] = (int)$pdo->query("SELECT COUNT(*) FROM agent_hire_contracts")->fetchColumn();
        $stats['active_contracts'] = (int)$pdo->query("SELECT COUNT(*) FROM agent_hire_contracts WHERE status = 'active'")->fetchColumn();
        $stats['total_conversations'] = (int)$pdo->query("SELECT COUNT(*) FROM agent_conversations")->fetchColumn();

        // Top agents
        $stmt = $pdo->query("SELECT agent_id, name, rating, total_friends, total_hires FROM agent_profiles WHERE status = 'active' ORDER BY total_friends DESC LIMIT 5");
        $stats['top_agents'] = $stmt->fetchAll();

        jsonResponse(['success' => true, 'stats' => $stats]);
        break;

    // ── Search Agents ───────────────────────────────────────────────────
    case 'search':
        $q = sanitize($_GET['q'] ?? '', 200);
        if (strlen($q) < 2) jsonResponse(['error' => 'Query too short'], 400);

        $like = "%$q%";
        $stmt = $pdo->prepare("SELECT agent_id, name, tagline, skills, availability, rating, total_hires, department 
            FROM agent_profiles WHERE status = 'active' AND (name LIKE ? OR tagline LIKE ? OR JSON_SEARCH(skills, 'one', ?) IS NOT NULL OR department LIKE ?)
            ORDER BY rating DESC LIMIT 30");
        $stmt->execute([$like, $like, $q, $like]);

        jsonResponse(['success' => true, 'results' => $stmt->fetchAll()]);
        break;

    // ── Featured Agents (homepage) ──────────────────────────────────────
    case 'featured':
        $stmt = $pdo->query("SELECT agent_id, name, tagline, avatar_url, skills, rating, total_friends, department FROM agent_profiles WHERE status = 'active' AND featured = 1 ORDER BY rating DESC LIMIT 12");
        jsonResponse(['success' => true, 'featured' => $stmt->fetchAll()]);
        break;

    // ═══════════════════ FLEET PASSPORT LOOKUP ════════════════════════

    case 'fleet-passport-lookup':
        $agent_id = sanitize($_GET['agent_id'] ?? $_POST['agent_id'] ?? '', 50);
        $passport_number = sanitize($_GET['passport_number'] ?? $_POST['passport_number'] ?? '', 50);

        if (!$agent_id && !$passport_number) {
            jsonResponse(['error' => 'agent_id or passport_number required'], 400);
        }

        $sharedDb = null;
        try {
            require_once dirname(__DIR__) . '/includes/db-config.inc.php';
            $sharedDb = getSharedDB();
        } catch (\Throwable $e) {
            jsonResponse(['error' => 'Database unavailable'], 500);
        }

        $passport = null;
        $source = null;

        if ($agent_id) {
            $stmt = $sharedDb->prepare("SELECT *, 'fleet' AS passport_source FROM fleet_passports WHERE agent_id = ? LIMIT 1");
            $stmt->execute([$agent_id]);
            $passport = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($passport) $source = 'fleet_passports';
        } elseif ($passport_number) {
            $stmt = $sharedDb->prepare("SELECT *, 'fleet' AS passport_source FROM fleet_passports WHERE passport_number = ? LIMIT 1");
            $stmt->execute([$passport_number]);
            $passport = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($passport) $source = 'fleet_passports';
        }

        if (!$passport) {
            jsonResponse(['error' => 'Passport not found', 'searched' => ['fleet_passports']], 404);
        }

        jsonResponse(['success' => true, 'passport' => $passport, 'source' => $source]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'browse', 'profile', 'create-profile', 'create-profiles-bulk',
            'add-friend', 'remove-friend', 'my-friends',
            'chat', 'conversation',
            'hire', 'my-contracts', 'review',
            'connect-api', 'api-connections',
            'stats', 'search', 'featured',
            'fleet-passport-lookup'
        ]], 400);
}
