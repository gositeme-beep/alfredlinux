<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
$db = cp_db();

function b_json(array $payload, int $status = 200): void
{
    cp_json_response($payload, $status);
}

function b_read_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function b_get_vault_secret(string $filename): string
{
    $path = '/home/gositeme/.vault/' . $filename;
    $val = @trim((string) @file_get_contents($path));
    return $val;
}

function b_verify_stripe_signature(string $rawBody): bool
{
    $secret = b_get_vault_secret('stripe-webhook-secret');
    if ($secret === '') {
        return false;
    }

    $header = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
    if ($header === '') {
        return false;
    }

    $parts = [];
    foreach (explode(',', $header) as $chunk) {
        $kv = explode('=', trim($chunk), 2);
        if (count($kv) === 2) {
            $parts[$kv[0]] = $kv[1];
        }
    }

    $ts = isset($parts['t']) ? (int) $parts['t'] : 0;
    $v1 = (string) ($parts['v1'] ?? '');
    if ($ts <= 0 || $v1 === '') {
        return false;
    }

    $age = abs(time() - $ts);
    if ($age > 300) {
        return false;
    }

    $signedPayload = $ts . '.' . $rawBody;
    $expected = hash_hmac('sha256', $signedPayload, $secret);
    return hash_equals($expected, $v1);
}

function b_verify_manual_webhook_key(): bool
{
    $expected = b_get_vault_secret('control-api-key');
    $provided = (string) ($_SERVER['HTTP_X_CONTROL_KEY'] ?? '');
    return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
}

function b_verify_gateway_request(string $gateway, string $rawBody): bool
{
    $g = strtolower(trim($gateway));
    return match ($g) {
        'stripe' => b_verify_stripe_signature($rawBody),
        'manual' => b_verify_manual_webhook_key(),
        default => false,
    };
}

