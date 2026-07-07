<?php
// Simple test to debug static file serving
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$static_path = __DIR__ . '/.next/static' . $path;

echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Path: " . $path . "\n";
echo "Static path: " . $static_path . "\n";
echo "Is static request: " . (strpos($path, '/_next/static/') === 0 ? 'YES' : 'NO') . "\n";
echo "File exists: " . (file_exists($static_path) ? 'YES' : 'NO') . "\n";

if (strpos($path, '/_next/static/') === 0 && file_exists($static_path)) {
    header('Content-Type: application/javascript; charset=UTF-8');
    readfile($static_path);
} else {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Not a static file or file doesn't exist\n";
}
?> 