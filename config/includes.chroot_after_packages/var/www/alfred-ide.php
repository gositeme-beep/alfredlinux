<?php
$page_title = 'Alfred IDE - Official Launch | Sovereign Cloud IDE by GoSiteMe';
$page_description = 'Alfred IDE is the official GoSiteMe browser IDE: sovereign account access, Alfred built in, terminal, Git, MCP tools, workspace launch, and customer workspaces tied to your service.';
$page_canonical = 'https://root.com/alfred-ide.php';
$page_og_title = 'Alfred IDE - Official Launch';
$page_og_description = 'Launch Alfred IDE in your browser today. Alfred built in, Git and terminal ready, sovereign account flow, and workspace access tied to your GoSiteMe services.';
include __DIR__ . '/includes/site-header.inc.php';

require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/includes/alfred-workspace-launch.inc.php';
require_once __DIR__ . '/includes/alfred-ide-bearer.inc.php';

$isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$clientId = (int) ($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
$isCommander = $isLoggedIn && $clientId === 33;

$sharedDb = null;
try {
    $sharedDb = getSharedDB();
} catch (Throwable $dbBootstrapError) {
    $sharedDb = null;
}

$runtimeReachable = false;
$runtimeLatencyMs = null;
$runtimeStart = microtime(true);
$runtimeSocket = @fsockopen('127.0.0.1', 8443, $runtimeErrNo, $runtimeErrStr, 0.75);
if (is_resource($runtimeSocket)) {
    $runtimeReachable = true;
    $runtimeLatencyMs = (int) round((microtime(true) - $runtimeStart) * 1000);
    fclose($runtimeSocket);
}

$customerWorkspaceEligible = false;
$workspaceCheckKnown = false;
if ($isLoggedIn && !$isCommander && $sharedDb instanceof PDO) {
    try {
        $customerWorkspaceEligible = alfred_workspace_client_has_access($sharedDb, $clientId);
        $workspaceCheckKnown = true;
    } catch (Throwable $workspaceCheckError) {
        $workspaceCheckKnown = false;
    }
}

$launchUrl = $isCommander ? '/alfred-ide/' : '/alfred-ide-auth.php';
$launchLabel = $isCommander ? 'Open Commander Workspace' : ($isLoggedIn ? 'Open Workspace Access Gate' : 'Sign In To Launch');

$runtimeStatusLabel = $runtimeReachable ? 'Live runtime reachable' : 'Runtime check unavailable';
$runtimeStatusTone = $runtimeReachable ? 'live' : 'warn';
$runtimeStatusDetail = $runtimeReachable
    ? 'The Alfred IDE code-server runtime responded on the live stack' . ($runtimeLatencyMs !== null ? ' in about ' . $runtimeLatencyMs . ' ms.' : '.')
    : 'The landing page could not confirm a direct socket response from the IDE runtime right now. The public route may still work through the auth gate, but this should be treated as an operational signal to verify.';

$authStatusLabel = $isLoggedIn ? 'GoSiteMe identity detected' : 'Public page, protected app';
$authStatusTone = $isLoggedIn ? 'live' : 'idle';
$authStatusDetail = $isLoggedIn
    ? ($isCommander
        ? 'This session is the Commander account, so the IDE route should resolve toward the server workspace after token validation.'
        : 'This session is a customer account, so Alfred IDE should route through the sovereign auth gate and then into the correct customer workspace path if service access exists.')
    : 'This launch page stays public, while `/alfred-ide/` remains protected by the Alfred IDE auth gate and token flow.';

$workspaceStatusLabel = $isCommander
    ? 'Commander workspace path ready'
    : ($isLoggedIn
        ? ($workspaceCheckKnown
            ? ($customerWorkspaceEligible ? 'Customer workspace service found' : 'No active customer workspace service found')
            : 'Workspace eligibility check unavailable')
        : 'Customer routing resolves after sign-in');
$workspaceStatusTone = $isCommander || $customerWorkspaceEligible ? 'live' : ($isLoggedIn ? 'warn' : 'idle');
$workspaceStatusDetail = $isCommander
    ? 'The Commander account stays on the primary server workspace path.'
    : ($isLoggedIn
        ? ($workspaceCheckKnown
            ? ($customerWorkspaceEligible
                ? 'This account appears eligible for the service-linked Alfred workspace launch path.'
                : 'This account does not currently appear to have an active GoCodeMe service attached, so the workspace launch should be treated as not yet entitled.')
            : 'The landing page could not verify customer workspace entitlement from the current request.')
        : 'After sign-in, Alfred IDE routes customer accounts according to service entitlement instead of dropping everyone into one shared environment.');

$commandSurfaceLabel = 'Alfred panel ships today';
$commandSurfaceTone = 'live';
$commandSurfaceDetail = 'The current IDE layer already includes Alfred chat, voice STT/TTS, attachments, account stats, terminal launch, save actions, split editor, command palette access, and code insertion.';

$workspaceServices = [];
$workspaceServicesKnown = false;
$activeWorkspaceServiceCount = 0;
$primaryWorkspaceServiceId = null;
if ($isLoggedIn && !$isCommander && $sharedDb instanceof PDO) {
    try {
        $serviceStmt = $sharedDb->prepare("SELECT s.id, s.status, s.domain, s.username, s.next_due_date, s.billing_cycle, s.amount, p.name AS product_name
            FROM services s
            INNER JOIN products p ON p.id = s.product_id
            WHERE s.client_id = ?
              AND p.server_module = 'gocodeme'
            ORDER BY CASE WHEN s.status = 'Active' THEN 0 ELSE 1 END, s.id DESC
            LIMIT 4");
        $serviceStmt->execute([$clientId]);
        $workspaceServices = $serviceStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $workspaceServicesKnown = true;
        foreach ($workspaceServices as $workspaceService) {
            if (($workspaceService['status'] ?? '') === 'Active') {
                $activeWorkspaceServiceCount++;
                $primaryWorkspaceServiceId = $primaryWorkspaceServiceId ?: (int)($workspaceService['id'] ?? 0);
            }
        }
        if ($primaryWorkspaceServiceId === null && !empty($workspaceServices[0]['id'])) {
            $primaryWorkspaceServiceId = (int)$workspaceServices[0]['id'];
        }
    } catch (Throwable $workspaceServiceError) {
        $workspaceServices = [];
        $workspaceServicesKnown = false;
        $activeWorkspaceServiceCount = 0;
        $primaryWorkspaceServiceId = null;
    }
}

$ideToken = trim((string)($_COOKIE['alfred_ide_token'] ?? $_SESSION['ide_session_token'] ?? ''));
$ideSessionKnown = false;
$ideSessionValid = false;
if ($isLoggedIn && $sharedDb instanceof PDO) {
    $ideSessionKnown = true;
    if ($ideToken !== '') {
        try {
            $ideSessionValid = alfred_ide_lookup_user_by_token_hash($sharedDb, hash('sha256', $ideToken)) !== null;
        } catch (Throwable $ideSessionError) {
            $ideSessionKnown = false;
            $ideSessionValid = false;
        }
    }
}

$workspaceServiceCount = count($workspaceServices);
$accountControlUrl = $isLoggedIn ? '/pay/account/logins.php' : '/alfred-ide-auth.php';
$workspaceManageUrl = $primaryWorkspaceServiceId ? '/pay/account/service.php?id=' . $primaryWorkspaceServiceId : $accountControlUrl;

if ($isCommander) {
    $workspaceAccountLabel = 'Commander session active';
    $workspaceAccountTone = 'live';
    $workspaceAccountDetail = 'This signed-in session is the Commander account, so the primary server workspace can open directly without a customer-service lookup.';
} elseif ($isLoggedIn) {
    $workspaceAccountLabel = 'Customer session active';
    $workspaceAccountTone = 'live';
    $workspaceAccountDetail = 'This page can resolve Alfred IDE readiness against the signed-in GoSiteMe customer account instead of treating everyone like a guest.';
} else {
    $workspaceAccountLabel = 'Guest view only';
    $workspaceAccountTone = 'idle';
    $workspaceAccountDetail = 'The launch page stays public, but workspace readiness only becomes real after GoSiteMe sign-in.';
}

if ($isCommander) {
    $workspaceEntitlementLabel = 'Primary workspace unlocked';
    $workspaceEntitlementTone = 'live';
    $workspaceEntitlementDetail = 'Commander bypasses customer-service routing and stays on the live server workspace path.';
} elseif (!$isLoggedIn) {
    $workspaceEntitlementLabel = 'Service check starts after sign-in';
    $workspaceEntitlementTone = 'idle';
    $workspaceEntitlementDetail = 'Customer workspace access is resolved after account sign-in and an active Alfred IDE service lookup.';
} elseif ($workspaceServicesKnown) {
    if ($activeWorkspaceServiceCount > 0) {
        $workspaceEntitlementLabel = $activeWorkspaceServiceCount === 1
            ? '1 active Alfred IDE service'
            : $activeWorkspaceServiceCount . ' active Alfred IDE services';
        $workspaceEntitlementTone = 'live';
        $workspaceEntitlementDetail = 'This account has at least one active Alfred IDE service, so the launch control can mint a short-lived customer workspace handoff.';
    } elseif ($workspaceServiceCount > 0) {
        $workspaceEntitlementLabel = 'Workspace service found, but not active';
        $workspaceEntitlementTone = 'warn';
        $workspaceEntitlementDetail = 'An Alfred IDE service record exists on this account, but nothing active can launch right now.';
    } else {
        $workspaceEntitlementLabel = 'No Alfred IDE service found';
        $workspaceEntitlementTone = 'warn';
        $workspaceEntitlementDetail = 'This account does not appear to have Alfred IDE service entitlement yet, so launch should route through access selection instead of a workspace handoff.';
    }
} else {
    $workspaceEntitlementLabel = 'Service lookup unavailable';
    $workspaceEntitlementTone = 'warn';
    $workspaceEntitlementDetail = 'The landing page could not load Alfred IDE service state from the current request.';
}

if (!$isLoggedIn) {
    $workspaceSessionLabel = 'IDE session begins after sign-in';
    $workspaceSessionTone = 'idle';
    $workspaceSessionDetail = 'The Alfred IDE workspace token is only issued after the auth gate runs.';
} elseif ($ideToken === '') {
    $workspaceSessionLabel = 'No active IDE token yet';
    $workspaceSessionTone = 'warn';
    $workspaceSessionDetail = 'This account is signed in on the web, but Alfred IDE has not issued or refreshed a workspace token yet. Opening Alfred IDE will create the proper handoff.';
} elseif ($ideSessionKnown && $ideSessionValid) {
    $workspaceSessionLabel = 'IDE token validates cleanly';
    $workspaceSessionTone = 'live';
    $workspaceSessionDetail = 'The stored Alfred IDE token resolves to a valid session and should support workspace APIs plus the in-IDE Alfred panel.';
} elseif ($ideSessionKnown) {
    $workspaceSessionLabel = 'Stored IDE token needs refresh';
    $workspaceSessionTone = 'warn';
    $workspaceSessionDetail = 'A workspace token exists, but this page could not validate it as a current Alfred IDE session. Re-entering the IDE should refresh it.';
} else {
    $workspaceSessionLabel = 'IDE token check unavailable';
    $workspaceSessionTone = 'warn';
    $workspaceSessionDetail = 'The page could not confirm current Alfred IDE token state from the live request.';
}

if ($isCommander) {
    $workspaceLaunchLabel = 'Direct launch path ready';
    $workspaceLaunchTone = 'live';
    $workspaceLaunchDetail = 'The launch control can send this session straight to `/alfred-ide/` without billing SSO.';
} elseif (!$isLoggedIn) {
    $workspaceLaunchLabel = 'Launch will open the auth gate';
    $workspaceLaunchTone = 'idle';
    $workspaceLaunchDetail = 'One click first resolves Alfred IDE sign-in, then the workspace path.';
} elseif ($activeWorkspaceServiceCount > 0) {
    $workspaceLaunchLabel = 'Billing SSO handoff ready';
    $workspaceLaunchTone = 'live';
    $workspaceLaunchDetail = 'One click can mint a short-lived workspace URL and route this account into the correct customer workspace.';
} elseif ($workspaceServiceCount > 0) {
    $workspaceLaunchLabel = 'Manage service before launch';
    $workspaceLaunchTone = 'warn';
    $workspaceLaunchDetail = 'The account has Alfred IDE service history, but nothing active can launch until billing or service state is corrected.';
} else {
    $workspaceLaunchLabel = 'Choose or activate access';
    $workspaceLaunchTone = 'warn';
    $workspaceLaunchDetail = 'This account needs active Alfred IDE access before workspace handoff can complete.';
}
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Alfred IDE",
  "applicationCategory": "DeveloperApplication",
  "operatingSystem": "Web, Windows, Linux",
  "url": "https://root.com/alfred-ide.php",
  "description": "Sovereign cloud IDE with AI agents, terminal, Git, MCP tools, and workspace management. Free tier available.",
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD", "description": "Free tier with upgrade options" },
  "author": { "@type": "Organization", "name": "GoSiteMe", "url": "https://root.com" },
  "featureList": ["AI Agent Integration", "Terminal Access", "Git Integration", "MCP Tools (500+)", "Customer Workspaces", "Post-Quantum Security"]
}
</script>

<style>
    :root {
        --ide-ink: #f3efe6;
        --ide-muted: #b4b0a7;
        --ide-bg: #0b0f12;
        --ide-surface: rgba(17, 24, 28, 0.86);
        --ide-border: rgba(255, 255, 255, 0.09);
        --ide-cyan: #4dd6ff;
        --ide-gold: #e0b14a;
        --ide-green: #39c98b;
        --ide-red: #ff7a59;
    }

    body {
        background:
            radial-gradient(circle at 18% 18%, rgba(224, 177, 74, 0.12), transparent 28%),
            radial-gradient(circle at 82% 12%, rgba(77, 214, 255, 0.12), transparent 26%),
            linear-gradient(180deg, #091015 0%, #0b0f12 42%, #10171b 100%);
        color: var(--ide-ink);
    }

    .ide-launch-shell {
        max-width: 1220px;
        margin: 0 auto;
        padding: 124px 24px 88px;
        position: relative;
    }

    .ide-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        gap: 28px;
        align-items: stretch;
    }

    .ide-hero-copy,
    .ide-hero-panel,
    .ide-section-card,
    .ide-launch-card,
    .ide-feature-card,
    .ide-truth-card,
    .ide-legacy-card {
        background: var(--ide-surface);
        border: 1px solid var(--ide-border);
        border-radius: 28px;
        box-shadow: 0 20px 70px rgba(0, 0, 0, 0.28);
        backdrop-filter: blur(18px);
    }

    .ide-hero-copy {
        padding: 38px;
        position: relative;
        overflow: hidden;
    }

    .ide-hero-copy::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(224, 177, 74, 0.08), transparent 32%, rgba(77, 214, 255, 0.08));
        pointer-events: none;
    }

    .ide-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 9px 16px;
        border-radius: 999px;
        border: 1px solid rgba(224, 177, 74, 0.28);
        background: rgba(224, 177, 74, 0.1);
        color: var(--ide-gold);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 22px;
    }

    .ide-hero-copy h1 {
        margin: 0 0 18px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(2.8rem, 6vw, 5.1rem);
        line-height: 0.95;
        letter-spacing: -0.05em;
    }

    .ide-hero-copy h1 span {
        display: block;
        color: var(--ide-gold);
    }

    .ide-hero-copy p {
        max-width: 690px;
        margin: 0 0 28px;
        color: var(--ide-muted);
        font-size: 1.05rem;
        line-height: 1.75;
    }

    .ide-hero-actions {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .ide-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 54px;
        padding: 0 22px;
        border-radius: 16px;
        border: 1px solid transparent;
        text-decoration: none;
        font-weight: 700;
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
    }

    .ide-btn:hover {
        transform: translateY(-2px);
    }

    .ide-btn-primary {
        background: linear-gradient(135deg, var(--ide-gold), #f7d27a);
        color: #11161a;
    }

    .ide-btn-secondary {
        border-color: rgba(77, 214, 255, 0.26);
        background: rgba(77, 214, 255, 0.08);
        color: var(--ide-cyan);
    }

    .ide-note {
        color: rgba(243, 239, 230, 0.65);
        font-size: 0.88rem;
    }

    .ide-hero-panel {
        padding: 28px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .ide-panel-label,
    .ide-section-kicker {
        margin: 0;
        color: var(--ide-cyan);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .ide-panel-title,
    .ide-section-title {
        margin: 0;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.6rem;
        line-height: 1.1;
    }

    .ide-panel-list {
        display: grid;
        gap: 12px;
    }

    .ide-panel-item {
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr);
        gap: 12px;
        align-items: start;
        padding: 14px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .ide-panel-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(224, 177, 74, 0.12);
        color: var(--ide-gold);
        font-size: 1rem;
    }

    .ide-panel-item strong {
        display: block;
        margin-bottom: 4px;
        font-size: 0.98rem;
    }

    .ide-panel-item p,
    .ide-launch-card p,
    .ide-feature-card p,
    .ide-truth-card p,
    .ide-legacy-card p {
        margin: 0;
        color: var(--ide-muted);
        line-height: 1.65;
        font-size: 0.92rem;
    }

    .ide-trust-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin: 22px 0 34px;
    }

    .ide-truth-card {
        padding: 18px;
        border-radius: 20px;
    }

    .ide-truth-card strong {
        display: block;
        margin-bottom: 8px;
        font-size: 1rem;
    }

    .ide-status-grid,
    .ide-flow-grid {
        display: grid;
        gap: 18px;
        margin-top: 22px;
    }

    .ide-status-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .ide-flow-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ide-status-card,
    .ide-flow-card {
        padding: 22px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .ide-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    .ide-status-pill.live {
        color: var(--ide-green);
        background: rgba(57, 201, 139, 0.12);
        border: 1px solid rgba(57, 201, 139, 0.22);
    }

    .ide-status-pill.warn {
        color: var(--ide-red);
        background: rgba(255, 122, 89, 0.12);
        border: 1px solid rgba(255, 122, 89, 0.22);
    }

    .ide-status-pill.idle {
        color: var(--ide-cyan);
        background: rgba(77, 214, 255, 0.1);
        border: 1px solid rgba(77, 214, 255, 0.2);
    }

    .ide-status-pill.available {
        color: var(--ide-gold);
        background: rgba(224, 177, 74, 0.12);
        border: 1px solid rgba(224, 177, 74, 0.22);
    }

    .ide-status-pill.planned {
        color: var(--ide-cyan);
        background: rgba(77, 214, 255, 0.08);
        border: 1px solid rgba(77, 214, 255, 0.18);
    }

    .ide-status-card h3,
    .ide-flow-card h3 {
        margin: 0 0 10px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.12rem;
    }

    .ide-status-card p,
    .ide-flow-card p {
        margin: 0;
        color: var(--ide-muted);
        line-height: 1.65;
        font-size: 0.92rem;
    }

    .ide-step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 14px;
        margin-bottom: 14px;
        background: linear-gradient(135deg, rgba(224, 177, 74, 0.9), rgba(77, 214, 255, 0.9));
        color: #10171b;
        font-weight: 900;
        font-family: 'Space Grotesk', sans-serif;
    }

    .ide-flow-card ul {
        margin: 14px 0 0;
        padding-left: 18px;
        color: var(--ide-muted);
        line-height: 1.7;
        font-size: 0.9rem;
    }

    .ide-sections {
        display: grid;
        gap: 28px;
    }

    .ide-section-card {
        padding: 32px;
    }

    .ide-launch-grid,
    .ide-feature-grid {
        display: grid;
        gap: 18px;
        margin-top: 22px;
    }

    .ide-launch-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ide-service-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 18px;
    }

    .ide-feature-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ide-launch-card,
    .ide-feature-card {
        padding: 22px;
        border-radius: 24px;
    }

    .ide-launch-card h3,
    .ide-feature-card h3,
    .ide-legacy-card h3 {
        margin: 14px 0 10px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.2rem;
    }

    .ide-launch-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(57, 201, 139, 0.12);
        border: 1px solid rgba(57, 201, 139, 0.2);
        color: var(--ide-green);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .ide-feature-card i,
    .ide-launch-card i,
    .ide-legacy-card i {
        color: var(--ide-cyan);
        font-size: 1.1rem;
    }

    .ide-inline-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
        color: var(--ide-gold);
        font-weight: 700;
        text-decoration: none;
    }

    .ide-service-meta {
        display: grid;
        gap: 8px;
        margin-top: 12px;
        color: var(--ide-muted);
        font-size: 0.88rem;
    }

    .ide-service-meta strong {
        color: var(--ide-ink);
    }

    .ide-control-actions {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 24px;
    }

    .ide-control-message {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 18px;
        border: 1px solid transparent;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .ide-control-message.live {
        color: var(--ide-green);
        background: rgba(57, 201, 139, 0.1);
        border-color: rgba(57, 201, 139, 0.2);
    }

    .ide-control-message.warn {
        color: var(--ide-red);
        background: rgba(255, 122, 89, 0.1);
        border-color: rgba(255, 122, 89, 0.2);
    }

    .ide-control-message.idle {
        color: var(--ide-cyan);
        background: rgba(77, 214, 255, 0.08);
        border-color: rgba(77, 214, 255, 0.18);
    }

    .ide-btn.is-loading {
        opacity: 0.78;
        pointer-events: none;
    }

    .ide-legacy-card {
        margin-top: 22px;
        padding: 24px;
    }

    .ide-legacy-copy {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
    }

    .ide-final-cta {
        margin-top: 30px;
        padding: 34px;
        border-radius: 30px;
        background: linear-gradient(135deg, rgba(224, 177, 74, 0.12), rgba(77, 214, 255, 0.08));
        border: 1px solid rgba(224, 177, 74, 0.18);
        text-align: center;
    }

    .ide-final-cta h2 {
        margin: 0 0 10px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(1.8rem, 3vw, 2.6rem);
    }

    .ide-final-cta p {
        max-width: 700px;
        margin: 0 auto 22px;
        color: var(--ide-muted);
        line-height: 1.7;
    }

    @media (max-width: 1040px) {
        .ide-hero,
        .ide-launch-grid,
        .ide-feature-grid,
        .ide-trust-strip,
        .ide-status-grid,
        .ide-legacy-copy {
            grid-template-columns: 1fr 1fr;
        }

        .ide-hero {
            grid-template-columns: 1fr;
        }

        .ide-flow-grid {
            grid-template-columns: 1fr;
        }

        .ide-legacy-copy {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .ide-launch-shell {
            padding: 108px 16px 72px;
        }

        .ide-hero-copy,
        .ide-hero-panel,
        .ide-section-card,
        .ide-final-cta {
            padding: 24px;
        }

        .ide-trust-strip,
        .ide-launch-grid,
        .ide-feature-grid,
        .ide-status-grid,
        .ide-flow-grid {
            grid-template-columns: 1fr;
        }

        .ide-hero-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .ide-btn {
            width: 100%;
        }
    }
</style>

<main class="ide-launch-shell">
    <section class="ide-hero">
        <div class="ide-hero-copy">
            <div class="ide-badge"><i class="fas fa-bolt"></i> Official launch</div>
            <h1>Alfred IDE<span>is live.</span></h1>
            <p>Alfred IDE is now the official front door for GoSiteMe development: browser-native workspace access, Alfred built directly into the coding flow, sovereign account control, and a real launch path that fits the rest of the ecosystem.</p>
            <div class="ide-hero-actions">
                <a class="ide-btn ide-btn-primary" href="<?php echo htmlspecialchars($launchUrl, ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-rocket"></i> <?php echo htmlspecialchars($launchLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                <a class="ide-btn ide-btn-secondary" href="/pricing.php"><i class="fas fa-layer-group"></i> View access paths</a>
            </div>
            <div class="ide-note">GoCodeMe remains the legacy product name, but Alfred IDE is the official public launch name moving forward.</div>
        </div>

        <aside class="ide-hero-panel">
            <p class="ide-panel-label">What is live today</p>
            <h2 class="ide-panel-title">Launch truth, not vapor.</h2>
            <div class="ide-panel-list">
                <div class="ide-panel-item">
                    <div class="ide-panel-icon"><i class="fas fa-window-maximize"></i></div>
                    <div>
                        <strong>Browser IDE runtime is online</strong>
                        <p>Alfred IDE is already running on the live stack and protected by the GoSiteMe auth flow.</p>
                    </div>
                </div>
                <div class="ide-panel-item">
                    <div class="ide-panel-icon"><i class="fas fa-user-shield"></i></div>
                    <div>
                        <strong>Sovereign sign-in is already wired</strong>
                        <p>PIN and account verification flow through the Alfred IDE gate instead of a generic third-party login wall.</p>
                    </div>
                </div>
                <div class="ide-panel-item">
                    <div class="ide-panel-icon"><i class="fas fa-diagram-project"></i></div>
                    <div>
                        <strong>Customer workspaces stay service-aware</strong>
                        <p>Commander launches the server workspace directly. Customer launches stay tied to their own GoCodeMe service and billing state.</p>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    <div class="ide-trust-strip">
        <div class="ide-truth-card">
            <strong>Alfred built in</strong>
            <p>Chat, coding assistance, and account-aware tooling live inside the workspace instead of being bolted on after the fact.</p>
        </div>
        <div class="ide-truth-card">
            <strong>Terminal and Git ready</strong>
            <p>The live environment is positioned as a real working IDE, not a toy editor or read-only code viewer.</p>
        </div>
        <div class="ide-truth-card">
            <strong>MCP tool reach</strong>
            <p>GoSiteMe infrastructure already exposes deep tool access through the surrounding Alfred platform and middleware services.</p>
        </div>
        <div class="ide-truth-card">
            <strong>Sovereign account model</strong>
            <p>Access stays inside the GoSiteMe identity fabric instead of fragmenting the user into separate disconnected IDE identities.</p>
        </div>
    </div>

    <section class="ide-section-card">
        <p class="ide-section-kicker">Platform matrix</p>
        <h2 class="ide-section-title">Web is official now. Desktop is tiered honestly.</h2>
        <div class="ide-launch-grid">
            <article class="ide-launch-card">
                <span class="ide-status-pill live">Official now</span>
                <h3>Web / Cloud</h3>
                <p>Alfred IDE is fully official in the browser today. This is the flagship path: no install, sovereign sign-in, service-aware workspace routing, and the live Alfred IDE stack.</p>
                <div class="ide-service-meta">
                    <div><strong>Best path:</strong> open Alfred IDE in the browser</div>
                    <div><strong>Support level:</strong> flagship platform</div>
                </div>
                <a class="ide-inline-link" href="<?php echo htmlspecialchars($launchUrl, ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-arrow-right"></i> Open web Alfred IDE</a>
            </article>
            <article class="ide-launch-card">
                <span class="ide-status-pill available">Available now</span>
                <h3>Windows</h3>
                <p>A real Alfred IDE Windows build exists today as a portable desktop package. It is the next platform to productize fully after the web flagship is locked.</p>
                <div class="ide-service-meta">
                    <div><strong>Package:</strong> portable x64 ZIP</div>
                    <div><strong>Current stance:</strong> available, still being hardened</div>
                </div>
                <a class="ide-inline-link" href="/downloads/alfred-ide/"><i class="fas fa-arrow-right"></i> Get Alfred IDE for Windows</a>
            </article>
            <article class="ide-launch-card">
                <span class="ide-status-pill planned">Browser-first for now</span>
                <h3>Ubuntu / Linux</h3>
                <p>Ubuntu and Linux users should use the browser IDE today. Native Alfred IDE packaging is the next Linux track, but it should not be implied as finished before it is real.</p>
                <div class="ide-service-meta">
                    <div><strong>Current best path:</strong> launch the web IDE</div>
                    <div><strong>Next native target:</strong> Ubuntu/Debian packaging</div>
                </div>
                <a class="ide-inline-link" href="/alfred-ide.php"><i class="fas fa-arrow-right"></i> Use Alfred IDE in browser</a>
            </article>
        </div>
        <div class="ide-note" style="margin-top:18px;">Platform clarity matters more than pretending every build is equally mature. Web is the flagship, Windows is real and available, and Ubuntu/Linux stays browser-first until native Alfred IDE packaging is truly ready.</div>
        <div style="margin-top:16px;text-align:center;">
            <a href="/apps" style="font-size:13px;color:#34d399;font-weight:600;text-decoration:none;"><i class="fas fa-th" style="margin-right:4px;"></i> See all GoSiteMe apps & downloads</a>
        </div>
    </section>

    <div class="ide-sections">
        <section class="ide-section-card">
            <p class="ide-section-kicker">Operational snapshot</p>
            <h2 class="ide-section-title">What the live Alfred IDE stack says right now</h2>
            <div class="ide-status-grid">
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($runtimeStatusTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($runtimeStatusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>IDE runtime</h3>
                    <p><?php echo htmlspecialchars($runtimeStatusDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($authStatusTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($authStatusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>Access gate</h3>
                    <p><?php echo htmlspecialchars($authStatusDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($workspaceStatusTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($workspaceStatusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>Workspace routing</h3>
                    <p><?php echo htmlspecialchars($workspaceStatusDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($commandSurfaceTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($commandSurfaceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>In-IDE command surface</h3>
                    <p><?php echo htmlspecialchars($commandSurfaceDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
            </div>
        </section>

        <section class="ide-section-card">
            <p class="ide-section-kicker">Workspace readiness</p>
            <h2 class="ide-section-title">Can this account open Alfred IDE right now?</h2>
            <div class="ide-status-grid">
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($workspaceAccountTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($workspaceAccountLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>Account state</h3>
                    <p><?php echo htmlspecialchars($workspaceAccountDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($workspaceEntitlementTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($workspaceEntitlementLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>Service entitlement</h3>
                    <p><?php echo htmlspecialchars($workspaceEntitlementDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($workspaceSessionTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($workspaceSessionLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>IDE session</h3>
                    <p><?php echo htmlspecialchars($workspaceSessionDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
                <article class="ide-status-card">
                    <span class="ide-status-pill <?php echo htmlspecialchars($workspaceLaunchTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($workspaceLaunchLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>Launch handoff</h3>
                    <p><?php echo htmlspecialchars($workspaceLaunchDetail, ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
            </div>

            <div class="ide-launch-grid ide-service-grid">
                <?php if ($isCommander): ?>
                    <article class="ide-launch-card">
                        <span class="ide-launch-pill"><i class="fas fa-crown"></i> Primary path</span>
                        <h3>Commander workspace</h3>
                        <p>The Commander account stays on the main live Alfred IDE environment and can open it directly through the protected route.</p>
                        <div class="ide-service-meta">
                            <div><strong>Route:</strong> /alfred-ide/</div>
                            <div><strong>Mode:</strong> direct server workspace</div>
                        </div>
                    </article>
                <?php elseif ($isLoggedIn && $workspaceServicesKnown && !empty($workspaceServices)): ?>
                    <?php foreach ($workspaceServices as $workspaceService): ?>
                        <?php
                        $serviceStatus = (string)($workspaceService['status'] ?? 'Unknown');
                        $serviceTone = $serviceStatus === 'Active' ? 'live' : 'warn';
                        $serviceLabel = trim((string)($workspaceService['domain'] ?: $workspaceService['username'] ?: ('Service #' . (int)($workspaceService['id'] ?? 0))));
                        $serviceDueDate = trim((string)($workspaceService['next_due_date'] ?? ''));
                        $serviceBillingCycle = trim((string)($workspaceService['billing_cycle'] ?? ''));
                        ?>
                        <article class="ide-launch-card">
                            <span class="ide-status-pill <?php echo htmlspecialchars($serviceTone, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($serviceStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                            <h3><?php echo htmlspecialchars((string)($workspaceService['product_name'] ?? 'Alfred IDE'), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars($serviceLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="ide-service-meta">
                                <div><strong>Service ID:</strong> <?php echo (int)($workspaceService['id'] ?? 0); ?></div>
                                <div><strong>Billing cycle:</strong> <?php echo htmlspecialchars($serviceBillingCycle !== '' ? $serviceBillingCycle : 'Not set', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div><strong>Next due:</strong> <?php echo htmlspecialchars($serviceDueDate !== '' ? $serviceDueDate : 'Not set', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <a class="ide-inline-link" href="/pay/account/service.php?id=<?php echo (int)($workspaceService['id'] ?? 0); ?>"><i class="fas fa-arrow-right"></i> Review service</a>
                        </article>
                    <?php endforeach; ?>
                <?php elseif ($isLoggedIn): ?>
                    <article class="ide-launch-card">
                        <span class="ide-status-pill warn">Access missing</span>
                        <h3>No Alfred IDE service on this account</h3>
                        <p>The account is signed in, but the launch page did not find an active Alfred IDE service to route into a customer workspace.</p>
                        <div class="ide-service-meta">
                            <div><strong>Next move:</strong> choose an Alfred IDE access path or activate service entitlement</div>
                        </div>
                        <a class="ide-inline-link" href="/pricing.php"><i class="fas fa-arrow-right"></i> Review Alfred IDE access</a>
                    </article>
                <?php else: ?>
                    <article class="ide-launch-card">
                        <span class="ide-status-pill idle">Sign in first</span>
                        <h3>Launch control activates after login</h3>
                        <p>Guests can read the product story here, but actual workspace routing starts once the GoSiteMe account session is known.</p>
                        <div class="ide-service-meta">
                            <div><strong>Next move:</strong> enter the Alfred IDE auth flow</div>
                        </div>
                        <a class="ide-inline-link" href="/alfred-ide-auth.php"><i class="fas fa-arrow-right"></i> Open Alfred IDE sign-in</a>
                    </article>
                <?php endif; ?>
            </div>

            <div class="ide-control-actions">
                <a class="ide-btn ide-btn-primary" href="<?php echo htmlspecialchars($launchUrl, ENT_QUOTES, 'UTF-8'); ?>" data-alfred-launch="true"><i class="fas fa-rocket"></i> Open live workspace</a>
                <a class="ide-btn ide-btn-secondary" href="<?php echo htmlspecialchars($accountControlUrl, ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-key"></i> Manage login access</a>
                <a class="ide-btn ide-btn-secondary" href="<?php echo htmlspecialchars($workspaceManageUrl, ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-sliders"></i> Review workspace service</a>
            </div>
            <div id="ideLaunchMessage" class="ide-control-message idle" hidden>Checking Alfred IDE access...</div>
        </section>

        <section class="ide-section-card">
            <p class="ide-section-kicker">Launch paths</p>
            <h2 class="ide-section-title">Three clean ways into Alfred IDE</h2>
            <div class="ide-launch-grid">
                <article class="ide-launch-card">
                    <span class="ide-launch-pill"><i class="fas fa-crown"></i> Commander</span>
                    <h3>Server workspace</h3>
                    <p>The Commander account opens the primary Alfred IDE server workspace at the live IDE route.</p>
                    <a class="ide-inline-link" href="/alfred-ide/"><i class="fas fa-arrow-right"></i> Launch commander workspace</a>
                </article>
                <article class="ide-launch-card">
                    <span class="ide-launch-pill"><i class="fas fa-user-check"></i> Sign-in</span>
                    <h3>Access verification</h3>
                    <p>The public sign-in and PIN flow is already wired for Alfred IDE and redirects correctly into the workspace.</p>
                    <a class="ide-inline-link" href="/alfred-ide-auth.php"><i class="fas fa-arrow-right"></i> Open Alfred IDE sign-in</a>
                </article>
                <article class="ide-launch-card">
                    <span class="ide-launch-pill"><i class="fas fa-box-open"></i> Customers</span>
                    <h3>Service-linked workspaces</h3>
                    <p>Customer launches stay tied to active GoCodeMe service access instead of dropping everyone into the Commander environment.</p>
                    <a class="ide-inline-link" href="/pricing.php"><i class="fas fa-arrow-right"></i> Review plans and service access</a>
                </article>
            </div>
        </section>

        <section class="ide-section-card">
            <p class="ide-section-kicker">First-session flow</p>
            <h2 class="ide-section-title">How a real Alfred IDE session is supposed to unfold</h2>
            <div class="ide-flow-grid">
                <article class="ide-flow-card">
                    <div class="ide-step-num">1</div>
                    <h3>Authenticate through GoSiteMe</h3>
                    <p>Users do not hit a generic editor login wall. They come through the Alfred IDE auth flow, which already supports GoSiteMe sign-in, PIN verification, and token issuance.</p>
                    <ul>
                        <li>Commander uses the protected server workspace path.</li>
                        <li>Customers stay tied to their own service entitlement.</li>
                    </ul>
                </article>
                <article class="ide-flow-card">
                    <div class="ide-step-num">2</div>
                    <h3>Land in the correct workspace</h3>
                    <p>Workspace launch is role-aware. Commander stays on the main environment, while customer launches are meant to resolve into their own service-linked workspace instead of one shared server shell.</p>
                    <ul>
                        <li>No fake “one size fits all” workspace story.</li>
                        <li>Customer access is governed by active GoCodeMe service status.</li>
                    </ul>
                </article>
                <article class="ide-flow-card">
                    <div class="ide-step-num">3</div>
                    <h3>Use Alfred inside the IDE</h3>
                    <p>The current Alfred Commander layer already gives the user a meaningful operating surface inside the editor instead of just a renamed stock code-server shell.</p>
                    <ul>
                        <li>Chat, model selection, voice STT/TTS, and hands-free mode.</li>
                        <li>Attachments for images, PDFs, text, code files, and ZIP bundles.</li>
                    </ul>
                </article>
                <article class="ide-flow-card">
                    <div class="ide-step-num">4</div>
                    <h3>Build, save, run, and ship</h3>
                    <p>Quick IDE actions already surface terminal, save, save all, command palette, split editor, and new file flows. The next maturity step is making workspace health, preview, and runtime workflows just as visible.</p>
                    <ul>
                        <li>Terminal and code insertion are already wired.</li>
                        <li>Onboarding and lifecycle visibility are the next product gaps to close.</li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="ide-section-card">
            <p class="ide-section-kicker">Why it matters</p>
            <h2 class="ide-section-title">The official product story is finally coherent</h2>
            <div class="ide-feature-grid">
                <article class="ide-feature-card">
                    <i class="fas fa-code"></i>
                    <h3>Real browser IDE</h3>
                    <p>Alfred IDE launches as a full browser workspace, not a brochure that hands the user off to a vague developer promise.</p>
                </article>
                <article class="ide-feature-card">
                    <i class="fas fa-robot"></i>
                    <h3>Alfred in the workflow</h3>
                    <p>The product name now matches the actual experience: Alfred is not adjacent to the IDE, Alfred is part of it.</p>
                </article>
                <article class="ide-feature-card">
                    <i class="fas fa-lock"></i>
                    <h3>Sovereign access</h3>
                    <p>Sessions, account checks, and workspace launches are handled inside GoSiteMe instead of outsourcing trust to unrelated platforms.</p>
                </article>
                <article class="ide-feature-card">
                    <i class="fas fa-toolbox"></i>
                    <h3>Tool gravity</h3>
                    <p>The IDE sits inside the broader Alfred platform, which is where the MCP tools, automation, hosting, and launch leverage already live.</p>
                </article>
                <article class="ide-feature-card">
                    <i class="fas fa-terminal"></i>
                    <h3>Build and ship</h3>
                    <p>Terminal, Git, browser access, and ecosystem routing make Alfred IDE part of the actual build path, not a detached experiment.</p>
                </article>
                <article class="ide-feature-card">
                    <i class="fas fa-network-wired"></i>
                    <h3>Unified platform surface</h3>
                    <p>Search, browser, hosting, billing, and IDE can now be presented as one ecosystem instead of separate isolated stories.</p>
                </article>
                <article class="ide-feature-card">
                    <i class="fas fa-code-branch"></i>
                    <h3>Open source on GoForge</h3>
                    <p>Alfred's source code lives on <a href="https://alfredlinux.com/forge/explore/repos" target="_blank" style="color:#6366f1;">GoForge</a> — our sovereign Git platform. 8 public repos including the build system, AI agent, and Commander extension.</p>
                </article>
            </div>

            <div class="ide-legacy-card">
                <div class="ide-legacy-copy">
                    <div>
                        <i class="fas fa-arrows-rotate"></i>
                        <h3>GoCodeMe becomes Alfred IDE</h3>
                        <p>GoCodeMe still exists as the legacy product label and customer service context, but the public launch name shifts to Alfred IDE so the product aligns with the ecosystem brand and the live runtime.</p>
                    </div>
                    <a class="ide-btn ide-btn-secondary" href="/gocodeme.php"><i class="fas fa-clock-rotate-left"></i> View legacy GoCodeMe page</a>
                </div>
            </div>
        </section>
    </div>

    <section class="ide-final-cta">
        <h2>Open the IDE that belongs to the platform.</h2>
        <p>Today&apos;s launch does not need fantasy. It needs a public entry point, honest access rules, and one clear message: Alfred IDE is live, it is part of GoSiteMe, and it is ready to open in the browser now.</p>
        <div class="ide-hero-actions" style="justify-content:center;margin-bottom:0;">
            <a class="ide-btn ide-btn-primary" href="<?php echo htmlspecialchars($launchUrl, ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-laptop-code"></i> <?php echo htmlspecialchars($launchLabel, ENT_QUOTES, 'UTF-8'); ?></a>
            <a class="ide-btn ide-btn-secondary" href="/projects.php"><i class="fas fa-compass"></i> Browse the ecosystem</a>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var launchAction = document.querySelector('[data-alfred-launch="true"]');
    var launchMessage = document.getElementById('ideLaunchMessage');

    if (!launchAction || !launchMessage) {
        return;
    }

    function showLaunchMessage(text, tone) {
        launchMessage.hidden = false;
        launchMessage.className = 'ide-control-message ' + tone;
        launchMessage.textContent = text;
    }

    launchAction.addEventListener('click', function (event) {
        event.preventDefault();

        if (launchAction.classList.contains('is-loading')) {
            return;
        }

        launchAction.classList.add('is-loading');
        showLaunchMessage('Checking Alfred IDE launch access...', 'idle');

        fetch('/api/alfred-ide-launch.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return {};
                }).then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                var data = result.data || {};

                if (data.success && data.url) {
                    showLaunchMessage(data.message || 'Workspace ready. Opening Alfred IDE...', 'live');
                    window.location.href = data.url;
                    return;
                }

                if (data.next) {
                    showLaunchMessage(data.error || 'Opening Alfred IDE sign-in...', data.code === 'login_required' ? 'idle' : 'warn');
                    window.location.href = data.next;
                    return;
                }

                if (data.manage) {
                    showLaunchMessage(data.error || 'Opening Alfred IDE account controls...', 'warn');
                    window.location.href = data.manage;
                    return;
                }

                showLaunchMessage(data.error || 'Could not resolve Alfred IDE launch right now.', 'warn');
                launchAction.classList.remove('is-loading');
            })
            .catch(function () {
                showLaunchMessage('Launch check failed. Use the standard access path if needed.', 'warn');
                launchAction.classList.remove('is-loading');
            });
    });
});
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>