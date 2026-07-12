<?php
/**
 * GSM Alfred OS — Regulatory Certification Tracker
 *
 * Tracks ALL regulatory certifications required to sell robots in every market.
 * CSA, FCC, CE, ISO 13482, UL, IC, RoHS, WEEE, RED, MDR, and more.
 * Manages test lab submissions, document lifecycle, expiration alerts.
 *
 * Endpoints (14):
 *   certifications       — List all certifications and their status
 *   certification_detail — Get detailed certification info
 *   create_cert          — Add a new certification requirement
 *   update_cert          — Update certification status/docs
 *   markets              — List target markets and their requirements
 *   add_market           — Add a target market
 *   test_labs            — List accredited test labs
 *   add_test_lab         — Add a test lab
 *   submit_test          — Submit product for testing
 *   test_status          — Get test submission status
 *   compliance_matrix    — Full compliance matrix (product × market × cert)
 *   expiring_certs       — Get certs expiring within N days
 *   documents            — Manage certification documents
 *   audit_readiness      — Overall readiness assessment
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
function certEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_certifications (
        id VARCHAR(32) PRIMARY KEY,
        cert_code VARCHAR(32) NOT NULL,
        cert_name VARCHAR(128) NOT NULL,
        issuing_body VARCHAR(128) NOT NULL,
        standard_ref VARCHAR(128),
        product_model VARCHAR(64),
        market VARCHAR(64) NOT NULL,
        category ENUM('safety','electromagnetic','wireless','environmental','medical','privacy','accessibility','transport') NOT NULL,
        status ENUM('not_started','researching','preparing','submitted','testing','conditional','approved','expired','rejected','renewal') NOT NULL DEFAULT 'not_started',
        priority ENUM('critical','high','medium','low') NOT NULL DEFAULT 'high',
        estimated_cost_usd INT,
        actual_cost_usd INT,
        estimated_duration_weeks INT,
        submitted_at DATETIME,
        approved_at DATETIME,
        expires_at DATETIME,
        certificate_number VARCHAR(128),
        test_lab_id VARCHAR(32),
        notes TEXT,
        documents JSON,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_cert_product_market (cert_code, product_model, market),
        INDEX idx_status (status),
        INDEX idx_market (market),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_cert_markets (
        id VARCHAR(32) PRIMARY KEY,
        market_code VARCHAR(16) NOT NULL UNIQUE,
        market_name VARCHAR(128) NOT NULL,
        region ENUM('north_america','europe','asia_pacific','latin_america','middle_east','africa','global') NOT NULL,
        required_certs JSON NOT NULL,
        import_restrictions TEXT,
        language_requirements JSON,
        local_representative_required TINYINT(1) DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        notes TEXT,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_cert_test_labs (
        id VARCHAR(32) PRIMARY KEY,
        lab_name VARCHAR(128) NOT NULL,
        accreditations JSON NOT NULL,
        country VARCHAR(64),
        city VARCHAR(64),
        contact_email VARCHAR(128),
        contact_phone VARCHAR(32),
        specializations JSON,
        avg_turnaround_weeks INT,
        rating FLOAT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_cert_test_submissions (
        id VARCHAR(32) PRIMARY KEY,
        certification_id VARCHAR(32) NOT NULL,
        test_lab_id VARCHAR(32) NOT NULL,
        submission_type ENUM('initial','retest','renewal','modification') NOT NULL DEFAULT 'initial',
        status ENUM('preparing','submitted','in_testing','awaiting_results','passed','failed','conditional') NOT NULL DEFAULT 'preparing',
        samples_sent INT DEFAULT 0,
        test_report_url VARCHAR(512),
        findings JSON,
        submitted_at DATETIME,
        results_at DATETIME,
        cost_usd INT,
        notes TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_cert (certification_id),
        INDEX idx_lab (test_lab_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ─── Seed Required Certifications ─────────────────────────
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM agentos_certifications");
    if ((int)$stmt->fetch()['c'] === 0) {
        $certs = [
            // CANADA
            ['CSA-C22.2', 'CSA Electrical Safety', 'CSA Group', 'CAN/CSA-C22.2 No. 60950-1', 'Alfred-1', 'CA', 'safety', 'critical', 15000, 12],
            ['IC-RSS', 'Industry Canada Radio', 'ISED Canada', 'RSS-247/RSS-102', 'Alfred-1', 'CA', 'wireless', 'critical', 8000, 8],
            ['CSA-Z434', 'CSA Robot Safety', 'CSA Group', 'CSA Z434-14', 'Alfred-1', 'CA', 'safety', 'critical', 20000, 16],

            // USA
            ['FCC-Part15', 'FCC Part 15 Certification', 'FCC', '47 CFR Part 15', 'Alfred-1', 'US', 'wireless', 'critical', 10000, 8],
            ['UL-3300', 'UL Robot Safety', 'UL LLC', 'UL 3300', 'Alfred-1', 'US', 'safety', 'critical', 25000, 16],
            ['FCC-EMC', 'FCC EMC Compliance', 'FCC', '47 CFR Part 15B', 'Alfred-1', 'US', 'electromagnetic', 'critical', 8000, 6],

            // EUROPE
            ['CE-MachDir', 'CE Machinery Directive', 'EU Commission', '2006/42/EC', 'Alfred-1', 'EU', 'safety', 'critical', 18000, 14],
            ['CE-RED', 'CE Radio Equipment Directive', 'EU Commission', '2014/53/EU', 'Alfred-1', 'EU', 'wireless', 'critical', 12000, 10],
            ['CE-EMC', 'CE EMC Directive', 'EU Commission', '2014/30/EU', 'Alfred-1', 'EU', 'electromagnetic', 'high', 8000, 8],
            ['CE-LVD', 'CE Low Voltage Directive', 'EU Commission', '2014/35/EU', 'Alfred-1', 'EU', 'safety', 'high', 6000, 6],
            ['RoHS', 'RoHS Compliance', 'EU Commission', '2011/65/EU', 'Alfred-1', 'EU', 'environmental', 'high', 3000, 4],
            ['WEEE', 'WEEE Registration', 'National Authorities', '2012/19/EU', 'Alfred-1', 'EU', 'environmental', 'medium', 2000, 4],
            ['GDPR-DPA', 'GDPR Data Protection Assessment', 'DPA', 'GDPR Art. 35', 'Alfred-1', 'EU', 'privacy', 'critical', 5000, 8],

            // ISO INTERNATIONAL
            ['ISO-13482', 'Personal Care Robots Safety', 'ISO', 'ISO 13482:2014', 'Alfred-1', 'INTL', 'safety', 'critical', 30000, 24],
            ['ISO-13849', 'Safety Control Systems', 'ISO', 'ISO 13849-1:2023', 'Alfred-1', 'INTL', 'safety', 'critical', 15000, 12],
            ['ISO-10218', 'Industrial Robot Safety', 'ISO', 'ISO 10218-1:2011', 'Alfred-1', 'INTL', 'safety', 'high', 12000, 12],
            ['IEC-62443', 'Industrial Cybersecurity', 'IEC', 'IEC 62443', 'Alfred-1', 'INTL', 'safety', 'high', 20000, 16],

            // Battery / Transport
            ['UN38.3', 'UN Battery Transport Safety', 'UN', 'UN 38.3', 'Alfred-1', 'INTL', 'transport', 'critical', 8000, 6],
            ['UL-2272', 'Battery System Safety', 'UL LLC', 'UL 2272', 'Alfred-1', 'US', 'safety', 'critical', 12000, 10],

            // JAPAN
            ['PSE', 'Japan Electrical Safety', 'METI', 'PSE Mark', 'Alfred-1', 'JP', 'safety', 'high', 10000, 10],
            ['TELEC', 'Japan Radio Certification', 'MIC', 'Radio Law', 'Alfred-1', 'JP', 'wireless', 'high', 8000, 8],
        ];

        $stmt = $pdo->prepare('INSERT INTO agentos_certifications (id, cert_code, cert_name, issuing_body, standard_ref, product_model, market, category, status, priority, estimated_cost_usd, estimated_duration_weeks, documents, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        foreach ($certs as $c) {
            $stmt->execute([agentos_id('cert'), $c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], 'not_started', $c[7], $c[8], $c[9], '[]']);
        }

        // Seed markets
        $markets = [
            ['CA', 'Canada', 'north_america', ['CSA-C22.2','IC-RSS','CSA-Z434','ISO-13482','UN38.3'], 1],
            ['US', 'United States', 'north_america', ['FCC-Part15','FCC-EMC','UL-3300','UL-2272','ISO-13482','UN38.3'], 0],
            ['EU', 'European Union', 'europe', ['CE-MachDir','CE-RED','CE-EMC','CE-LVD','RoHS','WEEE','GDPR-DPA','ISO-13482','UN38.3'], 1],
            ['UK', 'United Kingdom', 'europe', ['UKCA-Safety','UKCA-EMC','UKCA-Radio','RoHS','WEEE','ISO-13482','UN38.3'], 1],
            ['JP', 'Japan', 'asia_pacific', ['PSE','TELEC','ISO-13482','UN38.3'], 1],
            ['AU', 'Australia', 'asia_pacific', ['RCM-EMC','RCM-Radio','ISO-13482','UN38.3'], 0],
        ];

        $stmt = $pdo->prepare('INSERT INTO agentos_cert_markets (id, market_code, market_name, region, required_certs, local_representative_required, created_at) VALUES (?,?,?,?,?,?,NOW())');
        foreach ($markets as $m) {
            $stmt->execute([agentos_id('mkt'), $m[0], $m[1], $m[2], json_encode($m[3]), $m[4]]);
        }

        // Seed test labs
        $labs = [
            ['UL Solutions', ['UL','FCC','IC','CSA'], 'US', 'Northbrook, IL', 'certifications@ul.com', 8, ['safety','wireless','battery']],
            ['TÜV Rheinland', ['CE','ISO','IEC'], 'DE', 'Cologne', 'robotics@tuv.com', 10, ['safety','machinery','cybersecurity']],
            ['Intertek', ['FCC','CE','CSA','IC'], 'US', 'Cortland, NY', 'testing@intertek.com', 6, ['electromagnetic','wireless','safety']],
            ['Bureau Veritas', ['CE','ISO','RoHS'], 'FR', 'Paris', 'certs@bureauveritas.com', 8, ['environmental','safety','machinery']],
            ['CSA Group', ['CSA','IC','UL'], 'CA', 'Toronto, ON', 'testing@csagroup.org', 12, ['safety','electrical','robot']],
            ['SGS', ['CE','ISO','RoHS','WEEE'], 'CH', 'Geneva', 'testing@sgs.com', 8, ['environmental','safety','chemical']],
        ];

        $stmt = $pdo->prepare('INSERT INTO agentos_cert_test_labs (id, lab_name, accreditations, country, city, contact_email, avg_turnaround_weeks, specializations, active, created_at) VALUES (?,?,?,?,?,?,?,?,1,NOW())');
        foreach ($labs as $l) {
            $stmt->execute([agentos_id('lab'), $l[0], json_encode($l[1]), $l[2], $l[3], $l[4], $l[5], json_encode($l[6])]);
        }
    }
}

certEnsureSchema();
$auth = agentos_auth();

// ─── Handlers ───────────────────────────────────────────────────

function handleCertifications(): void {
    $pdo = agentos_pdo();
    $market = $_GET['market'] ?? null;
    $status = $_GET['status'] ?? null;
    $category = $_GET['category'] ?? null;

    $sql = 'SELECT * FROM agentos_certifications WHERE 1=1';
    $params = [];
    if ($market) { $sql .= ' AND market = ?'; $params[] = $market; }
    if ($status) { $sql .= ' AND status = ?'; $params[] = $status; }
    if ($category) { $sql .= ' AND category = ?'; $params[] = $category; }
    $sql .= ' ORDER BY priority ASC, market, cert_code';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $certs = $stmt->fetchAll();
    foreach ($certs as &$c) $c['documents'] = json_decode($c['documents'] ?? '[]', true);

    // Summary
    $total = count($certs);
    $byStatus = [];
    $totalCost = 0;
    foreach ($certs as $c) {
        $byStatus[$c['status']] = ($byStatus[$c['status']] ?? 0) + 1;
        $totalCost += (int)($c['estimated_cost_usd'] ?? 0);
    }

    agentos_respond(['ok' => true, 'certifications' => $certs, 'summary' => [
        'total' => $total, 'by_status' => $byStatus, 'estimated_total_cost_usd' => $totalCost
    ]]);
}

function handleCertificationDetail(): void {
    $pdo = agentos_pdo();
    $certId = $_GET['cert_id'] ?? '';
    if (!$certId) agentos_error('Missing cert_id');

    $stmt = $pdo->prepare('SELECT c.*, l.lab_name, l.country as lab_country FROM agentos_certifications c LEFT JOIN agentos_cert_test_labs l ON c.test_lab_id = l.id WHERE c.id = ?');
    $stmt->execute([$certId]);
    $cert = $stmt->fetch();
    if (!$cert) agentos_error('Certification not found', 404);

    $cert['documents'] = json_decode($cert['documents'] ?? '[]', true);

    // Get test submissions
    $stmt = $pdo->prepare('SELECT * FROM agentos_cert_test_submissions WHERE certification_id = ? ORDER BY created_at DESC');
    $stmt->execute([$certId]);
    $cert['test_submissions'] = $stmt->fetchAll();
    foreach ($cert['test_submissions'] as &$s) $s['findings'] = json_decode($s['findings'] ?? '[]', true);

    agentos_respond(['ok' => true, 'certification' => $cert]);
}

function handleCreateCert(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['cert_code', 'cert_name', 'issuing_body', 'market', 'category'];
    foreach ($required as $f) { if (empty($data[$f])) agentos_error("Missing: $f"); }

    $id = agentos_id('cert');
    $stmt = $pdo->prepare('INSERT INTO agentos_certifications (id, cert_code, cert_name, issuing_body, standard_ref, product_model, market, category, status, priority, estimated_cost_usd, estimated_duration_weeks, notes, documents, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $data['cert_code'], $data['cert_name'], $data['issuing_body'],
        $data['standard_ref'] ?? null, $data['product_model'] ?? 'Alfred-1',
        $data['market'], $data['category'], 'not_started',
        $data['priority'] ?? 'high',
        $data['estimated_cost_usd'] ?? null,
        $data['estimated_duration_weeks'] ?? null,
        $data['notes'] ?? null,
        json_encode($data['documents'] ?? [])
    ]);

    agentos_respond(['ok' => true, 'cert_id' => $id], 201);
}

function handleUpdateCert(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $certId = $data['cert_id'] ?? '';
    if (!$certId) agentos_error('Missing cert_id');

    $fields = [];
    $params = [];
    $allowed = ['status','priority','estimated_cost_usd','actual_cost_usd','certificate_number','test_lab_id','notes','submitted_at','approved_at','expires_at'];
    foreach ($allowed as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
    }
    if (isset($data['documents'])) { $fields[] = 'documents = ?'; $params[] = json_encode($data['documents']); }

    if (empty($fields)) agentos_error('No fields to update');
    $fields[] = 'updated_at = NOW()';
    $params[] = $certId;

    $pdo->prepare('UPDATE agentos_certifications SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    agentos_audit(['action_type' => 'cert_update', 'user_id' => $auth['user_id'], 'status' => 'success',
        'metadata' => ['cert_id' => $certId, 'new_status' => $data['status'] ?? 'unchanged']]);

    agentos_respond(['ok' => true, 'updated' => true]);
}

function handleMarkets(): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->query('SELECT * FROM agentos_cert_markets ORDER BY region, market_name');
    $markets = $stmt->fetchAll();
    foreach ($markets as &$m) {
        $m['required_certs'] = json_decode($m['required_certs'], true);
        $m['language_requirements'] = json_decode($m['language_requirements'] ?? '[]', true);
    }
    agentos_respond(['ok' => true, 'markets' => $markets]);
}

function handleAddMarket(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = agentos_id('mkt');
    $stmt = $pdo->prepare('INSERT INTO agentos_cert_markets (id, market_code, market_name, region, required_certs, import_restrictions, language_requirements, local_representative_required, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $data['market_code'] ?? '', $data['market_name'] ?? '',
        $data['region'] ?? 'global',
        json_encode($data['required_certs'] ?? []),
        $data['import_restrictions'] ?? null,
        json_encode($data['language_requirements'] ?? []),
        (int)($data['local_representative_required'] ?? 0),
        $data['notes'] ?? null
    ]);

    agentos_respond(['ok' => true, 'market_id' => $id], 201);
}

function handleTestLabs(): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->query('SELECT * FROM agentos_cert_test_labs WHERE active = 1 ORDER BY lab_name');
    $labs = $stmt->fetchAll();
    foreach ($labs as &$l) {
        $l['accreditations'] = json_decode($l['accreditations'], true);
        $l['specializations'] = json_decode($l['specializations'] ?? '[]', true);
    }
    agentos_respond(['ok' => true, 'test_labs' => $labs]);
}

function handleAddTestLab(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = agentos_id('lab');
    $stmt = $pdo->prepare('INSERT INTO agentos_cert_test_labs (id, lab_name, accreditations, country, city, contact_email, contact_phone, specializations, avg_turnaround_weeks, active, created_at) VALUES (?,?,?,?,?,?,?,?,?,1,NOW())');
    $stmt->execute([
        $id, $data['lab_name'] ?? '', json_encode($data['accreditations'] ?? []),
        $data['country'] ?? null, $data['city'] ?? null,
        $data['contact_email'] ?? null, $data['contact_phone'] ?? null,
        json_encode($data['specializations'] ?? []),
        $data['avg_turnaround_weeks'] ?? null
    ]);

    agentos_respond(['ok' => true, 'lab_id' => $id], 201);
}

function handleSubmitTest(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $certId = $data['certification_id'] ?? '';
    $labId = $data['test_lab_id'] ?? '';
    if (!$certId || !$labId) agentos_error('Missing certification_id or test_lab_id');

    $id = agentos_id('test');
    $stmt = $pdo->prepare('INSERT INTO agentos_cert_test_submissions (id, certification_id, test_lab_id, submission_type, status, samples_sent, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $certId, $labId,
        $data['submission_type'] ?? 'initial',
        'preparing',
        (int)($data['samples_sent'] ?? 0),
        $data['notes'] ?? null
    ]);

    // Update certification status
    $pdo->prepare("UPDATE agentos_certifications SET status = 'preparing', test_lab_id = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$labId, $certId]);

    agentos_respond(['ok' => true, 'submission_id' => $id], 201);
}

function handleTestStatus(): void {
    $pdo = agentos_pdo();
    $submissionId = $_GET['submission_id'] ?? '';
    if (!$submissionId) agentos_error('Missing submission_id');

    $stmt = $pdo->prepare('SELECT s.*, l.lab_name, c.cert_name, c.cert_code FROM agentos_cert_test_submissions s JOIN agentos_cert_test_labs l ON s.test_lab_id = l.id JOIN agentos_certifications c ON s.certification_id = c.id WHERE s.id = ?');
    $stmt->execute([$submissionId]);
    $sub = $stmt->fetch();
    if (!$sub) agentos_error('Submission not found', 404);

    $sub['findings'] = json_decode($sub['findings'] ?? '[]', true);
    agentos_respond(['ok' => true, 'submission' => $sub]);
}

function handleComplianceMatrix(): void {
    $pdo = agentos_pdo();

    // Get all markets
    $markets = $pdo->query('SELECT market_code, market_name, required_certs FROM agentos_cert_markets WHERE active = 1')->fetchAll();

    // Get all certifications
    $certs = $pdo->query('SELECT cert_code, cert_name, market, status, priority, approved_at, expires_at FROM agentos_certifications ORDER BY market, cert_code')->fetchAll();

    $certByCodeMarket = [];
    foreach ($certs as $c) {
        $certByCodeMarket[$c['market'] . ':' . $c['cert_code']] = $c;
    }

    $matrix = [];
    foreach ($markets as $m) {
        $m['required_certs'] = json_decode($m['required_certs'], true);
        $marketEntry = [
            'market_code' => $m['market_code'],
            'market_name' => $m['market_name'],
            'certifications' => [],
            'ready_to_sell' => true
        ];

        foreach ($m['required_certs'] as $certCode) {
            $key = $m['market_code'] . ':' . $certCode;
            $intlKey = 'INTL:' . $certCode;
            $cert = $certByCodeMarket[$key] ?? $certByCodeMarket[$intlKey] ?? null;

            $status = $cert ? $cert['status'] : 'not_started';
            if ($status !== 'approved') $marketEntry['ready_to_sell'] = false;

            $marketEntry['certifications'][] = [
                'cert_code' => $certCode,
                'status' => $status,
                'priority' => $cert['priority'] ?? 'unknown',
                'approved_at' => $cert['approved_at'] ?? null,
                'expires_at' => $cert['expires_at'] ?? null
            ];
        }

        $matrix[] = $marketEntry;
    }

    agentos_respond(['ok' => true, 'compliance_matrix' => $matrix]);
}

function handleExpiringCerts(): void {
    $pdo = agentos_pdo();
    $days = min(max((int)($_GET['days'] ?? 90), 1), 365);

    $stmt = $pdo->prepare("SELECT * FROM agentos_certifications WHERE expires_at IS NOT NULL AND expires_at <= DATE_ADD(NOW(), INTERVAL ? DAY) AND status = 'approved' ORDER BY expires_at ASC");
    $stmt->execute([$days]);
    $expiring = $stmt->fetchAll();
    foreach ($expiring as &$c) {
        $c['documents'] = json_decode($c['documents'] ?? '[]', true);
        $c['days_until_expiry'] = max(0, (int)((strtotime($c['expires_at']) - time()) / 86400));
    }

    agentos_respond(['ok' => true, 'expiring_certifications' => $expiring, 'window_days' => $days]);
}

function handleDocuments(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $certId = $data['cert_id'] ?? '';
        if (!$certId) agentos_error('Missing cert_id');

        $stmt = $pdo->prepare('SELECT documents FROM agentos_certifications WHERE id = ?');
        $stmt->execute([$certId]);
        $cert = $stmt->fetch();
        if (!$cert) agentos_error('Certification not found', 404);

        $docs = json_decode($cert['documents'] ?? '[]', true);
        $docs[] = [
            'name' => $data['document_name'] ?? 'Unnamed',
            'type' => $data['document_type'] ?? 'other',
            'url' => $data['url'] ?? '',
            'uploaded_at' => date('c')
        ];

        $pdo->prepare('UPDATE agentos_certifications SET documents = ?, updated_at = NOW() WHERE id = ?')
            ->execute([json_encode($docs), $certId]);

        agentos_respond(['ok' => true, 'documents' => $docs]);
    } else {
        $certId = $_GET['cert_id'] ?? '';
        if (!$certId) agentos_error('Missing cert_id');

        $stmt = $pdo->prepare('SELECT documents FROM agentos_certifications WHERE id = ?');
        $stmt->execute([$certId]);
        $cert = $stmt->fetch();
        if (!$cert) agentos_error('Certification not found', 404);

        agentos_respond(['ok' => true, 'documents' => json_decode($cert['documents'] ?? '[]', true)]);
    }
}

function handleAuditReadiness(): void {
    $pdo = agentos_pdo();

    $total = (int)$pdo->query('SELECT COUNT(*) as c FROM agentos_certifications')->fetch()['c'];
    $approved = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_certifications WHERE status = 'approved'")->fetch()['c'];
    $critical = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_certifications WHERE priority = 'critical'")->fetch()['c'];
    $criticalApproved = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_certifications WHERE priority = 'critical' AND status = 'approved'")->fetch()['c'];

    $stmt = $pdo->query('SELECT SUM(estimated_cost_usd) as est, SUM(actual_cost_usd) as act FROM agentos_certifications');
    $costs = $stmt->fetch();

    $stmt = $pdo->query("SELECT market, COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved FROM agentos_certifications GROUP BY market");
    $byMarket = $stmt->fetchAll();

    $readiness = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
    $criticalReadiness = $critical > 0 ? round(($criticalApproved / $critical) * 100, 1) : 0;

    agentos_respond(['ok' => true, 'readiness' => [
        'overall_percent' => $readiness,
        'critical_percent' => $criticalReadiness,
        'total_certifications' => $total,
        'approved' => $approved,
        'critical_total' => $critical,
        'critical_approved' => $criticalApproved,
        'estimated_total_cost_usd' => (int)($costs['est'] ?? 0),
        'actual_total_cost_usd' => (int)($costs['act'] ?? 0),
        'by_market' => $byMarket,
        'can_sell_robots' => $criticalReadiness >= 100,
        'recommendation' => $criticalReadiness >= 100
            ? 'All critical certifications approved — clear to sell.'
            : 'Critical certifications pending — DO NOT ship until all critical certs are approved.'
    ]]);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'certifications'       => 'handleCertifications',
    'certification_detail' => 'handleCertificationDetail',
    'create_cert'          => 'handleCreateCert',
    'update_cert'          => 'handleUpdateCert',
    'markets'              => 'handleMarkets',
    'add_market'           => 'handleAddMarket',
    'test_labs'            => 'handleTestLabs',
    'add_test_lab'         => 'handleAddTestLab',
    'submit_test'          => 'handleSubmitTest',
    'test_status'          => 'handleTestStatus',
    'compliance_matrix'    => 'handleComplianceMatrix',
    'expiring_certs'       => 'handleExpiringCerts',
    'documents'            => 'handleDocuments',
    'audit_readiness'      => 'handleAuditReadiness',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — Regulatory Certification Tracker', 'version' => AGENTOS_VERSION,
        'description' => 'Tracks CSA, FCC, CE, ISO, UL certifications for robot market readiness',
        'endpoints' => array_keys($routes)]);
}

$routes[$action]();
