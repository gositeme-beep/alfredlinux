<?php
/**
 * COMMANDER RELEASE API — Push Releases Programmatically
 * ══════════════════════════════════════════════════════════
 * Used by: Alfred AI, coder agents, CI/CD pipelines, Eden's future tools
 *
 * Auth: Bearer token (gsr_*) checked against commander_release_keys
 * Also accepts Commander session auth (client_id 33)
 *
 * Actions: create, list, latest, publish, delete, products
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/../includes/db-config.inc.php';
$db = getSharedDB();

// ── AUTH ──
$caller = null;  // ['name' => ..., 'role' => ..., 'permissions' => ...]

// Method 1: Bearer token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(gsr_[a-f0-9]{64})$/i', $authHeader, $m)) {
    $tokenHash = hash('sha256', $m[1]);
    $stmt = $db->prepare("SELECT key_name, role, permissions FROM commander_release_keys WHERE api_key_hash = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$tokenHash]);
    $keyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($keyRow) {
        $caller = [
            'name'        => $keyRow['key_name'],
            'role'        => $keyRow['role'],
            'permissions' => json_decode($keyRow['permissions'] ?? '{}', true) ?: [],
        ];
        // Update last_used
        $db->prepare("UPDATE commander_release_keys SET last_used = NOW() WHERE key_name = ?")->execute([$keyRow['key_name']]);
    }
}

// Method 2: Internal secret header
if (!$caller) {
    $internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    if ($internal) {
        $expected = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : (getenv('INTERNAL_SECRET') ?: '');
        if ($expected && hash_equals($expected, $internal)) {
            $caller = ['name' => 'Internal', 'role' => 'commander', 'permissions' => ['products' => '*', 'can_publish' => true, 'can_delete' => true]];
        }
    }
}

// Method 3: Session auth (Commander)
if (!$caller) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['client_id']) && (int)$_SESSION['client_id'] === 33) {
        $caller = ['name' => 'Commander Danny', 'role' => 'commander', 'permissions' => ['products' => '*', 'can_publish' => true, 'can_delete' => true]];
    }
}

// ── PARSE REQUEST ──
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? $_GET['action'] ?? 'list';

// Public actions that don't need auth
$publicActions = ['check', 'products'];

if (!$caller && !in_array($action, $publicActions, true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Provide Bearer gsr_* token or Commander session.']);
    exit;
}

// Default caller for public actions
if (!$caller) {
    $caller = ['name' => 'public', 'role' => 'public', 'permissions' => []];
}

// ── PRODUCTS ──
$products = [
    'alfred-linux', 'alfred-browser', 'alfred-ide', 'alfred-mobile',
    'alfred-extension', 'veil-messenger', 'pulse-social', 'metadome',
    'voice-ai', 'sacred-library', 'gohost-platform', 'alfred-search',
];

// ── ROUTE ──
switch ($action) {

    // ═══════════════════════════════════════════
    // CREATE A RELEASE
    // ═══════════════════════════════════════════
    case 'create':
        $product     = $input['product'] ?? '';
        $version     = trim($input['version'] ?? '');
        $channel     = $input['channel'] ?? 'stable';
        $platform    = $input['platform'] ?? 'all';
        $releaseType = $input['release_type'] ?? 'patch';
        $title       = trim($input['title'] ?? '');
        $changelog   = trim($input['changelog'] ?? '');
        $downloadUrl = trim($input['download_url'] ?? '');
        $minVersion  = trim($input['min_version'] ?? '') ?: null;
        $isCritical  = !empty($input['is_critical']) ? 1 : 0;
        $sha256      = trim($input['sha256'] ?? '') ?: null;
        $publish     = !empty($input['publish']);

        // Validate
        if (!$product || !$version || !$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Required: product, version, title']);
            exit;
        }
        if (!in_array($product, $products, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product', 'valid_products' => $products]);
            exit;
        }
        if (!preg_match('/^\d+\.\d+\.\d+/', $version)) {
            http_response_code(400);
            echo json_encode(['error' => 'Version must be semver: X.Y.Z or X.Y.Z.W']);
            exit;
        }

        // Permission check
        $perms = $caller['permissions'];
        if (($perms['products'] ?? '*') !== '*' && !in_array($product, (array)($perms['products'] ?? []), true)) {
            http_response_code(403);
            echo json_encode(['error' => 'No permission for product: ' . $product]);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO commander_releases 
                (product, version, channel, platform, release_type, title, changelog,
                 download_url, sha256, min_version, released_by, released_by_id,
                 is_draft, is_critical, released_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,NULL,?,?,". ($publish ? "NOW()" : "NULL") .")");
            $stmt->execute([
                $product, $version, $channel, $platform, $releaseType,
                $title, $changelog, $downloadUrl ?: null, $sha256, $minVersion,
                $caller['name'], $publish ? 0 : 1, $isCritical
            ]);
            $id = (int)$db->lastInsertId();
            echo json_encode([
                'success' => true,
                'id' => $id,
                'product' => $product,
                'version' => $version,
                'status' => $publish ? 'published' : 'draft',
                'released_by' => $caller['name'],
            ]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                http_response_code(409);
                echo json_encode(['error' => "Version {$version} already exists for {$product}/{$platform}"]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Database error']);
                error_log('releases-api: ' . $e->getMessage());
            }
        }
        break;

    // ═══════════════════════════════════════════
    // LIST RELEASES
    // ═══════════════════════════════════════════
    case 'list':
        $product = $input['product'] ?? $_GET['product'] ?? null;
        $channel = $input['channel'] ?? $_GET['channel'] ?? null;
        $limit   = min((int)($input['limit'] ?? $_GET['limit'] ?? 50), 200);

        $sql = "SELECT id, product, version, channel, platform, release_type, title, changelog,
                       download_url, sha256, min_version, released_by, is_draft, is_critical,
                       download_count, created_at, released_at
                FROM commander_releases WHERE 1=1";
        $params = [];

        if ($product) { $sql .= " AND product = ?"; $params[] = $product; }
        if ($channel) { $sql .= " AND channel = ?"; $params[] = $channel; }

        // Agents only see published unless commander
        if ($caller['role'] === 'agent') {
            $sql .= " AND is_draft = 0";
        }

        $sql .= " ORDER BY released_at DESC, created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['releases' => $rows, 'count' => count($rows), 'caller' => $caller['name']]);
        break;

    // ═══════════════════════════════════════════
    // LATEST VERSION PER PRODUCT
    // ═══════════════════════════════════════════
    case 'latest':
        $product = $input['product'] ?? $_GET['product'] ?? null;
        $channel = $input['channel'] ?? $_GET['channel'] ?? 'stable';

        if ($product) {
            $stmt = $db->prepare("SELECT * FROM commander_releases WHERE product = ? AND channel = ? AND is_draft = 0 ORDER BY released_at DESC LIMIT 1");
            $stmt->execute([$product, $channel]);
            $rel = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($rel ?: ['error' => 'No release found', 'product' => $product, 'channel' => $channel]);
        } else {
            // All products latest
            $results = [];
            foreach ($products as $p) {
                $stmt = $db->prepare("SELECT product, version, channel, platform, title, download_url, sha256, released_at FROM commander_releases WHERE product = ? AND channel = ? AND is_draft = 0 ORDER BY released_at DESC LIMIT 1");
                $stmt->execute([$p, $channel]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) $results[$p] = $row;
            }
            echo json_encode(['latest' => $results, 'channel' => $channel]);
        }
        break;

    // ═══════════════════════════════════════════
    // PUBLISH A DRAFT
    // ═══════════════════════════════════════════
    case 'publish':
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Required: id']);
            exit;
        }
        $id = (int)$input['id'];
        $canPublish = $caller['permissions']['can_publish'] ?? ($caller['role'] !== 'agent');
        if (!$canPublish) {
            http_response_code(403);
            echo json_encode(['error' => 'No publish permission']);
            exit;
        }
        $db->prepare("UPDATE commander_releases SET is_draft = 0, released_at = NOW() WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'id' => $id, 'status' => 'published']);
        break;

    // ═══════════════════════════════════════════
    // DELETE A RELEASE (commander/heir only)
    // ═══════════════════════════════════════════
    case 'delete':
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Required: id']);
            exit;
        }
        $canDelete = $caller['permissions']['can_delete'] ?? false;
        if (!$canDelete) {
            http_response_code(403);
            echo json_encode(['error' => 'Only Commander and Heir can delete releases']);
            exit;
        }
        $id = (int)$input['id'];
        $db->prepare("DELETE FROM commander_releases WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'id' => $id, 'deleted' => true]);
        break;

    // ═══════════════════════════════════════════
    // LIST PRODUCTS
    // ═══════════════════════════════════════════
    case 'products':
        echo json_encode(['products' => $products]);
        break;

    // ═══════════════════════════════════════════
    // CHECK FOR UPDATES (client-facing, no auth needed for public)
    // ═══════════════════════════════════════════
    case 'check':
        // This action is PUBLIC — any Alfred Linux install can check for updates
        $product  = $input['product'] ?? $_GET['product'] ?? '';
        $current  = $input['current_version'] ?? $_GET['current_version'] ?? '';
        $channel  = $input['channel'] ?? $_GET['channel'] ?? 'stable';
        $platform = $input['platform'] ?? $_GET['platform'] ?? 'all';

        if (!$product) {
            http_response_code(400);
            echo json_encode(['error' => 'Required: product']);
            exit;
        }

        $stmt = $db->prepare("SELECT version, title, changelog, download_url, sha256, is_critical, released_at 
                              FROM commander_releases 
                              WHERE product = ? AND channel = ? AND is_draft = 0 
                                AND (platform = ? OR platform = 'all' OR ? = 'all')
                              ORDER BY released_at DESC LIMIT 1");
        $stmt->execute([$product, $channel, $platform, $platform]);
        $latest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$latest) {
            echo json_encode(['update_available' => false, 'product' => $product]);
        } else {
            $hasUpdate = $current ? version_compare($current, $latest['version'], '<') : true;
            echo json_encode([
                'update_available' => $hasUpdate,
                'product'          => $product,
                'current_version'  => $current ?: 'unknown',
                'latest_version'   => $latest['version'],
                'title'            => $latest['title'],
                'changelog'        => $latest['changelog'],
                'download_url'     => $latest['download_url'],
                'sha256'           => $latest['sha256'],
                'is_critical'      => (bool)$latest['is_critical'],
                'released_at'      => $latest['released_at'],
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action', 'valid_actions' => ['create', 'list', 'latest', 'publish', 'delete', 'products', 'check']]);
}
