<?php
/**
 * /home/gositeme/public_html/api/agenda.php
 *
 * Read-only agenda endpoint for alfred-agenda CLI.
 * Returns deterministic Sabbath/portfolio cadence items derived from the
 * canonical doctrine — no DB required for v1.
 *
 * Optional auth: if env ALFRED_AGENDA_TOKEN is set, requires
 *   Authorization: Bearer <token>
 *
 * Response shape (matches what alfred-agenda expects):
 * {
 *   "generated_at": "2026-05-04T08:00:00Z",
 *   "items": [
 *     { "when": "2026-05-04T08:00:00Z", "title": "Sabbath 1 · Witness",
 *       "kind": "sabbath", "doctrine": "Treasury custody audit",
 *       "cmd": "alfred-sabbath 1" },
 *     ...
 *   ]
 * }
 *
 * In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('X-Robots-Tag: noindex');

// ─── Optional bearer auth ────────────────────────────────────────────
$expected = getenv('ALFRED_AGENDA_TOKEN') ?: '';
if ($expected !== '') {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/', $hdr, $m) || !hash_equals($expected, trim($m[1]))) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
}

// ─── Build deterministic cadence ─────────────────────────────────────
date_default_timezone_set('UTC');
$now    = new DateTimeImmutable('now');
$todayY = (int)$now->format('Y');
$todayM = (int)$now->format('m');
$todayD = (int)$now->format('d');

$items = [];

// Daily — Sabbath 1 (Witness) at 08:00 UTC for next 14 days
for ($i = 0; $i < 14; $i++) {
    $d = $now->modify("+$i day")->setTime(8, 0, 0);
    $items[] = [
        'when'     => $d->format('Y-m-d\TH:i:s\Z'),
        'title'    => 'Sabbath 1 · Witness',
        'kind'     => 'sabbath',
        'doctrine' => 'Daily on-chain GSM treasury audit',
        'cmd'      => 'alfred-sabbath 1',
    ];
}

// Weekly — Sabbath 4 (Allocations) every Sunday 09:00 UTC for next 4 weeks
$next_sun = $now->modify('next sunday')->setTime(9, 0, 0);
for ($i = 0; $i < 4; $i++) {
    $d = $next_sun->modify("+$i week");
    $items[] = [
        'when'     => $d->format('Y-m-d\TH:i:s\Z'),
        'title'    => 'Sabbath 4 · Allocations',
        'kind'     => 'sabbath',
        'doctrine' => '30/25/20/15/10 portfolio review',
        'cmd'      => 'alfred-portfolio drift; alfred-sabbath 4',
    ];
}

// Monthly — Sabbath 5 (Tithe) on 1st of each of next 3 months
for ($i = 0; $i < 3; $i++) {
    $d = (new DateTimeImmutable("first day of +$i month"))->setTime(10, 0, 0);
    $items[] = [
        'when'     => $d->format('Y-m-d\TH:i:s\Z'),
        'title'    => 'Sabbath 5 · Tithe',
        'kind'     => 'sabbath',
        'doctrine' => 'Mal 3:10 — pay outstanding 10%',
        'cmd'      => 'alfred-tithe balance; alfred-sabbath 5',
    ];
}

// Daily — daily bread (verse) at 06:30 UTC for next 7 days
for ($i = 0; $i < 7; $i++) {
    $d = $now->modify("+$i day")->setTime(6, 30, 0);
    $items[] = [
        'when'     => $d->format('Y-m-d\TH:i:s\Z'),
        'title'    => 'Daily Bread',
        'kind'     => 'scripture',
        'doctrine' => 'Matt 4:4 — every word from the mouth of God',
        'cmd'      => 'alfred-bible daily',
    ];
}

// Quarterly — Sabbath 6 (Two Talents) every 90 days from today 11:00 UTC
$items[] = [
    'when'     => $now->modify('+90 day')->setTime(11, 0, 0)->format('Y-m-d\TH:i:s\Z'),
    'title'    => 'Sabbath 6 · Two Talents',
    'kind'     => 'sabbath',
    'doctrine' => 'Matt 25 — multiply, do not bury',
    'cmd'      => 'alfred-sabbath 6',
];

// Sort by 'when'
usort($items, fn($a, $b) => strcmp($a['when'], $b['when']));

// ─── Emit ────────────────────────────────────────────────────────────
echo json_encode([
    'generated_at' => $now->format('Y-m-d\TH:i:s\Z'),
    'doctrine'     => 'Kingdom of God · GSM treasury · Nine Pillars',
    'items'        => $items,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
