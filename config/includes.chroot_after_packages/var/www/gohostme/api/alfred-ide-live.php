<?php
/**
 * /api/alfred-ide-live.php — JSON feed for live refresh (Account dashboard panel
 * + alfred-ide-dashboard.php). Accepts EITHER:
 *   - Bearer token (IDE extensions / non-browser clients)
 *   - Website PHP session ($_SESSION['client_id']) for the dashboard page
 *
 * Returns JSON 401 on missing auth — never a 302 to /login (the IDE webview
 * cannot follow website-session redirects, that produced
 * "Unexpected token '<'" JSON.parse errors).
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true;
$GLOBALS['RATE_LIMIT_EXEMPT'] = true;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Alfred-IDE-Token, X-Alfred-Source');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../includes/path-guard.inc.php';
require_once __DIR__ . '/../includes/alfred-ide-bearer.inc.php';

function jlive(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, gositeme_json_public_encode_flags() | JSON_UNESCAPED_SLASHES);
    exit;
}

try { $db = getSharedDB(); }
catch (Throwable $e) { jlive(['ok'=>false,'error'=>'db_unavailable'], 503); }

// ── Resolve $clientId from Bearer first, then PHP session ───────────────────
$clientId = 0;

$token = alfred_resolve_ide_bearer_token();
if ($token !== '') {
    $tokenHash = hash('sha256', $token);
    $st = $db->prepare(
        "SELECT t.user_id FROM alfred_oauth_tokens t
         WHERE (t.access_token = ? OR t.access_token = ?)
           AND (t.expires_at IS NULL OR t.expires_at > NOW())
         LIMIT 1"
    );
    $st->execute([$tokenHash, $token]);
    $clientId = (int)($st->fetchColumn() ?: 0);

    // Fallback: alfred_ide_users.session_token (alfred-ide-auth.php bridge token)
    if ($clientId === 0) {
        $st = $db->prepare(
            "SELECT client_id FROM alfred_ide_users
             WHERE session_token = ?
               AND (token_expires IS NULL OR token_expires > NOW())
             LIMIT 1"
        );
        $st->execute([$tokenHash]);
        $clientId = (int)($st->fetchColumn() ?: 0);
    }
}

if ($clientId === 0) {
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    if (!empty($_SESSION['client_id'])) {
        $clientId = (int)$_SESSION['client_id'];
    }
}

if ($clientId === 0) {
    jlive(['ok'=>false,'error'=>'unauthorized'], 401);
}

// ── Live feed payload ───────────────────────────────────────────────────────
$out = ['ok'=>false, 'recent'=>[], 'today'=>[]];
try {
    $q = function (string $sql, array $a = []) use ($db) {
        $s = $db->prepare($sql); $s->execute($a); return $s;
    };
    $out['recent'] = $q(
        "SELECT resource_type AS type, IFNULL(description,'') AS description,
                COALESCE(quantity,1) AS quantity, created_at,
                IFNULL(status,'completed') AS status
         FROM alfred_usage
         WHERE user_id = ?
         ORDER BY created_at DESC LIMIT 15",
        [$clientId]
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $out['today'] = [
        'api_calls'     => (int)$q("SELECT COUNT(*) FROM alfred_usage WHERE user_id=? AND resource_type='api_call' AND created_at>=CURDATE()", [$clientId])->fetchColumn(),
        'voice_minutes' => (int)$q("SELECT COALESCE(SUM(quantity),0) FROM alfred_usage WHERE user_id=? AND resource_type='voice_minute' AND created_at>=CURDATE()", [$clientId])->fetchColumn(),
        'tool_runs'     => (int)$q("SELECT COUNT(*) FROM alfred_tool_usage WHERE user_id=? AND used_at>=CURDATE()", [$clientId])->fetchColumn(),
        'conversations' => (int)$q("SELECT COUNT(*) FROM alfred_conversations WHERE user_id=? AND created_at>=CURDATE()", [$clientId])->fetchColumn(),
    ];
    $out['ok'] = true;
} catch (Throwable $e) {
    $out['error'] = 'query_failed';
    error_log('alfred-ide-live: ' . $e->getMessage());
}

echo json_encode($out, gositeme_json_public_encode_flags() | JSON_UNESCAPED_SLASHES);
