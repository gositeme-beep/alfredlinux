<?php
/**
 * ══════════════════════════════════════════════════════════════
 * GoSiteMe — Backup Continuity System
 * ══════════════════════════════════════════════════════════════
 * 
 * Own backup method replacing DirectAdmin backups.
 * Creates database dumps and file archives, tracks backup history,
 * and can sync backups across registered servers.
 *
 * Actions:
 *   create       - Create a new backup (DB + critical files)
 *   list         - List all backups
 *   status       - Backup system status
 *   restore-info - Get restore instructions for a backup
 *   cleanup      - Remove old backups (keep last N)
 *   sync         - Sync latest backup to a remote server
 *
 * Usage:
 *   CLI: php api/backup-system.php create
 *   API: GET /api/backup-system.php?action=create&secret=INTERNAL_SECRET
 *
 * PM2 Cron: Can be scheduled via ecosystem.config.js
 * ══════════════════════════════════════════════════════════════
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// ── Auth ────────────────────────────────────────────────
$headers = function_exists('getallheaders') ? getallheaders() : [];
$internalSecret = $headers['X-Internal-Secret'] ?? $headers['x-internal-secret'] ?? ($_GET['secret'] ?? '');
$isOwner = false;

if ($internalSecret && defined('INTERNAL_SECRET') && hash_equals(INTERNAL_SECRET, $internalSecret)) {
    $isOwner = true;
}

if (!$isOwner && php_sapi_name() !== 'cli') {
    session_start();
    $clientId = (int)($_SESSION['client_id'] ?? 0);
    if (in_array($clientId, [1, 33])) {
        $isOwner = true;
    }
}

if (php_sapi_name() === 'cli') {
    // CLI mode requires INTERNAL_SECRET env var for security
    $cliSecret = getenv('INTERNAL_SECRET') ?: '';
    if ($cliSecret !== '' && defined('INTERNAL_SECRET') && hash_equals(INTERNAL_SECRET, $cliSecret)) {
        $isOwner = true;
    }
}

if (!$isOwner) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

// CLI: large DB dumps / orchestration — avoid 128M default fatal on fallback paths
if (php_sapi_name() === 'cli') {
    @ini_set('memory_limit', '1024M');
}

$db = getDB();
$backupDir = dirname(__DIR__) . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0750, true);
}

// Protect backup directory
$htaccess = $backupDir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

// ── DB Setup ────────────────────────────────────────────
try {
    $db->exec("CREATE TABLE IF NOT EXISTS system_backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        backup_id VARCHAR(50) UNIQUE NOT NULL,
        backup_type ENUM('full','db_only','files_only') DEFAULT 'full',
        db_dump_file VARCHAR(255) DEFAULT NULL,
        files_archive VARCHAR(255) DEFAULT NULL,
        db_size_mb DECIMAL(10,2) DEFAULT 0,
        files_size_mb DECIMAL(10,2) DEFAULT 0,
        total_size_mb DECIMAL(10,2) DEFAULT 0,
        tables_backed_up INT DEFAULT 0,
        files_backed_up INT DEFAULT 0,
        status ENUM('running','completed','failed','synced') DEFAULT 'running',
        sync_target VARCHAR(100) DEFAULT NULL,
        synced_at DATETIME DEFAULT NULL,
        error_message TEXT DEFAULT NULL,
        duration_seconds INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {}

// ── Parse Action ────────────────────────────────────────
$rawInput = file_get_contents('php://input');
$jsonInput = $rawInput ? json_decode($rawInput, true) : [];
$action = $jsonInput['action'] ?? ($_GET['action'] ?? (isset($argv[1]) ? $argv[1] : 'status'));

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

switch ($action) {
    case 'create': createBackup($db, $backupDir); break;
    case 'list': listBackups($db); break;
    case 'status': backupStatus($db, $backupDir); break;
    case 'restore-info': restoreInfo($db); break;
    case 'cleanup': cleanupBackups($db, $backupDir); break;
    case 'sync': syncBackup($db, $backupDir); break;
    default:
        $msg = ['error' => 'Unknown action', 'valid' => ['create','list','status','restore-info','cleanup','sync']];
        echo json_encode($msg);
}

// ══════════════════════════════════════════════════════════════
// Create Backup — DB dump + critical file archive
// ══════════════════════════════════════════════════════════════
function createBackup($db, $backupDir) {
    $startTime = microtime(true);
    $backupId = 'backup-' . date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 6);
    $timestamp = date('Ymd-His');

    // Record that backup is running
    $db->prepare("INSERT INTO system_backups (backup_id, status) VALUES (?, 'running')")->execute([$backupId]);

    $output = fn($msg) => php_sapi_name() === 'cli' ? print("$msg\n") : null;
    $output("Starting backup: {$backupId}");

    try {
        // ── 1. Database Dump (mysqldump CLI + secure defaults-extra-file) ──
        $output("  Dumping database...");
        $dbGzFile = "{$backupDir}/{$backupId}-db.sql.gz";
        $stderrFile = "{$backupDir}/{$backupId}-mysqldump.stderr";

        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
        $dbHost = GOSITEME_DB_HOST;
        $dbName = GOSITEME_DB_NAME;
        $dbUser = GOSITEME_DB_USER;
        $dbPass = GOSITEME_DB_PASS;

        $cnfFile = tempnam($backupDir, 'mydump_');
        if ($cnfFile === false) {
            throw new \Exception('Could not create temp file for DB credentials');
        }
        $cnfBody = "[client]\n"
            . 'host=' . str_replace(["\n", "\r"], '', $dbHost) . "\n"
            . 'user=' . str_replace(["\n", "\r"], '', $dbUser) . "\n"
            . 'password=' . str_replace(["\n", "\r"], '', $dbPass) . "\n";
        file_put_contents($cnfFile, $cnfBody);
        chmod($cnfFile, 0600);

        $mysqldumpBin = trim((string) shell_exec('which mysqldump 2>/dev/null')) ?: '/usr/bin/mysqldump';
        $gzipBin = trim((string) shell_exec('which pigz 2>/dev/null')) ?: 'gzip';

        // stderr to file so errors are visible; stdout → compressor → .sql.gz
        $dumpCmd = escapeshellarg($mysqldumpBin)
            . ' --defaults-extra-file=' . escapeshellarg($cnfFile)
            . ' --single-transaction --quick --skip-lock-tables --routines'
            . ' --max-statement-time=0 --max-allowed-packet=512M'
            . ' ' . escapeshellarg($dbName)
            . ' 2>' . escapeshellarg($stderrFile)
            . ' | ' . ($gzipBin === 'gzip' ? 'gzip -1' : 'pigz -3')
            . ' > ' . escapeshellarg($dbGzFile);

        $exitCode = 0;
        system($dumpCmd, $exitCode);
        @unlink($cnfFile);

        $gzOk = file_exists($dbGzFile) && filesize($dbGzFile) > 2000;
        $gzMagic = $gzOk && (file_get_contents($dbGzFile, false, null, 0, 2) === "\x1f\x8b");

        if ($exitCode !== 0 || !$gzMagic) {
            $err = @file_get_contents($stderrFile) ?: '';
            @unlink($stderrFile);
            @unlink($dbGzFile);
            throw new \Exception('mysqldump failed (exit ' . $exitCode . '): ' . trim(substr($err, 0, 800)));
        }
        @unlink($stderrFile);

        $tableCount = count($db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM));
        $dbSizeMB = file_exists($dbGzFile) ? round(filesize($dbGzFile) / (1024 * 1024), 2) : 0;
        $output("  Database dump: {$dbSizeMB}MB ({$tableCount} tables)");

        // ── 2. Critical Files Archive ──
        $output("  Archiving critical files...");
        $filesArchive = "{$backupDir}/{$backupId}-files.tar.gz";
        $rootDir = dirname(__DIR__);
        
        // Critical directories and files to back up
        $includes = [
            'api/*.php',
            'config/*.php',
            'includes/*.php',
            'veil/*.php',
            'scripts/*.sh',
            'scripts/*.js',
            'ecosystem.config.js',
            'Caddyfile',
            'composer.json',
            '.htaccess'
        ];
        
        $includeArgs = array_map(fn($p) => escapeshellarg($p), $includes);
        $tarCmd = 'cd ' . escapeshellarg($rootDir) . ' && tar czf ' . escapeshellarg($filesArchive) 
            . ' ' . implode(' ', $includeArgs) . ' 2>/dev/null';
        exec($tarCmd, $tarOutput, $tarReturn);
        
        $filesSizeMB = file_exists($filesArchive) ? round(filesize($filesArchive) / (1024 * 1024), 2) : 0;
        $fileCount = 0;
        if (file_exists($filesArchive)) {
            exec('tar tzf ' . escapeshellarg($filesArchive) . ' 2>/dev/null | wc -l', $countOutput);
            $fileCount = (int)($countOutput[0] ?? 0);
        }
        $output("  Files archive: {$filesSizeMB}MB ({$fileCount} files)");

        // ── 3. Update record ──
        $duration = (int)(microtime(true) - $startTime);
        $totalSizeMB = round($dbSizeMB + $filesSizeMB, 2);

        $db->prepare("UPDATE system_backups SET 
            backup_type = 'full',
            db_dump_file = ?,
            files_archive = ?,
            db_size_mb = ?,
            files_size_mb = ?,
            total_size_mb = ?,
            tables_backed_up = ?,
            files_backed_up = ?,
            status = 'completed',
            duration_seconds = ?
            WHERE backup_id = ?")->execute([
            basename($dbGzFile), basename($filesArchive),
            $dbSizeMB, $filesSizeMB, $totalSizeMB,
            $tableCount, $fileCount, $duration, $backupId
        ]);

        $result = [
            'success' => true,
            'backup_id' => $backupId,
            'db_size_mb' => $dbSizeMB,
            'files_size_mb' => $filesSizeMB,
            'total_size_mb' => $totalSizeMB,
            'tables' => $tableCount,
            'files' => $fileCount,
            'duration_seconds' => $duration
        ];

        $output("  Backup completed in {$duration}s. Total: {$totalSizeMB}MB");
        echo json_encode($result);

    } catch (\Exception $e) {
        $db->prepare("UPDATE system_backups SET status = 'failed', error_message = ? WHERE backup_id = ?")
           ->execute([$e->getMessage(), $backupId]);
        
        $result = ['error' => 'Backup failed: ' . $e->getMessage()];
        $output("  ERROR: " . $e->getMessage());
        echo json_encode($result);
    }
}

// ══════════════════════════════════════════════════════════════
// List Backups
// ══════════════════════════════════════════════════════════════
function listBackups($db) {
    $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));
    $stmt = $db->prepare("SELECT * FROM system_backups ORDER BY created_at DESC LIMIT ?");
    dbExecute($stmt, [$limit]);
    
    echo json_encode([
        'success' => true,
        'backups' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

// ══════════════════════════════════════════════════════════════
// Backup Status — Overall health
// ══════════════════════════════════════════════════════════════
function backupStatus($db, $backupDir) {
    $total = (int)$db->query("SELECT COUNT(*) FROM system_backups")->fetchColumn();
    $completed = (int)$db->query("SELECT COUNT(*) FROM system_backups WHERE status='completed'")->fetchColumn();
    $failed = (int)$db->query("SELECT COUNT(*) FROM system_backups WHERE status='failed'")->fetchColumn();
    
    $lastBackup = $db->query("SELECT * FROM system_backups WHERE status='completed' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    $totalSizeMB = (float)$db->query("SELECT COALESCE(SUM(total_size_mb),0) FROM system_backups WHERE status='completed'")->fetchColumn();

    // Check backup directory size
    $dirSizeMB = 0;
    if (is_dir($backupDir)) {
        $dirSize = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($backupDir)) as $file) {
            if ($file->isFile()) $dirSize += $file->getSize();
        }
        $dirSizeMB = round($dirSize / (1024 * 1024), 2);
    }

    // Free disk space
    $freeGB = round(disk_free_space(dirname(__DIR__)) / (1024*1024*1024), 2);

    $hoursAgo = $lastBackup ? round((time() - strtotime($lastBackup['created_at'])) / 3600, 1) : null;

    echo json_encode([
        'success' => true,
        'total_backups' => $total,
        'completed' => $completed,
        'failed' => $failed,
        'last_backup' => $lastBackup,
        'hours_since_last' => $hoursAgo,
        'total_backup_size_mb' => $totalSizeMB,
        'backup_dir_size_mb' => $dirSizeMB,
        'disk_free_gb' => $freeGB,
        'health' => $hoursAgo === null ? 'no_backups' : ($hoursAgo < 24 ? 'healthy' : ($hoursAgo < 72 ? 'warning' : 'critical'))
    ]);
}

// ══════════════════════════════════════════════════════════════
// Restore Info — Instructions for restoring a backup
// ══════════════════════════════════════════════════════════════
function restoreInfo($db) {
    $backupId = $_GET['backup_id'] ?? '';
    if (!$backupId) {
        echo json_encode(['error' => 'backup_id required']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM system_backups WHERE backup_id = ?");
    $stmt->execute([$backupId]);
    $backup = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$backup) {
        echo json_encode(['error' => 'Backup not found']);
        return;
    }

    echo json_encode([
        'success' => true,
        'backup' => $backup,
        'restore_commands' => [
            'db' => "gunzip -c backups/{$backup['db_dump_file']} | mysql -u \$DB_USER -p \$DB_NAME",
            'files' => "tar xzf backups/{$backup['files_archive']} -C /path/to/webroot",
            'note' => 'Always verify backup integrity before restoring. Test on a staging server first.'
        ]
    ]);
}

// ══════════════════════════════════════════════════════════════
// Cleanup — Remove old backups, keep last N
// ══════════════════════════════════════════════════════════════
function cleanupBackups($db, $backupDir) {
    $keep = max(3, (int)($_GET['keep'] ?? 10));

    // Get backups to remove
    $stmt = $db->prepare("SELECT backup_id, db_dump_file, files_archive FROM system_backups 
        WHERE status = 'completed' ORDER BY created_at DESC");
    $stmt->execute();
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $removed = 0;
    foreach ($all as $i => $b) {
        if ($i < $keep) continue; // Keep recent ones

        // Delete files
        if ($b['db_dump_file'] && file_exists($backupDir . '/' . $b['db_dump_file'])) {
            unlink($backupDir . '/' . $b['db_dump_file']);
        }
        if ($b['files_archive'] && file_exists($backupDir . '/' . $b['files_archive'])) {
            unlink($backupDir . '/' . $b['files_archive']);
        }

        // Delete record
        $db->prepare("DELETE FROM system_backups WHERE backup_id = ?")->execute([$b['backup_id']]);
        $removed++;
    }

    echo json_encode([
        'success' => true,
        'kept' => min($keep, count($all)),
        'removed' => $removed
    ]);
}

// ══════════════════════════════════════════════════════════════
// Sync Backup — Copy latest backup to a registered server
// ══════════════════════════════════════════════════════════════
function syncBackup($db, $backupDir) {
    $targetServer = $_GET['server_id'] ?? ($jsonInput['server_id'] ?? '');
    if (!$targetServer) {
        echo json_encode(['error' => 'server_id required']);
        return;
    }

    // Get target server details
    $stmt = $db->prepare("SELECT * FROM server_registry WHERE server_id = ? AND status != 'decommissioned'");
    $stmt->execute([$targetServer]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$server) {
        echo json_encode(['error' => 'Target server not found']);
        return;
    }

    // Get latest backup
    $backup = $db->query("SELECT * FROM system_backups WHERE status='completed' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$backup) {
        echo json_encode(['error' => 'No completed backups to sync']);
        return;
    }

    // Queue sync command to target server
    $db->prepare("INSERT INTO server_command_log (server_id, command, payload) VALUES (?, 'sync-backup', ?)")
       ->execute([$targetServer, json_encode([
           'backup_id' => $backup['backup_id'],
           'source_url' => 'https://gositeme.com/api/backup-system.php?action=download&backup_id=' . urlencode($backup['backup_id']),
           'db_file' => $backup['db_dump_file'],
           'files_archive' => $backup['files_archive']
       ])]);

    // Mark backup as synced
    $db->prepare("UPDATE system_backups SET sync_target = ?, synced_at = NOW(), status = 'synced' WHERE backup_id = ?")
       ->execute([$targetServer, $backup['backup_id']]);

    echo json_encode([
        'success' => true,
        'message' => "Sync command queued for server {$server['server_name']}",
        'backup_id' => $backup['backup_id'],
        'target' => $server['server_name']
    ]);
}
