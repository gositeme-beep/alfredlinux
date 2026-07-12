<?php
/**
 * Resolve Alfred IDE session token from the request.
 *
 * Apache + PHP-FPM/CGI often does NOT populate $_SERVER['HTTP_AUTHORIZATION'].
 * Use RewriteRule (see api/.htaccess) + fallbacks below + optional X-Alfred-IDE-Token header.
 */
function alfred_resolve_ide_bearer_token(): string
{
    $headers = [
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
    ];
    foreach ($headers as $h) {
        $h = trim((string) $h);
        if ($h !== '' && preg_match('/^Bearer\s+(\S+)/i', $h, $m)) {
            return trim($m[1]);
        }
    }

    $xt = trim((string) ($_SERVER['HTTP_X_ALFRED_IDE_TOKEN'] ?? ''));
    if ($xt !== '') {
        return $xt;
    }

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            $name = (string) $name;
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            if (strcasecmp($name, 'Authorization') === 0 && preg_match('/Bearer\s+(\S+)/i', $value, $m)) {
                return trim($m[1]);
            }
            if (strcasecmp($name, 'X-Alfred-IDE-Token') === 0) {
                return $value;
            }
        }
    }

    return '';
}

/**
 * Safe diagnostics for IDE token plumbing (no secret values — lengths/flags only).
 */
function alfred_ide_bearer_debug_channels(): array
{
    $ghAuth = false;
    $ghX = false;
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            $name = (string) $name;
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            if (strcasecmp($name, 'Authorization') === 0) {
                $ghAuth = true;
            }
            if (strcasecmp($name, 'X-Alfred-IDE-Token') === 0) {
                $ghX = true;
            }
        }
    }

    $resolved = alfred_resolve_ide_bearer_token();

    return [
        'HTTP_AUTHORIZATION_nonempty' => trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '')) !== '',
        'REDIRECT_HTTP_AUTHORIZATION_nonempty' => trim((string) ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '')) !== '',
        'HTTP_X_ALFRED_IDE_TOKEN_nonempty' => trim((string) ($_SERVER['HTTP_X_ALFRED_IDE_TOKEN'] ?? '')) !== '',
        'getallheaders_Authorization_nonempty' => $ghAuth,
        'getallheaders_X_Alfred_IDE_Token_nonempty' => $ghX,
        'resolved_token_length' => strlen($resolved),
        'php_sapi' => PHP_SAPI,
    ];
}

/**
 * Look up alfred_ide_users by hashed session token and apply a sliding expiry.
 * Long-running IDE tabs can outlive token_expires in MySQL; without this, chat API
 * loses Bearer auth and CSRF handling breaks while the UI still looks "logged in".
 *
 * @param string $tokenHash sha256 hex of the raw cookie / Bearer token
 * @return array|null user row (same shape as previous SELECTs) or null if unknown / too stale
 */
function alfred_ide_lookup_user_by_token_hash(PDO $db, string $tokenHash): ?array
{
    $stmt = $db->prepare('SELECT id, client_id, email, google_email, display_name, google_name, token_expires, google_avatar, last_login FROM alfred_ide_users WHERE session_token = ? LIMIT 1');
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $expTs = strtotime((string) ($row['token_expires'] ?? ''));
    $now = time();
    if ($expTs && $expTs < $now) {
        $graceSec = 14 * 86400;
        if ($now - $expTs > $graceSec) {
            return null;
        }
    }

    $uid = (int) ($row['id'] ?? 0);
    $upd = $db->prepare('UPDATE alfred_ide_users SET token_expires = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = ?');
    $upd->execute([$uid]);

    $ref = $db->prepare('SELECT token_expires FROM alfred_ide_users WHERE id = ? LIMIT 1');
    $ref->execute([$uid]);
    $row['token_expires'] = $ref->fetchColumn();

    return $row;
}