function b_event(PDO $db, string $entityType, int $entityId, string $eventType, string $message, array $payload = []): void
{
    $stmt = $db->prepare('INSERT INTO billing_events (event_type, actor_type, actor_id, entity_type, entity_id, amount, currency, description, metadata, ip_address, created_at) VALUES (?, "api", NULL, ?, ?, NULL, "USD", ?, ?, ?, NOW(3))');
    $stmt->execute([
        $eventType,
        $entityType,
        $entityId,
        $message,
        json_encode($payload, JSON_UNESCAPED_SLASHES),
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function b_next_number(string $prefix): string
{
    return $prefix . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
}

function b_add_days_for_cycle(?string $cycle): int
{
    $c = strtolower(trim((string) $cycle));
    return match ($c) {
        'monthly' => 30,
        'quarterly' => 90,
        'semi-annually', 'semiannually' => 182,
        'annually', 'yearly' => 365,
        default => 30,
    };
}

function b_enqueue_control_job(PDO $db, string $action, array $payload, string $requestedBy, string $idempotencyKey): int
{
    $check = $db->prepare('SELECT id FROM control_jobs WHERE action = ? AND idempotency_key = ? ORDER BY id DESC LIMIT 1');
    $check->execute([$action, $idempotencyKey]);
    $existing = $check->fetch();
    if ($existing) {
        return (int) $existing['id'];
    }

    $insert = $db->prepare('INSERT INTO control_jobs (action, payload_json, idempotency_key, status, requested_by, created_at, updated_at) VALUES (?, ?, ?, "pending", ?, NOW(), NOW())');
    $insert->execute([$action, json_encode($payload, JSON_UNESCAPED_SLASHES), $idempotencyKey, $requestedBy]);
    $jobId = (int) $db->lastInsertId();

    cp_insert_event($db, $jobId, 'info', 'Job created by billing API', ['action' => $action, 'idempotency_key' => $idempotencyKey]);
    return $jobId;
}

function b_process_paid_order(PDO $db, int $orderId): array
{
    $orderStmt = $db->prepare('SELECT o.id, o.client_id, o.status FROM orders o WHERE o.id = ? LIMIT 1');
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch();
    if (!$order) {
        return ['ok' => false, 'error' => 'Order not found'];
    }

    $itemsStmt = $db->prepare('SELECT oi.id, oi.product_id, oi.domain, oi.billing_cycle, oi.amount, oi.status, oi.service_id, p.name AS product_name, p.slug, p.server_module, p.auto_setup FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id ASC');
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();

    $jobs = [];
    $jobServiceMap = [];
    foreach ($items as $item) {
        $itemId = (int) $item['id'];
        $serviceId = (int) ($item['service_id'] ?? 0);
        $domain = trim((string) ($item['domain'] ?? ''));
        $module = strtolower(trim((string) ($item['server_module'] ?? '')));
        $autoSetup = strtolower(trim((string) ($item['auto_setup'] ?? 'on_payment')));

        if ($serviceId <= 0) {
            $serviceInsert = $db->prepare('INSERT INTO services (client_id, order_id, product_id, domain, status, billing_cycle, amount, next_due_date, registration_date, created_at, updated_at) VALUES (?, ?, ?, ?, "Pending", ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), CURDATE(), NOW(), NOW())');
            $days = b_add_days_for_cycle((string) ($item['billing_cycle'] ?? 'monthly'));
            $serviceInsert->execute([
                (int) $order['client_id'],
                $orderId,
                (int) $item['product_id'],
                $domain !== '' ? $domain : null,
                (string) ($item['billing_cycle'] ?? 'monthly'),
                (float) $item['amount'],
                $days,
            ]);
            $serviceId = (int) $db->lastInsertId();

            $u = 'u' . (int) $order['client_id'];
            $db->prepare('UPDATE services SET username = COALESCE(username, ?) WHERE id = ?')->execute([$u, $serviceId]);
            $db->prepare('UPDATE order_items SET service_id = ? WHERE id = ?')->execute([$serviceId, $itemId]);
            b_event($db, 'service', $serviceId, 'service.created', 'Service created from paid order item', ['order_item_id' => $itemId]);
        }

        if (in_array($module, ['gohostme', 'directadmin'], true) && in_array($autoSetup, ['on_payment', 'on_order'], true) && $domain !== '') {
            $usernameStmt = $db->prepare('SELECT COALESCE(NULLIF(c.da_username, ""), CONCAT("u", c.id)) AS provision_username FROM clients c WHERE c.id = ? LIMIT 1');
            $usernameStmt->execute([(int) $order['client_id']]);
            $usernameRow = $usernameStmt->fetch();
            $username = trim((string) ($usernameRow['provision_username'] ?? ('u' . (int) $order['client_id'])));
            $db->prepare('UPDATE services SET username = ? WHERE id = ?')->execute([$username, $serviceId]);

            $payload = [
                'username' => $username,
                'domain' => $domain,
                'package' => trim((string) ($item['slug'] ?? 'default')),
            ];
            $idem = 'svc:' . $serviceId . ':account-create';
            $jobId = b_enqueue_control_job($db, 'account-create', $payload, 'billing-paid-order', $idem);
            $jobs[] = $jobId;
            $jobServiceMap[$jobId] = $serviceId;

            $db->prepare('UPDATE order_items SET status = "Active" WHERE id = ?')->execute([$itemId]);
            b_event($db, 'service', $serviceId, 'provision.enqueued', 'Provision job queued', ['job_id' => $jobId]);
        } else {
            $db->prepare('UPDATE order_items SET status = "Active" WHERE id = ?')->execute([$itemId]);
            $db->prepare('UPDATE services SET status = "Active" WHERE id = ?')->execute([$serviceId]);
        }
    }

    if (!empty($jobs)) {
        @exec('/usr/bin/php /home/gositeme/gohostme/control-plane/worker.php >/dev/null 2>&1');
        foreach ($jobs as $jid) {
            $statusStmt = $db->prepare('SELECT status FROM control_jobs WHERE id = ? LIMIT 1');
            $statusStmt->execute([$jid]);
            $j = $statusStmt->fetch();
            if (($j['status'] ?? '') === 'completed') {
                if (isset($jobServiceMap[$jid])) {
                    $db->prepare('UPDATE services SET status = "Active", updated_at = NOW() WHERE id = ?')->execute([$jobServiceMap[$jid]]);
                    b_event($db, 'service', (int) $jobServiceMap[$jid], 'provision.completed', 'Provision job completed', ['job_id' => $jid]);
                }
                continue;
            }
            if (isset($jobServiceMap[$jid])) {
                $db->prepare('UPDATE services SET status = "Pending", notes = CONCAT(COALESCE(notes, ""), "\nProvision job pending/failed: ", ?) WHERE id = ?')->execute([$jid, $jobServiceMap[$jid]]);
            }
        }
    }

    $db->prepare('UPDATE orders SET status = "Active", updated_at = NOW() WHERE id = ?')->execute([$orderId]);
    b_event($db, 'order', $orderId, 'order.activated', 'Order marked active after payment', ['jobs' => $jobs]);

    return ['ok' => true, 'jobs' => $jobs];
}

function b_generate_renewal_invoices(PDO $db, ?int $limit = 100): array
{
    $limit = max(1, min(500, (int) $limit));
    $servicesStmt = $db->query('SELECT s.id, s.client_id, s.product_id, s.amount, s.billing_cycle, s.next_due_date, p.name AS product_name FROM services s JOIN products p ON p.id = s.product_id WHERE s.status = "Active" AND s.next_due_date IS NOT NULL AND s.next_due_date <= CURDATE() ORDER BY s.next_due_date ASC LIMIT ' . $limit);
    $services = $servicesStmt->fetchAll();

    $created = [];
    foreach ($services as $svc) {
        $serviceId = (int) $svc['id'];
        $clientId = (int) $svc['client_id'];
        $amount = (float) $svc['amount'];
        $due = (string) $svc['next_due_date'];

        $dupStmt = $db->prepare('SELECT id FROM invoices WHERE service_id = ? AND status IN ("Unpaid","Overdue") ORDER BY id DESC LIMIT 1');
        $dupStmt->execute([$serviceId]);
        if ($dupStmt->fetch()) {
            continue;
        }

        $invoiceNumber = b_next_number('INV-REN-');
        $desc = 'Renewal for service #' . $serviceId . ' (' . (string) ($svc['product_name'] ?? 'Hosting') . ')';

        $invIns = $db->prepare('INSERT INTO invoices (client_id, service_id, invoice_number, status, subtotal, tax, tax_rate, total, amount_paid, balance, currency_id, payment_method, due_date, notes, created_at, updated_at) VALUES (?, ?, ?, "Unpaid", ?, 0.00, 0.00, ?, 0.00, ?, 1, "manual", CURDATE(), ?, NOW(), NOW())');
        $invIns->execute([$clientId, $serviceId, $invoiceNumber, $amount, $amount, $amount, $desc]);
        $invoiceId = (int) $db->lastInsertId();

        b_event($db, 'invoice', $invoiceId, 'invoice.renewal_created', 'Renewal invoice generated', ['service_id' => $serviceId, 'previous_due_date' => $due]);
        $created[] = ['invoice_id' => $invoiceId, 'service_id' => $serviceId];
    }

    return ['count' => count($created), 'invoices' => $created];
}

function b_get_cutover_mode(PDO $db): string
{
    $stmt = $db->query("SELECT value FROM control_settings WHERE `key` = 'whmcs_cutover_mode' LIMIT 1");
    $row = $stmt->fetch();
    $mode = strtolower(trim((string) ($row['value'] ?? 'hybrid')));
    return in_array($mode, ['legacy', 'hybrid', 'native'], true) ? $mode : 'hybrid';
}

function b_set_cutover_mode(PDO $db, string $mode): void
{
    $m = strtolower(trim($mode));
    if (!in_array($m, ['legacy', 'hybrid', 'native'], true)) {
        throw new RuntimeException('Invalid cutover mode');
    }

    $stmt = $db->prepare("INSERT INTO control_settings (`key`, value, updated_at) VALUES ('whmcs_cutover_mode', ?, NOW()) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()");
    $stmt->execute([$m]);
}

function b_client_summary(PDO $db, int $clientId): array
{
    $summary = [
        'active_services' => 0,
        'unpaid_invoices' => 0,
        'unpaid_total' => 0.0,
        'active_orders' => 0,
    ];

    $s1 = $db->prepare("SELECT COUNT(*) FROM services WHERE client_id = ? AND status = 'Active'");
    $s1->execute([$clientId]);
    $summary['active_services'] = (int) $s1->fetchColumn();

    $s2 = $db->prepare("SELECT COUNT(*), COALESCE(SUM(balance),0) FROM invoices WHERE client_id = ? AND status IN ('Unpaid','Overdue')");
    $s2->execute([$clientId]);
    $r2 = $s2->fetch(PDO::FETCH_NUM);
    $summary['unpaid_invoices'] = (int) ($r2[0] ?? 0);
    $summary['unpaid_total'] = (float) ($r2[1] ?? 0);

    $s3 = $db->prepare("SELECT COUNT(*) FROM orders WHERE client_id = ? AND status IN ('Pending','Active')");
    $s3->execute([$clientId]);
    $summary['active_orders'] = (int) $s3->fetchColumn();

    return $summary;
}

function b_client_invoices(PDO $db, int $clientId, int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $stmt = $db->prepare("SELECT id, invoice_number, status, total, amount_paid, balance, payment_method, due_date, paid_date, created_at FROM invoices WHERE client_id = ? ORDER BY id DESC LIMIT {$limit}");
    $stmt->execute([$clientId]);
    return $stmt->fetchAll();
}

function b_client_services(PDO $db, int $clientId, int $limit = 50): array
{
    $limit = max(1, min(200, $limit));
    $stmt = $db->prepare("SELECT s.id, s.product_id, p.name AS product_name, p.slug, s.domain, s.username, s.status, s.billing_cycle, s.amount, s.next_due_date, s.registration_date, s.created_at FROM services s JOIN products p ON p.id = s.product_id WHERE s.client_id = ? ORDER BY s.id DESC LIMIT {$limit}");
    $stmt->execute([$clientId]);
    return $stmt->fetchAll();
}

function b_reconcile_services(PDO $db, int $limit = 200): array
{
    $limit = max(1, min(2000, $limit));
    $q = $db->query("SELECT id, JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.domain')) AS domain, JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.username')) AS username FROM control_jobs WHERE action = 'account-create' AND status = 'completed' ORDER BY id DESC LIMIT {$limit}");
    $jobs = $q->fetchAll();

    $updated = [];
    $updateStmt = $db->prepare("UPDATE services SET status = 'Active', username = COALESCE(NULLIF(username,''), ?), updated_at = NOW() WHERE status = 'Pending' AND domain = ?");
    $findStmt = $db->prepare("SELECT id FROM services WHERE domain = ? ORDER BY id DESC LIMIT 1");

    foreach ($jobs as $job) {
        $domain = trim((string) ($job['domain'] ?? ''));
        $username = trim((string) ($job['username'] ?? ''));
        if ($domain === '') {
            continue;
        }

        $updateStmt->execute([$username, $domain]);
        if ($updateStmt->rowCount() > 0) {
            $findStmt->execute([$domain]);
            $svc = $findStmt->fetch();
            $sid = (int) ($svc['id'] ?? 0);
            if ($sid > 0) {
                b_event($db, 'service', $sid, 'service.reconciled', 'Service status reconciled from completed provisioning job', ['job_id' => (int) $job['id']]);
                $updated[] = ['service_id' => $sid, 'domain' => $domain, 'job_id' => (int) $job['id']];
            }
        }
    }

    return ['updated' => count($updated), 'services' => $updated];
}

function b_ratio_score(int $native, int $legacy): float
{
    if ($legacy <= 0) {
        return $native > 0 ? 1.0 : 1.0;
    }
    return max(0.0, min(1.0, $native / $legacy));
}

function b_is_external_offsite_detail(?string $method, ?string $detail): bool
{
    $m = strtolower(trim((string) $method));
    if ($m === 'ftp') {
        return true;
    }

    $raw = trim((string) $detail);
    if ($raw === '') {
        return false;
    }

    $host = $raw;
    if (str_contains($host, '@')) {
        $host = (string) substr($host, strrpos($host, '@') + 1);
    }
    if (str_contains($host, ':')) {
        $host = (string) strstr($host, ':', true);
    }
    $host = strtolower(trim($host));
    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
        return false;
    }

    $selfHost = strtolower((string) gethostname());
    if ($host === $selfHost) {
        return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        if (preg_match('/^(10\.|127\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/', $host)) {
            return false;
        }
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        if ($host === '::1' || str_starts_with($host, 'fc') || str_starts_with($host, 'fd') || str_starts_with($host, 'fe80:')) {
            return false;
        }
    }

    $selfIpsRaw = @shell_exec('hostname -I 2>/dev/null');
    if (is_string($selfIpsRaw) && $selfIpsRaw !== '') {
        $selfIps = preg_split('/\s+/', trim($selfIpsRaw)) ?: [];
        if (in_array($host, $selfIps, true)) {
            return false;
        }
    }

    return true;
}

function b_migration_confidence(PDO $db): array
{
    $nativeServices = (int) $db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    $nativeInvoices = (int) $db->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
    $nativeOrders = (int) $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();

    $legacyServices = (int) $db->query('SELECT COUNT(*) FROM tblhosting')->fetchColumn();
    $legacyInvoices = (int) $db->query('SELECT COUNT(*) FROM tblinvoices')->fetchColumn();
    $legacyOrders = (int) $db->query('SELECT COUNT(*) FROM tblorders')->fetchColumn();

    $svcRatio = b_ratio_score($nativeServices, $legacyServices);
    $invRatio = b_ratio_score($nativeInvoices, $legacyInvoices);
    $ordRatio = b_ratio_score($nativeOrders, $legacyOrders);
    $parityScore = (($svcRatio + $invRatio + $ordRatio) / 3.0) * 50.0;

    $backupDir = '/home/gositeme/backups/native-platform';
    $datedDirs = glob($backupDir . '/20*', GLOB_ONLYDIR) ?: [];
    sort($datedDirs);
    $latestBackup = count($datedDirs) > 0 ? end($datedDirs) : '';
    $latestBackupTs = $latestBackup !== '' ? @filemtime($latestBackup) : 0;
    $backupFresh = $latestBackupTs > 0 && (time() - $latestBackupTs) <= (36 * 3600);

    $drillLog = '/home/gositeme/gohostme/control-plane/ops/restore-drill-native.log';
    $drillFresh = false;
    $drillOkLine = '';
    if (is_file($drillLog)) {
        $lines = @file($drillLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (strpos($lines[$i], 'RESTORE_DRILL_OK:') !== false) {
                $drillOkLine = $lines[$i];
                if (preg_match('/^\[(.*?)\]/', $drillOkLine, $m)) {
                    $ts = strtotime($m[1]);
                    $drillFresh = $ts !== false && (time() - $ts) <= (8 * 24 * 3600);
                }
                break;
            }
        }
    }

    $offsiteAlert = '/home/gositeme/gohostme/control-plane/ops/OFFSITE_ALERT.txt';
    $offsiteHealthy = !is_file($offsiteAlert);
    $offsiteSuccessFile = '/home/gositeme/gohostme/control-plane/ops/OFFSITE_LAST_SUCCESS.txt';
    $offsiteFresh = false;
    $offsiteMethod = null;
    $offsiteLastSuccess = null;
    $offsiteDetail = null;
    $offsiteExternal = false;
    if (is_file($offsiteSuccessFile)) {
        $raw = trim((string) @file_get_contents($offsiteSuccessFile));
        if ($raw !== '') {
            $parts = preg_split('/\s+/', $raw) ?: [];
            foreach ($parts as $part) {
                if (str_starts_with($part, 'method=')) {
                    $offsiteMethod = substr($part, 7);
                }
                if (str_starts_with($part, 'ts=')) {
                    $offsiteLastSuccess = substr($part, 3);
                }
            }
            if (preg_match('/\bdetail=(.*)$/', $raw, $m)) {
                $offsiteDetail = trim((string) $m[1]);
            }
            if ($offsiteLastSuccess !== null) {
                $ts = strtotime($offsiteLastSuccess);
                $offsiteFresh = $ts !== false && (time() - $ts) <= (36 * 3600);
            }
            $offsiteExternal = b_is_external_offsite_detail($offsiteMethod, $offsiteDetail);
        }
    }

    $resilienceScore = 0.0;
    $resilienceScore += $backupFresh ? 10.0 : 0.0;
    $resilienceScore += $drillFresh ? 10.0 : 0.0;
    $resilienceScore += $offsiteHealthy ? 10.0 : 0.0;
    $resilienceScore += ($offsiteFresh && $offsiteExternal) ? 5.0 : 0.0;

    $mode = b_get_cutover_mode($db);
    $modeScore = match ($mode) {
        'native' => 20.0,
        'hybrid' => 10.0,
        default => 0.0,
    };

    $score = (int) round($parityScore + $resilienceScore + $modeScore);
    $score = max(0, min(100, $score));
    $level = max(9, min(19, 9 + (int) floor($score / 10)));

    $blockers = [];
    if (!$backupFresh) {
        $blockers[] = 'backup_not_fresh';
    }
    if (!$drillFresh) {
        $blockers[] = 'restore_drill_not_fresh';
    }
    if (!$offsiteHealthy) {
        $blockers[] = 'offsite_alert_active';
    }
    if (!$offsiteFresh) {
        $blockers[] = 'offsite_not_fresh';
    }
    if (!$offsiteExternal) {
        $blockers[] = 'offsite_not_external';
    }

    $readiness = 'ready';
    if ($score < 85 || !empty($blockers)) {
        $readiness = 'caution';
    }
    if ($score < 70 || in_array('offsite_alert_active', $blockers, true) || in_array('offsite_not_external', $blockers, true)) {
        $readiness = 'blocked';
    }

    return [
        'score' => $score,
        'level' => $level,
        'readiness' => $readiness,
        'cutover_mode' => $mode,
        'native' => [
            'services' => $nativeServices,
            'invoices' => $nativeInvoices,
            'orders' => $nativeOrders,
        ],
        'legacy' => [
            'tblhosting' => $legacyServices,
            'tblinvoices' => $legacyInvoices,
            'tblorders' => $legacyOrders,
        ],
        'ratios' => [
            'services' => round($svcRatio, 4),
            'invoices' => round($invRatio, 4),
            'orders' => round($ordRatio, 4),
        ],
        'resilience' => [
            'backup_fresh' => $backupFresh,
            'latest_backup_dir' => $latestBackup !== '' ? basename($latestBackup) : null,
            'restore_drill_fresh' => $drillFresh,
            'last_drill_ok_line' => $drillOkLine !== '' ? $drillOkLine : null,
            'offsite_healthy' => $offsiteHealthy,
            'offsite_fresh' => $offsiteFresh,
            'offsite_last_success' => $offsiteLastSuccess,
            'offsite_method' => $offsiteMethod,
            'offsite_detail' => $offsiteDetail,
            'offsite_external' => $offsiteExternal,
        ],
        'blockers' => $blockers,
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$rawBody = $method === 'POST' ? (string) file_get_contents('php://input') : '';
$data = [];
if ($method === 'POST') {
    $decoded = json_decode($rawBody !== '' ? $rawBody : '{}', true);
    $data = is_array($decoded) ? $decoded : [];
}
if ($action === '' && isset($data['action']) && is_string($data['action'])) {
    $action = $data['action'];
}

if ($action !== 'payment_webhook') {
    cp_require_api_key();
}

if ($method !== 'POST') {
    b_json(['ok' => false, 'error' => 'POST required'], 405);
}

if ($action === 'create_order') {
    $clientId = (int) ($data['client_id'] ?? 0);
    $paymentMethod = trim((string) ($data['payment_method'] ?? 'manual'));
    $items = $data['items'] ?? [];
    if ($clientId <= 0 || !is_array($items) || count($items) === 0) {
        b_json(['ok' => false, 'error' => 'client_id and items are required'], 400);
    }

    $clientCheck = $db->prepare('SELECT id FROM clients WHERE id = ? LIMIT 1');
    $clientCheck->execute([$clientId]);
    if (!$clientCheck->fetch()) {
        b_json(['ok' => false, 'error' => 'Client not found'], 404);
    }

    $db->beginTransaction();
    try {
        $orderNumber = b_next_number('ORD-');
        $subtotal = 0.0;
        foreach ($items as $it) {
            $subtotal += (float) ($it['amount'] ?? 0);
        }

        $orderIns = $db->prepare('INSERT INTO orders (client_id, order_number, status, payment_method, subtotal, tax, total, currency_id, ip_address, created_at, updated_at) VALUES (?, ?, "Pending", ?, ?, 0.00, ?, 1, ?, NOW(), NOW())');
        $orderIns->execute([$clientId, $orderNumber, $paymentMethod, $subtotal, $subtotal, $_SERVER['REMOTE_ADDR'] ?? null]);
        $orderId = (int) $db->lastInsertId();

        $itemIns = $db->prepare('INSERT INTO order_items (order_id, product_id, domain, billing_cycle, amount, setup_fee, config_options, status) VALUES (?, ?, ?, ?, ?, 0.00, ?, "Pending")');
        foreach ($items as $it) {
            $productId = (int) ($it['product_id'] ?? 0);
            if ($productId <= 0) {
                throw new RuntimeException('Invalid product_id in items');
            }
            $domain = trim((string) ($it['domain'] ?? ''));
            $cycle = trim((string) ($it['billing_cycle'] ?? 'monthly'));
            $amount = (float) ($it['amount'] ?? 0);
            $cfg = $it['config_options'] ?? [];
            if (!is_array($cfg)) {
                $cfg = [];
            }

            $itemIns->execute([$orderId, $productId, $domain !== '' ? $domain : null, $cycle, $amount, json_encode($cfg, JSON_UNESCAPED_SLASHES)]);
        }

        $invoiceNumber = b_next_number('INV-');
        $invIns = $db->prepare('INSERT INTO invoices (client_id, invoice_number, status, subtotal, tax, tax_rate, total, amount_paid, balance, currency_id, payment_method, due_date, created_at, updated_at) VALUES (?, ?, "Unpaid", ?, 0.00, 0.00, ?, 0.00, ?, 1, ?, CURDATE(), NOW(), NOW())');
        $invIns->execute([$clientId, $invoiceNumber, $subtotal, $subtotal, $subtotal, $paymentMethod]);
        $invoiceId = (int) $db->lastInsertId();

        $db->prepare('UPDATE orders SET invoice_id = ? WHERE id = ?')->execute([$invoiceId, $orderId]);

        $txIns = $db->prepare('INSERT INTO payment_transactions (invoice_id, client_id, gateway, transaction_id, amount, currency, status, description, gateway_response, created_at) VALUES (?, ?, ?, NULL, ?, "USD", "pending", ?, NULL, NOW())');
        $txIns->execute([$invoiceId, $clientId, $paymentMethod, $subtotal, 'Order ' . $orderNumber . ' pending payment']);

        b_event($db, 'order', $orderId, 'order.created', 'Order created', ['invoice_id' => $invoiceId]);
        b_event($db, 'invoice', $invoiceId, 'invoice.created', 'Invoice created', ['order_id' => $orderId]);

        $db->commit();
        b_json([
            'ok' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'total' => $subtotal,
        ], 201);
    } catch (Throwable $e) {
        $db->rollBack();
        b_json(['ok' => false, 'error' => 'create_order failed: ' . $e->getMessage()], 500);
    }
}

if ($action === 'payment_webhook') {
    $gateway = trim((string) ($data['gateway'] ?? 'manual'));
    $eventId = trim((string) ($data['event_id'] ?? ''));
    $invoiceId = (int) ($data['invoice_id'] ?? 0);
    $transactionId = trim((string) ($data['transaction_id'] ?? ''));
    $amount = (float) ($data['amount'] ?? 0);
    $status = strtolower(trim((string) ($data['status'] ?? 'completed')));

    if (!b_verify_gateway_request($gateway, $rawBody)) {
        b_json(['ok' => false, 'error' => 'Webhook signature verification failed'], 401);
    }

    if ($invoiceId <= 0 || $transactionId === '' || $amount <= 0) {
        b_json(['ok' => false, 'error' => 'invoice_id, transaction_id, amount are required'], 400);
    }

    if ($eventId !== '') {
        try {
            $evtIns = $db->prepare('INSERT INTO gateway_webhook_events (gateway, event_id, payload_json, received_at) VALUES (?, ?, ?, NOW())');
            $evtIns->execute([$gateway, $eventId, json_encode($data, JSON_UNESCAPED_SLASHES)]);
        } catch (Throwable $e) {
            b_json(['ok' => true, 'deduplicated' => true, 'message' => 'Event already processed']);
        }
    }

    $db->beginTransaction();
    try {
        $invStmt = $db->prepare('SELECT id, client_id, total, amount_paid, balance, status FROM invoices WHERE id = ? FOR UPDATE');
        $invStmt->execute([$invoiceId]);
        $invoice = $invStmt->fetch();
        if (!$invoice) {
            throw new RuntimeException('Invoice not found');
        }

        $txIns = $db->prepare('INSERT INTO payment_transactions (invoice_id, client_id, gateway, transaction_id, amount, currency, status, description, gateway_response, created_at) VALUES (?, ?, ?, ?, ?, "USD", ?, ?, ?, NOW())');
        $txIns->execute([
            $invoiceId,
            (int) $invoice['client_id'],
            $gateway,
            $transactionId,
            $amount,
            in_array($status, ['completed', 'failed', 'refunded'], true) ? $status : 'completed',
            'Webhook payment for invoice ' . $invoiceId,
            json_encode($data, JSON_UNESCAPED_SLASHES),
        ]);
        $paymentId = (int) $db->lastInsertId();

        $newPaid = (float) $invoice['amount_paid'];
        if ($status === 'completed') {
            $newPaid += $amount;
        }

        $total = (float) $invoice['total'];
        $newBalance = max(0, $total - $newPaid);
        $newStatus = $newBalance <= 0.00001 ? 'Paid' : 'Unpaid';

        $updInv = $db->prepare('UPDATE invoices SET amount_paid = ?, balance = ?, status = ?, paid_date = IF(? = "Paid" AND paid_date IS NULL, NOW(), paid_date), updated_at = NOW() WHERE id = ?');
        $updInv->execute([$newPaid, $newBalance, $newStatus, $newStatus, $invoiceId]);

        b_event($db, 'payment', $paymentId, 'payment.recorded', 'Payment transaction recorded', ['invoice_id' => $invoiceId]);
        b_event($db, 'invoice', $invoiceId, 'invoice.updated', 'Invoice payment state updated', ['status' => $newStatus, 'balance' => $newBalance]);

        $orderId = 0;
        if ($newStatus === 'Paid') {
            $ordStmt = $db->prepare('SELECT id FROM orders WHERE invoice_id = ? ORDER BY id DESC LIMIT 1');
            $ordStmt->execute([$invoiceId]);
            $ord = $ordStmt->fetch();
            if ($ord) {
                $orderId = (int) $ord['id'];
            }
        }

        $db->commit();

        $provision = ['ok' => true];
        if ($newStatus === 'Paid' && $orderId > 0) {
            $provision = b_process_paid_order($db, $orderId);
        }

        b_json([
            'ok' => true,
            'invoice_id' => $invoiceId,
            'invoice_status' => $newStatus,
            'balance' => $newBalance,
            'order_id' => $orderId > 0 ? $orderId : null,
            'provision' => $provision,
        ]);
    } catch (Throwable $e) {
        $db->rollBack();
        b_json(['ok' => false, 'error' => 'payment_webhook failed: ' . $e->getMessage()], 500);
    }
}

if ($action === 'generate_renewals') {
    $limit = isset($data['limit']) ? (int) $data['limit'] : 100;
    $result = b_generate_renewal_invoices($db, $limit);
    b_json([
        'ok' => true,
        'generated' => $result['count'],
        'invoices' => $result['invoices'],
    ]);
}

if ($action === 'get_cutover_mode') {
    b_json([
        'ok' => true,
        'mode' => b_get_cutover_mode($db),
    ]);
}

if ($action === 'set_cutover_mode') {
    $mode = trim((string) ($data['mode'] ?? ''));
    try {
        b_set_cutover_mode($db, $mode);
        b_event($db, 'webhook', 0, 'cutover.mode_updated', 'WHMCS cutover mode changed', ['mode' => strtolower($mode)]);
        b_json([
            'ok' => true,
            'mode' => b_get_cutover_mode($db),
        ]);
    } catch (Throwable $e) {
        b_json(['ok' => false, 'error' => $e->getMessage()], 400);
    }
}

if ($action === 'client_dashboard') {
    $clientId = (int) ($data['client_id'] ?? 0);
    if ($clientId <= 0) {
        b_json(['ok' => false, 'error' => 'client_id required'], 400);
    }

    b_json([
        'ok' => true,
        'client_id' => $clientId,
        'summary' => b_client_summary($db, $clientId),
        'services' => b_client_services($db, $clientId, (int) ($data['services_limit'] ?? 25)),
        'invoices' => b_client_invoices($db, $clientId, (int) ($data['invoices_limit'] ?? 25)),
        'cutover_mode' => b_get_cutover_mode($db),
    ]);
}

if ($action === 'reconcile_services') {
    $limit = (int) ($data['limit'] ?? 200);
    $res = b_reconcile_services($db, $limit);
    b_json([
        'ok' => true,
        'updated' => $res['updated'],
        'services' => $res['services'],
    ]);
}

if ($action === 'migration_confidence') {
    b_json([
        'ok' => true,
        'confidence' => b_migration_confidence($db),
    ]);
}

if ($action === 'order_status') {
    $orderId = (int) ($data['order_id'] ?? ($_GET['order_id'] ?? 0));
    if ($orderId <= 0) {
        b_json(['ok' => false, 'error' => 'order_id required'], 400);
    }

    $orderStmt = $db->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch();
    if (!$order) {
        b_json(['ok' => false, 'error' => 'Order not found'], 404);
    }

    $itemsStmt = $db->prepare('SELECT oi.*, s.status AS service_status FROM order_items oi LEFT JOIN services s ON s.id = oi.service_id WHERE oi.order_id = ? ORDER BY oi.id ASC');
    $itemsStmt->execute([$orderId]);

    b_json([
        'ok' => true,
        'order' => $order,
        'items' => $itemsStmt->fetchAll(),
    ]);
}

b_json(['ok' => false, 'error' => 'Unknown action'], 404);
