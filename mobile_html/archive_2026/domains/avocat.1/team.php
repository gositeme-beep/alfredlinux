<?php
/**
 * Team Workspace — Alfred AI
 * Agent 7 — Sprint 3
 *
 * Team collaboration page: org overview, members, teams, shared agents/conversations, settings.
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
$page_title = 'Team Workspace - Alfred AI';
$page_description = 'Collaborate with your team on AI agents, fleets, and conversations.';
$page_canonical = 'https://gositeme.com/team';
require_once 'includes/site-header.inc.php';
?>

<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

<style>
/* ========== ROOT & RESET ========== */
:root {
    --al-bg: #0a0a14;
    --al-surface: #12121e;
    --al-card: #1a1a2e;
    --al-card-hover: #22223a;
    --al-accent: #6c5ce7;
    --al-accent-glow: rgba(108,92,231,.35);
    --al-accent-light: #a29bfe;
    --al-green: #00b894;
    --al-yellow: #ffd600;
    --al-blue: #448aff;
    --al-red: #ff5252;
    --al-cyan: #18ffff;
    --al-text: #e8e8f0;
    --al-text-sec: #9898b0;
    --al-text-muted: #68688a;
    --al-border: rgba(255,255,255,.06);
    --al-glass: rgba(255,255,255,.04);
    --al-radius: 12px;
    --al-radius-sm: 8px;
    --al-radius-lg: 16px;
    --al-font: 'Segoe UI', system-ui, -apple-system, sans-serif;
    --al-mono: 'JetBrains Mono', 'Fira Code', monospace;
}

.tw-wrap { max-width: 1400px; margin: 0 auto; padding: 0 1.5rem 3rem; }

/* ========== SCROLLBAR ========== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--al-surface); }
::-webkit-scrollbar-thumb { background: var(--al-accent); border-radius: 3px; }

/* ========== ONBOARDING (no org) ========== */
.tw-onboard { padding: 4rem 1rem; text-align: center; }
.tw-onboard h1 { font-size: clamp(1.8rem,4vw,2.8rem); font-weight: 800; margin-bottom: .5rem; }
.tw-onboard h1 .hl { background: linear-gradient(135deg, var(--al-accent), var(--al-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.tw-onboard .sub { color: var(--al-text-sec); font-size: 1.05rem; max-width: 560px; margin: 0 auto 2.5rem; }

.tw-onboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; max-width: 800px; margin: 0 auto 3rem; }
.tw-ob-card { background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius-lg); padding: 2rem; text-align: left; }
.tw-ob-card h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: .4rem; display: flex; align-items: center; gap: .5rem; }
.tw-ob-card h3 i { color: var(--al-accent); }
.tw-ob-card p { color: var(--al-text-sec); font-size: .88rem; margin-bottom: 1.2rem; }
.tw-ob-card .form-group { margin-bottom: .8rem; }
.tw-ob-card label { display: block; font-size: .75rem; font-weight: 600; color: var(--al-text-sec); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .04em; }

.tw-input { width: 100%; background: var(--al-surface); border: 1px solid var(--al-border); border-radius: var(--al-radius-sm); padding: .65rem .9rem; color: var(--al-text); font-size: .88rem; font-family: var(--al-font); outline: none; transition: border-color .2s, box-shadow .2s; }
.tw-input:focus { border-color: var(--al-accent); box-shadow: 0 0 0 3px var(--al-accent-glow); }

