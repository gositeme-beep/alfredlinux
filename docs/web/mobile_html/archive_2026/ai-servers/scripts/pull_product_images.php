<?php
/**
 * Download and save product images from imageSourceUrl in data/*.json.
 * Run from CLI: php pull_product_images.php
 * Only products with imageSourceUrl are downloaded; no placeholders.
 */
$baseDir = dirname(__DIR__);
require_once $baseDir . '/includes/config.php';

$dataDir = defined('AI_SERVERS_DATA_DIR') ? AI_SERVERS_DATA_DIR : $baseDir . '/data';
$outDir = $baseDir . '/assets/products';
$categories = ['gpus', 'cpus', 'motherboards', 'ram', 'storage', 'psus', 'cases'];

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$pulled = 0;
$errors = [];

foreach ($categories as $cat) {
    $file = $dataDir . '/' . $cat . '.json';
    if (!is_file($file)) continue;
    $list = json_decode(file_get_contents($file), true);
    if (!is_array($list)) continue;
    $modified = false;
    foreach ($list as $idx => &$product) {
        $imageUrl = isset($product['imageUrl']) ? trim($product['imageUrl']) : '';
        $sourceUrl = isset($product['imageSourceUrl']) ? trim($product['imageSourceUrl']) : '';

        if ($imageUrl === '' || $sourceUrl === '' || !preg_match('#^https?://#i', $sourceUrl)) {
            continue;
        }

        $baseName = pathinfo($imageUrl, PATHINFO_FILENAME);
        $urlExt = strtolower(pathinfo(parse_url($sourceUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($urlExt, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) $urlExt = 'png';
        $saveFilename = $baseName . '.' . $urlExt;
        $path = $outDir . '/' . $saveFilename;

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 15,
                'follow_location' => true,
                'user_agent' => 'AI-Servers-Configurator/1.0 (Product images; contact via site)',
            ],
            'ssl' => ['verify_peer' => true],
        ]);
        $img = @file_get_contents($sourceUrl, false, $ctx);
        if ($img !== false && strlen($img) > 0) {
            if (file_put_contents($path, $img) !== false) {
                $pulled++;
                if ($saveFilename !== basename($imageUrl)) {
                    $product['imageUrl'] = $saveFilename;
                    $modified = true;
                }
            } else {
                $errors[] = "Write failed: $saveFilename";
            }
        } else {
            $errors[] = "Download failed: $saveFilename from " . substr($sourceUrl, 0, 60) . '...';
        }
    }
    unset($product);
    if ($modified && !empty($list)) {
        file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

if (php_sapi_name() === 'cli') {
    echo "Downloaded $pulled images.\n";
    if (!empty($errors)) foreach ($errors as $e) echo "  - $e\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['pulled' => $pulled, 'errors' => $errors]);
}
