<?php
/**
 * Auth Gate — shared authentication check for protected pages.
 * Supports both custom sessions and legacy sessions.
 *
 * After including this file, the following variables are guaranteed:
 *   $clientId    — int  (clients.id)
 *   $clientName  — string
 *   $clientEmail — string
 *   $initials    — string (two-char uppercase)
 *
 * If the visitor is not authenticated through either system,
 * they are redirect to /?login=1 and execution stops.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Shared database configuration
require_once __DIR__ . '/db-config.inc.php';

$authenticated = false;
$clientId    = null;
$clientName  = null;
$clientEmail = null;

// ── Method 1: Custom auth.php session ──────────────────────────────
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
    && !empty($_SESSION['client_id'])) {
    $clientId    = (int) $_SESSION['client_id'];
    $clientName  = $_SESSION['client_name']  ?? '';
    $clientEmail = $_SESSION['client_email'] ?? '';
    $authenticated = true;
    
    // Sync uid/username for Alfred widget compatibility
    if (empty($_SESSION['uid']))      $_SESSION['uid']      = $clientId;
    if (empty($_SESSION['username'])) $_SESSION['username'] = $clientName;
}

// ── Method 2: SSO token authentication (signed URL parameter) ──────────
if (!$authenticated && !empty($_GET['sso'])) {
    try {
        $decoded = base64_decode($_GET['sso'], true);
        if ($decoded) {
            $parts = explode('|', $decoded, 3);
            if (count($parts) === 3) {
                list($ssoClientId, $ssoTimestamp, $ssoSignature) = $parts;
                $ssoSecret  = getenv('SSO_SECRET') ?: '';
                if (!$ssoSecret) {
                    throw new Exception('SSO_SECRET not configured');
                }
                $expected   = hash_hmac('sha256', $ssoClientId . '|' . $ssoTimestamp, $ssoSecret);
                // Verify signature and that token is < 5 minutes old
                if (hash_equals($expected, $ssoSignature) && (time() - (int)$ssoTimestamp) < 300) {
                    try {
                        $pdo = getSharedDB();
                        $stmt = $pdo->prepare(
                            "SELECT id, firstname, lastname, email FROM clients WHERE id = ? AND status = 'Active' LIMIT 1"
                        );
                        $stmt->execute([(int) $ssoClientId]);
                        $client = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($client) {
                            $clientId    = (int) $client['id'];
                            $clientName  = trim($client['firstname'] . ' ' . $client['lastname']);
                            $clientEmail = $client['email'];
                            $_SESSION['client_id']    = $clientId;
                            $_SESSION['client_name']  = $clientName;
                            $_SESSION['client_email'] = $clientEmail;
                            $_SESSION['logged_in']    = true;
                            $_SESSION['uid']          = $clientId;
                            $_SESSION['username']     = $clientName;
                            session_regenerate_id(true);
                            $authenticated = true;

                            // Strip SSO token from URL to prevent reuse / bookmarking
                            $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
                            $params = $_GET;
                            unset($params['sso']);
                            if ($params) $cleanUrl .= '?' . http_build_query($params);
                            header('Location: ' . $cleanUrl);
                            exit;
                        }
                    } catch (Exception $e) {
                        error_log('auth-gate SSO DB error: ' . $e->getMessage());
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('auth-gate SSO token error: ' . $e->getMessage());
    }
}

// ── Method 3: Legacy uid session ──
if (!$authenticated && !empty($_SESSION['uid'])) {
    try {
        $pdo = getSharedDB();

        $stmt = $pdo->prepare(
            "SELECT id, firstname, lastname, email FROM clients WHERE id = ? AND status = 'Active' LIMIT 1"
        );
        $stmt->execute([(int) $_SESSION['uid']]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($client) {
            $clientId    = (int) $client['id'];
            $clientName  = trim($client['firstname'] . ' ' . $client['lastname']);
            $clientEmail = $client['email'];

            $_SESSION['client_id']    = $clientId;
            $_SESSION['client_name']  = $clientName;
            $_SESSION['client_email'] = $clientEmail;
            $_SESSION['logged_in']    = true;
            $_SESSION['uid']          = $clientId;
            $_SESSION['username']     = $clientName;

            $authenticated = true;
        }
    } catch (Exception $e) {
        error_log('auth-gate legacy uid fallback error: ' . $e->getMessage());
    }
}

// ── Not authenticated → redirect ───────────────────────────────────
if (!$authenticated) {
    header('Location: /?login=1');
    exit;
}

// ── Session Timeout Checks ─────────────────────────────────────────
// Absolute timeout: 24 hours since login
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
    session_unset();
    session_destroy();
    header('Location: /?login=1&reason=expired');
    exit;
}

// Idle timeout: 2 hours since last activity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
    session_unset();
    session_destroy();
    header('Location: /?login=1&reason=idle');
    exit;
}

// Refresh last activity timestamp
$_SESSION['last_activity'] = time();

// Set login_time if not already set (for sessions created before this code)
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// Derive initials (used by both dashboard and voice-portal)
$initials = strtoupper(
    substr($clientName, 0, 1)
    . substr(strstr($clientName, ' ') ?: '', 1, 1)
);

// ── Security Vault: post-login PIN verification for all users ──────
require_once __DIR__ . '/commander-vault.inc.php';
