<?php
/**
 * GoSiteMe Discord Bot — Tools Module
 * ════════════════════════════════════
 * /status  — Website monitoring & SSL check
 * /weather — Weather lookup (OpenMeteo, free)
 * /domain  — Domain availability & WHOIS
 * /qr      — QR code generator
 * /crypto  — Crypto prices via CoinGecko
 * /color   — Color info & palette
 */

function handleStatus(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $url = '';
    foreach ($opts as $o) { if ($o['name'] === 'url') $url = $o['value']; }
    if (!$url) { respond("Enter a URL! `/status url:example.com`"); return; }

    // Sanitize: only allow valid hostnames
    $url = preg_replace('#^https?://#', '', $url);
    $url = preg_replace('#[/\\?#].*$#', '', $url);
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\.\-]{1,253}\.[a-zA-Z]{2,}$/', $url)) {
        respond("❌ Invalid domain format."); return;
    }

    deferResponse();

    $results = [];
    $start = microtime(true);
    $ch = curl_init("https://$url");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_USERAGENT => 'GoSiteMe-Bot/2.0',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $responseTime = round((microtime(true) - $start) * 1000);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $sslInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
    $redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $primaryIP = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    $error = curl_error($ch);
    curl_close($ch);

    // SSL cert check
    $sslEmbed = '';
    $sslExpiry = '';
    $ctx = stream_context_create(["ssl" => ["capture_peer_cert" => true, "verify_peer" => false]]);
    $socket = @stream_socket_client("ssl://$url:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if ($socket) {
        $params = stream_context_get_params($socket);
        if (isset($params['options']['ssl']['peer_certificate'])) {
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            $validTo = $cert['validTo_time_t'] ?? 0;
            $daysLeft = intdiv($validTo - time(), 86400);
            $issuer = $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown';
            $sslExpiry = date('M j, Y', $validTo);
            $sslEmbed = 'valid';
            if ($daysLeft < 7) $sslEmbed = 'critical';
            elseif ($daysLeft < 30) $sslEmbed = 'warning';
        }
        fclose($socket);
    }

    // Status determination
    $online = $httpCode >= 200 && $httpCode < 400;
    $statusStr = $online ? '🟢 Online' : ($httpCode ? "🔴 Error ($httpCode)" : '🔴 Offline');
    $color = $online ? 0x57F287 : 0xED4245;

    // DNS records
    $dns = @dns_get_record($url, DNS_A);
    $dnsIps = array_map(fn($r) => $r['ip'] ?? '', $dns ?: []);

    $fields = [
        field('Status', $statusStr, true),
        field('HTTP Code', $httpCode ? (string)$httpCode : 'N/A', true),
        field('Response Time', "{$responseTime}ms", true),
        field('IP Address', $primaryIP ?: 'N/A', true),
        field('Redirects', (string)$redirectCount, true),
    ];
    if ($sslEmbed) {
        $sslStatus = $sslEmbed === 'valid' ? "🟢 Valid (expires $sslExpiry)" :
            ($sslEmbed === 'warning' ? "🟡 Expires soon ($sslExpiry)" : "🔴 Expiring! ($sslExpiry)");
        $fields[] = field('SSL Certificate', $sslStatus, false);
    }
    if ($error) $fields[] = field('Error', truncate($error, 100), false);
    if (!empty($dnsIps)) $fields[] = field('DNS A Records', implode(', ', array_filter($dnsIps)), false);

    followUp([
        'embeds' => [embed("🌐 Status: $url", '', $color, $fields)],
        'components' => [actionRow([
            btn("status_recheck_$url", '🔄 Recheck', 2),
            btn("status_ssl_$url", '🔒 SSL Details', 2),
        ])],
    ]);
}


