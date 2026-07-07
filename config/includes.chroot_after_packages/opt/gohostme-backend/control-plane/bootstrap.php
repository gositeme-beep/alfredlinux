<?php

declare(strict_types=1);

function cp_env(string $name, string $default = ''): string
{
    $v = getenv($name);
    if ($v === false || $v === '') {
        return $default;
    }
    return $v;
}

function cp_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = cp_env('CP_DB_HOST', '127.0.0.1');
    $db = cp_env('CP_DB_NAME', 'gositeme_whmcs');
    $user = cp_env('CP_DB_USER', 'gositeme_whmcs');
    $pass = cp_env('CP_DB_PASS', '!q@w#e$r5t');
    $port = (int) cp_env('CP_DB_PORT', '3306');

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $db);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function cp_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function cp_require_api_key(): void
{
    $primaryKeyFile = '/home/gositeme/.vault/control-api-key';
    $agentKeyFile = '/home/gositeme/.vault/control-api-key-agent';

    $expectedPrimary = @trim((string) @file_get_contents($primaryKeyFile));
    $expectedAgent = @trim((string) @file_get_contents($agentKeyFile));

    $validKeys = [];
    if ($expectedPrimary !== '') {
        $validKeys[] = $expectedPrimary;
    }
    if ($expectedAgent !== '') {
        $validKeys[] = $expectedAgent;
    }

    if (count($validKeys) === 0) {
        cp_json_response(['ok' => false, 'error' => 'Control API key not configured'], 500);
    }

    $provided = $_SERVER['HTTP_X_CONTROL_KEY'] ?? '';
    if (!is_string($provided) || $provided === '') {
        cp_json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
    }

    foreach ($validKeys as $expected) {
        if (hash_equals($expected, $provided)) {
            return;
        }
    }

    cp_json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

function cp_validate_action(string $action): bool
{
    $allowed = [
        'account-create',
        'account-suspend',
        'account-unsuspend',
        'account-terminate',
        'account-change-password',
        'account-change-package',
    ];
    return in_array($action, $allowed, true);
}

function cp_insert_event(PDO $db, int $jobId, string $level, string $message, array $context = []): void
{
    $stmt = $db->prepare('INSERT INTO control_events (job_id, level, message, context_json, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$jobId, $level, $message, json_encode($context, JSON_UNESCAPED_SLASHES)]);
}

function cp_bridge_exec(string $command, array $args): array
{
    $parts = array_map(static fn(string $v): string => escapeshellarg($v), $args);
    $tokenCmd = 'sudo /opt/gohostme/bridge.sh token-generate ' . escapeshellarg($command);
    if (!empty($parts)) {
        $tokenCmd .= ' ' . implode(' ', $parts);
    }

    $tokenOut = [];
    $tokenExit = 0;
    exec($tokenCmd . ' 2>&1', $tokenOut, $tokenExit);
    $token = trim(implode("\n", $tokenOut));
    if ($tokenExit !== 0 || $token === '') {
        return [
            'ok' => false,
            'exit' => $tokenExit,
            'output' => implode("\n", $tokenOut),
        ];
    }

    $runCmd = 'sudo /opt/gohostme/bridge.sh ' . escapeshellarg($command);
    if (!empty($parts)) {
        $runCmd .= ' ' . implode(' ', $parts);
    }
    $runCmd .= ' ' . escapeshellarg('--token=' . $token);

    $runOut = [];
    $runExit = 0;
    exec($runCmd . ' 2>&1', $runOut, $runExit);

    return [
        'ok' => $runExit === 0,
        'exit' => $runExit,
        'output' => implode("\n", $runOut),
    ];
}
