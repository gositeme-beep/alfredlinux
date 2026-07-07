<?php
require_once __DIR__ . '/includes/lang.php';

$host = strtolower($_SERVER['HTTP_HOST'] ?? 'root.com');
$isMetaDome = str_contains($host, 'meta-dome.com');
$artifactPath = __DIR__ . '/scripts/optimization/generated/public-whats-new.jsonl';

$entries = [];
if (is_file($artifactPath)) {
    foreach (array_reverse(file($artifactPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) as $line) {
        $decoded = json_decode($line, true);
        if (!is_array($decoded)) {
            continue;
        }
        $domain = $decoded['domain'] ?? 'root';
        if ($isMetaDome) {
            if (!in_array($domain, ['meta-dome', 'shared'], true)) {
                continue;
            }
        } else {
            if (!in_array($domain, ['root', 'shared'], true)) {
                continue;
            }
        }
        $entries[] = $decoded;
        if (count($entries) >= 24) {
            break;
        }
    }
}

$page_title = $isMetaDome ? 'What\'s New in MetaDome' : 'What\'s New in GoSiteMe';
$page_description = $isMetaDome
    ? 'Public-safe changelog for MetaDome: new capabilities, visible improvements, and ecosystem milestones.'
    : 'Public-safe changelog for GoSiteMe: new capabilities, visible improvements, and ecosystem milestones.';

if ($isMetaDome) {
    include __DIR__ . '/includes/site-header.inc.php';
} else {
    include __DIR__ . '/includes/site-header.inc.php';
}
?>
<style>
    :root { --wn-bg:#09111d; --wn-card:#101827; --wn-border:#1f2937; --wn-text:#e5eef8; --wn-muted:#94a3b8; --wn-accent:#38bdf8; --wn-green:#22c55e; }
    .wn-wrap { max-width: 960px; margin: 0 auto; padding: 7rem 1.25rem 5rem; }
    .wn-hero { margin-bottom: 2rem; }
    .wn-hero h1 { font-size: clamp(2rem, 5vw, 3rem); margin: 0 0 .75rem; }
    .wn-hero p { color: var(--wn-muted); max-width: 700px; line-height: 1.7; }
    .wn-list { display: grid; gap: 1rem; }
    .wn-card { background: var(--wn-card); border: 1px solid var(--wn-border); border-radius: 18px; padding: 1.2rem 1.3rem; }
    .wn-meta { display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; margin-bottom:.5rem; }
    .wn-kind { display:inline-flex; align-items:center; padding:.2rem .55rem; border-radius:999px; background: rgba(56,189,248,.12); color: var(--wn-accent); font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; }
    .wn-date { color: var(--wn-muted); font-size:.85rem; }
    .wn-card h2 { font-size:1.15rem; margin:0 0 .45rem; }
    .wn-card p { color: var(--wn-text); line-height:1.65; margin:0; }
    .wn-link { display:inline-block; margin-top:.75rem; color:var(--wn-green); }
    .wn-empty { color: var(--wn-muted); }
</style>

<div class="wn-wrap">
    <section class="wn-hero">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <p><?php echo htmlspecialchars($page_description); ?> This page is public-safe by design: it shows visible product changes and milestones, not internal operational details.</p>
    </section>

    <section class="wn-list">
        <?php if ($entries): ?>
            <?php foreach ($entries as $entry): ?>
                <article class="wn-card">
                    <div class="wn-meta">
                        <span class="wn-kind"><?php echo htmlspecialchars((string)($entry['kind'] ?? 'update')); ?></span>
                        <span class="wn-date"><?php echo htmlspecialchars(date('M j, Y', strtotime((string)($entry['created_at'] ?? 'now')))); ?></span>
                    </div>
                    <h2><?php echo htmlspecialchars((string)($entry['title'] ?? 'Update')); ?></h2>
                    <p><?php echo htmlspecialchars((string)($entry['summary'] ?? '')); ?></p>
                    <?php if (!empty($entry['link'])): ?>
                        <a class="wn-link" href="<?php echo htmlspecialchars((string)$entry['link']); ?>">Read more</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="wn-empty">No public updates published yet.</div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>