function handleWeather(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $location = '';
    foreach ($opts as $o) { if ($o['name'] === 'location') $location = $o['value']; }
    if (!$location) { respond("Specify a location! `/weather location:New York`"); return; }

    deferResponse();

    // Geocode location via Open-Meteo
    $loc = urlencode($location);
    $geo = json_decode(httpGet("https://geocoding-api.open-meteo.com/v1/search?name=$loc&count=1&language=en"), true);
    if (empty($geo['results'])) {
        followUp(['content' => "❌ Location **$location** not found."]); return;
    }

    $place = $geo['results'][0];
    $lat = $place['latitude'];
    $lon = $place['longitude'];
    $city = $place['name'];
    $country = $place['country'] ?? '';
    $tz = $place['timezone'] ?? 'UTC';

    // Fetch weather
    $w = json_decode(httpGet("https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lon&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,wind_direction_10m,pressure_msl,uv_index&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=" . urlencode($tz) . "&forecast_days=3"), true);

    if (!isset($w['current'])) {
        followUp(['content' => "❌ Weather data unavailable."]); return;
    }

    $cur = $w['current'];
    $daily = $w['daily'] ?? [];
    $tempC = $cur['temperature_2m'];
    $tempF = round($tempC * 9 / 5 + 32, 1);
    $feelsC = $cur['apparent_temperature'];
    $feelsF = round($feelsC * 9 / 5 + 32, 1);
    $humidity = $cur['relative_humidity_2m'];
    $windKmh = $cur['wind_speed_10m'];
    $windMph = round($windKmh * 0.621371, 1);
    $pressure = $cur['pressure_msl'];
    $uv = $cur['uv_index'];
    $code = $cur['weather_code'];

    // Weather code to emoji/description
    $weatherMap = [
        0 => ['☀️', 'Clear sky'], 1 => ['🌤️', 'Mainly clear'], 2 => ['⛅', 'Partly cloudy'],
        3 => ['☁️', 'Overcast'], 45 => ['🌫️', 'Fog'], 48 => ['🌫️', 'Rime fog'],
        51 => ['🌦️', 'Light drizzle'], 53 => ['🌦️', 'Drizzle'], 55 => ['🌧️', 'Heavy drizzle'],
        61 => ['🌧️', 'Light rain'], 63 => ['🌧️', 'Moderate rain'], 65 => ['🌧️', 'Heavy rain'],
        71 => ['🌨️', 'Light snow'], 73 => ['🌨️', 'Snow'], 75 => ['❄️', 'Heavy snow'],
        80 => ['🌦️', 'Light showers'], 81 => ['🌧️', 'Showers'], 82 => ['⛈️', 'Heavy showers'],
        95 => ['⛈️', 'Thunderstorm'], 96 => ['⛈️', 'Thunder + hail'], 99 => ['⛈️', 'Severe storm'],
    ];
    [$emoji, $desc] = $weatherMap[$code] ?? ['🌡️', 'Unknown'];

    // UV index rating
    $uvRating = $uv <= 2 ? '🟢 Low' : ($uv <= 5 ? '🟡 Moderate' : ($uv <= 7 ? '🟠 High' : '🔴 Very High'));

    // Wind direction to compass
    $dirs = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
    $compass = $dirs[(int)round($cur['wind_direction_10m'] / 22.5) % 16];

    // Forecast
    $forecastStr = '';
    for ($i = 1; $i < min(3, count($daily['time'] ?? [])); $i++) {
        $day = date('D', strtotime($daily['time'][$i]));
        $hi = $daily['temperature_2m_max'][$i];
        $lo = $daily['temperature_2m_min'][$i];
        $hiF = round($hi * 9 / 5 + 32);
        $loF = round($lo * 9 / 5 + 32);
        $dc = $daily['weather_code'][$i] ?? 0;
        $de = $weatherMap[$dc][0] ?? '🌡️';
        $rain = $daily['precipitation_probability_max'][$i] ?? 0;
        $forecastStr .= "$de **$day** {$hi}°/{$lo}°C ({$hiF}°/{$loF}°F) 💧{$rain}%\n";
    }

    followUp(['embeds' => [embed(
        "$emoji Weather: $city, $country",
        "**$desc** — {$tempC}°C / {$tempF}°F\nFeels like {$feelsC}°C / {$feelsF}°F",
        0x3498DB,
        [
            field('🌡️ Temperature', "{$tempC}°C / {$tempF}°F", true),
            field('💧 Humidity', "{$humidity}%", true),
            field('💨 Wind', "{$windKmh} km/h ({$windMph} mph) $compass", true),
            field('📊 Pressure', "{$pressure} hPa", true),
            field('☀️ UV Index', "$uv ($uvRating)", true),
            field('📅 Forecast', $forecastStr ?: 'N/A', false),
        ]
    )]]);
}


