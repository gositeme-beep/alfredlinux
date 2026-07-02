<?php
/**
 * KINGDOM BUILD ORCHESTRATOR v2
 * Executes directly via shell — no engine dependency for the core pipeline.
 * Visit: https://root.com/admin-build-orchestrator.php?k=BUILD-777
 * Self-deletes after successful run.
 */
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(600);

if (($_GET['k'] ?? '') !== 'BUILD-777') { http_response_code(404); exit('Not found'); }

$PM2 = '/home/root/.local/node_modules/.bin/pm2';
$HOME = '/home/root';
$SSH_KEY = "$HOME/.ssh/id_ed25519";
$steps = [];

function run($label, $cmd, $timeout = 60) {
    global $steps;
    echo "═══ {$label} ═══\n";
    echo "  CMD: {$cmd}\n";
    flush();
    $output = ''; $exitCode = 1;
    $proc = proc_open($cmd, [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, '/home/root');
    if ($proc) {
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $start = time();
        while (true) {
            $status = proc_get_status($proc);
            $output .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            if (!$status['running']) { $exitCode = $status['exitcode']; break; }
            if (time() - $start > $timeout) { proc_terminate($proc); $output .= "\n[TIMEOUT after {$timeout}s]"; break; }
            usleep(100000);
        }
        fclose($pipes[1]); fclose($pipes[2]); proc_close($proc);
    }
    $ok = ($exitCode === 0);
    $steps[] = ['label' => $label, 'ok' => $ok];
    if (trim($output)) {
        $lines = explode("\n", trim($output));
        if (count($lines) > 30) {
            echo "  ... (" . (count($lines) - 30) . " lines trimmed) ...\n";
            $output = implode("\n", array_slice($lines, -30));
        }
        echo "  " . str_replace("\n", "\n  ", trim($output)) . "\n";
    }
    echo ($ok ? "  ✓ OK (exit 0)" : "  ✗ FAILED (exit {$exitCode})") . "\n\n";
    flush();
    return $ok;
}

function sudo($label, $cmd, $timeout = 60) {
    $sshCmd = 'ssh -o BatchMode=yes -o ConnectTimeout=5 -o StrictHostKeyChecking=no'
        . ' -i /home/root/.ssh/id_ed25519 ubuntu@localhost'
        . ' "sudo ' . str_replace('"', '\\"', $cmd) . '"';
    return run($label, $sshCmd . ' 2>&1', $timeout);
}

echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║  KINGDOM BUILD ORCHESTRATOR v2 — Alfred Linux 7.77                 ║\n";
echo "║  Direct execution mode — no engine dependency                       ║\n";
echo "║  Started: " . date('Y-m-d H:i:s T') . "                                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

// ── STEP 1: Restart engine (pick up sudo fix + HMAC + PATH fix) ──
run('STEP 1: Restart alfred-engine (sudo fix + HMAC)', "$PM2 restart alfred-engine 2>&1", 20);
sleep(3); // Let engine boot

// ── STEP 2: ENOPRO heal via engine ──
$ch = curl_init('http://127.0.0.1:7777/health');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$h = json_decode(curl_exec($ch), true);
curl_close($ch);
if ($h && ($h['status'] ?? '') === 'alive') {
    echo "Engine alive (uptime: {$h['uptime']}s) — good\n\n";
} else {
    echo "WARNING: Engine not responding after restart. Continuing anyway...\n\n";
}

// ── STEP 3: Kill stale workspace locks + WAL files ──
$wsMain = '/home/root/.local/share/code-server/User/workspaceStorage/-71fa0024';
run('STEP 2: Clean stale workspace locks', "bash -c 'rm -f {$wsMain}/vscode.lock {$wsMain}/*.vscdb-wal {$wsMain}/*.vscdb-shm 2>/dev/null; echo cleaned'", 10);

// ── STEP 4: Kill orphan extension hosts + ptyhost ──
run('STEP 3: Kill orphan processes', "bash -c 'pkill -u root -f extensionHost 2>/dev/null; pkill -u root -f ptyhost 2>/dev/null; echo done'", 10);

// ── STEP 5: Raise inotify via sudo ──
sudo('STEP 4: Raise inotify watches', 'sysctl -w fs.inotify.max_user_watches=524288 && sysctl -w fs.inotify.max_user_instances=1024', 15);

// ── STEP 6: Restart alfred-ide ──
run('STEP 5: Restart alfred-ide', "$PM2 restart alfred-ide 2>&1", 20);
sleep(3);

// ── STEP 7: Pre-stage build assets ──
run('STEP 6: Pre-stage build assets', "cd /home/root/alfred-linux-v2 && bash scripts/pre-stage-assets.sh 2>&1", 300);

// ── Summary ──
echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║  ORCHESTRATOR SUMMARY                                              ║\n";
echo "╠══════════════════════════════════════════════════════════════════════╣\n";
$allOk = true;
foreach ($steps as $s) {
    $icon = $s['ok'] ? '✓' : '✗';
    printf("║  %s %-62s  ║\n", $icon, $s['label']);
    if (!$s['ok']) $allOk = false;
}
echo "╠══════════════════════════════════════════════════════════════════════╣\n";
if ($allOk) {
    echo "║  ALL STEPS PASSED — Ready for ISO build                           ║\n";
    echo "║                                                                     ║\n";
    echo "║  NEXT: Visit admin-build-iso.php?k=BUILD-777 to start ISO build   ║\n";
} else {
    echo "║  SOME STEPS FAILED — Check output above                            ║\n";
}
echo "║  Completed: " . date('Y-m-d H:i:s T') . "                                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n";

// Self-delete on success
if ($allOk) {
    echo "\n[Self-deleting for security]\n";
    @unlink(__FILE__);
}
