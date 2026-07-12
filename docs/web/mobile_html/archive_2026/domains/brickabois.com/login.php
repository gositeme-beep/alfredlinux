<?php
/**
 * Login Page
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = $lang === 'fr' ? 'Veuillez remplir tous les champs' : 'Please fill in all fields';
    } else {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, username, password_hash, status FROM users WHERE (username = ? OR email = ?)");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password_hash'])) {
            if ($user['status'] === 'active') {
                loginUser($user['id']);
                $redirect = $_GET['redirect'] ?? '/dashboard.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = $lang === 'fr' ? 'Votre compte n\'est pas encore activé' : 'Your account is not yet activated';
            }
        } else {
            $error = $lang === 'fr' ? 'Nom d\'utilisateur ou mot de passe incorrect' : 'Incorrect username or password';
        }
    }
}

$translations = [
    'en' => [
        'title' => 'Login',
        'username' => 'Username or Email',
        'password' => 'Password',
        'login_btn' => 'Login',
        'no_account' => "Don't have an account?",
        'register' => 'Register',
        'forgot_password' => 'Forgot password?',
    ],
    'fr' => [
        'title' => 'Connexion',
        'username' => 'Nom d\'utilisateur ou Email',
        'password' => 'Mot de passe',
        'login_btn' => 'Se connecter',
        'no_account' => 'Vous n\'avez pas de compte?',
        'register' => 'S\'inscrire',
        'forgot_password' => 'Mot de passe oublié?',
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title']) ?> - Free Village Network</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/navbar-modern.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            padding-top: 100px;
        }
        .auth-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 3rem;
            max-width: 400px;
            width: 100%;
        }
        .auth-card h1 {
            margin-bottom: 2rem;
            text-align: center;
            color: var(--color-accent);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-text-secondary);
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 6px;
            color: var(--color-text);
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--color-accent);
        }
        .error, .success {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #ef4444;
        }
        .success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #10b981;
        }
        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--color-text-secondary);
        }
        .auth-links a {
            color: var(--color-accent);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="auth-container">
        <div class="auth-card">
            <h1><?= htmlspecialchars($t['title']) ?></h1>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username"><?= htmlspecialchars($t['username']) ?></label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password"><?= htmlspecialchars($t['password']) ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn-primary" style="width: 100%;">
                    <?= htmlspecialchars($t['login_btn']) ?>
                </button>
            </form>
            
            <div class="auth-links">
                <p><?= htmlspecialchars($t['no_account']) ?> <a href="/register"><?= htmlspecialchars($t['register']) ?></a></p>
            </div>
        </div>
    </div>
</body>
</html>

