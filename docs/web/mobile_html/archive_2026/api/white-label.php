<?php
/**
 * Alfred AI — White-Label Configuration API
 * Endpoints:
 *   GET  ?action=config      — Get white-label config for org
 *   POST ?action=config      — Save/update white-label config
 *   POST ?action=verify-domain — Check DNS for custom domain CNAME
 *   GET  ?action=preview     — Get CSS generated from white-label settings
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Ensure table exists ───
function ensureWhiteLabelTable() {
    $db = getDB();
    if (!$db) return false;
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_white_label (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL UNIQUE,
        company_name VARCHAR(200),
        logo_data MEDIUMTEXT,
        primary_color VARCHAR(7) DEFAULT '#6c5ce7',
        secondary_color VARCHAR(7) DEFAULT '#a29bfe',
        font_family VARCHAR(100) DEFAULT 'Inter',
        custom_css TEXT,
        custom_domain VARCHAR(200),
        domain_verified TINYINT(1) DEFAULT 0,
        email_sender_name VARCHAR(100),
        email_reply_to VARCHAR(200),
        welcome_template TEXT,
        notification_template TEXT,
        voice_greeting TEXT,
        voice_company_name VARCHAR(200),
        hold_music_url VARCHAR(500),
        feature_toggles JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return true;
}

// ─── Auth check ───
function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    }
    return (int)$_SESSION['client_id'];
}

// ─── Get org_id for client ───
function getOrgId($clientId) {
    $db = getDB();
    if (!$db) return $clientId; // fallback to client_id as org
    try {
        $stmt = $db->prepare("SELECT org_id FROM alfred_enterprise_members WHERE client_id = ? LIMIT 1");
        $stmt->execute([$clientId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['org_id'] : $clientId;
    } catch (Exception $e) {
        return $clientId;
    }
}

// ─── Route ───
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'config':
        if ($method === 'GET') getConfig();
        elseif ($method === 'POST') saveConfig();
        else jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        break;
    case 'verify-domain':
        if ($method === 'POST') verifyDomain();
        else jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        break;
    case 'preview':
        if ($method === 'GET') getPreviewCSS();
        else jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action. Use: config, verify-domain, preview'], 400);
}

// ═══════════════════════════════════════════════════════
// GET CONFIG
// ═══════════════════════════════════════════════════════
function getConfig() {
    $clientId = requireAuth();
    $orgId = getOrgId($clientId);

    ensureWhiteLabelTable();
    $db = getDB();
    if (!$db) jsonResponse(['success' => false, 'error' => 'Database error'], 500);

    $stmt = $db->prepare("SELECT * FROM alfred_white_label WHERE org_id = ? LIMIT 1");
    $stmt->execute([$orgId]);
    $config = $stmt->fetch();

    if (!$config) {
        jsonResponse([
            'success' => true,
            'config' => [
                'org_id' => $orgId,
                'company_name' => '',
                'logo_data' => '',
                'primary_color' => '#6c5ce7',
                'secondary_color' => '#a29bfe',
                'font_family' => 'Inter',
                'custom_css' => '',
                'custom_domain' => '',
                'domain_verified' => false,
                'email_sender_name' => '',
                'email_reply_to' => '',
                'welcome_template' => '',
                'notification_template' => '',
                'voice_greeting' => '',
                'voice_company_name' => '',
                'hold_music_url' => '',
                'feature_toggles' => new \stdClass()
            ],
            'is_new' => true
        ]);
    }

    // Decode feature_toggles JSON
    if (isset($config['feature_toggles'])) {
        $config['feature_toggles'] = json_decode($config['feature_toggles'], true) ?: new \stdClass();
    }
    $config['domain_verified'] = (bool)$config['domain_verified'];
    unset($config['id']);

    jsonResponse(['success' => true, 'config' => $config, 'is_new' => false]);
}

// ═══════════════════════════════════════════════════════
// SAVE CONFIG
// ═══════════════════════════════════════════════════════
function saveConfig() {
    $clientId = requireAuth();
    $orgId = getOrgId($clientId);

    ensureWhiteLabelTable();
    $db = getDB();
    if (!$db) jsonResponse(['success' => false, 'error' => 'Database error'], 500);

    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    // Allowed fields
    $allowed = [
        'company_name', 'logo_data', 'primary_color', 'secondary_color',
        'font_family', 'custom_css', 'custom_domain', 'email_sender_name',
        'email_reply_to', 'welcome_template', 'notification_template',
        'voice_greeting', 'voice_company_name', 'hold_music_url', 'feature_toggles'
    ];

    // Validate color fields
    if (!empty($input['primary_color']) && !preg_match('/^#[0-9a-fA-F]{6}$/', $input['primary_color'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid primary color format. Use #RRGGBB.'], 400);
    }
    if (!empty($input['secondary_color']) && !preg_match('/^#[0-9a-fA-F]{6}$/', $input['secondary_color'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid secondary color format. Use #RRGGBB.'], 400);
    }

    // Validate email
    if (!empty($input['email_reply_to']) && !filter_var($input['email_reply_to'], FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'error' => 'Invalid email format for reply-to address.'], 400);
    }

    // Validate domain
    if (!empty($input['custom_domain'])) {
        $domain = strtolower(trim($input['custom_domain']));
        if (!preg_match('/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*\.[a-z]{2,}$/', $domain)) {
            jsonResponse(['success' => false, 'error' => 'Invalid domain format.'], 400);
        }
        $input['custom_domain'] = $domain;
    }

    // Encode feature_toggles as JSON
    if (isset($input['feature_toggles']) && is_array($input['feature_toggles'])) {
        $input['feature_toggles'] = json_encode($input['feature_toggles']);
    }

    // Build update data
    $data = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $data[$field] = sanitize($input[$field], $field === 'logo_data' ? 0 : ($field === 'custom_css' ? 0 : ($field === 'welcome_template' ? 0 : ($field === 'notification_template' ? 0 : 500))));
        }
    }

    if (empty($data)) {
        jsonResponse(['success' => false, 'error' => 'No valid fields provided.'], 400);
    }

    try {
        // Check if record exists
        $stmt = $db->prepare("SELECT id FROM alfred_white_label WHERE org_id = ? LIMIT 1");
        $stmt->execute([$orgId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // UPDATE
            $sets = [];
            $vals = [];
            foreach ($data as $field => $value) {
                $sets[] = "$field = ?";
                $vals[] = $value;
            }
            $vals[] = $orgId;
            $sql = "UPDATE alfred_white_label SET " . implode(', ', $sets) . " WHERE org_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($vals);
        } else {
            // INSERT
            $data['org_id'] = $orgId;
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $sql = "INSERT INTO alfred_white_label (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($data));
        }

        jsonResponse(['success' => true, 'message' => 'White-label configuration saved.']);
    } catch (Exception $e) {
        error_log("White-label save error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to save configuration.'], 500);
    }
}

// ═══════════════════════════════════════════════════════
// VERIFY DOMAIN
// ═══════════════════════════════════════════════════════
function verifyDomain() {
    $clientId = requireAuth();
    $orgId = getOrgId($clientId);

    $db = getDB();
    if (!$db) jsonResponse(['success' => false, 'error' => 'Database error'], 500);

    $stmt = $db->prepare("SELECT custom_domain FROM alfred_white_label WHERE org_id = ? LIMIT 1");
    $stmt->execute([$orgId]);
    $row = $stmt->fetch();

    if (!$row || empty($row['custom_domain'])) {
        jsonResponse(['success' => false, 'error' => 'No custom domain configured. Save a domain first.'], 400);
    }

    $domain = $row['custom_domain'];

    // DNS lookup for CNAME
    $records = @dns_get_record($domain, DNS_CNAME);
    $verified = false;
    $cname_target = '';

    if ($records && is_array($records)) {
        foreach ($records as $record) {
            if (isset($record['target'])) {
                $cname_target = $record['target'];
                if (stripos($record['target'], 'gositeme.com') !== false) {
                    $verified = true;
                    break;
                }
            }
        }
    }

    // Update verification status
    $stmt = $db->prepare("UPDATE alfred_white_label SET domain_verified = ? WHERE org_id = ?");
    $stmt->execute([$verified ? 1 : 0, $orgId]);

    jsonResponse([
        'success' => true,
        'domain' => $domain,
        'verified' => $verified,
        'cname_found' => $cname_target,
        'expected_target' => 'gositeme.com',
        'message' => $verified
            ? 'Domain verified successfully! CNAME record points to gositeme.com.'
            : 'CNAME record not found or does not point to gositeme.com. Add a CNAME record for "' . $domain . '" pointing to "gositeme.com".'
    ]);
}

// ═══════════════════════════════════════════════════════
// GET PREVIEW CSS
// ═══════════════════════════════════════════════════════
function getPreviewCSS() {
    $clientId = requireAuth();
    $orgId = getOrgId($clientId);

    $db = getDB();
    if (!$db) jsonResponse(['success' => false, 'error' => 'Database error'], 500);

    $stmt = $db->prepare("SELECT primary_color, secondary_color, font_family, custom_css FROM alfred_white_label WHERE org_id = ? LIMIT 1");
    $stmt->execute([$orgId]);
    $config = $stmt->fetch();

    if (!$config) {
        header('Content-Type: text/css');
        echo '/* No white-label config found */';
        exit;
    }

    $primary = $config['primary_color'] ?: '#6c5ce7';
    $secondary = $config['secondary_color'] ?: '#a29bfe';
    $font = $config['font_family'] ?: 'Inter';
    $custom = $config['custom_css'] ?: '';

    header('Content-Type: text/css');
    echo ":root {\n";
    echo "  --wl-primary: {$primary};\n";
    echo "  --wl-secondary: {$secondary};\n";
    echo "  --wl-font: '{$font}', sans-serif;\n";
    echo "}\n\n";
    echo "body { font-family: var(--wl-font); }\n";
    echo ".navbar, .site-header { border-bottom-color: {$primary}; }\n";
    echo ".btn-primary, .al-btn-primary { background: {$primary} !important; }\n";
    echo ".btn-primary:hover, .al-btn-primary:hover { background: {$secondary} !important; }\n";
    echo "a { color: {$primary}; }\n\n";

    if ($custom) {
        echo "/* Custom CSS */\n";
        echo $custom . "\n";
    }

    exit;
}
