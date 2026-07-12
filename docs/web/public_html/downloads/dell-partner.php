<?php
/**
 * Private Dell Download Page — Alfred Linux v7.77 GA
 * Token-gated direct ISO download for Dell Technologies World 2026
 * 
 * URL: https://alfredlinux.com/downloads/dell-partner.php?token=DELL-TW2026-XXXXXX
 * 
 * This page provides Dell with a direct HTTP download link (no torrent),
 * SHA256 checksum, and basic download info. Token prevents public access.
 */

// ── Security: token validation ──
$validTokens = [
    'DELL-TW2026-7F42A9'     => ['name' => 'Dell Technologies',  'expires' => '2026-06-30'],
    'DELL-TW2026-K1NG77'     => ['name' => 'Dell Partnerships',  'expires' => '2026-06-30'],
    'COMMANDER-INTERNAL-777' => ['name' => 'Commander (Internal Test)', 'expires' => '2026-12-31'],
];

$token = $_GET['token'] ?? '';
$tokenData = $validTokens[$token] ?? null;

// Best-guess client IP (handles a single hop of CF/proxy without trusting arbitrary chains).
function dell_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
        if (!empty($_SERVER[$h])) {
            return trim(explode(',', (string) $_SERVER[$h])[0]);
        }
    }
    return '0.0.0.0';
}

function dell_audit_log(string $event, ?array $tokenData, string $token, array $extra = []): void {
    $line = [
        'ts'      => date('Y-m-d H:i:s P'),
        'event'   => $event,
        'token'   => $token ?: '-',
        'partner' => $tokenData['name'] ?? '(invalid)',
        'ip'      => dell_client_ip(),
        'ua'      => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 200),
        'ref'     => substr((string) ($_SERVER['HTTP_REFERER'] ?? '-'), 0, 200),
        'method'  => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'uri'     => substr((string) ($_SERVER['REQUEST_URI'] ?? '-'), 0, 200),
    ];
    foreach ($extra as $k => $v) $line[$k] = $v;
    @file_put_contents(
        __DIR__ . '/dell-download-log.txt',
        implode(' | ', array_map(static fn($k, $v) => "$k=" . str_replace(["\n","\r","|"], ' ', (string) $v), array_keys($line), array_values($line))) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

if (!$tokenData) {
    dell_audit_log('TOKEN_REJECTED', null, (string) $token);
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body style="font-family:system-ui;text-align:center;padding:4rem;background:#0a0a0a;color:#fff"><h1>403 — Access Denied</h1><p>This download requires a valid partner token.</p><p style="color:#888">Contact <a href="mailto:commander@gositeme.com" style="color:#b8860b">commander@gositeme.com</a> for access.</p></body></html>';
    exit;
}

// Check expiry
if (strtotime($tokenData['expires']) < time()) {
    http_response_code(410);
    echo '<!DOCTYPE html><html><head><title>Link Expired</title></head><body style="font-family:system-ui;text-align:center;padding:4rem;background:#0a0a0a;color:#fff"><h1>410 — Link Expired</h1><p>This download link has expired. Contact <a href="mailto:commander@gositeme.com" style="color:#b8860b">commander@gositeme.com</a> for a new link.</p></body></html>';
    exit;
}

require_once __DIR__ . '/../includes/ga-release-state.php';

// ── ISO details (canonical basename in ga-release-state.php) ──
$isoFilename = $gaIsoBasename . '.iso';
$isoPath = __DIR__ . '/' . $isoFilename;
$isoExists = file_exists($isoPath);
$isoSize = $isoExists ? filesize($isoPath) : 0;
$isoSizeGB = $isoExists ? round($isoSize / (1024*1024*1024), 2) : '~7.77';

function dell_partner_read_sum(string $file, string $isoFilename): string {
    if (!is_file($file)) return '';
    foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
        if (strpos($line, $isoFilename) !== false) {
            return trim(explode(' ', $line)[0]);
        }
    }
    return '';
}
$sha256 = dell_partner_read_sum(__DIR__ . '/SHA256SUMS-7.77.txt', $isoFilename);
if ($sha256 === '') {
    $sha256 = dell_partner_read_sum(__DIR__ . '/' . $isoFilename . '.sha256', $isoFilename);
}
$sha512 = dell_partner_read_sum(dirname(__DIR__) . '/releases/7.77/SHA512SUMS', $isoFilename);
$blake3 = dell_partner_read_sum(dirname(__DIR__) . '/releases/7.77/BLAKE3SUMS', $isoFilename);
$gpgSigPath = __DIR__ . '/' . $isoFilename . '.asc';
$gpgSigReady = is_readable($gpgSigPath) && (int) @filesize($gpgSigPath) > 64;
$dellHookCount = (int) ($gaFrozenIsoHookCount ?? 2);
$dellPlannedHookCount = (int) ($gaPlannedHookCount ?? 150);
$dellHooksMatchPlan = ($dellHookCount >= $dellPlannedHookCount);

