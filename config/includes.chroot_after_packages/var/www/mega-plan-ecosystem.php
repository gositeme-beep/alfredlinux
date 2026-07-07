<?php
/**
 * Ecosystem Mega Plan (L1–L9) — invite-only, GoSiteMe account required, access logged.
 *
 * Operator: set GOSITEME_MEGA_PLAN_INVITE_SECRET in .env.php (recommended) or .env (getenv).
 * Logs: domains/root.com/private/mega-plan-access.log (JSON lines).
 */
declare(strict_types=1);

$page_title       = 'Ecosystem Mega Plan (briefing) | GoSiteMe';
$page_description = 'Strategic roadmap: Alfred Linux, QGSM, IPFS anchoring, governance, and execution levels L1–L9. Access restricted.';
$_mega_host       = preg_replace('/^www\./i', '', strtolower((string)($_SERVER['HTTP_HOST'] ?? 'root.com')));
$page_canonical   = 'https://' . $_mega_host . '/mega-plan-ecosystem';
$page_robots      = 'noindex, nofollow';

require_once __DIR__ . '/includes/site-header.inc.php';
require_once __DIR__ . '/includes/mega-plan-access.inc.php';

$errorMsg = '';
$cid      = mega_plan_client_id();
$logged   = mega_plan_is_logged_in();
$configOk = mega_plan_invite_configured();

if ($logged && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'unlock') {
        if (!mega_plan_csrf_validate(isset($_POST['csrf']) ? (string)$_POST['csrf'] : null)) {
            $errorMsg = 'Security token expired. Refresh the page and try again.';
        } else {
            $invite = (string)($_POST['mega_plan_invite'] ?? '');
            if (mega_plan_verify_invite($invite)) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $_SESSION['mega_plan_invite_ok'] = true;
                mega_plan_log($cid, 'invite_unlock');
                header('Location: /mega-plan-ecosystem.php', true, 303);
                exit;
            }
            $errorMsg = 'That invite code is not valid.';
            mega_plan_log($cid, 'invite_fail');
        }
    } elseif ($act === 'logout_briefing') {
        if (mega_plan_csrf_validate(isset($_POST['csrf']) ? (string)$_POST['csrf'] : null)) {
            unset($_SESSION['mega_plan_invite_ok'], $_SESSION['mega_plan_view_logged'], $_SESSION['mega_plan_csrf']);
            mega_plan_log($cid, 'briefing_lock_reset');
        }
        header('Location: /mega-plan-ecosystem.php', true, 303);
        exit;
    }
}

$unlocked     = mega_plan_invite_ok();
// Site owner (internal client_id 33) always sees the briefing so the page isn’t empty before invite env is set.
$isSiteOwner = ($cid === 33);
$showPlan    = $logged && (($configOk && $unlocked) || $isSiteOwner);

if ($logged && $showPlan && empty($_SESSION['mega_plan_view_logged'])) {
    mega_plan_log($cid, $isSiteOwner ? 'view_plan_owner' : 'view_plan_first');
    $_SESSION['mega_plan_view_logged'] = true;
}
?>

