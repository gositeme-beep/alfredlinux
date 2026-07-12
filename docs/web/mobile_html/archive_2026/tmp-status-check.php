<?php
/**
 * Temporary server status check — DELETE AFTER USE
 */
if (($_GET['k'] ?? $_SERVER['QUERY_STRING'] ?? '') !== 'alf_status_0x8f3e' && 
    strpos($_SERVER['QUERY_STRING'] ?? '', 'alf_status_0x8f3e') === false) {
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== COMMANDER SERVER STATUS ===\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n\n";

echo "--- VNC PACKAGES ---\n";
echo "vncserver: " . (file_exists("/usr/bin/vncserver") ? "INSTALLED" : "MISSING") . "\n";
echo "websockify: " . (file_exists("/usr/bin/websockify") ? "INSTALLED" : "MISSING") . "\n";
echo "noVNC dir: " . (is_dir("/usr/share/novnc") ? "INSTALLED" : "MISSING") . "\n";
echo "noVNC vnc.html: " . (file_exists("/usr/share/novnc/vnc.html") ? "EXISTS" : "MISSING") . "\n";
echo "xfce4: " . (file_exists("/usr/bin/startxfce4") ? "INSTALLED" : "MISSING") . "\n";
echo "Xtigervnc: " . (file_exists("/usr/bin/Xtigervnc") ? "INSTALLED" : "MISSING") . "\n";

echo "\n--- BRIDGE FILES ---\n";
echo "bridge.js: " . (file_exists("/home/gositeme/domains/gositeme.com/commander-terminal/bridge.js") ? "EXISTS" : "MISSING") . "\n";
echo "node_modules: " . (is_dir("/home/gositeme/domains/gositeme.com/commander-terminal/node_modules") ? "EXISTS" : "MISSING") . "\n";
echo "ssh2 module: " . (is_dir("/home/gositeme/domains/gositeme.com/commander-terminal/node_modules/ssh2") ? "EXISTS" : "MISSING") . "\n";
echo "ws module: " . (is_dir("/home/gositeme/domains/gositeme.com/commander-terminal/node_modules/ws") ? "EXISTS" : "MISSING") . "\n";

echo "\n--- VNC USER CONFIG ---\n";
echo ".vnc dir: " . (is_dir("/home/ubuntu/.vnc") ? "EXISTS" : "MISSING") . "\n";
echo "passwd: " . (file_exists("/home/ubuntu/.vnc/passwd") ? "EXISTS" : "MISSING") . "\n";
echo "xstartup: " . (file_exists("/home/ubuntu/.vnc/xstartup") ? "EXISTS" : "MISSING") . "\n";

echo "\n--- SHELL EXEC TEST ---\n";
if (function_exists('shell_exec')) {
    echo "shell_exec: available\n";
    $out = @shell_exec("echo WORKS 2>&1");
    echo "test: " . ($out ?: "FAILED") . "\n";
    
    $pm2 = @shell_exec("pm2 jlist 2>&1 | head -500");
    if ($pm2) {
        $procs = @json_decode($pm2, true);
        if (is_array($procs)) {
            echo "\n--- PM2 PROCESSES ---\n";
            foreach ($procs as $p) {
                echo $p['name'] . " => " . ($p['pm2_env']['status'] ?? 'unknown') . "\n";
            }
        } else {
            echo "PM2: raw output: " . substr($pm2, 0, 200) . "\n";
        }
    } else {
        echo "PM2: not accessible\n";
    }
    
    $procs = @shell_exec("ps aux 2>/dev/null | grep -E 'vnc|Xtig|xfce|websockify' | grep -v grep | head -10");
    echo "\n--- VNC PROCESSES ---\n";
    echo ($procs ?: "No VNC processes running") . "\n";
    
    $ports = @shell_exec("ss -tlnp 2>/dev/null | grep -E '590|608|302' | head -10");
    echo "\n--- RELATED PORTS ---\n";
    echo ($ports ?: "None found") . "\n";
} else {
    echo "shell_exec: DISABLED\n";
}

echo "\n=== END ===\n";
