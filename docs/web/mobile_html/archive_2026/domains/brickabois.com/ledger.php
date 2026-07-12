<?php
/**
 * The Ledger - Governance & Transparency
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$currentUser = getCurrentUser();
$db = getDBConnection();

// Get proposals
$status_filter = $_GET['status'] ?? null;
$village_filter = isset($_GET['village']) ? (int)$_GET['village'] : null;

$sql = "SELECT p.*, u.username, u.display_name, v.name as village_name,
        (SELECT SUM(CASE WHEN vote = 'yes' THEN weight ELSE 0 END) FROM votes WHERE proposal_id = p.id) as yes_votes,
        (SELECT SUM(CASE WHEN vote = 'no' THEN weight ELSE 0 END) FROM votes WHERE proposal_id = p.id) as no_votes,
        (SELECT COUNT(*) FROM votes WHERE proposal_id = p.id) as total_votes
        FROM proposals p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN villages v ON p.village_id = v.id
        WHERE 1=1";

$params = [];
if ($status_filter) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}
if ($village_filter) {
    $sql .= " AND p.village_id = ?";
    $params[] = $village_filter;
}

$sql .= " ORDER BY p.created_at DESC LIMIT 50";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$proposals = $stmt->fetchAll();

// Get treasury summary
$treasuryStmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN transaction_type IN ('deposit', 'income') THEN amount ELSE 0 END) -
        SUM(CASE WHEN transaction_type IN ('withdrawal', 'expense') THEN amount ELSE 0 END) as balance,
        COUNT(*) as transaction_count
    FROM treasury_transactions
    WHERE village_id IS NULL OR village_id = ?
");
$treasuryStmt->execute([$village_filter ?: null]);
$treasury = $treasuryStmt->fetch();

// Get recent transactions
$transactionsStmt = $db->prepare("
    SELECT t.*, u.username, u.display_name, v.name as village_name
    FROM treasury_transactions t
    JOIN users u ON t.created_by = u.id
    LEFT JOIN villages v ON t.village_id = v.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
$transactionsStmt->execute();
$transactions = $transactionsStmt->fetchAll();

$translations = [
    'en' => [
        'title' => 'The Ledger',
        'subtitle' => 'Governance & Transparency',
        'description' => 'Blockchain-backed cooperative treasury & voting system',
        'proposals' => 'Governance Proposals',
        'treasury' => 'Treasury',
        'balance' => 'Balance',
        'transactions' => 'Recent Transactions',
        'create_proposal' => 'Create Proposal',
        'status' => 'Status',
        'voting' => 'Voting',
        'open' => 'Open',
        'passed' => 'Passed',
        'rejected' => 'Rejected',
        'closed' => 'Closed',
        'votes' => 'Votes',
        'yes' => 'Yes',
        'no' => 'No',
        'view_proposal' => 'View Proposal',
        'no_proposals' => 'No proposals yet',
        'no_transactions' => 'No transactions yet',
    ],
    'fr' => [
        'title' => 'Le Registre',
        'subtitle' => 'Gouvernance & Transparence',
        'description' => 'Système de trésorerie coopérative et de vote soutenu par la blockchain',
        'proposals' => 'Propositions de Gouvernance',
        'treasury' => 'Trésorerie',
        'balance' => 'Solde',
        'transactions' => 'Transactions Récentes',
        'create_proposal' => 'Créer une Proposition',
        'status' => 'Statut',
        'voting' => 'En Vote',
        'open' => 'Ouvert',
        'passed' => 'Approuvé',
        'rejected' => 'Rejeté',
        'closed' => 'Fermé',
        'votes' => 'Votes',
        'yes' => 'Oui',
        'no' => 'Non',
        'view_proposal' => 'Voir la Proposition',
        'no_proposals' => 'Aucune proposition pour le moment',
        'no_transactions' => 'Aucune transaction pour le moment',
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
    <link rel="stylesheet" href="/assets/css/ledger.css">
    <script>
        // Initialize theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            const colorTheme = localStorage.getItem('colorTheme') || 'forest';
            document.documentElement.setAttribute('data-color-theme', colorTheme);
        })();
    </script>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <section class="hero" style="min-height: 60vh; padding-top: 100px;">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><?= htmlspecialchars($t['title']) ?></h1>
                <p class="hero-subtitle"><?= htmlspecialchars($t['subtitle']) ?></p>
                <p class="hero-tagline"><?= htmlspecialchars($t['description']) ?></p>
            </div>
        </div>
    </section>

    <section class="activity" style="padding: 2rem 0;">
        <div class="container">
            <div class="ledger-layout">
                <!-- Main Content -->
                <div class="ledger-main">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2><?= htmlspecialchars($t['proposals']) ?></h2>
                        <?php if (isLoggedIn()): ?>
                            <a href="/ledger/create" class="btn-primary"><?= htmlspecialchars($t['create_proposal']) ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($proposals)): ?>
                        <p class="empty-state"><?= htmlspecialchars($t['no_proposals']) ?></p>
                    <?php else: ?>
                        <div class="proposals-list">
                            <?php foreach ($proposals as $proposal): ?>
                                <div class="proposal-card">
                                    <div class="proposal-header">
                                        <h3><?= htmlspecialchars($lang === 'fr' && $proposal['title_fr'] ? $proposal['title_fr'] : $proposal['title']) ?></h3>
                                        <span class="status-badge status-<?= $proposal['status'] ?>"><?= htmlspecialchars($t[$proposal['status']] ?? $proposal['status']) ?></span>
                                    </div>
                                    <p class="proposal-description"><?= htmlspecialchars(substr($lang === 'fr' && $proposal['description_fr'] ? $proposal['description_fr'] : $proposal['description'], 0, 200)) ?>...</p>
                                    <div class="proposal-meta">
                                        <span>By <?= htmlspecialchars($proposal['display_name'] ?: $proposal['username']) ?></span>
                                        <?php if ($proposal['village_name']): ?>
                                            <span>📍 <?= htmlspecialchars($proposal['village_name']) ?></span>
                                        <?php endif; ?>
                                        <time><?= date('M j, Y', strtotime($proposal['created_at'])) ?></time>
                                    </div>
                                    <?php if ($proposal['status'] === 'voting' || $proposal['status'] === 'open'): ?>
                                        <div class="proposal-votes">
                                            <div class="vote-bar">
                                                <div class="vote-bar-yes" style="width: <?= $proposal['total_votes'] > 0 ? ($proposal['yes_votes'] / ($proposal['yes_votes'] + $proposal['no_votes']) * 100) : 0 ?>%"></div>
                                            </div>
                                            <div class="vote-stats">
                                                <span>✅ <?= number_format($proposal['yes_votes'] ?? 0, 1) ?></span>
                                                <span>❌ <?= number_format($proposal['no_votes'] ?? 0, 1) ?></span>
                                                <span>📊 <?= $proposal['total_votes'] ?> <?= htmlspecialchars($t['votes']) ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <a href="/ledger/proposal/<?= $proposal['id'] ?>" class="view-proposal-link"><?= htmlspecialchars($t['view_proposal']) ?> →</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="ledger-sidebar">
                    <!-- Treasury -->
                    <div class="sidebar-section">
                        <h3><?= htmlspecialchars($t['treasury']) ?></h3>
                        <div class="treasury-balance">
                            <div class="balance-amount">$<?= number_format($treasury['balance'] ?? 0, 2) ?> CAD</div>
                            <div class="balance-label"><?= htmlspecialchars($t['balance']) ?></div>
                        </div>
                        <a href="/ledger/treasury" class="view-all">View Treasury →</a>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="sidebar-section">
                        <h3><?= htmlspecialchars($t['transactions']) ?></h3>
                        <?php if (empty($transactions)): ?>
                            <p class="empty-state"><?= htmlspecialchars($t['no_transactions']) ?></p>
                        <?php else: ?>
                            <div class="transactions-list">
                                <?php foreach ($transactions as $tx): ?>
                                    <div class="transaction-item">
                                        <div class="transaction-type type-<?= $tx['transaction_type'] ?>">
                                            <?= $tx['transaction_type'] === 'deposit' || $tx['transaction_type'] === 'income' ? '+' : '-' ?>
                                            $<?= number_format($tx['amount'], 2) ?>
                                        </div>
                                        <div class="transaction-info">
                                            <p><?= htmlspecialchars($lang === 'fr' && $tx['description_fr'] ? $tx['description_fr'] : $tx['description']) ?></p>
                                            <small><?= htmlspecialchars($tx['username']) ?> • <?= date('M j', strtotime($tx['created_at'])) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p style="text-align: center; color: var(--color-text-secondary);">&copy; <?= date('Y') ?> The Free Village Network</p>
        </div>
    </footer>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

