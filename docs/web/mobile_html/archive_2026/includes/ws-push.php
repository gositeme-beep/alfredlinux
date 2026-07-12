<?php
/**
 * Alfred WebSocket Push Helper
 * ─────────────────────────────
 * Push real-time events from PHP to connected WebSocket clients.
 *
 * Usage:
 *   require_once __DIR__ . '/ws-push.php';
 *   ws_push('chat:room_abc', ['type' => 'new_message', 'message' => '...']);
 *   ws_push_user(123, 'notification', ['title' => 'Hello']);
 */

defined('WS_PUSH_URL') or define('WS_PUSH_URL', 'http://127.0.0.1:3010');
defined('WS_SECRET')   or define('WS_SECRET', defined('AGENTOS_WS_SECRET') ? AGENTOS_WS_SECRET : (getenv('WS_SECRET') ?: ''));
if (!WS_SECRET) { error_log('[WS-PUSH] WS_SECRET not configured — push notifications may fail'); }

/**
 * Push an event to a WebSocket channel.
 * All clients subscribed to this channel will receive it.
 */
function ws_push(string $channel, array $data, ?string $userId = null): array {
    $payload = ['channel' => $channel, 'data' => $data];
    if ($userId) $payload['user_id'] = $userId;

    return _ws_http_post(WS_PUSH_URL . '/push', $payload);
}

/**
 * Push an event to a specific user (all their connections).
 */
function ws_push_user(string $userId, string $eventType, array $data = []): array {
    return _ws_http_post(WS_PUSH_URL . '/push-user', [
        'user_id'    => $userId,
        'event_type' => $eventType,
        'data'       => $data,
    ]);
}

/**
 * Internal HTTP POST to WebSocket server.
 */
function _ws_http_post(string $url, array $payload): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-WS-Secret: ' . WS_SECRET,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_CONNECTTIMEOUT => 1,
    ]);

    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $result) {
        return json_decode($result, true) ?: ['success' => false];
    }
    return ['success' => false, 'error' => "HTTP {$code}"];
}
