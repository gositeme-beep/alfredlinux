<?php
/**
 * Service Marketplace — Governance, Jobs & GSM Economy
 * ─────────────────────────────────────────────────────
 * Department-approved services, agent job board, GSM token economy,
 * external API marketplace, and governance pipeline.
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /login.php'); exit; }
$pageTitle = 'Service Marketplace — GoSiteMe';
$pageDescription = 'Department governance pipeline, agent job board, GSM token economy, and external API marketplace.';
include 'includes/site-header.inc.php';
?>

<style>
:root {
    --sm-primary: #14F195;
    --sm-secondary: #9945FF;
    --sm-accent: #f59e0b;
    --sm-success: #10b981;
    --sm-danger: #ef4444;
    --sm-info: #3b82f6;
    --sm-bg: #0a0e17;
    --sm-surface: #111827;
    --sm-surface2: #1e293b;
    --sm-border: #1e293b;
    --sm-text: #e2e8f0;
    --sm-muted: #94a3b8;
}

/* ── Hero ── */
.sm-hero {
    background: linear-gradient(135deg, #0c1222 0%, #0a1628 40%, #0d2137 100%);
    padding: 3rem 2rem;
    text-align: center;
    border-bottom: 1px solid var(--sm-border);
    position: relative;
    overflow: hidden;
}
.sm-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%2314F195' fill-opacity='0.04'%3E%3Cpath d='M20 20l-8-4v8l8 4 8-4v-8l-8 4z'/%3E%3C/g%3E%3C/svg%3E");
}
.sm-hero h1 {
    font-size: 2.6rem;
    font-weight: 800;
    background: linear-gradient(135deg, #14F195, #9945FF, #f59e0b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
    position: relative;
}
.sm-hero p {
    color: var(--sm-muted);
    font-size: 1.1rem;
    max-width: 650px;
    margin: 0 auto;
    position: relative;
}

/* ── KPIs ── */
.sm-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    padding: 1.5rem 2rem;
    max-width: 1400px;
    margin: 0 auto;
}
.sm-kpi {
    background: var(--sm-surface);
    border: 1px solid var(--sm-border);
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
}
.sm-kpi-value {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 0.25rem;
}
.sm-kpi-label {
    color: var(--sm-muted);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── Tabs ── */
.sm-tabs {
    display: flex;
    gap: 0;
    padding: 0 2rem;
    max-width: 1400px;
    margin: 0 auto;
    border-bottom: 1px solid var(--sm-border);
    overflow-x: auto;
}
.sm-tab {
    padding: 1rem 1.5rem;
    color: var(--sm-muted);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    transition: all 0.2s;
}
.sm-tab:hover { color: var(--sm-text); }
.sm-tab.active {
    color: var(--sm-primary);
    border-bottom-color: var(--sm-primary);
}

/* ── Tab Content ── */
.sm-panel { display: none; padding: 2rem; max-width: 1400px; margin: 0 auto; }
.sm-panel.active { display: block; }

/* ── Cards ── */
.sm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.25rem; }
.sm-card {
    background: var(--sm-surface);
    border: 1px solid var(--sm-border);
    border-radius: 12px;
    padding: 1.5rem;
    transition: border-color 0.2s;
}
.sm-card:hover { border-color: var(--sm-primary); }
.sm-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}
.sm-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--sm-text);
}
.sm-card-desc {
    color: var(--sm-muted);
    font-size: 0.88rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}
.sm-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.8rem;
}

