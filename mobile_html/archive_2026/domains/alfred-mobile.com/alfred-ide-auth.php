<?php
/**
 * Alfred IDE — Authentication Gate (GoSiteMe Sign-In)
 *
 * Primary: native email + password auth (no third-party dependency)
 * Secondary: Google OAuth (kept for transition, auto-migrates to native)
 *
 * Flow:
 *   1. User visits /alfred-ide/ → Apache checks for valid session cookie
 *   2. No cookie → redirect here → GoSiteMe Sign-In form (email + password)
 *   3. Auth successful → PIN prompt (set on first visit, verify on return)
 *   4. Issue session token cookie → redirect back to /alfred-ide/
 *
 * Google migration: existing Google users prompted to set a GoSiteMe
 * password on next login so they can sign in natively going forward.
 */

session_start();
ini_set('session.cookie_samesite', 'Lax');

require_once __DIR__ . '/includes/db-config.inc.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$siteUrl = 'https://gositeme.com';

function ideMainSiteClientId(): int {
    return (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
}

function ideMainSiteAuthenticated(): bool {
    return ideMainSiteClientId() > 0 && (!empty($_SESSION['logged_in']) || !empty($_SESSION['client_id']) || !empty($_SESSION['uid']));
}

function ideHasPaidAccess(int $clientId): bool {
    if ($clientId <= 0) return false;
    try {
        $db = getSharedDB();

        $svc = $db->prepare("SELECT COUNT(*) FROM services WHERE client_id = ? AND status = 'Active'");
        $svc->execute([$clientId]);
        if ((int)$svc->fetchColumn() > 0) {
            return true;
        }

        $dom = $db->prepare("SELECT COUNT(*) FROM domains WHERE client_id = ? AND status = 'Active'");
        $dom->execute([$clientId]);
        return (int)$dom->fetchColumn() > 0;
    } catch (Throwable $e) {
        error_log('[ALFRED-IDE-AUTH] Paid access check failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

function ideGetMainSitePinRecord(int $clientId): ?array {
    if ($clientId <= 0) return null;
    try {
        $db = getSharedDB();
        $stmt = $db->prepare("SELECT pin_hash, failed_attempts, lockout_until, frozen_until FROM commander_vault WHERE client_id = ? LIMIT 1");
        $stmt->execute([$clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('[ALFRED-IDE-AUTH] Main-site PIN lookup failed for client ' . $clientId . ': ' . $e->getMessage());
        return null;
    }
}

function ideSyncPinFromMainSite(int $clientId, int $ideUserId): bool {
    if ($clientId <= 0 || $ideUserId <= 0) return false;
    $pinRecord = ideGetMainSitePinRecord($clientId);
    if (!$pinRecord || empty($pinRecord['pin_hash'])) {
        return false;
    }
    $db = getSharedDB();
    $db->prepare("UPDATE alfred_ide_users SET pin_hash = ?, pin_set_at = COALESCE(pin_set_at, NOW()) WHERE id = ?")
        ->execute([(string)$pinRecord['pin_hash'], $ideUserId]);
    return true;
}

function ideLoadMainSiteIdentity(int $clientId): array {
    $identity = [
        'email' => (string)($_SESSION['email'] ?? $_SESSION['client_email'] ?? ''),
        'name' => trim((string)($_SESSION['username'] ?? $_SESSION['client_name'] ?? '')),
    ];

    try {
        $db = getSharedDB();
        $stmt = $db->prepare("SELECT email, firstname, lastname FROM clients WHERE id = ? LIMIT 1");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($client) {
            $identity['email'] = (string)($client['email'] ?? $identity['email']);
            $fullName = trim(((string)($client['firstname'] ?? '')) . ' ' . ((string)($client['lastname'] ?? '')));
            if ($fullName !== '') {
                $identity['name'] = $fullName;
            }
        }
    } catch (Throwable $e) {
        error_log('[ALFRED-IDE-AUTH] Identity lookup failed for client ' . $clientId . ': ' . $e->getMessage());
    }

    if ($identity['name'] === '') {
        $identity['name'] = $identity['email'] !== '' ? $identity['email'] : ('Client ' . $clientId);
    }
    return $identity;
}

function ideEnsureUserForClient(int $clientId): array {
    $db = getSharedDB();
    $identity = ideLoadMainSiteIdentity($clientId);

    $stmt = $db->prepare("SELECT * FROM alfred_ide_users WHERE client_id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $db->prepare("INSERT INTO alfred_ide_users (client_id, email, display_name, google_email, google_name) VALUES (?, ?, ?, ?, ?)")
            ->execute([$clientId, $identity['email'], $identity['name'], $identity['email'], $identity['name']]);
        $stmt->execute([$clientId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $db->prepare("UPDATE alfred_ide_users SET email = ?, display_name = ?, google_email = ?, google_name = ? WHERE id = ?")
            ->execute([$identity['email'], $identity['name'], $identity['email'], $identity['name'], $user['id']]);
        $stmt->execute([$clientId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        throw new RuntimeException('Unable to provision Alfred IDE user');
    }
    return $user;
}

function idePrimeMainSiteUserContext(): void {
    $clientId = ideMainSiteClientId();
    if ($clientId <= 0) return;

    $user = ideEnsureUserForClient($clientId);
    ideSyncPinFromMainSite($clientId, (int)$user['id']);
    $user = ideEnsureUserForClient($clientId);
    $_SESSION['ide_user_id'] = (int)$user['id'];
    $_SESSION['ide_google_email'] = $user['email'] ?: $user['google_email'] ?: '';
    $_SESSION['ide_google_name'] = $user['display_name'] ?: $user['google_name'] ?: ($_SESSION['client_name'] ?? 'User');
}

if (empty($_COOKIE['alfred_ide_token']) && !ideMainSiteAuthenticated() && !in_array($action, ['session-check', 'logout'], true)) {
    header('Location: /login?redirect=%2Falfred-ide%2F');
    exit;
}

if (ideMainSiteAuthenticated()) {
    $mainSiteClientId = ideMainSiteClientId();
    if (!ideHasPaidAccess($mainSiteClientId)) {
        echo ideError('Alfred IDE requires an active paid GoSiteMe hosting or domain service on your account.');
        exit;
    }
    idePrimeMainSiteUserContext();
}

// Commander fast-lane: if already logged in as client_id=33, show PIN prompt (uses existing GoSiteMe PIN)
if (!$action || $action === 'google-login' || $action === 'gsm-login') {
    if (ideMainSiteAuthenticated()) {
        $db = getSharedDB();
        $stmt = $db->prepare("SELECT pin_hash FROM alfred_ide_users WHERE id = ? LIMIT 1");
        $stmt->execute([(int)($_SESSION['ide_user_id'] ?? 0)]);
        $pinHash = (string)$stmt->fetchColumn();
        if ($pinHash !== '') {
            ideShowPinPrompt();
        } else {
            echo ideError('Alfred IDE requires your existing GoSiteMe secure PIN. Set your secure PIN on GoSiteMe first, then return here.');
        }
        exit;
    }
}

switch ($action) {
    case 'gsm-login':     ideGsmLogin(); break;
    case 'gsm-register':  ideGsmRegister(); break;
    case 'commander-pin':  ideCommanderPin(); break;
    case 'google-login':   ideGoogleLogin(); break;
    case 'google-cb':      ideGoogleCallback(); break;
    case 'set-password':   ideSetPassword(); break;
    case 'pin-verify':     idePinVerify(); break;
    case 'pin-setup':      idePinSetup(); break;
    case 'session-check':  ideSessionCheck(); break;
    case 'logout':         ideLogout(); break;
    default:               ideShowLogin(); break;
}

function ideSessionBridgePaths(): array {
    return [
        '/home/gositeme/domains/gositeme.com/logs/alfred-ide/session.json',
        '/home/gositeme/.alfred-ide/session.json',
    ];
}

function ideWriteSessionBridge($token) {
    if (!$token) return;
    $payload = [
        'token' => (string)$token,
        'issued_at' => time(),
        'expires_at' => time() + 86400,
        'ide_user_id' => (int)($_SESSION['ide_user_id'] ?? 0),
        'name' => (string)($_SESSION['ide_google_name'] ?? ''),
        'email' => (string)($_SESSION['ide_google_email'] ?? ''),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    foreach (ideSessionBridgePaths() as $path) {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            error_log('[ALFRED-IDE-AUTH] Could not create session bridge directory: ' . $dir);
            continue;
        }
        if (@file_put_contents($path, $json, LOCK_EX) !== false) {
            @chmod($path, 0600);
            return;
        }
        error_log('[ALFRED-IDE-AUTH] Could not write session bridge: ' . $path);
    }
}

function ideClearSessionBridge() {
    foreach (ideSessionBridgePaths() as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

// ── Commander PIN Login (8-16 digit PIN — no email needed) ──────────────────

function ideCommanderPin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ideShowLogin('', 'pin'); return; }

    $pin = $_POST['commander_pin'] ?? '';
    if (!$pin || strlen($pin) < 4 || strlen($pin) > 16 || !ctype_digit($pin)) {
        ideShowLogin('Enter your Commander PIN.', 'pin');
        return;
    }

    $db = getSharedDB();

    // Check against commander_vault — the SAME PIN used on gositeme.com/login
    $stmt = $db->prepare("SELECT * FROM commander_vault WHERE client_id = 33 LIMIT 1");
    $stmt->execute();
    $vault = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vault || empty($vault['pin_hash'])) {
        ideShowLogin('Commander PIN not set. Log in at gositeme.com first.', 'pin');
        return;
    }

    if ($vault['frozen_until'] && strtotime($vault['frozen_until']) > time()) {
        ideShowLogin('Account frozen. Try again later.', 'pin');
        return;
    }
    if ($vault['lockout_until'] && strtotime($vault['lockout_until']) > time()) {
        $wait = ceil((strtotime($vault['lockout_until']) - time()) / 60);
        ideShowLogin("Too many attempts. Try again in {$wait} min.", 'pin');
        return;
    }

    if (!password_verify($pin, $vault['pin_hash'])) {
        $attempts = (int)$vault['failed_attempts'] + 1;
        if ($attempts >= 10) {
            $db->prepare("UPDATE commander_vault SET failed_attempts = ?, frozen_until = ? WHERE id = ?")
               ->execute([$attempts, date('Y-m-d H:i:s', time() + 86400), $vault['id']]);
        } elseif ($attempts >= 5) {
            $db->prepare("UPDATE commander_vault SET failed_attempts = ?, lockout_until = ? WHERE id = ?")
               ->execute([$attempts, date('Y-m-d H:i:s', time() + 300), $vault['id']]);
        } else {
            $db->prepare("UPDATE commander_vault SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $vault['id']]);
        }
        ideShowLogin('Invalid Commander PIN.', 'pin');
        return;
    }

    // PIN verified — reset attempts and issue IDE session
    $db->prepare("UPDATE commander_vault SET failed_attempts = 0, lockout_until = NULL WHERE id = ?")
       ->execute([$vault['id']]);

    // Get or create IDE user
    $stmt = $db->prepare("SELECT * FROM alfred_ide_users WHERE client_id = 33 LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $db->prepare("INSERT INTO alfred_ide_users (client_id, email, google_name) VALUES (33, 'danny@gositeme.com', 'Commander')")->execute();
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $_SESSION['ide_user_id'] = (int)$user['id'];
    $_SESSION['ide_google_email'] = $user['email'];
    $_SESSION['ide_google_name'] = $user['google_name'] ?: 'Commander';

    ideIssueSession();
}

// ── GoSiteMe Sign-In (email + password) ─────────────────────────────────────

function ideGsmLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ideShowLogin('', 'login'); return; }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        ideShowLogin('Email and password are required.', 'login');
        return;
    }

    $db = getSharedDB();
    $stmt = $db->prepare("SELECT * FROM alfred_ide_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['password_hash'])) {
        ideShowLogin('Invalid email or password.', 'login');
        return;
    }

    if ($user['frozen_until'] && strtotime($user['frozen_until']) > time()) {
        ideShowLogin('Account frozen. Try again later or contact support.', 'login');
        return;
    }
    if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
        $wait = ceil((strtotime($user['lockout_until']) - time()) / 60);
        ideShowLogin("Too many attempts. Try again in {$wait} min.", 'login');
        return;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = (int)$user['failed_attempts'] + 1;
        if ($attempts >= 10) {
            $db->prepare("UPDATE alfred_ide_users SET failed_attempts = ?, frozen_until = ? WHERE id = ?")
               ->execute([$attempts, date('Y-m-d H:i:s', time() + 86400), $user['id']]);
        } elseif ($attempts >= 5) {
            $db->prepare("UPDATE alfred_ide_users SET failed_attempts = ?, lockout_until = ? WHERE id = ?")
               ->execute([$attempts, date('Y-m-d H:i:s', time() + 300), $user['id']]);
        } else {
            $db->prepare("UPDATE alfred_ide_users SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $user['id']]);
        }
        ideShowLogin('Invalid email or password.', 'login');
        return;
    }

    $db->prepare("UPDATE alfred_ide_users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?")
       ->execute([$user['id']]);

    $_SESSION['ide_user_id'] = (int)$user['id'];
    $_SESSION['ide_google_email'] = $user['email'];
    $_SESSION['ide_google_name'] = $user['display_name'] ?: $user['google_name'] ?: $user['email'];

    if (empty($user['pin_hash'])) {
        ideShowPinSetup();
    } else {
        ideShowPinPrompt();
    }
}

// ── GoSiteMe Register (new account) ─────────────────────────────────────────

function ideGsmRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ideShowLogin('', 'register'); return; }

    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ideShowLogin('Valid email is required.', 'register');
        return;
    }
    if (strlen($password) < 8) {
        ideShowLogin('Password must be at least 8 characters.', 'register');
        return;
    }
    if ($password !== $confirm) {
        ideShowLogin('Passwords do not match.', 'register');
        return;
    }

    $db = getSharedDB();
    $stmt = $db->prepare("SELECT id FROM alfred_ide_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        ideShowLogin('An account with this email already exists. Sign in instead.', 'login');
        return;
    }

    // Check if they're an existing WHMCS client
    $clientId = null;
    $cstmt = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
    $cstmt->execute([$email]);
    $client = $cstmt->fetch();
    if ($client) $clientId = (int)$client['id'];

    $hash = password_hash($password, PASSWORD_ARGON2ID);
    $db->prepare("INSERT INTO alfred_ide_users (client_id, email, display_name, password_hash, google_email) VALUES (?, ?, ?, ?, ?)")
       ->execute([$clientId, $email, $name ?: null, $hash, $email]);

    $stmt = $db->prepare("SELECT * FROM alfred_ide_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $_SESSION['ide_user_id'] = (int)$user['id'];
    $_SESSION['ide_google_email'] = $email;
    $_SESSION['ide_google_name'] = $name ?: $email;

    ideShowPinSetup();
}

// ── Set password (for Google users migrating to GoSiteMe auth) ───────────────

function ideSetPassword() {
    if (empty($_SESSION['ide_user_id'])) { header('Location: /alfred-ide-auth.php'); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ideShowLogin('', 'login'); return; }

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8) {
        ideShowSetPassword('Password must be at least 8 characters.');
        return;
    }
    if ($password !== $confirm) {
        ideShowSetPassword('Passwords do not match.');
        return;
    }

    $hash = password_hash($password, PASSWORD_ARGON2ID);
    $db = getSharedDB();
    $db->prepare("UPDATE alfred_ide_users SET password_hash = ?, email = COALESCE(email, google_email) WHERE id = ?")
       ->execute([$hash, $_SESSION['ide_user_id']]);

    ideIssueSession();
}

// ── Google OAuth: redirect to Google ────────────────────────────────────────

function ideGoogleLogin() {
    global $siteUrl;
    $clientId = getenv('GOOGLE_CLIENT_ID');
    if (!$clientId) { die(ideError('Google Sign-In not configured on this server.')); }

    $state = bin2hex(random_bytes(20));
    $_SESSION['ide_oauth_state'] = $state;

    $params = http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $siteUrl . '/alfred-ide-auth.php?action=google-cb',
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

// ── Google OAuth: callback ──────────────────────────────────────────────────

function ideGoogleCallback() {
    global $siteUrl;
    $clientId     = getenv('GOOGLE_CLIENT_ID');
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
    if (!$clientId || !$clientSecret) { die(ideError('Google OAuth not configured.')); }

    $state = $_GET['state'] ?? '';
    if (!$state || !hash_equals($_SESSION['ide_oauth_state'] ?? '', $state)) {
        die(ideError('Invalid OAuth state. Please try again.'));
    }
    unset($_SESSION['ide_oauth_state']);

    if (!empty($_GET['error'])) { die(ideError('Google sign-in was cancelled.')); }

    $code = $_GET['code'] ?? '';
    if (!$code) { die(ideError('No authorization code received.')); }

    $callbackUrl = $siteUrl . '/alfred-ide-auth.php?action=google-cb';
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code, 'client_id' => $clientId, 'client_secret' => $clientSecret,
            'redirect_uri' => $callbackUrl, 'grant_type' => 'authorization_code'
        ]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15
    ]);
    $tokenResp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($tokenResp['access_token'])) {
        die(ideError('Failed to authenticate with Google.'));
    }

    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenResp['access_token']]
    ]);
    $userInfo = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($userInfo['email'])) { die(ideError('Could not retrieve email from Google.')); }

    $db = getSharedDB();

    $stmt = $db->prepare("SELECT * FROM alfred_ide_users WHERE google_email = ? LIMIT 1");
    $stmt->execute([$userInfo['email']]);
    $ideUser = $stmt->fetch();

    if (!$ideUser) {
        $clientId = null;
        $cstmt = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
        $cstmt->execute([$userInfo['email']]);
        $client = $cstmt->fetch();
        if ($client) $clientId = (int)$client['id'];

        $db->prepare("INSERT INTO alfred_ide_users (client_id, google_email, google_name, google_avatar) VALUES (?, ?, ?, ?)")
           ->execute([$clientId, $userInfo['email'], trim(($userInfo['given_name'] ?? '') . ' ' . ($userInfo['family_name'] ?? '')), $userInfo['picture'] ?? '']);

        $stmt->execute([$userInfo['email']]);
        $ideUser = $stmt->fetch();
    } else {
        $db->prepare("UPDATE alfred_ide_users SET google_name = ?, google_avatar = ? WHERE id = ?")
           ->execute([trim(($userInfo['given_name'] ?? '') . ' ' . ($userInfo['family_name'] ?? '')), $userInfo['picture'] ?? '', $ideUser['id']]);
        $ideUser['google_name'] = trim(($userInfo['given_name'] ?? '') . ' ' . ($userInfo['family_name'] ?? ''));
        $ideUser['google_avatar'] = $userInfo['picture'] ?? '';
    }

    if ($ideUser['frozen_until'] && strtotime($ideUser['frozen_until']) > time()) {
        die(ideError('Account frozen due to too many failed PIN attempts. Try again later.'));
    }

    $_SESSION['ide_user_id'] = (int)$ideUser['id'];
    $_SESSION['ide_google_email'] = $ideUser['google_email'];
    $_SESSION['ide_google_name'] = $ideUser['google_name'] ?: $userInfo['email'];
    $_SESSION['ide_google_avatar'] = $ideUser['google_avatar'];

    // If Google user has no GoSiteMe password yet, prompt them to set one
    if (empty($ideUser['password_hash'])) {
        ideShowSetPassword();
        return;
    }

    if (empty($ideUser['pin_hash'])) {
        ideShowPinSetup();
    } else {
        ideShowPinPrompt();
    }
}