<style>
.mega-wrap{max-width:920px;margin:0 auto;padding:2rem 1.25rem 5rem;color:rgba(255,255,255,.9);line-height:1.65;}
.mega-wrap h1{font-size:clamp(1.6rem,4vw,2.2rem);margin:0 0 .75rem;background:linear-gradient(135deg,#00d4ff,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.mega-lead{color:rgba(255,255,255,.55);font-size:.95rem;margin:0 0 2rem;}
.mega-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:1.5rem 1.35rem;margin:0 0 1.25rem;}
.mega-card h2{font-size:1.05rem;margin:0 0 .75rem;color:#00d4ff;}
.mega-card p,.mega-card li{font-size:.9rem;color:rgba(255,255,255,.78);margin:0 0 .6rem;}
.mega-card ul{padding-left:1.2rem;}
.mega-alert{border:1px solid rgba(248,113,113,.4);background:rgba(248,113,113,.08);border-radius:12px;padding:1rem 1.2rem;margin:0 0 1.25rem;color:#fecaca;font-size:.9rem;}
.mega-ok{border-color:rgba(52,211,153,.35);background:rgba(52,211,153,.08);color:#a7f3d0;}
.mega-form label{display:block;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);margin-bottom:.35rem;}
.mega-form input[type=password],.mega-form input[type=text]{width:100%;max-width:420px;padding:.75rem 1rem;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:rgba(0,0,0,.35);color:#fff;font-size:1rem;}
.mega-form .btn-row{display:flex;flex-wrap:wrap;gap:.75rem;margin-top:1rem;align-items:center;}
.mega-form button,.mega-wrap .btn-inline{padding:.65rem 1.25rem;border-radius:10px;border:none;font-weight:600;cursor:pointer;font-size:.9rem;}
.mega-form button.primary{background:linear-gradient(135deg,#00a8ff,#8b5cf6);color:#fff;}
.mega-wrap a.link{color:#67e8f9;text-decoration:underline;}
details.mega-lvl{border:1px solid rgba(255,255,255,.08);border-radius:12px;margin:0 0 .65rem;background:rgba(0,0,0,.2);}
details.mega-lvl > summary{cursor:pointer;padding:.85rem 1.1rem;font-weight:600;color:#e2e8f0;list-style:none;}
details.mega-lvl > summary::-webkit-details-marker{display:none;}
details.mega-lvl[open] > summary{border-bottom:1px solid rgba(255,255,255,.08);}
details.mega-lvl .inner{padding:0 1.1rem 1.1rem;font-size:.88rem;color:rgba(255,255,255,.75);}
.mega-table{width:100%;border-collapse:collapse;font-size:.82rem;margin:.5rem 0;}
.mega-table th,.mega-table td{padding:.45rem .5rem;border-bottom:1px solid rgba(255,255,255,.08);text-align:left;}
.mega-table th{color:#94a3b8;font-weight:600;}
.mega-meta{font-size:.8rem;color:rgba(255,255,255,.45);margin-top:2rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,.08);}
code.inline{background:rgba(0,0,0,.45);padding:.15rem .4rem;border-radius:6px;font-size:.82em;}
</style>

<main class="main-content" id="main">
<section class="section" style="padding-top:1rem;">
<div class="mega-wrap">

<h1>Ecosystem mega plan</h1>
<p class="mega-lead">Alfred Linux, GoSiteMe / meta-dome, QGSM, IPFS anchoring, governance, and execution depth (levels 1–9). This page is not indexed by search engines.</p>

<?php if (!$logged): ?>
    <div class="mega-card">
        <h2>GoSiteMe account required</h2>
        <p>We record <strong>which account</strong> opened this briefing (by client ID) after you enter the invite code. Create a free account or sign in first.</p>
        <p class="btn-row" style="margin-top:1rem;">
            <a class="btn btn-primary" href="/register">Create account</a>
            <a class="btn btn-outline" href="<?php echo htmlspecialchars('/login?redirect=' . rawurlencode('/mega-plan-ecosystem.php'), ENT_QUOTES, 'UTF-8'); ?>">Sign in</a>
        </p>
    </div>
<?php elseif (!$showPlan): ?>
    <?php if (!$configOk): ?>
    <div class="mega-card mega-alert">
        This briefing is not configured yet (missing <code class="inline">GOSITEME_MEGA_PLAN_INVITE_SECRET</code> on the server). Contact your GoSiteMe administrator.
    </div>
    <?php else: ?>
    <div class="mega-card mega-form">
        <h2>Invite code</h2>
        <p>Enter the code you were given. It is checked on the server; use a unique long secret in production.</p>
        <?php if ($errorMsg !== ''): ?>
            <div class="mega-alert" style="margin-bottom:1rem;"><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" action="/mega-plan-ecosystem.php" autocomplete="off">
            <input type="hidden" name="action" value="unlock">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(mega_plan_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label for="mega_plan_invite">Invite code</label>
            <input id="mega_plan_invite" name="mega_plan_invite" type="password" required minlength="8" placeholder="Invite code" autofocus>
            <div class="btn-row">
                <button type="submit" class="primary">Unlock briefing</button>
            </div>
        </form>
        <p style="margin-top:1.25rem;font-size:.85rem;color:rgba(255,255,255,.5);">Signed in as client ID <strong><?php echo (int)$cid; ?></strong>. Not you? <a class="link" href="/logout.php">Sign out</a>.</p>
    </div>
    <?php endif; ?>
<?php else: ?>

    <?php if ($isSiteOwner && !$configOk): ?>
    <div class="mega-card mega-ok" style="margin-bottom:1.25rem;">
        <strong>Your account always has full access here</strong> — the L1–L9 briefing is below even before invite codes are turned on for guests.
        To let <em>other</em> logged-in people in, add <code class="inline">GOSITEME_MEGA_PLAN_INVITE_SECRET</code> to <code class="inline">.env.php</code> (or <code class="inline">.env</code>), restart PHP if needed, then share that value as their invite. Access attempts are appended to <code class="inline">private/mega-plan-access.log</code> on this domain. (The Shabbat line elsewhere on the page is from the normal site layout, not this briefing.)
    </div>
    <?php endif; ?>

    <?php if ($unlocked && $configOk): ?>
    <div class="mega-card mega-ok" style="margin-bottom:1.5rem;">
        <strong>Briefing unlocked</strong> (invite code). You can clear only this briefing on this browser — you’ll need the code again next time.
    </div>
    <form method="post" action="/mega-plan-ecosystem.php" style="margin:-.5rem 0 1.5rem;">
        <input type="hidden" name="action" value="logout_briefing">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(mega_plan_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn-inline" style="background:rgba(255,255,255,.1);color:#fff;">Lock briefing again on this browser</button>
    </form>
    <?php endif; ?>

    <details class="mega-lvl" open>
        <summary>Level 1 — Charter (north star)</summary>
        <div class="inner">
            <p><strong>Goal:</strong> One stack where Alfred Linux, GoSiteMe / meta-dome, and QGSM/GSM reinforce each other: trustworthy OS artifacts, fair agent economics, verifiable releases and large blobs—without forcing every machine to run a validator.</p>
            <p><strong>Rules:</strong> IPFS (or similar) remains the <em>blob</em> layer; the chain attests roots, policy, incentives, and governance. Default OS updates stay boring (HTTPS + signatures + mirrors). Big whitepaper claims ship only behind testnet, audit, and incident gates.</p>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 2 — Nine strategic pillars</summary>
        <div class="inner">
            <table class="mega-table">
                <thead><tr><th>#</th><th>Pillar</th><th>Outcome</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>OS trust &amp; supply chain</td><td>Signed releases, CI gates, SBOM / advisory hygiene.</td></tr>
                    <tr><td>2</td><td>Platform security</td><td>Domains, APIs, DB, hooks—same bar as Alfred.</td></tr>
                    <tr><td>3</td><td>QGSM core protocol</td><td>PQ L1 + DPoC + VM: spec → code → testnet → audits.</td></tr>
                    <tr><td>4</td><td>Bridges &amp; liquidity</td><td>GSM ↔ QGSM and externals: limits, audits, circuit breakers.</td></tr>
                    <tr><td>5</td><td>IPFS + chain</td><td>CID manifests, optional pinning market / treasury pins.</td></tr>
                    <tr><td>6</td><td>Identity &amp; compliance</td><td>Passport-linked flows without surveillance-by-default OS.</td></tr>
                    <tr><td>7</td><td>Economics &amp; governance</td><td>Tokenomics, treasuries, voting—simulated + legal where ramps touch.</td></tr>
                    <tr><td>8</td><td>Ops &amp; SRE</td><td>Nodes, gateways, monitoring, runbooks.</td></tr>
                    <tr><td>9</td><td>Story &amp; ecosystem</td><td>Accurate docs and portals vs what is actually shipped.</td></tr>
                </tbody>
            </table>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 3 — Programs</summary>
        <div class="inner">
            <ul>
                <li><strong>Trust factory:</strong> release signing, public manifest / transparency story, repo-health-class gates on publish.</li>
                <li><strong>Content network:</strong> IPFS cluster / pin partners; gateway strategy; GC and SLAs.</li>
                <li><strong>Chain factory:</strong> client, consensus, PQ libs, fuzzing, formal methods where ROI is highest.</li>
                <li><strong>Bridge factory:</strong> one boring bridge first (e.g. Solana GSM), then expand with separate threat models.</li>
                <li><strong>Product factory:</strong> wallets, payouts, marketplace escrow, justice flows—feature-flagged by maturity.</li>
            </ul>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 4 — Workstreams</summary>
        <div class="inner">
            <p>Protocol engineering · Cryptography · Contracts / state machine · IPFS &amp; distribution · OS integration (Alfred) · Web + meta-dome · Security &amp; audits · Data &amp; analytics · Legal / compliance.</p>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 5 — Phased roadmap (adjustable)</summary>
        <div class="inner">
            <table class="mega-table">
                <thead><tr><th>Phase</th><th>Horizon</th><th>Focus</th></tr></thead>
                <tbody>
                    <tr><td>0 Foundations</td><td>0–6 mo</td><td>Harden Alfred + web; define IPFS↔QGSM boundary; manifest format v1.</td></tr>
                    <tr><td>1 Anchoring</td><td>6–12 mo</td><td>CID roots for key assets; optional OS index; verify without token.</td></tr>
                    <tr><td>2 QGSM testnet</td><td>12–18 mo</td><td>Internal/partner validators; no mainnet money; shadow/capped bridges.</td></tr>
                    <tr><td>3 IPFS incentives</td><td>18–24 mo</td><td>Optional staking/treasury for pinners; strict caps; slashing.</td></tr>
                    <tr><td>4 Mainnet</td><td>After audits</td><td>Migration, bridge limits, war room.</td></tr>
                    <tr><td>5 Scale</td><td>Ongoing</td><td>Exchanges, ramps—only after mainnet is boring at 3 a.m.</td></tr>
                </tbody>
            </table>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 6 — Deliverables</summary>
        <div class="inner">
            <p>Specs (MANIFEST-v1, bridge threat models), code (node, contracts, Alfred integration), ops (systemd/k8s, dashboards, backups), user-facing (whitepaper § IPFS, “verify this ISO” one-pager).</p>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 7 — Trust architecture</summary>
        <div class="inner">
            <p>CI and publishers push blobs to IPFS; roots and policy are recorded on QGSM (or interim signed JSON). Clients verify <strong>(attestation) ∧ (CID matches content)</strong>. Gateways and mirrors are optional performance layers, not the root of trust.</p>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 8 — Quality gates</summary>
        <div class="inner">
            <ul>
                <li>No production bridge without external audit + internal red team + circuit breaker.</li>
                <li>Default OS update path works without wallet or chain sync.</li>
                <li>IPFS path degrades to HTTPS mirror with same root hash.</li>
                <li>PQ claims match deployed algorithms (FIPS 203/204 naming).</li>
                <li>Governance can emergency-stop bridges and pinning contracts independently.</li>
            </ul>
        </div>
    </details>

    <details class="mega-lvl">
        <summary>Level 9 — Next 90 days</summary>
        <div class="inner">
            <ol>
                <li>Publish <code class="inline">MANIFEST-v1</code> (product, version, files, root CID, signatures).</li>
                <li>One pipeline: one artifact or site bundle → CID + signed JSON; tag or DB pointer first, chain later.</li>
                <li>Whitepaper section: content addressing &amp; distribution (honest IPFS vs chain vs HTTPS).</li>
                <li>Security sweep on <code class="inline">domains/</code> + Alfred hooks/scripts.</li>
                <li>Choose IPFS ops model and document SLA.</li>
                <li>QGSM spike: block size vs PQ signature volume—reconcile with published targets.</li>
                <li>Bridge roadmap: Solana GSM only until one bridge is boring.</li>
                <li>Incident playbook: bridge pause, key compromise, bad CID.</li>
                <li>Quarterly pillar scorecard (green / yellow / red).</li>
            </ol>
        </div>
    </details>

    <p class="mega-meta">Access audit: JSON lines in <code class="inline">&lt;domain&gt;/private/mega-plan-access.log</code>. Rotate with <code class="inline">private/MEGA-PLAN-LOGROTATE.example</code> on the server. Canonical strategy + manifest spec in git: <code class="inline">law/alfredlinux-com-source-live/docs/MEGA-PLAN-L1-L9.txt</code> and <code class="inline">docs/MANIFEST-v1.txt</code>.</p>

<?php endif; ?>

</div>
</section>
</main>

<?php require_once __DIR__ . '/includes/site-footer.inc.php';
