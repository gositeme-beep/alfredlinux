<?php
/**
 * Registration Page
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $display_name = trim($_POST['display_name'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = $lang === 'fr' ? 'Veuillez remplir tous les champs requis' : 'Please fill in all required fields';
    } elseif (strlen($username) < 3) {
        $error = $lang === 'fr' ? 'Le nom d\'utilisateur doit contenir au moins 3 caractères' : 'Username must be at least 3 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $lang === 'fr' ? 'Email invalide' : 'Invalid email';
    } elseif (strlen($password) < 8) {
        $error = $lang === 'fr' ? 'Le mot de passe doit contenir au moins 8 caractères' : 'Password must be at least 8 characters';
    } elseif ($password !== $password_confirm) {
        $error = $lang === 'fr' ? 'Les mots de passe ne correspondent pas' : 'Passwords do not match';
    } else {
        $db = getDBConnection();
        
        // Check if username exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = $lang === 'fr' ? 'Ce nom d\'utilisateur est déjà pris' : 'Username already taken';
        } else {
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = $lang === 'fr' ? 'Cet email est déjà utilisé' : 'Email already in use';
            } else {
                // Create user
                $password_hash = hashPassword($password);
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password_hash, display_name, language_preference, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$username, $email, $password_hash, $display_name ?: $username, $lang]);
                
                $success = $lang === 'fr' 
                    ? 'Inscription réussie! Votre compte est en attente d\'activation.'
                    : 'Registration successful! Your account is pending activation.';
            }
        }
    }
}

$translations = [
    'en' => [
        'title' => 'Register',
        'username' => 'Username',
        'email' => 'Email',
        'display_name' => 'Display Name (optional)',
        'password' => 'Password',
        'password_confirm' => 'Confirm Password',
        'register_btn' => 'Register',
        'have_account' => 'Already have an account?',
        'login' => 'Login',
    ],
    'fr' => [
        'title' => 'Inscription',
        'username' => 'Nom d\'utilisateur',
        'email' => 'Email',
        'display_name' => 'Nom d\'affichage (optionnel)',
        'password' => 'Mot de passe',
        'password_confirm' => 'Confirmer le mot de passe',
        'register_btn' => 'S\'inscrire',
        'have_account' => 'Vous avez déjà un compte?',
        'login' => 'Se connecter',
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
                    <label for="username"><?= htmlspecialchars($t['username']) ?> *</label>
                    <input type="text" id="username" name="username" required autofocus minlength="3">
                </div>
                
                <div class="form-group">
                    <label for="email"><?= htmlspecialchars($t['email']) ?> *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="display_name"><?= htmlspecialchars($t['display_name']) ?></label>
                    <input type="text" id="display_name" name="display_name">
                </div>
                
                <div class="form-group">
                    <label for="password"><?= htmlspecialchars($t['password']) ?> *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="password_confirm"><?= htmlspecialchars($t['password_confirm']) ?> *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                
                <button type="submit" name="register" class="btn-primary" style="width: 100%;">
                    <?= htmlspecialchars($t['register_btn']) ?>
                </button>
            </form>
            
            <div class="auth-links">
                <p><?= htmlspecialchars($t['have_account']) ?> <a href="/login"><?= htmlspecialchars($t['login']) ?></a></p>
            </div>
        </div>
    </div>
</body>
</html>

