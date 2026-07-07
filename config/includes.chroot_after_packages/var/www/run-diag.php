<?php
/**
 * SSH Diagnostic — writes output to file for Alfred to read
 * Run via: include or web request
 */
ob_start();

echo "=== SSH ROOT EXECUTION DIAGNOSTIC ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// 1. sshpass
echo "--- 1. sshpass ---\n";
$sshpassPath = trim(shell_exec('which sshpass 2>/dev/null') ?? '');
$sshpassCheck = trim(shell_exec('command -v sshpass 2>/dev/null') ?? '');
echo "which sshpass: " . ($sshpassPath ?: 'NOT FOUND') . "\n";
echo "command -v sshpass: " . ($sshpassCheck ?: 'NOT FOUND') . "\n";

// Check if it exists in common paths
foreach (['/usr/bin/sshpass', '/usr/local/bin/sshpass', '/bin/sshpass'] as $path) {
    if (file_exists($path)) {
        echo "Found at: $path\n";
    }
}

// 2. SSH client
echo "\n--- 2. SSH client ---\n";
echo "which ssh: " . trim(shell_exec('which ssh 2>/dev/null') ?? '') . "\n";

// 3. Vault key
echo "\n--- 3. Vault key ---\n";
$keyFile = '/run/user/1004/keys/vault.key';
$altKey = '/home/root/.vault-master-key';
echo "tmpfs key: " . (file_exists($keyFile) ? 'PRESENT' : 'MISSING') . "\n";
echo "master key: " . (file_exists($altKey) ? 'PRESENT' : 'MISSING') . "\n";

