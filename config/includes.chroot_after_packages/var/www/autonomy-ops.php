<?php
require_once __DIR__ . '/includes/commander-guard.inc.php';
require_commander_or_404();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/scripts/optimization/ops-data.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function gsmOpsRunScript(array $commandParts): array {
    $escaped = array_map('escapeshellarg', $commandParts);
    $command = implode(' ', $escaped) . ' 2>&1';
    $output = [];
    $exitCode = 1;
    exec($command, $output, $exitCode);

    return [
        'ok' => $exitCode === 0,
        'exit_code' => $exitCode,
        'output' => trim(implode("\n", $output)),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $submittedToken)) {
        $_SESSION['autonomy_ops_flash'] = [
            'type' => 'error',
            'message' => 'CSRF validation failed. Refresh and try again.',
        ];
        header('Location: /autonomy-ops.php');
        exit;
    }

    $action = (string)($_POST['ops_action'] ?? '');
    $flash = ['type' => 'error', 'message' => 'Unknown operation.'];

    if ($action === 'run_integrity_scan') {
        $result = gsmOpsRunScript(['php', __DIR__ . '/scripts/optimization/site-integrity-scan.php']);
        $flash = [
            'type' => $result['ok'] ? 'success' : 'error',
            'message' => $result['ok']
                ? 'Integrity scan completed and the latest report was refreshed.'
                : 'Integrity scan failed: ' . ($result['output'] ?: 'Unknown error'),
        ];
    }

    if ($action === 'publish_public_update') {
        $title = trim((string)($_POST['title'] ?? ''));
        $summary = trim((string)($_POST['summary'] ?? ''));
        $kind = trim((string)($_POST['kind'] ?? 'platform'));
        $domain = trim((string)($_POST['domain'] ?? 'root'));
        $link = trim((string)($_POST['link'] ?? ''));

        if ($title === '' || $summary === '') {
            $flash = [
                'type' => 'error',
                'message' => 'Title and summary are required to publish a public update.',
            ];
        } else {
            $result = gsmOpsRunScript([
                'php',
                __DIR__ . '/scripts/optimization/public-whats-new-intake.php',
                '--title=' . $title,
                '--summary=' . $summary,
                '--kind=' . $kind,
                '--domain=' . $domain,
                '--link=' . $link,
            ]);
            $flash = [
                'type' => $result['ok'] ? 'success' : 'error',
                'message' => $result['ok']
                    ? 'Public update published to the visible What\'s New feed.'
                    : 'Publish failed: ' . ($result['output'] ?: 'Unknown error'),
            ];
        }
    }

    $_SESSION['autonomy_ops_flash'] = $flash;
    header('Location: /autonomy-ops.php');
    exit;
}

$opsSummary = gsmOpsSummary();
$artifactDir = $opsSummary['artifact_dir'];
$latestIntegrityPath = $opsSummary['latest_integrity_path'];
$latestIntegrity = $opsSummary['latest_integrity'];
$refreshItems = $opsSummary['refresh_items'];
$upgradeItems = $opsSummary['upgrade_items'];
$publicUpdates = $opsSummary['public_updates'];
$publicUpdateCount = (int)$opsSummary['public_update_count'];
$changelogPath = $opsSummary['changelog_path'];
$changelogEntries = $opsSummary['changelog_entries'];
$opsFlash = $_SESSION['autonomy_ops_flash'] ?? null;
unset($_SESSION['autonomy_ops_flash']);

