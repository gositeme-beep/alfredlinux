<?php
// includes/sovereign.php — Sovereign Domain Registry (Pillar 5)
//
// Doctrine: A covenant token (witness stone) is a Kingdom passport.
// A sovereign may bind one or more domains to their token, proving stewardship
// via DNS TXT challenge. Every binding is appended to a HMAC-chained registry,
// inheriting the same chain-of-witness as the covenant itself.
//
// "And hath made of one blood all nations of men for to dwell on all the face
//  of the earth, and hath determined the times before appointed, and the bounds
//  of their habitation."  — Acts 17:26 (AKJV)
//
// Storage:
//   /home/gositeme/covenant-audit/log.jsonl              (covenant entries)
//   /home/gositeme/covenant-audit/sovereign.jsonl        (domain bindings, chained)
//   /home/gositeme/covenant-audit/.sovereign-chain-head  (latest mac)
//   /home/gositeme/covenant-audit/.hmac.key              (shared key)

declare(strict_types=1);

const SOV_REGISTRY      = '/home/gositeme/covenant-audit/sovereign.jsonl';
const SOV_CHAIN_HEAD    = '/home/gositeme/covenant-audit/.sovereign-chain-head';
const SOV_HMAC_KEY_FILE = '/home/gositeme/covenant-audit/.hmac.key';
const SOV_COVENANT_LOG  = '/home/gositeme/covenant-audit/log.jsonl';
const SOV_TXT_PREFIX    = 'alfredlinux-covenant=';

if (!function_exists('sov_hmac_key')) {
    function sov_hmac_key(): string {
        $k = @file_get_contents(SOV_HMAC_KEY_FILE);
        if ($k === false || strlen($k) < 32) return hash('sha256', 'sov-fallback', true);
        return $k;
    }
}

if (!function_exists('sov_normalize_domain')) {
    function sov_normalize_domain(string $d): string {
        $d = strtolower(trim($d));
        $d = preg_replace('~^https?://~', '', $d);
        $d = preg_replace('~/.*$~', '', $d);
        $d = preg_replace('~^www\.~', '', $d);
        return $d;
    }
}