// 4. SSH creds from vault
echo "\n--- 4. SSH credentials ---\n";
$creds = null;
try {
    require_once '/home/root/.vault/key-loader.php';
    $vaultKeyHex = getVaultKeyFromTmpfs();
    echo "Vault key loaded: YES\n";
    
    $encFile = '/home/root/.vault/ssh-credentials.enc';
    if (!file_exists($encFile)) {
        echo "ssh-credentials.enc: MISSING\n";
    } else {
        echo "ssh-credentials.enc: PRESENT\n";
        $raw = file_get_contents($encFile);
        $version = str_starts_with($raw, 'V2:') ? 'V2' : 'V1';
        echo "Format: $version\n";
        
        if ($version === 'V2') $raw = substr($raw, 3);
        $binary = base64_decode($raw);
        $nonce = substr($binary, 0, 12);
        $tag = substr($binary, 12, 16);
        $ciphertext = substr($binary, 28);
        $aesKey = hex2bin(hash('sha256', $vaultKeyHex));
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $nonce, $tag);
        
        if ($decrypted === false) {
            echo "Decryption: FAILED\n";
        } else {
            $creds = json_decode($decrypted, true);
            echo "Decryption: SUCCESS\n";
            echo "User: " . ($creds['username'] ?? 'NOT SET') . "\n";
            echo "Host: " . ($creds['host'] ?? 'NOT SET') . "\n";
            echo "Pass length: " . strlen($creds['password'] ?? '') . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 5. SSHD config
echo "\n--- 5. SSHD config ---\n";
$sshdConf = @file_get_contents('/etc/ssh/sshd_config');
if ($sshdConf) {
    preg_match_all('/^\s*(#?\s*PasswordAuthentication\s+\w+)/m', $sshdConf, $matches);
    foreach ($matches[1] ?? [] as $line) echo "  " . trim($line) . "\n";
    
    preg_match_all('/^\s*Include\s+(.+)/m', $sshdConf, $includes);
    if (!empty($includes[1])) {
        echo "Includes: " . implode(', ', $includes[1]) . "\n";
        foreach ($includes[1] as $pattern) {
            foreach (glob($pattern) as $f) {
                $c = @file_get_contents($f);
                if ($c && preg_match('/PasswordAuthentication\s+(\w+)/i', $c, $pm)) {
                    echo "  $f: PasswordAuthentication " . $pm[1] . "\n";
                }
            }
        }
    }
} else {
    echo "Cannot read sshd_config\n";
}

// 6. SSH test (if sshpass exists)
echo "\n--- 6. SSH connection test ---\n";
$hasSshpass = !empty($sshpassPath) || !empty($sshpassCheck);
if ($hasSshpass && $creds) {
    $testCmd = sprintf(
        'sshpass -p %s ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR -o ConnectTimeout=5 %s@%s "whoami && echo SSH_OK" 2>&1',
        escapeshellarg($creds['password'] ?? ''),
        escapeshellarg($creds['username'] ?? 'ubuntu'),
        escapeshellarg($creds['host'] ?? '127.0.0.1')
    );
    $result = trim(shell_exec($testCmd) ?? '');
    echo "SSH test: $result\n";
    
    if (str_contains($result, 'SSH_OK')) {
        echo "STATUS: SSH WORKS!\n";
        
        $sudoCmd = sprintf(
            'sshpass -p %s ssh -o StrictHostKeyChecking=no -o LogLevel=ERROR %s@%s "echo %s | sudo -S whoami 2>&1" 2>&1',
            escapeshellarg($creds['password'] ?? ''),
            escapeshellarg($creds['username'] ?? 'ubuntu'),
            escapeshellarg($creds['host'] ?? '127.0.0.1'),
            escapeshellarg($creds['password'] ?? '')
        );
        $sudoResult = trim(shell_exec($sudoCmd) ?? '');
        echo "Sudo test: $sudoResult\n";
    }
} else {
    if (!$hasSshpass) echo "sshpass NOT INSTALLED\n";
    if (!$creds) echo "No creds available\n";
}

// 7. Alternative methods
echo "\n--- 7. Alternative root methods ---\n";
echo "User: " . trim(shell_exec('whoami')) . "\n";
echo "UID: " . posix_getuid() . "\n";
echo "sudo -n -l: " . trim(shell_exec('sudo -n -l 2>&1') ?? '') . "\n";

// Bridge at /opt
if (file_exists('/opt/gohostme/bridge.sh')) {
    echo "Bridge /opt/gohostme/bridge.sh: EXISTS\n";
    $stat = stat('/opt/gohostme/bridge.sh');
    echo "  Owner UID: " . $stat['uid'] . "\n";
    echo "  Perms: " . decoct($stat['mode'] & 07777) . "\n";
    echo "  SUID: " . (($stat['mode'] & 04000) ? 'YES' : 'NO') . "\n";
} else {
    echo "Bridge /opt/gohostme/bridge.sh: NOT FOUND\n";
}

// 8. Check DB for current ubuntu password
echo "\n--- 8. DB credential check ---\n";
try {
    $masterKey = trim(file_get_contents('/home/root/.vault-master-key'));
    $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=root_whmcs', 'root_whmcs_u', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check commander_credentials for SSH creds
    $stmt = $pdo->query("SELECT service, username, encrypted_value FROM commander_credentials WHERE service LIKE '%ssh%' OR service LIKE '%ubuntu%' LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No SSH entries in commander_credentials\n";
        // Show what services exist
        $stmt2 = $pdo->query("SELECT service FROM commander_credentials ORDER BY service");
        echo "Available services: " . implode(', ', array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'service')) . "\n";
    } else {
        foreach ($rows as $row) {
            echo "  Service: {$row['service']}, User: {$row['username']}\n";
            // Try to decrypt VENC1 value
            $enc = $row['encrypted_value'];
            if (str_starts_with($enc, 'VENC1:')) {
                $payload = base64_decode(substr($enc, 6));
                $iv = substr($payload, 0, 12);
                $tag = substr($payload, 12, 16);
                $hmac = substr($payload, 28, 32);
                $ct = substr($payload, 60);
                $key = hex2bin(substr(hash('sha256', $masterKey), 0, 64));
                $plain = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
                if ($plain !== false) {
                    echo "  Decrypted pass length: " . strlen($plain) . "\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}

// 9. Check password rotation cron
echo "\n--- 9. Password rotation ---\n";
$crontab = trim(shell_exec('crontab -l 2>&1') ?? '');
$rotateLines = [];
foreach (explode("\n", $crontab) as $line) {
    if (preg_match('/rotat|passw|ssh|cred|vault/i', $line)) {
        $rotateLines[] = $line;
    }
}
if (!empty($rotateLines)) {
    echo "Rotation crons:\n";
    foreach ($rotateLines as $l) echo "  $l\n";
} else {
    echo "No rotation crons found for root user\n";
}

// Write output
$output = ob_get_clean();
$outFile = '/home/root/ssh-diag-output.txt';
file_put_contents($outFile, $output);
echo $output;
