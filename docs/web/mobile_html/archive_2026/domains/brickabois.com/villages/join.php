<?php
/**
 * Join Village - One-Click Join Flow
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log
ini_set('log_errors', 1);

require_once dirname(__DIR__, 2) . '/private_html/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$slug = $_GET['slug'] ?? '';
$currentUser = getCurrentUser();

if (!$currentUser) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$db = getDBConnection();

// Get village
$villageStmt = $db->prepare("
    SELECT v.*, COUNT(DISTINCT vm.user_id) as member_count
    FROM villages v
    LEFT JOIN village_members vm ON v.id = vm.village_id
    WHERE v.slug = ? AND v.status IN ('active', 'forming')
    GROUP BY v.id
");
try {
    $villageStmt->execute([$slug]);
    $village = $villageStmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching village: " . $e->getMessage());
    die("Error loading village. Please try again.");
}

if (!$village) {
    error_log("Village not found for slug: " . $slug);
    die("Village not found: " . htmlspecialchars($slug) . ". <a href='/land'>Go back to villages</a>");
}

// Check if already member
$memberCheck = $db->prepare("SELECT id, role FROM village_members WHERE village_id = ? AND user_id = ?");
$memberCheck->execute([$village['id'], $currentUser['id']]);
$existing_member = $memberCheck->fetch();

$success = '';
$error = '';

// Handle join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_village'])) {
    if ($existing_member) {
        $error = $lang === 'fr' ? 'Vous êtes déjà membre de ce village' : 'You are already a member of this village';
    } else {
        try {
            // Determine role - admins can join as steward if village has no steward, otherwise as member
            $stewardCheck = $db->prepare("SELECT id FROM village_members WHERE village_id = ? AND role = 'steward' LIMIT 1");
            $stewardCheck->execute([$village['id']]);
            $hasSteward = $stewardCheck->fetch();
            
            // If no steward and user is admin, make them steward. Otherwise member.
            $userRole = $currentUser['role'] ?? 'citizen';
            $role = (!$hasSteward && $userRole === 'admin') ? 'steward' : 'member';
            
            // Double-check we're not already a member (race condition protection)
            $doubleCheck = $db->prepare("SELECT id FROM village_members WHERE village_id = ? AND user_id = ? LIMIT 1");
            $doubleCheck->execute([$village['id'], $currentUser['id']]);
            if ($doubleCheck->fetch()) {
                $error = $lang === 'fr' ? 'Vous êtes déjà membre de ce village' : 'You are already a member of this village';
                $memberCheck->execute([$village['id'], $currentUser['id']]);
                $existing_member = $memberCheck->fetch();
            } else {
                $joinStmt = $db->prepare("INSERT INTO village_members (village_id, user_id, role, joined_at) VALUES (?, ?, ?, NOW())");
                $joinStmt->execute([$village['id'], $currentUser['id'], $role]);
                
                // If admin became steward, update village steward_id
                if ($role === 'steward' && !$village['steward_id']) {
                    $updateStmt = $db->prepare("UPDATE villages SET steward_id = ? WHERE id = ?");
                    $updateStmt->execute([$currentUser['id'], $village['id']]);
                }
                
                $success = $lang === 'fr' ? 'Vous avez rejoint le village avec succès!' : 'Successfully joined the village!';
                $existing_member = ['role' => $role];
                
                // Redirect after 2 seconds
                header('Refresh: 2; url=/land/village/' . $slug);
            }
        } catch (PDOException $e) {
            error_log("Join village error: " . $e->getMessage());
            // Check for duplicate entry error
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = $lang === 'fr' ? 'Vous êtes déjà membre de ce village' : 'You are already a member of this village';
                // Refresh member check
                $memberCheck->execute([$village['id'], $currentUser['id']]);
                $existing_member = $memberCheck->fetch();
            } else {
                $error = $lang === 'fr' ? 'Erreur lors de l\'adhésion. Veuillez réessayer.' : 'Error joining village. Please try again.';
            }
        }
    }
}

// Handle leave request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_village']) && $existing_member) {
    if ($existing_member['role'] === 'steward') {
        $error = $lang === 'fr' ? 'Les gérants ne peuvent pas quitter le village' : 'Stewards cannot leave the village';
    } else {
        $leaveStmt = $db->prepare("DELETE FROM village_members WHERE village_id = ? AND user_id = ?");
        $leaveStmt->execute([$village['id'], $currentUser['id']]);
        $success = $lang === 'fr' ? 'Vous avez quitté le village' : 'You have left the village';
        $existing_member = false;
        header('Refresh: 2; url=/land/village/' . $slug);
    }
}

$translations = [
    'en' => [
        'title' => 'Join Village',
        'village_name' => 'Village',
        'members' => 'members',
        'description' => 'Description',
        'location' => 'Location',
        'join_btn' => 'Join Village',
        'leave_btn' => 'Leave Village',
        'already_member' => 'You are already a member',
        'member_since' => 'Member since',
        'back' => '← Back to Village',
        'confirm_join' => 'Are you sure you want to join this village?',
        'confirm_leave' => 'Are you sure you want to leave this village?',
    ],
    'fr' => [
        'title' => 'Rejoindre le Village',
        'village_name' => 'Village',
        'members' => 'membres',
        'description' => 'Description',
        'location' => 'Emplacement',
        'join_btn' => 'Rejoindre le Village',
        'leave_btn' => 'Quitter le Village',
        'already_member' => 'Vous êtes déjà membre',
        'member_since' => 'Membre depuis',
        'back' => '← Retour au Village',
        'confirm_join' => 'Êtes-vous sûr de vouloir rejoindre ce village?',
        'confirm_leave' => 'Êtes-vous sûr de vouloir quitter ce village?',
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title']) ?> - <?= htmlspecialchars($lang === 'fr' && $village['name_fr'] ? $village['name_fr'] : $village['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/navbar-modern.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <script>
        // Initialize theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            const colorTheme = localStorage.getItem('colorTheme') || 'forest';
            document.documentElement.setAttribute('data-color-theme', colorTheme);
        })();
    </script>
    <style>
        .join-container {
            max-width: 600px;
            margin: 120px auto 3rem;
            padding: 0 2rem;
        }
        .join-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        .village-preview {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--color-border);
        }
        .village-preview h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--color-accent);
        }
        .village-preview .village-meta {
            color: var(--color-text-secondary);
            margin: 1rem 0;
        }
        .join-form {
            text-align: center;
        }
        .join-form form {
            display: inline-block;
        }
        body {
            background: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <?php 
    // Initialize theme immediately to prevent black screen
    ?>
    <script>
        // Initialize theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            const colorTheme = localStorage.getItem('colorTheme') || 'forest';
            document.documentElement.setAttribute('data-color-theme', colorTheme);
        })();
    </script>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="join-container">
        <a href="/land/village/<?= htmlspecialchars($slug) ?>" style="color: var(--color-accent); text-decoration: none; margin-bottom: 2rem; display: inline-block;">
            <?= htmlspecialchars($t['back']) ?>
        </a>

        <div class="join-card">
            <?php if ($error): ?>
                <div class="error" style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 8px; color: #ef4444;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success" style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.5); border-radius: 8px; color: #10b981;">
                    <?= htmlspecialchars($success) ?>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem;"><?= $lang === 'fr' ? 'Redirection...' : 'Redirecting...' ?></p>
                </div>
            <?php endif; ?>

            <div class="village-preview">
                <h2><?= htmlspecialchars(!empty($village['name']) ? ($lang === 'fr' && !empty($village['name_fr']) ? $village['name_fr'] : $village['name']) : 'Village') ?></h2>
                <div class="village-meta">
                    <p>👥 <?= $village['member_count'] ?> <?= htmlspecialchars($t['members']) ?></p>
                    <?php if ($village['region']): ?>
                        <p>📍 <?= htmlspecialchars($village['region']) ?>, <?= htmlspecialchars($village['country'] ?? 'Canada') ?></p>
                    <?php endif; ?>
                </div>
                <?php 
                $description = $lang === 'fr' && !empty($village['description_fr']) ? $village['description_fr'] : (!empty($village['description']) ? $village['description'] : '');
                if (!empty($description)): ?>
                    <p style="color: var(--color-text-secondary); line-height: 1.6; margin-top: 1rem;">
                        <?= htmlspecialchars(substr($description, 0, 200)) ?>
                        <?= strlen($description) > 200 ? '...' : '' ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="join-form">
                <?php if ($existing_member): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <p style="color: var(--color-accent); font-weight: 600; margin-bottom: 1rem;">
                            ✓ <?= htmlspecialchars($t['already_member']) ?>
                        </p>
                        <?php
                        $memberInfo = $db->prepare("SELECT joined_at FROM village_members WHERE village_id = ? AND user_id = ?");
                        $memberInfo->execute([$village['id'], $currentUser['id']]);
                        $memberData = $memberInfo->fetch();
                        ?>
                        <p style="color: var(--color-text-secondary); font-size: 0.9rem;">
                            <?= htmlspecialchars($t['member_since']) ?> <?= date('M j, Y', strtotime($memberData['joined_at'])) ?>
                        </p>
                    </div>
                    <?php if ($existing_member['role'] !== 'steward'): ?>
                        <form method="POST" onsubmit="return confirm('<?= htmlspecialchars($t['confirm_leave']) ?>')">
                            <button type="submit" name="leave_village" class="btn btn-secondary" style="padding: 1rem 2rem; font-size: 1rem;">
                                <?= htmlspecialchars($t['leave_btn']) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="POST" onsubmit="return confirm('<?= htmlspecialchars($t['confirm_join']) ?>')">
                        <button type="submit" name="join_village" class="btn btn-primary" style="padding: 1.25rem 3rem; font-size: 1.1rem; font-weight: 600;">
                            <?= htmlspecialchars($t['join_btn']) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

