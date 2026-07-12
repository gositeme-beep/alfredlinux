<?php
/**
 * Mega Plan ecosystem page — access control helpers.
 *
 * Configure (outside webroot .env.php or webroot .env via getenv):
 *   GOSITEME_MEGA_PLAN_INVITE_SECRET — shared invite string you give users (use a long random value).
 *
 * Logs JSON lines to: ../private/mega-plan-access.log (client_id, time, IP, action).
 */
declare(strict_types=1);

if (!function_exists('mega_plan_client_id')) {
    function mega_plan_client_id(): int
    {
        return (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
    }

    function mega_plan_is_logged_in(): bool
    {
        $cid = mega_plan_client_id();
        return !empty($_SESSION['logged_in']) && $cid > 0;
    }

    function mega_plan_invite_configured(): bool
    {
        $s = getenv('GOSITEME_MEGA_PLAN_INVITE_SECRET');
        return is_string($s) && $s !== '';
    }

    function mega_plan_invite_ok(): bool
    {
        return !empty($_SESSION['mega_plan_invite_ok']);
    }

    function mega_plan_csrf_token(): string
    {
        if (empty($_SESSION['mega_plan_csrf'])) {
            $_SESSION['mega_plan_csrf'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['mega_plan_csrf'];
    }

    function mega_plan_csrf_validate(?string $token): bool
    {
        $expected = $_SESSION['mega_plan_csrf'] ?? '';
        return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
    }

    function mega_plan_verify_invite(string $input): bool
    {
        $stored = getenv('GOSITEME_MEGA_PLAN_INVITE_SECRET');
        if (!is_string($stored) || $stored === '') {
            return false;
        }
        return hash_equals($stored, $input);
    }

    function mega_plan_log(int $clientId, string $action): void
    {
        $dir = dirname(__DIR__, 2) . '/private';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $path = $dir . '/mega-plan-access.log';
        $row = [
            'ts'        => gmdate('c'),
            'client_id' => $clientId,
            'ip'        => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'action'    => $action,
            'ua'        => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 240),
            'uri'       => substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 500),
        ];
        @file_put_contents(
            $path,
            json_encode($row, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
