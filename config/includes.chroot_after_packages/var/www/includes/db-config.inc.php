<?php
/**
 * GoSiteMe — Shared Database Configuration
 * 
 * Single source of truth for DB credentials across the entire codebase.
 * Safe to require_once from any file — no guard constant needed.
 * 
 * Provides:
 *   GOSITEME_DB_HOST, GOSITEME_DB_NAME, GOSITEME_DB_USER, GOSITEME_DB_PASS  — constants
 *   getSharedDB()  — returns a singleton PDO connection
 */

// Only define once (safe for require_once but also guards against double-include)
if (defined('GOSITEME_DB_CONFIGURED')) return;
define('GOSITEME_DB_CONFIGURED', true);

// Load secure env file (outside webroot)
$_envFile = dirname(dirname(__DIR__)) . '/.env.php';
if (file_exists($_envFile)) require_once $_envFile;
unset($_envFile);

// Load webroot .env file (secrets for APIs, agents, services)
$_dotenv = __DIR__ . '/../.env';
if (file_exists($_dotenv) && !getenv('INTERNAL_SECRET')) {
    foreach (file($_dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if ($_line[0] === '#') continue;
        if (strpos($_line, '=') === false) continue;
        [$_key, $_val] = explode('=', $_line, 2);
        $_key = trim($_key);
        $_val = trim($_val);
        if ($_key && !getenv($_key)) putenv("$_key=$_val");
    }
}
unset($_dotenv, $_line, $_key, $_val);

define('GOSITEME_DB_HOST', getenv('GOSITEME_DB_HOST') ?: 'localhost');
define('GOSITEME_DB_NAME', getenv('GOSITEME_DB_NAME') ?: 'root_whmcs');
define('GOSITEME_DB_USER', getenv('GOSITEME_DB_USER') ?: 'root_whmcs');
define('GOSITEME_DB_PASS', getenv('GOSITEME_DB_PASS') ?: die('GOSITEME_DB_PASS not set — configure .env.php'));

/**
 * Get a shared PDO connection (singleton).
 * All code should use this instead of creating PDO manually.
 */
function getSharedDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . GOSITEME_DB_HOST . ';dbname=' . GOSITEME_DB_NAME . ';charset=utf8mb4',
            GOSITEME_DB_USER,
            GOSITEME_DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

if (!function_exists('dbExecute')) {
    function dbExecute(PDOStatement $stmt, array $params): PDOStatement {
        foreach ($params as $i => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue($i + 1, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }
}
