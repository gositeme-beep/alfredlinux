<?php
/**
 * Authentication API
 * Handles client login, registration, and session management
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // OAuth callbacks + login (no session yet)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/family-detection.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Strict rate limits for auth endpoints (brute-force protection)
if (in_array($action, ['login', 'register', 'forgot', 'reset', 'verify-2fa'], true)) {
    apiRateLimit(10, 60, 'auth_sensitive');
}

switch ($action) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'register':
        register();
        break;
    case 'check':
        checkAuth();
        break;
    case 'forgot':
        forgotPassword();
        break;
    case 'reset':
        resetPassword();
        break;
    case 'google-login':
        googleLogin();
        break;
    case 'google-callback':
        googleCallback();
        break;
    case 'github-login':
        githubLogin();
        break;
    case 'github-callback':
        githubCallback();
        break;
    case 'verify-2fa':
        verify2FA();
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Client login
 */
function login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    // CSRF verification
    $csrfToken = $_POST['csrf_token'] ?? '';
    $sessionCsrf = $_SESSION['alfred_csrf'] ?? '';
    if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
        log_suspicious('login_csrf_fail', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        jsonResponse(['error' => 'Invalid security token. Please refresh and try again.'], 403);
    }
    
    // Validate required fields
    $required = validate_required($_POST, ['email', 'password']);
    if ($required !== true) {
        log_suspicious('login_missing_fields', ['missing' => $required]);
        jsonResponse(['error' => 'Email and password required'], 400);
    }
    
    // Validate email format
    $email = validate_email($_POST['email']);
    if (!$email) {
        log_suspicious('login_invalid_email', ['email' => $_POST['email']]);
        jsonResponse(['error' => 'Invalid email address'], 400);
    }
    
    $password = $_POST['password'];
    
    // Password length check (basic validation)
    if (strlen($password) < 1 || strlen($password) > 255) {
        log_suspicious('login_invalid_password', ['reason' => 'invalid_length']);
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    // Rate limiting — check before attempting login
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitResult = checkRateLimit($db, $ip, $email);
    if ($rateLimitResult !== true) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Too many login attempts. Please try again later.',
            'retry_after' => $rateLimitResult
        ]);
        exit;
    }
    
    // Find client by email
    $stmt = $db->prepare("
        SELECT id, firstname, lastname, email, password, status,
               two_factor_enabled, two_factor_secret
        FROM clients 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $client = $stmt->fetch();
    
    if (!$client) {
        // Log failed attempt
        logLoginAttempt($email, false);
        recordFailedAttempt($db, $ip, $email);
        log_suspicious('login_failed', ['email' => $email, 'reason' => 'not_found']);
        jsonResponse(['error' => 'Invalid email or password'], 401);
    }
    
    // Check if account is active
    if ($client['status'] !== 'Active') {
        log_suspicious('login_inactive_account', ['email' => $email]);
        jsonResponse(['error' => 'Account is not active'], 403);
    }
    
    // Verify password (bcrypt)
    if (!password_verify($password, $client['password'])) {
        logLoginAttempt($email, false);
        recordFailedAttempt($db, $ip, $email);
        log_suspicious('login_failed', ['email' => $email, 'reason' => 'wrong_password']);
        jsonResponse(['error' => 'Invalid email or password'], 401);
    }
    
    // 2FA Challenge
    if (!empty($client['two_factor_enabled']) && !empty($client['two_factor_secret'])) {
        $twoFACode = $_POST['totp_code'] ?? '';
        
        if (empty($twoFACode)) {
            // First step: password correct, but 2FA needed
            // Store temp auth in session
            $_SESSION['2fa_pending_client_id'] = $client['id'];
            $_SESSION['2fa_pending_email'] = $client['email'];
            $_SESSION['2fa_pending_time'] = time();
            
            jsonResponse([
                'success' => false,
                'requires_2fa' => true,
                'message' => 'Two-factor authentication code required'
            ]);
        }
        
        // Verify TOTP code
        require_once __DIR__ . '/../pay/includes/totp.php';
        if (!TOTP::verify($client['two_factor_secret'], $twoFACode)) {
            // Check backup codes
            $backupValid = false;
            $backupStmt = $db->prepare("SELECT id, code_hash FROM two_factor_backup WHERE client_id = ? AND used = 0");
            $backupStmt->execute([$client['id']]);
            $backupCodes = $backupStmt->fetchAll();
            foreach ($backupCodes as $bc) {
                if (password_verify($twoFACode, $bc['code_hash'])) {
                    // Mark backup code as used
                    $db->prepare("UPDATE two_factor_backup SET used = 1, used_at = NOW() WHERE id = ?")->execute([$bc['id']]);
                    $backupValid = true;
                    break;
                }
            }
            
            if (!$backupValid) {
                log_suspicious('login_2fa_failed', ['email' => $email]);
                jsonResponse(['error' => 'Invalid 2FA code'], 401);
            }
        }
        
        // Clear pending 2FA session
        unset($_SESSION['2fa_pending_client_id'], $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_time']);
    }
    
    // Successful login
    logLoginAttempt($email, true);
    clearFailedAttempts($db, $email);

    // Probabilistic cleanup of old attempts (1% of requests)
    if (mt_rand(1, 100) === 1) {
        cleanupOldAttempts($db);
    }
    
    // Create session
    $_SESSION['client_id'] = $client['id'];
    $_SESSION['client_email'] = $client['email'];
    $_SESSION['client_name'] = $client['firstname'] . ' ' . $client['lastname'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Set uid/username for Alfred widget compatibility (legacy session vars)
    $_SESSION['uid'] = $client['id'];
    $_SESSION['username'] = $client['firstname'] . ' ' . $client['lastname'];
    
    // Generate session token
    $token = bin2hex(random_bytes(32));
    $_SESSION['token'] = $token;
    
    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'client' => [
            'id' => $client['id'],
            'name' => $client['firstname'] . ' ' . $client['lastname'],
            'email' => $client['email']
        ],
        'token' => $token
    ]);
}

