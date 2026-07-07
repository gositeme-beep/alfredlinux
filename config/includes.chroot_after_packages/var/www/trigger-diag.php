<?php
// Trigger diagnostic via HTTP to self
$url = 'http://127.0.0.1:80/run-diag.php';
$ctx = stream_context_create(['http' => [
    'timeout' => 30,
    'header' => "Host: root.com\r\n"
]]);
$result = @file_get_contents($url, false, $ctx);
if ($result === false) {
    // Try port 443 via https
    $url2 = 'https://root.com/run-diag.php';
    $ctx2 = stream_context_create(['http' => ['timeout' => 30], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $result = @file_get_contents($url2, false, $ctx2);
}
if ($result === false) {
    // Try running it directly
    $result = shell_exec('php /var/www/run-diag.php 2>&1');
}
file_put_contents('/home/root/ssh-diag-output.txt', $result ?: 'FAILED TO RUN DIAGNOSTIC');
