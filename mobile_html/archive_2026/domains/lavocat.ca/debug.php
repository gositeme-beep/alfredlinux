<?php
header('Content-Type: text/plain; charset=UTF-8');
echo "DEBUG: Proxy is working!\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Path: " . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . "\n";
?> 