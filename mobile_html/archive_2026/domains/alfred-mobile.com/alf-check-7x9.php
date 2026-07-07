<?php
header('Content-Type: text/plain');
echo "OK\n";
echo "vncserver: " . (file_exists("/usr/bin/vncserver") ? "YES" : "NO") . "\n";
echo "websockify: " . (file_exists("/usr/bin/websockify") ? "YES" : "NO") . "\n";
echo "novnc: " . (is_dir("/usr/share/novnc") ? "YES" : "NO") . "\n";
echo "xfce4: " . (file_exists("/usr/bin/startxfce4") ? "YES" : "NO") . "\n";
echo "bridge: " . (file_exists("/home/gositeme/domains/gositeme.com/commander-terminal/bridge.js") ? "YES" : "NO") . "\n";
echo "ssh2: " . (is_dir("/home/gositeme/domains/gositeme.com/commander-terminal/node_modules/ssh2") ? "YES" : "NO") . "\n";
echo "vncdir: " . (is_dir("/home/ubuntu/.vnc") ? "YES" : "NO") . "\n";
echo "shell: " . (function_exists('shell_exec') ? "YES" : "NO") . "\n";
if (function_exists('shell_exec')) {
    $r = @shell_exec("ps aux 2>/dev/null | grep -cE 'vnc|Xtig|websockify'");
    echo "vnc_procs: " . trim($r ?? "0") . "\n";
}
