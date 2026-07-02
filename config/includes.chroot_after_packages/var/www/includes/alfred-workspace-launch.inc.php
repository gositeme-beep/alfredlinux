<?php

function alfred_workspace_ensure_sso_table(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS sso_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(64) NOT NULL,
        client_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(128) DEFAULT '',
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        used TINYINT DEFAULT 0,
        INDEX(token), INDEX(client_id), INDEX(expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function alfred_workspace_client_has_access(PDO $db, int $clientId): bool
{
    if ($clientId <= 0) {
        return false;
    }

    $stmt = $db->prepare("SELECT COUNT(*)
        FROM services s
        INNER JOIN products p ON p.id = s.product_id
        WHERE s.client_id = ?
          AND s.status = 'Active'
          AND p.server_module = 'gocodeme'");
    $stmt->execute([$clientId]);
    return (int)$stmt->fetchColumn() > 0;
}

function alfred_workspace_issue_billing_token(PDO $db, int $clientId): array
{
    alfred_workspace_ensure_sso_table($db);

    $stmt = $db->prepare("SELECT email, firstname, lastname FROM clients WHERE id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$client) {
        return ['success' => false, 'error' => 'Client not found'];
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 300);
    $name = trim(((string)($client['firstname'] ?? '')) . ' ' . ((string)($client['lastname'] ?? '')));

    $db->prepare("INSERT INTO sso_tokens (token, client_id, email, name, expires_at, created_at, used)
        VALUES (?,?,?,?,?,NOW(),0)")->execute([
        hash('sha256', $token),
        $clientId,
        (string)($client['email'] ?? ''),
        $name,
        $expires,
    ]);

    return [
        'success' => true,
        'token' => $token,
        'expires' => $expires,
    ];
}

function alfred_workspace_middleware_json_request(string $path, array $payload, array $headers = [], int $timeoutSeconds = 20): array
{
    $ch = curl_init('http://127.0.0.1:3001' . $path);
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_HTTPHEADER => array_merge([
            'Accept: application/json',
            'Content-Type: application/json',
        ], $headers),
        CURLOPT_POSTFIELDS => $body,
    ]);

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'status' => $status,
            'error' => $error !== '' ? $error : 'Middleware request failed',
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'status' => $status,
            'error' => 'Middleware returned invalid JSON',
        ];
    }

    $decoded['status'] = $status;
    return $decoded;
}

function alfred_workspace_build_launch(PDO $db, int $clientId): array
{
    if (!alfred_workspace_client_has_access($db, $clientId)) {
        return ['success' => false, 'error' => 'Active Alfred IDE service required'];
    }

    $billingToken = alfred_workspace_issue_billing_token($db, $clientId);
    if (empty($billingToken['success']) || empty($billingToken['token'])) {
        return ['success' => false, 'error' => $billingToken['error'] ?? 'Unable to issue workspace token'];
    }

    $exchange = alfred_workspace_middleware_json_request(
        '/api/sso/billing-exchange',
        ['token' => $billingToken['token']],
        [],
        15
    );
    if (empty($exchange['ok']) || empty($exchange['token'])) {
        return ['success' => false, 'error' => $exchange['error'] ?? 'Unable to exchange workspace token'];
    }

    $launch = alfred_workspace_middleware_json_request(
        '/api/launch',
        ['workspace' => 'public_html'],
        ['Authorization: Bearer ' . $exchange['token']],
        75
    );
    if (empty($launch['ok']) || empty($launch['ideUrl'])) {
        return ['success' => false, 'error' => $launch['error'] ?? 'Unable to start Alfred workspace'];
    }

    return [
        'success' => true,
        'url' => (string)$launch['ideUrl'],
        'method' => 'billing-exchange-launch',
        'expires' => (string)($billingToken['expires'] ?? ''),
        'session_token' => (string)$exchange['token'],
        'agent_url' => (string)($launch['agentUrl'] ?? ''),
    ];
}