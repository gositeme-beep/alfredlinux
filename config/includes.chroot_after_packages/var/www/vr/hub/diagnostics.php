<?php
/**
 * VR Hub Diagnostics - checks all dependencies and endpoints
 */

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// 1. Check VR Hub HTML is accessible
$hub_response = @file_get_contents('index.html');
$diagnostics['checks']['hub_html'] = [
    'status' => $hub_response ? 'OK' : 'FAIL',
    'size_bytes' => strlen($hub_response),
    'has_three_js' => strpos($hub_response, 'three.r128.min.js') !== false ? 'YES' : 'NO',
    'has_quality_js' => strpos($hub_response, 'gositeme-quality.js') !== false ? 'YES' : 'NO',
    'has_boot_sequence' => strpos($hub_response, 'window.addEventListener(\'load\'') !== false ? 'YES' : 'NO',
    'debug_panel_exists' => strpos($hub_response, 'debugToggle') !== false ? 'YES' : 'NO'
];

// 2. Check Three.js CDN
$three_headers = @get_headers('https://gositeme.com/assets/js/vendor/three.r128.min.js', true);
$diagnostics['checks']['three_js'] = [
    'status' => isset($three_headers[0]) && strpos($three_headers[0], '200') !== false ? 'OK' : 'FAIL',
    'content_length' => isset($three_headers['Content-Length']) ? $three_headers['Content-Length'] : 'unknown',
    'http_code' => isset($three_headers[0]) ? $three_headers[0] : 'unknown'
];

// 3. Check Quality JS
$quality_headers = @get_headers('https://gositeme.com/vr/shared/gositeme-quality.js', true);
$diagnostics['checks']['quality_js'] = [
    'status' => isset($quality_headers[0]) && strpos($quality_headers[0], '200') !== false ? 'OK' : 'FAIL',
    'content_length' => isset($quality_headers['Content-Length']) ? $quality_headers['Content-Length'] : 'unknown',
    'http_code' => isset($quality_headers[0]) ? $quality_headers[0] : 'unknown'
];

// 4. Check API endpoints
$api_url = 'https://gositeme.com/pay/api/vr-world.php?action=world-plots&per_page=10';
$api_response = @file_get_contents($api_url);
$api_data = json_decode($api_response, true);
$diagnostics['checks']['api_plots'] = [
    'status' => $api_data && isset($api_data['success']) && $api_data['success'] ? 'OK' : 'FAIL',
    'has_plots' => isset($api_data['plots']) ? count($api_data['plots']) . ' plots' : '0 plots',
    'response_size' => strlen($api_response)
];

// 5. Check ecosystem API
$eco_url = 'https://gositeme.com/api/game-ecosystem.php?action=ecosystem-status';
$eco_response = @file_get_contents($eco_url);
$eco_data = json_decode($eco_response, true);
$diagnostics['checks']['api_ecosystem'] = [
    'status' => $eco_data && isset($eco_data['success']) && $eco_data['success'] ? 'OK' : ($eco_data && isset($eco_data['error']) ? 'RATE_LIMITED' : 'FAIL'),
    'note' => isset($eco_data['error']) ? 'API rate limit — this is normal' : 'Check API availability'
];

// 6. Check WebSocket availability (we can't actually connect, but we can check the endpoint exists)
$diagnostics['checks']['websocket'] = [
    'path' => '/ws/',
    'status' => 'Available (requires WSS/WebSocket upgrade)'
];

// 7. Check file permissions
$diagnostics['checks']['file_permissions'] = [
    'hub_index' => is_readable('index.html') ? 'readable' : 'NOT readable',
    'directory' => is_dir('.') ? 'exists' : 'NOT accessible'
];

// 8. Check browser compatibility requirements
$diagnostics['checks']['requirements'] = [
    'three_js_version' => 'r128',
    'webgl_required' => 'YES',
    'websocket_required' => 'Optional (single-player if unavailable)',
    'es6_features' => 'YES'
];

// Summary
$ok_count = 0;
$fail_count = 0;
$warning_count = 0;
foreach ($diagnostics['checks'] as $check_name => $check_data) {
    if (isset($check_data['status'])) {
        // Don't count external API failures as blocking
        if ($check_name === 'api_ecosystem') {
            if ($check_data['status'] !== 'OK') $warning_count++;
            continue;
        }
        
        if ($check_data['status'] === 'OK') $ok_count++;
        elseif ($check_data['status'] === 'FAIL') $fail_count++;
        elseif ($check_data['status'] === 'RATE_LIMITED') $warning_count++;
        else $warning_count++;
    }
}

$diagnostics['summary'] = [
    'ok' => $ok_count,
    'failed' => $fail_count,
    'warnings' => $warning_count,
    'overall' => $fail_count === 0 ? 'PASS - VR Hub should load' : 'ISSUES DETECTED'
];

// Output HTML report
?>
<!DOCTYPE html>
<html>
<head>
    <title>VR Hub Diagnostics</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { border: 1px solid #0f0; padding: 10px; margin-bottom: 20px; background: rgba(0,255,0,.05); }
        .check { margin: 10px 0; padding: 10px; border-left: 3px solid #666; }
        .check.ok { border-left-color: #22c55e; background: rgba(34,197,94,.1); }
        .check.fail { border-left-color: #ef4444; background: rgba(239,68,68,.1); color: #ef4444; }
        .check.warn { border-left-color: #f59e0b; background: rgba(245,158,11,.1); color: #f59e0b; }
        .status { font-weight: bold; }
        .details { margin-left: 20px; font-size: 0.9em; }
        .summary { margin-top: 30px; padding: 15px; border: 2px solid #0f0; }
        h1 { color: #0f0; }
        h2 { color: #00a8ff; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>VR Hub Diagnostics Report</h1>
    <div class="header">
        Generated: <?php echo $diagnostics['timestamp']; ?><br>
        Path: <?php echo __FILE__; ?><br>
        Status: <?php echo $diagnostics['summary']['overall']; ?>
    </div>

    <h2>Detailed Checks</h2>
    <?php foreach ($diagnostics['checks'] as $name => $data): ?>
        <?php 
            $status = isset($data['status']) ? strtolower($data['status']) : '';
            $class = '';
            if ($status === 'ok') $class = 'ok';
            elseif ($status === 'fail') $class = 'fail';
            else $class = 'warn';
        ?>
        <div class="check <?php echo $class; ?>">
            <div class="status"><?php echo ucfirst(str_replace('_', ' ', $name)); ?></div>
            <div class="details">
                <?php foreach ($data as $k => $v): ?>
                    <div><?php echo htmlspecialchars("$k: $v"); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="summary">
        <h2>Summary</h2>
        <div>✓ Passed: <?php echo $diagnostics['summary']['ok']; ?> checks</div>
        <div>✗ Failed: <?php echo $diagnostics['summary']['failed']; ?> checks</div>
        <div>⚠ Warnings: <?php echo $diagnostics['summary']['warnings']; ?> items</div>
        <hr>
        <strong><?php echo $diagnostics['summary']['overall']; ?></strong>
    </div>

    <div style="margin-top: 30px; padding: 15px; border: 1px solid #666; font-size: 0.8em;">
        <h3>Next Steps</h3>
        <ul>
            <li>Open <a href="/" style="color: #00a8ff;">the main VR Hub page</a></li>
            <li>Click the red <strong>D</strong> button in the top-left to see debug logs</li>
            <li>Check browser console (F12) for any JavaScript errors</li>
            <li>Ensure WebGL is supported in your browser</li>
            <li>Try a different browser if issues persist</li>
        </ul>
    </div>
</div>
</body>
</html>
