<?php
/**
 * Free Village Network - Secure Configuration
 * This file should NEVER be accessible via web browser
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gositeme_brickaboiscom');
define('DB_USER', 'gositeme_brickaboiscom');
define('DB_PASS', 'LYUDbyz97tSumNtRZeuu');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Free Village Network');
define('APP_URL', 'https://brickabois.com');
define('APP_ENV', 'production'); // development | production

// Security
define('SESSION_LIFETIME', 86400); // 24 hours
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Paths (relative to public_html)
define('ROOT_PATH', dirname(__DIR__) . '/');
define('PUBLIC_PATH', ROOT_PATH . 'public_html/');
define('PRIVATE_PATH', ROOT_PATH . 'private_html/');
define('UPLOAD_PATH', PUBLIC_PATH . 'uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Timezone
date_default_timezone_set('America/Montreal');

// Error Reporting (disable in production)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database Connection Function
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }
    
    return $conn;
}

// Helper function to include config
function requireConfig() {
    require_once __DIR__ . '/config.php';
}

