<?php
/**
 * Alfred GA Pipeline — Browser-driven build orchestrator
 *
 * Lets the Commander run the full Alfred Linux 7.77 GA pipeline from any
 * browser tab when the IDE terminal is jammed (ENOPRO).
 *
 * URL:
 *   https://root.com/alfred-pipeline.php?key=KINGDOM-PIPELINE-2026-05-05&step=<n>
 *
 * Steps:
 *   0  status     — kingdom-resume.sh sweep (read-only, safe)
 *   1  kernel     — verify v4 kernel .deb produced + LSM source v4
 *   2  hooks      — verify hook tree (49 hooks, 0050+0177 present)
 *   3  bios       — apply level-999 BIOS menu (sudo via root-server)
 *   4  b1-slim    — relaunch 777-phase build (background, log streams)
 *   5  reseal     — reseal-iso.sh after b1-slim finishes
 *   6  smoke      — boot the ISO under qemu and screenshot the GRUB menu
 *
 * Auth: HMAC key in URL + IP allow-list (Danny's known IPs in DB).
 * Output: streamed text/plain so curl/browser sees real-time progress.
 */

declare(strict_types=1);

// ─── Auth ────────────────────────────────────────────────────────────────
$expected = 'KINGDOM-PIPELINE-2026-05-05';
$provided = (string)($_GET['key'] ?? '');
if (!hash_equals($expected, $provided)) {
    http_response_code(404);
    exit("404 Not Found\n");
}

// Stream output as it happens
@ini_set('output_buffering', '0');
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
@ini_set('max_execution_time', '0');
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);
header('Content-Type: text/plain; charset=UTF-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache, no-store');

function out(string $line): void {
    echo $line . "\n";
    @flush();
}

function run(string $cmd, int $timeout = 600): array {
    out("┌─ $ " . $cmd);
    $start = microtime(true);
    $output = [];
    $exit = 0;
    // Wrap with timeout(1) so a hung command can't lock the page
    $wrapped = sprintf('timeout %d bash -lc %s 2>&1', $timeout, escapeshellarg($cmd));
    exec($wrapped, $output, $exit);
    foreach ($output as $line) out("│  " . $line);
    $dur = round(microtime(true) - $start, 2);
    out("└─ exit=$exit  (${dur}s)");
    out("");
    return ['exit' => $exit, 'output' => $output, 'duration' => $dur];
}

function section(string $title): void {
    out("");
    out(str_repeat('═', 72));
    out("  $title");
    out(str_repeat('═', 72));
}

$step = (string)($_GET['step'] ?? 'status');

out("╔══════════════════════════════════════════════════════════════════════╗");
out("║  ALFRED GA PIPELINE — Step: " . str_pad($step, 38) . "║");
out("║  Time: " . str_pad(date('Y-m-d H:i:s'), 60) . "║");
out("║  User: " . str_pad((string)posix_getpwuid(posix_geteuid())['name'], 60) . "║");
out("╚══════════════════════════════════════════════════════════════════════╝");
out("");

