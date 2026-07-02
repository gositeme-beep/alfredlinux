<?php
// includes/sabbath.php — Sabbath honor gate (server-side authority).
//
// Primary source: https://gositeme.com/api/daniel-calendar.php?city=montreal
//   (the same API the JS shabbat-banner already consumes — single source of truth).
// Fallback: month-indexed sundown table for ~45°N (America/Montreal).
//
// Public functions:
//   sabbath_state(): array  ['active'=>bool,'erev'=>bool,'end_ts'=>?int,'source'=>string]
//   sabbath_block_commerce(): void   — HTTP 423 Locked + Retry-After if Sabbath active.
//
// "Remember the sabbath day, to keep it holy." — Exodus 20:8 (AKJV)

declare(strict_types=1);

if (!defined('SABBATH_API_URL'))   define('SABBATH_API_URL',   'https://gositeme.com/api/daniel-calendar.php?city=montreal');
if (!defined('SABBATH_CACHE_FILE'))define('SABBATH_CACHE_FILE', sys_get_temp_dir() . '/.sabbath-state.json');
if (!defined('SABBATH_CACHE_TTL')) define('SABBATH_CACHE_TTL', 300); // 5 min

if (!function_exists('sabbath_state')) {
    function sabbath_state(): array {
        // Cache lookup
        if (is_readable(SABBATH_CACHE_FILE) && (time() - filemtime(SABBATH_CACHE_FILE)) < SABBATH_CACHE_TTL) {
            $cached = json_decode((string)@file_get_contents(SABBATH_CACHE_FILE), true);
            if (is_array($cached) && isset($cached['active'])) return $cached;
        }
        $state = sabbath_state_from_api();
        if ($state === null) $state = sabbath_state_fallback();
        @file_put_contents(SABBATH_CACHE_FILE, json_encode($state), LOCK_EX);
        return $state;
    }
}

if (!function_exists('sabbath_state_from_api')) {
    function sabbath_state_from_api(): ?array {
        $ctx = stream_context_create(['http' => ['timeout' => 2, 'method' => 'GET',
            'header' => "User-Agent: alfredlinux-sabbath-gate/1.0\r\n"]]);
        $raw = @file_get_contents(SABBATH_API_URL, false, $ctx);
        if (!$raw) return null;
        $d = json_decode($raw, true);
        if (!is_array($d) || !isset($d['shabbat'])) return null;
        $s = $d['shabbat'];
        $active = !empty($s['isShabbat']);
        $erev   = !empty($s['isErevShabbat']);
        $endTs  = null;
        if ($active && !empty($s['havdalah'])) {
            $tz = new DateTimeZone('America/Montreal');
            try { $endTs = (new DateTimeImmutable((string)$s['havdalah'], $tz))->getTimestamp(); }
            catch (Exception $e) { $endTs = null; }
        }
        return ['active'=>$active,'erev'=>$erev,'end_ts'=>$endTs,'source'=>'daniel-calendar-api'];
    }
}

if (!function_exists('sabbath_state_fallback')) {
    function sabbath_state_fallback(): array {
        $tz  = new DateTimeZone('America/Montreal');
        $now = new DateTimeImmutable('now', $tz);
        // Approx sundown by month for ~45°N (HH:MM 24h)
        static $sundown = [
            1=>'17:10',2=>'17:45',3=>'19:15',4=>'19:45',
            5=>'20:15',6=>'20:45',7=>'20:45',8=>'20:15',
            9=>'19:30',10=>'18:30',11=>'16:45',12=>'16:30',
        ];
        $weekday = (int)$now->format('N'); // 1=Mon..7=Sun
        $diffFri = ($weekday >= 5) ? ($weekday - 5) : ($weekday + 2);
        $fri = $now->modify('-' . $diffFri . ' days');
        [$h,$m] = explode(':', $sundown[(int)$fri->format('n')]);
        $start = $fri->setTime((int)$h, (int)$m);
        $sat = $start->modify('+1 day');
        [$h2,$m2] = explode(':', $sundown[(int)$sat->format('n')]);
        $end = $sat->setTime((int)$h2, (int)$m2);
        if ($now > $end) { $start = $start->modify('+7 days'); $end = $end->modify('+7 days'); }
        $active = ($now >= $start && $now <= $end);
        return ['active'=>$active,'erev'=>false,'end_ts'=>$active ? $end->getTimestamp() : null,'source'=>'fallback-table'];
    }
}

if (!function_exists('sabbath_block_commerce')) {
    function sabbath_block_commerce(): void {
        $st = sabbath_state();
        if (empty($st['active'])) return;
        $endTs = $st['end_ts'] ?: (time() + 3600);
        $retry = max(1, $endTs - time());
        http_response_code(423);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: ' . $retry);
        $endHuman = date('l, F j Y \a\t g:i A T', $endTs);
        echo '<!doctype html><meta charset=utf-8><title>Shabbat Shalom</title>'
           . '<body style="font-family:Georgia,serif;max-width:640px;margin:5em auto;'
           . 'background:#0d0d12;color:#e8e2c8;padding:2em;text-align:center;line-height:1.7">'
           . '<h1 style="color:#e8c97a">&#10010; Shabbat Shalom &#10010;</h1>'
           . '<blockquote style="font-style:italic;color:#c8c2a8">'
           . '"Remember the sabbath day, to keep it holy. Six days shalt thou labour, and do all thy work: '
           . 'But the seventh day is the sabbath of the LORD thy God: in it thou shalt not do any work."'
           . '<br>&mdash; Exodus 20:8-10 (AKJV)</blockquote>'
           . '<p>Commerce on this site is paused for the Sabbath.</p>'
           . '<p>It will resume after sundown: <strong>' . htmlspecialchars($endHuman) . '</strong>.</p>'
           . '</body>';
        exit;
    }
}
