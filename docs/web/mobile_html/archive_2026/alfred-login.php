<?php
/**
 * Alfred IDE — GoSiteMe Login Bridge
 *
 * When Alfred IDE user clicks "Sign in to GoSiteMe":
 *   IDE opens this page in their browser with ?state=xxx&port=35721
 *   If not logged in → redirected to normal login → returns here
 *   If logged in → generates auth code → redirects to http://127.0.0.1:PORT/callback
 */
session_start();

// ── Input validation ─────────────────────────────────────────────────────────
$state = $_GET['state'] ?? '';
$port  = (int)($_GET['port'] ?? 35721);

if (!preg_match('/^[a-f0-9]{40,64}$/', $state)) {
    http_response_code(400);
    die(renderError('Invalid request. Please try signing in again from Alfred IDE.'));
}

if ($port < 1024 || $port > 65535) {
    $port = 35721;
}

$callbackBase = 'http://127.0.0.1:' . $port . '/callback';

// ── If not logged in, redirect to login first ────────────────────────────────
if (empty($_SESSION['client_id'])) {
    $returnUrl = '/alfred-login?' . http_build_query(['state' => $state, 'port' => $port]);
    header('Location: /login.php?return=' . urlencode($returnUrl));
    exit;
}

// ── User is logged in — generate auth code ───────────────────────────────────
require_once __DIR__ . '/includes/db-config.inc.php';

$db     = getSharedDB();
$userId = (int)$_SESSION['client_id'];

// Get alfred-ide app
$stmt = $db->prepare("SELECT id FROM alfred_oauth_apps WHERE client_id = 'alfred-ide-builtin' AND is_approved = 1 LIMIT 1");
$stmt->execute();
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die(renderError('Alfred IDE integration is not configured on this server.'));
}

// Expire any unused codes for this user+app
$db->prepare("DELETE FROM alfred_oauth_codes WHERE app_id = ? AND user_id = ? AND used_at IS NULL")
   ->execute([$app['id'], $userId]);

// Generate a fresh auth code
$code      = bin2hex(random_bytes(32));
$scopes    = json_encode(['profile:read', 'billing:read']);
$expiresAt = date('Y-m-d H:i:s', time() + 600);

$db->prepare("
    INSERT INTO alfred_oauth_codes (app_id, user_id, code, redirect_uri, scopes, expires_at)
    VALUES (?, ?, ?, ?, ?, ?)
")->execute([$app['id'], $userId, $code, $callbackBase, $scopes, $expiresAt]);

// Get user name for the confirmation page
$stmt = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user     = $stmt->fetch(PDO::FETCH_ASSOC);
$userName = htmlspecialchars(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')), ENT_QUOTES, 'UTF-8');

// ── Show "connecting..." page then redirect ──────────────────────────────────
// We use a tiny meta-refresh so the user sees a success screen
$callbackUrl = $callbackBase . '?' . http_build_query(['code' => $code, 'state' => $state]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="1;url=<?= htmlspecialchars($callbackUrl, ENT_QUOTES, 'UTF-8') ?>">
<title>Alfred IDE — Connecting to GoSiteMe</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: #0d1117;
    color: #e0e0e0;
    font-family: 'Segoe UI', system-ui, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
  }
  .card {
    text-align: center;
    padding: 48px 40px;
    background: #161b22;
    border: 1px solid #2a2a4a;
    border-radius: 16px;
    max-width: 420px;
    width: 90%;
    box-shadow: 0 0 40px rgba(226,179,64,0.08);
  }
  .logo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid #e2b340;
    margin: 0 auto 24px;
    display: block;
    object-fit: cover;
  }
  h1 { color: #e2b340; font-size: 1.6rem; margin-bottom: 8px; }
  .sub { color: #8b949e; font-size: 0.9rem; margin-bottom: 28px; }
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #1a2a1a;
    border: 1px solid #2ea043;
    border-radius: 8px;
    padding: 12px 20px;
    color: #56d364;
    font-size: 0.95rem;
    margin-bottom: 24px;
  }
  .badge::before { content: "✓"; font-size: 1.1rem; }
  .note { color: #6e7681; font-size: 0.8rem; }
  .spinner {
    width: 20px; height: 20px;
    border: 2px solid #2a2a4a;
    border-top-color: #e2b340;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>
  <div class="card">
    <img src="/assets/images/alfred-portrait.png" alt="Alfred" class="logo" onerror="this.style.display='none'">
    <h1>Alfred IDE</h1>
    <p class="sub">GoSiteMe Account</p>
    <div class="badge">
      Authenticated as <?= $userName ?: htmlspecialchars($user['email'] ?? 'user', ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="spinner"></div>
    <p class="note">Connecting to Alfred IDE…<br>You can close this window once the IDE confirms.</p>
  </div>
</body>
</html>
<?php
// ── Helpers ──────────────────────────────────────────────────────────────────
function renderError(string $msg): string {
    return '<!DOCTYPE html><html><head><title>Alfred IDE Error</title>
<style>body{background:#0d1117;color:#e2b340;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{text-align:center;padding:40px;background:#161b22;border:1px solid #e2b340;border-radius:12px;max-width:400px}
h2{margin-bottom:12px}p{color:#8b949e;font-size:.9rem}</style></head>
<body><div class="box"><h2>⚠ Connection Error</h2><p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
}
