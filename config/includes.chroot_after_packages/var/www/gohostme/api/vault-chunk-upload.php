<?php
/**
 * Commander Vault — Chunked Upload API
 * RESTRICTED: client_id 33 ONLY
 * 
 * Accepts file chunks, reassembles, then encrypts with AES-256-GCM.
 * Handles files of any size (tested up to multi-GB).
 */
session_start();
header('Content-Type: application/json');

// Auth check
require_once dirname(__DIR__) . '/includes/auth-gate.inc.php';
if (!isset($_SESSION['client_id']) || (int)$_SESSION['client_id'] !== 33) {
    http_response_code(403);
    die(json_encode(['error' => 'Access denied']));
}

$dropbox  = '/home/gositeme/.vault/personal/dropbox';
$tmpDir   = '/home/gositeme/.vault/personal/tmp';

// Ensure dirs exist
if (!is_dir($tmpDir)) { mkdir($tmpDir, 0700, true); }
if (!is_dir($dropbox)) { mkdir($dropbox, 0700, true); }

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'init':
        // Start a new upload — create a unique upload ID
        $fileName   = basename($_POST['fileName'] ?? 'unknown');
        $fileSize   = (int)($_POST['fileSize'] ?? 0);
        $totalChunks = (int)($_POST['totalChunks'] ?? 0);
        
        if ($fileSize < 1 || $totalChunks < 1) {
            die(json_encode(['error' => 'Invalid file parameters']));
        }
        
        $uploadId = bin2hex(random_bytes(16));
        $uploadDir = $tmpDir . '/' . $uploadId;
        mkdir($uploadDir, 0700);
        
        // Save metadata
        file_put_contents($uploadDir . '/meta.json', json_encode([
            'fileName'    => $fileName,
            'fileSize'    => $fileSize,
            'totalChunks' => $totalChunks,
            'received'    => 0,
            'startedAt'   => time(),
        ]));
        
        echo json_encode(['uploadId' => $uploadId, 'status' => 'ready']);
        break;
        
    case 'chunk':
        $uploadId = preg_replace('/[^a-f0-9]/', '', $_POST['uploadId'] ?? '');
        $chunkIndex = (int)($_POST['chunkIndex'] ?? -1);
        $uploadDir = $tmpDir . '/' . $uploadId;
        
        if (!$uploadId || !is_dir($uploadDir) || $chunkIndex < 0) {
            die(json_encode(['error' => 'Invalid upload session']));
        }
        
        if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            die(json_encode(['error' => 'Chunk upload failed']));
        }
        
        // Save chunk
        $chunkPath = $uploadDir . '/chunk_' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);
        move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath);
        
        // Update metadata
        $meta = json_decode(file_get_contents($uploadDir . '/meta.json'), true);
        $meta['received']++;
        file_put_contents($uploadDir . '/meta.json', json_encode($meta));
        
        echo json_encode([
            'status'   => 'ok',
            'received' => $meta['received'],
            'total'    => $meta['totalChunks'],
            'percent'  => round($meta['received'] / $meta['totalChunks'] * 100, 1),
        ]);
        break;
        
    case 'finalize':
        $uploadId = preg_replace('/[^a-f0-9]/', '', $_POST['uploadId'] ?? '');
        $notes    = $_POST['notes'] ?? '';
        $uploadDir = $tmpDir . '/' . $uploadId;
        
        if (!$uploadId || !is_dir($uploadDir)) {
            die(json_encode(['error' => 'Invalid upload session']));
        }
        
        $meta = json_decode(file_get_contents($uploadDir . '/meta.json'), true);
        
        // Reassemble file
        $destFile = $dropbox . '/' . $meta['fileName'];
        $out = fopen($destFile, 'wb');
        if (!$out) {
            die(json_encode(['error' => 'Could not create output file']));
        }
        
        for ($i = 0; $i < $meta['totalChunks']; $i++) {
            $chunkPath = $uploadDir . '/chunk_' . str_pad($i, 6, '0', STR_PAD_LEFT);
            if (!file_exists($chunkPath)) {
                fclose($out);
                unlink($destFile);
                die(json_encode(['error' => "Missing chunk {$i}"]));
            }
            $chunkData = file_get_contents($chunkPath);
            fwrite($out, $chunkData);
            unset($chunkData);
            unlink($chunkPath); // Clean up chunk
        }
        fclose($out);
        
        // Clean up upload dir
        @unlink($uploadDir . '/meta.json');
        @rmdir($uploadDir);
        
        // Now encrypt the reassembled file
        $keyFile = '/home/gositeme/.vault-master-key';
        $masterKey = @file_get_contents($keyFile);
        if (!$masterKey) {
            die(json_encode(['error' => 'Vault key not loaded']));
        }
        $encKey = hex2bin(hash('sha256', trim($masterKey)));
        
        $vaultDir = '/home/gositeme/.vault/personal/documents';
        $timestamp = date('Ymd-His');
        $safeName = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($meta['fileName']));
        $encName = $timestamp . '_' . $safeName . '.enc';
        $encPath = $vaultDir . '/' . $encName;
        
        // Read, encrypt, write
        $plaintext = file_get_contents($destFile);
        $iv = random_bytes(12);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $encKey, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($ciphertext === false) {
            unlink($destFile);
            die(json_encode(['error' => 'Encryption failed']));
        }
        
        // Clear plaintext
        if (function_exists('sodium_memzero')) sodium_memzero($plaintext);
        
        // Store envelope
        $envelope = "\x02" . $iv . $tag . $ciphertext;
        file_put_contents($encPath, $envelope);
        chmod($encPath, 0600);
        unset($ciphertext, $envelope);
        
        // Shred original
        $fh = fopen($destFile, 'w');
        if ($fh) { fwrite($fh, random_bytes(min(filesize($destFile) ?: 4096, 4096))); fclose($fh); }
        unlink($destFile);
        
        // Log to DB
        $_envFile = '/home/gositeme/.env.php';
        if (file_exists($_envFile)) require_once $_envFile;
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
        $pdo = getSharedDB();
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS commander_vault_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_name VARCHAR(255) NOT NULL,
            encrypted_name VARCHAR(255) NOT NULL,
            file_size BIGINT DEFAULT 0,
            mime_type VARCHAR(100) DEFAULT 'application/octet-stream',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            INDEX idx_uploaded (uploaded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO commander_vault_files (original_name, encrypted_name, file_size, mime_type, uploaded_at, notes) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$meta['fileName'], $encName, $meta['fileSize'], 'application/octet-stream', $notes]);
        
        echo json_encode([
            'status' => 'complete',
            'fileName' => $meta['fileName'],
            'size' => $meta['fileSize'],
            'sizeMB' => round($meta['fileSize'] / 1024 / 1024, 2),
            'encrypted' => $encName,
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}