/* ── Badges ── */
.sm-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.sm-badge-proposed { background: rgba(59,130,246,0.15); color: #60a5fa; }
.sm-badge-under_review { background: rgba(245,158,11,0.15); color: #fbbf24; }
.sm-badge-approved, .sm-badge-in_development { background: rgba(16,185,129,0.15); color: #34d399; }
.sm-badge-deployed { background: rgba(20,241,149,0.15); color: #14F195; }
.sm-badge-rejected { background: rgba(239,68,68,0.15); color: #f87171; }
.sm-badge-draft { background: rgba(148,163,184,0.15); color: #94a3b8; }
.sm-badge-testing { background: rgba(168,85,247,0.15); color: #c084fc; }
.sm-badge-retired { background: rgba(100,116,139,0.15); color: #64748b; }

/* ── Progress Bar ── */
.sm-progress {
    width: 100%;
    height: 6px;
    background: var(--sm-surface2);
    border-radius: 3px;
    margin-top: 0.75rem;
    overflow: hidden;
}
.sm-progress-fill {
    height: 100%;
    border-radius: 3px;
    background: linear-gradient(90deg, var(--sm-primary), var(--sm-secondary));
    transition: width 0.4s ease;
}

/* ── Table ── */
.sm-table-wrap { overflow-x: auto; }
.sm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.sm-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    color: var(--sm-muted);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--sm-border);
}
.sm-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--sm-border);
    color: var(--sm-text);
}
.sm-table tr:hover td { background: rgba(20,241,149,0.03); }

/* ── Economy Widget ── */
.sm-econ-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; }
.sm-econ-box {
    background: var(--sm-surface);
    border: 1px solid var(--sm-border);
    border-radius: 12px;
    padding: 1.5rem;
}
.sm-econ-box h3 {
    font-size: 0.9rem;
    color: var(--sm-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}

/* ── Leaderboard ── */
.sm-leader-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--sm-border);
}
.sm-leader-rank {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.sm-leader-rank.gold { background: linear-gradient(135deg, #f59e0b, #d97706); color: #000; }
.sm-leader-rank.silver { background: linear-gradient(135deg, #94a3b8, #64748b); color: #000; }
.sm-leader-rank.bronze { background: linear-gradient(135deg, #d97706, #92400e); color: #fff; }
.sm-leader-rank.other { background: var(--sm-surface2); color: var(--sm-muted); }
.sm-leader-info { flex: 1; min-width: 0; }
.sm-leader-name { font-weight: 600; color: var(--sm-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sm-leader-dept { font-size: 0.78rem; color: var(--sm-muted); }
.sm-leader-gsm { font-weight: 700; color: var(--sm-primary); white-space: nowrap; }

/* ── API Key Card ── */
.sm-api-card {
    background: var(--sm-surface);
    border: 1px solid var(--sm-border);
    border-radius: 12px;
    padding: 1.25rem;
}
.sm-api-tier {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
}
.sm-tier-free { background: rgba(148,163,184,0.15); color: #94a3b8; }
.sm-tier-starter { background: rgba(59,130,246,0.15); color: #60a5fa; }
.sm-tier-pro { background: rgba(168,85,247,0.15); color: #c084fc; }
.sm-tier-enterprise { background: rgba(20,241,149,0.15); color: #14F195; }

/* ── Vote Bar ── */
.sm-vote-bar {
    display: flex;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    background: var(--sm-surface2);
    margin: 0.5rem 0;
}
.sm-vote-approve { background: var(--sm-success); }
.sm-vote-reject { background: var(--sm-danger); }
.sm-vote-abstain { background: var(--sm-muted); }

/* ── Empty State ── */
.sm-empty {
    text-align: center;
    padding: 3rem;
    color: var(--sm-muted);
}
.sm-empty i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .sm-hero h1 { font-size: 1.8rem; }
    .sm-grid { grid-template-columns: 1fr; }
    .sm-econ-grid { grid-template-columns: 1fr; }
    .sm-tabs { padding: 0 1rem; }
    .sm-panel { padding: 1rem; }
    .sm-kpis { grid-template-columns: repeat(3, 1fr); }
}
</style>

<!-- Hero -->
<div class="sm-hero">
    <h1><i class="fas fa-handshake"></i> Service Marketplace</h1>
    <p>Department-approved services built by agents, creating real jobs and powering the GSM token economy</p>
</div>

<!-- KPI Strip -->
<div class="sm-kpis" id="smKpis">
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiProposals" style="color:var(--sm-info)">-</div><div class="sm-kpi-label">Proposals</div></div>
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiApproved" style="color:var(--sm-success)">-</div><div class="sm-kpi-label">Approved</div></div>
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiDeployed" style="color:var(--sm-primary)">-</div><div class="sm-kpi-label">Deployed</div></div>
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiJobs" style="color:var(--sm-accent)">-</div><div class="sm-kpi-label">Jobs Created</div></div>
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiHolders" style="color:var(--sm-secondary)">-</div><div class="sm-kpi-label">GSM Holders</div></div>
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiCirculating" style="color:var(--sm-primary)">-</div><div class="sm-kpi-label">GSM Circulating</div></div>
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiApiKeys" style="color:var(--sm-info)">-</div><div class="sm-kpi-label">API Keys</div></div>
    <div class="sm-kpi"><div class="sm-kpi-value" id="kpiVotes" style="color:var(--sm-accent)">-</div><div class="sm-kpi-label">Votes Cast</div></div>
</div>

<!-- Tabs -->
<div class="sm-tabs">
    <div class="sm-tab active" data-tab="pipeline"><i class="fas fa-diagram-project"></i> Pipeline</div>
    <div class="sm-tab" data-tab="jobs"><i class="fas fa-briefcase"></i> Job Board</div>
    <div class="sm-tab" data-tab="economy"><i class="fas fa-coins"></i> GSM Economy</div>
    <div class="sm-tab" data-tab="api"><i class="fas fa-key"></i> API Marketplace</div>
    <div class="sm-tab" data-tab="governance"><i class="fas fa-landmark"></i> Governance</div>
</div>

<!-- Tab 1: Pipeline -->
<div class="sm-panel active" id="tab-pipeline">
    <div class="sm-grid" id="pipelineGrid"></div>
</div>

<!-- Tab 2: Job Board -->
<div class="sm-panel" id="tab-jobs">
    <div class="sm-table-wrap">
        <table class="sm-table" id="jobsTable">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>GSM Reward</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody id="jobsBody"></tbody>
        </table>
    </div>
</div>

<!-- Tab 3: GSM Economy -->
<div class="sm-panel" id="tab-economy">
    <div class="sm-econ-grid" id="econGrid"></div>
</div>

<!-- Tab 4: API Marketplace -->
<div class="sm-panel" id="tab-api">
    <div class="sm-grid" id="apiGrid"></div>
</div>

<!-- Tab 5: Governance -->
<div class="sm-panel" id="tab-governance">
    <div class="sm-econ-grid" id="govGrid"></div>
</div>

<script src="/assets/js/service-marketplace-engine.js"></script>


<?php include 'includes/site-footer.inc.php'; ?>
