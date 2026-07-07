<?php
/**
 * Build Status Monitor — Check ISO build progress
 * Visit: https://root.com/admin-build-status.php?k=BUILD-777
 */
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['k'] ?? '') !== 'BUILD-777') { http_response_code(404); exit('Not found'); }

$logFile = '/home/root/build-ga-' . date('Ymd') . '.log';
$isoDir = '/home/root/alfred-linux-v2';

echo "═══ BUILD STATUS — " . date('Y-m-d H:i:s T') . " ═══\n\n";

// Check if build process is running
$running = false;
$ps = shell_exec("pgrep -fa 'build-unified' 2>/dev/null");
if ($ps && trim($ps)) {
    echo "STATUS: 🔄 BUILD IN PROGRESS\n";
    echo "Process: " . trim($ps) . "\n\n";
    $running = true;
} else {
    echo "STATUS: ⏹ No active build process\n\n";
}

// Check log file
if (file_exists($logFile)) {
    $size = filesize($logFile);
    $lines = intval(shell_exec("wc -l < " . escapeshellarg($logFile)));
    echo "Log: {$logFile}\n";
    echo "Size: " . round($size / 1024) . " KB ({$lines} lines)\n\n";

    // Show last 30 lines
    echo "── Last 30 lines ──\n";
    echo shell_exec("tail -30 " . escapeshellarg($logFile));
    echo "\n";

    // Check for success/failure indicators
    $tail = shell_exec("tail -100 " . escapeshellarg($logFile));
    if (stripos($tail, 'BUILD COMPLETE') !== false || stripos($tail, 'ISO created') !== false) {
        echo "\n✓✓✓ BUILD APPEARS SUCCESSFUL ✓✓✓\n";
    } elseif (stripos($tail, 'BUILD FAILED') !== false || stripos($tail, 'exited with code') !== false) {
        echo "\n✗✗✗ BUILD FAILED ✗✗✗\n";
    }
} else {
    echo "No log file found for today.\n";
    // Check for any recent log
    $logs = glob('/home/root/build-ga-*.log');
    if ($logs) {
        sort($logs);
        $latest = end($logs);
        echo "Latest log: {$latest}\n";
        echo "Last 10 lines:\n";
        echo shell_exec("tail -10 " . escapeshellarg($latest));
    }
}

// Check for ISO files
echo "\n── ISO Files ──\n";
$isos = glob($isoDir . '/*.iso') ?: [];
$isos = array_merge($isos, glob('/home/root/law/*.iso') ?: []);
if ($isos) {
    foreach ($isos as $iso) {
        $s = filesize($iso);
        $m = date('Y-m-d H:i', filemtime($iso));
        printf("  %s  %s  %s\n", basename($iso), round($s / 1048576) . " MB", $m);
    }
} else {
    echo "  No ISO files found\n";
}

// Engine status
echo "\n── Engine Status ──\n";
$ch = curl_init('http://127.0.0.1:7777/health');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$h = json_decode(curl_exec($ch), true);
curl_close($ch);
echo $h ? "  Engine: alive (uptime: {$h['uptime']}s)\n" : "  Engine: NOT RESPONDING\n";

// Disk space
echo "\n── Disk Space ──\n";
echo shell_exec("df -h / | tail -1");
echo "\n";
