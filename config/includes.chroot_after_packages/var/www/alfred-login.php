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

require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/includes/alfred-oauth-uri.inc.php';

// ── Input validation ─────────────────────────────────────────────────────────
$state        = $_GET['state'] ?? '';
$port         = (int)($_GET['port'] ?? 35721);
$callbackUri  = trim((string)($_GET['callback_uri'] ?? ''));

if (!preg_match('/^[a-f0-9]{40,64}$/', $state)) {
    http_response_code(400);
    die(renderError('Invalid request. Please try signing in again from Alfred IDE.'));
}

if ($port < 1024 || $port > 65535) {
    $port = 35721;
}

/*
 * Preferred: the IDE passes a full callback_uri resolved via vscode.env.asExternalUri.
 *   Native VS Code  → http://127.0.0.1:PORT/callback
 *   Remote-SSH/web  → https://<auto-tunnel>.github.dev/callback (or similar)
 *   code-server     → https://<port>.root.com/  (proxy-domain form — DNS does NOT resolve;
 *                                                    we rewrite to same-origin path-proxy)
 * Legacy fallback:   port=XXXXX → resolved to same-origin path-proxy
 *
 * Security hardening:
 *   - Strict scheme allowlist (http/https only)
 *   - Strict host allowlist (loopback, known tunnel providers, root.com)
 *   - Same-origin code-server callbacks normalized to /alfred-ide/proxy/<port>/callback
 *   - state bound to PHP session to prevent CSRF replay across browsers/users
 *   - state TTL 10 min; single-use (cleared on consume)
 *   - port range validated (1024–65535)
 *   - callback_uri length capped (2000 chars), no userinfo, no fragments preserved
 */
// Resolve callback via shared OAuth normalizer (also used by token API).
$callbackBase = $callbackUri !== ''
    ? alfredNormalizeRedirectUri($callbackUri, $port)
    : null;
if ($callbackBase === null) {
    // No (or invalid) callback_uri → assume code-server / web IDE on this origin.
    $callbackBase = 'https://root.com/alfred-ide/proxy/' . $port . '/callback';
}

// ── CSRF: bind state to this PHP session so a stolen state value cannot be
//        replayed in a different browser. Stored at most 10 min; single-use.
$_SESSION['alfred_login_state'] = [
    'state'        => $state,
    'callback'     => $callbackBase,
    'port'         => $port,
    'issued_at'    => time(),
    'expires_at'   => time() + 600,
];

// ── If not logged in, redirect to login first ────────────────────────────────
if (empty($_SESSION['client_id'])) {
    $returnUrl = '/alfred-login?' . http_build_query(['state' => $state, 'port' => $port]);
    header('Location: /login.php?return=' . urlencode($returnUrl));
    exit;
}

