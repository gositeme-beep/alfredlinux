<?php
session_start();

// ── Handle logout ──────────────────────────────────────────────────
if (isset($_GET['logout']) || isset($_GET['emergency'])) {
    header('Location: ' . (isset($_GET['emergency']) ? '/logout.php?emergency=1' : '/logout.php'));
    exit;
}

// Generate CSRF token
if (empty($_SESSION['alfred_csrf'])) {
    $_SESSION['alfred_csrf'] = bin2hex(random_bytes(32));
}

// Already logged in? Redirect
if (!empty($_SESSION['client_id'])) {
    $return = $_GET['return'] ?? '/dashboard.php';
    // Only allow relative URLs
    if (strpos($return, '/') !== 0 || strpos($return, '//') === 0) {
        $return = '/dashboard.php';
    }
    header('Location: ' . $return);
    exit;
}

$return = htmlspecialchars($_GET['return'] ?? '/dashboard.php', ENT_QUOTES, 'UTF-8');
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
            $redirectTo = '/dashboard.php';
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
<title>Sign In — GoSiteMe</title>
<link rel="icon" href="/brand/favicon.ico">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--bg:#09090f;--surface:rgba(17,17,30,.97);--accent:#7c3aed;--accent2:#06b6d4;--text:#e8ecf4;--muted:rgba(255,255,255,.35);--danger:#ef4444;--success:#10b981}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,-apple-system,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden}

/* Ambient glow */
.bg-glow{position:fixed;inset:0;pointer-events:none;z-index:0}
.bg-glow::before,.bg-glow::after{content:'';position:absolute;border-radius:50%;filter:blur(140px);opacity:.06}
.bg-glow::before{width:500px;height:500px;background:var(--accent);top:-120px;right:-80px;animation:drift 20s ease-in-out infinite}
.bg-glow::after{width:400px;height:400px;background:var(--accent2);bottom:-100px;left:-80px;animation:drift 25s ease-in-out infinite reverse}
@keyframes drift{0%,100%{transform:translate(0,0)}50%{transform:translate(40px,30px)}}

/* Card */
.login-wrap{position:relative;z-index:1;width:100%;max-width:420px;padding:0 1rem}
.login-card{background:var(--surface);border:1px solid rgba(255,255,255,.06);border-radius:24px;padding:3rem 2.5rem;backdrop-filter:blur(20px);box-shadow:0 25px 60px rgba(0,0,0,.5)}

/* Logo */
.logo{text-align:center;margin-bottom:2rem}
.logo img{height:36px;border-radius:8px;margin-bottom:.5rem}
.logo-text{font-size:.7rem;font-weight:700;color:var(--muted);letter-spacing:3px;text-transform:uppercase}

h1{font-size:1.5rem;text-align:center;font-weight:800;margin-bottom:.35rem}
.subtitle{text-align:center;color:var(--muted);font-size:.8rem;margin-bottom:2rem}

/* Alerts */
.alert{padding:.75rem 1rem;border-radius:10px;font-size:.8rem;margin-bottom:1.25rem;text-align:center;display:flex;align-items:center;justify-content:center;gap:.5rem}
.alert-error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.15);color:var(--danger)}
.alert-success{background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.15);color:var(--success)}

/* Fields */
.field{margin-bottom:1.1rem}
.field label{display:block;font-size:.7rem;color:var(--muted);margin-bottom:.35rem;letter-spacing:1px;text-transform:uppercase;font-weight:600}
.field .input-wrap{position:relative}
.field input{width:100%;padding:.85rem 1rem .85rem 2.5rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;color:var(--text);font-size:.9rem;outline:none;transition:.2s}
.field input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.field .input-icon{position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem}
.field .toggle-pw{position:absolute;right:.85rem;top:50%;transform:translateY(-50%);color:var(--muted);cursor:pointer;font-size:.85rem;background:none;border:none;padding:4px}
.field .toggle-pw:hover{color:var(--text)}

/* Remember row */
.remember-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;font-size:.78rem}
.remember-row label{display:flex;align-items:center;gap:.4rem;color:var(--muted);cursor:pointer}
.remember-row input[type=checkbox]{accent-color:var(--accent);width:14px;height:14px}
.remember-row a{color:var(--accent);text-decoration:none;font-weight:500}
.remember-row a:hover{text-decoration:underline}

