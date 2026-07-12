<?php
/**
 * GoCodeMe Editor - Authentication API
 * Handles session authentication
 */

require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if this file is being accessed directly (as API) or included
$isDirectAccess = (basename($_SERVER['SCRIPT_FILENAME']) === 'auth.php');

if ($isDirectAccess) {
    // CORS headers for API
    header('Access-Control-Allow-Origin: https://gositeme.com');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

// Session already started by bootstrap
if (!defined('EDITOR_SESSION_LOADED') && $isDirectAccess) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Get current logged-in user
 */
function getCurrentUser() {
    
    // Check if user is logged in via session
    if (isset($_SESSION['uid']) && $_SESSION['uid'] > 0) {
        $userId = (int)$_SESSION['uid'];
        
        // Get user details from database
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                firstname, 
                lastname, 
                email, 
                companyname,
                status
            FROM clients 
            WHERE id = ? AND status = 'Active'
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check if user has active hosting (for premium features)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM services 
                WHERE userid = ? AND domainstatus = 'Active'
            ");
            $stmt->execute([$userId]);
            $hosting = $stmt->fetch();
            
            $user['has_hosting'] = $hosting['count'] > 0;
            $user['is_premium'] = $hosting['count'] > 0;
            
            // Get AI usage stats
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(ai_used_this_month, 0) as ai_used,
                    COALESCE(ai_monthly_limit, ?) as ai_limit
                FROM editor_user_settings 
                WHERE user_id = ?
            ");
            $defaultLimit = $user['is_premium'] ? AI_MONTHLY_LIMIT_PAID : AI_MONTHLY_LIMIT_FREE;
            $stmt->execute([$defaultLimit, $userId]);
            $settings = $stmt->fetch();
            
            if ($settings) {
                $user['ai_used'] = $settings['ai_used'];
                $user['ai_limit'] = $settings['ai_limit'];
            } else {
                $user['ai_used'] = 0;
                $user['ai_limit'] = $defaultLimit;
            }
            
            // Get project count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM editor_projects WHERE user_id = ?");
            $stmt->execute([$userId]);
            $projects = $stmt->fetch();
            
            $user['project_count'] = $projects['count'];
            $user['project_limit'] = $user['is_premium'] ? MAX_PROJECTS_PAID : MAX_PROJECTS_FREE;
            
            return $user;
        }
    }
    
    return null;
}

/**
 * Check if user can perform action
 */
function checkPermission($action = 'view') {
    $user = getCurrentUser();
    
    if (!REQUIRE_LOGIN && $action === 'view') {
        return ['guest' => true, 'can_save' => false];
    }
    
    if (!$user) {
        return null;
    }
    
    switch ($action) {
        case 'create_project':
            return $user['project_count'] < $user['project_limit'] ? $user : null;
        
        case 'use_ai':
            return $user['ai_used'] < $user['ai_limit'] ? $user : null;
        
        case 'publish':
            return $user['has_hosting'] ? $user : null;
        
        default:
            return $user;
    }
}

// Handle API routes - ONLY when accessed directly
if ($isDirectAccess) {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'status';
    
    switch ($action) {
        case 'status':
            // Get current auth status
            $user = getCurrentUser();
            if ($user) {
                jsonResponse([
                    'authenticated' => true,
                    'user' => [
                        'id' => $user['id'],
                        'name' => trim($user['firstname'] . ' ' . $user['lastname']),
                        'email' => $user['email'],
                        'is_premium' => $user['is_premium'],
                        'ai_used' => $user['ai_used'],
                        'ai_limit' => $user['ai_limit'],
                        'project_count' => $user['project_count'],
                        'project_limit' => $user['project_limit']
                    ]
                ]);
            } else {
                jsonResponse([
                    'authenticated' => false,
                    'guest_allowed' => ALLOW_GUEST_PREVIEW,
                    'login_url' => BILLING_URL . '/clientarea.php'
                ]);
            }
            break;
        
        case 'login':
            // Redirect to login
            $returnUrl = urlencode($_GET['return'] ?? '/editor/');
            header('Location: ' . BILLING_URL . '/clientarea.php?action=login&redirect=' . $returnUrl);
            exit;
            break;
        
        case 'logout':
            // Clear session and redirect
            session_destroy();
            header('Location: ' . BILLING_URL . '/logout.php');
            exit;
            break;
        
        case 'check_permission':
            $permAction = $_GET['permission'] ?? 'view';
            $result = checkPermission($permAction);
            jsonResponse([
                'allowed' => $result !== null,
                'reason' => $result === null ? 'Permission denied or limit reached' : null
            ]);
            break;
        
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}
