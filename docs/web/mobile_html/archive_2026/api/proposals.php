<?php
/**
 * GoSiteMe Proposals & Agenda API
 * Agent demand/approval panel + Owner's agenda & organizer
 * Agents submit proposals → Owner approves/denies
 * Development updates flow into agenda automatically
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? $_REQUEST['internal_secret'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);

if (!$client_id && !$is_internal) {
    jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$is_owner = ($client_id == 33);
$is_admin = $is_owner || $is_internal;

$pdo = getDB();
if (!$pdo) jsonResponse(['error' => 'Database unavailable'], 500);

// ── Schema ──────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS `proposals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proposal_id` VARCHAR(50) UNIQUE NOT NULL,
    `submitted_by` VARCHAR(150) NOT NULL,
    `submitted_by_type` ENUM('agent','human','team','system') DEFAULT 'agent',
    `category` ENUM('project','feature','budget','infrastructure','hiring','security','partnership','research','other') NOT NULL DEFAULT 'other',
    `priority` ENUM('critical','high','medium','low') DEFAULT 'medium',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `justification` TEXT DEFAULT NULL,
    `estimated_cost` DECIMAL(12,2) DEFAULT NULL,
    `estimated_timeline` VARCHAR(100) DEFAULT NULL,
    `resources_needed` JSON DEFAULT NULL,
    `attachments` JSON DEFAULT NULL,
    `status` ENUM('submitted','under_review','approved','denied','deferred','in_progress','completed') DEFAULT 'submitted',
    `owner_notes` TEXT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `completion_date` DATETIME DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `agenda_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_type` ENUM('update','meeting','milestone','task','reminder','alert','report') NOT NULL DEFAULT 'update',
    `source` VARCHAR(150) DEFAULT 'system',
    `source_type` ENUM('agent','human','team','system','proposal') DEFAULT 'system',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `priority` ENUM('critical','high','medium','low') DEFAULT 'medium',
    `scheduled_at` DATETIME DEFAULT NULL,
    `due_date` DATE DEFAULT NULL,
    `status` ENUM('pending','in_progress','completed','cancelled','overdue') DEFAULT 'pending',
    `read_by_owner` TINYINT(1) DEFAULT 0,
    `acknowledged` TINYINT(1) DEFAULT 0,
    `related_proposal_id` INT DEFAULT NULL,
    `department` VARCHAR(30) DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `team_discussions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `channel` VARCHAR(100) NOT NULL DEFAULT 'general',
    `sender_name` VARCHAR(150) NOT NULL,
    `sender_type` ENUM('owner','manager','agent','human') NOT NULL DEFAULT 'agent',
    `sender_id` INT DEFAULT NULL,
    `message` TEXT NOT NULL,
    `reply_to` INT DEFAULT NULL,
    `pinned` TINYINT(1) DEFAULT 0,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Helpers ─────────────────────────────────────────────────────────────
function generateProposalId(): string {
    return 'PRP-' . strtoupper(dechex(time())) . '-' . strtoupper(bin2hex(random_bytes(4)));
}

// ── Routing ─────────────────────────────────────────────────────────────
$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ═══════════════════ PROPOSALS ═══════════════════════════════════════

    // ── Submit Proposal (agents/teams/humans submit) ────────────────────
    case 'submit-proposal':
        $title = sanitize($_POST['title'] ?? '', 255);
        $description = sanitize($_POST['description'] ?? '', 10000);
        $category = sanitize($_POST['category'] ?? 'other', 20);
        $priority = sanitize($_POST['priority'] ?? 'medium', 10);
        $justification = sanitize($_POST['justification'] ?? '', 5000);
        $cost = isset($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null;
        $timeline = sanitize($_POST['estimated_timeline'] ?? '', 100);
        $resources = $_POST['resources_needed'] ?? null;
        $submitted_by = sanitize($_POST['submitted_by'] ?? 'Unknown Agent', 150);
        $submitted_by_type = sanitize($_POST['submitted_by_type'] ?? 'agent', 10);

        if (!$title || !$description) {
            jsonResponse(['error' => 'Title and description required'], 400);
        }

        $valid_categories = ['project','feature','budget','infrastructure','hiring','security','partnership','research','other'];
        if (!in_array($category, $valid_categories)) $category = 'other';

        $proposal_id = generateProposalId();

        $stmt = $pdo->prepare("INSERT INTO proposals 
            (proposal_id, submitted_by, submitted_by_type, category, priority, title, description, justification, estimated_cost, estimated_timeline, resources_needed) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $proposal_id, $submitted_by, $submitted_by_type,
            $category, $priority, $title, $description, $justification,
            $cost, $timeline,
            is_array($resources) ? json_encode($resources) : $resources
        ]);

        // Auto-create agenda item for owner
        $pdo->prepare("INSERT INTO agenda_items (item_type, source, source_type, title, description, priority, related_proposal_id) 
            VALUES ('alert', ?, 'proposal', ?, ?, ?, ?)")
            ->execute([$submitted_by, "New Proposal: $title", "Proposal $proposal_id from $submitted_by requires your review.", $priority, $pdo->lastInsertId()]);

        jsonResponse(['success' => true, 'proposal_id' => $proposal_id, 'message' => 'Proposal submitted for owner review']);
        break;

    // ── List Proposals ──────────────────────────────────────────────────
    case 'proposals':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $status = sanitize($_GET['status'] ?? 'all', 20);
        $category = sanitize($_GET['category'] ?? 'all', 20);
        $priority = sanitize($_GET['priority'] ?? 'all', 10);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];
        if ($status !== 'all') { $where[] = 'status = ?'; $params[] = $status; }
        if ($category !== 'all') { $where[] = 'category = ?'; $params[] = $category; }
        if ($priority !== 'all') { $where[] = 'priority = ?'; $params[] = $priority; }

        $w = implode(' AND ', $where);

        $count = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE $w");
        dbExecute($count, $params);
        $total = $count->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare("SELECT * FROM proposals WHERE $w ORDER BY 
            FIELD(priority, 'critical','high','medium','low'), created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);

        jsonResponse([
            'success' => true,
            'proposals' => $stmt->fetchAll(),
            'total' => (int)$total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ── Review Proposal (approve/deny/defer) ────────────────────────────
    case 'review-proposal':
        if (!$is_owner) jsonResponse(['error' => 'Owner access required'], 403);

        $proposal_id = sanitize($_POST['proposal_id'] ?? '', 50);
        $decision = sanitize($_POST['decision'] ?? '', 20);
        $notes = sanitize($_POST['owner_notes'] ?? '', 5000);

        $valid_decisions = ['approved', 'denied', 'deferred', 'under_review'];
        if (!$proposal_id || !in_array($decision, $valid_decisions)) {
            jsonResponse(['error' => 'Valid proposal_id and decision required'], 400);
        }

        $stmt = $pdo->prepare("UPDATE proposals SET status = ?, owner_notes = ?, reviewed_at = NOW() WHERE proposal_id = ?");
        $stmt->execute([$decision, $notes, $proposal_id]);

        if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Proposal not found'], 404);

        // Log in agenda
        $pdo->prepare("INSERT INTO agenda_items (item_type, source, source_type, title, description, priority) VALUES ('update', 'Owner', 'system', ?, ?, 'medium')")
            ->execute(["Proposal $proposal_id $decision", $notes ?: "Decision: $decision"]);

        jsonResponse(['success' => true, 'message' => "Proposal $decision"]);
        break;

    // ── Proposal Stats ──────────────────────────────────────────────────
    case 'proposal-stats':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $stats = [];
        $r = $pdo->query("SELECT status, COUNT(*) as cnt FROM proposals GROUP BY status");
        while ($row = $r->fetch()) $stats['by_status'][$row['status']] = (int)$row['cnt'];

        $r = $pdo->query("SELECT category, COUNT(*) as cnt FROM proposals GROUP BY category ORDER BY cnt DESC");
        $stats['by_category'] = $r->fetchAll();

        $r = $pdo->query("SELECT priority, COUNT(*) as cnt FROM proposals GROUP BY priority");
        while ($row = $r->fetch()) $stats['by_priority'][$row['priority']] = (int)$row['cnt'];

        $r = $pdo->query("SELECT COUNT(*) FROM proposals WHERE status = 'submitted'");
        $stats['awaiting_review'] = (int)$r->fetchColumn();

        jsonResponse(['success' => true, 'stats' => $stats]);
        break;

    // ═══════════════════ AGENDA & ORGANIZER ═════════════════════════════

    // ── Add Agenda Item ─────────────────────────────────────────────────
    case 'add-agenda':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $item_type = sanitize($_POST['item_type'] ?? 'update', 20);
        $source = sanitize($_POST['source'] ?? 'system', 150);
        $source_type = sanitize($_POST['source_type'] ?? 'system', 20);
        $title = sanitize($_POST['title'] ?? '', 255);
        $description = sanitize($_POST['description'] ?? '', 5000);
        $priority = sanitize($_POST['priority'] ?? 'medium', 10);
        $scheduled = $_POST['scheduled_at'] ?? null;
        $due = $_POST['due_date'] ?? null;
        $dept = sanitize($_POST['department'] ?? '', 30);

        if (!$title) jsonResponse(['error' => 'Title required'], 400);

        $stmt = $pdo->prepare("INSERT INTO agenda_items 
            (item_type, source, source_type, title, description, priority, scheduled_at, due_date, department) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$item_type, $source, $source_type, $title, $description, $priority, $scheduled, $due, $dept ?: null]);

        jsonResponse(['success' => true, 'agenda_id' => $pdo->lastInsertId()]);
        break;

    // ── Get Agenda (Owner's View) ───────────────────────────────────────
    case 'agenda':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $filter = sanitize($_GET['filter'] ?? 'all', 20);
        $date = sanitize($_GET['date'] ?? '', 10);
        $unread_only = ($_GET['unread'] ?? '0') === '1';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 30)));
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];

        if ($filter !== 'all') { $where[] = 'item_type = ?'; $params[] = $filter; }
        if ($date) { $where[] = 'DATE(COALESCE(scheduled_at, created_at)) = ?'; $params[] = $date; }
        if ($unread_only) { $where[] = 'read_by_owner = 0'; }

        $w = implode(' AND ', $where);

        $count = $pdo->prepare("SELECT COUNT(*) FROM agenda_items WHERE $w");
        dbExecute($count, $params);
        $total = $count->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare("SELECT * FROM agenda_items WHERE $w ORDER BY 
            FIELD(priority, 'critical','high','medium','low'), 
            COALESCE(scheduled_at, created_at) DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);

        // Count unread
        $unread = $pdo->query("SELECT COUNT(*) FROM agenda_items WHERE read_by_owner = 0")->fetchColumn();

        jsonResponse([
            'success' => true,
            'items' => $stmt->fetchAll(),
            'total' => (int)$total,
            'unread' => (int)$unread,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ── Mark Agenda Read ────────────────────────────────────────────────
    case 'mark-read':
        if (!$is_owner) jsonResponse(['error' => 'Owner access required'], 403);

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE agenda_items SET read_by_owner = 1 WHERE id = ?")->execute([$id]);
        } else {
            // Mark all read
            $pdo->exec("UPDATE agenda_items SET read_by_owner = 1 WHERE read_by_owner = 0");
        }
        jsonResponse(['success' => true]);
        break;

    // ── Acknowledge Agenda Item ─────────────────────────────────────────
    case 'acknowledge':
        if (!$is_owner) jsonResponse(['error' => 'Owner access required'], 403);

        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE agenda_items SET acknowledged = 1, read_by_owner = 1 WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    // ── Agenda Summary (Daily Digest) ───────────────────────────────────
    case 'agenda-summary':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $summary = [];

        $summary['unread'] = (int)$pdo->query("SELECT COUNT(*) FROM agenda_items WHERE read_by_owner = 0")->fetchColumn();
        $summary['critical'] = (int)$pdo->query("SELECT COUNT(*) FROM agenda_items WHERE priority = 'critical' AND status = 'pending'")->fetchColumn();
        $summary['pending_proposals'] = (int)$pdo->query("SELECT COUNT(*) FROM proposals WHERE status = 'submitted'")->fetchColumn();
        $summary['today_items'] = (int)$pdo->query("SELECT COUNT(*) FROM agenda_items WHERE DATE(COALESCE(scheduled_at, created_at)) = CURDATE()")->fetchColumn();
        $summary['overdue'] = (int)$pdo->query("SELECT COUNT(*) FROM agenda_items WHERE due_date < CURDATE() AND status NOT IN ('completed','cancelled')")->fetchColumn();

        // Recent critical items
        $stmt = $pdo->query("SELECT id, title, source, priority, created_at FROM agenda_items WHERE priority IN ('critical','high') AND read_by_owner = 0 ORDER BY created_at DESC LIMIT 10");
        $summary['urgent_items'] = $stmt->fetchAll();

        jsonResponse(['success' => true, 'summary' => $summary]);
        break;

    // ═══════════════════ TEAM DISCUSSIONS ═══════════════════════════════

    // ── Send Message to Team ────────────────────────────────────────────
    case 'send-message':
        $channel = sanitize($_POST['channel'] ?? 'general', 100);
        $message = sanitize($_POST['message'] ?? '', 5000);
        $sender_name = sanitize($_POST['sender_name'] ?? '', 150);
        $sender_type = sanitize($_POST['sender_type'] ?? 'agent', 10);
        $reply_to = (int)($_POST['reply_to'] ?? 0) ?: null;

        if (!$message) jsonResponse(['error' => 'Message required'], 400);

        if ($is_owner) {
            $sender_name = 'Danny (Owner)';
            $sender_type = 'owner';
        } elseif (!$sender_name) {
            $sender_name = 'Anonymous';
        }

        $stmt = $pdo->prepare("INSERT INTO team_discussions (channel, sender_name, sender_type, sender_id, message, reply_to) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$channel, $sender_name, $sender_type, $client_id, $message, $reply_to]);

        jsonResponse(['success' => true, 'message_id' => $pdo->lastInsertId()]);
        break;

    // ── Get Team Messages ───────────────────────────────────────────────
    case 'messages':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $channel = sanitize($_GET['channel'] ?? 'general', 100);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("SELECT * FROM team_discussions WHERE channel = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, [$channel, $limit, $offset]);

        $total = $pdo->prepare("SELECT COUNT(*) FROM team_discussions WHERE channel = ?");
        $total->execute([$channel]);

        jsonResponse([
            'success' => true,
            'messages' => array_reverse($stmt->fetchAll()),
            'total' => (int)$total->fetchColumn(),
            'channel' => $channel
        ]);
        break;

    // ── List Channels ───────────────────────────────────────────────────
    case 'channels':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $stmt = $pdo->query("SELECT channel, COUNT(*) as message_count, MAX(created_at) as last_activity FROM team_discussions GROUP BY channel ORDER BY last_activity DESC");
        jsonResponse(['success' => true, 'channels' => $stmt->fetchAll()]);
        break;

    // ── Pin Message ─────────────────────────────────────────────────────
    case 'pin-message':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $msg_id = (int)($_POST['message_id'] ?? 0);
        $pdo->prepare("UPDATE team_discussions SET pinned = NOT pinned WHERE id = ?")->execute([$msg_id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'submit-proposal', 'proposals', 'review-proposal', 'proposal-stats',
            'add-agenda', 'agenda', 'mark-read', 'acknowledge', 'agenda-summary',
            'send-message', 'messages', 'channels', 'pin-message'
        ]], 400);
}
