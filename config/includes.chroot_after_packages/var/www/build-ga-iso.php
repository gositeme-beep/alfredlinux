<?php
/**
 * Alfred Linux GA ISO Build Launcher
 * Checks prerequisites, then launches the build in background.
 * Visit: https://root.com/build-ga-iso.php?key=ALFRED-BOOT-2026
 * 
 * Does NOT self-destruct — you may need to run multiple builds tonight.
 */

$expectedKey = 'ALFRED-BOOT-2026';
$providedKey = $_GET['key'] ?? '';
if (!hash_equals($expectedKey, $providedKey)) {
    http_response_code(404);
    die('<!DOCTYPE html><html><body><h1>404</h1></body></html>');
}

// Prevent timeout — builds take 30-60+ minutes
set_time_limit(0);
ini_set('max_execution_time', 0);

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html><head><title>Alfred Linux GA Build</title>';
echo '<style>body{background:#0a0a0a;color:#e0e0e0;font-family:monospace;padding:2rem;max-width:1000px;margin:0 auto}';
echo '.ok{color:#22c55e}.err{color:#ef4444}.warn{color:#eab308}.step{margin:1rem 0;padding:1rem;background:#111;border-left:3px solid #b8860b;border-radius:4px}';
echo 'h1{color:#b8860b}pre{white-space:pre-wrap;max-height:400px;overflow-y:auto;background:#0d0d0d;padding:0.5rem;border:1px solid #222;border-radius:4px;font-size:0.85rem}';
echo '.pulse{animation:pulse 2s infinite}@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}</style></head><body>';
echo '<h1>&#x2694; Alfred Linux 7.77 GA — ISO Build Launcher</h1>';
echo '<p>"The grass withereth, the flower fadeth: but the word of our God shall stand for ever." — Isaiah 40:8 AKJV</p><hr>';
ob_flush(); flush();

$projectDir = '/home/root/alfred-linux-v2';
$scriptDir = "$projectDir/scripts";
$buildScript = "$scriptDir/build-unified.sh";
$hooksDir = "$projectDir/config/hooks/live";
$outputDir = "$projectDir/iso-output";
$buildLog = "$projectDir/build-ga-live.log";

// ─── Step 1: Check build tools ───────────────────────────
echo '<div class="step"><h3>Step 1: Check Build Tools</h3><pre>';
$tools = ['lb' => 'live-build', 'debootstrap' => 'debootstrap', 'mksquashfs' => 'squashfs-tools', 'xorriso' => 'xorriso'];
$missing = [];
foreach ($tools as $cmd => $pkg) {
    $path = trim(shell_exec("which $cmd 2>/dev/null"));
    if ($path) {
        echo "✓ $cmd → $path\n";
    } else {
        echo "✗ $cmd MISSING (apt install $pkg)\n";
        $missing[] = $pkg;
    }
}
echo '</pre>';
if (!empty($missing)) {
    echo '<p class="err">Missing tools: ' . implode(', ', $missing) . '</p>';
    echo '<p>Install with: <code>sudo apt-get install -y ' . implode(' ', $missing) . '</code></p></div>';
    echo '</body></html>';
    exit;
}
echo '<p class="ok">✓ All build tools present</p></div>';
ob_flush(); flush();

// ─── Step 2: Check disk space ────────────────────────────
echo '<div class="step"><h3>Step 2: Disk Space</h3>';
$freeGB = round(disk_free_space('/home/root') / 1073741824, 1);
echo "<p>Free: {$freeGB} GB</p>";
if ($freeGB < 30) {
    echo '<p class="err">Need at least 30GB free for GA build!</p></div>';
    echo '</body></html>';
    exit;
}
echo '<p class="ok">✓ Sufficient disk space</p></div>';
ob_flush(); flush();

