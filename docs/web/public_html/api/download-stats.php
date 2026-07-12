<?php
/**
 * GA download-start counter — Redis, calendar day in America/Montreal.
 * POST action=record + JSON { sid } — at most one count per sid per local day.
 * GET  action=today — public read of today's total (no auth).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

date_default_timezone_set('America/Montreal');
$day    = date('Y-m-d');
$prefix = 'alfredlinux:stats:';

function alfred_dl_client_ip(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'])[0]);
    }
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

function alfred_dl_redis(): ?Redis {
    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 1.0);
        return $r;
    } catch (Exception $e) {
        return null;
    }
}

/** POST must look like it came from the real site (Origin or Referer). */
function alfred_dl_post_allowed(): bool {
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if (preg_match('#^https://(www\.)?alfredlinux\.com$#i', $origin)) {
        return true;
    }
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    return (bool) preg_match('#^https://(www\.)?alfredlinux\.com/#i', $ref);
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'today') {
    $r = alfred_dl_redis();
    if (!$r) {
        echo json_encode(['ok' => false, 'today' => null, 'day' => $day, 'tz' => 'America/Montreal']);
        exit;
    }
    $todayKey = $prefix . 'dl_starts:' . $day;
    $n        = (int) $r->get($todayKey);
    echo json_encode(['ok' => true, 'today' => $n, 'day' => $day, 'tz' => 'America/Montreal']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!alfred_dl_post_allowed()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden_origin']);
        exit;
    }

    $raw = (string) file_get_contents('php://input');
    $j   = json_decode($raw, true);
    if (!is_array($j)) {
        $j = [];
    }
    $postAction = (string) ($j['action'] ?? $_POST['action'] ?? '');
    if ($postAction !== 'record') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bad_action']);
        exit;
    }

    $sid = preg_replace('/[^a-zA-Z0-9]/', '', (string) ($j['sid'] ?? $_POST['sid'] ?? ''));
    if (strlen($sid) < 8) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bad_sid']);
        exit;
    }

    $r = alfred_dl_redis();
    if (!$r) {
        echo json_encode(['ok' => false, 'today' => null, 'error' => 'redis']);
        exit;
    }

    $ip = alfred_dl_client_ip();
    if ($ip !== '') {
        $ipSlice = substr(hash('sha256', $ip . '|alfred-dl-rl', true), 0, 12);
        $ipKey   = $prefix . 'rl_d:' . $day . ':' . bin2hex($ipSlice);
        $nIp     = $r->incr($ipKey);
        if ($nIp === 1) {
            $r->expire($ipKey, 100000);
        }
        if ($nIp > 120) {
            echo json_encode([
                'ok'    => false,
                'error' => 'rate_limited',
                'today' => (int) $r->get($prefix . 'dl_starts:' . $day),
            ]);
            exit;
        }
    }

    $todayKey = $prefix . 'dl_starts:' . $day;
    $dedupe   = $prefix . 'dl_once:' . $day . ':' . $sid;
    $setOk    = $r->set($dedupe, '1', ['nx', 'ex' => 100000]);
    $counted  = false;
    if ($setOk) {
        $v = $r->incr($todayKey);
        if ($v === 1) {
            $r->expire($todayKey, 172800);
        }
        $counted = true;
    }

    $today = (int) $r->get($todayKey);
    echo json_encode([
        'ok'      => true,
        'counted' => $counted,
        'today'   => $today,
        'day'     => $day,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'bad_request']);
