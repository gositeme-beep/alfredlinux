<?php
session_start();

// Generate CSRF token
if (empty($_SESSION['alfred_csrf'])) {
    $_SESSION['alfred_csrf'] = bin2hex(random_bytes(32));
}

// Already logged in? Redirect
if (!empty($_SESSION['client_id'])) {
    $return = $_GET['return'] ?? '/veil/';
    // Only allow relative URLs
    if (strpos($return, '/') !== 0 || strpos($return, '//') === 0) {
        $return = '/veil/';
    }
    header('Location: ' . $return);
    exit;
}

$return = htmlspecialchars($_GET['return'] ?? '/veil/', ENT_QUOTES, 'UTF-8');
$error = '';

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    // Forward to auth API internally
    $ch = curl_init('https://gositeme.com/api/auth.php?action=login');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'email' => $email,
            'password' => $password,
            'csrf_token' => $csrf,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIE => 'PHPSESSID=' . session_id(),
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode === 200 && !empty($data['success'])) {
        // Refresh session
        session_regenerate_id(true);
        $redirectTo = html_entity_decode($return);
        // Only allow relative URLs (prevent open redirect)
        if (strpos($redirectTo, '/') !== 0 || strpos($redirectTo, '//') === 0) {
            $redirectTo = '/veil/';
        }
        header('Location: ' . $redirectTo);
        exit;
    } else {
        $error = $data['error'] ?? 'Login failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — GoSiteMe</title>
<link rel="icon" href="/brand/favicon.ico">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root { --bg:#0a0a12; --surface:rgba(15,20,40,0.95); --accent:#00d4ff; --gold:#ffd700; --text:#e8ecf4; --muted:#6b7394; --danger:#ff4444; }
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--bg); color:var(--text); font-family:'Segoe UI',system-ui,sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.login-card { background:var(--surface); border:1px solid rgba(255,255,255,0.06); border-radius:20px; padding:48px 40px; width:100%; max-width:420px; backdrop-filter:blur(20px); }
.logo { text-align:center; margin-bottom:32px; }
.logo img { height:40px; }
.logo-text { font-size:1.1rem; font-weight:700; color:var(--gold); letter-spacing:2px; margin-top:8px; }
h1 { font-size:1.3rem; text-align:center; margin-bottom:8px; }
.subtitle { text-align:center; color:var(--muted); font-size:0.8rem; margin-bottom:28px; }
.field { margin-bottom:20px; }
.field label { display:block; font-size:0.75rem; color:var(--muted); margin-bottom:6px; letter-spacing:1px; text-transform:uppercase; }
.field input { width:100%; padding:14px 16px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:var(--text); font-size:0.9rem; outline:none; transition:border-color 0.2s; }
.field input:focus { border-color:var(--accent); }
.btn-login { width:100%; padding:14px; background:linear-gradient(135deg,var(--accent),#7c3aed); color:white; border:none; border-radius:10px; font-size:0.9rem; font-weight:600; cursor:pointer; transition:transform 0.2s,box-shadow 0.2s; }
.btn-login:hover { transform:translateY(-2px); box-shadow:0 8px 30px rgba(0,212,255,0.3); }
.error { background:rgba(255,68,68,0.1); border:1px solid rgba(255,68,68,0.2); color:var(--danger); padding:12px 16px; border-radius:10px; font-size:0.8rem; margin-bottom:20px; text-align:center; }
.links { text-align:center; margin-top:24px; font-size:0.8rem; }
.links a { color:var(--accent); text-decoration:none; }
.links a:hover { text-decoration:underline; }
.divider { display:flex; align-items:center; gap:12px; margin:24px 0; color:var(--muted); font-size:0.75rem; }
.divider::before,.divider::after { content:''; flex:1; height:1px; background:rgba(255,255,255,0.08); }
.social-btns { display:flex; gap:12px; }
.social-btns a { flex:1; display:flex; align-items:center; justify-content:center; gap:8px; padding:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; color:var(--text); text-decoration:none; font-size:0.8rem; transition:border-color 0.2s; }
.social-btns a:hover { border-color:var(--accent); }
</style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>
<div class="login-card">
    <div class="logo">
        <div class="logo-text">GOSITEME</div>
    </div>
    <h1>Welcome Back</h1>
    <div class="subtitle">Sign in to your encrypted workspace</div>

    <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login.php?return=<?= urlencode(html_entity_decode($return)) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['alfred_csrf']) ?>">
        <div class="field">
            <label>Email</label>
            <input type="email" name="email" required autocomplete="email" autofocus placeholder="you@example.com">
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        <button type="submit" class="btn-login"><i class="fas fa-shield-alt"></i> Sign In</button>
    </form>

    <div class="divider">or continue with</div>
    <div class="social-btns">
        <a href="/api/auth.php?action=google-login"><i class="fab fa-google"></i> Google</a>
        <a href="/api/auth.php?action=github-login"><i class="fab fa-github"></i> GitHub</a>
    </div>

    <div class="links">
        <a href="/api/auth.php?action=forgot">Forgot password?</a>
        &nbsp;·&nbsp;
        <a href="/?register=1">Create account</a>
    </div>
    <div class="links" style="margin-top:8px;font-size:11px;opacity:0.6">
        <a href="/privacy-policy/">Privacy Policy</a>
        &nbsp;·&nbsp;
        <a href="/terms-of-service.php">Terms of Service</a>
    </div>
</div>
</body>
</html>
