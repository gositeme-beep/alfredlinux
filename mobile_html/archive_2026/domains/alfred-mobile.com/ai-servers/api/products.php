<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$category = isset($_GET['category']) ? preg_replace('/[^a-z_-]/', '', $_GET['category']) : '';
$valid = ['gpus', 'cpus', 'motherboards', 'ram', 'storage', 'psus', 'cases'];
if (!in_array($category, $valid, true)) {
    echo json_encode(['error' => 'Invalid or missing category', 'valid' => $valid]);
    exit;
}

$file = AI_SERVERS_DATA_DIR . '/' . $category . '.json';
if (!is_file($file)) {
    echo json_encode(['error' => 'Data not found', 'category' => $category]);
    exit;
}

$data = json_decode(file_get_contents($file), true);
$base = rtrim(AI_SERVERS_PRODUCT_IMAGE_BASE, '/');
foreach ($data as &$p) {
    if (!empty($p['imageUrl']) && strpos($p['imageUrl'], '/') !== 0) {
        $p['imageUrl'] = $base . '/' . $p['imageUrl'];
    }
    unset($p['supplierRef'], $p['supplierCost']);
}
unset($p);

echo json_encode(['currency' => AI_SERVERS_CURRENCY, 'products' => $data]);
