<?php
/**
 * COVENANT CHECK API — Family Bible Gate
 * Every Kingdom service must check this before granting access.
 * 
 * GET  ?action=check     Check if current user has Family Bible covenant
 * POST ?action=generate  Generate covenant for authenticated user
 * 
 * Auth: GoSiteMe session cookie, SSO token, or IDE token
 * Returns: { has_covenant, family_name, covenant_hash, display_name, client_id }
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type, X-Vault-Token');
    http_response_code(204);
    exit;
}

require_once dirname(__DIR__) . '/includes/db-config.inc.php';
require_once dirname(__DIR__) . '/includes/auth-gate.inc.php';

$pdo = getSharedDB();
$action = $_GET['action'] ?? 'check';

// Ensure covenant table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS family_bible_covenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    family_name VARCHAR(255) NOT NULL,
    covenant_hash VARCHAR(64) NOT NULL,
    covenant_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    scripture_ref VARCHAR(255) DEFAULT 'Ruth 4:13-16',
    notes TEXT DEFAULT NULL,
    INDEX idx_client (client_id),
    INDEX idx_hash (covenant_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get authenticated user
$clientId = null;
$displayName = null;

if (isset($_SESSION['client_id'])) {
    $clientId = (int)$_SESSION['client_id'];
    $displayName = $_SESSION['client_name'] ?? $_SESSION['firstname'] ?? null;
}

if (!$clientId && isset($_SESSION['uid'])) {
    $clientId = (int)$_SESSION['uid'];
}

// Try IDE token auth
if (!$clientId) {
    $token = $_COOKIE['alfred_ide_token'] ?? $_GET['token'] ?? '';
    if ($token) {
        $hash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT s.user_id, u.display_name, u.client_id FROM ide_sessions s JOIN ide_users u ON s.user_id = u.id WHERE s.session_token = ? AND s.expires_at > NOW()");
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $clientId = (int)($row['client_id'] ?: $row['user_id']);
            $displayName = $row['display_name'];
        }
    }
}

$isCommander = ($clientId === 33);

switch ($action) {
    case 'check':
        if (!$clientId) {
            echo json_encode([
                'authenticated' => false,
                'has_covenant' => false,
                'message' => 'Please log in to your GoSiteMe account first.',
                'login_url' => '/login.php?return=' . urlencode($_GET['return'] ?? '/'),
            ]);
            break;
        }

        // Commander always has covenant
        if ($isCommander) {
            echo json_encode([
                'authenticated' => true,
                'has_covenant' => true,
                'family_name' => 'Perez',
                'covenant_hash' => hash('sha256', 'Perez-33-Kingdom'),
                'display_name' => $displayName ?: 'Commander Danny William Perez',
                'client_id' => 33,
                'is_commander' => true,
            ]);
            break;
        }

        $stmt = $pdo->prepare("SELECT * FROM family_bible_covenants WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $covenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($covenant) {
            echo json_encode([
                'authenticated' => true,
                'has_covenant' => true,
                'family_name' => $covenant['family_name'],
                'covenant_hash' => $covenant['covenant_hash'],
                'display_name' => $displayName,
                'client_id' => $clientId,
                'covenant_date' => $covenant['covenant_date'],
            ]);
        } else {
            echo json_encode([
                'authenticated' => true,
                'has_covenant' => false,
                'display_name' => $displayName,
                'client_id' => $clientId,
                'message' => 'Generate your Family Bible covenant to access Kingdom services.',
                'generate_url' => '/family-bible.php',
            ]);
        }
        break;

    case 'generate':
        if (!$clientId) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $familyName = trim($input['family_name'] ?? '');

        if (!$familyName || strlen($familyName) < 2 || strlen($familyName) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Family name is required (2-100 characters)']);
            break;
        }

        // Sanitize: only letters, spaces, hyphens, apostrophes
        if (!preg_match('/^[\p{L}\s\'-]+$/u', $familyName)) {
            http_response_code(400);
            echo json_encode(['error' => 'Family name can only contain letters, spaces, hyphens, and apostrophes']);
            break;
        }

        $covenantHash = hash('sha256', $familyName . '-' . $clientId . '-Kingdom-' . date('Y'));

        $stmt = $pdo->prepare("INSERT INTO family_bible_covenants (client_id, family_name, covenant_hash) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE family_name = VALUES(family_name), covenant_hash = VALUES(covenant_hash)");
        $stmt->execute([$clientId, $familyName, $covenantHash]);

        echo json_encode([
            'ok' => true,
            'has_covenant' => true,
            'family_name' => $familyName,
            'covenant_hash' => $covenantHash,
            'client_id' => $clientId,
            'message' => "The $familyName family Bible covenant is sealed.",
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