// ── PIN setup (first time) ──────────────────────────────────────────────────

function idePinSetup() {
    if (empty($_SESSION['ide_user_id'])) { header('Location: /alfred-ide-auth.php'); exit; }

    if (ideMainSiteAuthenticated()) {
        $clientId = ideMainSiteClientId();
        $ideUserId = (int)($_SESSION['ide_user_id'] ?? 0);
        if (ideSyncPinFromMainSite($clientId, $ideUserId)) {
            ideShowPinPrompt('Use your existing GoSiteMe secure PIN for Alfred IDE.');
        } else {
            echo ideError('Alfred IDE cannot create its own PIN. Please set your secure PIN on GoSiteMe, then come back here.');
        }
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ideShowPinSetup(); return; }

    $pin = $_POST['pin'] ?? '';
    $confirm = $_POST['pin_confirm'] ?? '';

    if (strlen($pin) < 4 || strlen($pin) > 8 || !ctype_digit($pin)) {
        ideShowPinSetup('PIN must be 4-8 digits.');
        return;
    }
    if ($pin !== $confirm) {
        ideShowPinSetup('PINs do not match.');
        return;
    }

    $hash = password_hash($pin, PASSWORD_ARGON2ID);
    $db = getSharedDB();
    $db->prepare("UPDATE alfred_ide_users SET pin_hash = ?, pin_set_at = NOW(), failed_attempts = 0 WHERE id = ?")
       ->execute([$hash, $_SESSION['ide_user_id']]);

    ideIssueSession();
}

