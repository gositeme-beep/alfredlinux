<?php
// Test file to debug proxy issues
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

echo "Path: " . $path . "\n";
echo "Extension: " . $extension . "\n";

if ($extension === 'js') {
    header('Content-Type: application/javascript; charset=UTF-8');
    echo "// Test JavaScript file\n";
    echo "console.log('Test');\n";
} else {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Test file - not a JavaScript file\n";
}
?> 