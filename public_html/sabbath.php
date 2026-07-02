<?php
// inc/sabbath.php — Sabbath honor module.
// Computes Erev Shabbat (Friday sundown) → Motzei Shabbat (Saturday sundown)
// using a simple US Eastern fallback (no astronomy API dependency).
//
// "Remember the sabbath day, to keep it holy." — Exodus 20:8 (AKJV)

declare(strict_types=1);

if (!function_exists('sabbath_window')) {
    /**
     * Returns ['start' => DateTimeImmutable, 'end' => DateTimeImmutable, 'active' => bool, 'now' => DateTimeImmutable]
     * Times in America/Montreal (Erev Shabbat ~sundown).
     */
    function sabbath_window(?DateTimeImmutable $now = null): array
    {
        $tz   = new DateTimeZone('America/Montreal');
        $now  = $now ?? new DateTimeImmutable('now', $tz);
        $now  = $now->setTimezone($tz);

        // Approximate sundown by month (avg for ~45°N latitude).
        // Index by month number (1-12) → 24h "HH:MM" at end of civil twilight ish.
        static $sundown = [
            1 => '17:10', 2 => '17:45', 3 => '19:15', 4 => '19:45',
            5 => '20:15', 6 => '20:45', 7 => '20:45', 8 => '20:15',
            9 => '19:30', 10 => '18:30', 11 => '16:45', 12 => '16:30',
        ];

        // Compute most recent Friday at <month>'s sundown
        $weekday   = (int)$now->format('N'); // 1=Mon ... 7=Sun
        $diffToFri = ($weekday >= 5) ? ($weekday - 5) : ($weekday + 2);
        $fri       = $now->modify('-' . $diffToFri . ' days');
        $friSet    = $sundown[(int)$fri->format('n')];
        $start     = $fri->setTime((int)substr($friSet,0,2), (int)substr($friSet,3,2));

        $sat       = $start->modify('+1 day');
        $satSet    = $sundown[(int)$sat->format('n')];
        $end       = $sat->setTime((int)substr($satSet,0,2), (int)substr($satSet,3,2));

        if ($now > $end) {
            // Roll forward to next week
            $start = $start->modify('+7 days');
            $end   = $end->modify('+7 days');
        }

        $active = ($now >= $start && $now <= $end);

        return [
            'start'  => $start,
            'end'    => $end,
            'active' => $active,
            'now'    => $now,
        ];
    }
}

if (!function_exists('sabbath_banner_html')) {
    function sabbath_banner_html(): string
    {
        $w = sabbath_window();
        if (!$w['active']) return '';
        $end = $w['end']->format('l g:i A T');
        return '<div style="background:#1a0d22;color:#e8c97a;padding:1em;text-align:center;'
             . 'border-bottom:2px solid #c69b3a;font-family:Georgia,serif">'
             . '&#10010; <strong>Shabbat Shalom.</strong> Today we rest in the Lord. '
             . 'Commerce is paused until ' . htmlspecialchars($end) . '. '
             . '<em>"Remember the sabbath day, to keep it holy." &mdash; Exodus 20:8</em> &#10010;'
             . '</div>';
    }
}

if (!function_exists('sabbath_block_commerce')) {
    /** Call from cart/checkout endpoints. Halts with 423 Locked if Sabbath active. */
    function sabbath_block_commerce(): void
    {
        $w = sabbath_window();
        if (!$w['active']) return;
        http_response_code(423);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: ' . max(1, $w['end']->getTimestamp() - time()));
        echo '<!doctype html><meta charset=utf-8><title>Shabbat Shalom</title>';
        echo '<body style="font-family:Georgia,serif;max-width:640px;margin:5em auto;'
           . 'background:#0d0d12;color:#e8e2c8;padding:2em;text-align:center;line-height:1.7">';
        echo '<h1 style="color:#e8c97a">&#10010; Shabbat Shalom &#10010;</h1>';
        echo '<blockquote style="font-style:italic;color:#c8c2a8">'
           . '"Remember the sabbath day, to keep it holy. Six days shalt thou labour, and do all thy work: '
           . 'But the seventh day is the sabbath of the LORD thy God: in it thou shalt not do any work."'
           . '<br>&mdash; Exodus 20:8-10 (AKJV)</blockquote>';
        echo '<p>Commerce on this site is paused for the Sabbath.</p>';
        echo '<p>It will resume after sundown: <strong>'
           . htmlspecialchars($w['end']->format('l, F j Y, g:i A T')) . '</strong>.</p>';
        echo '</body>';
        exit;
    }
}