// ── PIN verify (returning user) ─────────────────────────────────────────────

function idePinVerify() {
    if (empty($_SESSION['ide_user_id'])) { header('Location: /alfred-ide-auth.php'); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ideShowPinPrompt(); return; }

    $pin = $_POST['pin'] ?? '';
    $db = getSharedDB();

    $stmt = $db->prepare("SELECT pin_hash, failed_attempts, lockout_until, frozen_until FROM alfred_ide_users WHERE id = ?");
    $stmt->execute([$_SESSION['ide_user_id']]);
    $user = $stmt->fetch();

    if (!$user) { header('Location: /alfred-ide-auth.php'); exit; }
    if (empty($user['pin_hash'])) {
        if (ideMainSiteAuthenticated() && ideSyncPinFromMainSite(ideMainSiteClientId(), (int)$_SESSION['ide_user_id'])) {
            $stmt->execute([$_SESSION['ide_user_id']]);
            $user = $stmt->fetch();
        }
        if (empty($user['pin_hash'])) {
            echo ideError('No secure PIN is available for this account. Set your GoSiteMe secure PIN first.');
            return;
        }
    }

    if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
        $wait = ceil((strtotime($user['lockout_until']) - time()) / 60);
        ideShowPinPrompt("Locked out. Try again in {$wait} minute(s).");
        return;
    }

    if ($user['frozen_until'] && strtotime($user['frozen_until']) > time()) {
        ideShowPinPrompt('Account frozen. Contact support.');
        return;
    }

    if (!password_verify($pin, $user['pin_hash'])) {
        $attempts = (int)$user['failed_attempts'] + 1;
        $lockout = null;
        $frozen = null;

        if ($attempts >= 10) {
            $frozen = date('Y-m-d H:i:s', time() + 86400);
            $db->prepare("UPDATE alfred_ide_users SET failed_attempts = ?, frozen_until = ? WHERE id = ?")
               ->execute([$attempts, $frozen, $_SESSION['ide_user_id']]);
            ideShowPinPrompt('Account frozen for 24 hours due to too many failed attempts.');
            return;
        } elseif ($attempts >= 5) {
            $lockout = date('Y-m-d H:i:s', time() + 300);
            $db->prepare("UPDATE alfred_ide_users SET failed_attempts = ?, lockout_until = ? WHERE id = ?")
               ->execute([$attempts, $lockout, $_SESSION['ide_user_id']]);
            ideShowPinPrompt('Incorrect PIN. Locked out for 5 minutes.');
            return;
        }

        $db->prepare("UPDATE alfred_ide_users SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $_SESSION['ide_user_id']]);
        $remaining = 5 - $attempts;
        ideShowPinPrompt("Incorrect PIN. {$remaining} attempt(s) before lockout.");
        return;
    }

    $db->prepare("UPDATE alfred_ide_users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?")
       ->execute([$_SESSION['ide_user_id']]);

    ideIssueSession();
}

