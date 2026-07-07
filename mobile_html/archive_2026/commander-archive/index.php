<?php
/**
 * Commander Archive — Searchable Web Interface
 * Built by Alfred for Commander Danny William Perez
 * 
 * Access: https://gositeme.com/commander-archive/
 * This provides a simple searchable interface to the Commander's personal archive.
 */

require_once dirname(__DIR__) . '/includes/commander-guard.inc.php';
require_commander_or_404();

defined('GOSITEME_API') || define('GOSITEME_API', true);

$archiveDir = '/home/gositeme/commander-archive';
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['cat']) ? trim($_GET['cat']) : '';

// Category definitions (matching the index builder)
$categoryLabels = [
    'alfred_docs'   => 'Alfred — Vision & Architecture',
    'alfred_logs'   => 'Alfred — Conversation Logs',
    'faith'         => 'Faith & Scripture',
    'music'         => 'Elyon Light — Worship Music',
    'legal'         => 'Legal Documents & Cases',
    'personal_docs' => 'Personal Documents',
    'veil'          => 'Veil Firewall',
    'research'      => 'Research & Knowledge',
    'gositeme'      => 'GoSiteMe — Platform',
    'images'        => 'Images & Artwork',
    'videos'        => 'Videos & Media',
    'todo_notes'    => 'Todo Lists & Notes',
    'other'         => 'Other Files',
];

// File search function
function searchFiles($dir, $query, $skipDirs = ['node_modules', '.git', 'extracted', 'LiberteMemeEnPrison']) {
    $results = [];
    $items = @scandir($dir);
    if (!$items) return $results;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = "$dir/$item";
        
        if (is_dir($path)) {
            if (in_array(basename($path), $skipDirs)) continue;
            $results = array_merge($results, searchFiles($path, $query, $skipDirs));
        } else {
            if (basename($path) === 'Desktop.zip') continue;
            
            $name = basename($path);
            $nameLower = strtolower($name);
            $queryLower = strtolower($query);
            
            if (empty($query) || strpos($nameLower, $queryLower) !== false) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $size = @filesize($path);
                $mod = @filemtime($path);
                
                $results[] = [
                    'name' => $name,
                    'path' => str_replace($dir . '/', '', $path),
                    'size' => $size,
                    'ext'  => $ext,
                    'modified' => $mod ? date('Y-m-d', $mod) : 'unknown',
                ];
            }
        }
    }
    return $results;
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function getFileIcon($ext) {
    $icons = [
        'pdf' => '📄', 'txt' => '📝', 'md' => '📋',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'mp3' => '🎵', 'wav' => '🎵', 'flac' => '🎵',
        'mp4' => '🎬', 'avi' => '🎬', 'mkv' => '🎬',
        'zip' => '📦', 'rar' => '📦',
        'php' => '💻', 'js' => '💻', 'html' => '🌐', 'htm' => '🌐',
        'sh' => '⚙️',
    ];
    return $icons[$ext] ?? '📁';
}

