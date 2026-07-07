<?php
/**
 * Alfred IDE — Desktop Pairing Confirmation Page
 *
 * URL: /alfred-ide-pair.php?code=XXXX
 * User logs in (if not already), confirms the code, and desktop gets a token.
 */
session_start();
require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/includes/alfred-ide-bearer.inc.php';

$db = getSharedDB();
$code = strtoupper(trim($_GET['code'] ?? ''));
$error = '';
$success = false;
$userId = 0;
$userName = '';

// Check if user is authenticated via cookie
$cookieToken = trim($_COOKIE['alfred_ide_token'] ?? '');
if ($cookieToken !== '') {
    $tokenHash = hash('sha256', $cookieToken);
    $user = alfred_ide_lookup_user_by_token_hash($db, $tokenHash);
    if ($user) {
        $userId = (int)$user['id'];
        $userName = $user['display_name'] ?: $user['google_name'] ?: $user['email'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId > 0) {
    $submittedCode = strtoupper(trim($_POST['code'] ?? ''));
    if ($submittedCode === '') {
        $error = 'Please enter the pairing code.';
    } else {
        // Call the approve API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'http://127.0.0.1/api/alfred-ide-pair.php?action=approve',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['code' => $submittedCode, 'user_id' => $userId]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($resp, true);
        if ($httpCode === 200 && ($result['paired'] ?? false)) {
            $success = true;
            $code = $submittedCode;
        } else {
            $error = $result['error'] ?? 'Pairing failed. Code may be expired or invalid.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred IDE — Pair Desktop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 2.5em;
            max-width: 440px;
            width: 90%;
            text-align: center;
        }
        h1 {
            color: #e2b340;
            font-size: 1.5em;
            margin-bottom: 0.5em;
        }
        .subtitle { color: #8b949e; margin-bottom: 1.5em; }
        .code-display {
            font-family: 'Fira Code', monospace;
            font-size: 2.5em;
            letter-spacing: 0.2em;
            color: #e2b340;
            background: #0d1117;
            padding: 0.4em 0.8em;
            border-radius: 8px;
            border: 2px solid #30363d;
            margin: 1em 0;
            display: inline-block;
        }
        input[type="text"] {
            font-family: 'Fira Code', monospace;
            font-size: 2em;
            letter-spacing: 0.2em;
            text-align: center;
            color: #e2b340;
            background: #0d1117;
            padding: 0.4em;
            border-radius: 8px;
            border: 2px solid #30363d;
            width: 100%;
            text-transform: uppercase;
            margin: 1em 0;
        }
        input[type="text"]:focus { outline: none; border-color: #e2b340; }
        .btn {
            display: inline-block;
            background: #e2b340;
            color: #0d1117;
            padding: 0.8em 2em;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin-top: 0.5em;
        }
        .btn:hover { background: #f0c95c; }
        .error { color: #f85149; margin: 1em 0; font-weight: 600; }
        .success {
            color: #3fb950;
            font-size: 1.2em;
            font-weight: 700;
            margin: 1em 0;
        }
        .success-icon { font-size: 3em; margin-bottom: 0.3em; }
        .user-badge {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 0.8em;
            margin: 1em 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8em;
        }
        .user-badge img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        .login-prompt { margin-top: 1em; }
        .login-prompt a { color: #e2b340; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <h1>Alfred IDE — Desktop Pairing</h1>

    <?php if ($success): ?>
        <div class="success-icon">&#10003;</div>
        <div class="success">Desktop paired successfully!</div>
        <p>Code <span class="code-display" style="font-size:1.2em;"><?= htmlspecialchars($code) ?></span> has been confirmed.</p>
        <p style="margin-top:1em;color:#8b949e;">You can close this page. Your desktop Alfred IDE is now connected.</p>

    <?php elseif ($userId <= 0): ?>
        <p class="subtitle">Sign in to pair your desktop with your GoSiteMe account.</p>
        <?php if ($code !== ''): ?>
            <p>Pairing code:</p>
            <div class="code-display"><?= htmlspecialchars($code) ?></div>
        <?php endif; ?>
        <div class="login-prompt">
            <a class="btn" href="/alfred-ide-auth.php?return=<?= urlencode('/alfred-ide-pair.php?code=' . urlencode($code)) ?>">
                Sign In to Pair
            </a>
        </div>

    <?php else: ?>
        <p class="subtitle">Confirm the code shown on your desktop to link it to your account.</p>

        <div class="user-badge">
            <span>Pairing as: <strong><?= htmlspecialchars($userName) ?></strong></span>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php if ($code !== ''): ?>
                <div class="code-display"><?= htmlspecialchars($code) ?></div>
                <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
            <?php else: ?>
                <p>Enter the 6-character code shown on your desktop:</p>
                <input type="text" name="code" maxlength="6" autocomplete="off" autofocus placeholder="ABC123">
            <?php endif; ?>
            <button type="submit" class="btn">Confirm Pairing</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