// ─── Step 3: Count hooks ───────────────────────────────
echo '<div class="step"><h3>Step 3: Verify 42 Hooks (Matthew 1:17)</h3><pre>';
$gaHooks = [
    "0100-alfred-customize.hook.chroot",
    "0150-alfred-hardware.hook.chroot",
    "0160-alfred-security.hook.chroot",
    "0165-alfred-network-hardening.hook.chroot",
    "0166-alfred-quantum.hook.chroot",
    "0167-alfred-mesh.hook.chroot",
    "0168-alfred-productivity.hook.chroot",
    "0170-alfred-fde.hook.chroot",
    "0175-omahon-seal.hook.chroot",
    "0176-kingdom-covenant-shield.hook.chroot",
    "0200-alfred-browser.hook.chroot",
    "0250-alfred-ai.hook.chroot",
    "0255-alfred-dev-tools.hook.chroot",
    "0260-alfred-terminal-power.hook.chroot",
    "0265-alfred-containers.hook.chroot",
    "0270-alfred-sovereign.hook.chroot",
    "0275-alfred-gpu.hook.chroot",
    "0280-alfred-max-sovereign.hook.chroot",
    "0285-alfred-eternal-storage.hook.chroot",
    "0290-alfred-bible.hook.chroot",
    "0291-alfred-family-bible.hook.chroot",
    "0292-alfred-bible-tongues.hook.chroot",
    "0295-alfred-worship.hook.chroot",
    "0296-alfred-testimony.hook.chroot",
    "0297-alfred-kingdom-locale-payload.hook.chroot",
    "0300-alfred-ide.hook.chroot",
    "0400-alfred-voice.hook.chroot",
    "0500-alfred-search.hook.chroot",
    "0600-alfred-installer.hook.chroot",
    "0605-alfred-callings.hook.chroot",
    "0700-alfred-welcome.hook.chroot",
    "0701-alfred-stranger.hook.chroot",
    "0702-alfred-accessibility.hook.chroot",
    "0703-alfred-hearth.hook.chroot",
    "0710-alfred-update.hook.chroot",
    "0720-alfred-sacred-rest.hook.chroot",
    "0722-alfred-sabbath.hook.chroot",
    "0723-alfred-morning-watch.hook.chroot",
    "0724-alfred-inheritance.hook.chroot",
    "0725-alfred-assembly.hook.chroot",
    "0800-alfred-store.hook.chroot",
    "0900-alfred-voice-v2.hook.chroot",
];

$present = 0;
$missingHooks = [];
foreach ($gaHooks as $i => $hook) {
    $num = $i + 1;
    if (file_exists("$hooksDir/$hook")) {
        echo "✓ #{$num} $hook\n";
        $present++;
    } else {
        echo "✗ #{$num} $hook — MISSING\n";
        $missingHooks[] = $hook;
    }
}
echo '</pre>';
echo "<p>Hooks: $present / " . count($gaHooks) . " (need 42)</p>";
if (!empty($missingHooks)) {
    echo '<p class="warn">⚠ Missing ' . count($missingHooks) . ' hooks — build will fail if these are required</p>';
} else {
    echo '<p class="ok">✓ All 42 hooks present — Matthew 1:17 fulfilled</p>';
}
echo '</div>';
ob_flush(); flush();

// ─── Step 4: Check sudo access (via GoHostMe Bridge) ────
echo '<div class="step"><h3>Step 4: Sudo Access (GoHostMe Bridge)</h3>';
$hasSudo = false;
$sudoPass = '';
$sudoMethod = 'bridge';

// Use the GoHostMe Bridge v2.0 — already has root exec via SSH+sudo from vault
$bridgeTokenFile = '/home/root/.vault/bridge-token.php';
$hmacSecretFile = '/home/root/.vault/bridge-hmac-secret';