function handleDomain(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $domain = '';
    foreach ($opts as $o) { if ($o['name'] === 'domain') $domain = $o['value']; }
    if (!$domain) { respond("Enter a domain! `/domain domain:example.com`"); return; }

    // Sanitize domain
    $domain = strtolower(preg_replace('#^https?://#', '', $domain));
    $domain = preg_replace('#[/\\?#].*$#', '', $domain);
    if (!preg_match('/^[a-z0-9][a-z0-9.\-]{1,253}\.[a-z]{2,}$/', $domain)) {
        respond("❌ Invalid domain format."); return;
    }

    deferResponse();

    // DNS lookup
    $aRecords = @dns_get_record($domain, DNS_A);
    $mxRecords = @dns_get_record($domain, DNS_MX);
    $nsRecords = @dns_get_record($domain, DNS_NS);
    $txtRecords = @dns_get_record($domain, DNS_TXT);

    $registered = !empty($aRecords) || !empty($nsRecords);
    $color = $registered ? 0xFEE75C : 0x57F287;
    $status = $registered ? '🔴 Registered' : '🟢 Available';

    $fields = [field('Status', $status, true)];

    if (!empty($aRecords)) {
        $ips = array_map(fn($r) => $r['ip'] ?? '', $aRecords);
        $fields[] = field('A Records', implode(', ', array_filter($ips)), true);
    }
    if (!empty($nsRecords)) {
        $ns = array_map(fn($r) => $r['target'] ?? '', $nsRecords);
        $fields[] = field('Nameservers', implode("\n", array_slice(array_filter($ns), 0, 4)), true);
    }
    if (!empty($mxRecords)) {
        $mx = array_map(fn($r) => ($r['pri'] ?? 0) . ' ' . ($r['target'] ?? ''), $mxRecords);
        $fields[] = field('MX (Email)', implode("\n", array_slice($mx, 0, 3)), true);
    }

    // TXT highlights
    $hasSPF = false; $hasDMARC = false; $hasVerification = false;
    foreach ($txtRecords ?: [] as $t) {
        $txt = $t['txt'] ?? '';
        if (str_starts_with($txt, 'v=spf')) $hasSPF = true;
        if (str_starts_with($txt, 'v=DMARC')) $hasDMARC = true;
        if (str_contains($txt, 'google-site-verification') || str_contains($txt, 'MS=')) $hasVerification = true;
    }
    $fields[] = field('Email Security', ($hasSPF ? '✅ SPF' : '❌ No SPF') . "\n" . ($hasDMARC ? '✅ DMARC' : '❌ No DMARC'), true);

    // SSL check
    $ctx = stream_context_create(["ssl" => ["capture_peer_cert" => true, "verify_peer" => false]]);
    $socket = @stream_socket_client("ssl://$domain:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx);
    if ($socket) {
        $params = stream_context_get_params($socket);
        if (isset($params['options']['ssl']['peer_certificate'])) {
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            $validTo = $cert['validTo_time_t'] ?? 0;
            $daysLeft = intdiv($validTo - time(), 86400);
            $issuer = $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown';
            $sslText = "✅ Valid ($daysLeft days)\nIssuer: $issuer";
        } else {
            $sslText = '❌ No certificate';
        }
        fclose($socket);
    } else {
        $sslText = '❌ No SSL';
    }
    $fields[] = field('🔒 SSL', $sslText, true);

    $buyBtn = !$registered
        ? [btn('domain_buy_' . str_replace('.', '_', $domain), '🛒 Buy at GoSiteMe', 5, false, null, 'https://gositeme.com/domains')]
        : [];

    followUp([
        'embeds' => [embed("🌍 Domain: $domain", '', $color, $fields)],
        'components' => !empty($buyBtn) ? [actionRow($buyBtn)] : [],
    ]);
}


function handleQr(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $text = ''; $size = 200;
    foreach ($opts as $o) {
        if ($o['name'] === 'text') $text = $o['value'];
        if ($o['name'] === 'size') $size = max(100, min(1000, (int)$o['value']));
    }
    if (!$text) { respond("Enter text or URL! `/qr text:https://gositeme.com`"); return; }

    // Use Google Charts API for QR generation (free, reliable)
    $encoded = urlencode($text);
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=$encoded&format=png&margin=10";

    respond(null, [embed("📱 QR Code", "Content: `" . truncate($text, 100) . "`", 0x2F3136, [], $qrUrl)], [
        actionRow([
            btn("qr_dl_" . substr(md5($text), 0, 8), '📥 Download', 5, false, null, $qrUrl),
        ])
    ]);
}


function handleCrypto(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $coin = 'bitcoin';
    foreach ($opts as $o) { if ($o['name'] === 'coin') $coin = strtolower($o['value']); }

    // Normalize common tickers
    $aliases = [
        'btc' => 'bitcoin', 'eth' => 'ethereum', 'sol' => 'solana', 'bnb' => 'binancecoin',
        'xrp' => 'ripple', 'ada' => 'cardano', 'dot' => 'polkadot', 'doge' => 'dogecoin',
        'avax' => 'avalanche-2', 'matic' => 'matic-network', 'link' => 'chainlink',
        'ltc' => 'litecoin', 'shib' => 'shiba-inu', 'uni' => 'uniswap', 'atom' => 'cosmos',
        'xlm' => 'stellar', 'near' => 'near', 'algo' => 'algorand', 'apt' => 'aptos',
        'arb' => 'arbitrum', 'op' => 'optimism', 'sui' => 'sui',
    ];
    $coin = $aliases[$coin] ?? $coin;

    deferResponse();

    $url = "https://api.coingecko.com/api/v3/coins/$coin?localization=false&tickers=false&community_data=false&developer_data=false";
    $resp = json_decode(httpGet($url), true);

    if (!$resp || isset($resp['error'])) {
        followUp(['content' => "❌ Coin **$coin** not found. Try `/crypto coin:bitcoin` or `/crypto coin:eth`"]); return;
    }

    $name = $resp['name'] ?? $coin;
    $symbol = strtoupper($resp['symbol'] ?? '');
    $thumb = $resp['image']['small'] ?? '';
    $market = $resp['market_data'] ?? [];
    $price = $market['current_price']['usd'] ?? 0;
    $change24h = $market['price_change_percentage_24h'] ?? 0;
    $change7d = $market['price_change_percentage_7d'] ?? 0;
    $change30d = $market['price_change_percentage_30d'] ?? 0;
    $marketCap = $market['market_cap']['usd'] ?? 0;
    $volume = $market['total_volume']['usd'] ?? 0;
    $ath = $market['ath']['usd'] ?? 0;
    $athChange = $market['ath_change_percentage']['usd'] ?? 0;
    $rank = $resp['market_cap_rank'] ?? 'N/A';
    $high24 = $market['high_24h']['usd'] ?? 0;
    $low24 = $market['low_24h']['usd'] ?? 0;
    $supply = $market['circulating_supply'] ?? 0;
    $maxSupply = $market['max_supply'] ?? null;

    $arrow24 = $change24h >= 0 ? '📈' : '📉';
    $arrow7 = $change7d >= 0 ? '📈' : '📉';
    $changeColor = $change24h >= 0 ? 0x57F287 : 0xED4245;

    $formatNum = function($n) {
        if ($n >= 1e12) return '$' . round($n / 1e12, 2) . 'T';
        if ($n >= 1e9) return '$' . round($n / 1e9, 2) . 'B';
        if ($n >= 1e6) return '$' . round($n / 1e6, 2) . 'M';
        if ($n >= 1e3) return '$' . number_format($n, 0);
        return '$' . number_format($n, 2);
    };

    $priceStr = $price >= 1 ? '$' . number_format($price, 2) : '$' . number_format($price, 6);

    followUp(['embeds' => [array_merge(
        embed(
            "$name ($symbol) — $priceStr",
            "$arrow24 **24h:** " . number_format($change24h, 2) . "% | $arrow7 **7d:** " . number_format($change7d, 2) . "% | **30d:** " . number_format($change30d, 2) . "%",
            $changeColor,
            [
                field('💰 Price', $priceStr, true),
                field('📊 Rank', "#$rank", true),
                field('📈 24h Range', ($formatNum)($low24) . ' — ' . ($formatNum)($high24), true),
                field('🏦 Market Cap', ($formatNum)($marketCap), true),
                field('💹 Volume 24h', ($formatNum)($volume), true),
                field('📦 Supply', number_format($supply, 0) . ($maxSupply ? ' / ' . number_format($maxSupply, 0) : ''), true),
                field('🏆 ATH', ($formatNum)($ath) . " (" . number_format($athChange, 1) . "%)", true),
            ]
        ),
        ['thumbnail' => ['url' => $thumb]]
    )]]);
}


function handleColor(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $hex = '';
    foreach ($opts as $o) { if ($o['name'] === 'hex') $hex = $o['value']; }
    $hex = ltrim($hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        respond("Enter a valid hex color! `/color hex:#FF5733`"); return;
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // HSL conversion
    $rn = $r / 255; $gn = $g / 255; $bn = $b / 255;
    $max = max($rn, $gn, $bn); $min = min($rn, $gn, $bn);
    $l = ($max + $min) / 2; $d = $max - $min;
    if ($d == 0) { $h = $s = 0; }
    else {
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max == $rn) $h = fmod(($gn - $bn) / $d, 6);
        elseif ($max == $gn) $h = ($bn - $rn) / $d + 2;
        else $h = ($rn - $gn) / $d + 4;
        $h = round($h * 60); if ($h < 0) $h += 360;
    }
    $s = round($s * 100); $l = round($l * 100);

    // Complementary color
    $compH = ($h + 180) % 360;
    $compRGB = hslToRgb($compH, $s / 100, $l / 100);
    $compHex = sprintf('%02X%02X%02X', $compRGB[0], $compRGB[1], $compRGB[2]);

    // Analogous
    $a1H = ($h + 30) % 360; $a2H = ($h + 330) % 360;

    $intColor = hexdec($hex);
    $swatch = "https://singlecolorimage.com/get/$hex/200x200";

    respond(null, [array_merge(
        embed("🎨 Color: #$hex", '', $intColor, [
            field('HEX', "#$hex", true),
            field('RGB', "rgb($r, $g, $b)", true),
            field('HSL', "hsl($h, $s%, $l%)", true),
            field('Decimal', (string)$intColor, true),
            field('Complementary', "#$compHex", true),
            field('Brightness', ($l > 50 ? '☀️ Light' : '🌙 Dark') . " ($l%)", true),
        ]),
        ['thumbnail' => ['url' => $swatch]]
    )]);
}

function hslToRgb(float $h, float $s, float $l): array {
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;
    if ($h < 60) [$r, $g, $b] = [$c, $x, 0];
    elseif ($h < 120) [$r, $g, $b] = [$x, $c, 0];
    elseif ($h < 180) [$r, $g, $b] = [0, $c, $x];
    elseif ($h < 240) [$r, $g, $b] = [0, $x, $c];
    elseif ($h < 300) [$r, $g, $b] = [$x, 0, $c];
    else [$r, $g, $b] = [$c, 0, $x];
    return [(int)round(($r + $m) * 255), (int)round(($g + $m) * 255), (int)round(($b + $m) * 255)];
}