/**
 * Client logout
 */
function logout() {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logged out']);
}

/**
 * Verify 2FA code after password has been validated
 */
function verify2FA() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }
    
    $pendingClientId = $_SESSION['2fa_pending_client_id'] ?? null;
    $pendingTime = $_SESSION['2fa_pending_time'] ?? 0;
    
    // Check if there's a pending 2FA challenge and it hasn't expired (5 min)
    if (!$pendingClientId || (time() - $pendingTime) > 300) {
        unset($_SESSION['2fa_pending_client_id'], $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_time']);
        jsonResponse(['error' => '2FA session expired. Please login again.', 'expired' => true], 401);
    }
    
    $code = $_POST['totp_code'] ?? '';
    if (empty($code)) {
        jsonResponse(['error' => '2FA code required'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, firstname, lastname, email, two_factor_secret FROM clients WHERE id = ?");
    $stmt->execute([$pendingClientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        jsonResponse(['error' => 'Invalid session'], 400);
    }
    
    require_once __DIR__ . '/../pay/includes/totp.php';
    $valid = TOTP::verify($client['two_factor_secret'], $code);
    
    if (!$valid) {
        // Check backup codes
        $backupStmt = $db->prepare("SELECT id, code_hash FROM two_factor_backup WHERE client_id = ? AND used = 0");
        $backupStmt->execute([$client['id']]);
        $backupCodes = $backupStmt->fetchAll();
        foreach ($backupCodes as $bc) {
            if (password_verify($code, $bc['code_hash'])) {
                $db->prepare("UPDATE two_factor_backup SET used = 1, used_at = NOW() WHERE id = ?")->execute([$bc['id']]);
                $valid = true;
                break;
            }
        }
    }
    
    if (!$valid) {
        jsonResponse(['error' => 'Invalid 2FA code'], 401);
    }
    
    // Clear pending 2FA
    $isOAuth = !empty($_SESSION['2fa_pending_oauth']);
    $oauthProvider = $_SESSION['2fa_pending_provider'] ?? '';
    $oauthProviderId = $_SESSION['2fa_pending_provider_id'] ?? '';
    $oauthRedirect = $_SESSION['oauth_redirect'] ?? '/account';
    unset(
        $_SESSION['2fa_pending_client_id'], $_SESSION['2fa_pending_email'],
        $_SESSION['2fa_pending_time'], $_SESSION['2fa_pending_oauth'],
        $_SESSION['2fa_pending_provider'], $_SESSION['2fa_pending_provider_id']
    );
    
    // Prevent session fixation
    session_regenerate_id(true);
    
    // Complete login
    $_SESSION['client_id'] = $client['id'];
    $_SESSION['client_email'] = $client['email'];
    $_SESSION['client_name'] = $client['firstname'] . ' ' . $client['lastname'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['uid'] = $client['id'];
    $_SESSION['username'] = $client['firstname'] . ' ' . $client['lastname'];
    $token = bin2hex(random_bytes(32));
    $_SESSION['token'] = $token;
    if ($isOAuth) {
        $_SESSION['oauth_provider'] = $oauthProvider;
    }
    
    // If this was an OAuth-initiated 2FA, redirect instead of JSON
    if ($isOAuth) {
        log_suspicious('oauth_admin_2fa_success', [
            'email'    => $client['email'],
            'provider' => $oauthProvider,
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        header('Location: ' . $oauthRedirect);
        exit;
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'client' => [
            'id' => $client['id'],
            'name' => $client['firstname'] . ' ' . $client['lastname'],
            'email' => $client['email']
        ],
        'token' => $token
    ]);
}

/**
 * Check if client is authenticated
 */
function checkAuth() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        jsonResponse([
            'authenticated' => true,
            'client' => [
                'id' => $_SESSION['client_id'],
                'name' => $_SESSION['client_name'],
                'email' => $_SESSION['client_email']
            ]
        ]);
    } else {
        jsonResponse(['authenticated' => false]);
    }
}

/**
 * Client registration
 */
function register() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    // CSRF verification
    $csrfToken = $_POST['csrf_token'] ?? '';
    $sessionCsrf = $_SESSION['alfred_csrf'] ?? '';
    if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
        log_suspicious('register_csrf_fail', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        jsonResponse(['error' => 'Invalid security token. Please refresh and try again.'], 403);
    }
    
    // Validate required fields
    $required = validate_required($_POST, ['firstname', 'lastname', 'email', 'password']);
    if ($required !== true) {
        log_suspicious('register_missing_fields', ['missing' => $required]);
        jsonResponse(['error' => 'Required fields: firstname, lastname, email, password'], 400);
    }
    
    // Sanitize and validate input
    $firstName = sanitize($_POST['firstname'], 50);
    $lastName = sanitize($_POST['lastname'], 50);
    $email = validate_email($_POST['email']);
    $phone = sanitize($_POST['phone'] ?? '', 20);
    $address1 = sanitize($_POST['address1'] ?? '', 100);
    $city = sanitize($_POST['city'] ?? '', 50);
    $state = sanitize($_POST['state'] ?? '', 50);
    $postcode = sanitize($_POST['postcode'] ?? '', 20);
    $country = sanitize($_POST['country'] ?? 'US', 2);
    
    // Validation
    if (empty($firstName) || empty($lastName)) {
        log_suspicious('register_empty_names', []);
        jsonResponse(['error' => 'First and last name required'], 400);
    }
    
    if (!$email) {
        log_suspicious('register_invalid_email', ['email' => $_POST['email']]);
        jsonResponse(['error' => 'Invalid email address'], 400);
    }
    
    $password = $_POST['password'];
    if (strlen($password) < 8) {
        log_suspicious('register_weak_password', ['email' => $email]);
        jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
    }
    
    // Check password complexity (at least 1 uppercase, 1 number)
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        jsonResponse(['error' => 'Password must contain at least 1 uppercase letter and 1 number'], 400);
    }
    
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        log_suspicious('register_duplicate_email', ['email' => $email]);
        jsonResponse(['error' => 'Email already registered'], 409);
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new client
    try {
        $stmt = $db->prepare("
            INSERT INTO clients 
            (firstname, lastname, email, password, phone, address1, city, state, postcode, country, status, date_created)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $firstName, $lastName, $email, $hashedPassword,
            $phone, $address1, $city, $state, $postcode, $country, 'Active'
        ]);
        
        $clientId = $db->lastInsertId();
        
        // ── Affiliate referral capture ──
        $refCode = $_SESSION['affiliate_ref'] ?? $_COOKIE['gositeme_ref'] ?? '';
        if ($refCode) {
            try {
                $affStmt = $db->prepare("SELECT id FROM affiliates WHERE referral_code = ? AND is_active = 1 LIMIT 1");
                $affStmt->execute([strtoupper($refCode)]);
                $aff = $affStmt->fetch();
                if ($aff) {
                    $db->prepare("UPDATE clients SET referred_by = ? WHERE id = ?")->execute([$aff['id'], $clientId]);
                    // Mark the click as converted
                    $db->prepare("UPDATE affiliate_hits SET converted = 1 WHERE affiliate_id = ? AND ip_address = ? AND converted = 0 ORDER BY created_at DESC LIMIT 1")
                        ->execute([$aff['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
                }
            } catch (\Throwable $e) {
                error_log('Affiliate referral capture error: ' . $e->getMessage());
            }
        }
        
        log_suspicious('register_success', ['email' => $email, 'client_id' => $clientId]);
        
        // Family detection check
        checkFamilyDetection(['email' => $email, 'firstname' => $firstName, 'lastname' => $lastName]);
        
        // Create session
        $_SESSION['client_id'] = $clientId;
        $_SESSION['client_email'] = $email;
        $_SESSION['client_name'] = $firstName . ' ' . $lastName;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Set uid/username for Alfred widget compatibility (legacy session vars)
        $_SESSION['uid'] = $clientId;
        $_SESSION['username'] = $firstName . ' ' . $lastName;
        
        jsonResponse([
            'success' => true,
            'message' => 'Registration successful',
            'client' => [
                'id' => $clientId,
                'name' => $firstName . ' ' . $lastName,
                'email' => $email
            ]
        ], 201);
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        log_suspicious('register_error', ['email' => $email, 'error' => $e->getMessage()]);
        jsonResponse(['error' => 'Registration failed'], 500);
    }
}

/**
 * Forgot password — generate reset token and send email
 */
function forgotPassword() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }
    
    $email_input = $_POST['email'] ?? '';
    $email = validate_email($email_input);
    
    if (!$email) {
        log_suspicious('forgot_invalid_email', ['email' => $email_input]);
        // Still return generic success to prevent enumeration
        jsonResponse([
            'success' => true,
            'message' => 'If an account exists with that email, you will receive a reset link.'
        ]);
    }
    
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    // Look up the user
    $stmt = $db->prepare("SELECT id, firstname FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch();
    
    if ($client) {
        // Delete any previous tokens for this email
        $stmt = $db->prepare("DELETE FROM alfred_password_resets WHERE email = ?");
        $stmt->execute([$email]);
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store hashed token
        $stmt = $db->prepare("
            INSERT INTO alfred_password_resets (email, token_hash, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$email, $tokenHash, $expiresAt]);
        
        // Build reset link
        $resetLink = SITE_URL . '/?reset=' . $token;
        $firstName = htmlspecialchars($client['firstname'] ?? 'there');
        
        // Send reset email
        $subject = 'Reset Your Alfred AI Password';
        $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#0a0a1a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<div style="max-width:560px;margin:40px auto;background:#12122a;border-radius:16px;border:1px solid rgba(0,168,255,0.15);overflow:hidden;">
  <div style="background:linear-gradient(135deg,#0a0a1a,#1a1a3e);padding:32px 40px;text-align:center;border-bottom:1px solid rgba(0,168,255,0.1);">
    <img src="https://gositeme.com/brand/logo_w.png" alt="GoSiteMe" style="height:36px;margin-bottom:8px;">
    <h1 style="color:#fff;font-size:22px;margin:12px 0 0;">Password Reset Request</h1>
  </div>
  <div style="padding:32px 40px;color:#ccc;font-size:15px;line-height:1.7;">
    <p style="margin:0 0 16px;">Hi {$firstName},</p>
    <p style="margin:0 0 24px;">We received a request to reset your Alfred AI password. Click the button below to set a new password:</p>
    <div style="text-align:center;margin:28px 0;">
      <a href="{$resetLink}" style="display:inline-block;background:linear-gradient(135deg,#00a8ff,#0070cc);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:600;font-size:15px;">Reset Password</a>
    </div>
    <p style="margin:0 0 8px;font-size:13px;color:#888;">This link expires in 1 hour. If you didn't request this, you can safely ignore this email.</p>
    <p style="margin:16px 0 0;font-size:12px;color:#666;">Or copy this link: <span style="color:#00a8ff;word-break:break-all;">{$resetLink}</span></p>
  </div>
  <div style="padding:20px 40px;background:rgba(0,0,0,0.2);text-align:center;border-top:1px solid rgba(255,255,255,0.05);">
    <p style="margin:0;font-size:12px;color:#666;">&copy; " . date('Y') . " GoSiteMe.com &mdash; Alfred AI Platform</p>
  </div>
</div>
</body>
</html>
HTML;
        
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Alfred AI <noreply@gositeme.com>\r\n";
        $headers .= "Reply-To: noreply@gositeme.com\r\n";
        $headers .= "X-Mailer: Alfred-AI/1.0\r\n";
        
        @mail($email, $subject, $htmlBody, $headers);
    }
    
    // Always return success to prevent email enumeration
    jsonResponse([
        'success' => true,
        'message' => 'If an account exists with that email, you will receive a reset link.'
    ]);
}

/**
 * Reset password — verify token and update password
 */
function resetPassword() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }
    
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    // Validate inputs
    if (empty($token) || strlen($token) !== 64) {
        jsonResponse(['error' => 'Invalid or missing reset token'], 400);
    }
    
    if (empty($password)) {
        jsonResponse(['error' => 'Password is required'], 400);
    }
    
    // Validate password strength (min 8 chars, at least 1 number)
    if (strlen($password) < 8) {
        jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        jsonResponse(['error' => 'Password must contain at least 1 number'], 400);
    }
    
    if ($password !== $passwordConfirm) {
        jsonResponse(['error' => 'Passwords do not match'], 400);
    }
    
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    // Hash the submitted token and look it up
    $tokenHash = hash('sha256', $token);
    
    $stmt = $db->prepare("
        SELECT id, email, expires_at, used_at 
        FROM alfred_password_resets 
        WHERE token_hash = ?
    ");
    $stmt->execute([$tokenHash]);
    $resetRecord = $stmt->fetch();
    
    if (!$resetRecord) {
        log_suspicious('reset_invalid_token', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        jsonResponse(['error' => 'Invalid or expired reset token'], 400);
    }
    
    // Check if already used
    if ($resetRecord['used_at'] !== null) {
        jsonResponse(['error' => 'This reset token has already been used'], 400);
    }
    
    // Check if expired
    if (strtotime($resetRecord['expires_at']) < time()) {
        jsonResponse(['error' => 'This reset token has expired. Please request a new one.'], 400);
    }
    
    $email = $resetRecord['email'];
    
    // Update password in clients table
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE clients SET password = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, $email]);
    
    // Mark token as used
    $stmt = $db->prepare("UPDATE alfred_password_resets SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$resetRecord['id']]);
    
    // Delete all tokens for this email
    $stmt = $db->prepare("DELETE FROM alfred_password_resets WHERE email = ?");
    $stmt->execute([$email]);
    
    log_suspicious('password_reset_success', ['email' => $email]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Password reset successfully! You can now log in with your new password.'
    ]);
}

/* ================================================================
   Google OAuth 2.0
   ================================================================ */

/**
 * Step 1: Redirect user to Google's OAuth consent screen
 */
function googleLogin() {
    $clientId = getenv('GOOGLE_CLIENT_ID');
    if (empty($clientId)) {
        header('Location: /login?error=' . urlencode('Google sign-in is not configured yet. Please use email & password.'));
        exit;
    }

    // Store redirect target and CSRF state (validated to prevent open redirect)
    $oauthRedir = $_GET['redirect'] ?? '/account';
    if (!preg_match('#^/[^/]#', $oauthRedir)) { $oauthRedir = '/account'; }
    $_SESSION['oauth_redirect'] = $oauthRedir;
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    // Clean callback URL — update Google Cloud Console redirect URI to match:
    // https://gositeme.com/api/auth.php?action=google-callback
    $googleCallbackUrl = SITE_URL . '/api/auth.php?action=google-callback';

    $params = http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $googleCallbackUrl,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ]);

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

