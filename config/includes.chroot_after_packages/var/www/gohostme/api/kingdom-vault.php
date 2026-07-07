<?php
/**
 * Kingdom Vault API — Sovereign File Management
 * ──────────────────────────────────────────────
 * Per-user encrypted file vault. Each user gets isolated storage.
 * Commander (client_id 33) has full access. Other users get quota-limited storage.
 *
 * Endpoints:
 *   GET  ?action=list&path=/          List files/folders in a directory
 *   GET  ?action=download&path=/x.pdf Download a file
 *   GET  ?action=preview&path=/x.txt  Preview file content (text/images)
 *   GET  ?action=search&q=term        Search files by name
 *   GET  ?action=stats                Storage usage stats
 *   POST ?action=upload               Upload file(s)
 *   POST ?action=mkdir                Create a folder
 *   POST ?action=rename               Rename a file/folder
 *   POST ?action=delete               Delete a file/folder
 *   POST ?action=star                 Toggle star on a file
 *   POST ?action=index                Rebuild index from disk (Commander only)
 */

define('GOSITEME_API', true);
$GLOBALS['RATE_LIMIT_EXEMPT'] = false;
$GLOBALS['CSRF_EXEMPT'] = true;

require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/db-config.inc.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Auth ────────────────────────────────────────────────────────────
$clientId = (int)($_SESSION['client_id'] ?? 0);
if (!$clientId) {
    // Also check IDE session token
    $token = $_COOKIE['alfred_ide_token'] ?? $_SERVER['HTTP_X_VAULT_TOKEN'] ?? $_GET['token'] ?? '';
    if ($token) {
        $hash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT ide_user_id, client_id FROM ide_sessions WHERE session_token = ? AND expires_at > NOW()");
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $clientId = (int)$row['client_id'];
        }
    }
}
if (!$clientId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$isCommander = ($clientId === 33);

// ── Storage paths & quotas ──────────────────────────────────────────
$VAULT_ROOT = '/home/gositeme/vault-storage';
$userVaultDir = $VAULT_ROOT . '/' . $clientId;

// Create user vault if it doesn't exist
if (!is_dir($userVaultDir)) {
    mkdir($userVaultDir, 0700, true);
}

// Quotas: Commander = unlimited, others = 5GB default
$QUOTA_BYTES = $isCommander ? PHP_INT_MAX : 5 * 1024 * 1024 * 1024;

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// ── Helpers ─────────────────────────────────────────────────────────

function sanitizePath(string $path): string {
    // Normalize path separators, remove double slashes
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#/+#', '/', $path);
    
    // Remove any path traversal attempts
    $parts = explode('/', $path);
    $safe = [];
    foreach ($parts as $part) {
        if ($part === '..' || $part === '') continue;
        if ($part === '.') continue;
        $safe[] = $part;
    }
    return '/' . implode('/', $safe);
}

function getAbsPath(string $vaultDir, string $relativePath): string {
    $clean = sanitizePath($relativePath);
    $abs = realpath($vaultDir . $clean);
    if ($abs === false) {
        // File/dir doesn't exist yet — construct it safely
        $abs = $vaultDir . $clean;
    }
    // Security: ensure it's within the vault directory
    if (strpos($abs, $vaultDir) !== 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied — path traversal blocked']);
        exit;
    }
    return $abs;
}

function getMimeType(string $path): string {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $map = [
        'txt' => 'text/plain', 'md' => 'text/markdown', 'json' => 'application/json',
        'php' => 'text/x-php', 'js' => 'text/javascript', 'css' => 'text/css',
        'html' => 'text/html', 'htm' => 'text/html', 'xml' => 'text/xml',
        'csv' => 'text/csv', 'sh' => 'text/x-shellscript', 'py' => 'text/x-python',
        'pdf' => 'application/pdf', 'zip' => 'application/zip',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp',
        'mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'flac' => 'audio/flac',
        'wav' => 'audio/wav', 'mp4' => 'video/mp4', 'webm' => 'video/webm',
        'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    return $map[$ext] ?? (function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream');
}

function formatSize(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function dirSizeRecursive(string $dir): int {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $f) {
        if ($f->isFile()) $size += $f->getSize();
    }
    return $size;
}

// ── Actions ─────────────────────────────────────────────────────────

try {
    switch ($action) {

        // ── LIST ────────────────────────────────────────────────
        case 'list':
            $path = sanitizePath($_GET['path'] ?? '/');
            $absDir = getAbsPath($userVaultDir, $path);
            
            if (!is_dir($absDir)) {
                echo json_encode(['error' => 'Directory not found', 'path' => $path]);
                exit;
            }
            
            $entries = [];
            $items = scandir($absDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $fullPath = $absDir . '/' . $item;
                $relPath = rtrim($path, '/') . '/' . $item;
                
                $entry = [
                    'name' => $item,
                    'path' => $relPath,
                    'type' => is_dir($fullPath) ? 'folder' : 'file',
                    'modified' => date('c', filemtime($fullPath)),
                ];
                
                if (is_dir($fullPath)) {
                    $entry['children'] = count(array_diff(scandir($fullPath), ['.', '..']));
                } else {
                    $entry['size'] = filesize($fullPath);
                    $entry['size_human'] = formatSize($entry['size']);
                    $entry['mime'] = getMimeType($fullPath);
                }
                
                $entries[] = $entry;
            }
            
            // Sort: folders first, then alphabetically
            usort($entries, function($a, $b) {
                if ($a['type'] !== $b['type']) return $a['type'] === 'folder' ? -1 : 1;
                return strcasecmp($a['name'], $b['name']);
            });
            
            // Breadcrumb
            $parts = array_filter(explode('/', $path));
            $breadcrumb = [['name' => 'Vault', 'path' => '/']];
            $buildPath = '';
            foreach ($parts as $p) {
                $buildPath .= '/' . $p;
                $breadcrumb[] = ['name' => $p, 'path' => $buildPath];
            }
            
            echo json_encode([
                'path' => $path,
                'breadcrumb' => $breadcrumb,
                'entries' => $entries,
                'count' => count($entries),
            ]);
            break;

        // ── DOWNLOAD ────────────────────────────────────────────
        case 'download':
            $path = sanitizePath($_GET['path'] ?? '');
            $absFile = getAbsPath($userVaultDir, $path);
            
            if (!is_file($absFile)) {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
                exit;
            }
            
            $mime = getMimeType($absFile);
            $name = basename($absFile);
            
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
            header('Content-Length: ' . filesize($absFile));
            header('Cache-Control: private, max-age=3600');
            readfile($absFile);
            exit;

        // ── PREVIEW ─────────────────────────────────────────────
        case 'preview':
            $path = sanitizePath($_GET['path'] ?? '');
            $absFile = getAbsPath($userVaultDir, $path);
            
            if (!is_file($absFile)) {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
                exit;
            }
            
            $mime = getMimeType($absFile);
            $size = filesize($absFile);
            
            // Text preview (up to 500KB)
            if (strpos($mime, 'text/') === 0 || in_array($mime, ['application/json', 'text/markdown'])) {
                $content = file_get_contents($absFile, false, null, 0, 512000);
                $truncated = $size > 512000;
                echo json_encode([
                    'type' => 'text',
                    'mime' => $mime,
                    'size' => $size,
                    'size_human' => formatSize($size),
                    'content' => mb_convert_encoding($content, 'UTF-8', 'UTF-8'),
                    'truncated' => $truncated,
                    'name' => basename($absFile),
                ]);
            }
            // Image preview (serve directly)
            elseif (strpos($mime, 'image/') === 0) {
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . $size);
                header('Cache-Control: private, max-age=3600');
                readfile($absFile);
                exit;
            }
            // Audio preview
            elseif (strpos($mime, 'audio/') === 0) {
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . $size);
                header('Accept-Ranges: bytes');
                readfile($absFile);
                exit;
            }
            // PDF — serve inline
            elseif ($mime === 'application/pdf') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . addslashes(basename($absFile)) . '"');
                header('Content-Length: ' . $size);
                readfile($absFile);
                exit;
            }
            else {
                echo json_encode([
                    'type' => 'binary',
                    'mime' => $mime,
                    'size' => $size,
                    'size_human' => formatSize($size),
                    'name' => basename($absFile),
                    'message' => 'Preview not available for this file type',
                ]);
            }
            break;

        // ── SEARCH ──────────────────────────────────────────────
        case 'search':
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 2) {
                echo json_encode(['error' => 'Search query too short (min 2 chars)']);
                exit;
            }
            
            $results = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($userVaultDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            $qLower = strtolower($q);
            $count = 0;
            foreach ($iterator as $file) {
                if ($count >= 100) break;
                $name = $file->getFilename();
                if (stripos($name, $q) !== false) {
                    $relPath = str_replace($userVaultDir, '', $file->getPathname());
                    $entry = [
                        'name' => $name,
                        'path' => $relPath,
                        'type' => $file->isDir() ? 'folder' : 'file',
                        'modified' => date('c', $file->getMTime()),
                    ];
                    if ($file->isFile()) {
                        $entry['size'] = $file->getSize();
                        $entry['size_human'] = formatSize($entry['size']);
                        $entry['mime'] = getMimeType($file->getPathname());
                    }
                    $results[] = $entry;
                    $count++;
                }
            }
            
            echo json_encode([
                'query' => $q,
                'results' => $results,
                'count' => count($results),
            ]);
            break;

        // ── STATS ───────────────────────────────────────────────
        case 'stats':
            $totalSize = dirSizeRecursive($userVaultDir);
            $fileCount = 0;
            $folderCount = 0;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($userVaultDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $f) {
                if ($f->isDir()) $folderCount++;
                else $fileCount++;
            }
            
            // Top-level folder sizes
            $folders = [];
            foreach (scandir($userVaultDir) as $item) {
                if ($item === '.' || $item === '..') continue;
                $p = $userVaultDir . '/' . $item;
                if (is_dir($p)) {
                    $folders[] = [
                        'name' => $item,
                        'size' => dirSizeRecursive($p),
                        'size_human' => formatSize(dirSizeRecursive($p)),
                    ];
                }
            }
            
            echo json_encode([
                'total_size' => $totalSize,
                'total_size_human' => formatSize($totalSize),
                'file_count' => $fileCount,
                'folder_count' => $folderCount,
                'quota' => $QUOTA_BYTES < PHP_INT_MAX ? $QUOTA_BYTES : null,
                'quota_human' => $QUOTA_BYTES < PHP_INT_MAX ? formatSize($QUOTA_BYTES) : 'Unlimited',
                'usage_percent' => $QUOTA_BYTES < PHP_INT_MAX ? round(($totalSize / $QUOTA_BYTES) * 100, 1) : 0,
                'folders' => $folders,
                'is_commander' => $isCommander,
            ]);
            break;

        // ── UPLOAD ──────────────────────────────────────────────
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST required']);
                exit;
            }
            
            $targetDir = sanitizePath($_POST['path'] ?? '/');
            $absDir = getAbsPath($userVaultDir, $targetDir);
            
            if (!is_dir($absDir)) {
                mkdir($absDir, 0700, true);
            }
            
            // Check quota
            if (!$isCommander) {
                $currentSize = dirSizeRecursive($userVaultDir);
                $uploadSize = 0;
                foreach ($_FILES['files']['size'] ?? [] as $s) $uploadSize += $s;
                if (($currentSize + $uploadSize) > $QUOTA_BYTES) {
                    http_response_code(413);
                    echo json_encode(['error' => 'Storage quota exceeded', 'used' => formatSize($currentSize), 'quota' => formatSize($QUOTA_BYTES)]);
                    exit;
                }
            }
            
            $uploaded = [];
            $files = $_FILES['files'] ?? [];
            if (!empty($files['name'])) {
                $count = is_array($files['name']) ? count($files['name']) : 1;
                for ($i = 0; $i < $count; $i++) {
                    $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                    $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                    $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                    
                    if ($error !== UPLOAD_ERR_OK) continue;
                    
                    // Sanitize filename
                    $safeName = preg_replace('/[^\w\s\-\.\(\)]/', '_', $name);
                    $safeName = trim($safeName);
                    if (empty($safeName)) $safeName = 'unnamed_' . time();
                    
                    $dest = $absDir . '/' . $safeName;
                    
                    // Don't overwrite — append number
                    if (file_exists($dest)) {
                        $base = pathinfo($safeName, PATHINFO_FILENAME);
                        $ext = pathinfo($safeName, PATHINFO_EXTENSION);
                        $n = 1;
                        while (file_exists($dest)) {
                            $dest = $absDir . '/' . $base . '_' . $n . ($ext ? '.' . $ext : '');
                            $n++;
                        }
                        $safeName = basename($dest);
                    }
                    
                    if (move_uploaded_file($tmpName, $dest)) {
                        chmod($dest, 0600);
                        $uploaded[] = [
                            'name' => $safeName,
                            'path' => rtrim($targetDir, '/') . '/' . $safeName,
                            'size' => $size,
                            'size_human' => formatSize($size),
                        ];
                    }
                }
            }
            
            echo json_encode(['uploaded' => $uploaded, 'count' => count($uploaded)]);
            break;

        // ── MKDIR ───────────────────────────────────────────────
        case 'mkdir':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $path = sanitizePath($input['path'] ?? '/');
            $name = preg_replace('/[^\w\s\-\.\(\)]/', '_', $input['name'] ?? '');
            
            if (empty($name)) {
                echo json_encode(['error' => 'Folder name required']);
                exit;
            }
            
            $absDir = getAbsPath($userVaultDir, $path . '/' . $name);
            
            if (is_dir($absDir)) {
                echo json_encode(['error' => 'Folder already exists']);
                exit;
            }
            
            mkdir($absDir, 0700, true);
            echo json_encode(['ok' => true, 'path' => rtrim($path, '/') . '/' . $name]);
            break;

        // ── RENAME ──────────────────────────────────────────────
        case 'rename':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $oldPath = sanitizePath($input['path'] ?? '');
            $newName = preg_replace('/[^\w\s\-\.\(\)]/', '_', $input['new_name'] ?? '');
            
            if (empty($newName)) {
                echo json_encode(['error' => 'New name required']);
                exit;
            }
            
            $absOld = getAbsPath($userVaultDir, $oldPath);
            if (!file_exists($absOld)) {
                echo json_encode(['error' => 'File/folder not found']);
                exit;
            }
            
            $parentDir = dirname($absOld);
            $absNew = $parentDir . '/' . $newName;
            
            // Security check
            if (strpos(realpath($parentDir) . '/' . $newName, $userVaultDir) !== 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            rename($absOld, $absNew);
            echo json_encode(['ok' => true, 'new_path' => dirname($oldPath) . '/' . $newName]);
            break;

        // ── DELETE ──────────────────────────────────────────────
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $path = sanitizePath($input['path'] ?? '');
            
            if ($path === '/' || $path === '') {
                echo json_encode(['error' => 'Cannot delete vault root']);
                exit;
            }
            
            $absPath = getAbsPath($userVaultDir, $path);
            if (!file_exists($absPath)) {
                echo json_encode(['error' => 'Not found']);
                exit;
            }
            
            if (is_dir($absPath)) {
                // Recursive delete
                $it = new RecursiveDirectoryIterator($absPath, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $f) {
                    if ($f->isDir()) rmdir($f->getRealPath());
                    else unlink($f->getRealPath());
                }
                rmdir($absPath);
            } else {
                unlink($absPath);
            }
            
            echo json_encode(['ok' => true, 'deleted' => $path]);
            break;

        // ── STAR ────────────────────────────────────────────────
        case 'star':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $path = sanitizePath($input['path'] ?? '');
            
            // Store starred status in DB
            $stmt = $pdo->prepare("SELECT id, starred FROM kingdom_vault_entries WHERE client_id = ? AND path = ?");
            $stmt->execute([$clientId, $path]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $newStarred = $row['starred'] ? 0 : 1;
                $pdo->prepare("UPDATE kingdom_vault_entries SET starred = ? WHERE id = ?")->execute([$newStarred, $row['id']]);
            } else {
                $absPath = getAbsPath($userVaultDir, $path);
                $name = basename($absPath);
                $pdo->prepare("INSERT INTO kingdom_vault_entries (client_id, name, path, parent_path, entry_type, starred) VALUES (?, ?, ?, ?, ?, 1)")
                    ->execute([$clientId, $name, $path, dirname($path), is_dir($absPath) ? 'folder' : 'file']);
                $newStarred = 1;
            }
            
            echo json_encode(['ok' => true, 'path' => $path, 'starred' => (bool)$newStarred]);
            break;

        // ═══ INDEX — Rebuild DB index from disk (Commander only) ═══
        case 'index':
            if (!$isCommander) {
                http_response_code(403);
                echo json_encode(['error' => 'Commander only']);
                break;
            }

            $pdo->prepare("DELETE FROM kingdom_vault_entries WHERE client_id = ? AND starred = 0")->execute([$clientId]);
            
            $indexed = 0;
            $mimeMap = [
                'pdf' => 'application/pdf', 'txt' => 'text/plain', 'md' => 'text/markdown',
                'php' => 'text/x-php', 'js' => 'text/javascript', 'json' => 'application/json',
                'html' => 'text/html', 'css' => 'text/css', 'xml' => 'application/xml',
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
                'mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'flac' => 'audio/flac', 'wav' => 'audio/wav',
                'mp4' => 'video/mp4', 'webm' => 'video/webm',
                'zip' => 'application/zip', 'rar' => 'application/x-rar-compressed',
                'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];

            $stmt = $pdo->prepare("INSERT INTO kingdom_vault_entries (client_id, name, path, parent_path, entry_type, file_size, mime_type, disk_path, sha256_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE file_size=VALUES(file_size), mime_type=VALUES(mime_type), updated_at=NOW()");

            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($userVaultDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($rii as $file) {
                $absPath = $file->getPathname();
                $relPath = '/' . ltrim(str_replace($userVaultDir, '', $absPath), '/');
                $parentPath = dirname($relPath);
                if ($parentPath === '.') $parentPath = '/';
                $name = $file->getBasename();
                $isDir = $file->isDir();
                $size = $isDir ? 0 : $file->getSize();
                $ext = strtolower($file->getExtension());
                $mime = $isDir ? 'directory' : ($mimeMap[$ext] ?? 'application/octet-stream');
                $hash = null;
                if (!$isDir && $size < 104857600) { // skip hash for files > 100MB
                    $hash = @hash_file('sha256', $absPath);
                }
                
                $stmt->execute([
                    $clientId, $name, $relPath, $parentPath,
                    $isDir ? 'folder' : 'file',
                    $size, $mime, $absPath, $hash
                ]);
                $indexed++;
            }

            echo json_encode(['ok' => true, 'indexed' => $indexed, 'client_id' => $clientId]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action: ' . $action]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error', 'message' => $isCommander ? $e->getMessage() : 'Contact support']);
}
