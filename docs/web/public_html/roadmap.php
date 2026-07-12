<?php
/**
 * Alfred Linux — Roadmap
 * Release cadence, version timeline, what's planned
 *
 * GoSiteMe Inc. — April 2026
 */
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadmap — Alfred Linux</title>
    <meta name="description" content="Alfred Linux release roadmap. Version timeline from RC to GA to LTS. Transparent planning — see what's built, what's next, and when LTS lands.">
    <meta property="og:title" content="Roadmap — Alfred Linux">
    <meta property="og:description" content="Release cadence, LTS timeline, and what's next for Alfred Linux. Transparent development planning.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/roadmap">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/roadmap">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b; --surface: rgba(255,255,255,0.03); --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06); --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0; --text-muted: #9ca3af; --text-dim: #6b7280;
            --accent: #6366f1; --accent-light: #a5b4fc; --accent2: #8b5cf6;
            --green: #34d399; --amber: #f59e0b; --cyan: #22d3ee;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; line-height: 1.7; }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }


        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 20%, rgba(245,158,11,0.08) 0%, transparent 55%); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 900; margin-bottom: 1rem; background: linear-gradient(135deg, #fff, var(--amber), var(--accent-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { color: var(--text-muted); font-size: 1.1rem; max-width: 650px; margin: 0 auto; }

        .container { max-width: 900px; margin: 0 auto; padding: 0 2rem 4rem; }

        .section { margin-top: 4rem; }
        .section h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .section p { color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem; }

        /* Timeline */
        .timeline { position: relative; padding-left: 2.5rem; margin: 2rem 0; }
        .timeline::before { content: ''; position: absolute; left: 0.6rem; top: 0; bottom: 0; width: 2px; background: linear-gradient(180deg, var(--green), var(--accent), var(--amber), var(--text-dim)); }
        .tl-item { position: relative; margin-bottom: 2rem; }
        .tl-item::before { content: ''; position: absolute; left: -2.15rem; top: 0.35rem; width: 14px; height: 14px; border-radius: 50%; border: 2px solid var(--bg); }
        .tl-item.done::before { background: var(--green); }
        .tl-item.current::before { background: var(--accent); box-shadow: 0 0 12px var(--accent); }
        .tl-item.next::before { background: var(--amber); }
        .tl-item.future::before { background: var(--text-dim); }
        .tl-item h3 { font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem; }
        .tl-item .date { font-size: 0.8rem; color: var(--text-dim); margin-bottom: 0.4rem; }
        .tl-item p { font-size: 0.88rem; color: var(--text-muted); }
        .tl-item .tag { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-left: 0.5rem; }
        .tag-done { background: rgba(52,211,153,0.15); color: var(--green); }
        .tag-now { background: rgba(99,102,241,0.2); color: var(--accent-light); }
        .tag-next { background: rgba(245,158,11,0.15); color: var(--amber); }
        .tag-planned { background: rgba(255,255,255,0.06); color: var(--text-dim); }

        /* Cadence grid */
        .cadence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.25rem; margin: 2rem 0; }
        .cadence-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem; }
        .cadence-card h3 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .cadence-card p { color: var(--text-muted); font-size: 0.85rem; line-height: 1.6; margin: 0; }
        .cadence-card .value { font-size: 1.4rem; font-weight: 900; color: var(--accent-light); margin-bottom: 0.25rem; }

        /* Goals table */
        .goal-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
        .goal-table th { text-align: left; padding: 0.6rem 1rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); border-bottom: 1px solid var(--border); }
        .goal-table td { padding: 0.6rem 1rem; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--text-muted); }
        .goal-table .item { color: #fff; font-weight: 600; }
        .goal-table tr:hover td { background: var(--surface-hover); }

        footer { text-align: center; padding: 3rem 2rem; color: var(--text-dim); font-size: 0.85rem; border-top: 1px solid var(--border); }
        footer a { color: var(--accent-light); }

        @media (max-width: 768px) {
            .hero { padding: 5rem 1.5rem 2rem; }
            .container { padding: 0 1.25rem 3rem; }
        }
    </style>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebPage","name":"Roadmap — Alfred Linux","description":"Alfred Linux development roadmap. Timeline from v2.0 RC to v7.77 GA Kingdom of God Edition and beyond.","url":"https://alfredlinux.com/roadmap","isPartOf":{"@type":"WebSite","name":"Alfred Linux","url":"https://alfredlinux.com"},"publisher":{"@type":"Organization","name":"GoSiteMe Inc.","url":"https://gositeme.com"}}
    </script>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php $currentPage = 'roadmap'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Release Roadmap</h1>
    <p>Where we've been, where we are, and where we're going. Engineering milestones shipped as <strong>v2&ndash;v4</strong> release candidates; the <strong>public product line stays on 7.77.x</strong> &mdash; God&rsquo;s number does not &ldquo;count backward&rdquo; to an older semver like 5.0.</p>
</div>

<div class="container">

    <!-- ── Version Timeline ────────────────────────────────── -->
    <div class="section">
        <h2>Version Timeline</h2>
        <div class="timeline">
            <div class="tl-item done">
                <h3>v2.0 RC1–RC3 <span class="tag tag-done">Done</span></h3>
                <div class="date">March 2026</div>
                <p>First bootable ISOs. Debian Bookworm base, kernel 6.1.0-44 LTS. Live-build pipeline established. RC3 was the first verified-bootable ISO — resolved the dual kernel-naming bug that caused boot failures on RC1–RC2.</p>
            </div>
            <div class="tl-item done">
                <h3>v3.0 RC4–RC5 <span class="tag tag-done">Done</span></h3>
                <div class="date">March–April 2026</div>
                <p>Rebased from Bookworm → Trixie (Debian 13). Kernel upgraded to 6.12 series. New hooks: network hardening (nftables, MAC randomization), full disk encryption (LUKS2).</p>
            </div>
            <div class="tl-item done">
                <h3>v4.0 RC6 <span class="tag tag-done">Done</span></h3>
                <div class="date">April 2026</div>
                <p>Early hook milestone (12 hooks at this stage; later expanded to 150). 32 security modules configured. Calamares graphical installer with FDE option. Full security audit of hook code.</p>
            </div>
            <div class="tl-item done">
                <h3>v4.0 RC7 — Kernel 7.0 <span class="tag tag-done">Done</span></h3>
                <div class="date">April 2026</div>
                <p>First distro to ship Linux kernel 7.0.12, custom-compiled from Linus Torvalds' mainline tree. Three kernel-7-exclusive mitigations: ITS, TSA, VMSCAPE. 24 total CPU mitigations.</p>
            </div>
            <div class="tl-item done">
                <h3>v4.0 RC8 <span class="tag tag-done">Done</span></h3>
                <div class="date">April 2026</div>
                <p>Enterprise security hardening. 32 security modules. 3 dedicated security hooks. Stability fixes, installer polish, documentation pass. Community infrastructure launched (GoForge issues, hardware compatibility list). 2.4 GB ISO. Superseded by GA.</p>
            </div>
            <div class="tl-item current">
                <h3>v7.77 GA &ldquo;Kingdom of God Edition&rdquo; <span class="tag tag-now">Current</span></h3>
                <div class="date">April 11, 2026</div>
                <p>The Kingdom of God Edition. 157 build hooks. 41 security modules + Omahon Seal. AKJV Bible (94 books, 39,482 verses). 27-track worship album. GPU compute, eternal storage, sovereign identity, container runtime, AI dev stack, terminal power tools. God&rsquo;s number on every byte.</p>
            </div>
            <div class="tl-item future">
                <h3>v7.77.x LTS <span class="tag tag-planned">Planned</span></h3>
                <div class="date">Target: Q3 2026</div>
                <p>First Long Term Support release. 2-year security patch commitment. Kernel track pinned to stable LTS (7.x when available). Automated security updates via apt.</p>
            </div>
            <div class="tl-item future">
                <h3>v7.77.1 &mdash; Multi-arch + session hardening <span class="tag tag-planned">Planned</span></h3>
                <div class="date">Target: Q4 2026</div>
                <p>ARM64 images (Raspberry Pi 5, Apple Silicon via UTM / virt), optional Wayland session, Alfred Agent as a first-class system service. Same Kingdom line &mdash; still <strong>7.77</strong>, not a separate &ldquo;5.0&rdquo; product generation.</p>
            </div>
        </div>
    </div>

    <!-- ── Release Cadence ─────────────────────────────────── -->
    <div class="section">
        <h2>Release Cadence</h2>
        <p>How we plan to version and maintain Alfred Linux going forward.</p>
        <div class="cadence-grid">
            <div class="cadence-card">
                <div class="value">RC → GA</div>
                <h3>Release Candidates</h3>
                <p>RCs are functional but not recommended for production. Each RC adds features or fixes from the previous. GA is the first production-ready version.</p>
            </div>
            <div class="cadence-card">
                <div class="value">2 Years</div>
                <h3>LTS Support Window</h3>
                <p>LTS releases receive security patches for 2 years. Critical CVEs patched within 7 days. Kernel and userland updates via standard apt.</p>
            </div>
            <div class="cadence-card">
                <div class="value">7.77.x</div>
                <h3>Kingdom line cadence</h3>
                <p>Feature and arch expansions ship as <strong>7.77.x</strong> (e.g. multi-arch, LTS) under the same God-numbered line &mdash; not a reset to &ldquo;v5&rdquo; or &ldquo;v8.&rdquo;</p>
            </div>
            <div class="cadence-card">
                <div class="value">KCL-1.0</div>
                <h3>Always Open Source</h3>
                <p>Every version, every hook, every build script — publicly auditable on <a href="/forge/explore/repos">GoForge</a>. No closed modules, no enterprise-only features.</p>
            </div>
        </div>
    </div>

    <!-- ── GA Goals ────────────────────────────────────────── -->
    <div class="section">
        <h2>v7.77 GA — Goals Checklist</h2>
        <p>What was required to ship v7.77 Kingdom of God Edition &mdash; and where each goal landed.</p>
        <table class="goal-table">
            <thead>
                <tr><th>Goal</th><th>Status</th><th>Tracking</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td class="item">All 157 build hooks passing clean build</td>
                    <td><span style="color:var(--green)">✓ Done</span></td>
                    <td><a href="/forge/commander/alfredlinux.com">alfred-linux repo</a></td>
                </tr>
                <tr>
                    <td class="item">41 security modules active on boot (incl. Omahon Seal)</td>
                    <td><span style="color:var(--green)">✓ Done</span></td>
                    <td><a href="/security">Security page</a></td>
                </tr>
                <tr>
                    <td class="item">Kernel 7.0 compiled and booting</td>
                    <td><span style="color:var(--green)">✓ Done</span></td>
                    <td>RC7+</td>
                </tr>
                <tr>
                    <td class="item">Calamares installer with FDE</td>
                    <td><span style="color:var(--green)">✓ Done</span></td>
                    <td>RC6+</td>
                </tr>
                <tr>
                    <td class="item">DistroWatch submission</td>
                    <td><span style="color:var(--green)">✓ Submitted</span></td>
                    <td>April 2026</td>
                </tr>
                <tr>
                    <td class="item">Hardware compatibility list (20+ configs)</td>
                    <td><span style="color:var(--amber)">In progress</span></td>
                    <td><a href="/hardware">HCL page</a></td>
                </tr>
                <tr>
                    <td class="item">Community contribution guide</td>
                    <td><span style="color:var(--green)">✓ Done</span></td>
                    <td><a href="/community">Community page</a></td>
                </tr>
                <tr>
                    <td class="item">GPG-signed release ISOs</td>
                    <td><span style="color:var(--green)">✓ Done</span></td>
                    <td>GA — RSA-4096, Key ID 32BCEDE8C8DD8B00</td>
                </tr>
                <tr>
                    <td class="item">Public kernel / GoForge supply-chain transparency</td>
                    <td><span style="color:var(--green)">✓ Done</span></td>
                    <td><a href="/security-kernel">/security-kernel</a> + <a href="/forge/commander/alfredlinux.com/raw/branch/main/docs/GOFORGE-INFRASTRUCTURE-UPGRADE.txt">operator runbook</a></td>
                </tr>
                <tr>
                    <td class="item">ARM64 research complete</td>
                    <td><span style="color:var(--amber)">Research phase</span></td>
                    <td><a href="/forge/commander/alfredlinux.com/src/branch/main/docs/ARM64_BUILD_INVESTIGATION.md">ARM64 doc</a></td>
                </tr>
                <tr>
                    <td class="item">Automated security update pipeline</td>
                    <td><span style="color:var(--text-dim)">Planned</span></td>
                    <td>7.77.x LTS target</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Track Progress ──────────────────────────────────── -->
    <div class="section">
        <h2>Track Progress</h2>
        <p>All roadmap items are tracked as issues on GoForge. You can watch progress in real time:</p>
        <ul style="list-style:none;padding:0;margin:1rem 0;">
            <li style="padding:0.5rem 0 0.5rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">→</span> <a href="/forge/commander/alfredlinux.com/issues">alfred-linux issues</a> — build system, hooks, ISO bugs</li>
            <li style="padding:0.5rem 0 0.5rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">→</span> <a href="/forge/commander/alfred-ide/issues">alfred-commander issues</a> — IDE extension features and bugs</li>
            <li style="padding:0.5rem 0 0.5rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">→</span> <a href="/forge/commander/alfred-agent/issues">alfred-agent issues</a> — AI agent runtime</li>
            <li style="padding:0.5rem 0 0.5rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">→</span> <a href="/releases">Release history</a> — all previous RC changelogs</li>
        </ul>
    </div>

</div>

<footer>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)</p>
</footer>

<script>
document.querySelector('.nav-toggle')?.addEventListener('click', () => {
    document.querySelector('.nav-links').classList.toggle('open');
});
</script>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
