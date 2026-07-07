<?php
/**
 * GoCodeMe API — Project Management
 * ──────────────────────────────────
 * Handles project CRUD for the Games Maker and GoCodeMe editor.
 * Uses the existing `editor_projects` / `editor_project_versions` tables.
 *
 * Endpoints:
 *   POST ?action=create_project  → Create a new project
 *   GET  ?action=list_projects   → List user's projects
 *   GET  ?action=get_project     → Get single project by id or slug
 *   POST ?action=update_project  → Update project content
 *   POST ?action=delete_project  → Soft-delete a project
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

// ── Auth helpers ────────────────────────────────────────────────────────────
function getAuthUser(): ?int {
    return isset($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;
}

function requireAuth(): int {
    $clientId = getAuthUser();
    if (!$clientId) {
        apiError('Authentication required', 401, 'AUTH_REQUIRED');
    }
    return $clientId;
}

// ── Routing ─────────────────────────────────────────────────────────────────
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? $_POST['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

switch ($action) {
    case 'create_project':
        requireCSRF();
        apiRateLimit(10, 60, 'gocodeme_create');
        createProject($body ?? []);
        break;
    case 'list_projects':
        apiRateLimit(30, 60, 'gocodeme_list');
        listProjects();
        break;
    case 'get_project':
        apiRateLimit(30, 60, 'gocodeme_get');
        getProject();
        break;
    case 'update_project':
        requireCSRF();
        apiRateLimit(20, 60, 'gocodeme_update');
        updateProject($body ?? []);
        break;
    case 'delete_project':
        requireCSRF();
        apiRateLimit(10, 60, 'gocodeme_delete');
        deleteProject($body ?? []);
        break;
    default:
        apiError('Invalid action', 400, 'INVALID_ACTION');
}

// ── Validation helpers ──────────────────────────────────────────────────────
function sanitizeSlug(string $raw): string {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($raw)));
    return trim($slug, '-');
}

// ── Create Project ──────────────────────────────────────────────────────────
function createProject(array $body): void {
    $clientId = requireAuth();
    $db = getDB();
    if (!$db) apiError('Database unavailable', 503, 'DB_ERROR');

    // Validate inputs
    $name = trim($body['name'] ?? '');
    $slug = sanitizeSlug($body['slug'] ?? $name);
    $description = trim($body['description'] ?? '');
    $language = trim($body['language'] ?? 'html');
    $template = trim($body['template'] ?? 'blank');

    if ($name === '' || mb_strlen($name) > 100) {
        apiError('Project name is required (max 100 chars)', 422, 'INVALID_NAME');
    }
    if ($slug === '' || strlen($slug) > 60) {
        apiError('Invalid project slug', 422, 'INVALID_SLUG');
    }

    // Whitelist language and template values
    $allowedLanguages = ['html', 'javascript', 'webxr', 'python', 'typescript', 'php'];
    if (!in_array($language, $allowedLanguages, true)) {
        $language = 'html';
    }
    $allowedTemplates = [
        'blank', 'platformer', 'rpg', 'puzzle', 'cards', 'trivia',
        'vr-dnd', 'vr-arena', 'vr-explorer', 'vr-room',
        'chat-app', 'task-app', 'social', 'dashboard', 'ecommerce',
        'ai-chatbot', 'ai-art', 'ai-writer',
    ];
    if (!in_array($template, $allowedTemplates, true)) {
        $template = 'blank';
    }

    // Cap projects per user (prevent abuse)
    $stmt = $db->prepare('SELECT COUNT(*) FROM editor_projects WHERE user_id = ?');
    $stmt->execute([$clientId]);
    if ($stmt->fetchColumn() >= 50) {
        apiError('Project limit reached (max 50)', 429, 'PROJECT_LIMIT');
    }

    // Ensure slug is unique for this user
    $stmt = $db->prepare('SELECT COUNT(*) FROM editor_projects WHERE user_id = ? AND slug = ?');
    $stmt->execute([$clientId, $slug]);
    if ($stmt->fetchColumn() > 0) {
        // Append a short suffix to make it unique
        $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    // Generate starter content based on template
    $starterHtml = generateStarterHtml($name, $template, $language);
    $starterCss  = generateStarterCss($template);
    $starterJs   = generateStarterJs($template, $language);

    $stmt = $db->prepare(
        'INSERT INTO editor_projects (user_id, name, slug, description, html_content, css_content, js_content, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $stmt->execute([
        $clientId,
        mb_substr($name, 0, 255),
        substr($slug, 0, 255),
        mb_substr($description, 0, 2000),
        $starterHtml,
        $starterCss,
        $starterJs,
    ]);

    $projectId = (int) $db->lastInsertId();

    // Build the workspace URL — launches GoCodeMe IDE for this project
    $workspaceUrl = '/middleware/dashboard#project=' . $projectId;

    apiSuccess([
        'project_id'    => $projectId,
        'name'          => $name,
        'slug'          => $slug,
        'template'      => $template,
        'language'       => $language,
        'workspace_url' => $workspaceUrl,
        'created_at'    => date('c'),
    ], 201);
}

// ── List Projects ───────────────────────────────────────────────────────────
function listProjects(): void {
    $clientId = requireAuth();
    $db = getDB();
    if (!$db) apiError('Database unavailable', 503, 'DB_ERROR');

    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $limit  = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // Total count
    $stmt = $db->prepare('SELECT COUNT(*) FROM editor_projects WHERE user_id = ?');
    $stmt->execute([$clientId]);
    $total = (int) $stmt->fetchColumn();

    // Fetch page
    $stmt = $db->prepare(
        'SELECT id, name, slug, description, thumbnail, is_public, is_published, published_url, created_at, updated_at
         FROM editor_projects
         WHERE user_id = ?
         ORDER BY updated_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->bindValue(1, $clientId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    apiSuccess($projects, 200, [
        'page'       => $page,
        'limit'      => $limit,
        'total'      => $total,
        'total_pages' => (int) ceil($total / $limit),
    ]);
}

// ── Get Single Project ──────────────────────────────────────────────────────
function getProject(): void {
    $clientId = requireAuth();
    $db = getDB();
    if (!$db) apiError('Database unavailable', 503, 'DB_ERROR');

    $id   = (int) ($_GET['id'] ?? 0);
    $slug = trim($_GET['slug'] ?? '');

    if ($id > 0) {
        $stmt = $db->prepare('SELECT * FROM editor_projects WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $clientId]);
    } elseif ($slug !== '') {
        $stmt = $db->prepare('SELECT * FROM editor_projects WHERE slug = ? AND user_id = ?');
        $stmt->execute([substr($slug, 0, 255), $clientId]);
    } else {
        apiError('Provide id or slug parameter', 400, 'MISSING_PARAM');
    }

    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) {
        apiError('Project not found', 404, 'NOT_FOUND');
    }

    // Fetch version count
    $vStmt = $db->prepare('SELECT COUNT(*) FROM editor_project_versions WHERE project_id = ?');
    $vStmt->execute([$project['id']]);
    $project['version_count'] = (int) $vStmt->fetchColumn();

    apiSuccess($project);
}

// ── Update Project ──────────────────────────────────────────────────────────
function updateProject(array $body): void {
    $clientId = requireAuth();
    $db = getDB();
    if (!$db) apiError('Database unavailable', 503, 'DB_ERROR');

    $projectId = (int) ($body['project_id'] ?? 0);
    if ($projectId <= 0) apiError('project_id required', 400, 'MISSING_PARAM');

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM editor_projects WHERE id = ? AND user_id = ?');
    $stmt->execute([$projectId, $clientId]);
    if (!$stmt->fetch()) {
        apiError('Project not found', 404, 'NOT_FOUND');
    }

    $updates = [];
    $params  = [];

    if (isset($body['name'])) {
        $updates[] = 'name = ?';
        $params[]  = mb_substr(trim($body['name']), 0, 255);
    }
    if (isset($body['description'])) {
        $updates[] = 'description = ?';
        $params[]  = mb_substr(trim($body['description']), 0, 2000);
    }
    if (isset($body['html_content'])) {
        $updates[] = 'html_content = ?';
        $params[]  = $body['html_content'];
    }
    if (isset($body['css_content'])) {
        $updates[] = 'css_content = ?';
        $params[]  = $body['css_content'];
    }
    if (isset($body['js_content'])) {
        $updates[] = 'js_content = ?';
        $params[]  = $body['js_content'];
    }
    if (isset($body['is_public'])) {
        $updates[] = 'is_public = ?';
        $params[]  = $body['is_public'] ? 1 : 0;
    }

    if (empty($updates)) {
        apiError('No fields to update', 400, 'NO_UPDATES');
    }

    $updates[] = 'updated_at = NOW()';
    $params[]  = $projectId;

    $sql = 'UPDATE editor_projects SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $db->prepare($sql)->execute($params);

    // Save version snapshot if content was updated
    if (isset($body['html_content']) || isset($body['css_content']) || isset($body['js_content'])) {
        $vStmt = $db->prepare('SELECT COALESCE(MAX(version_number), 0) FROM editor_project_versions WHERE project_id = ?');
        $vStmt->execute([$projectId]);
        $nextVersion = (int) $vStmt->fetchColumn() + 1;

        $db->prepare(
            'INSERT INTO editor_project_versions (project_id, version_number, html_content, css_content, js_content, commit_message, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            $projectId,
            $nextVersion,
            $body['html_content'] ?? null,
            $body['css_content'] ?? null,
            $body['js_content'] ?? null,
            mb_substr(trim($body['commit_message'] ?? 'Auto-save'), 0, 500),
        ]);
    }

    apiSuccess(['updated' => true, 'project_id' => $projectId]);
}

// ── Delete Project ──────────────────────────────────────────────────────────
function deleteProject(array $body): void {
    $clientId = requireAuth();
    $db = getDB();
    if (!$db) apiError('Database unavailable', 503, 'DB_ERROR');

    $projectId = (int) ($body['project_id'] ?? 0);
    if ($projectId <= 0) apiError('project_id required', 400, 'MISSING_PARAM');

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM editor_projects WHERE id = ? AND user_id = ?');
    $stmt->execute([$projectId, $clientId]);
    if (!$stmt->fetch()) {
        apiError('Project not found', 404, 'NOT_FOUND');
    }

    // Delete versions first (FK), then the project
    $db->prepare('DELETE FROM editor_project_versions WHERE project_id = ?')->execute([$projectId]);
    $db->prepare('DELETE FROM editor_projects WHERE id = ? AND user_id = ?')->execute([$projectId, $clientId]);

    apiSuccess(['deleted' => true, 'project_id' => $projectId]);
}

// ── Template Starter Content ────────────────────────────────────────────────
function generateStarterHtml(string $name, string $template, string $language): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

    if (str_starts_with($template, 'vr-')) {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeName}</title>
    <script src="https://aframe.io/releases/1.5.0/aframe.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a-scene>
        <a-sky color="#1a1a2e"></a-sky>
        <a-plane position="0 0 -4" rotation="-90 0 0" width="10" height="10" color="#2d2d44"></a-plane>
        <a-box position="0 1 -3" rotation="0 45 0" color="#a855f7" shadow></a-box>
        <a-sphere position="-2 1.5 -4" radius="0.8" color="#f472b6" shadow></a-sphere>
        <a-text value="{$safeName}" position="0 3 -4" align="center" color="#fff" width="6"></a-text>
    </a-scene>
    <script src="script.js"></script>
</body>
</html>
HTML;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeName}</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="app">
        <h1>{$safeName}</h1>
        <canvas id="gameCanvas" width="800" height="600"></canvas>
    </div>
    <script src="script.js"></script>
</body>
</html>
HTML;
}

function generateStarterCss(string $template): string {
    return <<<CSS
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #0a0a1a; color: #fff; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
#app { text-align: center; }
h1 { font-size: 2rem; margin-bottom: 1rem; background: linear-gradient(135deg, #a855f7, #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
canvas { border: 1px solid rgba(255,255,255,.1); border-radius: 8px; display: block; margin: 0 auto; }
CSS;
}

function generateStarterJs(string $template, string $language): string {
    if ($language === 'webxr') {
        return <<<JS
// {$template} — WebXR Starter
// Use A-Frame components and systems to build your VR experience
document.addEventListener('DOMContentLoaded', () => {
    console.log('WebXR project loaded');
});
JS;
    }

    if (in_array($template, ['platformer', 'rpg', 'puzzle', 'cards', 'trivia'], true)) {
        return <<<JS
// Game Starter — {$template}
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');

let lastTime = 0;

function gameLoop(timestamp) {
    const dt = (timestamp - lastTime) / 1000;
    lastTime = timestamp;

    // Clear
    ctx.fillStyle = '#0a0a1a';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw placeholder
    ctx.fillStyle = '#a855f7';
    ctx.font = '24px system-ui';
    ctx.textAlign = 'center';
    ctx.fillText('Your game starts here!', canvas.width / 2, canvas.height / 2);

    requestAnimationFrame(gameLoop);
}

requestAnimationFrame(gameLoop);
JS;
    }

    return <<<JS
// Project Starter
document.addEventListener('DOMContentLoaded', () => {
    console.log('Project loaded');
    const app = document.getElementById('app');
    // Start building here
});
JS;
}
