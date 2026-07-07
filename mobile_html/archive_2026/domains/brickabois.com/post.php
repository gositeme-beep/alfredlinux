<?php
/**
 * Individual Post View with Comments and Reactions
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$currentUser = getCurrentUser();
$db = getDBConnection();

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get post
$stmt = $db->prepare("
    SELECT p.*, u.username, u.display_name, u.avatar_url, v.name as village_name, v.slug as village_slug
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN villages v ON p.village_id = v.id
    WHERE p.id = ? AND p.deleted_at IS NULL
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: /commons');
    exit;
}

// Handle comment creation
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_comment']) && isLoggedIn()) {
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) {
        $error = $lang === 'fr' ? 'Le commentaire ne peut pas être vide' : 'Comment cannot be empty';
    } else {
        $stmt = $db->prepare("
            INSERT INTO comments (post_id, user_id, content, language)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$post_id, $currentUser['id'], $content, $lang]);
        $success = $lang === 'fr' ? 'Commentaire ajouté!' : 'Comment added!';
        header('Location: /post?id=' . $post_id);
        exit;
    }
}

// Handle reaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['react']) && isLoggedIn()) {
    $reaction_type = $_POST['reaction_type'] ?? 'like';
    $target_type = 'post';
    $target_id = $post_id;
    
    // Check if user already reacted
    $checkStmt = $db->prepare("
        SELECT id FROM reactions 
        WHERE user_id = ? AND target_type = ? AND target_id = ? AND reaction_type = ?
    ");
    $checkStmt->execute([$currentUser['id'], $target_type, $target_id, $reaction_type]);
    
    if ($checkStmt->fetch()) {
        // Remove reaction
        $delStmt = $db->prepare("DELETE FROM reactions WHERE user_id = ? AND target_type = ? AND target_id = ? AND reaction_type = ?");
        $delStmt->execute([$currentUser['id'], $target_type, $target_id, $reaction_type]);
    } else {
        // Add reaction
        $insStmt = $db->prepare("
            INSERT INTO reactions (user_id, target_type, target_id, reaction_type)
            VALUES (?, ?, ?, ?)
        ");
        $insStmt->execute([$currentUser['id'], $target_type, $target_id, $reaction_type]);
    }
    header('Location: /post?id=' . $post_id);
    exit;
}

// Get reactions for this post
$reactionsStmt = $db->prepare("
    SELECT reaction_type, COUNT(*) as count
    FROM reactions
    WHERE target_type = 'post' AND target_id = ?
    GROUP BY reaction_type
");
$reactionsStmt->execute([$post_id]);
$reactions = $reactionsStmt->fetchAll();
$reactions_by_type = [];
foreach ($reactions as $r) {
    $reactions_by_type[$r['reaction_type']] = $r['count'];
}

// Check if current user has reacted
$user_reactions = [];
if (isLoggedIn()) {
    $userReactionsStmt = $db->prepare("
        SELECT reaction_type FROM reactions
        WHERE user_id = ? AND target_type = 'post' AND target_id = ?
    ");
    $userReactionsStmt->execute([$currentUser['id'], $post_id]);
    $user_reactions = array_column($userReactionsStmt->fetchAll(), 'reaction_type');
}

// Get comments
$commentsStmt = $db->prepare("
    SELECT c.*, u.username, u.display_name, u.avatar_url,
    (SELECT COUNT(*) FROM reactions WHERE target_type = 'comment' AND target_id = c.id) as reaction_count
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ? AND c.deleted_at IS NULL
    ORDER BY c.created_at ASC
");
$commentsStmt->execute([$post_id]);
$comments = $commentsStmt->fetchAll();

$translations = [
    'en' => [
        'title' => 'Post',
        'comments' => 'Comments',
        'add_comment' => 'Add Comment',
        'comment_placeholder' => 'Write a comment...',
        'post_comment' => 'Post Comment',
        'reactions' => 'Reactions',
        'no_comments' => 'No comments yet. Be the first!',
        'login_to_comment' => 'Login to comment',
        'back_to_feed' => '← Back to Feed',
    ],
    'fr' => [
        'title' => 'Publication',
        'comments' => 'Commentaires',
        'add_comment' => 'Ajouter un Commentaire',
        'comment_placeholder' => 'Écrire un commentaire...',
        'post_comment' => 'Publier',
        'reactions' => 'Réactions',
        'no_comments' => 'Aucun commentaire pour le moment. Soyez le premier!',
        'login_to_comment' => 'Connectez-vous pour commenter',
        'back_to_feed' => '← Retour au Fil',
    ]
];

$t = $translations[$lang];

// Reaction types with emojis (matching database enum)
$reaction_types = [
    'like' => '👍',
    'love' => '❤️',
    'support' => '🤝',
    'celebrate' => '🎉'
];
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
    <link rel="stylesheet" href="/assets/css/commons.css">
    <style>
        .post-detail {
            max-width: 800px;
            margin: 100px auto 2rem;
            padding: 0 2rem;
        }
        .post-card-full {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .reactions-bar {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--color-bg-light);
            border-radius: 12px;
            flex-wrap: wrap;
        }
        .reaction-btn {
            background: transparent;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }
        .reaction-btn:hover {
            background: var(--color-bg-card);
            border-color: var(--color-accent);
            transform: translateY(-2px);
        }
        .reaction-btn.active {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: white;
        }
        .comments-section {
            margin-top: 3rem;
        }
        .comment-form {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .comment-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 1rem;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            background: var(--color-bg);
            color: var(--color-text);
            font-family: inherit;
            resize: vertical;
        }
        .comment-item {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .comment-header {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .comment-author {
            font-weight: 600;
        }
        .comment-date {
            color: var(--color-text-secondary);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="post-detail">
        <a href="/commons" style="color: var(--color-accent); text-decoration: none; margin-bottom: 2rem; display: inline-block;">
            <?= htmlspecialchars($t['back_to_feed']) ?>
        </a>

        <!-- Post -->
        <div class="post-card-full">
            <div class="post-header">
                <div class="post-author">
                    <?php if ($post['avatar_url']): ?>
                        <img src="<?= htmlspecialchars($post['avatar_url']) ?>" alt="" class="avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?= strtoupper(substr($post['username'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <div>
                        <strong><?= htmlspecialchars($post['display_name'] ?: $post['username']) ?></strong>
                        <?php if ($post['village_name']): ?>
                            <span class="village-badge">📍 <a href="/land/village/<?= htmlspecialchars($post['village_slug']) ?>" style="color: inherit;"><?= htmlspecialchars($post['village_name']) ?></a></span>
                        <?php endif; ?>
                    </div>
                </div>
                <time><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></time>
            </div>
            <p class="post-content" style="font-size: 1.1rem; line-height: 1.7; margin: 1.5rem 0;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>

            <!-- Reactions -->
            <div class="reactions-bar">
                <?php if (isLoggedIn()): ?>
                    <?php foreach ($reaction_types as $type => $emoji): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="react" value="1">
                            <input type="hidden" name="reaction_type" value="<?= $type ?>">
                            <button type="submit" class="reaction-btn <?= in_array($type, $user_reactions) ? 'active' : '' ?>">
                                <span><?= $emoji ?></span>
                                <span><?= $reactions_by_type[$type] ?? 0 ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($reaction_types as $type => $emoji): ?>
                        <div class="reaction-btn" style="cursor: default;">
                            <span><?= $emoji ?></span>
                            <span><?= $reactions_by_type[$type] ?? 0 ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <h2 style="margin-bottom: 1.5rem;"><?= htmlspecialchars($t['comments']) ?> (<?= count($comments) ?>)</h2>

            <!-- Comment Form -->
            <?php if (isLoggedIn()): ?>
                <div class="comment-form">
                    <?php if ($error): ?>
                        <div class="error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success" style="margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <textarea name="content" placeholder="<?= htmlspecialchars($t['comment_placeholder']) ?>" required></textarea>
                        <button type="submit" name="create_comment" class="btn-primary" style="margin-top: 1rem;"><?= htmlspecialchars($t['post_comment']) ?></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="comment-form" style="text-align: center; padding: 2rem;">
                    <p><?= htmlspecialchars($t['login_to_comment']) ?> <a href="/login">Login</a></p>
                </div>
            <?php endif; ?>

            <!-- Comments List -->
            <?php if (empty($comments)): ?>
                <p class="empty-state"><?= htmlspecialchars($t['no_comments']) ?></p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <?php if ($comment['avatar_url']): ?>
                                <img src="<?= htmlspecialchars($comment['avatar_url']) ?>" alt="" class="avatar" style="width: 40px; height: 40px;">
                            <?php else: ?>
                                <div class="avatar-placeholder" style="width: 40px; height: 40px; font-size: 1rem;"><?= strtoupper(substr($comment['username'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <div style="flex: 1;">
                                <div class="comment-author"><?= htmlspecialchars($comment['display_name'] ?: $comment['username']) ?></div>
                                <div class="comment-date"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></div>
                            </div>
                        </div>
                        <p style="margin-top: 0.75rem; line-height: 1.6;"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                        <?php if ($comment['reaction_count'] > 0): ?>
                            <div style="margin-top: 0.75rem; color: var(--color-text-secondary); font-size: 0.875rem;">
                                ❤️ <?= $comment['reaction_count'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

