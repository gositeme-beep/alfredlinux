<?php
/**
 * GSM Alfred OS — Customer RMA & Support System
 *
 * Return Merchandise Authorization, warranty claims, support tickets,
 * repair tracking, replacement units, customer hardware lifecycle.
 *
 * Endpoints (16):
 *   create_rma       — Open a new RMA request
 *   rmas             — List RMA requests
 *   rma_detail       — Get detailed RMA info
 *   update_rma       — Update RMA status
 *   create_ticket    — Open a support ticket
 *   tickets          — List support tickets
 *   ticket_detail    — Get ticket detail with conversation
 *   reply_ticket     — Reply to a support ticket
 *   close_ticket     — Close a ticket
 *   warranty_check   — Check warranty status by serial
 *   warranty_claim   — File a warranty claim
 *   repair_log       — Add/view repair log entries
 *   replacement      — Track replacement unit
 *   customer_devices — List customer's registered devices
 *   satisfaction     — Submit satisfaction survey
 *   support_stats    — Support analytics
 */

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://gositeme.com','https://www.gositeme.com'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET,POST,PUT,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type,Authorization,X-Internal-Secret');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─── Schema ─────────────────────────────────────────────────────
function rmaEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_rma (
        id VARCHAR(32) PRIMARY KEY,
        rma_number VARCHAR(32) NOT NULL UNIQUE,
        customer_id VARCHAR(32) NOT NULL,
        serial_number VARCHAR(64) NOT NULL,
        product_model VARCHAR(64),
        reason ENUM('defective','damaged_shipping','wrong_item','malfunction','upgrade','recall','cosmetic','performance','other') NOT NULL,
        description TEXT,
        status ENUM('requested','approved','label_sent','received','inspecting','repairing','testing','shipping_replacement','completed','denied','cancelled') NOT NULL DEFAULT 'requested',
        priority ENUM('critical','high','normal','low') NOT NULL DEFAULT 'normal',
        warranty_status ENUM('in_warranty','out_of_warranty','extended','unknown') DEFAULT 'unknown',
        return_shipping_label VARCHAR(256),
        tracking_inbound VARCHAR(128),
        tracking_outbound VARCHAR(128),
        inspection_notes TEXT,
        repair_cost_usd DECIMAL(10,2),
        replacement_serial VARCHAR(64),
        assigned_to VARCHAR(128),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        completed_at DATETIME,
        INDEX idx_customer (customer_id),
        INDEX idx_serial (serial_number),
        INDEX idx_status (status),
        INDEX idx_rma (rma_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_support_tickets (
        id VARCHAR(32) PRIMARY KEY,
        ticket_number VARCHAR(16) NOT NULL UNIQUE,
        customer_id VARCHAR(32) NOT NULL,
        serial_number VARCHAR(64),
        category ENUM('hardware','software','billing','setup','connectivity','performance','safety','general','feature_request') NOT NULL DEFAULT 'general',
        subject VARCHAR(256) NOT NULL,
        status ENUM('open','in_progress','awaiting_customer','awaiting_parts','escalated','resolved','closed') NOT NULL DEFAULT 'open',
        priority ENUM('critical','high','normal','low') NOT NULL DEFAULT 'normal',
        assigned_to VARCHAR(128),
        tags JSON,
        rma_id VARCHAR(32),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        resolved_at DATETIME,
        first_response_at DATETIME,
        INDEX idx_customer (customer_id),
        INDEX idx_status (status),
        INDEX idx_category (category),
        INDEX idx_ticket (ticket_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_support_messages (
        id VARCHAR(32) PRIMARY KEY,
        ticket_id VARCHAR(32) NOT NULL,
        sender_type ENUM('customer','agent','system','alfred_ai') NOT NULL,
        sender_name VARCHAR(128),
        message TEXT NOT NULL,
        attachments JSON,
        internal_note TINYINT(1) DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX idx_ticket (ticket_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_warranty (
        id VARCHAR(32) PRIMARY KEY,
        serial_number VARCHAR(64) NOT NULL,
        customer_id VARCHAR(32) NOT NULL,
        product_model VARCHAR(64),
        warranty_type ENUM('standard','extended','premium','lifetime') NOT NULL DEFAULT 'standard',
        purchase_date DATE NOT NULL,
        warranty_start DATE NOT NULL,
        warranty_end DATE NOT NULL,
        coverage JSON,
        status ENUM('active','expired','voided','transferred') NOT NULL DEFAULT 'active',
        registered_at DATETIME NOT NULL,
        UNIQUE KEY uk_serial (serial_number),
        INDEX idx_customer (customer_id),
        INDEX idx_end (warranty_end)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_repair_log (
        id VARCHAR(32) PRIMARY KEY,
        rma_id VARCHAR(32) NOT NULL,
        serial_number VARCHAR(64) NOT NULL,
        technician VARCHAR(128),
        action ENUM('inspection','disassembly','component_replacement','firmware_flash','calibration','reassembly','testing','packaging','scrap') NOT NULL,
        component_replaced VARCHAR(128),
        part_number VARCHAR(64),
        cost_usd DECIMAL(10,2),
        time_minutes INT,
        notes TEXT,
        passed TINYINT(1),
        created_at DATETIME NOT NULL,
        INDEX idx_rma (rma_id),
        INDEX idx_serial (serial_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_satisfaction (
        id VARCHAR(32) PRIMARY KEY,
        ticket_id VARCHAR(32),
        rma_id VARCHAR(32),
        customer_id VARCHAR(32) NOT NULL,
        rating INT NOT NULL,
        nps_score INT,
        feedback TEXT,
        categories JSON,
        created_at DATETIME NOT NULL,
        INDEX idx_customer (customer_id),
        INDEX idx_rating (rating)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

rmaEnsureSchema();
$auth = agentos_auth();

// ─── Handlers ───────────────────────────────────────────────────

function handleCreateRma(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $customerId = $data['customer_id'] ?? '';
    $serial = $data['serial_number'] ?? '';
    if (!$customerId || !$serial) agentos_error('Missing customer_id or serial_number');

    $id = agentos_id('rma');
    $rmaNum = 'RMA-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Check warranty
    $stmt = $pdo->prepare("SELECT * FROM agentos_warranty WHERE serial_number = ? AND status = 'active'");
    $stmt->execute([$serial]);
    $warranty = $stmt->fetch();
    $warrantyStatus = 'unknown';
    if ($warranty) {
        $warrantyStatus = strtotime($warranty['warranty_end']) >= time() ? 'in_warranty' : 'out_of_warranty';
    }

    $stmt = $pdo->prepare('INSERT INTO agentos_rma (id, rma_number, customer_id, serial_number, product_model, reason, description, status, priority, warranty_status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $rmaNum, $customerId, $serial,
        $data['product_model'] ?? null,
        $data['reason'] ?? 'malfunction',
        $data['description'] ?? null,
        'requested',
        $data['priority'] ?? 'normal',
        $warrantyStatus
    ]);

    // Auto-create support ticket
    $ticketId = agentos_id('tkt');
    $ticketNum = 'T' . str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
    $reason = $data['reason'] ?? 'malfunction';
    $priority = $data['priority'] ?? 'normal';
    $pdo->prepare('INSERT INTO agentos_support_tickets (id, ticket_number, customer_id, serial_number, category, subject, status, priority, rma_id, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())')
        ->execute([$ticketId, $ticketNum, $customerId, $serial, 'hardware',
            "RMA Request: $reason - $serial",
            'open', $priority, $id]);

    // System message
    $pdo->prepare('INSERT INTO agentos_support_messages (id, ticket_id, sender_type, sender_name, message, created_at) VALUES (?,?,?,?,?,NOW())')
        ->execute([agentos_id('msg'), $ticketId, 'system', 'System',
            "RMA $rmaNum created. Warranty: $warrantyStatus. Reason: $reason"]);

    agentos_respond(['ok' => true, 'rma_id' => $id, 'rma_number' => $rmaNum, 'ticket_number' => $ticketNum, 'warranty_status' => $warrantyStatus], 201);
}

function handleRmas(): void {
    $pdo = agentos_pdo();
    $customerId = $_GET['customer_id'] ?? null;
    $status = $_GET['status'] ?? null;

    $sql = 'SELECT * FROM agentos_rma WHERE 1=1';
    $params = [];
    if ($customerId) { $sql .= ' AND customer_id = ?'; $params[] = $customerId; }
    if ($status) { $sql .= ' AND status = ?'; $params[] = $status; }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    agentos_respond(['ok' => true, 'rmas' => $stmt->fetchAll()]);
}

function handleRmaDetail(): void {
    $pdo = agentos_pdo();
    $rmaId = $_GET['rma_id'] ?? '';
    if (!$rmaId) agentos_error('Missing rma_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_rma WHERE id = ?');
    $stmt->execute([$rmaId]);
    $rma = $stmt->fetch();
    if (!$rma) agentos_error('RMA not found', 404);

    // Repair logs
    $stmt = $pdo->prepare('SELECT * FROM agentos_repair_log WHERE rma_id = ? ORDER BY created_at');
    $stmt->execute([$rmaId]);
    $rma['repair_logs'] = $stmt->fetchAll();

    // Related ticket
    $stmt = $pdo->prepare('SELECT * FROM agentos_support_tickets WHERE rma_id = ?');
    $stmt->execute([$rmaId]);
    $rma['support_ticket'] = $stmt->fetch() ?: null;

    agentos_respond(['ok' => true, 'rma' => $rma]);
}

function handleUpdateRma(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $rmaId = $data['rma_id'] ?? '';
    if (!$rmaId) agentos_error('Missing rma_id');

    $fields = [];
    $params = [];
    $allowed = ['status','priority','return_shipping_label','tracking_inbound','tracking_outbound','inspection_notes','repair_cost_usd','replacement_serial','assigned_to'];
    foreach ($allowed as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
    }
    if (($data['status'] ?? '') === 'completed') { $fields[] = 'completed_at = NOW()'; }

    if (empty($fields)) agentos_error('No fields to update');
    $fields[] = 'updated_at = NOW()';
    $params[] = $rmaId;

    $pdo->prepare('UPDATE agentos_rma SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    agentos_audit(['action_type' => 'rma_update', 'status' => 'success',
        'metadata' => ['rma_id' => $rmaId, 'new_status' => $data['status'] ?? 'unchanged']]);

    agentos_respond(['ok' => true, 'updated' => true]);
}

function handleCreateTicket(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $customerId = $data['customer_id'] ?? '';
    if (!$customerId) agentos_error('Missing customer_id');

    $id = agentos_id('tkt');
    $ticketNum = 'T' . str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare('INSERT INTO agentos_support_tickets (id, ticket_number, customer_id, serial_number, category, subject, status, priority, tags, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $ticketNum, $customerId,
        $data['serial_number'] ?? null,
        $data['category'] ?? 'general',
        $data['subject'] ?? 'Support Request',
        'open',
        $data['priority'] ?? 'normal',
        json_encode($data['tags'] ?? [])
    ]);

    // Initial message
    if (!empty($data['message'])) {
        $pdo->prepare('INSERT INTO agentos_support_messages (id, ticket_id, sender_type, sender_name, message, attachments, created_at) VALUES (?,?,?,?,?,?,NOW())')
            ->execute([agentos_id('msg'), $id, 'customer', $data['customer_name'] ?? 'Customer',
                $data['message'], json_encode($data['attachments'] ?? [])]);
    }

    agentos_respond(['ok' => true, 'ticket_id' => $id, 'ticket_number' => $ticketNum], 201);
}

function handleTickets(): void {
    $pdo = agentos_pdo();
    $customerId = $_GET['customer_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $category = $_GET['category'] ?? null;

    $sql = 'SELECT * FROM agentos_support_tickets WHERE 1=1';
    $params = [];
    if ($customerId) { $sql .= ' AND customer_id = ?'; $params[] = $customerId; }
    if ($status) { $sql .= ' AND status = ?'; $params[] = $status; }
    if ($category) { $sql .= ' AND category = ?'; $params[] = $category; }
    $sql .= ' ORDER BY FIELD(priority, "critical","high","normal","low"), created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    foreach ($tickets as &$t) $t['tags'] = json_decode($t['tags'] ?? '[]', true);

    agentos_respond(['ok' => true, 'tickets' => $tickets]);
}

function handleTicketDetail(): void {
    $pdo = agentos_pdo();
    $ticketId = $_GET['ticket_id'] ?? '';
    if (!$ticketId) agentos_error('Missing ticket_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_support_tickets WHERE id = ?');
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    if (!$ticket) agentos_error('Ticket not found', 404);

    $ticket['tags'] = json_decode($ticket['tags'] ?? '[]', true);

    // Messages
    $stmt = $pdo->prepare('SELECT * FROM agentos_support_messages WHERE ticket_id = ? ORDER BY created_at ASC');
    $stmt->execute([$ticketId]);
    $msgs = $stmt->fetchAll();
    foreach ($msgs as &$m) $m['attachments'] = json_decode($m['attachments'] ?? '[]', true);
    $ticket['messages'] = $msgs;

    agentos_respond(['ok' => true, 'ticket' => $ticket]);
}

function handleReplyTicket(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $ticketId = $data['ticket_id'] ?? '';
    if (!$ticketId || empty($data['message'])) agentos_error('Missing ticket_id or message');

    $id = agentos_id('msg');
    $stmt = $pdo->prepare('INSERT INTO agentos_support_messages (id, ticket_id, sender_type, sender_name, message, attachments, internal_note, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $ticketId,
        $data['sender_type'] ?? 'agent',
        $data['sender_name'] ?? 'Support',
        $data['message'],
        json_encode($data['attachments'] ?? []),
        (int)($data['internal_note'] ?? 0)
    ]);

    // Update ticket
    $newStatus = $data['sender_type'] === 'customer' ? 'open' : 'in_progress';
    $pdo->prepare('UPDATE agentos_support_tickets SET status = CASE WHEN first_response_at IS NULL AND ? != ? THEN ? ELSE status END, first_response_at = CASE WHEN first_response_at IS NULL AND ? != ? THEN NOW() ELSE first_response_at END, updated_at = NOW() WHERE id = ?')
        ->execute([$data['sender_type'] ?? 'agent', 'customer', $newStatus, $data['sender_type'] ?? 'agent', 'customer', $ticketId]);

    agentos_respond(['ok' => true, 'message_id' => $id]);
}

function handleCloseTicket(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $ticketId = $data['ticket_id'] ?? '';
    if (!$ticketId) agentos_error('Missing ticket_id');

    $pdo->prepare("UPDATE agentos_support_tickets SET status = 'closed', resolved_at = NOW(), updated_at = NOW() WHERE id = ?")
        ->execute([$ticketId]);

    // Resolution message
    $pdo->prepare('INSERT INTO agentos_support_messages (id, ticket_id, sender_type, sender_name, message, created_at) VALUES (?,?,?,?,?,NOW())')
        ->execute([agentos_id('msg'), $ticketId, 'system', 'System',
            'Ticket closed. Resolution: ' . ($data['resolution'] ?? 'Resolved')]);

    agentos_respond(['ok' => true, 'closed' => true]);
}

function handleWarrantyCheck(): void {
    $pdo = agentos_pdo();
    $serial = $_GET['serial_number'] ?? '';
    if (!$serial) agentos_error('Missing serial_number');

    $stmt = $pdo->prepare('SELECT * FROM agentos_warranty WHERE serial_number = ?');
    $stmt->execute([$serial]);
    $warranty = $stmt->fetch();

    if (!$warranty) {
        agentos_respond(['ok' => true, 'warranty' => null, 'status' => 'not_registered']);
        return;
    }

    $warranty['coverage'] = json_decode($warranty['coverage'] ?? '[]', true);
    $daysLeft = max(0, (int)((strtotime($warranty['warranty_end']) - time()) / 86400));
    $warranty['days_remaining'] = $daysLeft;
    $warranty['is_active'] = $warranty['status'] === 'active' && $daysLeft > 0;

    agentos_respond(['ok' => true, 'warranty' => $warranty]);
}

function handleWarrantyClaim(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $serial = $data['serial_number'] ?? '';
    $customerId = $data['customer_id'] ?? '';
    if (!$serial || !$customerId) agentos_error('Missing serial_number or customer_id');

    // Register warranty if not exists
    $stmt = $pdo->prepare('SELECT id FROM agentos_warranty WHERE serial_number = ?');
    $stmt->execute([$serial]);
    if (!$stmt->fetch()) {
        $purchaseDate = $data['purchase_date'] ?? date('Y-m-d');
        $warrantyYears = match($data['warranty_type'] ?? 'standard') {
            'extended' => 3, 'premium' => 5, 'lifetime' => 99, default => 2
        };
        $wEnd = date('Y-m-d', strtotime("+$warrantyYears years", strtotime($purchaseDate)));

        $pdo->prepare('INSERT INTO agentos_warranty (id, serial_number, customer_id, product_model, warranty_type, purchase_date, warranty_start, warranty_end, coverage, status, registered_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())')
            ->execute([
                agentos_id('wrn'), $serial, $customerId,
                $data['product_model'] ?? 'Alfred-1',
                $data['warranty_type'] ?? 'standard',
                $purchaseDate, $purchaseDate, $wEnd,
                json_encode($data['coverage'] ?? ['parts','labor','shipping']),
                'active'
            ]);
    }

    agentos_respond(['ok' => true, 'registered' => true]);
}

function handleRepairLog(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $rmaId = $data['rma_id'] ?? '';
        if (!$rmaId) agentos_error('Missing rma_id');

        $id = agentos_id('rep');
        $stmt = $pdo->prepare('INSERT INTO agentos_repair_log (id, rma_id, serial_number, technician, action, component_replaced, part_number, cost_usd, time_minutes, notes, passed, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $id, $rmaId,
            $data['serial_number'] ?? '',
            $data['technician'] ?? null,
            $data['action'] ?? 'inspection',
            $data['component_replaced'] ?? null,
            $data['part_number'] ?? null,
            (float)($data['cost_usd'] ?? 0),
            (int)($data['time_minutes'] ?? 0),
            $data['notes'] ?? null,
            isset($data['passed']) ? (int)$data['passed'] : null
        ]);

        agentos_respond(['ok' => true, 'repair_log_id' => $id], 201);
    } else {
        $rmaId = $_GET['rma_id'] ?? '';
        if (!$rmaId) agentos_error('Missing rma_id');

        $stmt = $pdo->prepare('SELECT * FROM agentos_repair_log WHERE rma_id = ? ORDER BY created_at');
        $stmt->execute([$rmaId]);
        agentos_respond(['ok' => true, 'repair_logs' => $stmt->fetchAll()]);
    }
}

function handleReplacement(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $rmaId = $data['rma_id'] ?? '';
    if (!$rmaId) agentos_error('Missing rma_id');

    $pdo->prepare('UPDATE agentos_rma SET replacement_serial = ?, tracking_outbound = ?, status = ?, updated_at = NOW() WHERE id = ?')
        ->execute([
            $data['replacement_serial'] ?? null,
            $data['tracking_outbound'] ?? null,
            'shipping_replacement',
            $rmaId
        ]);

    agentos_respond(['ok' => true, 'updated' => true]);
}

function handleCustomerDevices(): void {
    $pdo = agentos_pdo();
    $customerId = $_GET['customer_id'] ?? '';
    if (!$customerId) agentos_error('Missing customer_id');

    // From warranty registrations
    $stmt = $pdo->prepare('SELECT w.serial_number, w.product_model, w.warranty_type, w.warranty_start, w.warranty_end, w.status, DATEDIFF(w.warranty_end, CURDATE()) as days_left FROM agentos_warranty w WHERE w.customer_id = ? ORDER BY w.registered_at DESC');
    $stmt->execute([$customerId]);
    $devices = $stmt->fetchAll();

    // Check for open RMAs/tickets per device
    foreach ($devices as &$d) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM agentos_rma WHERE serial_number = ? AND status NOT IN ('completed','denied','cancelled')");
        $stmt->execute([$d['serial_number']]);
        $d['open_rmas'] = (int)$stmt->fetch()['c'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM agentos_support_tickets WHERE serial_number = ? AND status NOT IN ('resolved','closed')");
        $stmt->execute([$d['serial_number']]);
        $d['open_tickets'] = (int)$stmt->fetch()['c'];
    }

    agentos_respond(['ok' => true, 'devices' => $devices]);
}

function handleSatisfaction(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = agentos_id('sat');
        $stmt = $pdo->prepare('INSERT INTO agentos_satisfaction (id, ticket_id, rma_id, customer_id, rating, nps_score, feedback, categories, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $id,
            $data['ticket_id'] ?? null,
            $data['rma_id'] ?? null,
            $data['customer_id'] ?? '',
            min(max((int)($data['rating'] ?? 5), 1), 5),
            isset($data['nps_score']) ? min(max((int)$data['nps_score'], 0), 10) : null,
            $data['feedback'] ?? null,
            json_encode($data['categories'] ?? [])
        ]);

        agentos_respond(['ok' => true, 'survey_id' => $id], 201);
    } else {
        $stmt = $pdo->query('SELECT AVG(rating) as avg_rating, AVG(nps_score) as avg_nps, COUNT(*) as total FROM agentos_satisfaction');
        $stats = $stmt->fetch();

        $ratingDist = $pdo->query('SELECT rating, COUNT(*) as count FROM agentos_satisfaction GROUP BY rating ORDER BY rating')->fetchAll();

        agentos_respond(['ok' => true, 'satisfaction' => [
            'average_rating' => round((float)($stats['avg_rating'] ?? 0), 2),
            'average_nps' => round((float)($stats['avg_nps'] ?? 0), 1),
            'total_responses' => (int)$stats['total'],
            'rating_distribution' => $ratingDist
        ]]);
    }
}

function handleSupportStats(): void {
    $pdo = agentos_pdo();

    $ticketStats = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_support_tickets GROUP BY status")->fetchAll();
    $rmaStats = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_rma GROUP BY status")->fetchAll();
    $categoryStats = $pdo->query("SELECT category, COUNT(*) as count FROM agentos_support_tickets GROUP BY category ORDER BY count DESC")->fetchAll();

    $avgResponse = $pdo->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes FROM agentos_support_tickets WHERE first_response_at IS NOT NULL")->fetch();
    $avgResolution = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours FROM agentos_support_tickets WHERE resolved_at IS NOT NULL")->fetch();

    $openTickets = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_support_tickets WHERE status NOT IN ('resolved','closed')")->fetch()['c'];
    $openRmas = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_rma WHERE status NOT IN ('completed','denied','cancelled')")->fetch()['c'];

    agentos_respond(['ok' => true, 'support_stats' => [
        'open_tickets' => $openTickets,
        'open_rmas' => $openRmas,
        'tickets_by_status' => $ticketStats,
        'rmas_by_status' => $rmaStats,
        'tickets_by_category' => $categoryStats,
        'avg_first_response_minutes' => round((float)($avgResponse['avg_minutes'] ?? 0), 1),
        'avg_resolution_hours' => round((float)($avgResolution['avg_hours'] ?? 0), 1),
    ]]);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'create_rma'       => 'handleCreateRma',
    'rmas'             => 'handleRmas',
    'rma_detail'       => 'handleRmaDetail',
    'update_rma'       => 'handleUpdateRma',
    'create_ticket'    => 'handleCreateTicket',
    'tickets'          => 'handleTickets',
    'ticket_detail'    => 'handleTicketDetail',
    'reply_ticket'     => 'handleReplyTicket',
    'close_ticket'     => 'handleCloseTicket',
    'warranty_check'   => 'handleWarrantyCheck',
    'warranty_claim'   => 'handleWarrantyClaim',
    'repair_log'       => 'handleRepairLog',
    'replacement'      => 'handleReplacement',
    'customer_devices' => 'handleCustomerDevices',
    'satisfaction'     => 'handleSatisfaction',
    'support_stats'    => 'handleSupportStats',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — Customer RMA & Support', 'version' => AGENTOS_VERSION,
        'description' => 'Returns, repairs, warranty, support tickets, customer device lifecycle',
        'endpoints' => array_keys($routes)]);
}

$routes[$action]();
