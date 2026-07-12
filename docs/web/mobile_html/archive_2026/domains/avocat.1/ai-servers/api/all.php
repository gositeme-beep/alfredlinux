<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$categories = ['gpus', 'cpus', 'motherboards', 'ram', 'storage', 'psus', 'cases'];
$pathBase = rtrim(AI_SERVERS_PRODUCT_IMAGE_BASE, '/');
// Absolute URL for images so they load from any domain/proxy
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$base = $scheme . '://' . $host . $pathBase;
$out = ['currency' => AI_SERVERS_CURRENCY, 'products' => [], 'presets' => []];

foreach ($categories as $cat) {
    $file = AI_SERVERS_DATA_DIR . '/' . $cat . '.json';
    if (!is_file($file)) continue;
    $data = json_decode(file_get_contents($file), true);
    $key = rtrim($cat, 's');
    if ($key === 'motherboard') $key = 'motherboards';
    $key = $key === 'ram' ? 'ram' : $key;
    foreach ($data as &$p) {
        if (!empty($p['imageUrl'])) {
            if (strpos($p['imageUrl'], 'http') === 0) {
                // already absolute
            } elseif (strpos($p['imageUrl'], '/') === 0) {
                $p['imageUrl'] = $scheme . '://' . $host . $p['imageUrl'];
            } else {
                $p['imageUrl'] = $base . '/' . ltrim($p['imageUrl'], '/');
            }
        }
        unset($p['supplierRef'], $p['supplierCost']);
    }
    unset($p);
    $out['products'][$cat] = $data;
}

$presetFile = AI_SERVERS_DATA_DIR . '/presets.json';
if (is_file($presetFile)) {
    $out['presets'] = json_decode(file_get_contents($presetFile), true);
}

echo json_encode($out);