// ── User is logged in — generate auth code ───────────────────────────────────
require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/includes/alfred-oauth-uri.inc.php';

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
<meta name="referrer" content="no-referrer">
<meta http-equiv="refresh" content="2;url=<?= htmlspecialchars($callbackUrl, ENT_QUOTES, 'UTF-8') ?>">
<title>Alfred IDE — Signed in</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --gold:        #e2b340;
    --gold-soft:   #f6d27a;
    --bg:          #07090f;
    --bg-2:        #0d1422;
    --panel:       rgba(22, 27, 34, 0.72);
    --panel-edge:  rgba(226, 179, 64, 0.28);
    --green:       #56d364;
    --muted:       #8b949e;
    --dim:         #6e7681;
  }
  html, body { height: 100%; }
  body {
    color: #e8edf3;
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    -webkit-font-smoothing: antialiased;
    background:
      radial-gradient(1200px 700px at 80% -10%, rgba(226,179,64,0.18), transparent 60%),
      radial-gradient(900px 600px at -10% 110%, rgba(80,140,255,0.14), transparent 60%),
      linear-gradient(180deg, var(--bg) 0%, var(--bg-2) 100%);
    overflow: hidden;
    display: grid;
    place-items: center;
    min-height: 100vh;
  }
  /* animated starfield */
  .stars { position: fixed; inset: 0; pointer-events: none; opacity: .55; }
  .stars i {
    position: absolute;
    width: 2px; height: 2px;
    background: #fff; border-radius: 50%;
    box-shadow: 0 0 6px rgba(255,255,255,0.6);
    animation: twinkle 4s ease-in-out infinite;
  }
  @keyframes twinkle { 0%,100%{ opacity: .25 } 50%{ opacity: 1 } }

  .card {
    position: relative;
    width: min(92vw, 460px);
    padding: 44px 38px 34px;
    background: var(--panel);
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    border: 1px solid var(--panel-edge);
    border-radius: 22px;
    text-align: center;
    box-shadow:
      0 30px 80px rgba(0,0,0,0.55),
      0 0 80px rgba(226,179,64,0.10),
      inset 0 1px 0 rgba(255,255,255,0.05);
    animation: rise .55s cubic-bezier(.2,.8,.2,1) both;
  }
  @keyframes rise { from { opacity: 0; transform: translateY(14px) scale(.98) } to { opacity: 1; transform: none } }

  /* gold sweep border glow */
  .card::before {
    content: "";
    position: absolute; inset: -1px;
    border-radius: 23px;
    padding: 1px;
    background: conic-gradient(from 0deg, transparent 0 70%, var(--gold) 80%, transparent 95%);
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    animation: sweep 4s linear infinite;
    opacity: .55;
    pointer-events: none;
  }
  @keyframes sweep { to { transform: rotate(360deg) } }

  .crest {
    position: relative;
    width: 92px; height: 92px;
    margin: 0 auto 18px;
    border-radius: 50%;
    display: grid; place-items: center;
    background: radial-gradient(circle at 30% 30%, #1d2536, #0a0f1a);
    box-shadow:
      0 0 0 2px rgba(226,179,64,0.55),
      0 0 30px rgba(226,179,64,0.35),
      inset 0 0 20px rgba(0,0,0,0.5);
  }
  .crest img { width: 78px; height: 78px; border-radius: 50%; object-fit: cover; }
  .crest .ring {
    position: absolute; inset: -10px;
    border-radius: 50%;
    border: 1px dashed rgba(226,179,64,0.35);
    animation: spin 14s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg) } }

  h1 {
    font-size: 1.55rem;
    letter-spacing: .3px;
    background: linear-gradient(180deg, var(--gold-soft), var(--gold));
    -webkit-background-clip: text; background-clip: text;
    color: transparent;
    margin-bottom: 4px;
  }
  .sub { color: var(--muted); font-size: .9rem; margin-bottom: 22px; }

  .badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(180deg, rgba(46,160,67,0.18), rgba(46,160,67,0.08));
    border: 1px solid rgba(86,211,100,0.45);
    border-radius: 999px;
    padding: 10px 18px;
    color: var(--green);
    font-size: .92rem;
    font-weight: 500;
    margin-bottom: 22px;
    animation: pop .5s .15s cubic-bezier(.2,.9,.3,1.4) both;
  }
  @keyframes pop { from { transform: scale(.85); opacity: 0 } to { transform: scale(1); opacity: 1 } }
  .check {
    width: 18px; height: 18px;
    display: inline-grid; place-items: center;
    background: var(--green);
    color: #0b160d;
    border-radius: 50%;
    font-weight: 800;
    font-size: 12px;
  }

  .progress {
    height: 4px;
    width: 100%;
    background: rgba(255,255,255,0.06);
    border-radius: 999px;
    overflow: hidden;
    margin: 6px 0 14px;
  }
  .progress > span {
    display: block; height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--gold-soft), var(--gold));
    box-shadow: 0 0 14px rgba(226,179,64,0.55);
    animation: fill 2s linear forwards;
  }
  @keyframes fill { to { width: 100% } }

  .note { color: var(--dim); font-size: .82rem; line-height: 1.55; }
  .note strong { color: #c9d1d9; font-weight: 500; }
  .actions {
    margin-top: 18px;
    display: flex; gap: 10px; justify-content: center;
  }
  .btn {
    appearance: none; border: 1px solid rgba(226,179,64,0.45);
    background: linear-gradient(180deg, rgba(226,179,64,0.16), rgba(226,179,64,0.05));
    color: var(--gold-soft);
    font: inherit; font-size: .85rem;
    padding: 8px 16px; border-radius: 10px;
    cursor: pointer; text-decoration: none;
    transition: transform .12s ease, background .2s ease;
  }
  .btn:hover { transform: translateY(-1px); background: linear-gradient(180deg, rgba(226,179,64,0.28), rgba(226,179,64,0.10)); }
  .btn.ghost { color: var(--muted); border-color: rgba(255,255,255,0.12); background: transparent; }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; }
  }
</style>
</head>
<body>
  <div class="stars" aria-hidden="true">
    <?php for ($i = 0; $i < 40; $i++): ?>
      <i style="top:<?= rand(0,100) ?>%;left:<?= rand(0,100) ?>%;animation-delay:<?= rand(0,4000)/1000 ?>s"></i>
    <?php endfor; ?>
  </div>

  <main class="card" role="status" aria-live="polite">
    <div class="crest">
      <span class="ring" aria-hidden="true"></span>
      <img src="/assets/images/alfred-portrait.png" alt="" onerror="this.style.display='none'">
    </div>

    <h1>Welcome back</h1>
    <p class="sub">Alfred IDE · GoSiteMe Account</p>

    <div class="badge">
      <span class="check">✓</span>
      Signed in as <?= $userName ?: htmlspecialchars($user['email'] ?? 'user', ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div class="progress" aria-hidden="true"><span></span></div>

    <p class="note">
      Handing the secure session back to <strong>Alfred IDE</strong>…<br>
      This window will close automatically.
    </p>

    <div class="actions">
      <a class="btn" id="continueNow" href="<?= htmlspecialchars($callbackUrl, ENT_QUOTES, 'UTF-8') ?>">Continue now</a>
      <button class="btn ghost" type="button" onclick="window.close()">Close window</button>
    </div>
  </main>

<script>
(function () {
  var url = <?= json_encode($callbackUrl, JSON_UNESCAPED_SLASHES) ?>;
  // Fast path: navigate as soon as the splash has rendered (~600ms).
  setTimeout(function () { try { location.replace(url); } catch (e) { location.href = url; } }, 600);
  // After redirect lands, attempt to auto-close (works only if the IDE opened
  // this as a popup — VS Code OAuth flows usually do).
  setTimeout(function () { try { window.close(); } catch (e) {} }, 2400);
})();
</script>
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
