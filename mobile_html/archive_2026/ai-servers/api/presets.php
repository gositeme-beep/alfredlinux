<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$file = AI_SERVERS_DATA_DIR . '/presets.json';
if (!is_file($file)) {
    echo json_encode(['error' => 'Presets not found']);
    exit;
}
echo file_get_contents($file);
