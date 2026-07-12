<?php
/**
 * Alfred Live Codes — Real-time Verification Code Dashboard
 * ============================================
 * Auth: Commander Only (client_id 33)
 * Auto-refreshes every 15 seconds
 * Reads from the email watcher's JSON output
 */
session_start();
require_once __DIR__ . '/../includes/auth-gate.inc.php';

if (!isset($_SESSION['client_id']) || $_SESSION['client_id'] !== 33) {
    http_response_code(403);
    exit('Access denied.');
}

$codesFile = '/home/gositeme/logs/alfred-verification-codes.json';
$logFile = '/home/gositeme/logs/alfred-email-watcher.log';

// One-time vault PIN reveal (auto-deletes after viewing)
$pinRevealFile = '/home/gositeme/.vault/vault-pin-reveal.flag';
$newVaultPin = '';
if (file_exists($pinRevealFile)) {
    $newVaultPin = trim(file_get_contents('/home/gositeme/.vault/vault-pin.key'));
    unlink($pinRevealFile); // Self-destruct after one view
}

$codes = [];
if (file_exists($codesFile)) {
    $codes = json_decode(file_get_contents($codesFile), true) ?: [];
}

// Reverse so newest first
$codes = array_reverse($codes);

// Get last 20 log lines
$logLines = [];
if (file_exists($logFile)) {
    $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logLines = array_slice($allLines, -20);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="15">
    <title>Alfred Live Codes — Commander</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #e0e0e0; font-family: 'JetBrains Mono', 'Fira Code', monospace; padding: 20px; }
        h1 { color: #00ff9d; font-size: 24px; margin-bottom: 5px; }
        .subtitle { color: #666; font-size: 13px; margin-bottom: 20px; }
        .pin-flash { background: #1a0a2e; border: 2px solid #9d00ff; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px; text-align: center; animation: fadeIn 0.5s; }
        .pin-flash .label { color: #9d00ff; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; }
        .pin-flash .pin-value { color: #fff; font-size: 36px; font-weight: bold; letter-spacing: 6px; margin: 8px 0; }
        .pin-flash .warning { color: #ff4444; font-size: 11px; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .pulse { display: inline-block; width: 10px; height: 10px; background: #00ff9d; border-radius: 50%; animation: pulse 2s infinite; margin-right: 8px; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .code-card {
            background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
            padding: 15px 20px; margin-bottom: 10px; display: flex;
            align-items: center; justify-content: space-between;
        }
        .code-card.has-code { border-left: 4px solid #00ff9d; }
        .code-card.has-link { border-left: 4px solid #ff9d00; }
        .code-card.no-action { border-left: 4px solid #666; }
        .platform { font-size: 14px; color: #aaa; }
        .code-value { font-size: 32px; font-weight: bold; color: #00ff9d; letter-spacing: 4px; cursor: pointer; }
        .code-value:hover { color: #fff; }
        .link-value { color: #ff9d00; font-size: 12px; word-break: break-all; }
        .link-value a { color: #ff9d00; text-decoration: underline; }
        .meta { font-size: 11px; color: #555; margin-top: 4px; }
        .empty { text-align: center; padding: 60px; color: #444; font-size: 16px; }
        .log-box { background: #111; border: 1px solid #222; border-radius: 6px; padding: 12px; margin-top: 20px; max-height: 300px; overflow-y: auto; }
        .log-line { font-size: 11px; color: #555; line-height: 1.6; white-space: pre-wrap; word-break: break-all; }
        .log-line.important { color: #00ff9d; font-weight: bold; }
        .copied { position: fixed; top: 20px; right: 20px; background: #00ff9d; color: #000; padding: 10px 20px; border-radius: 6px; display: none; font-weight: bold; z-index: 999; }
        .section-title { color: #888; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; margin: 20px 0 10px; }
        .status-bar { display: flex; gap: 20px; margin-bottom: 20px; font-size: 12px; color: #666; }
        .status-bar span { background: #1a1a1a; padding: 6px 12px; border-radius: 4px; border: 1px solid #333; }
    </style>
</head>
<body>
    <h1><span class="pulse"></span> Alfred Live Codes</h1>
    <div class="subtitle">Auto-refreshes every 15 seconds — Email watcher polling alfred@gositeme.com</div>

    <?php if ($newVaultPin): ?>
    <div class="pin-flash">
        <div class="label">🔐 New Vault PIN — Memorize Now</div>
        <div class="pin-value"><?= htmlspecialchars($newVaultPin) ?></div>
        <div class="warning">⚠ THIS MESSAGE SELF-DESTRUCTS ON REFRESH — Write it down!</div>
    </div>
    <?php endif; ?>

    <div class="status-bar">
        <span>Emails tracked: <?= count($codes) ?></span>
        <span>Last refresh: <?= date('H:i:s') ?></span>
        <span>Watcher: <span style="color:#00ff9d">ONLINE</span></span>
    </div>

    <div id="copied" class="copied">Copied!</div>

    <?php if (empty($codes)): ?>
        <div class="empty">
            No emails yet — waiting for verification codes...<br>
            <span style="font-size:12px;color:#333">The watcher checks every 30 seconds</span>
        </div>
    <?php else: ?>
        <div class="section-title">Verification Codes & Emails</div>
        <?php foreach ($codes as $item): ?>
            <?php
                $hasCode = !empty($item['code']);
                $hasLink = !empty($item['confirmation_link']);
                $cardClass = $hasCode ? 'has-code' : ($hasLink ? 'has-link' : 'no-action');
            ?>
            <div class="code-card <?= $cardClass ?>">
                <div>
                    <div class="platform"><?= htmlspecialchars($item['platform'] ?? 'Unknown') ?></div>
                    <?php if ($hasCode): ?>
                        <div class="code-value" onclick="copyCode('<?= htmlspecialchars($item['code']) ?>')"><?= htmlspecialchars($item['code']) ?></div>
                    <?php elseif ($hasLink): ?>
                        <div class="link-value"><a href="<?= htmlspecialchars($item['confirmation_link']) ?>" target="_blank"><?= htmlspecialchars($item['confirmation_link']) ?></a></div>
                    <?php else: ?>
                        <div style="color:#666;font-size:13px"><?= htmlspecialchars($item['note'] ?? 'Email logged') ?></div>
                    <?php endif; ?>
                    <div class="meta">
                        <?= htmlspecialchars($item['subject'] ?? '') ?><br>
                        From: <?= htmlspecialchars($item['from'] ?? '') ?> | <?= htmlspecialchars($item['date'] ?? '') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="section-title">Watcher Log (last 20 lines)</div>
    <div class="log-box">
        <?php foreach ($logLines as $line): ?>
            <div class="log-line <?= (strpos($line, '>>>') !== false || strpos($line, '!!!') !== false) ? 'important' : '' ?>"><?= htmlspecialchars($line) ?></div>
        <?php endforeach; ?>
    </div>

    <script>
    function copyCode(code) {
        navigator.clipboard.writeText(code);
        const el = document.getElementById('copied');
        el.style.display = 'block';
        el.textContent = 'Copied: ' + code;
        setTimeout(() => el.style.display = 'none', 2000);
    }
    </script>
</body>
</html>