// ── Handle direct download request ──
if (isset($_GET['download']) && $isoExists) {
    dell_audit_log('DOWNLOAD_START', $tokenData, $token, [
        'iso'   => $isoFilename,
        'bytes' => $isoSize,
    ]);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $isoFilename . '"');
    header('Content-Length: ' . $isoSize);
    header('Cache-Control: no-cache, must-revalidate');
    @ob_end_clean();
    readfile($isoPath);
    exit;
}

// Page view — record so Commander panel sees Dell open the page even before they click Download.
dell_audit_log('PAGE_VIEW', $tokenData, $token, [
    'iso_present' => $isoExists ? '1' : '0',
]);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred Linux v7.77 — Partner Download | Dell Technologies</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0a0a0f; color: #e0e0e0; min-height: 100vh; }
        
        .header { background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3e 100%); padding: 2rem; text-align: center; border-bottom: 3px solid #b8860b; }
        .header h1 { font-size: 2rem; color: #fff; margin-bottom: 0.5rem; }
        .header h1 span { color: #b8860b; }
        .header .subtitle { color: #888; font-size: 0.95rem; }
        .header .partner-badge { display: inline-block; background: #b8860b; color: #000; padding: 0.3rem 1rem; border-radius: 20px; font-weight: 700; font-size: 0.85rem; margin-top: 0.8rem; }
        
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1.5rem; }
        
        .card { background: #12121a; border: 1px solid #2a2a3e; border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem; }
        .card h2 { color: #b8860b; font-size: 1.3rem; margin-bottom: 1rem; }
        
        .download-btn { display: block; text-align: center; background: linear-gradient(135deg, #b8860b, #daa520); color: #000; font-size: 1.2rem; font-weight: 700; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; margin: 1.5rem 0; transition: transform 0.2s; }
        .download-btn:hover { transform: scale(1.02); }
        .download-btn.disabled { background: #333; color: #666; cursor: not-allowed; }
        
        .specs { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .spec { padding: 1rem; background: #1a1a25; border-radius: 8px; }
        .spec .label { color: #888; font-size: 0.85rem; margin-bottom: 0.3rem; }
        .spec .value { color: #fff; font-weight: 600; }
        
        .checksum { background: #1a1a25; padding: 1rem; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.8rem; word-break: break-all; color: #4cc9f0; margin-top: 1rem; }
        .checksum .label { color: #888; font-family: 'Segoe UI', system-ui, sans-serif; margin-bottom: 0.3rem; }
        
        .hooks-badge { display: inline-block; background: #1a3a1a; color: #4caf50; padding: 0.2rem 0.8rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        
        .scripture { text-align: center; color: #b8860b; font-style: italic; padding: 1.5rem; font-size: 0.95rem; line-height: 1.6; }
        
        .footer { text-align: center; padding: 2rem; color: #555; font-size: 0.85rem; border-top: 1px solid #1a1a2e; margin-top: 2rem; }
        .footer a { color: #b8860b; }
        
        @media (max-width: 600px) { .specs { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="header">
    <h1><span>Alfred Linux</span> v7.77</h1>
    <div class="subtitle">Kingdom of God Edition — General Availability</div>
    <div class="partner-badge">PARTNER: <?= htmlspecialchars($tokenData['name'], ENT_QUOTES, 'UTF-8') ?></div>
</div>

<div class="container">
    
    <div class="card">
        <h2>Download ISO</h2>
        <p>Direct HTTP download — no torrent required. <?php if ($dellHooksMatchPlan): ?>Official GA build with <span class="hooks-badge"><?= $dellPlannedHookCount ?> hooks</span> — Matthew 1:17 lineage.<?php else: ?>This snapshot ships <span class="hooks-badge"><?= $dellHookCount ?> of <?= $dellPlannedHookCount ?> hooks</span>; the full <?= $dellPlannedHookCount ?>-hook tree is in source and a reseal is queued — your evaluation copy will be replaced by the full build before Dell Technologies World.<?php endif; ?></p>

        <?php if ($isoExists): ?>
            <a href="?token=<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>&download=1" class="download-btn">
                Download Alfred Linux v7.77 GA (<?= $isoSizeGB ?> GB)
            </a>
        <?php else: ?>
            <div class="download-btn disabled">
                ISO build in progress — check back shortly
            </div>
            <p style="text-align:center;color:#888;margin-top:0.5rem;">The fresh GA build is being prepared. You'll be notified at your partnership email when it's ready.</p>
        <?php endif; ?>

        <div class="specs">
            <div class="spec">
                <div class="label">Base</div>
                <div class="value">Debian Trixie (13)</div>
            </div>
            <div class="spec">
                <div class="label">Desktop</div>
                <div class="value">Wayland (Sovereign Desktop)</div>
            </div>
            <div class="spec">
                <div class="label">Architecture</div>
                <div class="value">x86_64 (file basename <code>intel-amd64</code>; Debian dpkg arch <code>amd64</code>)</div>
            </div>
            <div class="spec">
                <div class="label">Boot</div>
                <div class="value">UEFI + Legacy BIOS</div>
            </div>
            <div class="spec">
                <div class="label">Kernel</div>
                <div class="value">Linux 7.0.12 (custom-compiled)</div>
            </div>
            <div class="spec">
                <div class="label">Encryption</div>
                <div class="value">LUKS2 + Post-quantum (Kyber-1024)</div>
            </div>
            <div class="spec">
                <div class="label">Filesystem</div>
                <div class="value">BTRFS Snapper Matrix (Time Machine)</div>
            </div>
        </div>

        <?php if ($sha512 || $blake3 || $sha256): ?>
        <div class="checksum">
            <?php if ($sha512): ?>
            <div class="label">SHA-512 (recommended)</div>
            <?= htmlspecialchars($sha512, ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
            <?php if ($blake3): ?>
            <div class="label" style="margin-top:0.6rem;">BLAKE3</div>
            <?= htmlspecialchars($blake3, ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
            <?php if ($sha256): ?>
            <div class="label" style="margin-top:0.6rem;">SHA-256 (legacy)</div>
            <?= htmlspecialchars($sha256, ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
            <?php if ($gpgSigReady): ?>
            <div class="label" style="margin-top:0.6rem;">GPG signature</div>
            <a href="<?= htmlspecialchars($isoFilename . '.asc', ENT_QUOTES, 'UTF-8') ?>" style="color:#b8860b;"><?= htmlspecialchars($isoFilename . '.asc', ENT_QUOTES, 'UTF-8') ?></a> &middot; verify with <a href="GPG-KEY.asc" style="color:#b8860b;">GPG-KEY.asc</a> (Key 32BCEDE8C8DD8B00)
            <?php else: ?>
            <div class="label" style="margin-top:0.6rem;color:#e17055;">GPG signature</div>
            <span style="color:#e17055;">pending — will land beside the ISO before Dell evaluation</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>What's Inside — <?= $dellPlannedHookCount ?> Hooks (Matthew 1:17 lineage)</h2>
        <p style="margin-bottom:1rem;">The Alfred Linux GA pipeline runs <?= $dellPlannedHookCount ?> live-build hooks — one for each generation from Abraham to Christ. From post-quantum encryption to the AKJV Bible, from AI agents to sacred stillness mode.<?php if (!$dellHooksMatchPlan): ?> <strong style="color:#b8860b;">This evaluation snapshot</strong> ships <?= $dellHookCount ?> of <?= $dellPlannedHookCount ?>; the rest are committed in source and packed into the next reseal.<?php endif; ?></p>
        <div class="specs">
            <div class="spec"><div class="label">Security</div><div class="value">Zero-Trust Hybrid FDE, Kyber-1024 Quantum Shield, Omahon Memory Vault (Air-gapped AI isolation)</div></div>
            <div class="spec"><div class="label">Enterprise AI</div><div class="value">Alfred Cognitive Engine, Local-Only Voice AI v2, Context-Sealed Intelligence (No telemetry)</div></div>
            <div class="spec"><div class="label">Infrastructure</div><div class="value">Immutable BTRFS Architecture, Auto-rollback Time Machine, Bare-metal Hypervisor ready</div></div>
            <div class="spec"><div class="label">Fleet Management</div><div class="value">Autonomous APT hook injection, Fleet-wide cryptographic attestation, Sovereign OTA</div></div>
            <div class="spec"><div class="label">Development</div><div class="value">Containerized Alfred IDE, Edge-compute optimized, Tier-1 Terminal Toolchain</div></div>
            <div class="spec"><div class="label">Faith & Gaming</div><div class="value">AKJV Bible, Sabbath Stillness Mode, VR Chess Masters (WebXR 3D Arena)</div></div>
        </div>
    </div>
    
    <div class="card">
        <h2>Dell Technologies World 2026</h2>
        <p>May 18–21, 2026 — The Venetian, Las Vegas</p>
        <p style="margin-top:0.8rem;color:#888;">Alfred Linux is seeking a founding hardware partnership with Dell Technologies. We believe Dell's enterprise infrastructure + Alfred's sovereign AI stack = the future of computing. This ISO is provided for your evaluation ahead of Dell Technologies World.</p>
        <p style="margin-top:1rem;">
            <strong>Contact:</strong> <a href="mailto:commander@gositeme.com" style="color:#b8860b;">commander@gositeme.com</a><br>
            <strong>Website:</strong> <a href="https://alfredlinux.com" style="color:#b8860b;">alfredlinux.com</a> | <a href="https://gositeme.com" style="color:#b8860b;">gositeme.com</a>
        </p>
    </div>
    
    <div class="scripture">
        "The grass withereth, the flower fadeth: but the word of our God shall stand for ever."<br>
        — Isaiah 40:8 (AKJV)
    </div>
    
</div>

<div class="footer">
    &copy; 2026 GoSiteMe Inc. — Alfred Linux is a sovereign operating system.<br>
    Partner access expires: <?= htmlspecialchars($tokenData['expires'], ENT_QUOTES, 'UTF-8') ?> | 
    <a href="https://gositeme.com/sovereignty">Sovereignty Declarations</a>
</div>

</body>
</html>