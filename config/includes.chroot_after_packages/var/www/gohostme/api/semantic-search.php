<?php
/**
 * Alfred Semantic Search API Proxy
 * Proxies requests to the internal Node.js semantic search server (port 3020)
 * Keeps the semantic engine internal — not publicly accessible directly.
 *
 * Actions:
 *   GET  ?action=search&q=...&workspace=...&limit=10  → vector search
 *   GET  ?action=status                               → engine status
 *   GET  ?action=models                               → list Ollama models
 *   POST ?action=index  body: {"workspace":"...","force":false} → trigger indexing
 *   GET  ?action=progress                             → indexing progress
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-IDE-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('SEMANTIC_OWNER_CLIENT_ID', 33);
define('SEMANTIC_ALLOWED_WORKSPACE', '/home/gositeme');
define('SEMANTIC_AUDIT_LOG', '/home/gositeme/domains/gositeme.com/logs/semantic-access.log');

// Rate-limit this endpoint independently.
apiRateLimit(45, 60, 'semantic-search');

function semanticAudit(string $event, array $meta = []): void {
    $payload = [
        'ts' => date('c'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'action' => $_GET['action'] ?? ($_POST['action'] ?? 'status'),
        'meta' => $meta,
    ];
    @file_put_contents(SEMANTIC_AUDIT_LOG, json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
}

/**
 * Require a valid Alfred IDE session token (Bearer, X-Alfred-IDE-Token,
 * cookie/session bridge) before allowing semantic operations.
 */
function requireSemanticAuth(): int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = '';

    // 1) Authorization: Bearer <token>
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth !== '' && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        $token = trim($m[1]);
    }

    // 2) Explicit IDE token header
    if ($token === '') {
        $token = trim($_SERVER['HTTP_X_ALFRED_IDE_TOKEN'] ?? '');
    }

    // 3) Browser/session bridge fallback
    if ($token === '') {
        $token = trim($_COOKIE['alfred_ide_token'] ?? ($_SESSION['ide_session_token'] ?? ''));
    }

    if ($token === '') {
        semanticAudit('auth_missing_token');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: missing IDE session token']);
        exit;
    }

    try {
        $db = getSharedDB();
        $hash = hash('sha256', $token);
        $stmt = $db->prepare("SELECT client_id FROM alfred_ide_users WHERE session_token = ? AND token_expires > NOW() LIMIT 1");
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $clientId = (int)($row['client_id'] ?? 0);
        if (!$row || $clientId <= 0) {
            semanticAudit('auth_invalid_or_expired');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: invalid or expired IDE session']);
            exit;
        }
        semanticAudit('auth_ok', ['client_id' => $clientId]);
        return $clientId;
    } catch (Throwable $e) {
        semanticAudit('auth_exception');
        http_response_code(500);
        echo json_encode(['error' => 'Auth check failed']);
        exit;
    }
}

$semanticClientId = requireSemanticAuth();
if ($semanticClientId !== SEMANTIC_OWNER_CLIENT_ID) {
    semanticAudit('forbidden_client', ['client_id' => $semanticClientId]);
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Commander-only semantic endpoint']);
    exit;
}

function normalizeSemanticWorkspace(string $workspace): string {
    $candidate = trim($workspace);
    if ($candidate === '') return SEMANTIC_ALLOWED_WORKSPACE;
    $real = realpath($candidate);
    $allowed = SEMANTIC_ALLOWED_WORKSPACE;
    if ($real === false) return $allowed;
    if ($real === $allowed || str_starts_with($real, $allowed . '/')) return $real;
    semanticAudit('workspace_clamped', ['requested' => $candidate, 'resolved' => $real]);
    return $allowed;
}

define('SEMANTIC_HOST', 'http://127.0.0.1:3020');

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

function semanticRequest(string $method, string $path, ?array $body = null): array {
    $url = SEMANTIC_HOST . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? new stdClass()));
    }
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['error' => 'Semantic engine unavailable: ' . $err];
    if (!$response) return ['error' => 'Empty response from semantic engine'];

    $data = json_decode($response, true);
    return is_array($data) ? $data : ['error' => 'Invalid JSON from semantic engine'];
}

switch ($action) {
    case 'search': {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing q parameter']);
            exit;
        }
        $workspace = normalizeSemanticWorkspace($_GET['workspace'] ?? '');
        $limit = max(1, min(30, (int)($_GET['limit'] ?? 10)));
        $qs = http_build_query(['q' => $q, 'workspace' => $workspace, 'limit' => $limit]);
        echo json_encode(semanticRequest('GET', '/search?' . $qs));
        break;
    }
    case 'index': {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $workspace = normalizeSemanticWorkspace((string)($body['workspace'] ?? SEMANTIC_ALLOWED_WORKSPACE));
        $force = (bool)($body['force'] ?? false);
        echo json_encode(semanticRequest('POST', '/index', ['workspace' => $workspace, 'force' => $force]));
        break;
    }
    case 'models':
        echo json_encode(semanticRequest('GET', '/models'));
        break;
    case 'progress':
        echo json_encode(semanticRequest('GET', '/progress'));
        break;
    case 'status':
    default:
        echo json_encode(semanticRequest('GET', '/status'));
        break;
}
