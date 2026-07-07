<?php
/**
 * GoCodeMe Editor - Projects API
 * CRUD operations for user projects
 */

require_once dirname(__DIR__) . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
// CSRF protection
require_once dirname(dirname(__DIR__)) . '/includes/api-security.php';

// CORS headers
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get current user
$user = getCurrentUser();
$isGuest = !$user && ALLOW_GUEST_PREVIEW;

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Guest restrictions
if ($isGuest && !in_array($action, ['list_templates', 'get_template'])) {
    jsonResponse([
        'error' => 'Login required to manage projects',
        'login_url' => BILLING_URL . '/clientarea.php'
    ], 401);
}

$pdo = getDB();

switch ($action) {
    // ===================================
    // LIST USER'S PROJECTS
    // ===================================
    case 'list':
        if ($method !== 'GET') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                id, name, slug, description, thumbnail,
                is_public, is_published, published_url, published_at,
                created_at, updated_at
            FROM editor_projects 
            WHERE user_id = ?
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$user['id']]);
        $projects = $stmt->fetchAll();
        
        jsonResponse([
            'projects' => $projects,
            'count' => count($projects),
            'limit' => $user['is_premium'] ? MAX_PROJECTS_PAID : MAX_PROJECTS_FREE
        ]);
        break;
    
    // ===================================
    // GET SINGLE PROJECT
    // ===================================
    case 'get':
        if ($method !== 'GET') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        if (!$projectId) {
            jsonResponse(['error' => 'Project ID required'], 400);
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM editor_projects 
            WHERE id = ? AND (user_id = ? OR is_public = 1)
        ");
        $stmt->execute([$projectId, $user['id']]);
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(['error' => 'Project not found'], 404);
        }
        
        jsonResponse(['project' => $project]);
        break;
    
    // ===================================
    // CREATE NEW PROJECT
    // ===================================
    case 'create':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        // Check project limit
        $permission = checkPermission('create_project');
        if (!$permission) {
            jsonResponse([
                'error' => 'Project limit reached. Upgrade to create more projects.',
                'upgrade_url' => BILLING_URL . '/cart.php'
            ], 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? 'Untitled Project');
        $description = trim($input['description'] ?? '');
        $html = $input['html'] ?? '';
        $css = $input['css'] ?? '';
        $js = $input['js'] ?? '';
        
        // Generate unique slug
        $slug = generateSlug($name, function($s) use ($pdo, $user) {
            $stmt = $pdo->prepare("SELECT id FROM editor_projects WHERE slug = ? AND user_id = ?");
            $stmt->execute([$s, $user['id']]);
            return $stmt->fetch() !== false;
        });
        
        $stmt = $pdo->prepare("
            INSERT INTO editor_projects 
            (user_id, name, slug, description, html_content, css_content, js_content)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $name,
            $slug,
            $description,
            $html,
            $css,
            $js
        ]);
        
        $newId = $pdo->lastInsertId();
        
        // Create project directory
        $projectDir = PROJECTS_PATH . '/' . $user['id'] . '/' . $newId;
        if (!is_dir($projectDir)) {
            mkdir($projectDir, 0755, true);
        }
        
        // Save files
        file_put_contents($projectDir . '/index.html', $html);
        file_put_contents($projectDir . '/styles.css', $css);
        file_put_contents($projectDir . '/script.js', $js);
        
        jsonResponse([
            'success' => true,
            'project' => [
                'id' => $newId,
                'name' => $name,
                'slug' => $slug
            ]
        ], 201);
        break;
    
    // ===================================
    // UPDATE PROJECT
    // ===================================
    case 'update':
        if ($method !== 'PUT' && $method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        if (!$projectId) {
            jsonResponse(['error' => 'Project ID required'], 400);
        }
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT * FROM editor_projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, $user['id']]);
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(['error' => 'Project not found'], 404);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        $allowedFields = ['name', 'description', 'html_content', 'css_content', 'js_content', 'is_public'];
        $fieldMap = [
            'html' => 'html_content',
            'css' => 'css_content',
            'js' => 'js_content'
        ];
        
        foreach ($input as $key => $value) {
            $dbKey = $fieldMap[$key] ?? $key;
            if (in_array($dbKey, $allowedFields)) {
                $updates[] = "$dbKey = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            jsonResponse(['error' => 'No valid fields to update'], 400);
        }
        
        $params[] = $projectId;
        $params[] = $user['id'];
        
        $sql = "UPDATE editor_projects SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Update files on disk
        $projectDir = PROJECTS_PATH . '/' . $user['id'] . '/' . $projectId;
        if (!is_dir($projectDir)) {
            mkdir($projectDir, 0755, true);
        }
        
        if (isset($input['html'])) {
            file_put_contents($projectDir . '/index.html', $input['html']);
        }
        if (isset($input['css'])) {
            file_put_contents($projectDir . '/styles.css', $input['css']);
        }
        if (isset($input['js'])) {
            file_put_contents($projectDir . '/script.js', $input['js']);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Project updated'
        ]);
        break;
    
    // ===================================
    // DELETE PROJECT
    // ===================================
    case 'delete':
        if ($method !== 'DELETE' && $method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        if (!$projectId) {
            jsonResponse(['error' => 'Project ID required'], 400);
        }
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM editor_projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, $user['id']]);
        
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Project not found'], 404);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM editor_projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, $user['id']]);
        
        // Delete versions
        $stmt = $pdo->prepare("DELETE FROM editor_project_versions WHERE project_id = ?");
        $stmt->execute([$projectId]);
        
        // Delete files
        $projectDir = PROJECTS_PATH . '/' . $user['id'] . '/' . $projectId;
        if (is_dir($projectDir)) {
            array_map('unlink', glob("$projectDir/*"));
            rmdir($projectDir);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Project deleted'
        ]);
        break;
    
    // ===================================
    // SAVE VERSION (for undo/history)
    // ===================================
    case 'save_version':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        if (!$projectId) {
            jsonResponse(['error' => 'Project ID required'], 400);
        }
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT * FROM editor_projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, $user['id']]);
        $project = $stmt->fetch();
        
        if (!$project) {
            jsonResponse(['error' => 'Project not found'], 404);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? 'Auto-save');
        
        // Get next version number
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(version_number), 0) + 1 as next_version 
            FROM editor_project_versions WHERE project_id = ?
        ");
        $stmt->execute([$projectId]);
        $nextVersion = $stmt->fetch()['next_version'];
        
        // Save version
        $stmt = $pdo->prepare("
            INSERT INTO editor_project_versions 
            (project_id, version_number, html_content, css_content, js_content, commit_message)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $projectId,
            $nextVersion,
            $project['html_content'],
            $project['css_content'],
            $project['js_content'],
            $message
        ]);
        
        // Keep only last 20 versions
        $stmt = $pdo->prepare("
            DELETE FROM editor_project_versions 
            WHERE project_id = ? AND version_number < (
                SELECT MIN(v.version_number) FROM (
                    SELECT version_number FROM editor_project_versions 
                    WHERE project_id = ? ORDER BY version_number DESC LIMIT 20
                ) v
            )
        ");
        $stmt->execute([$projectId, $projectId]);
        
        jsonResponse([
            'success' => true,
            'version' => $nextVersion
        ]);
        break;
    
    // ===================================
    // LIST TEMPLATES
    // ===================================
    case 'list_templates':
        $category = $_GET['category'] ?? null;
        
        $sql = "SELECT id, name, slug, category, description, thumbnail, is_premium FROM editor_templates";
        $params = [];
        
        if ($category) {
            $sql .= " WHERE category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY sort_order, name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $templates = $stmt->fetchAll();
        
        jsonResponse(['templates' => $templates]);
        break;
    
    // ===================================
    // GET TEMPLATE
    // ===================================
    case 'get_template':
        $templateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : null;
        $templateSlug = $_GET['template_slug'] ?? null;
        
        if (!$templateId && !$templateSlug) {
            jsonResponse(['error' => 'Template ID or slug required'], 400);
        }
        
        if ($templateId) {
            $stmt = $pdo->prepare("SELECT * FROM editor_templates WHERE id = ?");
            $stmt->execute([$templateId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM editor_templates WHERE slug = ?");
            $stmt->execute([$templateSlug]);
        }
        
        $template = $stmt->fetch();
        
        if (!$template) {
            jsonResponse(['error' => 'Template not found'], 404);
        }
        
        // Check premium access
        if ($template['is_premium'] && (!$user || !$user['is_premium'])) {
            jsonResponse([
                'error' => 'Premium template requires hosting subscription',
                'upgrade_url' => BILLING_URL . '/cart.php'
            ], 403);
        }
        
        jsonResponse(['template' => $template]);
        break;
    
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
