<?php
/**
 * GoSiteMe — Sign In (restored from Wayback snapshot 2026-03-23)
 * Two-panel green-G design, posts to /api/auth.php (which already handles 2FA).
 */
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();

if (empty($_SESSION['alfred_csrf'])) {
    $_SESSION['alfred_csrf'] = bin2hex(random_bytes(32));
}

// Already logged in? Bounce to dashboard (or requested return)
if (!empty($_SESSION['client_id']) && !empty($_SESSION['logged_in'])) {
    $r = $_GET['redirect'] ?? $_GET['return'] ?? '/dashboard.php';
    if (strpos($r, '/') !== 0 || strpos($r, '//') === 0) { $r = '/dashboard.php'; }
    header('Location: ' . $r);
    exit;
}

$redirectRaw = $_GET['redirect'] ?? $_GET['return'] ?? '/dashboard.php';
// Sanitize redirect: must be relative and not //
if (strpos($redirectRaw, '/') !== 0 || strpos($redirectRaw, '//') === 0) {
    $redirectRaw = '/dashboard.php';
}
$redirectTarget = $redirectRaw;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — GoSiteMe</title>
    <meta name="description" content="Sign in to your GoSiteMe account to manage hosting, domains, and services.">
    <link rel="icon" type="image/png" href="/brand/favicon.png" sizes="32x32">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --bg:         #06060e;
        --bg-card:    #0e0e1f;
        --bg-input:   #0a0a18;
        --border:     rgba(255,255,255,0.06);
        --border-focus: rgba(0,168,255,0.5);
        --text:       #e8e8f0;
        --text-dim:   #8888aa;
        --text-muted: #5a5a7a;
        --accent:     #00a8ff;
        --accent-2:   #7c3aed;
        --green:      #22c55e;
        --red:        #ef4444;
        --radius:     12px;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        -webkit-font-smoothing: antialiased;
        overflow: hidden;
    }

    /* ── Left Panel — Branding ───────────────────────── */
    .login-brand {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #06060e 0%, #0c0c22 50%, #120e28 100%);
    }

    .login-brand::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 600px 600px at 20% 50%, rgba(0,168,255,0.08), transparent),
            radial-gradient(ellipse 400px 400px at 80% 20%, rgba(124,58,237,0.06), transparent),
            radial-gradient(ellipse 300px 300px at 60% 80%, rgba(0,168,255,0.04), transparent);
        pointer-events: none;
    }

    /* Animated grid */
    .login-brand::after {
        content: '';
        position: absolute;
        inset: -50%;
        background-image:
            linear-gradient(rgba(0,168,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,168,255,0.03) 1px, transparent 1px);
        background-size: 60px 60px;
        transform: perspective(500px) rotateX(60deg);
        animation: gridFloat 20s linear infinite;
        pointer-events: none;
    }

    @keyframes gridFloat {
        0% { transform: perspective(500px) rotateX(60deg) translateY(0); }
        100% { transform: perspective(500px) rotateX(60deg) translateY(60px); }
    }

    .brand-content {
        position: relative;
        z-index: 1;
        max-width: 520px;
    }

    .brand-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 48px;
        text-decoration: none;
    }

    .brand-logo img {
        height: 36px;
        opacity: 0.9;
    }

    .brand-logo span {
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 700;
        font-size: 1.4rem;
        color: var(--accent);
        letter-spacing: -0.5px;
    }

    .brand-content h1 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 42px;
        font-weight: 700;
        line-height: 1.15;
        margin-bottom: 16px;
        letter-spacing: -1px;
        background: linear-gradient(135deg, #fff 0%, #c8c8e0 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .brand-content > p {
        font-size: 17px;
        color: var(--text-dim);
        line-height: 1.6;
        margin-bottom: 48px;
    }

    .brand-features {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .brand-feature {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .feature-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
    }

    .feature-icon.blue   { background: rgba(0,168,255,0.12); color: var(--accent); }
    .feature-icon.purple { background: rgba(124,58,237,0.12); color: var(--accent-2); }
    .feature-icon.green  { background: rgba(34,197,94,0.12); color: var(--green); }

    .feature-text h3 {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 3px;
    }

    .feature-text p {
        font-size: 13.5px;
        color: var(--text-dim);
        line-height: 1.5;
    }

    .brand-trust {
        display: flex;
        align-items: center;
        gap: 24px;
        margin-top: 56px;
        padding-top: 32px;
        border-top: 1px solid var(--border);
    }

    .trust-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--text-muted);
    }

    .trust-item svg {
        width: 16px;
        height: 16px;
        color: var(--green);
    }

    /* ── Right Panel — Login Form ────────────────────── */
    .login-form-panel {
        width: 520px;
        min-width: 520px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px 60px;
        position: relative;
        border-left: 1px solid var(--border);
        background: linear-gradient(180deg, #0a0a18 0%, #08081a 100%);
    }

    .login-form-wrap {
        width: 100%;
        max-width: 380px;
    }

    .login-mobile-logo {
        display: none;
        text-align: center;
        margin-bottom: 32px;
    }

    .login-mobile-logo a {
        text-decoration: none;
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 700;
        font-size: 1.4rem;
        color: var(--accent);
    }

    .login-heading {
        margin-bottom: 32px;
    }

    .login-heading h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .login-heading p {
        font-size: 15px;
        color: var(--text-dim);
    }

    /* Social Buttons */
    .social-login {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 24px;
    }

    .btn-social {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 11px 16px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        font-family: inherit;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border);
    }

    .btn-social svg { width: 20px; height: 20px; flex-shrink: 0; }

    .btn-google {
        background: #fff;
        color: #3c4043;
    }
    .btn-google:hover { background: #f8f8f8; box-shadow: 0 2px 12px rgba(0,0,0,0.15); }

    .btn-github {
        background: #161b22;
        color: #e6edf3;
        border-color: rgba(255,255,255,0.1);
    }
    .btn-github:hover { background: #1e242c; border-color: rgba(255,255,255,0.2); }

    /* Divider */
    .login-divider {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        color: var(--text-muted);
        font-size: 13px;
    }

    .login-divider::before,
    .login-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    /* Alerts */
    .login-alert {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13.5px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .login-alert.error {
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.2);
        color: #f87171;
    }

    .login-alert.success {
        background: rgba(34,197,94,0.1);
        border: 1px solid rgba(34,197,94,0.2);
        color: #4ade80;
    }

    /* Form */
    .form-field {
        margin-bottom: 20px;
    }

    .form-field label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-dim);
        margin-bottom: 7px;
    }

    .form-field label a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 400;
        font-size: 12.5px;
    }

    .form-field label a:hover { text-decoration: underline; }

    .input-wrap {
        position: relative;
    }

    .input-wrap input {
        width: 100%;
        padding: 12px 16px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        border-radius: 10px;
        color: var(--text);
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
    }

    .input-wrap input:focus {
        border-color: var(--border-focus);
        box-shadow: 0 0 0 3px rgba(0,168,255,0.1);
    }

    .input-wrap input::placeholder { color: var(--text-muted); }

    .input-wrap .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
        font-size: 15px;
    }

    .input-wrap.has-icon input { padding-left: 42px; }

    .pw-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
    }

    .pw-toggle:hover { color: var(--text); }

    .form-remember {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        font-size: 13.5px;
        color: var(--text-dim);
        cursor: pointer;
    }

    .form-remember input {
        accent-color: var(--accent);
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    /* Submit Button */
    .btn-login {
        width: 100%;
        padding: 13px 24px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        color: #fff;
        background: linear-gradient(135deg, var(--accent) 0%, #0070cc 100%);
        transition: all 0.25s;
        position: relative;
        overflow: hidden;
    }

    .btn-login:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 24px rgba(0,168,255,0.3);
    }

    .btn-login:active { transform: translateY(0); }

    .btn-login:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    .btn-login .spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        margin: 0 auto;
    }

    .btn-login.loading .btn-label { display: none; }
    .btn-login.loading .spinner { display: block; }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* Footer links */
    .login-footer {
        text-align: center;
        margin-top: 28px;
        font-size: 14px;
        color: var(--text-dim);
    }

    .login-footer a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 500;
    }

    .login-footer a:hover { text-decoration: underline; }

    .login-terms {
        text-align: center;
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 24px;
        line-height: 1.6;
    }

    .login-terms a {
        color: var(--text-dim);
        text-decoration: none;
    }

    .login-terms a:hover { color: var(--accent); }

    .back-link {
        position: absolute;
        top: 32px;
        left: 60px;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: color 0.2s;
    }

    .back-link:hover { color: var(--text); }

    /* ── Responsive ─────────────────────────────────── */
    @media (max-width: 1024px) {
        .login-brand { display: none; }
        .login-form-panel {
            width: 100%;
            min-width: unset;
            border-left: none;
        }
        .login-mobile-logo { display: block; }
        .back-link { left: 24px; top: 24px; }
    }

    @media (max-width: 480px) {
        .login-form-panel { padding: 24px; }
        .login-heading h2 { font-size: 22px; }
        .back-link { position: static; margin-bottom: 24px; }
    }
    </style>
