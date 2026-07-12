<?php
/**
 * GoSiteMe Environment Variable Validation
 * ──────────────────────────────────────────
 * Validates all required environment variables and config constants on startup.
 * Include early in bootstrap to catch misconfigurations before they cause errors.
 *
 * Usage:
 *   require_once __DIR__ . '/config/validate.php';
 *   // Throws RuntimeException if any required var is missing
 *
 * @since v14.0
 */

(function () {
    $errors = [];

    // ── Required PHP constants (set by config.php or db-config.inc.php) ──
    $requiredConstants = [
        'DB_HOST'  => 'Database host',
        'DB_NAME'  => 'Database name',
        'DB_USER'  => 'Database user',
        'DB_PASS'  => 'Database password',
    ];

    foreach ($requiredConstants as $const => $label) {
        if (!defined($const)) {
            $errors[] = "Missing constant: {$const} ({$label})";
        } elseif (constant($const) === '') {
            $errors[] = "Empty constant: {$const} ({$label})";
        }
    }

    // ── Required files ──
    $requiredFiles = [
        dirname(__DIR__) . '/includes/site-header.inc.php'  => 'Site header',
        dirname(__DIR__) . '/includes/site-footer.inc.php'  => 'Site footer',
    ];

    foreach ($requiredFiles as $file => $label) {
        if (!file_exists($file)) {
            $errors[] = "Missing required file: {$file} ({$label})";
        }
    }

    // ── Required directories (writable) ──
    $writableDirs = [
        dirname(__DIR__) . '/cache'  => 'Cache directory',
        dirname(__DIR__) . '/logs'   => 'Logs directory',
    ];

    foreach ($writableDirs as $dir => $label) {
        if (!is_dir($dir)) {
            $errors[] = "Missing directory: {$dir} ({$label})";
        } elseif (!is_writable($dir)) {
            $errors[] = "Not writable: {$dir} ({$label})";
        }
    }

    // ── Optional but recommended ──
    $warnings = [];

    if (!extension_loaded('redis')) {
        $warnings[] = 'Redis extension not loaded — file-based fallbacks will be used';
    }
    if (!extension_loaded('curl')) {
        $warnings[] = 'cURL extension not loaded — external API calls will fail';
    }
    if (!extension_loaded('mbstring')) {
        $warnings[] = 'mbstring extension not loaded — UTF-8 handling may fail';
    }

    // ── Report ──
    if ($errors) {
        // In CLI mode, print and exit
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "Configuration errors:\n" . implode("\n", $errors) . "\n");
            exit(1);
        }
        // In web mode, log and throw
        error_log('[GoSiteMe] Config validation failed: ' . implode('; ', $errors));
        throw new RuntimeException('Configuration validation failed. Check error log.');
    }

    // Log warnings (non-fatal)
    foreach ($warnings as $w) {
        error_log('[GoSiteMe] Config warning: ' . $w);
    }
})();