// ── Issue session token and redirect to IDE ─────────────────────────────────

function ideIssueSession() {
    session_regenerate_id(true);
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 86400);

    $db = getSharedDB();
    $db->prepare("UPDATE alfred_ide_users SET session_token = ?, token_expires = ?, last_login = NOW() WHERE id = ?")
       ->execute([$tokenHash, $expires, $_SESSION['ide_user_id']]);

    $_SESSION['ide_session_token'] = $token;
    $_SESSION['ide_authenticated'] = true;

    setcookie('alfred_ide_token', $token, [
        'expires' => time() + 86400,
        'path' => '/alfred-ide/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    setcookie('alfred_ide_token', $token, [
        'expires' => time() + 86400,
        'path' => '/alfred-ide-auth.php',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    ideWriteSessionBridge($token);

    header('Location: /alfred-ide/');
    exit;
}

// ── Session check (called by .htaccess auth gate) ──────────────────────────

function ideSessionCheck() {
    header('Content-Type: application/json');

    $token = $_COOKIE['alfred_ide_token'] ?? $_SESSION['ide_session_token'] ?? '';
    if (!$token) { echo json_encode(['valid' => false]); exit; }

    $tokenHash = hash('sha256', $token);
    $db = getSharedDB();
    $stmt = $db->prepare("SELECT u.id, u.email, u.google_email, u.google_name, u.google_avatar, u.display_name, u.client_id FROM alfred_ide_users u WHERE u.session_token = ? AND u.token_expires > NOW() LIMIT 1");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['valid' => false]);
        exit;
    }

    echo json_encode([
        'valid' => true,
        'user_id' => (int)$user['id'],
        'email' => $user['email'] ?: $user['google_email'],
        'name' => $user['display_name'] ?: $user['google_name'],
        'avatar' => $user['google_avatar'],
        'client_id' => $user['client_id'],
        'auth_provider' => !empty($user['google_email']) ? 'gositeme+google' : 'gositeme'
    ]);
}