/**
 * Step 2: Handle Google's OAuth callback
 */
function googleCallback() {
    $clientId     = getenv('GOOGLE_CLIENT_ID');
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET');

    if (empty($clientId) || empty($clientSecret)) {
        header('Location: /login?error=' . urlencode('Google OAuth not configured.'));
        exit;
    }

    // Verify state to prevent CSRF
    $state = $_GET['state'] ?? '';
    if (empty($state) || !hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
        header('Location: /login?error=' . urlencode('Invalid OAuth state. Please try again.'));
        exit;
    }
    unset($_SESSION['oauth_state']);

    // Check for errors from Google
    if (!empty($_GET['error'])) {
        header('Location: /login?error=' . urlencode('Google sign-in was cancelled.'));
        exit;
    }

    $code = $_GET['code'] ?? '';
    if (empty($code)) {
        header('Location: /login?error=' . urlencode('No authorization code received.'));
        exit;
    }

    // Exchange code for access token
    // Must use the same redirect_uri that was sent in the initial auth request
    $googleCallbackUrl = SITE_URL . '/api/auth.php?action=google-callback';

    $tokenResponse = curlPost('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $googleCallbackUrl,
        'grant_type'    => 'authorization_code',
    ]);

    if (empty($tokenResponse['access_token'])) {
        error_log('Google OAuth token error: ' . json_encode($tokenResponse));
        header('Location: /login?error=' . urlencode('Failed to authenticate with Google. Please try again.'));
        exit;
    }

    // Get user info from Google
    $userInfo = curlGet('https://www.googleapis.com/oauth2/v2/userinfo', $tokenResponse['access_token']);

    if (empty($userInfo['email'])) {
        header('Location: /login?error=' . urlencode('Could not retrieve your email from Google.'));
        exit;
    }

    // Find or create client
    completeOAuthLogin(
        $userInfo['email'],
        $userInfo['given_name'] ?? '',
        $userInfo['family_name'] ?? '',
        'google',
        $userInfo['id'] ?? ''
    );
}

