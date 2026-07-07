<?php
/**
 * GoSiteMe Structured JSON Logger
 * ─────────────────────────────────
 * PSR-3 inspired structured logging with JSON output.
 * Supports: file output, severity levels, context data, request metadata.
 *
 * Usage:
 *   require_once __DIR__ . '/logger.php';
 *   $log = new AppLogger('api');          // logs to logs/api/YYYY-MM-DD.json
 *   $log->info('User logged in', ['client_id' => 33]);
 *   $log->error('Payment failed', ['amount' => 99.99, 'error' => $e->getMessage()]);
 *   $log->warning('Rate limit approached', ['ip' => $ip, 'count' => $count]);
 *
 * @since v14.0
 */

class AppLogger {
    private string $channel;
    private string $logDir;

    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    public function __construct(string $channel = 'app') {
        $this->channel = $channel;
        $this->logDir  = dirname(__DIR__) . '/logs/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $channel);
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
    }

    public function emergency(string $message, array $context = []): void { $this->log(self::EMERGENCY, $message, $context); }
    public function alert(string $message, array $context = []): void     { $this->log(self::ALERT, $message, $context); }
    public function critical(string $message, array $context = []): void  { $this->log(self::CRITICAL, $message, $context); }
    public function error(string $message, array $context = []): void     { $this->log(self::ERROR, $message, $context); }
    public function warning(string $message, array $context = []): void   { $this->log(self::WARNING, $message, $context); }
    public function notice(string $message, array $context = []): void    { $this->log(self::NOTICE, $message, $context); }
    public function info(string $message, array $context = []): void      { $this->log(self::INFO, $message, $context); }
    public function debug(string $message, array $context = []): void     { $this->log(self::DEBUG, $message, $context); }

    public function log(string $level, string $message, array $context = []): void {
        $entry = [
            'timestamp'  => gmdate('Y-m-d\TH:i:s.') . substr(microtime(true) * 1000 % 1000, 0, 3) . 'Z',
            'level'      => $level,
            'channel'    => $this->channel,
            'message'    => $message,
        ];

        if ($context) {
            $entry['context'] = $context;
        }

        // Add request metadata (only in web context)
        if (PHP_SAPI !== 'cli') {
            $entry['request'] = [
                'method'  => $_SERVER['REQUEST_METHOD'] ?? '',
                'uri'     => $_SERVER['REQUEST_URI'] ?? '',
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
            ];
            if (!empty($_SESSION['client_id'])) {
                $entry['client_id'] = (int)$_SESSION['client_id'];
            }
        }

        $file = $this->logDir . '/' . gmdate('Y-m-d') . '.json';
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        // Atomic append with file locking
        $fp = @fopen($file, 'a');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $line);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
