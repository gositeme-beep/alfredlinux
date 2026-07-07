<?php
// Temporary E2E test script — delete after use
chdir(__DIR__ . '/../..');
$secret = '3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d';

$tests = [
    ['capabilities.php', 'action=list'],
    ['skills.php', 'action=list'],
    ['memory.php', 'action=recall&type=episodic&agent_id=alfred'],
    ['memory.php', 'action=recall&type=semantic&agent_id=alfred'],
    ['memory.php', 'action=recall&type=procedural&agent_id=alfred'],
    ['memory.php', 'action=recall&type=spatial&agent_id=alfred&world_id=default'],
    ['memory.php', 'action=recall&type=relational&subject_id=user_1'],
    ['memory.php', 'action=stats&agent_id=alfred'],
    ['memory.php', 'action=search&q=test&agent_id=alfred'],
    ['policy.php', 'action=list'],
    ['audit.php', 'action=stats'],
    ['world-state.php', 'action=get&world_id=default'],
    ['world-state.php', 'action=entities&world_id=default'],
    ['simulation.php', 'action=list'],
    ['bridge.php', 'action=list'],
    ['tasks.php', 'action=list'],
    ['runtime.php', 'action=sessions'],
    ['runtime.php', 'action=observe'],
];

echo "=== Alfred OS E2E Test Suite ===\n\n";
$pass = 0; $fail = 0;

foreach ($tests as $i => [$file, $qs]) {
    $cmd = sprintf(
        'php -d display_errors=1 -r %s 2>&1',
        escapeshellarg(
            '$_SERVER["REQUEST_METHOD"]="GET";'
            . '$_SERVER["HTTP_X_INTERNAL_SECRET"]="' . $secret . '";'
            . 'parse_str("' . $qs . '", $_GET);'
            . 'ob_start(); include "api/agentos/' . $file . '"; $out=ob_get_clean();'
            . '$r=json_decode($out,true);'
            . 'if($r && isset($r["ok"]) && $r["ok"]) echo "OK|".json_encode(array_diff_key($r,["ok"=>1]));'
            . 'else echo "FAIL|".substr($out,0,300);'
        )
    );
    
    $result = shell_exec($cmd);
    $num = $i + 1;
    
    if (strpos($result, 'OK|') === 0) {
        $data = substr($result, 3);
        echo "✓ [{$num}] {$file}?{$qs} — OK";
        $decoded = json_decode($data, true);
        if ($decoded) {
            $keys = array_keys($decoded);
            foreach ($keys as $k) {
                $v = $decoded[$k];
                if (is_array($v)) echo " | {$k}:" . count($v);
                elseif (is_numeric($v)) echo " | {$k}:{$v}";
            }
        }
        echo "\n";
        $pass++;
    } else {
        $data = substr($result, 5);
        echo "✗ [{$num}] {$file}?{$qs} — FAIL: " . trim(substr($data, 0, 200)) . "\n";
        $fail++;
    }
}

echo "\n=== Results: {$pass} passed, {$fail} failed ===\n";
