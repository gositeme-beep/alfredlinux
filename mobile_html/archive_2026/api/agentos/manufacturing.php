<?php
/**
 * GSM Alfred OS — Manufacturing & BOM System
 *
 * Bill of Materials, vendor/supplier management, production orders,
 * serial number generation, quality control checkpoints, cost tracking.
 *
 * Endpoints (18):
 *   bom_list           — List all BOMs
 *   bom_detail         — Get specific BOM with all components
 *   create_bom         — Create a new BOM
 *   add_component      — Add a component to a BOM
 *   update_component   — Update component details
 *   vendors            — List all vendors
 *   add_vendor         — Add a new vendor
 *   create_order       — Create production order
 *   orders             — List production orders
 *   order_detail       — Get production order with checkpoints
 *   update_order       — Update production order status
 *   generate_serial    — Generate unique serial number
 *   serial_lookup      — Lookup unit by serial number
 *   qc_checkpoints     — Get QC checkpoints for an order
 *   update_qc          — Update a QC checkpoint result
 *   inventory          — Current component inventory levels
 *   cost_analysis      — Cost breakdown per unit
 *   production_stats   — Production statistics
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
function mfgEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_bom (
        id VARCHAR(32) PRIMARY KEY,
        product_model VARCHAR(64) NOT NULL,
        revision VARCHAR(16) NOT NULL,
        status ENUM('draft','review','approved','production','deprecated') NOT NULL DEFAULT 'draft',
        total_cost_usd DECIMAL(12,2) DEFAULT 0,
        total_components INT DEFAULT 0,
        notes TEXT,
        approved_by VARCHAR(32),
        approved_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_model_rev (product_model, revision),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_bom_components (
        id VARCHAR(32) PRIMARY KEY,
        bom_id VARCHAR(32) NOT NULL,
        part_number VARCHAR(64) NOT NULL,
        part_name VARCHAR(128) NOT NULL,
        category ENUM('chassis','motor','sensor','battery','pcb','cable','connector','fastener','software','display','camera','lidar','imu','gps','antenna','compute','memory','storage','power_supply','cooling','enclosure','actuator','other') NOT NULL,
        vendor_id VARCHAR(32),
        quantity INT NOT NULL DEFAULT 1,
        unit_cost_usd DECIMAL(10,2) DEFAULT 0,
        lead_time_days INT,
        moq INT DEFAULT 1,
        alternatives JSON,
        specifications JSON,
        rohs_compliant TINYINT(1) DEFAULT 1,
        critical_component TINYINT(1) DEFAULT 0,
        stock_on_hand INT DEFAULT 0,
        reorder_point INT DEFAULT 10,
        notes TEXT,
        created_at DATETIME NOT NULL,
        INDEX idx_bom (bom_id),
        INDEX idx_category (category),
        INDEX idx_vendor (vendor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_mfg_vendors (
        id VARCHAR(32) PRIMARY KEY,
        vendor_name VARCHAR(128) NOT NULL,
        vendor_code VARCHAR(32) NOT NULL UNIQUE,
        country VARCHAR(64),
        city VARCHAR(64),
        contact_name VARCHAR(128),
        contact_email VARCHAR(128),
        contact_phone VARCHAR(32),
        payment_terms VARCHAR(64),
        rating FLOAT DEFAULT 0,
        certifications JSON,
        specializations JSON,
        lead_time_avg_days INT,
        active TINYINT(1) DEFAULT 1,
        notes TEXT,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_mfg_orders (
        id VARCHAR(32) PRIMARY KEY,
        bom_id VARCHAR(32) NOT NULL,
        order_number VARCHAR(32) NOT NULL UNIQUE,
        quantity INT NOT NULL,
        status ENUM('planned','sourcing','in_production','assembly','testing','qc_hold','packaging','ready','shipped','completed','cancelled') NOT NULL DEFAULT 'planned',
        priority ENUM('critical','high','normal','low') NOT NULL DEFAULT 'normal',
        production_line VARCHAR(64),
        start_date DATE,
        target_date DATE,
        completed_date DATE,
        units_completed INT DEFAULT 0,
        units_failed INT DEFAULT 0,
        cost_per_unit_usd DECIMAL(10,2),
        total_cost_usd DECIMAL(12,2),
        assigned_to VARCHAR(128),
        notes TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_status (status),
        INDEX idx_bom (bom_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_mfg_serials (
        id VARCHAR(32) PRIMARY KEY,
        serial_number VARCHAR(64) NOT NULL UNIQUE,
        order_id VARCHAR(32) NOT NULL,
        product_model VARCHAR(64) NOT NULL,
        bom_revision VARCHAR(16),
        firmware_version VARCHAR(32),
        status ENUM('manufactured','testing','qc_passed','qc_failed','ready','shipped','activated','returned','refurbished','scrapped') NOT NULL DEFAULT 'manufactured',
        manufactured_at DATETIME NOT NULL,
        shipped_at DATETIME,
        activated_at DATETIME,
        customer_id VARCHAR(32),
        hardware_config JSON,
        test_results JSON,
        notes TEXT,
        INDEX idx_serial (serial_number),
        INDEX idx_order (order_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_mfg_qc (
        id VARCHAR(32) PRIMARY KEY,
        order_id VARCHAR(32) NOT NULL,
        serial_number VARCHAR(64),
        checkpoint_name VARCHAR(128) NOT NULL,
        checkpoint_type ENUM('visual','electrical','mechanical','functional','safety','firmware','calibration','stress','environmental','final') NOT NULL,
        sequence_order INT NOT NULL DEFAULT 0,
        status ENUM('pending','in_progress','passed','failed','skipped','conditional') NOT NULL DEFAULT 'pending',
        inspector VARCHAR(128),
        criteria TEXT,
        measurement_value VARCHAR(64),
        measurement_unit VARCHAR(32),
        pass_range_min FLOAT,
        pass_range_max FLOAT,
        notes TEXT,
        completed_at DATETIME,
        created_at DATETIME NOT NULL,
        INDEX idx_order (order_id),
        INDEX idx_serial (serial_number),
        INDEX idx_type (checkpoint_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

mfgEnsureSchema();
$auth = agentos_auth();

// ─── Helper: Generate serial number ──────────────────────────
function generateSerialNumber(string $model): string {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $model), 0, 4));
    $year = date('y');
    $month = str_pad((string)date('n'), 2, '0', STR_PAD_LEFT);
    $random = strtoupper(bin2hex(random_bytes(4)));
    return "{$prefix}-{$year}{$month}-{$random}";
}

// ─── Handlers ───────────────────────────────────────────────────

function handleBomList(): void {
    $pdo = agentos_pdo();
    $status = $_GET['status'] ?? null;
    $sql = 'SELECT * FROM agentos_bom';
    $params = [];
    if ($status) { $sql .= ' WHERE status = ?'; $params[] = $status; }
    $sql .= ' ORDER BY product_model, revision DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    agentos_respond(['ok' => true, 'boms' => $stmt->fetchAll()]);
}

function handleBomDetail(): void {
    $pdo = agentos_pdo();
    $bomId = $_GET['bom_id'] ?? '';
    if (!$bomId) agentos_error('Missing bom_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_bom WHERE id = ?');
    $stmt->execute([$bomId]);
    $bom = $stmt->fetch();
    if (!$bom) agentos_error('BOM not found', 404);

    $stmt = $pdo->prepare('SELECT c.*, v.vendor_name FROM agentos_bom_components c LEFT JOIN agentos_mfg_vendors v ON c.vendor_id = v.id WHERE c.bom_id = ? ORDER BY c.category, c.part_name');
    $stmt->execute([$bomId]);
    $components = $stmt->fetchAll();
    foreach ($components as &$c) {
        $c['alternatives'] = json_decode($c['alternatives'] ?? '[]', true);
        $c['specifications'] = json_decode($c['specifications'] ?? '{}', true);
    }

    $bom['components'] = $components;
    agentos_respond(['ok' => true, 'bom' => $bom]);
}

function handleCreateBom(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = agentos_id('bom');
    $stmt = $pdo->prepare('INSERT INTO agentos_bom (id, product_model, revision, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([$id, $data['product_model'] ?? 'Alfred-1', $data['revision'] ?? 'A1', 'draft', $data['notes'] ?? null]);

    agentos_respond(['ok' => true, 'bom_id' => $id], 201);
}

function handleAddComponent(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $bomId = $data['bom_id'] ?? '';
    if (!$bomId) agentos_error('Missing bom_id');

    $id = agentos_id('comp');
    $stmt = $pdo->prepare('INSERT INTO agentos_bom_components (id, bom_id, part_number, part_name, category, vendor_id, quantity, unit_cost_usd, lead_time_days, moq, alternatives, specifications, rohs_compliant, critical_component, stock_on_hand, reorder_point, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $bomId, $data['part_number'] ?? '', $data['part_name'] ?? '',
        $data['category'] ?? 'other', $data['vendor_id'] ?? null,
        (int)($data['quantity'] ?? 1), (float)($data['unit_cost_usd'] ?? 0),
        $data['lead_time_days'] ?? null, (int)($data['moq'] ?? 1),
        json_encode($data['alternatives'] ?? []),
        json_encode($data['specifications'] ?? []),
        (int)($data['rohs_compliant'] ?? 1),
        (int)($data['critical_component'] ?? 0),
        (int)($data['stock_on_hand'] ?? 0),
        (int)($data['reorder_point'] ?? 10),
        $data['notes'] ?? null
    ]);

    // Update BOM totals
    $pdo->prepare('UPDATE agentos_bom SET total_components = (SELECT COUNT(*) FROM agentos_bom_components WHERE bom_id = ?), total_cost_usd = (SELECT COALESCE(SUM(unit_cost_usd * quantity), 0) FROM agentos_bom_components WHERE bom_id = ?), updated_at = NOW() WHERE id = ?')
        ->execute([$bomId, $bomId, $bomId]);

    agentos_respond(['ok' => true, 'component_id' => $id], 201);
}

function handleUpdateComponent(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $compId = $data['component_id'] ?? '';
    if (!$compId) agentos_error('Missing component_id');

    $fields = [];
    $params = [];
    $allowed = ['part_number','part_name','category','vendor_id','quantity','unit_cost_usd','lead_time_days','moq','rohs_compliant','critical_component','stock_on_hand','reorder_point','notes'];
    foreach ($allowed as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
    }
    if (isset($data['alternatives'])) { $fields[] = 'alternatives = ?'; $params[] = json_encode($data['alternatives']); }
    if (isset($data['specifications'])) { $fields[] = 'specifications = ?'; $params[] = json_encode($data['specifications']); }

    if (empty($fields)) agentos_error('No fields to update');
    $params[] = $compId;
    $pdo->prepare('UPDATE agentos_bom_components SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    // Recalculate BOM totals
    $bomId = $pdo->prepare('SELECT bom_id FROM agentos_bom_components WHERE id = ?');
    $bomId->execute([$compId]);
    $bId = $bomId->fetchColumn();
    if ($bId) {
        $pdo->prepare('UPDATE agentos_bom SET total_cost_usd = (SELECT COALESCE(SUM(unit_cost_usd * quantity), 0) FROM agentos_bom_components WHERE bom_id = ?), updated_at = NOW() WHERE id = ?')
            ->execute([$bId, $bId]);
    }

    agentos_respond(['ok' => true, 'updated' => true]);
}

function handleVendors(): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->query('SELECT * FROM agentos_mfg_vendors WHERE active = 1 ORDER BY vendor_name');
    $vendors = $stmt->fetchAll();
    foreach ($vendors as &$v) {
        $v['certifications'] = json_decode($v['certifications'] ?? '[]', true);
        $v['specializations'] = json_decode($v['specializations'] ?? '[]', true);
    }
    agentos_respond(['ok' => true, 'vendors' => $vendors]);
}

function handleAddVendor(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = agentos_id('vnd');
    $stmt = $pdo->prepare('INSERT INTO agentos_mfg_vendors (id, vendor_name, vendor_code, country, city, contact_name, contact_email, contact_phone, payment_terms, certifications, specializations, lead_time_avg_days, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $data['vendor_name'] ?? '', $data['vendor_code'] ?? '',
        $data['country'] ?? null, $data['city'] ?? null,
        $data['contact_name'] ?? null, $data['contact_email'] ?? null,
        $data['contact_phone'] ?? null, $data['payment_terms'] ?? 'Net 30',
        json_encode($data['certifications'] ?? []),
        json_encode($data['specializations'] ?? []),
        $data['lead_time_avg_days'] ?? null,
        $data['notes'] ?? null
    ]);

    agentos_respond(['ok' => true, 'vendor_id' => $id], 201);
}

function handleCreateOrder(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $bomId = $data['bom_id'] ?? '';
    if (!$bomId) agentos_error('Missing bom_id');

    // Verify BOM exists and is approved
    $stmt = $pdo->prepare('SELECT * FROM agentos_bom WHERE id = ?');
    $stmt->execute([$bomId]);
    $bom = $stmt->fetch();
    if (!$bom) agentos_error('BOM not found', 404);

    $id = agentos_id('ord');
    $orderNum = 'PO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $quantity = max(1, (int)($data['quantity'] ?? 1));

    $stmt = $pdo->prepare('INSERT INTO agentos_mfg_orders (id, bom_id, order_number, quantity, status, priority, production_line, start_date, target_date, cost_per_unit_usd, total_cost_usd, assigned_to, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $bomId, $orderNum, $quantity, 'planned',
        $data['priority'] ?? 'normal',
        $data['production_line'] ?? null,
        $data['start_date'] ?? null,
        $data['target_date'] ?? null,
        (float)$bom['total_cost_usd'],
        (float)$bom['total_cost_usd'] * $quantity,
        $data['assigned_to'] ?? null,
        $data['notes'] ?? null
    ]);

    // Create standard QC checkpoints for each unit
    $checkpoints = [
        ['PCB Visual Inspection', 'visual', 1],
        ['Electrical Continuity Test', 'electrical', 2],
        ['Motor Function Test', 'mechanical', 3],
        ['Sensor Calibration', 'calibration', 4],
        ['Battery Safety Test', 'safety', 5],
        ['Firmware Flash & Verify', 'firmware', 6],
        ['Functional Integration Test', 'functional', 7],
        ['Safety Interlock Test', 'safety', 8],
        ['Stress Test (2hr)', 'stress', 9],
        ['Environmental Seal Test', 'environmental', 10],
        ['Final Inspection & Pack', 'final', 11],
    ];

    $stmtQc = $pdo->prepare('INSERT INTO agentos_mfg_qc (id, order_id, checkpoint_name, checkpoint_type, sequence_order, status, criteria, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    foreach ($checkpoints as $cp) {
        $stmtQc->execute([agentos_id('qc'), $id, $cp[0], $cp[1], $cp[2], 'pending', null]);
    }

    agentos_respond(['ok' => true, 'order_id' => $id, 'order_number' => $orderNum], 201);
}

function handleOrders(): void {
    $pdo = agentos_pdo();
    $status = $_GET['status'] ?? null;
    $sql = 'SELECT o.*, b.product_model, b.revision FROM agentos_mfg_orders o JOIN agentos_bom b ON o.bom_id = b.id';
    $params = [];
    if ($status) { $sql .= ' WHERE o.status = ?'; $params[] = $status; }
    $sql .= ' ORDER BY o.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    agentos_respond(['ok' => true, 'orders' => $stmt->fetchAll()]);
}

function handleOrderDetail(): void {
    $pdo = agentos_pdo();
    $orderId = $_GET['order_id'] ?? '';
    if (!$orderId) agentos_error('Missing order_id');

    $stmt = $pdo->prepare('SELECT o.*, b.product_model, b.revision FROM agentos_mfg_orders o JOIN agentos_bom b ON o.bom_id = b.id WHERE o.id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) agentos_error('Order not found', 404);

    // QC checkpoints
    $stmt = $pdo->prepare('SELECT * FROM agentos_mfg_qc WHERE order_id = ? ORDER BY sequence_order');
    $stmt->execute([$orderId]);
    $order['qc_checkpoints'] = $stmt->fetchAll();

    // Serial numbers
    $stmt = $pdo->prepare('SELECT * FROM agentos_mfg_serials WHERE order_id = ? ORDER BY manufactured_at');
    $stmt->execute([$orderId]);
    $serials = $stmt->fetchAll();
    foreach ($serials as &$s) {
        $s['hardware_config'] = json_decode($s['hardware_config'] ?? '{}', true);
        $s['test_results'] = json_decode($s['test_results'] ?? '{}', true);
    }
    $order['serial_numbers'] = $serials;

    agentos_respond(['ok' => true, 'order' => $order]);
}

function handleUpdateOrder(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'] ?? '';
    if (!$orderId) agentos_error('Missing order_id');

    $fields = [];
    $params = [];
    $allowed = ['status','priority','production_line','start_date','target_date','completed_date','units_completed','units_failed','assigned_to','notes'];
    foreach ($allowed as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
    }
    if (empty($fields)) agentos_error('No fields to update');
    $fields[] = 'updated_at = NOW()';
    $params[] = $orderId;

    $pdo->prepare('UPDATE agentos_mfg_orders SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    agentos_respond(['ok' => true, 'updated' => true]);
}

function handleGenerateSerial(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $orderId = $data['order_id'] ?? '';
    if (!$orderId) agentos_error('Missing order_id');

    $stmt = $pdo->prepare('SELECT o.*, b.product_model, b.revision FROM agentos_mfg_orders o JOIN agentos_bom b ON o.bom_id = b.id WHERE o.id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) agentos_error('Order not found', 404);

    $count = min(max((int)($data['count'] ?? 1), 1), 100);
    $serials = [];

    $stmt = $pdo->prepare('INSERT INTO agentos_mfg_serials (id, serial_number, order_id, product_model, bom_revision, firmware_version, status, manufactured_at, hardware_config, test_results) VALUES (?,?,?,?,?,?,?,NOW(),?,?)');
    for ($i = 0; $i < $count; $i++) {
        $serial = generateSerialNumber($order['product_model']);
        $id = agentos_id('sn');
        $stmt->execute([
            $id, $serial, $orderId, $order['product_model'], $order['revision'],
            $data['firmware_version'] ?? null, 'manufactured',
            json_encode($data['hardware_config'] ?? []),
            json_encode([])
        ]);
        $serials[] = ['id' => $id, 'serial_number' => $serial];
    }

    agentos_respond(['ok' => true, 'serials' => $serials, 'count' => $count], 201);
}

function handleSerialLookup(): void {
    $pdo = agentos_pdo();
    $sn = $_GET['serial_number'] ?? '';
    if (!$sn) agentos_error('Missing serial_number');

    $stmt = $pdo->prepare('SELECT s.*, o.order_number FROM agentos_mfg_serials s JOIN agentos_mfg_orders o ON s.order_id = o.id WHERE s.serial_number = ?');
    $stmt->execute([$sn]);
    $unit = $stmt->fetch();
    if (!$unit) agentos_error('Serial number not found', 404);

    $unit['hardware_config'] = json_decode($unit['hardware_config'] ?? '{}', true);
    $unit['test_results'] = json_decode($unit['test_results'] ?? '{}', true);

    agentos_respond(['ok' => true, 'unit' => $unit]);
}

function handleQcCheckpoints(): void {
    $pdo = agentos_pdo();
    $orderId = $_GET['order_id'] ?? '';
    if (!$orderId) agentos_error('Missing order_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_mfg_qc WHERE order_id = ? ORDER BY sequence_order');
    $stmt->execute([$orderId]);
    $cps = $stmt->fetchAll();

    $passed = count(array_filter($cps, fn($c) => $c['status'] === 'passed'));
    $total = count($cps);

    agentos_respond(['ok' => true, 'checkpoints' => $cps, 'summary' => [
        'total' => $total, 'passed' => $passed, 'progress' => $total > 0 ? round(($passed / $total) * 100, 1) : 0
    ]]);
}

function handleUpdateQc(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $qcId = $data['qc_id'] ?? '';
    if (!$qcId) agentos_error('Missing qc_id');

    $fields = ['status = ?', 'updated_at = NOW()'];
    $params = [$data['status'] ?? 'pending'];

    if (isset($data['inspector'])) { $fields[] = 'inspector = ?'; $params[] = $data['inspector']; }
    if (isset($data['measurement_value'])) { $fields[] = 'measurement_value = ?'; $params[] = $data['measurement_value']; }
    if (isset($data['measurement_unit'])) { $fields[] = 'measurement_unit = ?'; $params[] = $data['measurement_unit']; }
    if (isset($data['notes'])) { $fields[] = 'notes = ?'; $params[] = $data['notes']; }
    if (in_array($data['status'] ?? '', ['passed','failed','conditional','skipped'], true)) {
        $fields[] = 'completed_at = NOW()';
    }

    $params[] = $qcId;
    $pdo->prepare('UPDATE agentos_mfg_qc SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    agentos_respond(['ok' => true, 'updated' => true]);
}

function handleInventory(): void {
    $pdo = agentos_pdo();
    $lowStock = isset($_GET['low_stock']);

    $sql = 'SELECT c.part_number, c.part_name, c.category, c.stock_on_hand, c.reorder_point, c.unit_cost_usd, c.lead_time_days, c.critical_component, v.vendor_name FROM agentos_bom_components c LEFT JOIN agentos_mfg_vendors v ON c.vendor_id = v.id';
    if ($lowStock) {
        $sql .= ' WHERE c.stock_on_hand <= c.reorder_point';
    }
    $sql .= ' ORDER BY c.critical_component DESC, c.stock_on_hand ASC';

    $items = $pdo->query($sql)->fetchAll();
    agentos_respond(['ok' => true, 'inventory' => $items, 'count' => count($items)]);
}

function handleCostAnalysis(): void {
    $pdo = agentos_pdo();
    $bomId = $_GET['bom_id'] ?? '';
    if (!$bomId) agentos_error('Missing bom_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_bom WHERE id = ?');
    $stmt->execute([$bomId]);
    $bom = $stmt->fetch();
    if (!$bom) agentos_error('BOM not found', 404);

    $stmt = $pdo->prepare('SELECT category, SUM(unit_cost_usd * quantity) as category_cost, COUNT(*) as component_count FROM agentos_bom_components WHERE bom_id = ? GROUP BY category ORDER BY category_cost DESC');
    $stmt->execute([$bomId]);
    $breakdown = $stmt->fetchAll();

    $totalMaterial = (float)$bom['total_cost_usd'];
    $laborEstimate = $totalMaterial * 0.15;
    $overheadEstimate = $totalMaterial * 0.10;
    $testingEstimate = $totalMaterial * 0.05;
    $totalCost = $totalMaterial + $laborEstimate + $overheadEstimate + $testingEstimate;

    agentos_respond(['ok' => true, 'cost_analysis' => [
        'product_model' => $bom['product_model'],
        'revision' => $bom['revision'],
        'material_cost_usd' => $totalMaterial,
        'labor_estimate_usd' => round($laborEstimate, 2),
        'overhead_estimate_usd' => round($overheadEstimate, 2),
        'testing_estimate_usd' => round($testingEstimate, 2),
        'total_unit_cost_usd' => round($totalCost, 2),
        'category_breakdown' => $breakdown,
        'margins' => [
            'at_30_percent' => round($totalCost / 0.70, 2),
            'at_40_percent' => round($totalCost / 0.60, 2),
            'at_50_percent' => round($totalCost / 0.50, 2),
        ]
    ]]);
}

function handleProductionStats(): void {
    $pdo = agentos_pdo();

    $orderStats = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_mfg_orders GROUP BY status")->fetchAll();
    $totalUnits = $pdo->query("SELECT SUM(quantity) as total, SUM(units_completed) as completed, SUM(units_failed) as failed FROM agentos_mfg_orders")->fetch();
    $serialStats = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_mfg_serials GROUP BY status")->fetchAll();
    $qcStats = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_mfg_qc GROUP BY status")->fetchAll();

    agentos_respond(['ok' => true, 'production_stats' => [
        'orders_by_status' => $orderStats,
        'total_units_ordered' => (int)($totalUnits['total'] ?? 0),
        'total_units_completed' => (int)($totalUnits['completed'] ?? 0),
        'total_units_failed' => (int)($totalUnits['failed'] ?? 0),
        'yield_rate' => ($totalUnits['completed'] ?? 0) > 0
            ? round((1 - ($totalUnits['failed'] / $totalUnits['completed'])) * 100, 1) : 0,
        'serial_numbers_by_status' => $serialStats,
        'qc_by_status' => $qcStats
    ]]);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'bom_list'         => 'handleBomList',
    'bom_detail'       => 'handleBomDetail',
    'create_bom'       => 'handleCreateBom',
    'add_component'    => 'handleAddComponent',
    'update_component' => 'handleUpdateComponent',
    'vendors'          => 'handleVendors',
    'add_vendor'       => 'handleAddVendor',
    'create_order'     => 'handleCreateOrder',
    'orders'           => 'handleOrders',
    'order_detail'     => 'handleOrderDetail',
    'update_order'     => 'handleUpdateOrder',
    'generate_serial'  => 'handleGenerateSerial',
    'serial_lookup'    => 'handleSerialLookup',
    'qc_checkpoints'   => 'handleQcCheckpoints',
    'update_qc'        => 'handleUpdateQc',
    'inventory'        => 'handleInventory',
    'cost_analysis'    => 'handleCostAnalysis',
    'production_stats' => 'handleProductionStats',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — Manufacturing & BOM System', 'version' => AGENTOS_VERSION,
        'description' => 'Bill of Materials, production orders, QC, serial numbers, vendor management',
        'endpoints' => array_keys($routes)]);
}

$routes[$action]();
