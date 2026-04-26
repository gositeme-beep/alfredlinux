<?php
/**
 * Alfred Linux — Kernel 7.0.1 security & supply-chain transparency (public)
 * Deploy at site root: /security-kernel (same pattern as apps.php).
 *
 * Canonical long-form: GoForge docs/ in the Alfred Linux source repository.
 */
$forgeDocsRaw = 'https://alfredlinux.com/forge/commander/alfredlinux.com/raw/branch/main/docs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kernel 7.0.1 — Security & Supply Chain — Alfred Linux</title>
<meta name="description" content="How Alfred Linux handles Linux 7.0.1: download integrity, trust boundaries, what we do not claim, and where to read the full technical manifests.">
<meta property="og:title" content="Kernel 7.0.1 Security & Supply Chain — Alfred Linux">
<meta property="og:description" content="Transparency on kernel tarball verification, ISO trust model, and GoForge source documentation.">
<meta property="og:url" content="https://alfredlinux.com/security-kernel">
<meta property="og:type" content="website">
<link rel="canonical" href="https://alfredlinux.com/security-kernel">
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="/assets/css/nav.css">
<style>
:root {
    --bg:#0a0a0f; --surface:#12121a; --border:#1e1e2e; --accent:#6c5ce7; --accent2:#00cec9;
    --gold:#fdcb6e; --text:#e0e0e0; --dim:#888; --warn:#e17055;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;line-height:1.65;}
.hero{text-align:center;padding:72px 24px 40px;}
.hero h1{font-size:clamp(1.75rem,4vw,2.5rem);font-weight:900;letter-spacing:-1px;margin-bottom:14px;}
.hero h1 span{background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{color:var(--dim);max-width:720px;margin:0 auto;font-size:1.02rem;}
.container{max-width:820px;margin:0 auto;padding:0 24px 80px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px;margin-bottom:22px;}
.card h2{font-size:1.1rem;margin-bottom:14px;color:var(--accent2);}
.card ul{margin:10px 0 0 1.1rem;}
.card li{margin:8px 0;color:var(--dim);}
.card li strong{color:var(--text);}
.links a{display:inline-block;margin:6px 14px 6px 0;color:var(--accent2);font-weight:600;text-decoration:none;font-size:.9rem;}
.links a:hover{text-decoration:underline;}
.pill{display:inline-block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:4px 10px;border-radius:100px;background:rgba(225,112,85,.15);color:var(--warn);margin-bottom:12px;}
footer{text-align:center;padding:1.5rem;color:#94a3b8;font-size:.85rem;border-top:1px solid rgba(255,255,255,.06);}
footer a{color:#6366f1;text-decoration:none;}
</style>
</head>
<body>

<?php $currentPage = 'security-kernel'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <div class="pill">Public transparency</div>
    <h1>Linux <span>7.0.1</span> — security &amp; supply chain</h1>
    <p>What Alfred verifies in git, what still depends on your builder, and where the full technical write-ups live on GoForge. Honest scope for auditors and the public.</p>
</div>

<div class="container">

    <div class="card">
        <h2>What we do not claim</h2>
        <ul>
            <li><strong>No “every CVE in 7.0.1” list</strong> inside the Alfred OS repo — that set is not finite over time, and the full kernel tree is built <strong>out of tree</strong>, not vendored in the live-build repository.</li>
            <li>We do <strong>not</strong> run a full static analysis of Linus’s tree from the small Alfred integration repo alone; heavy scanning belongs on the <strong>extracted kernel tree</strong> (private builder or a dedicated kernel mirror + CI on GoForge).</li>
        </ul>
    </div>

    <div class="card">
        <h2>What we do ship — integrity &amp; trust boundaries</h2>
        <ul>
            <li><strong>Download integrity:</strong> <code>scripts/kernel-download-7.0.1.sh</code> fetches <code>linux-7.0.1.tar.xz</code> and <code>patch-7.0.1.xz</code> from <strong>cdn.kernel.org</strong> over HTTPS and verifies <strong>SHA256</strong> against <strong>sha256sums.asc</strong> before you unpack (skip only with an explicit env flag — not for production ISOs).</li>
            <li><strong>ISO trust model:</strong> published checksums + GPG on sums; staging sync before <code>lb build</code>; hooks <strong>0050</strong> (kernel package gate), <strong>0160</strong> (sysctl / audit / modules), <strong>0710</strong> (do not replace Alfred kernel with Debian meta). See verification flow at <a href="/verify" style="color:var(--accent2);">/verify</a> and apps at <a href="/apps" style="color:var(--accent2);">/apps</a>.</li>
            <li><strong>Explicit ownership</strong> of other supply-chain edges: host <code>KERNEL_WORK</code>, Docker bind mounts, gitignored <code>.deb</code> binaries, staged tarballs (e.g. liboqs), and Kconfig defaults when no Alfred <code>.config</code> is supplied — all documented in the manifests below.</li>
        </ul>
    </div>

    <div class="card">
        <h2>GoForge — canonical technical documents (AGPL source)</h2>
        <p style="color:var(--dim);font-size:.92rem;margin-bottom:12px;">These are the same files shipped in the Alfred Linux repository; raw links follow the GoForge <code>…/raw/branch/main/docs/…</code> layout. If a link 404s after a rename, open the repo tree and browse <code>docs/</code>.</p>
        <div class="links">
            <a href="<?= htmlspecialchars($forgeDocsRaw) ?>/GOFORGE-INFRASTRUCTURE-UPGRADE.txt">GoForge infrastructure upgrade</a>
            <a href="<?= htmlspecialchars($forgeDocsRaw) ?>/KERNEL-7.0.1-SECURITY-MANIFEST.txt">Security manifest</a>
            <a href="<?= htmlspecialchars($forgeDocsRaw) ?>/KERNEL-7.0.1-SUPPLY-CHAIN-AUDIT.txt">Supply-chain &amp; trojan-path audit</a>
            <a href="<?= htmlspecialchars($forgeDocsRaw) ?>/ISO-STAGING-SHIP-GAPS.txt">ISO staging ship gaps</a>
            <a href="<?= htmlspecialchars($forgeDocsRaw) ?>/ISO-BUILD-RISK-REGISTER.txt">ISO completeness covenant + risk register</a>
        </div>
    </div>

    <div class="card">
        <h2>Bigger picture — where full kernel audit runs</h2>
        <ul>
            <li><strong>Alfred repo</strong> = integration, hooks, live-build staging, download verification.</li>
            <li><strong>Kernel audit</strong> = separate pipeline: extracted <code>linux-7.0.1</code> on a trusted host or a <strong>second GoForge repo</strong> whose CI <strong>checks out or unpacks</strong> Linux and runs scanners (sparse, checkstack, distro checklist, SBOM). That is <strong>workflow + runner capacity you define</strong> — not something a forge <strong>UI refresh</strong> replaces.</li>
            <li><strong>When “upgrade GoForge” actually helps:</strong> bigger runners (disk/RAM for the full tree + ccache), first-class <strong>Actions</strong> (or equivalent pipelines), <strong>artifacts</strong> for logs/SARIF/deb hashes, registry/LFS if you mirror sources — optionally a <strong>dedicated heavy repo</strong> so Alfred PRs stay fast.</li>
            <li><strong>Roadmap bar:</strong> Alfred’s forge is aimed at <strong>self-hosted seriousness</strong> — kernel-sized jobs, sovereign infra, and release integrity without depending on a single proprietary SaaS ceiling. Beating GitHub (and “every competitor”) is <strong>earned in shipped runner scale + pipeline ergonomics + trust tooling</strong>, not in themes alone.</li>
        </ul>
    </div>

</div>

<footer>
    &copy; <?= date('Y') ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)
    &middot; <a href="/apps">Apps</a> &middot; <a href="/verify">Verify</a>
    &middot; <a href="/docs">Docs</a> &middot; <a href="/developers">Developers</a>
</footer>

<?php include __DIR__ . '/includes/shabbat-banner.php'; ?>
</body>
</html>
