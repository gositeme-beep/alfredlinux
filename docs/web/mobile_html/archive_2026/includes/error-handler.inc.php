<?php
/**
 * Alfred AI Error Handler
 * Centralized error and exception handling for consistent logging.
 * Include at top of critical files for consistent error logging.
 * 
 * Usage:
 *   require_once __DIR__ . '/error-handler.inc.php';
 *
 * For API files, define GOSITEME_API before including:
 *   define('GOSITEME_API', true);
 *   require_once __DIR__ . '/../includes/error-handler.inc.php';
 */

// Prevent double-registration
if (defined('ALFRED_ERROR_HANDLER_LOADED')) return;
define('ALFRED_ERROR_HANDLER_LOADED', true);

/**
 * Map PHP error codes to human-readable labels
 */
function alfredErrorLabel(int $errno): string {
    $map = [
        E_ERROR             => 'E_ERROR',
        E_WARNING           => 'E_WARNING',
        E_PARSE             => 'E_PARSE',
        E_NOTICE            => 'E_NOTICE',
        E_CORE_ERROR        => 'E_CORE_ERROR',
        E_CORE_WARNING      => 'E_CORE_WARNING',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_USER_WARNING      => 'E_USER_WARNING',
        E_USER_NOTICE       => 'E_USER_NOTICE',
        E_STRICT            => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED        => 'E_DEPRECATED',
        E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
    ];
    return $map[$errno] ?? "UNKNOWN({$errno})";
}

/**
 * Custom error handler — logs to daily rotated file.
 * Fatal errors trigger a user-safe error page.
 */
function alfredErrorHandler(int $errno, string $errstr, string $errfile, int $errline): bool {
    // Respect error_reporting() / @ operator
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }

    $label   = alfredErrorLabel($errno);
    $logFile = $logDir . '/alfred-errors-' . date('Y-m-d') . '.log';
    $entry   = date('[Y-m-d H:i:s]') . " [{$label}] {$errstr} in {$errfile}:{$errline}\n";
    @error_log($entry, 3, $logFile);

    // Fatal-class errors: show safe page and exit
    $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
    if (in_array($errno, $fatalErrors, true)) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (defined('GOSITEME_API')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Internal server error', 'code' => 'internal_error']);
        } else {
            // Serve the 404 page (acts as generic error page)
            $errorPage = __DIR__ . '/../404.php';
            if (file_exists($errorPage)) {
                include $errorPage;
            } else {
                echo '<!DOCTYPE html><html><body><h1>500 — Internal Server Error</h1></body></html>';
            }
        }
        exit(1);
    }

    return true; // Don't execute PHP internal handler for non-fatal
}

/**
 * Custom exception handler — logs full trace, shows safe page.
 */
function alfredExceptionHandler(\Throwable $exception): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }

    $logFile = $logDir . '/alfred-errors-' . date('Y-m-d') . '.log';
    $entry   = date('[Y-m-d H:i:s]') . " [EXCEPTION] "
             . get_class($exception) . ": " . $exception->getMessage()
             . " in " . $exception->getFile() . ":" . $exception->getLine() . "\n"
             . $exception->getTraceAsString() . "\n";
    @error_log($entry, 3, $logFile);

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (defined('GOSITEME_API')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Internal server error', 'code' => 'internal_error']);
    } else {
        $errorPage = __DIR__ . '/../404.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo '<!DOCTYPE html><html><body><h1>500 — Internal Server Error</h1></body></html>';
        }
    }
    exit(1);
}

// Register handlers
set_error_handler('alfredErrorHandler');
set_exception_handler('alfredExceptionHandler');