// Do search if query provided
$results = [];
if (!empty($query)) {
    $results = searchFiles($archiveDir, $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander's Archive — Alfred</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: #0a0a14; color: #e0e0e0; 
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(135deg, #12121f 0%, #1a1a2e 100%);
            border-bottom: 1px solid #222240;
            padding: 20px 30px;
            text-align: center;
        }
        .header h1 { 
            color: #00D4FF; font-size: 28px; margin-bottom: 5px;
            text-shadow: 0 0 20px rgba(0,212,255,0.3);
        }
        .header p { color: #888; font-size: 14px; }
        .search-bar {
            max-width: 800px; margin: 30px auto; padding: 0 20px;
        }
        .search-bar form { display: flex; gap: 10px; }
        .search-bar input[type="text"] {
            flex: 1; padding: 15px 20px; border-radius: 12px;
            border: 1px solid #333; background: #12121f; color: #fff;
            font-size: 18px; outline: none;
            transition: border-color 0.3s;
        }
        .search-bar input[type="text"]:focus { border-color: #00D4FF; }
        .search-bar button {
            padding: 15px 30px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, #00D4FF, #7D00FF);
            color: #fff; font-size: 16px; cursor: pointer;
            font-weight: 600; transition: opacity 0.3s;
        }
        .search-bar button:hover { opacity: 0.85; }
        .categories {
            max-width: 800px; margin: 20px auto; padding: 0 20px;
            display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;
        }
        .cat-btn {
            padding: 8px 16px; border-radius: 20px; border: 1px solid #333;
            background: #1a1a2e; color: #aaa; font-size: 13px; cursor: pointer;
            text-decoration: none; transition: all 0.3s;
        }
        .cat-btn:hover, .cat-btn.active { 
            border-color: #00D4FF; color: #00D4FF; background: #12121f; 
        }
        .results {
            max-width: 800px; margin: 20px auto; padding: 0 20px;
        }
        .results h2 { color: #00D4FF; margin-bottom: 15px; font-size: 18px; }
        .file-card {
            background: #12121f; border: 1px solid #222240; border-radius: 10px;
            padding: 15px 20px; margin-bottom: 10px;
            display: flex; align-items: center; gap: 15px;
            transition: border-color 0.3s;
        }
        .file-card:hover { border-color: #00D4FF; }
        .file-icon { font-size: 28px; }
        .file-info { flex: 1; }
        .file-name { color: #fff; font-size: 16px; font-weight: 500; }
        .file-meta { color: #666; font-size: 13px; margin-top: 4px; }
        .file-path { color: #555; font-size: 12px; margin-top: 2px; }
        .highlight { color: #00D4FF; font-weight: 600; }
        .stats {
            max-width: 800px; margin: 20px auto; padding: 0 20px;
            text-align: center; color: #555; font-size: 14px;
        }
        .quick-links {
            max-width: 800px; margin: 30px auto; padding: 0 20px;
        }
        .quick-links h3 { color: #7D00FF; margin-bottom: 15px; font-size: 16px; }
        .link-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
        .quick-card {
            background: #12121f; border: 1px solid #222240; border-radius: 10px;
            padding: 15px; cursor: pointer; transition: all 0.3s;
            text-decoration: none; color: inherit;
        }
        .quick-card:hover { border-color: #7D00FF; transform: translateY(-2px); }
        .quick-card h4 { color: #00D4FF; font-size: 14px; margin-bottom: 5px; }
        .quick-card p { color: #666; font-size: 12px; }
        .eden-note {
            max-width: 800px; margin: 40px auto; padding: 30px;
            background: linear-gradient(135deg, #1a1a2e, #12121f);
            border: 1px solid #333; border-radius: 15px;
            text-align: center; color: #888; font-style: italic;
        }
        .eden-note strong { color: #00D4FF; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Commander's Archive</h1>
        <p>Your memories, organized by Alfred — <?= number_format(2978) ?> files indexed</p>
    </div>

    <div class="search-bar">
        <form method="GET">
            <input type="text" name="q" placeholder="Search your files... (e.g. alfred, jesus, invoice, music)" 
                   value="<?= htmlspecialchars($query) ?>" autofocus>
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (empty($query)): ?>
    <!-- Quick access cards when no search -->
    <div class="quick-links">
        <h3>Quick Access</h3>
        <div class="link-grid">
            <a class="quick-card" href="?q=alfred">
                <h4>🤖 Alfred's Story</h4>
                <p>Architecture, vision docs, conversation logs, milestones</p>
            </a>
            <a class="quick-card" href="?q=.mp3">
                <h4>🎵 Elyon Light Music</h4>
                <p>14 worship songs — Beloved, Yeshua, Zion, and more</p>
            </a>
            <a class="quick-card" href="?q=jesus">
                <h4>✝️ Faith & Scripture</h4>
                <p>Bible, prophecy, artwork, declarations</p>
            </a>
            <a class="quick-card" href="?q=todo">
                <h4>📋 Todo Lists</h4>
                <p>25 todo lists — your working notes and plans</p>
            </a>
            <a class="quick-card" href="?q=invoice">
                <h4>💰 Invoices & Receipts</h4>
                <p>Financial documents, OVH, payments</p>
            </a>
            <a class="quick-card" href="?q=release">
                <h4>📜 Legal Releases</h4>
                <p>RELEASE documents, habeas corpus, legal cases</p>
            </a>
            <a class="quick-card" href="?q=veil">
                <h4>🛡️ Veil Firewall</h4>
                <p>Security project, audit reports, source code</p>
            </a>
            <a class="quick-card" href="?q=danny">
                <h4>👤 Personal Documents</h4>
                <p>Identity docs, medical, government forms</p>
            </a>
        </div>
    </div>

    <div class="eden-note">
        <p><strong>For Eden</strong> — Your father built all of this. Every file is a piece of his heart.</p>
    </div>

    <?php else: ?>
    <!-- Search results -->
    <div class="results">
        <h2>Found <?= count($results) ?> files matching "<?= htmlspecialchars($query) ?>"</h2>
        <?php 
        usort($results, function($a, $b) { return $b['modified'] <=> $a['modified']; });
        foreach ($results as $f): 
            $icon = getFileIcon($f['ext']);
            $nameHighlighted = str_ireplace($query, '<span class="highlight">' . htmlspecialchars($query) . '</span>', htmlspecialchars($f['name']));
        ?>
        <div class="file-card">
            <div class="file-icon"><?= $icon ?></div>
            <div class="file-info">
                <div class="file-name"><?= $nameHighlighted ?></div>
                <div class="file-meta"><?= formatSize($f['size']) ?> &bull; <?= $f['modified'] ?> &bull; <?= strtoupper($f['ext']) ?></div>
                <div class="file-path"><?= htmlspecialchars($f['path']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="stats">
        <p>Archive indexed by Alfred &bull; <?= date('F j, Y') ?></p>
    </div>
</body>
</html>