</head>
<body>

<!-- Left: Brand Panel -->
<div class="login-brand">
    <div class="brand-content">
        <a href="/" class="brand-logo">
            <img src="/brand/logo_w.png" alt="GoSiteMe" onerror="this.style.display='none'">
        </a>

        <h1>Your entire web presence, one platform.</h1>
        <p>Hosting, domains, AI website builder, and voice services — all managed from a single dashboard with enterprise-grade security.</p>

        <div class="brand-features">
            <div class="brand-feature">
                <div class="feature-icon blue">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div class="feature-text">
                    <h3>Instant WordPress Hosting</h3>
                    <p>Deploy WordPress sites in 60 seconds with free SSL, daily backups, and 99.9% uptime SLA.</p>
                </div>
            </div>

            <div class="brand-feature">
                <div class="feature-icon purple">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <div class="feature-text">
                    <h3>Alfred AI Assistant</h3>
                    <p>Build, manage, and optimize your website with our AI-powered assistant — no coding required.</p>
                </div>
            </div>

            <div class="brand-feature">
                <div class="feature-icon green">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                </div>
                <div class="feature-text">
                    <h3>Voice AI Services</h3>
                    <p>AI-powered phone agents, IVR systems, and voice automation for your business.</p>
                </div>
            </div>
        </div>

        <div class="brand-trust">
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7.0.10 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                256-bit SSL
            </div>
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7.0.10 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                SOC 2 Compliant
            </div>
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                99.9% Uptime
            </div>
        </div>
    </div>
