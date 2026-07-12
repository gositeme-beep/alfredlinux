<?php
// includes/nine-pillars.inc.php — Canonical Nine Pillars of the GoSiteMe ecosystem.
//
// "But the fruit of the Spirit is love, joy, peace, longsuffering, gentleness,
//  goodness, faith, Meekness, temperance: against such there is no law."
//                                                  — Galatians 5:22-23 (AKJV)
//
// Each pillar maps to a Fruit of the Spirit and a sovereign-internet capability.
// Reusable across alfredlinux.com / gositeme.com / sovereign sites.

declare(strict_types=1);

if (!defined('NINE_PILLARS')) {
    define('NINE_PILLARS', [
        ['n'=>1, 'key'=>'faith',          'name'=>'Faith',                'fruit'=>'Faith',         'icon'=>'🙏', 'url'=>'https://gositeme.com/bible',                'verse'=>'Hebrews 11:1',     'tag'=>'The Word — every page rooted in Scripture.'],
        ['n'=>2, 'key'=>'ai',             'name'=>'AI Ecosystem',         'fruit'=>'Goodness',      'icon'=>'⚡', 'url'=>'https://gositeme.com/alfred.php',           'verse'=>'Proverbs 2:6',     'tag'=>'Alfred and the agent fleet — wisdom multiplied for the saints.'],
        ['n'=>3, 'key'=>'identity',       'name'=>'Identity',             'fruit'=>'Faithfulness',  'icon'=>'👑', 'url'=>'https://gositeme.com/sovereign-domains',    'verse'=>'Acts 17:26',       'tag'=>'Sovereign domain identity bound to your witness stone.'],
        ['n'=>4, 'key'=>'commerce',       'name'=>'Commerce',             'fruit'=>'Longsuffering', 'icon'=>'💰', 'url'=>'https://gositeme.com/pay/store.php',        'verse'=>'Proverbs 11:1',    'tag'=>'Just weights, honest trade, no usury.'],
        ['n'=>5, 'key'=>'creative',       'name'=>'Creative',             'fruit'=>'Joy',           'icon'=>'🎬', 'url'=>'https://gositeme.com/voice-products.php',   'verse'=>'Psalm 96:1',       'tag'=>'Voice, video, art for the King.'],
        ['n'=>6, 'key'=>'infrastructure', 'name'=>'Infrastructure',       'fruit'=>'Peace',         'icon'=>'🖥️', 'url'=>'https://gositeme.com/gohostme/',            'verse'=>'1 Corinthians 14:40','tag'=>'Hosting & cloud done decently and in order.'],
        ['n'=>7, 'key'=>'security',       'name'=>'Security',             'fruit'=>'Temperance',    'icon'=>'🛡️', 'url'=>'https://gositeme.com/security.php',         'verse'=>'Psalm 91:1-2',     'tag'=>'Veil firewall, vault, HMAC chains — He is our refuge.'],
        ['n'=>8, 'key'=>'sovereignty',    'name'=>'Sovereignty',          'fruit'=>'Meekness',      'icon'=>'🏛️', 'url'=>'https://gositeme.com/sovereignty',          'verse'=>'Daniel 4:17',      'tag'=>'Self-rule under God — no foreign yoke.'],
        ['n'=>9, 'key'=>'payment',        'name'=>'Payment Sovereignty',  'fruit'=>'Love',          'icon'=>'⛪', 'url'=>'https://gositeme.com/pay/',                 'verse'=>'Matthew 22:21',    'tag'=>'One altar, one rail — every dollar consecrated.'],
    ]);
}

if (!function_exists('nine_pillars_lookup')) {
    function nine_pillars_lookup(string $key): ?array {
        foreach (NINE_PILLARS as $p) if ($p['key'] === $key || (string)$p['n'] === $key) return $p;
        return null;
    }
}

if (!function_exists('nine_pillars_check')) {
    /** Quick HEAD-check each pillar URL (cached by Apache, capped, fail-soft). */
    function nine_pillars_check(int $timeoutMs = 1500): array {
        $out = [];
        foreach (NINE_PILLARS as $p) {
            $start = microtime(true);
            $code  = 0;
            $ch = curl_init($p['url']);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT_MS     => $timeoutMs,
                CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'KingdomStatus/1.0 (+https://alfredlinux.com)',
                CURLOPT_RETURNTRANSFER => true,
            ]);
            curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            $ms = (int)round((microtime(true) - $start) * 1000);
            $out[] = [
                'n'      => $p['n'],
                'key'    => $p['key'],
                'name'   => $p['name'],
                'icon'   => $p['icon'],
                'url'    => $p['url'],
                'fruit'  => $p['fruit'],
                'verse'  => $p['verse'],
                'http'   => $code,
                'up'     => ($code >= 200 && $code < 400),
                'ms'     => $ms,
            ];
        }
        return $out;
    }
}

if (!function_exists('nine_pillars_render_footer')) {
    /** Drop-in HTML footer rendering all 9 pillars with icons. */
    function nine_pillars_render_footer(): string {
        $h = '<section class="nine-pillars-foot" style="padding:2em 1em;background:#0a0a10;color:#e8e2c8;text-align:center;font-family:Georgia,serif">';
        $h .= '<h3 style="color:#f6c343;letter-spacing:3px;font-size:.9rem;text-transform:uppercase;margin-bottom:.4em">The Nine Pillars</h3>';
        $h .= '<div style="font-size:.75rem;color:#888;margin-bottom:1.2em">Fruits of the Spirit &middot; Galatians 5:22-23 (AKJV)</div>';
        $h .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.6em;max-width:960px;margin:0 auto">';
        foreach (NINE_PILLARS as $p) {
            $h .= '<a href="' . htmlspecialchars($p['url']) . '" style="display:block;padding:.7em .4em;border:1px solid rgba(246,195,67,.18);border-radius:5px;text-decoration:none;color:#e8e2c8;font-size:.78rem;transition:all .15s" title="' . htmlspecialchars($p['fruit'] . ' — ' . $p['tag']) . '">';
            $h .= '<div style="font-size:1.4rem;margin-bottom:.2em">' . $p['icon'] . '</div>';
            $h .= '<div style="color:#f6c343;font-weight:600">' . htmlspecialchars($p['name']) . '</div>';
            $h .= '<div style="font-size:.65rem;color:#888;margin-top:.2em">' . htmlspecialchars($p['fruit']) . '</div>';
            $h .= '</a>';
        }
        $h .= '</div>';
        $h .= '<div style="margin-top:1.5em;font-size:.65rem;letter-spacing:4px;color:rgba(246,195,67,.3)">&#9849; SOLI DEO GLORIA &#9849;</div>';
        $h .= '</section>';
        return $h;
    }
}