if (file_exists($bridgeTokenFile) && file_exists($hmacSecretFile)) {
    echo '<p class="ok">✓ Bridge token generator found</p>';
    echo '<p class="ok">✓ HMAC secret file present</p>';
    
    // Test if vault SSH creds work
    require_once $bridgeTokenFile;
    try {
        $creds = vault_get_ssh_creds();
        if (!empty($creds['pass'])) {
            echo '<p class="ok">✓ SSH credentials decrypted from vault</p>';
            $hasSudo = true;
        } else {
            echo '<p class="err">✗ SSH credentials empty</p>';
        }
    } catch (Exception $e) {
        echo '<p class="err">✗ Vault SSH error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    if (!file_exists($bridgeTokenFile)) echo '<p class="err">✗ Bridge token file missing</p>';
    if (!file_exists($hmacSecretFile)) echo '<p class="err">✗ HMAC secret file missing</p>';
}

echo '</div>';
ob_flush(); flush();

// ─── Step 5: Check for existing ISO output ──────────────
echo '<div class="step"><h3>Step 5: Existing ISOs</h3><pre>';
$isos = glob("$outputDir/*.iso");
if (!empty($isos)) {
    foreach ($isos as $iso) {
        $size = round(filesize($iso) / 1073741824, 2);
        echo basename($iso) . " — {$size} GB\n";
    }
} else {
    echo "No existing ISOs in output directory\n";
}
echo '</pre></div>';
ob_flush(); flush();

// ─── Step 6: Memory status ──────────────────────────────
echo '<div class="step"><h3>Step 6: System Resources</h3><pre>';
$memInfo = @file_get_contents('/proc/meminfo');
preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $m);
$memMB = isset($m[1]) ? round((int)$m[1] / 1024) : 'unknown';
$watches = trim(@file_get_contents('/proc/sys/fs/inotify/max_user_watches'));
$loadavg = trim(@file_get_contents('/proc/loadavg'));
echo "Memory available: {$memMB} MB\n";
echo "Disk free: {$freeGB} GB\n";
echo "inotify watches: {$watches}\n";
echo "Load average: {$loadavg}\n";
echo '</pre></div>';
ob_flush(); flush();

// ─── Decision: Can we build? ────────────────────────────
$canBuild = empty($missing) && $freeGB >= 30 && $hasSudo;

if (!$canBuild) {
    echo '<hr>';
    echo '<h2 class="err">Cannot build — fix issues above first</h2>';
    echo '</body></html>';
    exit;
}

// ─── Step 7: Launch the build! ──────────────────────────
$action = $_GET['action'] ?? 'check';