</div>

<!-- Right: Login Form -->
<div class="login-form-panel">
    <a href="/" class="back-link">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to GoSiteMe
    </a>

    <div class="login-form-wrap">
        <div class="login-mobile-logo">
            <a href="/">GOSITEME</a>
        </div>

        <div class="login-heading">
            <h2>Welcome back</h2>
            <p>Sign in to your account to continue</p>
        </div>

        
        
        <!-- Social Login Buttons -->
        <div class="social-login">
            <button type="button" class="btn-social btn-google" id="googleLoginBtn">
                <svg viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                Continue with Google
            </button>

            <button type="button" class="btn-social btn-github" id="githubLoginBtn">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                Continue with GitHub
            </button>
        </div>

        <div class="login-divider">or sign in with email</div>

        <!-- Login Form -->
        <form id="loginForm" novalidate>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['alfred_csrf'], ENT_QUOTES) ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget, ENT_QUOTES) ?>">

            <div class="form-field">
                <label for="email">Email address</label>
                <div class="input-wrap has-icon">
                    <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg>
                    <input type="email" id="email" name="email" required autofocus
                           placeholder="you@example.com" autocomplete="email">
                </div>
            </div>

            <div class="form-field">
                <label for="password">
                    Password
                    <a href="/forgot-password">Forgot password?</a>
                </label>
                <div class="input-wrap has-icon">
                    <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password" autocomplete="current-password" style="padding-right:44px;">
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <label class="form-remember">
                <input type="checkbox" name="remember" value="1"> Keep me signed in
            </label>

            <button type="submit" class="btn-login" id="loginBtn">
                <span class="btn-label">Sign in</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="login-footer">
            Don't have an account? <a href="/register">Create one free</a>
        </div>

        <div class="login-terms">
            By signing in, you agree to our <a href="/terms-of-service">Terms of Service</a>
            and <a href="/privacy-policy">Privacy Policy</a>.
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('loginForm');
    const btn  = document.getElementById('loginBtn');
    const pwField  = document.getElementById('password');
    const pwToggle = document.getElementById('pwToggle');

    // Password toggle
    pwToggle.addEventListener('click', () => {
        const isPassword = pwField.type === 'password';
        pwField.type = isPassword ? 'text' : 'password';
        pwToggle.innerHTML = isPassword
            ? '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
            : '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    });

    // Form submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btn.classList.add('loading');
        btn.disabled = true;

        // Clear previous errors
        const oldAlert = document.querySelector('.login-alert.error');
        if (oldAlert) oldAlert.remove();

        try {
            const fd = new FormData(form);
            const res = await fetch('/api/auth.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            const data = await res.json();

            if (data.success) {
                // Quick green flash before redirect
                btn.style.background = 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)';
                btn.innerHTML = '<span class="btn-label">Redirecting&hellip;</span>';
                
                setTimeout(() => {
                    window.location.href = fd.get('redirect') || '/dashboard.php';
                }, 300);
            } else if (data.requires_2fa) {
                // Show 2FA input
                btn.classList.remove('loading');
                btn.disabled = false;
                show2FAChallenge(fd);
            } else {
                showError(data.error || 'Invalid email or password.');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        } catch (err) {
            showError('Connection error. Please check your network and try again.');
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    });

    function showError(msg) {
        const oldAlert = document.querySelector('.login-alert.error');
        if (oldAlert) oldAlert.remove();

        const el = document.createElement('div');
        el.className = 'login-alert error';
        el.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' + escHtml(msg);
        form.parentNode.insertBefore(el, form);

        // Shake the form card
        const wrap = document.querySelector('.login-form-wrap');
        wrap.style.animation = 'shake 0.4s ease';
        setTimeout(() => wrap.style.animation = '', 400);
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    // Social login buttons (OAUTH_REDIRECT_FIX: forward post-login redirect target)
    var __oauthRedir = encodeURIComponent(document.querySelector('input[name="redirect"]')?.value || '/dashboard.php');
    document.getElementById('googleLoginBtn').addEventListener('click', () => {
        window.location.href = '/api/auth.php?action=google-login&redirect=' + __oauthRedir;
    });
    document.getElementById('githubLoginBtn').addEventListener('click', () => {
        window.location.href = '/api/auth.php?action=github-login&redirect=' + __oauthRedir;
    });

    // Input focus animations
    document.querySelectorAll('.input-wrap input').forEach(input => {
        input.addEventListener('focus', () => input.closest('.input-wrap').style.transform = 'scale(1.01)');
        input.addEventListener('blur',  () => input.closest('.input-wrap').style.transform = 'scale(1)');
    });

    // 2FA Challenge UI
    function show2FAChallenge(originalFormData) {
        const formArea = document.querySelector('.login-card') || form.parentElement;
        const oldContent = formArea.innerHTML;
        
        formArea.innerHTML = `
            <div style="text-align:center;margin-bottom:24px">
                <div style="width:64px;height:64px;border-radius:16px;background:rgba(0,168,255,0.1);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                    <svg width="28" height="28" fill="none" stroke="var(--accent)" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                </div>
                <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;margin-bottom:6px">Two-Factor Authentication</h2>
                <p style="color:var(--text-dim);font-size:14px">Enter the 6-digit code from your authenticator app</p>
            </div>
            <form id="twoFAForm">
                <div class="input-wrap" style="margin-bottom:20px">
                    <input type="text" id="totpInput" name="totp_code" maxlength="6" pattern="[0-9]{6}" required
                           placeholder="000000" autocomplete="one-time-code" inputmode="numeric"
                           style="text-align:center;font-size:28px;letter-spacing:12px;font-family:'Space Grotesk',sans-serif;padding:16px">
                </div>
                <button type="submit" class="login-btn" style="width:100%;margin-bottom:12px">
                    <span class="btn-label">Verify</span>
                </button>
                <button type="button" class="login-btn" onclick="location.reload()" 
                        style="width:100%;background:transparent;border:1px solid var(--border)">
                    <span class="btn-label">← Back to Login</span>
                </button>
                <p style="text-align:center;margin-top:16px;font-size:12px;color:var(--text-muted)">
                    Lost your authenticator? Use a backup code instead.
                </p>
            </form>
        `;
        
        const totpInput = document.getElementById('totpInput');
        totpInput.focus();
        
        document.getElementById('twoFAForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = totpInput.value.trim();
            if (!code) return;
            
            const verifyBtn = e.target.querySelector('button[type="submit"]');
            verifyBtn.classList.add('loading');
            verifyBtn.disabled = true;
            
            try {
                const fd2 = new FormData();
                fd2.append('action', 'verify-2fa');
                fd2.append('totp_code', code);
                
                const res = await fetch('/api/auth.php', {
                    method: 'POST',
                    body: fd2,
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                if (data.success) {
                    verifyBtn.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
                    verifyBtn.innerHTML = '<span class="btn-label">Redirecting…</span>';
                    setTimeout(() => {
                        window.location.href = originalFormData.get('redirect') || '/dashboard.php';
                    }, 300);
                } else if (data.expired) {
                    location.reload();
                } else {
                    totpInput.value = '';
                    totpInput.style.borderColor = 'var(--red)';
                    totpInput.style.animation = 'shake 0.4s ease';
                    setTimeout(() => { totpInput.style.animation = ''; totpInput.style.borderColor = ''; }, 500);
                    verifyBtn.classList.remove('loading');
                    verifyBtn.disabled = false;
                }
            } catch(err) {
                verifyBtn.classList.remove('loading');
                verifyBtn.disabled = false;
            }
        });
    }
})();
</script>

<!-- Shake animation -->
<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    50% { transform: translateX(6px); }
    75% { transform: translateX(-4px); }
}
</style>

</body>
</html>
