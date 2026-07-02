<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$db = cp_db();

function cp_fail_job(PDO $db, int $jobId, string $message): void
{
    $stmt = $db->prepare('UPDATE control_jobs SET status = "failed", error_message = ?, finished_at = NOW(), updated_at = NOW() WHERE id = ?');
    $stmt->execute([$message, $jobId]);
    cp_insert_event($db, $jobId, 'error', $message);
}

function cp_complete_job(PDO $db, int $jobId, array $result): void
{
    $stmt = $db->prepare('UPDATE control_jobs SET status = "completed", result_json = ?, finished_at = NOW(), updated_at = NOW() WHERE id = ?');
    $stmt->execute([json_encode($result, JSON_UNESCAPED_SLASHES), $jobId]);
    cp_insert_event($db, $jobId, 'info', 'Job completed', $result);
}

$db->beginTransaction();
$jobStmt = $db->query('SELECT id, action, payload_json FROM control_jobs WHERE status = "pending" ORDER BY id ASC LIMIT 1 FOR UPDATE');
$job = $jobStmt->fetch();

if (!$job) {
    $db->commit();
    echo "NOOP\n";
    exit(0);
}

$jobId = (int) $job['id'];
$action = (string) $job['action'];
$payload = json_decode((string) ($job['payload_json'] ?? '{}'), true);
if (!is_array($payload)) {
    $payload = [];
}

$markStmt = $db->prepare('UPDATE control_jobs SET status = "running", started_at = NOW(), updated_at = NOW() WHERE id = ?');
$markStmt->execute([$jobId]);
cp_insert_event($db, $jobId, 'info', 'Job started', ['action' => $action]);
$db->commit();

$required = [
    'account-create' => ['username', 'domain'],
    'account-suspend' => ['username'],
    'account-unsuspend' => ['username'],
    'account-terminate' => ['username'],
    'account-change-password' => ['username', 'password'],
    'account-change-package' => ['username', 'package'],
];

if (!isset($required[$action])) {
    cp_fail_job($db, $jobId, 'Unsupported action: ' . $action);
    exit(1);
}

$args = [];
foreach ($required[$action] as $key) {
    $value = isset($payload[$key]) ? trim((string) $payload[$key]) : '';
    if ($value === '') {
        cp_fail_job($db, $jobId, 'Missing required payload field: ' . $key);
        exit(1);
    }
    $args[] = $value;
}

cp_insert_event($db, $jobId, 'info', 'Dispatching bridge command', ['command' => $action]);
$res = cp_bridge_exec($action, $args);

if (!$res['ok']) {
    cp_fail_job($db, $jobId, 'Bridge command failed: ' . $res['output']);
    exit(1);
}

cp_complete_job($db, $jobId, [
    'command' => $action,
    'args' => $args,
    'bridge_output' => $res['output'],
]);

echo "DONE:$jobId\n";