if (!function_exists('sov_valid_domain')) {
    function sov_valid_domain(string $d): bool {
        return (bool)preg_match('/^([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $d);
    }
}

if (!function_exists('sov_token_exists')) {
    /** Confirms a covenant token was actually written in the chained covenant log. */
    function sov_token_exists(string $token): bool {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return false;
        $fp = @fopen(SOV_COVENANT_LOG, 'r');
        if (!$fp) return false;
        $needle = '"token":"' . $token . '"';
        $found = false;
        while (($line = fgets($fp)) !== false) {
            if (strpos($line, $needle) !== false) { $found = true; break; }
        }
        fclose($fp);
        return $found;
    }
}

if (!function_exists('sov_challenge_for')) {
    /** Deterministic per (token,domain) — HMAC-derived, no state required. */
    function sov_challenge_for(string $token, string $domain): string {
        $mac = hash_hmac('sha256', 'CHALLENGE|' . $token . '|' . $domain, sov_hmac_key());
        return SOV_TXT_PREFIX . substr($mac, 0, 48);
    }
}

if (!function_exists('sov_dns_txt_records')) {
    function sov_dns_txt_records(string $domain): array {
        $records = @dns_get_record($domain, DNS_TXT);
        if (!is_array($records)) return [];
        $out = [];
        foreach ($records as $r) {
            if (!empty($r['txt'])) $out[] = (string)$r['txt'];
            if (!empty($r['entries']) && is_array($r['entries'])) {
                $out[] = implode('', $r['entries']);
            }
        }
        return $out;
    }
}

if (!function_exists('sov_verify_dns')) {
    function sov_verify_dns(string $token, string $domain): array {
        $expected = sov_challenge_for($token, $domain);
        $records  = sov_dns_txt_records($domain);
        if (!$records) return ['ok'=>false,'reason'=>'no TXT records found at apex of ' . $domain,'expected'=>$expected,'found'=>[]];
        foreach ($records as $r) {
            if (trim($r) === $expected) return ['ok'=>true,'reason'=>'matched','expected'=>$expected,'found'=>$records];
        }
        return ['ok'=>false,'reason'=>'expected TXT not present','expected'=>$expected,'found'=>$records];
    }
}

if (!function_exists('sov_already_sealed')) {
    function sov_already_sealed(string $token, string $domain): bool {
        if (!is_readable(SOV_REGISTRY)) return false;
        $needle1 = '"token":"' . $token . '"';
        $needle2 = '"domain":"' . $domain . '"';
        foreach (file(SOV_REGISTRY, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos($line, $needle1) !== false && strpos($line, $needle2) !== false) return true;
        }
        return false;
    }
}

if (!function_exists('sov_seal_domain')) {
    /** Append a chained binding entry. Returns ['ok'=>bool, 'mac'=>..., 'cert'=>...]. */
    function sov_seal_domain(string $token, string $domain, string $ip, string $ua, bool $publicListing): array {
        $token  = strtolower(trim($token));
        $domain = sov_normalize_domain($domain);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return ['ok'=>false,'reason'=>'bad token format'];
        if (!sov_valid_domain($domain))              return ['ok'=>false,'reason'=>'bad domain format'];
        if (!sov_token_exists($token))               return ['ok'=>false,'reason'=>'token not found in covenant log'];
        if (sov_already_sealed($token, $domain))     return ['ok'=>false,'reason'=>'already sealed'];

        $verify = sov_verify_dns($token, $domain);
        if (!$verify['ok']) return ['ok'=>false,'reason'=>'dns: ' . $verify['reason'], 'expected_txt'=>$verify['expected']];

        $prev   = is_readable(SOV_CHAIN_HEAD) ? trim((string)file_get_contents(SOV_CHAIN_HEAD)) : 'GENESIS';
        $now    = gmdate('c');
        $payload = $prev . '|' . $token . '|' . $domain . '|' . $ip . '|' . substr($ua,0,500) . '|' . $now;
        $mac    = hash_hmac('sha256', $payload, sov_hmac_key());

        $entry = [
            'ts'        => $now,
            'kind'      => 'sovereign-binding',
            'token'     => $token,
            'domain'    => $domain,
            'ip'        => $ip,
            'ua'        => substr($ua, 0, 500),
            'public'    => $publicListing,
            'prev'      => $prev,
            'hmac'      => $mac,
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
        $fp = fopen(SOV_REGISTRY, 'a');
        if (!$fp) return ['ok'=>false,'reason'=>'cannot open registry'];
        flock($fp, LOCK_EX);
        fwrite($fp, $line);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod(SOV_REGISTRY, 0640);

        file_put_contents(SOV_CHAIN_HEAD, $mac, LOCK_EX);
        @chmod(SOV_CHAIN_HEAD, 0640);

        return ['ok'=>true, 'mac'=>$mac, 'prev'=>$prev, 'cert'=>sov_certificate_text($token, $domain, $now, $mac)];
    }
}

if (!function_exists('sov_certificate_text')) {
    function sov_certificate_text(string $token, string $domain, string $ts, string $mac): string {
        $line = str_repeat('=', 70);
        $tokenShort = substr($token, 0, 16) . '...' . substr($token, -8);
        $cert  = "$line\n";
        $cert .= "             ALFRED LINUX SOVEREIGN DOMAIN BINDING\n";
        $cert .= "                       \xE2\x9C\xA0 Bound Stone \xE2\x9C\xA0\n";
        $cert .= "$line\n\n";
        $cert .= "  On this day, $ts (UTC),\n";
        $cert .= "  the bearer of covenant token\n\n";
        $cert .= "      $tokenShort\n\n";
        $cert .= "  did seal stewardship of the domain\n\n";
        $cert .= "      $domain\n\n";
        $cert .= "  unto the Kingdom of God, witnessed by DNS TXT and\n";
        $cert .= "  bound to the chain of witness stones.\n\n";
        $cert .= str_repeat('-', 70) . "\n";
        $cert .= "  HMAC seal:  $mac\n";
        $cert .= str_repeat('-', 70) . "\n\n";
        $cert .= "      \"And hath made of one blood all nations of men for to\n";
        $cert .= "       dwell on all the face of the earth, and hath determined\n";
        $cert .= "       the times before appointed, and the bounds of their\n";
        $cert .= "       habitation.\"\n";
        $cert .= "                                              \xE2\x80\x94 Acts 17:26 (AKJV)\n\n";
        $cert .= "      \"And he had a name written, that no man knew, but he\n";
        $cert .= "       himself.\"\n";
        $cert .= "                                              \xE2\x80\x94 Revelation 19:12 (AKJV)\n\n";
        $cert .= "$line\n";
        $cert .= "      Glory be to Yeshua Jesus, King of kings, forever.\n";
        $cert .= "$line\n";

        $dir = '/home/gositeme/covenant-audit/sovereign-certs';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $safe = preg_replace('/[^a-z0-9.-]/', '_', $domain);
        @file_put_contents("$dir/$safe.txt", $cert, LOCK_EX);
        return $cert;
    }
}

if (!function_exists('sov_directory')) {
    /** Returns array of public bindings for the directory page. */
    function sov_directory(): array {
        if (!is_readable(SOV_REGISTRY)) return [];
        $out = [];
        foreach (file(SOV_REGISTRY, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $e = json_decode($line, true);
            if (!is_array($e) || empty($e['public'])) continue;
            $out[] = [
                'ts'         => $e['ts'] ?? '',
                'domain'     => $e['domain'] ?? '',
                'token_head' => substr($e['token'] ?? '', 0, 16),
                'mac_head'   => substr($e['hmac'] ?? '', 0, 16),
            ];
        }
        return $out;
    }
}

if (!function_exists('sov_lookup_domain')) {
    /** Public verification: was this domain sealed by a covenant holder? */
    function sov_lookup_domain(string $domain): ?array {
        $domain = sov_normalize_domain($domain);
        if (!is_readable(SOV_REGISTRY)) return null;
        foreach (file(SOV_REGISTRY, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $e = json_decode($line, true);
            if (!is_array($e)) continue;
            if (($e['domain'] ?? '') !== $domain) continue;
            return [
                'sealed'     => true,
                'ts'         => $e['ts'] ?? '',
                'domain'     => $domain,
                'token_head' => substr($e['token'] ?? '', 0, 16) . '...',
                'mac'        => $e['hmac'] ?? '',
                'public'     => !empty($e['public']),
            ];
        }
        return ['sealed' => false, 'domain' => $domain];
    }
}

if (!function_exists('sov_client_ip')) {
    function sov_client_ip(): string {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) return trim(explode(',', $_SERVER[$h])[0]);
        }
        return '0.0.0.0';
    }
}