if ($action === 'build') {
    echo '<div class="step"><h3>Step 7: ⚔ LAUNCHING GA BUILD</h3>';
    echo '<p class="pulse" style="color:#b8860b;font-size:1.2rem">Building Alfred Linux 7.77 GA...</p>';
    echo '<p>Build log: ' . htmlspecialchars($buildLog) . '</p>';
    ob_flush(); flush();

    // First: sync bridge.sh to /opt/gohostme/ so new build-iso command is available
    echo '<p>Syncing bridge to production...</p>';
    ob_flush(); flush();
    list($syncExit, $syncOut, $syncErr) = sudo_exec('cp /home/root/gohostme/bridge.sh /opt/gohostme/bridge.sh && chmod 755 /opt/gohostme/bridge.sh && chown root:root /opt/gohostme/bridge.sh && echo SYNCED');
    if (strpos($syncOut, 'SYNCED') !== false) {
        echo '<p class="ok">✓ Bridge synced</p>';
    } else {
        echo '<pre>' . htmlspecialchars($syncOut . "\n" . $syncErr) . '</pre>';
        echo '<p class="warn">⚠ Bridge sync may have failed — trying build anyway</p>';
    }
    ob_flush(); flush();
    
    // Launch build via GoHostMe Bridge (runs as root via SSH+sudo)
    echo '<p>Launching via GoHostMe Bridge v2.0...</p>';
    ob_flush(); flush();
    
    list($exitCode, $stdout, $stderr) = bridge_exec('build-iso', ['ga']);
    
    echo '<pre>' . htmlspecialchars($stdout) . '</pre>';
    if (!empty($stderr)) {
        echo '<pre class="err">' . htmlspecialchars($stderr) . '</pre>';
    }
    
    sleep(3);
    
    // Check if it started
    $ps = trim(shell_exec("ps aux | grep 'build-unified.sh\\|lb build\\|run-ga-build' | grep -v grep | head -5"));
    echo '<pre>' . htmlspecialchars($ps ?: 'Process starting...') . '</pre>';
    
    if (!empty($ps)) {
        echo '<p class="ok">✓ Build process launched in background</p>';
    } else {
        // Check if the log has content
        if (file_exists($buildLog) && filesize($buildLog) > 0) {
            $logTail = shell_exec("tail -20 " . escapeshellarg($buildLog));
            echo '<pre>' . htmlspecialchars($logTail) . '</pre>';
            echo '<p class="warn">⚠ Process may still be starting — check log</p>';
        } else {
            echo '<p class="err">✗ Build may not have started — check sudo password</p>';
        }
    }
    
    echo '</div>';
    echo '<hr>';
    echo '<h2 style="color:#b8860b">Build Running in Background</h2>';
    echo '<p>The build runs independently of this page. You can close this tab.</p>';
    echo '<p>Monitor progress:</p>';
    echo '<ul>';
    echo '<li>Log: <code>tail -f ' . htmlspecialchars($buildLog) . '</code></li>';
    echo '<li>Or visit: <a href="?key=' . htmlspecialchars($expectedKey) . '&action=status" style="color:#b8860b">Build Status Page</a></li>';
    echo '</ul>';
    
} elseif ($action === 'status') {
    echo '<div class="step"><h3>Build Status</h3>';
    
    // Check if build is running
    $ps = trim(shell_exec("ps aux | grep 'build-unified.sh\\|lb build\\|run-ga-build\\|debootstrap\\|mksquashfs\\|xorriso' | grep -v grep"));
    if (!empty($ps)) {
        echo '<p class="pulse" style="color:#eab308">⚡ Build is RUNNING</p>';
        echo '<pre>' . htmlspecialchars($ps) . '</pre>';
    } else {
        echo '<p>No build process detected</p>';
    }
    
    // Show log tail
    if (file_exists($buildLog)) {
        $logSize = filesize($buildLog);
        $logSizeKB = round($logSize / 1024, 1);
        echo "<p>Log size: {$logSizeKB} KB</p>";
        $logTail = shell_exec("tail -50 " . escapeshellarg($buildLog));
        echo '<pre>' . htmlspecialchars($logTail) . '</pre>';
    } else {
        echo '<p>No build log found yet</p>';
    }
    
    // Check for ISO output
    $isos = glob("$outputDir/*.iso");
    if (!empty($isos)) {
        echo '<h4>ISO Output:</h4><pre>';
        foreach ($isos as $iso) {
            $size = round(filesize($iso) / 1073741824, 2);
            $time = date('Y-m-d H:i:s', filemtime($iso));
            echo basename($iso) . " — {$size} GB — {$time}\n";
        }
        $sha = glob("$outputDir/*.sha256");
        foreach ($sha as $s) {
            echo basename($s) . ": " . trim(file_get_contents($s)) . "\n";
        }
        echo '</pre>';
        echo '<p class="ok">✓ ISO READY</p>';
    }
    
    echo '</div>';
    echo '<p><a href="?key=' . htmlspecialchars($expectedKey) . '&action=status" style="color:#b8860b">↻ Refresh Status</a></p>';
    
} else {
    // Default: show check results + launch button
    echo '<hr>';
    echo '<h2 style="color:#22c55e">✓ All Prerequisites Passed — Ready to Build</h2>';
    echo '<p style="font-size:1.1rem">Click below to launch the GA ISO build:</p>';
    echo '<p><a href="?key=' . htmlspecialchars($expectedKey) . '&action=build" style="display:inline-block;padding:1rem 2rem;background:#b8860b;color:#000;text-decoration:none;font-weight:bold;font-size:1.2rem;border-radius:8px;margin:1rem 0">⚔ BUILD ALFRED LINUX 7.77 GA</a></p>';
    echo '<p style="color:#666">Build runs in background via nohup. You can close the tab and check status later.</p>';
    echo '<p><a href="?key=' . htmlspecialchars($expectedKey) . '&action=status" style="color:#b8860b">Check Build Status</a></p>';
}

echo '<p style="color:#b8860b;margin-top:2rem">"It is finished." — John 19:30</p>';
echo '</body></html>';
