<?php
/**
 * Create Proposal - Governance Proposal Creation
 */

require_once dirname(__DIR__, 2) . '/private_html/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$currentUser = getCurrentUser();
$db = getDBConnection();

$error = '';
$success = '';

// Get user's villages for selection
$userVillagesStmt = $db->prepare("
    SELECT v.* FROM villages v
    JOIN village_members vm ON v.id = vm.village_id
    WHERE vm.user_id = ? AND v.status IN ('active', 'forming')
    ORDER BY v.name
");
$userVillagesStmt->execute([$currentUser['id']]);
$userVillages = $userVillagesStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_proposal'])) {
    $title = trim($_POST['title'] ?? '');
    $title_fr = trim($_POST['title_fr'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $description_fr = trim($_POST['description_fr'] ?? '');
    $village_id = !empty($_POST['village_id']) ? (int)$_POST['village_id'] : null;
    $proposal_type = $_POST['proposal_type'] ?? 'governance';
    
    // Validation
    if (empty($title)) {
        $error = $lang === 'fr' ? 'Le titre est requis' : 'Title is required';
    } elseif (empty($description)) {
        $error = $lang === 'fr' ? 'La description est requise' : 'Description is required';
    } elseif (strlen($title) > 255) {
        $error = $lang === 'fr' ? 'Le titre est trop long (max 255 caractères)' : 'Title is too long (max 255 characters)';
    } else {
        try {
            // Create proposal
            $insertStmt = $db->prepare("
                INSERT INTO proposals (user_id, village_id, title, title_fr, description, description_fr, proposal_type, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            $insertStmt->execute([
                $currentUser['id'],
                $village_id,
                $title,
                $title_fr ?: null,
                $description,
                $description_fr ?: null,
                $proposal_type
            ]);
            
            $proposalId = $db->lastInsertId();
            $success = $lang === 'fr' ? 'Proposition créée avec succès!' : 'Proposal created successfully!';
            
            // Redirect to proposal page after 2 seconds
            header('Refresh: 2; url=/ledger/proposal/' . $proposalId);
        } catch (PDOException $e) {
            error_log("Create proposal error: " . $e->getMessage());
            $error = $lang === 'fr' ? 'Erreur lors de la création de la proposition. Veuillez réessayer.' : 'Error creating proposal. Please try again.';
        }
    }
}

$translations = [
    'en' => [
        'title' => 'Create Proposal',
        'subtitle' => 'Submit a governance proposal',
        'proposal_title' => 'Proposal Title',
        'proposal_title_fr' => 'Proposal Title (French)',
        'proposal_description' => 'Proposal Description',
        'proposal_description_fr' => 'Proposal Description (French)',
        'proposal_type' => 'Proposal Type',
        'governance' => 'Governance',
        'treasury' => 'Treasury',
        'village' => 'Village',
        'select_village' => 'Select Village (Optional)',
        'all_villages' => 'All Villages',
        'create_btn' => 'Create Proposal',
        'cancel' => 'Cancel',
        'title_placeholder' => 'Enter proposal title...',
        'description_placeholder' => 'Describe your proposal in detail...',
    ],
    'fr' => [
        'title' => 'Créer une Proposition',
        'subtitle' => 'Soumettre une proposition de gouvernance',
        'proposal_title' => 'Titre de la Proposition',
        'proposal_title_fr' => 'Titre de la Proposition (Français)',
        'proposal_description' => 'Description de la Proposition',
        'proposal_description_fr' => 'Description de la Proposition (Français)',
        'proposal_type' => 'Type de Proposition',
        'governance' => 'Gouvernance',
        'treasury' => 'Trésorerie',
        'village' => 'Village',
        'select_village' => 'Sélectionner un Village (Optionnel)',
        'all_villages' => 'Tous les Villages',
        'create_btn' => 'Créer la Proposition',
        'cancel' => 'Annuler',
        'title_placeholder' => 'Entrez le titre de la proposition...',
        'description_placeholder' => 'Décrivez votre proposition en détail...',
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
    <link rel="stylesheet" href="/assets/css/themes.css">
    <style>
        body {
            padding-top: 80px;
        }
        .create-proposal-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .create-proposal-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        .create-proposal-header h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            color: var(--color-accent);
            margin-bottom: 0.5rem;
        }
        .create-proposal-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--color-text);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 1rem;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            color: var(--color-text);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: var(--color-text-secondary);
            font-size: 0.9rem;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        .btn-primary {
            padding: 1rem 2rem;
            background: var(--color-accent);
            border: none;
            border-radius: 10px;
            color: var(--color-bg);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: var(--color-accent-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 165, 116, 0.3);
        }
        .btn-secondary {
            padding: 1rem 2rem;
            background: var(--color-bg-light);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            color: var(--color-text);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background: var(--color-bg-card);
            border-color: var(--color-accent);
        }
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #ef4444;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #10b981;
        }
        @media (max-width: 768px) {
            .create-proposal-container {
                padding: 0 1rem;
            }
            .create-proposal-card {
                padding: 2rem 1.5rem;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn-primary, .btn-secondary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="create-proposal-container">
        <div class="create-proposal-header">
            <h1><?= htmlspecialchars($t['title']) ?></h1>
            <p style="color: var(--color-text-secondary);"><?= htmlspecialchars($t['subtitle']) ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
                <p style="margin-top: 0.5rem; font-size: 0.9rem;"><?= $lang === 'fr' ? 'Redirection...' : 'Redirecting...' ?></p>
            </div>
        <?php endif; ?>

        <div class="create-proposal-card">
            <form method="POST">
                <div class="form-group">
                    <label for="title"><?= htmlspecialchars($t['proposal_title']) ?> *</label>
                    <input type="text" id="title" name="title" placeholder="<?= htmlspecialchars($t['title_placeholder']) ?>" required maxlength="255" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="title_fr"><?= htmlspecialchars($t['proposal_title_fr']) ?></label>
                    <input type="text" id="title_fr" name="title_fr" placeholder="<?= htmlspecialchars($t['title_placeholder']) ?>" maxlength="255" value="<?= htmlspecialchars($_POST['title_fr'] ?? '') ?>">
                    <small><?= $lang === 'fr' ? 'Optionnel - Titre en français' : 'Optional - French title' ?></small>
                </div>

                <div class="form-group">
                    <label for="description"><?= htmlspecialchars($t['proposal_description']) ?> *</label>
                    <textarea id="description" name="description" placeholder="<?= htmlspecialchars($t['description_placeholder']) ?>" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="description_fr"><?= htmlspecialchars($t['proposal_description_fr']) ?></label>
                    <textarea id="description_fr" name="description_fr" placeholder="<?= htmlspecialchars($t['description_placeholder']) ?>"><?= htmlspecialchars($_POST['description_fr'] ?? '') ?></textarea>
                    <small><?= $lang === 'fr' ? 'Optionnel - Description en français' : 'Optional - French description' ?></small>
                </div>

                <div class="form-group">
                    <label for="proposal_type"><?= htmlspecialchars($t['proposal_type']) ?></label>
                    <select id="proposal_type" name="proposal_type">
                        <option value="governance" <?= ($_POST['proposal_type'] ?? 'governance') === 'governance' ? 'selected' : '' ?>><?= htmlspecialchars($t['governance']) ?></option>
                        <option value="treasury" <?= ($_POST['proposal_type'] ?? '') === 'treasury' ? 'selected' : '' ?>><?= htmlspecialchars($t['treasury']) ?></option>
                        <option value="policy" <?= ($_POST['proposal_type'] ?? '') === 'policy' ? 'selected' : '' ?>><?= $lang === 'fr' ? 'Politique' : 'Policy' ?></option>
                        <option value="project" <?= ($_POST['proposal_type'] ?? '') === 'project' ? 'selected' : '' ?>><?= $lang === 'fr' ? 'Projet' : 'Project' ?></option>
                    </select>
                </div>

                <?php if (!empty($userVillages)): ?>
                    <div class="form-group">
                        <label for="village_id"><?= htmlspecialchars($t['select_village']) ?></label>
                        <select id="village_id" name="village_id">
                            <option value=""><?= htmlspecialchars($t['all_villages']) ?></option>
                            <?php foreach ($userVillages as $village): ?>
                                <option value="<?= $village['id'] ?>" <?= (isset($_POST['village_id']) && $_POST['village_id'] == $village['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lang === 'fr' && $village['name_fr'] ? $village['name_fr'] : $village['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small><?= $lang === 'fr' ? 'Si sélectionné, la proposition sera limitée à ce village' : 'If selected, proposal will be limited to this village' ?></small>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="/ledger" class="btn-secondary"><?= htmlspecialchars($t['cancel']) ?></a>
                    <button type="submit" name="create_proposal" class="btn-primary"><?= htmlspecialchars($t['create_btn']) ?></button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