/* ================================================================
   GitHub OAuth
   ================================================================ */

/**
 * Step 1: Redirect user to GitHub's OAuth consent screen
 */
function githubLogin() {
    $clientId = getenv('GITHUB_CLIENT_ID');
    if (empty($clientId)) {
        header('Location: /login?error=' . urlencode('GitHub sign-in is not configured yet. Please use email & password.'));
        exit;
    }

    $ghRedir = $_GET['redirect'] ?? '/account';
    if (!preg_match('#^/[^/]#', $ghRedir)) { $ghRedir = '/account'; }
    $_SESSION['oauth_redirect'] = $ghRedir;
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    $params = http_build_query([
        'client_id'    => $clientId,
        'redirect_uri' => SITE_URL . '/api/auth.php?action=github-callback',
        'scope'        => 'user:email',
        'state'        => $state,
    ]);

    header('Location: https://github.com/login/oauth/authorize?' . $params);
    exit;
}

/**
 * Step 2: Handle GitHub's OAuth callback
 */
function githubCallback() {
    $clientId     = getenv('GITHUB_CLIENT_ID');
    $clientSecret = getenv('GITHUB_CLIENT_SECRET');

    if (empty($clientId) || empty($clientSecret)) {
        header('Location: /login?error=' . urlencode('GitHub OAuth not configured.'));
        exit;
    }

    $state = $_GET['state'] ?? '';
    if (empty($state) || !hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
        header('Location: /login?error=' . urlencode('Invalid OAuth state. Please try again.'));
        exit;
    }
    unset($_SESSION['oauth_state']);

    if (!empty($_GET['error'])) {
        header('Location: /login?error=' . urlencode('GitHub sign-in was cancelled.'));
        exit;
    }

    $code = $_GET['code'] ?? '';
    if (empty($code)) {
        header('Location: /login?error=' . urlencode('No authorization code received.'));
        exit;
    }

    // Exchange code for access token
    $ch = curl_init('https://github.com/login/oauth/access_token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'redirect_uri'  => SITE_URL . '/api/auth.php?action=github-callback',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $tokenResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($tokenResponse['access_token'])) {
        error_log('GitHub OAuth token error: ' . json_encode($tokenResponse));
        header('Location: /login?error=' . urlencode('Failed to authenticate with GitHub.'));
        exit;
    }

    $token = $tokenResponse['access_token'];

    // Get user profile
    $userInfo = curlGet('https://api.github.com/user', $token, ['User-Agent: GoSiteMe/1.0']);

    // GitHub may hide email — fetch from /user/emails
    $email = $userInfo['email'] ?? '';
    if (empty($email)) {
        $emails = curlGet('https://api.github.com/user/emails', $token, ['User-Agent: GoSiteMe/1.0']);
        if (is_array($emails)) {
            foreach ($emails as $e) {
                if (!empty($e['primary']) && !empty($e['verified'])) {
                    $email = $e['email'];
                    break;
                }
            }
        }
    }

    if (empty($email)) {
        header('Location: /login?error=' . urlencode('Could not retrieve your email from GitHub. Make sure your email is public or verified.'));
        exit;
    }

    // Split name
    $name = $userInfo['name'] ?? $userInfo['login'] ?? '';
    $parts = explode(' ', $name, 2);

    completeOAuthLogin(
        $email,
        $parts[0] ?? '',
        $parts[1] ?? '',
        'github',
        (string)($userInfo['id'] ?? '')
    );
}