$page_title = 'Autonomy Ops — GoSiteMe';
$page_description = 'Autonomy operations dashboard for watchdog actions, integrity findings, and site refresh proposals.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root { --ops-bg:#08111f; --ops-card:#0f172a; --ops-border:#1e293b; --ops-text:#dbe4f0; --ops-muted:#94a3b8; --ops-accent:#22c55e; --ops-blue:#38bdf8; --ops-amber:#f59e0b; }
        body.ops-page { margin:0; background:radial-gradient(circle at top,#0f1f38,#08111f 48%); color:var(--ops-text); font-family:'Space Grotesk',system-ui,sans-serif; }
        .ops-wrap { max-width:1280px; margin:0 auto; padding:2rem 1.25rem 4rem; }
        .ops-hero { display:grid; grid-template-columns:2fr 1fr; gap:1rem; align-items:stretch; margin-bottom:1.5rem; }
        .ops-card { background:rgba(15,23,42,.92); border:1px solid var(--ops-border); border-radius:18px; padding:1.25rem; box-shadow:0 18px 60px rgba(0,0,0,.24); }
        .ops-grid { display:grid; grid-template-columns:1.3fr 1fr; gap:1rem; }
        .ops-list { display:grid; gap:.75rem; }
        .ops-item { border:1px solid rgba(148,163,184,.12); border-radius:14px; padding:.9rem 1rem; background:rgba(255,255,255,.02); }
        .ops-kpi { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; margin-top:1rem; }
        .ops-kpi div { background:rgba(255,255,255,.03); border-radius:14px; padding:.9rem; border:1px solid rgba(148,163,184,.1); }
        .ops-label { font-size:.74rem; letter-spacing:.08em; text-transform:uppercase; color:var(--ops-muted); }
        .ops-value { font-size:1.5rem; font-weight:700; margin-top:.3rem; }
        .ops-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .55rem; border-radius:999px; font-size:.72rem; background:rgba(34,197,94,.12); color:var(--ops-accent); }
        .ops-note { color:var(--ops-muted); line-height:1.6; }
        .ops-path { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.78rem; color:var(--ops-blue); }
        .ops-section-title { margin:0 0 .9rem; font-size:1.05rem; }
        .ops-item p { margin:.45rem 0 0; color:var(--ops-muted); }
        .ops-small { font-size:.84rem; color:var(--ops-muted); }
        .ops-actions { display:grid; gap:1rem; }
        .ops-form { display:grid; gap:.75rem; }
        .ops-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.75rem; }
        .ops-input, .ops-textarea, .ops-select { width:100%; border-radius:12px; border:1px solid rgba(148,163,184,.16); background:rgba(255,255,255,.03); color:var(--ops-text); padding:.8rem .9rem; font:inherit; }
        .ops-textarea { min-height:108px; resize:vertical; }
        .ops-button-row { display:flex; gap:.75rem; flex-wrap:wrap; }
        .ops-btn { border:1px solid transparent; border-radius:999px; padding:.72rem 1.1rem; font:inherit; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:.45rem; }
        .ops-btn-primary { background:var(--ops-accent); color:#04110b; }
        .ops-btn-secondary { background:rgba(56,189,248,.14); color:var(--ops-blue); border-color:rgba(56,189,248,.3); }
        .ops-flash { margin-bottom:1rem; border-radius:14px; padding:.95rem 1rem; border:1px solid rgba(148,163,184,.18); }
        .ops-flash.success { background:rgba(34,197,94,.12); color:#b6f3ca; border-color:rgba(34,197,94,.26); }
        .ops-flash.error { background:rgba(239,68,68,.12); color:#fecaca; border-color:rgba(239,68,68,.26); }
        @media (max-width: 980px) { .ops-hero, .ops-grid, .ops-kpi, .ops-form-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body class="ops-page">
<?php include __DIR__ . '/includes/site-header.inc.php'; ?>
<div class="ops-wrap">
    <?php if ($opsFlash): ?>
        <div class="ops-flash <?php echo htmlspecialchars((string)$opsFlash['type']); ?>"><?php echo htmlspecialchars((string)$opsFlash['message']); ?></div>
    <?php endif; ?>
    <div class="ops-hero">
        <div class="ops-card">
            <div class="ops-badge">Autonomy Ops</div>
            <h1 style="margin:.7rem 0 .5rem;font-size:2.3rem;line-height:1.05;">Past, Present, Next</h1>
            <p class="ops-note">This dashboard tracks what the watchdog and maintenance agents already did, what they are flagging right now, and what they propose to evolve next. The model is simple: observe clearly, act safely, and leave an auditable trail.</p>
            <div class="ops-kpi">
                <div><div class="ops-label">Latest Integrity Issues</div><div class="ops-value"><?php echo (int)($latestIntegrity['issue_count'] ?? 0); ?></div></div>
                <div><div class="ops-label">Refresh Proposals</div><div class="ops-value"><?php echo count($refreshItems); ?></div></div>
                <div><div class="ops-label">Public Updates</div><div class="ops-value"><?php echo $publicUpdateCount; ?></div></div>
            </div>
        </div>
        <div class="ops-card">
            <h2 class="ops-section-title">Current Sources</h2>
            <div class="ops-list">
                <div class="ops-item"><div class="ops-label">Integrity Report</div><div class="ops-path"><?php echo htmlspecialchars($latestIntegrityPath ?? 'none'); ?></div></div>
                <div class="ops-item"><div class="ops-label">Changelog</div><div class="ops-path"><?php echo htmlspecialchars($changelogPath); ?></div></div>
                <div class="ops-item"><div class="ops-label">Generated Artifacts</div><div class="ops-path"><?php echo htmlspecialchars($artifactDir); ?></div></div>
            </div>
        </div>
    </div>

    <div class="ops-grid" style="margin-bottom:1rem;">
        <div class="ops-card">
            <h2 class="ops-section-title">Commander Quick Actions</h2>
            <div class="ops-actions">
                <form class="ops-form" method="post" action="/autonomy-ops.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="ops_action" value="run_integrity_scan">
                    <div class="ops-item">
                        <div class="ops-label">Integrity</div>
                        <p>Run the scanner now and refresh the latest report and changelog entry.</p>
                    </div>
                    <div class="ops-button-row">
                        <button class="ops-btn ops-btn-primary" type="submit">Run Integrity Scan</button>
                        <a class="ops-btn ops-btn-secondary" href="/whats-new.php">View Public Feed</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="ops-card">
            <h2 class="ops-section-title">Publish Public Update</h2>
            <form class="ops-form" method="post" action="/autonomy-ops.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['csrf_token']); ?>">
                <input type="hidden" name="ops_action" value="publish_public_update">
                <div class="ops-form-grid">
                    <input class="ops-input" type="text" name="title" maxlength="140" placeholder="Title" required>
                    <input class="ops-input" type="text" name="link" maxlength="255" placeholder="Link, e.g. /agent-social.php">
                </div>
                <textarea class="ops-textarea" name="summary" maxlength="280" placeholder="Public-safe summary" required></textarea>
                <div class="ops-form-grid">
                    <select class="ops-select" name="kind">
                        <option value="platform">Platform</option>
                        <option value="feature">Feature</option>
                        <option value="ops">Ops</option>
                        <option value="metadome">MetaDome</option>
                    </select>
                    <select class="ops-select" name="domain">
                        <option value="root">GoSiteMe</option>
                        <option value="meta-dome">MetaDome</option>
                        <option value="shared">Shared</option>
                    </select>
                </div>
                <div class="ops-button-row">
                    <button class="ops-btn ops-btn-primary" type="submit">Publish Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ops-grid">
        <div class="ops-card">
            <h2 class="ops-section-title">Recent Changelog</h2>
            <div class="ops-list">
                <?php if ($changelogEntries): ?>
                    <?php foreach ($changelogEntries as $entry): ?>
                        <div class="ops-item">
                            <div class="ops-label"><?php echo htmlspecialchars((string)($entry['actor'] ?? 'autonomy')); ?></div>
                            <div><?php echo htmlspecialchars((string)($entry['summary'] ?? '')); ?></div>
                            <p><?php echo htmlspecialchars((string)($entry['timestamp'] ?? '')); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ops-item"><p>No changelog entries yet.</p></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="ops-card">
            <h2 class="ops-section-title">Integrity Queue</h2>
            <div class="ops-list">
                <?php if (!empty($latestIntegrity['issues'])): ?>
                    <?php foreach (array_slice($latestIntegrity['issues'], 0, 10) as $issue): ?>
                        <div class="ops-item">
                            <div class="ops-label"><?php echo htmlspecialchars($issue['type'] ?? 'issue'); ?></div>
                            <div><?php echo htmlspecialchars($issue['match'] ?? ''); ?></div>
                            <p><?php echo htmlspecialchars(basename((string)($issue['file'] ?? ''))); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ops-item"><p>No integrity issues in the latest report.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ops-grid" style="margin-top:1rem;">
        <div class="ops-card">
            <h2 class="ops-section-title">Public What\'s New Feed</h2>
            <div class="ops-list">
                <?php if ($publicUpdates): ?>
                    <?php foreach ($publicUpdates as $item): ?>
                        <div class="ops-item">
                            <div class="ops-label"><?php echo htmlspecialchars((string)($item['domain'] ?? 'shared')); ?> · <?php echo htmlspecialchars((string)($item['kind'] ?? 'platform')); ?></div>
                            <div><?php echo htmlspecialchars((string)($item['title'] ?? '')); ?></div>
                            <p><?php echo htmlspecialchars((string)($item['summary'] ?? '')); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ops-item"><p>No public-safe updates published yet.</p></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="ops-card">
            <h2 class="ops-section-title">Upgrade Proposals</h2>
            <div class="ops-list">
                <?php if ($upgradeItems): ?>
                    <?php foreach ($upgradeItems as $item): ?>
                        <div class="ops-item">
                            <div class="ops-label"><?php echo htmlspecialchars((string)($item['page'] ?? '')); ?></div>
                            <div><?php echo htmlspecialchars((string)($item['title'] ?? '')); ?></div>
                            <p><?php echo htmlspecialchars((string)($item['symptom'] ?? '')); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ops-item"><p>No upgrade proposals yet.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ops-grid" style="margin-top:1rem;">
        <div class="ops-card">
            <h2 class="ops-section-title">Content Refresh Proposals</h2>
            <div class="ops-list">
                <?php if ($refreshItems): ?>
                    <?php foreach ($refreshItems as $item): ?>
                        <div class="ops-item">
                            <div class="ops-label"><?php echo htmlspecialchars($item['page'] ?? ''); ?></div>
                            <div><?php echo htmlspecialchars($item['title'] ?? ''); ?></div>
                            <p><?php echo htmlspecialchars(($item['current_text'] ?? '') . ' -> ' . ($item['target_text'] ?? '')); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ops-item"><p>No content refresh proposals yet.</p></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="ops-card">
            <h2 class="ops-section-title">Commander Notes</h2>
            <div class="ops-list">
                <div class="ops-item">
                    <div class="ops-label">Access Mode</div>
                    <p>This page now uses the hard 404 commander guard and no longer advertises itself to non-owner accounts.</p>
                </div>
                <div class="ops-item">
                    <div class="ops-label">Action Model</div>
                    <p>Quick actions run the existing CLI scripts directly, so the UI and automation layer stay aligned.</p>
                </div>
                <div class="ops-item">
                    <div class="ops-label">Public Safety</div>
                    <p>Only explicitly published updates flow into What\'s New. Internal integrity reports and autonomy logs stay private here.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
</body>
</html>