/* Button */
.btn-login{width:100%;padding:.9rem;background:linear-gradient(135deg,var(--accent),#a855f7);color:white;border:none;border-radius:12px;font-size:.9rem;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:.5rem;letter-spacing:.3px}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(124,58,237,.35)}
.btn-login:active{transform:translateY(0)}

/* Divider */
.divider{display:flex;align-items:center;gap:.75rem;margin:1.5rem 0;color:var(--muted);font-size:.7rem;text-transform:uppercase;letter-spacing:1px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.06)}

/* Social */
.social-btns{display:flex;gap:.6rem}
.social-btns a{flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.7rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;color:var(--text);text-decoration:none;font-size:.8rem;font-weight:500;transition:.2s}
.social-btns a:hover{border-color:var(--accent);background:rgba(124,58,237,.05)}

/* Footer links */
.card-footer{text-align:center;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid rgba(255,255,255,.04)}
.card-footer .signup{font-size:.82rem;color:var(--muted);margin-bottom:.6rem}
.card-footer .signup a{color:var(--accent);text-decoration:none;font-weight:600}
.card-footer .signup a:hover{text-decoration:underline}
.card-footer .legal{font-size:.65rem;color:rgba(255,255,255,.2)}
.card-footer .legal a{color:rgba(255,255,255,.25);text-decoration:none}
.card-footer .legal a:hover{color:var(--accent)}

/* Loading state */
.btn-login.loading{opacity:.7;pointer-events:none}
.btn-login.loading .btn-text{display:none}
.btn-login .btn-spinner{display:none}
.btn-login.loading .btn-spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:480px){
    .login-card{padding:2rem 1.5rem;border-radius:18px}
    h1{font-size:1.25rem}
    .social-btns{flex-direction:column}
}
</style>
</head>
<body>
<div class="bg-glow"></div>
<div class="login-wrap">
<div class="login-card">
    <div class="logo">
        <img src="/logo_small.png" alt="GoSiteMe">
        <div class="logo-text">GoSiteMe</div>
    </div>
    <h1>Welcome Back</h1>
    <div class="subtitle">Sign in to your encrypted workspace</div>

    <?php if (!empty($_GET['logged_out'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> You've been signed out successfully.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login.php?return=<?= urlencode(html_entity_decode($return)) ?>" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['alfred_csrf']) ?>">
        <div class="field">
            <label>Email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" required autocomplete="email" autofocus placeholder="you@example.com">
            </div>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="pwField" required autocomplete="current-password" placeholder="••••••••">
                <button type="button" class="toggle-pw" onclick="togglePw()" aria-label="Toggle password visibility"><i class="fas fa-eye" id="pwEye"></i></button>
            </div>
        </div>
        <div class="remember-row">
            <label><input type="checkbox" name="remember" value="1"> Remember me</label>
            <a href="/api/auth.php?action=forgot">Forgot password?</a>
        </div>
        <button type="submit" class="btn-login" id="loginBtn">
            <i class="fas fa-shield-alt"></i>
            <span class="btn-text">Sign In</span>
            <span class="btn-spinner"></span>
        </button>
    </form>

    <div class="divider">or continue with</div>
    <div class="social-btns">
        <a href="/api/auth.php?action=google-login"><i class="fab fa-google"></i> Google</a>
        <a href="/api/auth.php?action=github-login"><i class="fab fa-github"></i> GitHub</a>
    </div>

    <div class="card-footer">
        <div class="signup">Don't have an account? <a href="/register">Create one</a></div>
        <div class="legal">
            <a href="/privacy-policy/">Privacy</a> · <a href="/terms-of-service.php">Terms</a>
        </div>
    </div>
</div>
</div>

<script>
function togglePw(){
    const f=document.getElementById('pwField'),e=document.getElementById('pwEye');
    const show=f.type==='password';
    f.type=show?'text':'password';
    e.className=show?'fas fa-eye-slash':'fas fa-eye';
}
document.getElementById('loginForm').addEventListener('submit',function(){
    document.getElementById('loginBtn').classList.add('loading');
});
</script>
</body>
</html>