/* ================================================================
   OAuth Helpers
   ================================================================ */

/**
 * Complete OAuth login: find or create client, create session, redirect
 */
function completeOAuthLogin(string $email, string $firstName, string $lastName, string $provider, string $providerId) {
    $db = getDB();
    if (!$db) {
        header('Location: /login?error=' . urlencode('Database error.'));
        exit;
    }

    // Look up existing client by email (include 2FA fields)
    $stmt = $db->prepare("SELECT id, firstname, lastname, email, status, two_factor_enabled, two_factor_secret FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    if ($client) {
        // Existing user — check status
        if ($client['status'] !== 'Active') {
            header('Location: /login?error=' . urlencode('Account is not active.'));
            exit;
        }
    } else {
        // Auto-register new user via OAuth
        $stmt = $db->prepare("
            INSERT INTO clients (firstname, lastname, email, password, status, date_created)
            VALUES (?, ?, ?, ?, 'Active', NOW())
        ");
        // OAuth users get a random password (they'll use OAuth to login)
        $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $stmt->execute([
            $firstName ?: 'User',
            $lastName ?: '',
            $email,
            $randomPassword
        ]);
        $client = [
            'id'        => $db->lastInsertId(),
            'firstname' => $firstName ?: 'User',
            'lastname'  => $lastName ?: '',
            'email'     => $email,
        ];
        
        // ── Affiliate referral capture (OAuth) ──
        $refCode = $_SESSION['affiliate_ref'] ?? $_COOKIE['gositeme_ref'] ?? '';
        if ($refCode) {
            try {
                $affStmt = $db->prepare("SELECT id FROM affiliates WHERE referral_code = ? AND is_active = 1 LIMIT 1");
                $affStmt->execute([strtoupper($refCode)]);
                $aff = $affStmt->fetch();
                if ($aff) {
                    $db->prepare("UPDATE clients SET referred_by = ? WHERE id = ?")->execute([$aff['id'], $client['id']]);
                    $db->prepare("UPDATE affiliate_hits SET converted = 1 WHERE affiliate_id = ? AND ip_address = ? AND converted = 0 ORDER BY created_at DESC LIMIT 1")
                        ->execute([$aff['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
                }
            } catch (\Throwable $e) {
                error_log('Affiliate referral capture (OAuth) error: ' . $e->getMessage());
            }
        }
        
        log_suspicious('oauth_auto_register', ['email' => $email, 'provider' => $provider]);
        
        // Family detection check on OAuth registration
        checkFamilyDetection(['email' => $email, 'firstname' => $firstName, 'lastname' => $lastName]);
    }

    // ── Admin account protection: require 2FA for admin OAuth login ──
    if ((int)$client['id'] === 1 && !empty($client['two_factor_enabled']) && !empty($client['two_factor_secret'])) {
        // Admin has 2FA enabled — force verification even via OAuth
        $_SESSION['2fa_pending_client_id'] = $client['id'];
        $_SESSION['2fa_pending_email']     = $client['email'] ?? $email;
        $_SESSION['2fa_pending_time']      = time();
        $_SESSION['2fa_pending_oauth']     = true;
        $_SESSION['2fa_pending_provider']  = $provider;
        $_SESSION['2fa_pending_provider_id'] = $providerId;
        log_suspicious('oauth_admin_2fa_challenge', [
            'email'    => $email,
            'provider' => $provider,
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        header('Location: /login?requires_2fa=1&provider=' . urlencode($provider));
        exit;
    }

    // ── Alert on admin login via OAuth (even without 2FA) ──
    if ((int)$client['id'] === 1) {
        log_suspicious('oauth_admin_login_alert', [
            'email'    => $email,
            'provider' => $provider,
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua'       => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
        error_log('[ADMIN-OAUTH-LOGIN] Admin account logged in via ' . $provider . ' from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }

    // Store/update OAuth link
    try {
        $db->prepare("
            INSERT INTO oauth_identities (client_id, provider, provider_id, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE provider_id = VALUES(provider_id), updated_at = NOW()
        ")->execute([$client['id'], $provider, $providerId]);
    } catch (Exception $e) {
        // Table might not exist yet — non-fatal
        error_log('OAuth identity storage: ' . $e->getMessage());
    }

    // Prevent session fixation — regenerate session ID before setting auth data
    $oauthRedirect = $_SESSION['oauth_redirect'] ?? '/account';
    session_regenerate_id(true);

    // Create session (same as email login)
    $_SESSION['client_id']    = $client['id'];
    $_SESSION['client_email'] = $client['email'] ?? $email;
    $_SESSION['client_name']  = trim(($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? ''));
    $_SESSION['logged_in']    = true;
    $_SESSION['login_time']   = time();
    $_SESSION['uid']          = $client['id'];
    $_SESSION['username']     = $_SESSION['client_name'];
    $_SESSION['oauth_provider'] = $provider;
    $_SESSION['token']        = bin2hex(random_bytes(32));

    logLoginAttempt($email, true);
    log_suspicious('oauth_login_success', ['email' => $email, 'provider' => $provider]);

    header('Location: ' . $oauthRedirect);
    exit;
}

/**
 * cURL POST helper for OAuth token exchange
 */
function curlPost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log('OAuth curlPost error: ' . $err);
        return [];
    }
    return json_decode($response, true) ?: [];
}

/**
 * cURL GET helper with Bearer token
 */
function curlGet(string $url, string $token, array $extraHeaders = []): array {
    $ch = curl_init($url);
    $headers = array_merge(['Authorization: Bearer ' . $token], $extraHeaders);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log('OAuth curlGet error: ' . $err);
        return [];
    }
    return json_decode($response, true) ?: [];
}

/**
 * Log login attempt
 */
function logLoginAttempt($email, $success) {
    $db = getDB();
    if (!$db) return;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Log to activity log
    $stmt = $db->prepare("
        INSERT INTO activity_log (date, description, user, ipaddr)
        VALUES (NOW(), ?, ?, ?)
    ");
    
    $description = $success 
        ? "Client Login Success - Email: $email (Custom Frontend)"
        : "Client Login Failed - Email: $email (Custom Frontend)";
    
    $stmt->execute([$description, $email, $ip]);
}

/**
 * Rate Limiting: Check if login attempts exceed threshold
 * Returns true if OK, or integer seconds until retry if rate-limited
 */
function checkRateLimit($db, $ip, $email) {
    $windowMinutes = 15;
    $windowStart = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
    
    // Check IP rate limit (max 10 per 15 min)
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt, MIN(attempted_at) as earliest 
        FROM alfred_login_attempts 
        WHERE identifier = ? AND identifier_type = 'ip' AND attempted_at > ?
    ");
    $stmt->execute([$ip, $windowStart]);
    $ipResult = $stmt->fetch();
    
    if ($ipResult && (int)$ipResult['cnt'] >= 10) {
        $earliest = strtotime($ipResult['earliest']);
        $retryAfter = ($earliest + ($windowMinutes * 60)) - time();
        log_suspicious('rate_limit_ip', ['ip' => $ip, 'count' => $ipResult['cnt']]);
        return max($retryAfter, 1);
    }
    
    // Check email rate limit (max 5 per 15 min)
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt, MIN(attempted_at) as earliest 
        FROM alfred_login_attempts 
        WHERE identifier = ? AND identifier_type = 'email' AND attempted_at > ?
    ");
    $stmt->execute([$email, $windowStart]);
    $emailResult = $stmt->fetch();
    
    if ($emailResult && (int)$emailResult['cnt'] >= 5) {
        $earliest = strtotime($emailResult['earliest']);
        $retryAfter = ($earliest + ($windowMinutes * 60)) - time();
        log_suspicious('rate_limit_email', ['email' => $email, 'count' => $emailResult['cnt']]);
        return max($retryAfter, 1);
    }
    
    return true;
}

/**
 * Record a failed login attempt for both IP and email
 */
function recordFailedAttempt($db, $ip, $email) {
    try {
        $stmt = $db->prepare("
            INSERT INTO alfred_login_attempts (identifier, identifier_type) VALUES (?, 'ip')
        ");
        $stmt->execute([$ip]);
        
        $stmt = $db->prepare("
            INSERT INTO alfred_login_attempts (identifier, identifier_type) VALUES (?, 'email')
        ");
        $stmt->execute([$email]);
    } catch (Exception $e) {
        error_log('Failed to record login attempt: ' . $e->getMessage());
    }
}

/**
 * Clear failed attempts for an email after successful login
 */
function clearFailedAttempts($db, $email) {
    try {
        $stmt = $db->prepare("DELETE FROM alfred_login_attempts WHERE identifier = ? AND identifier_type = 'email'");
        $stmt->execute([$email]);
    } catch (Exception $e) {
        error_log('Failed to clear login attempts: ' . $e->getMessage());
    }
}

/**
 * Cleanup old login attempts (older than 1 hour)
 */
function cleanupOldAttempts($db) {
    try {
        $cutoff = date('Y-m-d H:i:s', time() - 3600);
        $stmt = $db->prepare("DELETE FROM alfred_login_attempts WHERE attempted_at < ?");
        $stmt->execute([$cutoff]);
    } catch (Exception $e) {
        error_log('Failed to cleanup old attempts: ' . $e->getMessage());
    }
}
