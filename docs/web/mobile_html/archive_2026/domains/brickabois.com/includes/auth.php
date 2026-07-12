<?php
/**
 * Authentication Helper Functions
 */

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDBConnection();
        // Check if pro_account column exists
        $hasProAccount = false;
        try {
            $columns = $db->query("SHOW COLUMNS FROM users LIKE 'pro_account'")->fetch();
            $hasProAccount = (bool)$columns;
        } catch (Exception $e) {
            // Column doesn't exist, continue without it
            $hasProAccount = false;
        }
        
        $proAccountField = $hasProAccount ? ', pro_account' : '';
        $stmt = $db->prepare("SELECT id, username, email, display_name, avatar_url, role, village_id, language_preference, status{$proAccountField} FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // If user doesn't exist, return null
        if (!$user) {
            return null;
        }
        
        // For non-active users, only allow if they're admin (for troubleshooting)
        if (isset($user['status']) && $user['status'] !== 'active' && $user['role'] !== 'admin') {
            return null;
        }
        
        // Log warning for non-active admins
        if (isset($user['status']) && $user['status'] !== 'active' && $user['role'] === 'admin') {
            error_log("User {$user['username']} (ID: {$user['id']}) has status '{$user['status']}', not 'active'");
        }
        
        // Add pro_account as false if column doesn't exist
        if ($user && !isset($user['pro_account'])) {
            $user['pro_account'] = 0;
        }
        
        return $user;
    } catch (Exception $e) {
        error_log("getCurrentUser error: " . $e->getMessage());
        return null;
    }
}

function isImpersonating() {
    startSession();
    return isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true;
}

function stopImpersonating() {
    startSession();
    if (isset($_SESSION['original_admin_id'])) {
        $_SESSION['user_id'] = $_SESSION['original_admin_id'];
        unset($_SESSION['impersonating']);
        unset($_SESSION['original_admin_id']);
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function loginUser($userId) {
    startSession();
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
    
    // Update last login
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}

function logoutUser() {
    startSession();
    session_destroy();
    header('Location: /');
    exit;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateCSRFToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /');
        exit;
    }
}