.tw-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .65rem 1.3rem; border: none; border-radius: var(--al-radius-sm); font-size: .88rem; font-weight: 600; cursor: pointer; transition: all .2s; font-family: var(--al-font); }
.tw-btn-primary { background: var(--al-accent); color: #fff; }
.tw-btn-primary:hover { background: #7c6cf7; transform: translateY(-1px); }
.tw-btn-sm { padding: .45rem .9rem; font-size: .8rem; }
.tw-btn-danger { background: rgba(255,82,82,.15); color: var(--al-red); border: 1px solid rgba(255,82,82,.2); }
.tw-btn-danger:hover { background: rgba(255,82,82,.25); }
.tw-btn-ghost { background: transparent; color: var(--al-text-sec); border: 1px solid var(--al-border); }
.tw-btn-ghost:hover { background: var(--al-glass); color: var(--al-text); }
.tw-btn-green { background: rgba(0,184,148,.15); color: var(--al-green); border: 1px solid rgba(0,184,148,.2); }
.tw-btn-green:hover { background: rgba(0,184,148,.25); }

/* Benefits list */
.tw-benefits { max-width: 700px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.tw-benefit { display: flex; align-items: flex-start; gap: .6rem; background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius); padding: 1rem; }
.tw-benefit i { color: var(--al-green); margin-top: .15rem; font-size: .9rem; }
.tw-benefit span { font-size: .88rem; color: var(--al-text-sec); }

/* ========== WORKSPACE LAYOUT ========== */
.tw-workspace { display: grid; grid-template-columns: 240px 1fr; gap: 0; min-height: calc(100vh - 80px); }

/* Sidebar */
.tw-sidebar { background: var(--al-surface); border-right: 1px solid var(--al-border); padding: 1.5rem 0; }
.tw-org-badge { padding: 0 1.2rem 1.2rem; border-bottom: 1px solid var(--al-border); margin-bottom: .8rem; display: flex; align-items: center; gap: .8rem; }
.tw-org-logo { width: 40px; height: 40px; border-radius: var(--al-radius-sm); background: var(--al-accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; color: #fff; flex-shrink: 0; overflow: hidden; }
.tw-org-logo img { width: 100%; height: 100%; object-fit: cover; }
.tw-org-info { overflow: hidden; }
.tw-org-info .name { font-weight: 700; font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tw-org-info .plan { font-size: .7rem; color: var(--al-accent-light); text-transform: uppercase; letter-spacing: .04em; }

.tw-nav-item { display: flex; align-items: center; gap: .7rem; padding: .65rem 1.2rem; margin: 0 .5rem; border-radius: var(--al-radius-sm); color: var(--al-text-sec); font-size: .88rem; cursor: pointer; transition: all .15s; border: none; background: none; width: calc(100% - 1rem); text-align: left; font-family: var(--al-font); }
.tw-nav-item:hover { background: var(--al-glass); color: var(--al-text); }
.tw-nav-item.active { background: rgba(108,92,231,.15); color: var(--al-accent-light); font-weight: 600; }
.tw-nav-item i { width: 18px; text-align: center; font-size: .85rem; }
.tw-nav-item .badge-count { margin-left: auto; background: rgba(108,92,231,.2); color: var(--al-accent-light); font-size: .7rem; padding: .15rem .5rem; border-radius: 50px; font-weight: 600; }

/* Main content */
.tw-main { padding: 1.5rem 2rem; overflow-y: auto; max-height: calc(100vh - 80px); }
.tw-tab { display: none; }
.tw-tab.active { display: block; }

.tw-tab-header { margin-bottom: 1.5rem; }
.tw-tab-header h2 { font-size: 1.4rem; font-weight: 800; margin-bottom: .3rem; }
.tw-tab-header p { color: var(--al-text-sec); font-size: .9rem; }

/* ========== STATS ROW ========== */
.tw-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.tw-stat { background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius); padding: 1.2rem; text-align: center; position: relative; overflow: hidden; }
.tw-stat::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
.tw-stat:nth-child(1)::before { background: linear-gradient(90deg, var(--al-accent), transparent); }
.tw-stat:nth-child(2)::before { background: linear-gradient(90deg, var(--al-green), transparent); }
.tw-stat:nth-child(3)::before { background: linear-gradient(90deg, var(--al-blue), transparent); }
.tw-stat:nth-child(4)::before { background: linear-gradient(90deg, var(--al-cyan), transparent); }
.tw-stat .icon { font-size: 1.3rem; margin-bottom: .4rem; }
.tw-stat:nth-child(1) .icon { color: var(--al-accent); }
.tw-stat:nth-child(2) .icon { color: var(--al-green); }
.tw-stat:nth-child(3) .icon { color: var(--al-blue); }
.tw-stat:nth-child(4) .icon { color: var(--al-cyan); }
.tw-stat .val { font-size: 1.7rem; font-weight: 800; font-family: var(--al-mono); line-height: 1; }
.tw-stat .lbl { color: var(--al-text-muted); font-size: .72rem; margin-top: .3rem; text-transform: uppercase; letter-spacing: .06em; }

/* ========== CARDS ========== */
.tw-card { background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius-lg); padding: 1.5rem; margin-bottom: 1.2rem; transition: border-color .25s; }
.tw-card:hover { border-color: rgba(108,92,231,.2); }
.tw-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.tw-card-header h3 { font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
.tw-card-header h3 i { color: var(--al-accent); font-size: .9rem; }

/* ========== ACTIVITY FEED ========== */
.tw-activity { max-height: 420px; overflow-y: auto; }
.tw-activity-item { display: flex; align-items: flex-start; gap: .8rem; padding: .8rem 0; border-bottom: 1px solid var(--al-border); }
.tw-activity-item:last-child { border-bottom: none; }
.tw-activity-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--al-accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .7rem; color: #fff; flex-shrink: 0; }
.tw-activity-body { flex: 1; min-width: 0; }
.tw-activity-body .actor { font-weight: 600; font-size: .85rem; }
.tw-activity-body .action-text { color: var(--al-text-sec); font-size: .82rem; }
.tw-activity-body .time { color: var(--al-text-muted); font-size: .72rem; margin-top: .15rem; }

/* ========== MEMBERS LIST ========== */
.tw-members-list { display: flex; flex-direction: column; gap: .3rem; }
.tw-member-row { display: grid; grid-template-columns: 40px 1fr 120px 100px 80px; gap: 1rem; align-items: center; padding: .7rem .5rem; border-radius: var(--al-radius-sm); transition: background .15s; }
.tw-member-row:hover { background: var(--al-glass); }
.tw-member-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--al-accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .75rem; color: #fff; position: relative; }
.tw-member-avatar .online-dot { position: absolute; bottom: 0; right: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--al-green); border: 2px solid var(--al-card); }
.tw-member-info .name { font-weight: 600; font-size: .88rem; }
.tw-member-info .email { color: var(--al-text-muted); font-size: .78rem; }

.role-badge { font-size: .68rem; padding: .2rem .6rem; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; display: inline-block; }
.role-owner { background: rgba(255,214,0,.12); color: var(--al-yellow); }
.role-admin { background: rgba(108,92,231,.15); color: var(--al-accent-light); }
.role-manager { background: rgba(0,184,148,.12); color: var(--al-green); }
.role-member { background: rgba(68,138,255,.12); color: var(--al-blue); }
.role-viewer { background: rgba(152,152,176,.12); color: var(--al-text-sec); }

.tw-member-actions { display: flex; gap: .3rem; }
.tw-member-actions select { background: var(--al-surface); border: 1px solid var(--al-border); border-radius: var(--al-radius-sm); color: var(--al-text); font-size: .75rem; padding: .3rem .5rem; outline: none; cursor: pointer; }

/* ========== TEAMS TAB ========== */
.tw-teams-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.tw-team-card { background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius-lg); padding: 1.3rem; transition: border-color .2s, box-shadow .2s; cursor: pointer; }
.tw-team-card:hover { border-color: rgba(108,92,231,.25); box-shadow: 0 0 20px rgba(108,92,231,.08); }
.tw-team-card h4 { font-size: 1rem; font-weight: 700; margin-bottom: .3rem; display: flex; align-items: center; gap: .5rem; }
.tw-team-card h4 i { color: var(--al-accent); }
.tw-team-card .desc { color: var(--al-text-sec); font-size: .82rem; margin-bottom: .8rem; }
.tw-team-card .meta { display: flex; align-items: center; gap: 1rem; font-size: .78rem; color: var(--al-text-muted); }
.tw-team-card .meta i { color: var(--al-accent-light); }
.tw-team-detail { display: none; margin-top: .8rem; padding-top: .8rem; border-top: 1px solid var(--al-border); }
.tw-team-card.expanded .tw-team-detail { display: block; }

/* ========== AGENT GRID ========== */
.tw-agent-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.tw-agent-card { background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius-lg); padding: 1.3rem; transition: border-color .2s; }
.tw-agent-card:hover { border-color: rgba(108,92,231,.25); }
.tw-agent-card .agent-name { font-weight: 700; font-size: .95rem; margin-bottom: .2rem; }
.tw-agent-card .agent-meta { color: var(--al-text-muted); font-size: .78rem; margin-bottom: .6rem; }
.tw-agent-card .agent-perm { font-size: .7rem; padding: .15rem .5rem; border-radius: 50px; display: inline-block; }
.perm-view { background: rgba(68,138,255,.12); color: var(--al-blue); }
.perm-execute { background: rgba(0,184,148,.12); color: var(--al-green); }
.perm-manage { background: rgba(108,92,231,.15); color: var(--al-accent-light); }
.tw-agent-card .agent-actions { display: flex; gap: .4rem; margin-top: .8rem; }

/* ========== CONVERSATIONS ========== */
.tw-conv-list { display: flex; flex-direction: column; gap: .6rem; }
.tw-conv-card { background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius); padding: 1rem 1.2rem; display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 1rem; transition: border-color .2s; }
.tw-conv-card:hover { border-color: rgba(108,92,231,.2); }
.tw-conv-card .conv-title { font-weight: 600; font-size: .9rem; margin-bottom: .2rem; }
.tw-conv-card .conv-meta { color: var(--al-text-muted); font-size: .78rem; }
.tw-conv-card .conv-shared-by { color: var(--al-text-sec); font-size: .78rem; }

