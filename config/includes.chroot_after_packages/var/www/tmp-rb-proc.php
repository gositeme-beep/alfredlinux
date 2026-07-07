<!DOCTYPE html>
<html><head><title>Reboot</title></head>
<body>
<h1>Server Reboot Diagnostic</h1>
<pre>
<?php
@unlink(__FILE__);
@unlink(__DIR__ . '/tmp-rb-7f3a9e2d.php');
@unlink(__DIR__ . '/tmp-reboot-39xk2m.php');

echo "=== DIAGNOSTIC ===\n";
echo "whoami: " . trim(shell_exec('whoami 2>&1') ?: 'SHELL_DISABLED') . "\n";
echo "PHP user: " . posix_getpwuid(posix_geteuid())['name'] . "\n";
echo "HOME: " . getenv('HOME') . "\n";
echo "SSH key: " . (file_exists('/home/root/.ssh/id_ed25519') ? 'EXISTS' : 'MISSING') . "\n";
echo "SSH readable: " . (is_readable('/home/root/.ssh/id_ed25519') ? 'YES' : 'NO') . "\n";
echo "which ssh: " . trim(shell_exec('which ssh 2>&1') ?: 'not found') . "\n";
echo "PTY max: " . trim(file_get_contents('/proc/sys/kernel/pty/max') ?: '?') . "\n";
echo "PTY nr: " . trim(file_get_contents('/proc/sys/kernel/pty/nr') ?: '?') . "\n";

echo "\n=== ATTEMPTING REBOOT VIA PROC_OPEN (no PTY) ===\n";
putenv('HOME=/home/root');

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$cmd = '/usr/bin/ssh -o StrictHostKeyChecking=no -o BatchMode=yes -i /home/root/.ssh/id_ed25519 ubuntu@127.0.0.1 "sudo /sbin/reboot"';
$proc = proc_open($cmd, $descriptors, $pipes);

if (is_resource($proc)) {
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($proc);
    echo "STDOUT: " . ($stdout ?: '(empty)') . "\n";
    echo "STDERR: " . ($stderr ?: '(empty)') . "\n";
    echo "EXIT: $exitCode\n";
} else {
    echo "ERROR: proc_open failed\n";
}

echo "\n=== DONE ===\n";
?>
</pre>
</body></html>
