<?php
/**
 * Alfred 3D Asset Pipeline API — Phase 3: Digital Embodiment
 * ──────────────────────────────────────────────────────────
 * Manage 3D assets, avatar customization, AI-driven mesh generation,
 * and asset library for the metaverse Kingdom.
 *
 * Endpoints:
 *   POST ?action=generate         → Generate 3D model via AI (Meshy)
 *   GET  ?action=generations      → List generation jobs
 *   GET  ?action=generation       → Get single generation status
 *   GET  ?action=library          → Browse asset library
 *   POST ?action=upload           → Upload custom asset
 *   POST ?action=avatar-config    → Save avatar configuration
 *   GET  ?action=avatar           → Get avatar config
 *   GET  ?action=categories       → Asset categories
 *   GET  ?action=stats            → Pipeline stats
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function ensureAssetSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_assets (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        asset_id        VARCHAR(64) UNIQUE NOT NULL,
        owner_id        INT DEFAULT NULL,
        name            VARCHAR(200) NOT NULL,
        description     TEXT DEFAULT NULL,
        category        ENUM('avatar','furniture','decoration','weapon','vehicle','building','terrain','audio','texture','effect') NOT NULL,
        format          VARCHAR(20) DEFAULT 'glb',
        file_url        VARCHAR(500) DEFAULT NULL,
        thumbnail_url   VARCHAR(500) DEFAULT NULL,
        poly_count      INT DEFAULT 0,
        file_size_kb    INT DEFAULT 0,
        tags            JSON DEFAULT NULL,
        is_public       TINYINT(1) DEFAULT 0,
        is_approved     TINYINT(1) DEFAULT 0,
        downloads       INT DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_owner (owner_id),
        INDEX idx_category (category),
        INDEX idx_public (is_public, is_approved),
        FULLTEXT idx_search (name, description)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_generations (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        gen_id          VARCHAR(64) UNIQUE NOT NULL,
        client_id       INT NOT NULL,
        prompt          VARCHAR(500) NOT NULL,
        style           VARCHAR(50) DEFAULT 'realistic',
        stage           ENUM('queued','processing','completed','failed') DEFAULT 'queued',
        provider        VARCHAR(50) DEFAULT 'meshy',
        external_id     VARCHAR(200) DEFAULT NULL,
        model_url       VARCHAR(500) DEFAULT NULL,
        thumbnail_url   VARCHAR(500) DEFAULT NULL,
        error_message   VARCHAR(500) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at    TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_client (client_id),
        INDEX idx_stage (stage)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_avatars (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNIQUE NOT NULL,
        base_model      VARCHAR(50) DEFAULT 'humanoid_a',
        skin_color      VARCHAR(7) DEFAULT '#C68642',
        hair_style      VARCHAR(50) DEFAULT 'default',
        hair_color      VARCHAR(7) DEFAULT '#1A1A1A',
        eye_color       VARCHAR(7) DEFAULT '#4A90D9',
        outfit          VARCHAR(50) DEFAULT 'casual_01',
        head_accessory  VARCHAR(50) DEFAULT NULL,
        body_accessory  VARCHAR(50) DEFAULT NULL,
        animation_set   VARCHAR(50) DEFAULT 'default',
        custom_model_url VARCHAR(500) DEFAULT NULL,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// ─── Asset Categories ──────────────────────────────────────────────
$CATEGORIES = [
    'avatar'     => ['name' => 'Avatars', 'icon' => '🧑', 'description' => 'Player character models'],
    'furniture'  => ['name' => 'Furniture', 'icon' => '🪑', 'description' => 'Tables, chairs, shelves'],
    'decoration' => ['name' => 'Decorations', 'icon' => '🏺', 'description' => 'Aesthetic objects and art'],
    'weapon'     => ['name' => 'Weapons', 'icon' => '⚔️', 'description' => 'Swords, shields, bows'],
    'vehicle'    => ['name' => 'Vehicles', 'icon' => '🚗', 'description' => 'Cars, horses, ships'],
    'building'   => ['name' => 'Buildings', 'icon' => '🏰', 'description' => 'Structures and architecture'],
    'terrain'    => ['name' => 'Terrain', 'icon' => '🏔️', 'description' => 'Landscape and environment'],
    'audio'      => ['name' => 'Audio', 'icon' => '🔊', 'description' => 'Sound effects and music'],
    'texture'    => ['name' => 'Textures', 'icon' => '🎨', 'description' => 'Materials and surfaces'],
    'effect'     => ['name' => 'Effects', 'icon' => '✨', 'description' => 'Particles and shaders'],
];

$AVATAR_OPTIONS = [
    'base_models'     => ['humanoid_a','humanoid_b','humanoid_c','robot','fantasy_elf','fantasy_dwarf'],
    'hair_styles'     => ['default','short','long','mohawk','braids','bun','ponytail','bald','afro','spikes'],
    'outfits'         => ['casual_01','casual_02','formal_01','armor_light','armor_heavy','robe','sports','sci_fi','medieval','steampunk'],
    'head_accessories'=> ['none','crown','hat','glasses','mask','helmet','horns','halo','headband','earrings'],
    'body_accessories'=> ['none','cape','wings','backpack','scarf','belt','shoulder_pet','aura','tail','shield'],
    'animation_sets'  => ['default','energetic','cool','elegant','silly','warrior','dancer','robot','ninja'],
];

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureAssetSchema();

switch ($action) {

    case 'generate':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $prompt = sanitize($input['prompt'] ?? '', 500);
        $style = sanitize($input['style'] ?? 'realistic', 50);
        if (strlen($prompt) < 5) jsonResponse(['error' => 'Prompt must be at least 5 characters'], 400);

        // Rate limit: 5 generations per day per user
        $today = $db->prepare("SELECT COUNT(*) FROM kingdom_generations WHERE client_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $today->execute([$_SESSION['client_id']]);
        if ($today->fetchColumn() >= 5) jsonResponse(['error' => 'Daily generation limit reached (5/day)'], 429);

        $genId = 'gen_' . bin2hex(random_bytes(16));

        // Call Meshy API if key available
        $meshyKey = getenv('MESHY_API_KEY') ?: '';
        $externalId = null;
        $stage = 'queued';

        if ($meshyKey) {
            $ch = curl_init('https://api.meshy.ai/v2/text-to-3d');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $meshyKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'mode' => 'preview',
                    'prompt' => $prompt,
                    'art_style' => $style,
                ]),
                CURLOPT_TIMEOUT => 30,
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($resp, true);
                $externalId = $data['result'] ?? null;
                $stage = 'processing';
            }
        }

        $db->prepare("INSERT INTO kingdom_generations (gen_id, client_id, prompt, style, stage, external_id) VALUES (?, ?, ?, ?, ?, ?)")->execute([
            $genId, $_SESSION['client_id'], $prompt, $style, $stage, $externalId
        ]);

        jsonResponse(['success' => true, 'gen_id' => $genId, 'stage' => $stage]);
        break;

    case 'generations':
        requireAuth();
        $limit = min(max(intval($_GET['limit'] ?? 20), 1), 50);
        $stmt = $db->prepare("SELECT gen_id, prompt, style, stage, model_url, thumbnail_url, error_message, created_at, completed_at FROM kingdom_generations WHERE client_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$_SESSION['client_id'], $limit]);
        jsonResponse(['success' => true, 'generations' => $stmt->fetchAll()]);
        break;

    case 'generation':
        requireAuth();
        $genId = sanitize($_GET['gen_id'] ?? '', 64);
        $stmt = $db->prepare("SELECT * FROM kingdom_generations WHERE gen_id = ? AND client_id = ?");
        $stmt->execute([$genId, $_SESSION['client_id']]);
        $gen = $stmt->fetch();
        if (!$gen) jsonResponse(['error' => 'Generation not found'], 404);

        // Poll Meshy if processing
        if ($gen['stage'] === 'processing' && $gen['external_id']) {
            $meshyKey = getenv('MESHY_API_KEY') ?: '';
            if ($meshyKey) {
                $ch = curl_init('https://api.meshy.ai/v2/text-to-3d/' . urlencode($gen['external_id']));
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $meshyKey],
                    CURLOPT_TIMEOUT => 15,
                ]);
                $resp = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($resp, true);

                if (($data['status'] ?? '') === 'SUCCEEDED') {
                    $modelUrl = $data['model_urls']['glb'] ?? null;
                    $thumbUrl = $data['thumbnail_url'] ?? null;
                    $db->prepare("UPDATE kingdom_generations SET stage = 'completed', model_url = ?, thumbnail_url = ?, completed_at = NOW() WHERE gen_id = ?")->execute([$modelUrl, $thumbUrl, $genId]);
                    $gen['stage'] = 'completed';
                    $gen['model_url'] = $modelUrl;
                    $gen['thumbnail_url'] = $thumbUrl;
                } elseif (($data['status'] ?? '') === 'FAILED') {
                    $db->prepare("UPDATE kingdom_generations SET stage = 'failed', error_message = ? WHERE gen_id = ?")->execute([$data['message'] ?? 'Unknown error', $genId]);
                    $gen['stage'] = 'failed';
                }
            }
        }

        jsonResponse(['success' => true, 'generation' => $gen]);
        break;

    case 'library':
        if (!isInternalCall()) requireAuth();

        $category = sanitize($_GET['category'] ?? '', 30);
        $search = sanitize($_GET['q'] ?? '', 100);
        $page = max(intval($_GET['page'] ?? 1), 1);
        $perPage = min(max(intval($_GET['per_page'] ?? 20), 1), 50);
        $offset = ($page - 1) * $perPage;

        $where = "is_public = 1 AND is_approved = 1";
        $params = [];

        if ($category) {
            $where .= " AND category = ?";
            $params[] = $category;
        }
        if ($search) {
            $where .= " AND MATCH(name, description) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $search;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM kingdom_assets WHERE $where");
        dbExecute($countStmt, $params);
        $total = (int)$countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $db->prepare("SELECT asset_id, name, description, category, format, thumbnail_url, poly_count, file_size_kb, tags, downloads FROM kingdom_assets WHERE $where ORDER BY downloads DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);

        $assets = $stmt->fetchAll();
        foreach ($assets as &$a) $a['tags'] = json_decode($a['tags'], true);

        jsonResponse(['success' => true, 'assets' => $assets, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $perPage)]);
        break;

    case 'upload':
        requireAuth();
        if (!isAdmin()) jsonResponse(['error' => 'Admin required for uploads currently'], 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $name = sanitize($input['name'] ?? '', 200);
        $category = sanitize($input['category'] ?? '', 30);
        if (!$name || !$category) jsonResponse(['error' => 'name and category required'], 400);

        global $CATEGORIES;
        if (!isset($CATEGORIES[$category])) jsonResponse(['error' => 'Invalid category'], 400);

        $assetId = 'asset_' . bin2hex(random_bytes(12));

        $db->prepare("INSERT INTO kingdom_assets (asset_id, owner_id, name, description, category, format, file_url, thumbnail_url, poly_count, file_size_kb, tags, is_public, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $assetId,
            $_SESSION['client_id'],
            $name,
            sanitize($input['description'] ?? '', 1000),
            $category,
            sanitize($input['format'] ?? 'glb', 20),
            $input['file_url'] ?? null,
            $input['thumbnail_url'] ?? null,
            intval($input['poly_count'] ?? 0),
            intval($input['file_size_kb'] ?? 0),
            json_encode($input['tags'] ?? []),
            isAdmin() ? 1 : 0,
            isAdmin() ? 1 : 0,
        ]);

        jsonResponse(['success' => true, 'asset_id' => $assetId]);
        break;

    case 'avatar-config':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $clientId = $_SESSION['client_id'];

        global $AVATAR_OPTIONS;

        $fields = [
            'base_model' => ['field' => 'base_model', 'options' => $AVATAR_OPTIONS['base_models']],
            'skin_color' => ['field' => 'skin_color', 'validate' => 'color'],
            'hair_style' => ['field' => 'hair_style', 'options' => $AVATAR_OPTIONS['hair_styles']],
            'hair_color' => ['field' => 'hair_color', 'validate' => 'color'],
            'eye_color'  => ['field' => 'eye_color', 'validate' => 'color'],
            'outfit'     => ['field' => 'outfit', 'options' => $AVATAR_OPTIONS['outfits']],
            'head_accessory' => ['field' => 'head_accessory', 'options' => array_merge(['none'], $AVATAR_OPTIONS['head_accessories'])],
            'body_accessory' => ['field' => 'body_accessory', 'options' => array_merge(['none'], $AVATAR_OPTIONS['body_accessories'])],
            'animation_set'  => ['field' => 'animation_set', 'options' => $AVATAR_OPTIONS['animation_sets']],
        ];

        $updates = [];
        $params = [];

        foreach ($fields as $key => $def) {
            if (!isset($input[$key])) continue;
            $val = sanitize($input[$key], 50);
            if (isset($def['options']) && !in_array($val, $def['options'])) continue;
            if (isset($def['validate']) && $def['validate'] === 'color' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $val)) continue;
            $updates[] = "{$def['field']} = ?";
            $params[] = $val === 'none' ? null : $val;
        }

        if (isset($input['custom_model_url'])) {
            $url = filter_var($input['custom_model_url'], FILTER_VALIDATE_URL);
            if ($url) { $updates[] = "custom_model_url = ?"; $params[] = $url; }
        }

        if (empty($updates)) jsonResponse(['error' => 'No valid fields'], 400);

        $existing = $db->prepare("SELECT id FROM kingdom_avatars WHERE client_id = ?");
        $existing->execute([$clientId]);

        if ($existing->fetch()) {
            $params[] = $clientId;
            $db->prepare("UPDATE kingdom_avatars SET " . implode(', ', $updates) . " WHERE client_id = ?")->execute($params);
        } else {
            // Build insert with defaults
            $db->prepare("INSERT INTO kingdom_avatars (client_id) VALUES (?)")->execute([$clientId]);
            if (!empty($updates)) {
                $params[] = $clientId;
                $db->prepare("UPDATE kingdom_avatars SET " . implode(', ', $updates) . " WHERE client_id = ?")->execute($params);
            }
        }

        jsonResponse(['success' => true]);
        break;

    case 'avatar':
        requireAuth();
        $clientId = intval($_GET['player_id'] ?? $_SESSION['client_id']);

        $stmt = $db->prepare("SELECT * FROM kingdom_avatars WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $avatar = $stmt->fetch();

        if (!$avatar) {
            $db->prepare("INSERT INTO kingdom_avatars (client_id) VALUES (?)")->execute([$clientId]);
            $stmt->execute([$clientId]);
            $avatar = $stmt->fetch();
        }

        global $AVATAR_OPTIONS;
        jsonResponse(['success' => true, 'avatar' => $avatar, 'options' => $AVATAR_OPTIONS]);
        break;

    case 'categories':
        global $CATEGORIES;
        jsonResponse(['success' => true, 'categories' => $CATEGORIES]);
        break;

    case 'stats':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $totalAssets = $db->query("SELECT COUNT(*) FROM kingdom_assets")->fetchColumn();
        $publicAssets = $db->query("SELECT COUNT(*) FROM kingdom_assets WHERE is_public = 1 AND is_approved = 1")->fetchColumn();
        $generations = $db->query("SELECT COUNT(*) FROM kingdom_generations")->fetchColumn();
        $completed = $db->query("SELECT COUNT(*) FROM kingdom_generations WHERE stage = 'completed'")->fetchColumn();
        $avatars = $db->query("SELECT COUNT(*) FROM kingdom_avatars")->fetchColumn();

        $byCategory = $db->query("SELECT category, COUNT(*) as count FROM kingdom_assets GROUP BY category ORDER BY count DESC")->fetchAll();
        $topDownloaded = $db->query("SELECT asset_id, name, category, downloads FROM kingdom_assets WHERE is_public = 1 ORDER BY downloads DESC LIMIT 10")->fetchAll();

        jsonResponse([
            'success' => true,
            'total_assets' => (int)$totalAssets,
            'public_assets' => (int)$publicAssets,
            'total_generations' => (int)$generations,
            'completed_generations' => (int)$completed,
            'total_avatars' => (int)$avatars,
            'by_category' => $byCategory,
            'top_downloaded' => $topDownloaded,
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['generate','generations','generation','library','upload','avatar-config','avatar','categories','stats']], 400);
}
