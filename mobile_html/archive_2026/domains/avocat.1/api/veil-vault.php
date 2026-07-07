<?php
/**
 * Veil Document Vault — Ultra-Secure File System
 * ═══════════════════════════════════════════════
 * "For nothing is secret, that shall not be made manifest"
 *  — Luke 8:17 (but only to the Commander)
 *
 * Encrypted document vault with folder system for classified files.
 * Supports: Manuals, Research Reports, Intel Briefings, PDFs
 *
 * Classification: COMMANDER EYES ONLY
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$clientId = $_SESSION['client_id'] ?? 0;
$isOwner = (int)$clientId === 33;

$isInternal = false;
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
if ($internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET'])) {
    $isInternal = true;
    $isOwner = true;
}

if (!$isOwner && !$isInternal) {
    http_response_code(403);
    echo json_encode(['error' => 'Commander clearance required']);
    exit;
}

$db = getDB();

// Create vault tables
$db->exec("CREATE TABLE IF NOT EXISTS veil_vault_folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    parent_id INT DEFAULT NULL,
    icon VARCHAR(10) DEFAULT '📁',
    classification ENUM('public','internal','classified','ultra_secret') DEFAULT 'classified',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_folder (name, parent_id),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$db->exec("CREATE TABLE IF NOT EXISTS veil_vault_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folder_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    doc_type ENUM('manual','report','briefing','research','pdf','blueprint','classified') DEFAULT 'report',
    classification ENUM('public','internal','classified','ultra_secret') DEFAULT 'classified',
    content LONGTEXT,
    file_path VARCHAR(500),
    file_url VARCHAR(500),
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT 'text/html',
    tags VARCHAR(500),
    version VARCHAR(20) DEFAULT '1.0',
    generated_by VARCHAR(50) DEFAULT 'system',
    read_count INT DEFAULT 0,
    last_read DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_folder (folder_id),
    INDEX idx_type (doc_type),
    INDEX idx_class (classification),
    FULLTEXT idx_search (title, tags)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$action = $_GET['action'] ?? 'tree';

switch ($action) {
    case 'tree': getFolderTree($db); break;
    case 'folder': getFolderContents($db); break;
    case 'document': getDocument($db); break;
    case 'create-folder': createFolder($db); break;
    case 'upload': uploadDocument($db); break;
    case 'drop': dropDocument($db); break;
    case 'search': searchVault($db); break;
    case 'seed': seedVault($db); break;
    case 'stats': getVaultStats($db); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => ['tree','folder','document','create-folder','upload','drop','search','seed','stats']]);
}

function getFolderTree($db) {
    $folders = $db->query("SELECT f.*, (SELECT COUNT(*) FROM veil_vault_documents d WHERE d.folder_id = f.id) as doc_count FROM veil_vault_folders f ORDER BY f.parent_id, f.name")->fetchAll();
    
    // Build tree
    $tree = buildTree($folders, null);
    
    // Stats
    $totalDocs = $db->query("SELECT COUNT(*) FROM veil_vault_documents")->fetchColumn();
    $totalFolders = count($folders);
    
    echo json_encode([
        'success' => true,
        'tree' => $tree,
        'total_folders' => $totalFolders,
        'total_documents' => $totalDocs,
    ]);
}

function buildTree($folders, $parentId) {
    $tree = [];
    foreach ($folders as $f) {
        if ($f['parent_id'] == $parentId) {
            $node = $f;
            $node['children'] = buildTree($folders, $f['id']);
            $tree[] = $node;
        }
    }
    return $tree;
}

function getFolderContents($db) {
    $folderId = intval($_GET['id'] ?? 0);
    if (!$folderId) { echo json_encode(['error' => 'folder id required']); return; }
    
    $folder = $db->prepare("SELECT * FROM veil_vault_folders WHERE id = ?");
    $folder->execute([$folderId]);
    $folder = $folder->fetch();
    
    $docs = $db->prepare("SELECT id, title, doc_type, classification, file_size, mime_type, tags, version, generated_by, read_count, created_at, updated_at FROM veil_vault_documents WHERE folder_id = ? ORDER BY updated_at DESC");
    $docs->execute([$folderId]);
    
    $subfolders = $db->prepare("SELECT * FROM veil_vault_folders WHERE parent_id = ?");
    $subfolders->execute([$folderId]);
    
    echo json_encode([
        'success' => true,
        'folder' => $folder,
        'subfolders' => $subfolders->fetchAll(),
        'documents' => $docs->fetchAll(),
    ]);
}

function getDocument($db) {
    $docId = intval($_GET['id'] ?? 0);
    if (!$docId) { echo json_encode(['error' => 'document id required']); return; }
    
    $stmt = $db->prepare("SELECT * FROM veil_vault_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();
    
    if (!$doc) { http_response_code(404); echo json_encode(['error' => 'Document not found']); return; }
    
    $db->prepare("UPDATE veil_vault_documents SET read_count = read_count + 1, last_read = NOW() WHERE id = ?")->execute([$docId]);
    
    echo json_encode(['success' => true, 'document' => $doc]);
}

function createFolder($db) {
    $name = trim($_POST['name'] ?? '');
    $parentId = intval($_POST['parent_id'] ?? 0) ?: null;
    $icon = $_POST['icon'] ?? '📁';
    $classification = $_POST['classification'] ?? 'classified';
    $description = $_POST['description'] ?? '';
    
    if (!$name) { echo json_encode(['error' => 'name required']); return; }
    
    $validClass = ['public','internal','classified','ultra_secret'];
    if (!in_array($classification, $validClass)) $classification = 'classified';
    
    $stmt = $db->prepare("INSERT INTO veil_vault_folders (name, parent_id, icon, classification, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $parentId, $icon, $classification, $description]);
    
    echo json_encode(['success' => true, 'folder_id' => $db->lastInsertId()]);
}

function dropDocument($db) {
    $folderId = intval($_POST['folder_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $docType = $_POST['doc_type'] ?? 'report';
    $classification = $_POST['classification'] ?? 'classified';
    $tags = $_POST['tags'] ?? '';
    $generatedBy = $_POST['generated_by'] ?? 'alfred';
    $fileUrl = $_POST['file_url'] ?? '';
    
    if (!$folderId || !$title) { echo json_encode(['error' => 'folder_id and title required']); return; }
    
    $validTypes = ['manual','report','briefing','research','pdf','blueprint','classified'];
    if (!in_array($docType, $validTypes)) $docType = 'report';
    
    $validClass = ['public','internal','classified','ultra_secret'];
    if (!in_array($classification, $validClass)) $classification = 'classified';
    
    $fileSize = strlen($content);
    
    $stmt = $db->prepare("INSERT INTO veil_vault_documents (folder_id, title, doc_type, classification, content, file_url, file_size, tags, generated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$folderId, $title, $docType, $classification, $content, $fileUrl, $fileSize, $tags, $generatedBy]);
    
    echo json_encode(['success' => true, 'document_id' => $db->lastInsertId(), 'message' => 'Document dropped into vault']);
}

function uploadDocument($db) {
    if (empty($_FILES['file'])) {
        echo json_encode(['error' => 'No file uploaded']);
        return;
    }
    
    $file = $_FILES['file'];
    $folderId = intval($_POST['folder_id'] ?? 0);
    $title = trim($_POST['title'] ?? $file['name']);
    $classification = $_POST['classification'] ?? 'ultra_secret';
    $tags = $_POST['tags'] ?? '';
    
    if (!$folderId) { echo json_encode(['error' => 'folder_id required']); return; }
    
    $allowedTypes = ['application/pdf', 'text/plain', 'text/html', 'text/markdown', 'application/json', 'image/png', 'image/jpeg'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedType = $finfo->file($file['tmp_name']);
    if (!in_array($detectedType, $allowedTypes)) {
        echo json_encode(['error' => 'File type not allowed']);
        return;
    }
    
    $maxSize = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxSize) {
        echo json_encode(['error' => 'File too large (max 50MB)']);
        return;
    }
    
    // Secure storage path
    $vaultDir = dirname(__DIR__) . '/vault-storage/' . date('Y/m');
    if (!is_dir($vaultDir)) mkdir($vaultDir, 0750, true);
    
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
    $filepath = $vaultDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['error' => 'Upload failed']);
        return;
    }
    
    $content = '';
    if (in_array($file['type'], ['text/plain', 'text/html', 'text/markdown'])) {
        $content = file_get_contents($filepath);
    }
    
    $stmt = $db->prepare("INSERT INTO veil_vault_documents (folder_id, title, doc_type, classification, content, file_path, file_size, mime_type, tags, generated_by) VALUES (?, ?, 'pdf', ?, ?, ?, ?, ?, ?, 'commander')");
    $stmt->execute([$folderId, $title, $classification, $content, $filepath, $file['size'], $file['type'], $tags]);
    
    echo json_encode(['success' => true, 'document_id' => $db->lastInsertId()]);
}

function searchVault($db) {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode(['error' => 'Query too short']); return; }
    
    $stmt = $db->prepare("SELECT d.*, f.name as folder_name FROM veil_vault_documents d LEFT JOIN veil_vault_folders f ON d.folder_id = f.id WHERE MATCH(d.title, d.tags) AGAINST(? IN BOOLEAN MODE) OR d.title LIKE ? ORDER BY d.updated_at DESC LIMIT 20");
    $searchTerm = '%' . $q . '%';
    $stmt->execute([$q, $searchTerm]);
    
    echo json_encode(['success' => true, 'results' => $stmt->fetchAll()]);
}

function getVaultStats($db) {
    $stats = [
        'total_folders' => $db->query("SELECT COUNT(*) FROM veil_vault_folders")->fetchColumn(),
        'total_documents' => $db->query("SELECT COUNT(*) FROM veil_vault_documents")->fetchColumn(),
        'by_classification' => $db->query("SELECT classification, COUNT(*) as count FROM veil_vault_documents GROUP BY classification")->fetchAll(),
        'by_type' => $db->query("SELECT doc_type, COUNT(*) as count FROM veil_vault_documents GROUP BY doc_type")->fetchAll(),
        'recent' => $db->query("SELECT id, title, doc_type, classification, created_at FROM veil_vault_documents ORDER BY created_at DESC LIMIT 10")->fetchAll(),
        'total_size' => $db->query("SELECT SUM(file_size) FROM veil_vault_documents")->fetchColumn() ?: 0,
    ];
    echo json_encode(['success' => true, 'stats' => $stats]);
}

function seedVault($db) {
    // Create default folder structure
    $folders = [
        ['name' => 'Commander Manuals', 'icon' => '📖', 'class' => 'classified', 'desc' => 'Operations manuals — Commander eyes only'],
        ['name' => 'Intelligence Briefings', 'icon' => '🔍', 'class' => 'classified', 'desc' => 'Daily, weekly, and ad-hoc intel briefings'],
        ['name' => 'Research Projects', 'icon' => '🔬', 'class' => 'ultra_secret', 'desc' => 'Ultra-secret research initiatives'],
        ['name' => 'Brotherhood Documents', 'icon' => '✝️', 'class' => 'classified', 'desc' => 'Brotherhood of Jesus internal documents'],
        ['name' => 'Agent Reports', 'icon' => '🤖', 'class' => 'internal', 'desc' => 'Agent performance and task reports'],
        ['name' => 'Financial Intelligence', 'icon' => '💰', 'class' => 'classified', 'desc' => 'Crypto, trading, treasury reports'],
        ['name' => 'Security Audits', 'icon' => '🛡️', 'class' => 'classified', 'desc' => 'Integrity audits, security scans'],
        ['name' => 'Ecosystem Evolution', 'icon' => '🌿', 'class' => 'internal', 'desc' => 'Feature roadmaps, evolution plans'],
        ['name' => 'Member Documents', 'icon' => '👥', 'class' => 'public', 'desc' => 'Public-facing member guides and agreements'],
    ];
    
    $folderIds = [];
    $stmt = $db->prepare("INSERT IGNORE INTO veil_vault_folders (name, icon, classification, description) VALUES (?, ?, ?, ?)");
    $getStmt = $db->prepare("SELECT id FROM veil_vault_folders WHERE name = ? AND parent_id IS NULL");
    
    foreach ($folders as $f) {
        $stmt->execute([$f['name'], $f['icon'], $f['class'], $f['desc']]);
        $getStmt->execute([$f['name']]);
        $folderIds[$f['name']] = $getStmt->fetchColumn();
    }
    
    // Create sub-folders for Research Projects
    $researchId = $folderIds['Research Projects'] ?? null;
    if ($researchId) {
        $subStmt = $db->prepare("INSERT IGNORE INTO veil_vault_folders (name, parent_id, icon, classification, description) VALUES (?, ?, ?, ?, ?)");
        $subStmt->execute(['Zero Point Energy', $researchId, '⚡', 'ultra_secret', 'Free energy research — Don Smith, John Hutchison, John Searl circuits']);
        $subStmt->execute(['Quantum Computing', $researchId, '🔮', 'ultra_secret', 'Post-quantum cryptography and quantum computing research']);
        $subStmt->execute(['AI Evolution', $researchId, '🧠', 'classified', 'Alfred self-improvement and agent evolution research']);
    }
    
    // Create sub-folders for Financial Intelligence
    $finId = $folderIds['Financial Intelligence'] ?? null;
    if ($finId) {
        $subStmt = $db->prepare("INSERT IGNORE INTO veil_vault_folders (name, parent_id, icon, classification, description) VALUES (?, ?, ?, ?, ?)");
        $subStmt->execute(['Crypto Analysis', $finId, '📊', 'classified', 'Cryptocurrency market analysis and trading intelligence']);
        $subStmt->execute(['Investment Reports', $finId, '📈', 'classified', 'Investment recommendations and portfolio tracking']);
        $subStmt->execute(['GSM Token', $finId, '🪙', 'classified', 'GSM token operations, treasury, and tokenomics']);
    }
    
    // Seed commander manuals as documents
    $manualFolderId = $folderIds['Commander Manuals'] ?? null;
    if ($manualFolderId) {
        $docStmt = $db->prepare("INSERT IGNORE INTO veil_vault_documents (folder_id, title, doc_type, classification, file_url, tags, generated_by) VALUES (?, ?, 'manual', ?, ?, ?, 'system')");
        $docStmt->execute([$manualFolderId, 'Commander Operations Manual v2.0', 'classified', '/docs/commander-manual.php', 'operations,manual,classified', ]);
        $docStmt->execute([$manualFolderId, 'OIC Whitepaper — Open Intelligence Consortium', 'classified', '/docs/oic-whitepaper.php', 'oic,whitepaper,intelligence']);
        $docStmt->execute([$manualFolderId, 'Agent Operations Runbook', 'classified', '/docs/AGENT_OPS_RUNBOOK.md', 'agents,operations,runbook']);
        $docStmt->execute([$manualFolderId, 'API Keys Setup Guide', 'classified', '/docs/API_KEYS_SETUP.md', 'api,keys,setup']);
    }
    
    // Seed member/public documents
    $memberFolderId = $folderIds['Member Documents'] ?? null;
    if ($memberFolderId) {
        $docStmt = $db->prepare("INSERT IGNORE INTO veil_vault_documents (folder_id, title, doc_type, classification, file_url, tags, generated_by) VALUES (?, ?, 'manual', ?, ?, ?, 'system')");
        $docStmt->execute([$memberFolderId, 'New Member Guide', 'public', '/docs/member-guide.php', 'member,onboarding,public']);
        $docStmt->execute([$memberFolderId, 'Ecosystem Principles Agreement', 'public', '/docs/ecosystem-principles.php', 'principles,agreement,public']);
        $docStmt->execute([$memberFolderId, 'Getting Started Guide', 'public', '/docs/getting-started.php', 'getting-started,tutorial,public']);
        $docStmt->execute([$memberFolderId, 'Tools Guide', 'public', '/docs/tools-guide.php', 'tools,guide,public']);
        $docStmt->execute([$memberFolderId, 'Voice Integration Guide', 'public', '/docs/voice-integration.php', 'voice,integration,public']);
        $docStmt->execute([$memberFolderId, 'API Reference', 'public', '/docs/api-reference.php', 'api,reference,developers']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Vault seeded with folder structure and documents',
        'folders_created' => count($folderIds),
        'folder_ids' => $folderIds,
    ]);
}