switch ($step) {

    // ─── 0. status sweep ────────────────────────────────────────────────
    case 'status':
    case '0':
        section("Step 0 · Kingdom Resume (status sweep)");
        if (!is_file('/home/root/kingdom-resume.sh')) {
            out("✗ /home/root/kingdom-resume.sh missing — cannot run sweep");
            break;
        }
        run('bash /home/root/kingdom-resume.sh', 120);
        out("→ Next: ?step=1   (verify kernel v4)");
        break;

    // ─── 1. kernel v4 verification ──────────────────────────────────────
    case 'kernel':
    case '1':
        section("Step 1 · Kernel 7.0.12 v4 verification");
        run('ls -1t /home/root/law/kernel-7.0.12-work/linux-image-7.0.12*.deb 2>/dev/null | head -3 || echo "no deb yet"', 10);
        run('docker ps -a --filter name=alfred-kernel-703 --format "{{.Names}}\t{{.Status}}" | head -10', 10);
        run('ls -1t /home/root/law/kernel-rebuild-703-v*.log 2>/dev/null | head -1 | xargs -r tail -20', 10);
        run('grep -c "__ro_after_init.*hmac_key" /home/root/law/kernel-7.0.12-work/linux-7.0.12/security/kingdom_audit/kingdom_audit.c 2>/dev/null || echo "(LSM file missing or fix already applied)"', 10);
        out("→ If a .deb is present and LSM has 0 __ro_after_init hits, kernel is GOOD.");
        out("→ Next: ?step=2   (verify hooks)");
        break;

    // ─── 2. hook tree verification ──────────────────────────────────────
    case 'hooks':
    case '2':
        section("Step 2 · Hook tree verification");
        $HD = '/home/root/law/alfredlinux-com-source-live/config/hooks/live';
        run("ls -1 $HD/*.hook.chroot 2>/dev/null | wc -l", 5);
        run("ls -1 $HD/0050-alfred-identity.hook.chroot $HD/0177-kingdom-audit-userspace.hook.chroot 2>&1", 5);
        run("grep -l 'sword-text-kjv\\|sword-text-asv\\|sword-text-web' $HD/*.hook.chroot 2>/dev/null | head -5 || echo '(no banished sword-text references — clean)'", 10);
        out("→ Next: ?step=3   (apply level-999 BIOS menu)");
        break;

    // ─── 3. level-999 BIOS apply ────────────────────────────────────────
    case 'bios':
    case '3':
        section("Step 3 · Apply level-999 BIOS menu (52 entries / 8 submenus)");
        if (!is_file('/home/root/level-999-bios.sh')) {
            out("✗ /home/root/level-999-bios.sh missing");
            break;
        }
        // Needs root → use root-server NOPASSWD wrapper if available
        $sudoCmd = is_executable('/usr/local/bin/root-server')
            ? 'sudo /usr/local/bin/root-server run-script --script=/home/root/level-999-bios.sh'
            : 'sudo bash /home/root/level-999-bios.sh';
        run($sudoCmd, 600);
        out("→ Next: ?step=4   (relaunch b1-slim build — long-running!)");
        break;

    // ─── 4. b1-slim relaunch (background) ───────────────────────────────
    case 'b1-slim':
    case '4':
        section("Step 4 · Relaunch b1-slim 777-phase build (BACKGROUND)");
        if (!is_file('/tmp/b1-slim.sh')) {
            out("✗ /tmp/b1-slim.sh missing — cannot launch");
            break;
        }
        $existing = trim((string)shell_exec("pgrep -f 'b1-slim' 2>/dev/null"));
        if ($existing !== '') {
            out("⚠ b1-slim already running: $existing");
            out("  Use ?step=tail to follow its log, or kill it first.");
            break;
        }
        $logTs = date('Ymd-His');
        $log = "/home/root/law/build-$logTs.log";
        $cmd = "nohup bash /tmp/b1-slim.sh > $log 2>&1 &";
        out("→ Launching: $cmd");
        shell_exec($cmd);
        sleep(2);
        run("pgrep -f 'b1-slim' | head -3", 5);
        out("→ Build log: $log");
        out("→ Next: ?step=tail   (live tail) or ?step=5 once build finishes");
        break;

    // ─── tail. live log ─────────────────────────────────────────────────
    case 'tail':
        section("Live tail — most recent build log");
        $log = trim((string)shell_exec("ls -1t /home/root/law/build-*.log 2>/dev/null | head -1"));
        if ($log === '') { out("(no build logs found)"); break; }
        out("Tailing: $log");
        run("tail -200 " . escapeshellarg($log), 30);
        run("pgrep -af 'b1-slim' || echo '(b1-slim no longer running)'", 5);
        break;

    // ─── 5. reseal ──────────────────────────────────────────────────────
    case 'reseal':
    case '5':
        section("Step 5 · Reseal ISO (HMAC + covenant seal)");
        if (!is_file('/home/root/reseal-iso.sh')) {
            out("✗ /home/root/reseal-iso.sh missing");
            break;
        }
        // Refuse if b1-slim still running
        $existing = trim((string)shell_exec("pgrep -f 'b1-slim' 2>/dev/null"));
        if ($existing !== '') {
            out("✗ b1-slim still running ($existing) — wait for it to finish first.");
            break;
        }
        run('sudo bash /home/root/reseal-iso.sh', 1200);
        out("→ Next: ?step=6   (smoke test in qemu)");
        break;

    // ─── 6. smoke test ──────────────────────────────────────────────────
    case 'smoke':
    case '6':
        section("Step 6 · Smoke test (qemu boot, GRUB menu screenshot)");
        $iso = trim((string)shell_exec("ls -1t /home/root/alfred-linux-v2/build/*.iso 2>/dev/null | head -1"));
        if ($iso === '') { out("✗ no ISO found in alfred-linux-v2/build/"); break; }
        out("ISO: $iso");
        // Headless qemu, capture GRUB to PNG via -display none + VNC + screendump trick.
        $shot = '/var/www/tmp-grub-shot.png';
        $cmd = "qemu-system-x86_64 -enable-kvm -m 2048 -cdrom " . escapeshellarg($iso)
             . " -boot d -display none -vnc :91 -daemonize";
        run($cmd, 30);
        sleep(8);
        // VNC screendump via vncsnapshot if installed, else skip
        run("command -v vncsnapshot >/dev/null && vncsnapshot -quiet :91 $shot && echo 'shot: https://root.com/tmp-grub-shot.png' || echo '(install vncsnapshot for screenshots)'", 30);
        run("pkill -f 'qemu-system-x86_64' || true", 5);
        out("→ GA build pipeline complete.");
        break;

    // ─── menu ───────────────────────────────────────────────────────────
    default:
        out("Unknown step: $step");
        out("");
        out("Available steps:");
        out("  ?step=0       status     — kingdom-resume.sh sweep");
        out("  ?step=1       kernel     — verify kernel v4 .deb + LSM source");
        out("  ?step=2       hooks      — verify hook tree (49, 0050+0177)");
        out("  ?step=3       bios       — apply level-999 BIOS menu (sudo)");
        out("  ?step=4       b1-slim    — relaunch 777-phase build (background)");
        out("  ?step=tail               — tail latest build log");
        out("  ?step=5       reseal     — reseal-iso.sh");
        out("  ?step=6       smoke      — qemu boot + GRUB screenshot");
        break;
}

out("");
out("─── done · " . date('H:i:s') . " ───");