// ── Logout ──────────────────────────────────────────────────────────────────

function ideLogout() {
    if (!empty($_SESSION['ide_user_id'])) {
        $db = getSharedDB();
        $db->prepare("UPDATE alfred_ide_users SET session_token = NULL, token_expires = NULL WHERE id = ?")
           ->execute([$_SESSION['ide_user_id']]);
    }

    setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/alfred-ide/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/alfred-ide-auth.php', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);

    unset($_SESSION['ide_user_id'], $_SESSION['ide_google_email'], $_SESSION['ide_google_name'],
          $_SESSION['ide_google_avatar'], $_SESSION['ide_session_token'], $_SESSION['ide_authenticated'],
          $_SESSION['ide_gate_token'], $_SESSION['ide_gate_expires']);

    ideClearSessionBridge();

    session_regenerate_id(true);
    header('Location: /alfred-ide-auth.php');
    exit;
}

// ── Render: login page ──────────────────────────────────────────────────────

function ideShowLogin($error = '', $tab = 'login') {
    $already = !empty($_SESSION['ide_authenticated']) && !empty($_COOKIE['alfred_ide_token']);
    if ($already) {
        $db = getSharedDB();
        $th = hash('sha256', $_COOKIE['alfred_ide_token']);
        $stmt = $db->prepare("SELECT id FROM alfred_ide_users WHERE session_token = ? AND token_expires > NOW()");
        $stmt->execute([$th]);
        if ($stmt->fetch()) { header('Location: /alfred-ide/'); exit; }
    }

    if (!$error) $error = $_GET['error'] ?? '';
    $pinActive = ($tab === 'pin') ? 'active' : '';
    $loginActive = ($tab === 'login') ? 'active' : '';
    $registerActive = ($tab === 'register') ? 'active' : '';
    // Default to PIN tab for Commander convenience
    if (!$tab || $tab === 'login') { $pinActive = 'active'; $loginActive = ''; }

    echo idePageShell('Sign In', '
    <div class="gsm-brand">
      <svg class="gsm-logo" viewBox="0 0 40 40" width="48" height="48">
        <circle cx="20" cy="20" r="18" fill="none" stroke="#e2b340" stroke-width="2"/>
        <text x="20" y="26" text-anchor="middle" fill="#e2b340" font-size="18" font-weight="bold" font-family="system-ui">G</text>
      </svg>
      <div class="gsm-title">
        <span class="gsm-name">GoSiteMe</span>
        <span class="gsm-sub">Sign-On</span>
      </div>
    </div>
    <h1>Alfred IDE</h1>
    <p class="sub">Sovereign Authentication &mdash; powered by GoSiteMe</p>
    ' . ($error ? '<div class="error-msg">' . htmlspecialchars($error) . '</div>' : '') . '

    <div class="auth-tabs">
      <button class="auth-tab ' . $pinActive . '" onclick="showTab(\'pin\')" id="tabPin">&#x1F511; Commander PIN</button>
      <button class="auth-tab ' . $loginActive . '" onclick="showTab(\'login\')" id="tabLogin">&#x2709; Email</button>
      <button class="auth-tab ' . $registerActive . '" onclick="showTab(\'register\')" id="tabRegister">&#x2795; Register</button>
    </div>

    <form method="POST" action="/alfred-ide-auth.php?action=commander-pin" class="auth-form" id="formPin" style="' . ($pinActive ? '' : 'display:none') . '">
      <p style="color:#8b9dc3;font-size:0.85rem;margin-bottom:8px;">Same PIN you use on GoSiteMe</p>
      <input type="password" name="commander_pin" placeholder="Your Commander PIN" required class="auth-input" style="text-align:center;font-size:1.3rem;letter-spacing:4px;" autocomplete="off" minlength="4" maxlength="16" inputmode="numeric" pattern="[0-9]{4,16}" autofocus>
      <button type="submit" class="submit-btn">UNLOCK</button>
    </form>

    <form method="POST" action="/alfred-ide-auth.php?action=gsm-login" class="auth-form" id="formLogin" style="' . ($loginActive ? '' : 'display:none') . '">
      <input type="email" name="email" placeholder="Email address" required class="auth-input" autocomplete="email">
      <input type="password" name="password" placeholder="Password" required class="auth-input" autocomplete="current-password" minlength="8">
      <button type="submit" class="submit-btn">SIGN IN</button>
    </form>

    <form method="POST" action="/alfred-ide-auth.php?action=gsm-register" class="auth-form" id="formRegister" style="' . ($registerActive ? '' : 'display:none') . '">
      <input type="text" name="name" placeholder="Full name" class="auth-input" autocomplete="name">
      <input type="email" name="email" placeholder="Email address" required class="auth-input" autocomplete="email">
      <input type="password" name="password" placeholder="Password (8+ characters)" required class="auth-input" autocomplete="new-password" minlength="8">
      <input type="password" name="password_confirm" placeholder="Confirm password" required class="auth-input" autocomplete="new-password" minlength="8">
      <button type="submit" class="submit-btn">CREATE ACCOUNT</button>
    </form>

    <div class="gsm-badge">
      <span class="shield-icon">&#x1F6E1;</span>
      <span>Protected by <strong>GoSiteMe Sovereign Auth</strong></span>
    </div>
    <p class="footer-note">No Google &bull; No Microsoft &bull; Your keys, your kingdom</p>

    <script>
    function showTab(t){
      document.getElementById("formPin").style.display=t==="pin"?"":"none";
      document.getElementById("formLogin").style.display=t==="login"?"":"none";
      document.getElementById("formRegister").style.display=t==="register"?"":"none";
      document.getElementById("tabPin").classList.toggle("active",t==="pin");
      document.getElementById("tabLogin").classList.toggle("active",t==="login");
      document.getElementById("tabRegister").classList.toggle("active",t==="register");
    }
    </script>
    ');
}

function ideShowSetPassword($error = '') {
    $name = htmlspecialchars($_SESSION['ide_google_name'] ?? 'there');
    echo idePageShell('Set Password', '
    <img src="/assets/images/alfred-portrait.png" alt="Alfred" class="portrait">
    <h1>Welcome, ' . $name . '</h1>
    <p class="sub">Set a GoSiteMe password so you can sign in without Google next time</p>
    ' . ($error ? '<div class="error-msg">' . htmlspecialchars($error) . '</div>' : '') . '
    <form method="POST" action="/alfred-ide-auth.php?action=set-password" class="auth-form">
      <input type="password" name="password" placeholder="Choose a password (8+ chars)" required class="auth-input" autocomplete="new-password" minlength="8" autofocus>
      <input type="password" name="password_confirm" placeholder="Confirm password" required class="auth-input" autocomplete="new-password" minlength="8">
      <button type="submit" class="submit-btn">SET PASSWORD &amp; CONTINUE</button>
    </form>
    <a href="/alfred-ide-auth.php?action=pin-setup" class="switch-link">Skip for now</a>
    ');
}

// ── Render: PIN setup ───────────────────────────────────────────────────────

function ideShowPinSetup($error = '') {
    $name = htmlspecialchars($_SESSION['ide_google_name'] ?? 'User');
    echo idePageShell('Set Your PIN', '
    <img src="/assets/images/alfred-portrait.png" alt="Alfred" class="portrait">
    <h1>Welcome, ' . $name . '</h1>
    <p class="sub">Set a PIN to secure your Alfred IDE access</p>
    ' . ($error ? '<div class="error-msg">' . htmlspecialchars($error) . '</div>' : '') . '
    <form method="POST" action="/alfred-ide-auth.php?action=pin-setup" class="pin-form">
      <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="8" placeholder="Enter 4-8 digit PIN" required autofocus class="pin-input">
      <input type="password" name="pin_confirm" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="8" placeholder="Confirm PIN" required class="pin-input">
      <button type="submit" class="submit-btn">SET PIN &amp; ENTER</button>
    </form>
    ');
}

// ── Render: PIN prompt ──────────────────────────────────────────────────────

function ideShowPinPrompt($error = '') {
    $name = htmlspecialchars($_SESSION['ide_google_name'] ?? 'User');
    $avatar = htmlspecialchars($_SESSION['ide_google_avatar'] ?? '/assets/images/alfred-portrait.png');
    echo idePageShell('Enter PIN', '
    <img src="' . $avatar . '" alt="" class="portrait user-avatar" onerror="this.src=\'/assets/images/alfred-portrait.png\'">
    <h1>' . $name . '</h1>
    <p class="sub">Enter your PIN to access Alfred IDE</p>
    ' . ($error ? '<div class="error-msg">' . htmlspecialchars($error) . '</div>' : '') . '
    <form method="POST" action="/alfred-ide-auth.php?action=pin-verify" class="pin-form">
      <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="8" placeholder="Enter PIN" required autofocus class="pin-input">
      <button type="submit" class="submit-btn">ENGAGE</button>
    </form>
    <a href="/alfred-ide-auth.php" class="switch-link">Sign in with a different account</a>
    ');
}

// ── Page shell ──────────────────────────────────────────────────────────────

function idePageShell($title, $content) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Alfred IDE — ' . htmlspecialchars($title) . '</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: linear-gradient(135deg, #0d1117 0%, #1a1a2e 50%, #16213e 100%);
    color: #e0e0e0;
    font-family: "Segoe UI", system-ui, sans-serif;
    display: flex; align-items: center; justify-content: center;
    min-height: 100vh; padding: 20px;
  }
  .card {
    background: rgba(22, 33, 62, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(226, 179, 64, 0.15);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 40px rgba(226,179,64,0.05);
    max-width: 440px; width: 100%;
    padding: 44px 40px;
    text-align: center;
  }
  .portrait {
    width: 96px; height: 96px; border-radius: 50%;
    border: 3px solid #e2b340;
    box-shadow: 0 0 30px rgba(226,179,64,0.25);
    object-fit: cover; display: block; margin: 0 auto 20px;
  }
  .user-avatar { border-color: #3b82f6; box-shadow: 0 0 30px rgba(59,130,246,0.25); }
  h1 {
    color: #e2b340; font-size: 1.5rem; font-weight: 300;
    letter-spacing: 3px; text-transform: uppercase; margin-bottom: 6px;
  }
  .sub { color: #8b9dc3; font-size: 0.9rem; margin-bottom: 28px; }
  .error-msg {
    background: rgba(229,62,62,0.12); border: 1px solid rgba(229,62,62,0.3);
    border-radius: 8px; color: #fc8181; padding: 10px 16px;
    font-size: 0.85rem; margin-bottom: 20px;
  }
  .auth-tabs {
    display: flex; gap: 0; margin-bottom: 20px;
    border: 1px solid rgba(226,179,64,0.2); border-radius: 8px; overflow: hidden;
  }
  .auth-tab {
    flex: 1; padding: 12px; background: transparent; border: none;
    color: #8b9dc3; font-size: 0.85rem; font-weight: 600;
    cursor: pointer; transition: all 0.2s; letter-spacing: 1px;
  }
  .auth-tab.active {
    background: rgba(226,179,64,0.15); color: #e2b340;
  }
  .auth-tab:hover:not(.active) { background: rgba(255,255,255,0.03); }
  .auth-form { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
  .auth-input {
    background: rgba(13,17,23,0.6); border: 1px solid rgba(226,179,64,0.2);
    border-radius: 8px; padding: 14px 16px; color: #e0e0e0;
    font-size: 0.95rem; outline: none; transition: border-color 0.2s;
  }
  .auth-input:focus { border-color: #e2b340; box-shadow: 0 0 15px rgba(226,179,64,0.15); }
  .auth-input::placeholder { color: rgba(139,157,195,0.5); font-size: 0.85rem; }
  .divider {
    display: flex; align-items: center; gap: 12px; margin: 12px 0 16px;
    color: #4a5568; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
  }
  .divider::before, .divider::after {
    content: ""; flex: 1; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(226,179,64,0.15), transparent);
  }
  .google-btn {
    display: inline-flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.06); color: #8b9dc3; text-decoration: none;
    font-size: 0.85rem; font-weight: 500;
    padding: 12px 24px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.2s;
    margin-bottom: 20px;
  }
  .google-btn:hover { background: rgba(255,255,255,0.1); color: #e0e0e0; border-color: rgba(255,255,255,0.2); }
  .pin-form { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
  .pin-input {
    background: rgba(13,17,23,0.6); border: 1px solid rgba(226,179,64,0.2);
    border-radius: 8px; padding: 16px; color: #e0e0e0;
    font-size: 1.2rem; text-align: center; letter-spacing: 8px;
    outline: none; transition: border-color 0.2s;
  }
  .pin-input:focus { border-color: #e2b340; box-shadow: 0 0 15px rgba(226,179,64,0.15); }
  .pin-input::placeholder { letter-spacing: 1px; font-size: 0.85rem; color: rgba(139,157,195,0.5); }
  .submit-btn {
    background: linear-gradient(135deg, #e2b340 0%, #c49b2b 100%);
    border: none; border-radius: 8px; padding: 16px;
    color: #0d1117; font-weight: 700; font-size: 1rem;
    text-transform: uppercase; letter-spacing: 2px;
    cursor: pointer; transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(226,179,64,0.3);
  }
  .submit-btn:hover { background: linear-gradient(135deg, #f0c654, #d4a93a); transform: translateY(-1px); }
  .switch-link {
    color: #8b9dc3; font-size: 0.8rem; text-decoration: none;
    display: block; margin-top: 8px;
  }
  .switch-link:hover { color: #e2b340; }
  .footer-note { color: #4a5568; font-size: 0.75rem; margin-top: 4px; }
  .gsm-brand {
    display: flex; align-items: center; justify-content: center; gap: 12px;
    margin-bottom: 20px; padding-bottom: 16px;
    border-bottom: 1px solid rgba(226,179,64,0.1);
  }
  .gsm-logo { flex-shrink: 0; }
  .gsm-title { text-align: left; line-height: 1.2; }
  .gsm-name { display: block; color: #e2b340; font-size: 1.15rem; font-weight: 700; letter-spacing: 1px; }
  .gsm-sub { display: block; color: #8b9dc3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 3px; }
  .gsm-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(226,179,64,0.06); border: 1px solid rgba(226,179,64,0.12);
    border-radius: 20px; padding: 6px 16px; margin-top: 16px; margin-bottom: 8px;
    font-size: 0.75rem; color: #8b9dc3;
  }
  .shield-icon { font-size: 1rem; }
</style>
</head>
<body>
  <div class="card">' . $content . '</div>
</body>
</html>';
}

function ideError($msg) {
    return idePageShell('Error', '
    <img src="/assets/images/alfred-portrait.png" alt="Alfred" class="portrait">
    <h1 style="color:#e53e3e">Error</h1>
    <p class="sub">' . htmlspecialchars($msg) . '</p>
    <a href="/alfred-ide-auth.php" class="google-btn" style="background:#e2b340;color:#0d1117;">Try Again</a>
    ');
}
