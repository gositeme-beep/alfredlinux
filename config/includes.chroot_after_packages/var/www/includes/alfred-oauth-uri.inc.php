<?php
/**
 * Alfred IDE — shared OAuth redirect-URI normalization & validation.
 * Used by:
 *   - /alfred-login.php (issues codes; stores normalized redirect_uri in DB)
 *   - /api/alfred-ide-token.php (exchanges codes; must compare against the
 *     same normalized form, regardless of what shape the IDE sends)
 *
 * Threat model:
 *   - Open-redirect via attacker-controlled callback_uri → mitigated by host allowlist
 *   - Credentials embedded in URI (user:pass@) → rejected
 *   - Cross-origin code injection → state CSRF binding (login.php) + single-use codes
 *   - DNS-rebind / wildcard subdomain abuse → only loopback, root.com,
 *     <port>.root.com (digits only), and known tunnel providers allowed
 */

if (!function_exists('alfredAllowedHostPatterns')) {
    function alfredAllowedHostPatterns(): array {
        return [
            '#^127\.0\.0\.1(:\d+)?$#',
            '#^localhost(:\d+)?$#i',
            '#^\[::1\](:\d+)?$#',
            '#^root\.com$#i',
            '#^[0-9]+\.root\.com$#i',
            '#\.vscode-cdn\.net$#i',
            '#\.github\.dev$#i',
            '#\.githubpreview\.dev$#i',
            '#\.gitpod\.io$#i',
            '#\.app\.github\.dev$#i',
            '#\.tunnels\.api\.visualstudio\.com$#i',
            '#^[a-z0-9-]+\.inc\.vscode\.dev$#i',
        ];
    }
}

if (!function_exists('alfredNormalizeRedirectUri')) {
    /**
     * Returns the canonical, reachable redirect URI for the given input,
     * or null if the URI is not allowed.
     *
     * Rules:
     *   - https://<port>.root.com/...  → https://root.com/alfred-ide/proxy/<port>/callback
     *     (wildcard DNS does NOT exist on this server; the same-origin path-proxy is used)
     *   - http(s)://127.0.0.1|localhost|[::1] → kept loopback for native VS Code,
     *     OR rewritten to the same path-proxy when the request is reaching us
     *     via root.com (loopback is unreachable from a remote browser).
     *   - Other allowlisted hosts (github.dev, gitpod.io, …) → passed through
     *     with path forced to /callback.
     *
     * @param string $uri          Raw redirect_uri from caller.
     * @param int    $defaultPort  Fallback port for path-proxy rewrites.
     * @param array  $context      Optional. ['referer'=>…, 'host'=>…] used to
     *                             decide whether loopback is reachable. Defaults
     *                             to current request when omitted.
     */
    function alfredNormalizeRedirectUri(string $uri, int $defaultPort = 35721, array $context = []): ?string {
        $uri = trim($uri);
        if ($uri === '' || strlen($uri) > 2000) return null;

        $parts = parse_url($uri);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return null;
        if (!empty($parts['user']) || !empty($parts['pass'])) return null;
        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) return null;

        $hostLower = strtolower($parts['host']);
        $hostCheck = $hostLower . (isset($parts['port']) ? ':' . $parts['port'] : '');

        $allowed = false;
        foreach (alfredAllowedHostPatterns() as $pat) {
            if (preg_match($pat, $hostCheck) || preg_match($pat, $hostLower)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) return null;

        $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
        $path = $parts['path'] ?? '/callback';
        if ($path === '' || $path === '/') $path = '/callback';

        // <port>.root.com → same-origin path-proxy
        if (preg_match('/^([0-9]+)\.root\.com$/', $hostLower, $m)) {
            $proxyPort = (int)$m[1];
            if ($proxyPort < 1024 || $proxyPort > 65535) $proxyPort = $defaultPort;
            return 'https://root.com/alfred-ide/proxy/' . $proxyPort . '/callback';
        }

        // Loopback: rewrite to path-proxy when accessed via root.com origin
        if ($hostLower === '127.0.0.1' || $hostLower === 'localhost' || $hostLower === '[::1]') {
            $referer = $context['referer'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
            $hostSelf = $context['host'] ?? ($_SERVER['HTTP_HOST'] ?? '');
            if (stripos($referer, 'root.com/alfred-ide') !== false
                || stripos($hostSelf, 'root.com') !== false) {
                $proxyPort = $port ?: $defaultPort;
                if ($proxyPort < 1024 || $proxyPort > 65535) $proxyPort = $defaultPort;
                return 'https://root.com/alfred-ide/proxy/' . $proxyPort . '/callback';
            }
            return $scheme . '://' . $hostLower
                . (isset($parts['port']) ? ':' . $parts['port'] : '')
                . $path;
        }

        // Other allowlisted hosts pass through, path forced to /callback
        return $scheme . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . '/callback';
    }
}