/* ========== FILTERS ROW ========== */
.tw-filters { display: flex; gap: .8rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; }
.tw-filters select, .tw-filters input[type="date"] { background: var(--al-surface); border: 1px solid var(--al-border); border-radius: var(--al-radius-sm); color: var(--al-text); font-size: .82rem; padding: .45rem .7rem; outline: none; font-family: var(--al-font); }
.tw-filters select:focus, .tw-filters input[type="date"]:focus { border-color: var(--al-accent); }

/* ========== SETTINGS ========== */
.tw-settings-section { margin-bottom: 2rem; }
.tw-settings-section h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: .8rem; display: flex; align-items: center; gap: .5rem; }
.tw-settings-section h3 i { color: var(--al-accent); }
.tw-settings-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: .8rem; }
.tw-danger-zone { background: rgba(255,82,82,.05); border: 1px solid rgba(255,82,82,.15); border-radius: var(--al-radius-lg); padding: 1.5rem; }
.tw-danger-zone h3 { color: var(--al-red); }
.tw-danger-zone h3 i { color: var(--al-red) !important; }

/* ========== MODALS ========== */
.tw-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(4px); z-index: 1000; display: none; align-items: center; justify-content: center; }
.tw-modal-overlay.show { display: flex; }
.tw-modal { background: var(--al-surface); border: 1px solid var(--al-border); border-radius: var(--al-radius-lg); padding: 2rem; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.tw-modal h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
.tw-modal h3 i { color: var(--al-accent); }
.tw-modal .form-group { margin-bottom: .8rem; }
.tw-modal .form-group label { display: block; font-size: .75rem; font-weight: 600; color: var(--al-text-sec); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .04em; }
.tw-modal .modal-actions { display: flex; gap: .6rem; justify-content: flex-end; margin-top: 1.2rem; }

/* ========== TOAST ========== */
.tw-toast { position: fixed; bottom: 2rem; right: 2rem; background: var(--al-card); border: 1px solid var(--al-border); border-radius: var(--al-radius); padding: .8rem 1.2rem; font-size: .88rem; z-index: 2000; display: none; align-items: center; gap: .5rem; box-shadow: 0 8px 32px rgba(0,0,0,.4); animation: toastIn .3s ease; }
.tw-toast.show { display: flex; }
.tw-toast.success { border-color: rgba(0,184,148,.3); }
.tw-toast.success i { color: var(--al-green); }
.tw-toast.error { border-color: rgba(255,82,82,.3); }
.tw-toast.error i { color: var(--al-red); }
@keyframes toastIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

/* ========== EMPTY STATE ========== */
.tw-empty { text-align: center; padding: 3rem 1rem; color: var(--al-text-muted); }
.tw-empty i { font-size: 2.5rem; margin-bottom: .8rem; color: var(--al-accent); opacity: .5; }
.tw-empty p { font-size: .9rem; margin-bottom: .8rem; }

/* ========== RESPONSIVE ========== */
@media (max-width: 900px) {
    .tw-workspace { grid-template-columns: 1fr; }
    .tw-sidebar { display: flex; overflow-x: auto; padding: .8rem 1rem; border-right: none; border-bottom: 1px solid var(--al-border); gap: .3rem; }
    .tw-org-badge { display: none; }
    .tw-nav-item { white-space: nowrap; padding: .5rem 1rem; margin: 0; flex-shrink: 0; }
    .tw-nav-item .badge-count { display: none; }
    .tw-main { max-height: unset; padding: 1.2rem; }
    .tw-stats { grid-template-columns: repeat(2, 1fr); }
    .tw-member-row { grid-template-columns: 36px 1fr auto; }
    .tw-member-row .role-col, .tw-member-row .last-active-col { display: none; }
    .tw-onboard-grid { grid-template-columns: 1fr; }
    .tw-settings-row { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .tw-stats { grid-template-columns: 1fr 1fr; }
    .tw-agent-grid, .tw-teams-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- BODY CONTENT                                               -->
<!-- ════════════════════════════════════════════════════════════ -->

<div class="tw-wrap" id="teamApp">

    <!-- Loading state -->
    <div id="twLoading" style="text-align:center;padding:4rem;">
        <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--al-accent);"></i>
        <p style="color:var(--al-text-sec);margin-top:1rem;">Loading team workspace...</p>
    </div>

    <!-- ═══════ NO ORG — ONBOARDING ═══════ -->
    <div id="twOnboard" class="tw-onboard" style="display:none;">
        <h1>Create or Join an <span class="hl">Organization</span></h1>
        <p class="sub">Team up with colleagues to share AI agents, collaborate on conversations, and manage fleets together.</p>

        <div class="tw-onboard-grid">
            <!-- Create org -->
            <div class="tw-ob-card">
                <h3><i class="fas fa-building"></i> Create Organization</h3>
                <p>Start a new organization and invite your team members.</p>
                <div class="form-group">
                    <label>Organization Name</label>
                    <input type="text" id="createOrgName" class="tw-input" placeholder="Acme Corp">
                </div>
                <div class="form-group">
                    <label>Slug (URL identifier)</label>
                    <input type="text" id="createOrgSlug" class="tw-input" placeholder="acme-corp" pattern="[a-z0-9\-]{3,100}">
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input type="text" id="createOrgDesc" class="tw-input" placeholder="Our AI team workspace">
                </div>
                <button class="tw-btn tw-btn-primary" onclick="createOrg()">
                    <i class="fas fa-plus"></i> Create Organization
                </button>
            </div>

            <!-- Join with code -->
            <div class="tw-ob-card">
                <h3><i class="fas fa-ticket-alt"></i> Join with Invite Code</h3>
                <p>Enter an invite code from your team administrator.</p>
                <div class="form-group">
                    <label>Invite Code</label>
                    <input type="text" id="joinCode" class="tw-input" placeholder="AB12CD34-EF56" style="text-transform:uppercase;font-family:var(--al-mono);letter-spacing:.1em;">
                </div>
                <button class="tw-btn tw-btn-green" onclick="joinOrg()">
                    <i class="fas fa-sign-in-alt"></i> Join Organization
                </button>
            </div>
        </div>

        <h3 style="font-size:1.05rem;margin-bottom:1rem;color:var(--al-text-sec);">
            <i class="fas fa-star" style="color:var(--al-accent);"></i> Benefits of Team Features
        </h3>
        <div class="tw-benefits">
            <div class="tw-benefit"><i class="fas fa-check-circle"></i><span>Share AI agents across your organization</span></div>
            <div class="tw-benefit"><i class="fas fa-check-circle"></i><span>Collaborative conversation workspaces</span></div>
            <div class="tw-benefit"><i class="fas fa-check-circle"></i><span>Role-based access control (RBAC)</span></div>
            <div class="tw-benefit"><i class="fas fa-check-circle"></i><span>Team-specific agent fleets</span></div>
            <div class="tw-benefit"><i class="fas fa-check-circle"></i><span>Centralized billing &amp; usage tracking</span></div>
            <div class="tw-benefit"><i class="fas fa-check-circle"></i><span>Audit logging &amp; security controls</span></div>
        </div>
    </div>

    <!-- ═══════ HAS ORG — WORKSPACE ═══════ -->
    <div id="twWorkspace" class="tw-workspace" style="display:none;">

        <!-- Sidebar -->
        <nav class="tw-sidebar">
            <div class="tw-org-badge" id="twOrgBadge">
                <div class="tw-org-logo" id="twOrgLogo">A</div>
                <div class="tw-org-info">
                    <div class="name" id="twOrgName">Organization</div>
                    <div class="plan" id="twOrgPlan">Starter</div>
                </div>
            </div>
            <button class="tw-nav-item active" data-tab="overview" onclick="switchTab('overview')">
                <i class="fas fa-th-large"></i> Overview
            </button>
            <button class="tw-nav-item" data-tab="members" onclick="switchTab('members')">
                <i class="fas fa-users"></i> Members <span class="badge-count" id="navMemberCount">0</span>
            </button>
            <button class="tw-nav-item" data-tab="teams" onclick="switchTab('teams')">
                <i class="fas fa-layer-group"></i> Teams <span class="badge-count" id="navTeamCount">0</span>
            </button>
            <button class="tw-nav-item" data-tab="agents" onclick="switchTab('agents')">
                <i class="fas fa-robot"></i> Shared Agents <span class="badge-count" id="navAgentCount">0</span>
            </button>
            <button class="tw-nav-item" data-tab="conversations" onclick="switchTab('conversations')">
                <i class="fas fa-comments"></i> Shared Conversations
            </button>
            <button class="tw-nav-item" data-tab="settings" onclick="switchTab('settings')" id="navSettings" style="display:none;">
                <i class="fas fa-cog"></i> Settings
            </button>
        </nav>

        <!-- Main content area -->
        <div class="tw-main">

            <!-- ───── TAB: OVERVIEW ───── -->
            <div class="tw-tab active" id="tab-overview">
                <div class="tw-tab-header">
                    <h2><i class="fas fa-th-large" style="color:var(--al-accent);"></i> Team Overview</h2>
                    <p>Organization dashboard &amp; recent activity</p>
                </div>

                <div class="tw-stats">
                    <div class="tw-stat"><div class="icon"><i class="fas fa-users"></i></div><div class="val" id="statMembers">0</div><div class="lbl">Members</div></div>
                    <div class="tw-stat"><div class="icon"><i class="fas fa-layer-group"></i></div><div class="val" id="statTeams">0</div><div class="lbl">Teams</div></div>
                    <div class="tw-stat"><div class="icon"><i class="fas fa-robot"></i></div><div class="val" id="statAgents">0</div><div class="lbl">Shared Agents</div></div>
                    <div class="tw-stat"><div class="icon"><i class="fas fa-comments"></i></div><div class="val" id="statConvs">0</div><div class="lbl">Shared Convos</div></div>
                </div>

                <!-- Online Members -->
                <div class="tw-card">
                    <div class="tw-card-header">
                        <h3><i class="fas fa-circle" style="color:var(--al-green);font-size:.6rem;"></i> Team Members</h3>
                    </div>
                    <div id="onlineMembersList" style="display:flex;flex-wrap:wrap;gap:.5rem;"></div>
                </div>

                <!-- Recent Activity -->
                <div class="tw-card">
                    <div class="tw-card-header">
                        <h3><i class="fas fa-stream"></i> Recent Activity</h3>
                    </div>
                    <div class="tw-activity" id="activityFeed">
                        <div class="tw-empty"><i class="fas fa-stream"></i><p>No recent activity</p></div>
                    </div>
                </div>
            </div>

            <!-- ───── TAB: MEMBERS ───── -->
            <div class="tw-tab" id="tab-members">
                <div class="tw-tab-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h2><i class="fas fa-users" style="color:var(--al-accent);"></i> Members</h2>
                        <p>Manage organization members and roles</p>
                    </div>
                    <div style="display:flex;gap:.5rem;" id="memberActions" style="display:none;">
                        <button class="tw-btn tw-btn-primary tw-btn-sm" onclick="showModal('inviteModal')">
                            <i class="fas fa-user-plus"></i> Invite Member
                        </button>
                        <button class="tw-btn tw-btn-ghost tw-btn-sm" onclick="showModal('inviteCodeModal')">
                            <i class="fas fa-ticket-alt"></i> Invite Code
                        </button>
                    </div>
                </div>
                <div id="membersList" class="tw-members-list">
                    <div class="tw-empty"><i class="fas fa-users"></i><p>Loading members...</p></div>
                </div>
            </div>

            <!-- ───── TAB: TEAMS ───── -->
            <div class="tw-tab" id="tab-teams">
                <div class="tw-tab-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h2><i class="fas fa-layer-group" style="color:var(--al-accent);"></i> Teams</h2>
                        <p>Sub-groups within your organization</p>
                    </div>
                    <button class="tw-btn tw-btn-primary tw-btn-sm" onclick="showModal('createTeamModal')" id="btnCreateTeam">
                        <i class="fas fa-plus"></i> Create Team
                    </button>
                </div>
                <div id="teamsList" class="tw-teams-grid">
                    <div class="tw-empty"><i class="fas fa-layer-group"></i><p>No teams yet</p></div>
                </div>
            </div>

            <!-- ───── TAB: SHARED AGENTS ───── -->
            <div class="tw-tab" id="tab-agents">
                <div class="tw-tab-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h2><i class="fas fa-robot" style="color:var(--al-accent);"></i> Shared Agents</h2>
                        <p>AI agents shared across your organization</p>
                    </div>
                    <button class="tw-btn tw-btn-primary tw-btn-sm" onclick="showModal('shareAgentModal')">
                        <i class="fas fa-share-alt"></i> Share Agent
                    </button>
                </div>
                <div id="agentsList" class="tw-agent-grid">
                    <div class="tw-empty"><i class="fas fa-robot"></i><p>No shared agents yet</p></div>
                </div>
            </div>

            <!-- ───── TAB: SHARED CONVERSATIONS ───── -->
            <div class="tw-tab" id="tab-conversations">
                <div class="tw-tab-header">
                    <h2><i class="fas fa-comments" style="color:var(--al-accent);"></i> Shared Conversations</h2>
                    <p>Conversations visible to the whole team</p>
                </div>
                <div class="tw-filters">
                    <select id="filterTeam" onchange="loadConversations()">
                        <option value="">All Teams</option>
                    </select>
                    <input type="date" id="filterDateFrom" onchange="loadConversations()" title="From date">
                    <input type="date" id="filterDateTo" onchange="loadConversations()" title="To date">
                    <button class="tw-btn tw-btn-ghost tw-btn-sm" onclick="showModal('shareConvModal')">
                        <i class="fas fa-share"></i> Share Conversation
                    </button>
                </div>
                <div id="convList" class="tw-conv-list">
                    <div class="tw-empty"><i class="fas fa-comments"></i><p>No shared conversations</p></div>
                </div>
            </div>

            <!-- ───── TAB: SETTINGS ───── -->
            <div class="tw-tab" id="tab-settings">
                <div class="tw-tab-header">
                    <h2><i class="fas fa-cog" style="color:var(--al-accent);"></i> Organization Settings</h2>
                    <p>Manage your organization configuration</p>
                </div>

                <div class="tw-settings-section">
                    <h3><i class="fas fa-building"></i> Organization Details</h3>
                    <div class="tw-settings-row">
                        <div class="form-group">
                            <label style="display:block;font-size:.75rem;font-weight:600;color:var(--al-text-sec);margin-bottom:.3rem;text-transform:uppercase;">Name</label>
                            <input type="text" id="settOrgName" class="tw-input">
                        </div>
                        <div class="form-group">
                            <label style="display:block;font-size:.75rem;font-weight:600;color:var(--al-text-sec);margin-bottom:.3rem;text-transform:uppercase;">Logo URL</label>
                            <input type="text" id="settOrgLogo" class="tw-input" placeholder="https://...">
                        </div>
                    </div>
                    <div class="tw-settings-row">
                        <div class="form-group">
                            <label style="display:block;font-size:.75rem;font-weight:600;color:var(--al-text-sec);margin-bottom:.3rem;text-transform:uppercase;">Domain</label>
                            <input type="text" id="settOrgDomain" class="tw-input" placeholder="yourcompany.com">
                        </div>
                        <div class="form-group">
                            <label style="display:block;font-size:.75rem;font-weight:600;color:var(--al-text-sec);margin-bottom:.3rem;text-transform:uppercase;">Plan</label>
                            <input type="text" id="settOrgPlan" class="tw-input" disabled>
                        </div>
                    </div>
                    <button class="tw-btn tw-btn-primary" onclick="saveOrgSettings()">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>

                <div class="tw-settings-section">
                    <h3><i class="fas fa-shield-alt"></i> Security</h3>
                    <div class="tw-settings-row">
                        <div class="form-group">
                            <label style="display:block;font-size:.75rem;font-weight:600;color:var(--al-text-sec);margin-bottom:.3rem;text-transform:uppercase;">Max Users</label>
                            <input type="number" id="settMaxUsers" class="tw-input" min="1" max="10000">
                        </div>
                        <div class="form-group">
                            <label style="display:block;font-size:.75rem;font-weight:600;color:var(--al-text-sec);margin-bottom:.3rem;text-transform:uppercase;">Max Agents</label>
                            <input type="number" id="settMaxAgents" class="tw-input" min="1" max="10000">
                        </div>
                    </div>
                </div>

                <div class="tw-danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                    <p style="color:var(--al-text-sec);font-size:.85rem;margin-bottom:1rem;">These actions are irreversible. Proceed with caution.</p>
                    <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
                        <button class="tw-btn tw-btn-danger" onclick="transferOwnership()">
                            <i class="fas fa-exchange-alt"></i> Transfer Ownership
                        </button>
                        <button class="tw-btn tw-btn-danger" onclick="deleteOrg()">
                            <i class="fas fa-trash"></i> Delete Organization
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /.tw-main -->
    </div><!-- /#twWorkspace -->
</div><!-- /#teamApp -->

<!-- ════════════════════════════════════════════════════════════ -->
<!-- MODALS                                                     -->
<!-- ════════════════════════════════════════════════════════════ -->

<!-- Invite Member Modal -->
<div class="tw-modal-overlay" id="inviteModal">
    <div class="tw-modal">
        <h3><i class="fas fa-user-plus"></i> Invite Member</h3>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="inviteEmail" class="tw-input" placeholder="colleague@company.com">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select id="inviteRole" class="tw-input">
                <option value="member">Member</option>
                <option value="viewer">Viewer</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="tw-btn tw-btn-ghost" onclick="hideModal('inviteModal')">Cancel</button>
            <button class="tw-btn tw-btn-primary" onclick="sendInvite()"><i class="fas fa-paper-plane"></i> Send Invite</button>
        </div>
    </div>
</div>

<!-- Invite Code Modal -->
<div class="tw-modal-overlay" id="inviteCodeModal">
    <div class="tw-modal">
        <h3><i class="fas fa-ticket-alt"></i> Generate Invite Code</h3>
        <div class="form-group">
            <label>Default Role for Joiners</label>
            <select id="codeRoleId" class="tw-input">
                <option value="4">Member</option>
                <option value="5">Viewer</option>
                <option value="3">Manager</option>
            </select>
        </div>
        <div class="form-group">
            <label>Max Uses</label>
            <input type="number" id="codeMaxUses" class="tw-input" value="10" min="1" max="100">
        </div>
        <div id="generatedCodeBox" style="display:none;margin-top:1rem;background:var(--al-card);border:1px solid var(--al-border);border-radius:var(--al-radius);padding:1rem;text-align:center;">
            <div style="font-size:.72rem;color:var(--al-text-muted);text-transform:uppercase;margin-bottom:.3rem;">Invite Code (valid 7 days)</div>
            <div id="generatedCode" style="font-family:var(--al-mono);font-size:1.4rem;font-weight:800;color:var(--al-accent-light);letter-spacing:.1em;"></div>
        </div>
        <div class="modal-actions">
            <button class="tw-btn tw-btn-ghost" onclick="hideModal('inviteCodeModal')">Close</button>
            <button class="tw-btn tw-btn-primary" onclick="generateInviteCode()"><i class="fas fa-key"></i> Generate Code</button>
        </div>
    </div>
</div>

<!-- Create Team Modal -->
<div class="tw-modal-overlay" id="createTeamModal">
    <div class="tw-modal">
        <h3><i class="fas fa-layer-group"></i> Create Team</h3>
        <div class="form-group">
            <label>Team Name</label>
            <input type="text" id="newTeamName" class="tw-input" placeholder="Engineering">
        </div>
        <div class="form-group">
            <label>Description</label>
            <input type="text" id="newTeamDesc" class="tw-input" placeholder="Backend & frontend development team">
        </div>
        <div class="modal-actions">
            <button class="tw-btn tw-btn-ghost" onclick="hideModal('createTeamModal')">Cancel</button>
            <button class="tw-btn tw-btn-primary" onclick="createTeam()"><i class="fas fa-plus"></i> Create</button>
        </div>
    </div>
</div>

<!-- Share Agent Modal -->
<div class="tw-modal-overlay" id="shareAgentModal">
    <div class="tw-modal">
        <h3><i class="fas fa-share-alt"></i> Share Agent</h3>
        <div class="form-group">
            <label>Agent ID</label>
            <input type="number" id="shareAgentId" class="tw-input" placeholder="Enter agent ID">
        </div>
        <div class="form-group">
            <label>Permission Level</label>
            <select id="shareAgentPerm" class="tw-input">
                <option value="execute">Execute — Can run the agent</option>
                <option value="view">View — Read-only access</option>
                <option value="manage">Manage — Full control</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="tw-btn tw-btn-ghost" onclick="hideModal('shareAgentModal')">Cancel</button>
            <button class="tw-btn tw-btn-primary" onclick="shareAgent()"><i class="fas fa-share"></i> Share</button>
        </div>
    </div>
</div>

<!-- Share Conversation Modal -->
<div class="tw-modal-overlay" id="shareConvModal">
    <div class="tw-modal">
        <h3><i class="fas fa-comments"></i> Share Conversation</h3>
        <div class="form-group">
            <label>Conversation ID</label>
            <input type="text" id="shareConvId" class="tw-input" placeholder="conv_abc123...">
        </div>
        <div class="form-group">
            <label>Share with Team (optional)</label>
            <select id="shareConvTeam" class="tw-input">
                <option value="">Entire Organization</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="tw-btn tw-btn-ghost" onclick="hideModal('shareConvModal')">Cancel</button>
            <button class="tw-btn tw-btn-primary" onclick="shareConversation()"><i class="fas fa-share"></i> Share</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="tw-toast" id="twToast"><i class="fas fa-check-circle"></i><span id="twToastMsg"></span></div>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- JAVASCRIPT                                                 -->
<!-- ════════════════════════════════════════════════════════════ -->

<script src="/assets/js/team-engine.js"></script>


<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
