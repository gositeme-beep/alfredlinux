<?php
/**
 * ENOPRO Bridge Launcher
 *
 * Purpose:
 * - Run ENOPRO deep safe cleanup and then start next build even when VS Code tool bridge is broken.
 *
 * Usage:
 *   https://root.com/enopro-bridge-launch.php?key=ALFRED-BRIDGE-2026-05-05&action=run
 *
 * Security:
 * - Requires exact key match.
 * - Only accepts fixed actions.
 */

declare(strict_types=1);

$expectedKey = 'ALFRED-BRIDGE-2026-05-05';
$providedKey = (string)($_GET['key'] ?? '');
$action = (string)($_GET['action'] ?? 'status');

if (!hash_equals($expectedKey, $providedKey)) {
    http_response_code(404);
    echo '<!doctype html><html><body><h1>404 Not Found</h1></body></html>';
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');

// For poll action: buffer and discard the human-readable header, return pure JSON
if ($action === 'poll') { ob_start(); }

echo "ENOPRO Bridge Launcher\n";
echo "Time: " . date('c') . "\n";
echo "Action: {$action}\n\n";

$safeScript  = '/home/root/ENOPRO-SAFE.sh';
$buildScript = '/home/root/law/start-next-build.sh';
$logPath     = '/home/root/law/alfredlinux-com-source-live/lb-docker-build.log';
$namePath    = '/home/root/law/alfredlinux-com-source-live/lb-docker.containername';
$statusFile  = '/home/root/law/alfred-bridge-status.json';  // Alfred reads this directly
$watchmanScript = '/home/root/law/alfred-bridge-watchman.php';
$watchmanStatusFile = '/home/root/law/alfred-watchman-status.json';
$watchmanPidFile = '/home/root/law/alfred-watchman.pid';
$watchmanLogFile = '/home/root/law/alfred-watchman.log';

$ensureWatchman = static function () use ($watchmanPidFile, $watchmanScript, $watchmanLogFile): string {
    if (!is_file($watchmanScript)) {
        return 'missing';
    }
    if (is_file($watchmanPidFile)) {
        $existingPid = trim((string) file_get_contents($watchmanPidFile));
        $alive = trim((string) shell_exec('kill -0 ' . escapeshellarg($existingPid) . ' 2>/dev/null; echo $?'));
        if ($existingPid !== '' && $alive === '0') {
            return 'running:' . $existingPid;
        }
        @unlink($watchmanPidFile);
    }
    $cmd = 'nohup php ' . escapeshellarg($watchmanScript)
        . ' >> ' . escapeshellarg($watchmanLogFile)
        . ' 2>&1 < /dev/null & echo $!';
    $pid = trim((string) shell_exec($cmd));
    return $pid !== '' ? 'started:' . $pid : 'failed';
};

if (!is_file($safeScript) || !is_file($buildScript)) {
    echo "ERROR: required scripts missing.\n";
    echo "safeScript exists=" . (is_file($safeScript) ? 'yes' : 'no') . "\n";
    echo "buildScript exists=" . (is_file($buildScript) ? 'yes' : 'no') . "\n";
    exit;
}

if ($action === 'status') {
    echo "safeScript: OK\n";
    echo "buildScript: OK\n";
    echo "watchmanScript: " . (is_file($watchmanScript) ? 'present' : 'missing') . "\n";
    echo "buildLog: " . (is_file($logPath) ? 'present' : 'missing') . "\n";
    $container = is_file($namePath) ? trim((string)@file_get_contents($namePath)) : '';
    echo "containerFile: " . ($container ?: 'missing') . "\n";
    if ($container) {
        $dockerStatus = shell_exec('docker inspect --format "{{.State.Status}} (exit={{.State.ExitCode}})" ' . escapeshellarg($container) . ' 2>&1');
        echo "dockerState: " . trim((string)$dockerStatus) . "\n";
    }
    exit;
}

if ($action === 'logs') {
    $container = is_file($namePath) ? trim((string)@file_get_contents($namePath)) : '';
    if (!$container) {
        echo "ERROR: no container name in containerFile\n";
        exit;
    }
    $lines = (int)($_GET['lines'] ?? 100);
    if ($lines < 1 || $lines > 2000) $lines = 100;
    $out = shell_exec('docker logs --tail ' . $lines . ' ' . escapeshellarg($container) . ' 2>&1');
    echo "Container: {$container}\n";
    echo "--- last {$lines} lines ---\n";
    echo (string)$out;
    exit;
}

if ($action === 'docker-ps') {
    $out = shell_exec('docker ps --filter "name=alfred-lb-build" --format "table {{.Names}}\t{{.Status}}\t{{.ID}}" 2>&1');
    echo (string)$out;
    exit;
}

if ($action === 'check') {
    $buildRoot = '/home/root/law/alfredlinux-com-source-live';
    $cpJson    = '/home/root/law/alfred-build-control-plane/last-lb-docker.json';
    $nsState   = '/home/root/law/night-shift-state.txt';

    // Control plane JSON
    if (is_file($cpJson)) {
        $data = json_decode((string)file_get_contents($cpJson), true);
        echo "=== last-lb-docker.json ===\n";
        echo "phase:      " . ($data['phase']        ?? '?') . "\n";
        echo "container:  " . ($data['container']    ?? '?') . "\n";
        echo "docker_exit:" . ($data['docker_exit']  ?? '?') . "\n";
        echo "progress:   " . ($data['progress_pct'] ?? '?') . "%\n";
        echo "note:       " . ($data['note']         ?? '') . "\n";
        echo "ts:         " . ($data['ts'] ?? '') . "  (" . ($data['ts'] ? date('Y-m-d H:i:s', (int)$data['ts']) : '') . ")\n";
        if (!empty($data['iso_paths'])) {
            echo "iso_paths:\n";
            foreach ($data['iso_paths'] as $p) {
                if (is_file($p)) {
                    echo "  $p  [" . round(filesize($p)/1048576) . " MiB, modified " . date('Y-m-d H:i', filemtime($p)) . "]\n";
                } else {
                    echo "  $p  [MISSING]\n";
                }
            }
        }
    } else {
        echo "control plane JSON: missing\n";
    }

    // night-shift state
    echo "\n=== night-shift-state.txt ===\n";
    echo is_file($nsState) ? file_get_contents($nsState) : "(missing)\n";

    // All recent alfred-lb-build containers (running + exited)
    echo "\n=== docker ps -a (alfred-lb-build, last 5) ===\n";
    $ps = shell_exec('docker ps -a --filter "name=alfred-lb-build" --format "{{.Names}}\t{{.Status}}\t{{.CreatedAt}}" 2>&1');
    echo ($ps ?: "(none)\n");

    // If any exited container, grab its logs
    $exitedName = trim((string)shell_exec('docker ps -a --filter "name=alfred-lb-build" --filter "status=exited" --format "{{.Names}}" --latest 2>/dev/null'));
    if ($exitedName) {
        echo "\n=== docker logs (last 80 lines from exited: $exitedName) ===\n";
        echo (string)shell_exec('docker logs --tail 80 ' . escapeshellarg($exitedName) . ' 2>&1');
    }

    // ISO output dir
    echo "\n=== iso-output/ ===\n";
    $isoOut = $buildRoot . '/iso-output';
    if (is_dir($isoOut)) {
        $isos = glob($isoOut . '/*.iso') ?: [];
        foreach ($isos as $iso) {
            echo basename($iso) . "  " . round(filesize($iso)/1048576) . " MiB  " . date('Y-m-d H:i:s', filemtime($iso)) . "\n";
        }
        if (!$isos) echo "(no .iso files yet)\n";
    } else {
        echo "directory missing\n";
    }

    // build log tail
    if (is_file($logPath)) {
        echo "\n=== build log (last 40 lines) ===\n";
        $lines = array_slice(file($logPath), -40);
        echo implode('', $lines);
    } else {
        // Try to get logs from most recently exited container
        $lastContainer = trim((string)shell_exec('docker ps -a --filter "name=alfred-lb-build" --format "{{.Names}}" --latest 2>/dev/null'));
        if ($lastContainer) {
            echo "\n=== docker logs (last 60 lines from $lastContainer) ===\n";
            echo (string)shell_exec('docker logs --tail 60 ' . escapeshellarg($lastContainer) . ' 2>&1');
        } else {
            echo "\nbuild log: missing and no recent containers found\n";
        }
    }
    exit;
}

if ($action === 'poll') {
    // poll returns pure JSON — no header text
    $buildRoot = '/home/root/law/alfredlinux-com-source-live';
    $cpJson    = '/home/root/law/alfred-build-control-plane/last-lb-docker.json';
    $container = is_file($namePath) ? trim((string)file_get_contents($namePath)) : '';

    $status = [
        'polled_at'   => date('c'),
        'container'   => $container,
        'docker_state'=> '',
        'build_phase' => '',
        'iso_mtime'   => '',
        'iso_size_mib'=> 0,
        'build_iso_mtime' => '',
        'build_iso_size_mib' => 0,
        'log_tail'    => '',
        'cp'          => [],
    ];

    // Docker state
    if ($container) {
        $ds = trim((string)shell_exec('docker inspect --format "{{.State.Status}} exit={{.State.ExitCode}}" ' . escapeshellarg($container) . ' 2>&1'));
        $status['docker_state'] = $ds;
    }

    // Control plane
    if (is_file($cpJson)) {
        $cp = json_decode((string)file_get_contents($cpJson), true) ?? [];
        $status['cp'] = $cp;
        $status['build_phase'] = $cp['phase'] ?? '';
    }

    // ISO in iso-output/
    $iso = $buildRoot . '/iso-output/live-image-amd64.hybrid.iso';
    if (is_file($iso)) {
        $status['iso_mtime']    = date('Y-m-d H:i:s', filemtime($iso));
        $status['iso_size_mib'] = round(filesize($iso) / 1048576);
    }

    // ISO in build/ (written here first before restage moves it)
    $buildIso = $buildRoot . '/build/live-image-amd64.hybrid.iso';
    if (is_file($buildIso)) {
        $status['build_iso_mtime']    = date('Y-m-d H:i:s', filemtime($buildIso));
        $status['build_iso_size_mib'] = round(filesize($buildIso) / 1048576);
    }

    // Log tail or docker logs
    if (is_file($logPath)) {
        $lines = array_slice(file($logPath), -30);
        $status['log_tail'] = implode('', $lines);
    } elseif ($container) {
        $status['log_tail'] = (string)shell_exec('docker logs --tail 30 ' . escapeshellarg($container) . ' 2>&1');
    }

    // Write status file for Alfred to read directly
    file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Discard buffered header text, send pure JSON
    ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'clearlock') {
    $buildRoot = '/home/root/law/alfredlinux-com-source-live';
    $lockFile  = $buildRoot . '/build/.alfred-lb-docker-build.lock';
    $watchLock = $buildRoot . '/.lb-docker-watch.lock';
    echo "=== Clear Build Locks ===\n";
    foreach ([$lockFile, $watchLock] as $f) {
        if (is_file($f)) {
            // Check if any process holds it
            $fuser = trim((string)shell_exec('fuser ' . escapeshellarg($f) . ' 2>&1'));
            if ($fuser) {
                echo "LOCKED by PID $fuser: $f — not clearing (active process)\n";
            } else {
                unlink($f);
                echo "Cleared: $f\n";
            }
        } else {
            echo "Not present: $f\n";
        }
    }
    // Also show lock file ownership
    $ls = shell_exec('ls -la ' . escapeshellarg($buildRoot . '/build/') . ' 2>&1 | grep lock');
    if ($ls) echo "\nRemaining locks:\n$ls";
    exit;
}

if ($action === 'watchman-status') {
    echo "watchmanScript: " . (is_file($watchmanScript) ? 'present' : 'missing') . "\n";
    echo "watchmanPid: " . (is_file($watchmanPidFile) ? trim((string) file_get_contents($watchmanPidFile)) : 'missing') . "\n";
    echo "watchmanStatus: " . (is_file($watchmanStatusFile) ? 'present' : 'missing') . "\n";
    echo "watchmanLog: " . (is_file($watchmanLogFile) ? 'present' : 'missing') . "\n";
    if (is_file($watchmanStatusFile)) {
        echo "\n=== watchman status json ===\n";
        echo (string) file_get_contents($watchmanStatusFile);
    }
    exit;
}

if ($action === 'watchman-start') {
    $result = $ensureWatchman();
    echo "watchman: $result\n";
    echo "status: ?key={$expectedKey}&action=watchman-status\n";
    exit;
}

if ($action === 'watchman-stop') {
    if (!is_file($watchmanPidFile)) {
        echo "watchman not running\n";
        exit;
    }
    $pid = trim((string) file_get_contents($watchmanPidFile));
    shell_exec('kill ' . escapeshellarg($pid) . ' 2>&1');
    @unlink($watchmanPidFile);
    echo "watchman stopped: pid=$pid\n";
    exit;
}

if ($action === 'finalize') {
    $buildRoot  = '/home/root/law/alfredlinux-com-source-live';
    $restage    = $buildRoot . '/scripts/ops/post-build-restage.sh';
    $nightShift = $buildRoot . '/scripts/ops/alfred-night-shift.sh';
    $buildIso   = $buildRoot . '/build/live-image-amd64.hybrid.iso';
    $outIso     = $buildRoot . '/iso-output/live-image-amd64.hybrid.iso';

    echo "=== Finalize Build ===\n";
    echo "time: " . date('c') . "\n\n";

    if (is_file($buildIso)) {
        echo "build/ISO: " . round(filesize($buildIso)/1048576) . " MiB  " . date('Y-m-d H:i:s', filemtime($buildIso)) . "\n";
    } else {
        echo "build/ISO: not found — build may not have completed yet\n";
    }
    echo "iso-output/ISO: " . (is_file($outIso) ? round(filesize($outIso)/1048576) . ' MiB  ' . date('Y-m-d H:i:s', filemtime($outIso)) : 'missing') . "\n";

    if (is_file($restage)) {
        echo "\n[restage]\n";
        echo (string)shell_exec('bash ' . escapeshellarg($restage) . ' 2>&1');
    } elseif (is_file($nightShift)) {
        echo "\n[night-shift one-shot]\n";
        echo (string)shell_exec('ALFRED_NIGHT_SHIFT_ONE_SHOT=1 bash ' . escapeshellarg($nightShift) . ' 2>&1');
    } else {
        echo "ERROR: no restage or night-shift script found\n";
    }

    echo "\n=== post-finalize ===\n";
    echo "iso-output/ISO: " . (is_file($outIso) ? round(filesize($outIso)/1048576) . ' MiB  ' . date('Y-m-d H:i:s', filemtime($outIso)) : 'missing') . "\n";
    exit;
}

if ($action === 'debug') {
    echo "whoami: "    . trim((string)shell_exec('whoami 2>&1'))          . "\n";
    echo "id: "        . trim((string)shell_exec('id 2>&1'))              . "\n";
    echo "docker: "    . trim((string)shell_exec('which docker 2>&1'))    . "\n";
    echo "docker ps: " . trim((string)shell_exec('docker ps 2>&1'))       . "\n";
    echo "groups: "    . trim((string)shell_exec('groups 2>&1'))          . "\n";
    echo "lb: "        . trim((string)shell_exec('which lb 2>&1'))        . "\n";
    echo "start-next-build.sh last 20 lines of run:\n";
    $testOut = shell_exec('bash /home/root/law/start-next-build.sh 2>&1 | tail -30');
    echo (string)$testOut;
    exit;
}

if ($action !== 'run' && $action !== 'relaunch') {
    echo "ERROR: unsupported action. Use: status, poll, check, logs, docker-ps, clearlock, finalize, debug, watchman-start, watchman-status, watchman-stop, relaunch, run\n";
    exit;
}

// relaunch: run build directly with output going straight to log file, bypassing night-shift
if ($action === 'relaunch') {
    $buildRoot  = '/home/root/law/alfredlinux-com-source-live';
    $logFile    = $buildRoot . '/lb-docker-build.log';
    $namefile   = $buildRoot . '/lb-docker.containername';
    $stageScript = $buildRoot . '/scripts/stage-kernel-debs-for-iso.sh';
    $innerScript = $buildRoot . '/scripts/lb-docker-inner-build.sh';
    $name       = 'alfred-lb-build-' . time();
    $image      = 'debian:bookworm';

    echo "=== Relaunch Build ===\n";

    // Stage kernels first
    echo "[1] Stage kernel debs\n";
    $stageOut = shell_exec('cd ' . escapeshellarg($buildRoot) . ' && bash ' . escapeshellarg($stageScript) . ' --strict 2>&1');
    echo (string)$stageOut;

    // Check for running containers
    $running = trim((string)shell_exec('docker ps --filter "name=alfred-lb-build" --format "{{.Names}}" 2>&1'));
    if ($running) {
        echo "ERROR: existing build container already running: $running\n";
        echo "Stop it first or wait for it to finish.\n";
        exit;
    }

    // Launch detached with log via bind mount
    $uid = trim((string)shell_exec('id -u 2>&1'));
    $gid = trim((string)shell_exec('id -g 2>&1'));

    $cmd = 'docker run -d --init --privileged --network=host'
         . ' --name ' . escapeshellarg($name)
         . ' -e DEBIAN_FRONTEND=noninteractive'
         . ' -e BUILD_UID=' . escapeshellarg($uid)
         . ' -e BUILD_GID=' . escapeshellarg($gid)
         . ' -e ALFRED_LB_DOCKER_FLOCK_BLOCKING=1'
         . ' -e ALFRED_ALLOW_SSH_PASSWORD_AUTH=0'
         . ' -v ' . escapeshellarg($buildRoot) . ':/work'
         . ' -w /work'
         . ' ' . escapeshellarg($image)
         . ' bash /work/scripts/lb-docker-inner-build.sh'
         . ' 2>&1';

    echo "[2] Launch container: $name\n";
    $out = shell_exec($cmd);
    echo (string)$out;

    // Save container name
    file_put_contents($namefile, $name);
    $watchmanState = $ensureWatchman();

    // Verify it started
    sleep(3);
    $state = trim((string)shell_exec('docker inspect --format "{{.State.Status}}" ' . escapeshellarg($name) . ' 2>&1'));
    echo "[3] Container state after 3s: $state\n";
    echo "[4] Watchman: $watchmanState\n";
    echo "Log will appear at: $logFile\n";
    echo "Check progress: action=check\n";
    exit;
}

$commands = [
    'bash ' . escapeshellarg($safeScript) . ' --deep',
    'bash ' . escapeshellarg($buildScript),
];

foreach ($commands as $idx => $cmd) {
    $step = $idx + 1;
    echo "\n--- step {$step} ---\n";
    echo "cmd: {$cmd}\n";
    $output = [];
    $exitCode = 0;
    exec($cmd . ' 2>&1', $output, $exitCode);
    if ($output) {
        echo implode("\n", $output) . "\n";
    }
    echo "exit={$exitCode}\n";
    if ($exitCode !== 0) {
        echo "FAILED at step {$step}\n";
        exit;
    }
}

$watchmanState = $ensureWatchman();

echo "\nSUCCESS: deep ENOPRO cleanup + build launcher completed.\n";
echo "Watchman: $watchmanState\n";
if (is_file($namePath)) {
    echo "Container: " . trim((string)@file_get_contents($namePath)) . "\n";
}
if (is_file($logPath)) {
    echo "Log: {$logPath}\n";
}
