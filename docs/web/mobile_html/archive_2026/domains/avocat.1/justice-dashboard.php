<?php
/**
 * Justice & Threat Intelligence Dashboard
 * ════════════════════════════════════════
 * Phase 4.5 — Unified view of the justice system,
 * threat intelligence, and accountability ledger.
 *
 * Commander-only (client_id 33).
 */

session_start();
if ((int)($_SESSION['client_id'] ?? 0) !== 33) {
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = "Justice & Threat Intelligence";
$pageDescription = "Unified security, threat intelligence, and accountability dashboard.";
include 'includes/site-header.inc.php';
?>

<style>
:root {
    --jd-bg: #0a0a14;
    --jd-surface: #12121f;
    --jd-border: #1e1e3a;
    --jd-text: #e2e8f0;
    --jd-dim: #8b8fa3;
    --jd-gold: #f5c542;
    --jd-cyan: #22d3ee;
    --jd-purple: #8b5cf6;
    --jd-green: #34d399;
    --jd-red: #ef4444;
    --jd-orange: #f97316;
    --jd-pink: #ec4899;
    --jd-blue: #3b82f6;
}
.jd-wrap { max-width: 1200px; margin: 0 auto; padding: 20px; }

/* ─ Hero ─ */
.jd-hero {
    text-align: center; padding: 40px 20px 24px;
    background: linear-gradient(135deg, rgba(239,68,68,.06), rgba(245,197,66,.06));
    border: 1px solid var(--jd-border); border-radius: 20px;
    margin-bottom: 24px; position: relative; overflow: hidden;
}
.jd-hero::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--jd-red), var(--jd-gold), var(--jd-cyan), var(--jd-red));
    background-size: 300% 100%; animation: jdShimmer 6s linear infinite;
}
@keyframes jdShimmer { 0%{background-position:0 0} 100%{background-position:300% 0} }
.jd-hero h1 { font-size: 2rem; color: var(--jd-gold); margin-bottom: 6px; }
.jd-hero .jd-sub { color: var(--jd-dim); font-size: .9rem; }

/* ─ Tabs ─ */
.jd-tabs {
    display: flex; gap: 4px; margin-bottom: 20px; overflow-x: auto;
    border-bottom: 1px solid var(--jd-border); padding-bottom: 0;
}
.jd-tab {
    padding: 10px 18px; border: none; background: none; color: var(--jd-dim);
    font-size: .85rem; font-weight: 600; cursor: pointer; border-radius: 10px 10px 0 0;
    white-space: nowrap; transition: all .2s; border-bottom: 2px solid transparent;
}
.jd-tab:hover { color: var(--jd-text); background: rgba(255,255,255,.03); }
.jd-tab.active { color: var(--jd-gold); border-bottom-color: var(--jd-gold); background: rgba(245,197,66,.06); }
.jd-tab .jd-badge {
    display: inline-block; min-width: 18px; height: 18px; padding: 0 5px;
    border-radius: 9px; font-size: .7rem; font-weight: 700;
    margin-left: 6px; line-height: 18px; text-align: center;
}
.jd-badge-red { background: rgba(239,68,68,.2); color: var(--jd-red); }
.jd-badge-green { background: rgba(52,211,153,.2); color: var(--jd-green); }
.jd-badge-blue { background: rgba(59,130,246,.2); color: var(--jd-blue); }

/* ─ Panels ─ */
.jd-panel { display: none; }
.jd-panel.active { display: block; }

/* ─ Stats Grid ─ */
.jd-stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px; margin-bottom: 24px;
}
.jd-stat {
    background: var(--jd-surface); border: 1px solid var(--jd-border);
    border-radius: 12px; padding: 16px; text-align: center;
}
.jd-stat-num {
    font-size: 1.6rem; font-weight: 800; font-family: 'JetBrains Mono', monospace;
}
.jd-stat-lbl {
    font-size: .72rem; color: var(--jd-dim); text-transform: uppercase;
    letter-spacing: .05em; margin-top: 4px;
}

/* ─ Card ─ */
.jd-card {
    background: var(--jd-surface); border: 1px solid var(--jd-border);
    border-radius: 14px; padding: 20px; margin-bottom: 16px;
}
.jd-card h3 {
    color: var(--jd-gold); font-size: 1rem; margin-bottom: 14px;
    padding-bottom: 8px; border-bottom: 1px solid var(--jd-border);
    display: flex; align-items: center; gap: 8px;
}

/* ─ Table ─ */
.jd-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.jd-table th {
    text-align: left; padding: 8px 10px; background: rgba(255,255,255,.03);
    color: var(--jd-gold); font-size: .75rem; text-transform: uppercase; letter-spacing: .5px;
}
.jd-table td { padding: 8px 10px; border-bottom: 1px solid var(--jd-border); }
.jd-table tr:hover td { background: rgba(255,255,255,.02); }

/* ─ Severity Badges ─ */
.jd-sev {
    display: inline-block; padding: 2px 8px; border-radius: 4px;
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
}
.jd-sev-critical { background: rgba(239,68,68,.15); color: var(--jd-red); }
.jd-sev-high { background: rgba(249,115,22,.15); color: var(--jd-orange); }
.jd-sev-medium { background: rgba(245,197,66,.15); color: var(--jd-gold); }
.jd-sev-low { background: rgba(34,211,238,.15); color: var(--jd-cyan); }
.jd-sev-info { background: rgba(139,92,246,.15); color: var(--jd-purple); }

/* ─ Status Badges ─ */
.jd-status {
    display: inline-block; padding: 2px 8px; border-radius: 4px;
    font-size: .72rem; font-weight: 600;
}
.jd-status-detected, .jd-status-active { background: rgba(239,68,68,.12); color: var(--jd-red); }
.jd-status-investigating { background: rgba(249,115,22,.12); color: var(--jd-orange); }
.jd-status-confirmed { background: rgba(245,197,66,.12); color: var(--jd-gold); }
.jd-status-mitigated { background: rgba(59,130,246,.12); color: var(--jd-blue); }
.jd-status-resolved, .jd-status-closed { background: rgba(52,211,153,.12); color: var(--jd-green); }
.jd-status-blocked { background: rgba(239,68,68,.15); color: var(--jd-red); }

/* ─ Forms ─ */
.jd-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 16px; }
.jd-input, .jd-select {
    background: var(--jd-bg); border: 1px solid var(--jd-border); border-radius: 8px;
    padding: 8px 12px; color: var(--jd-text); font-size: .85rem;
}
.jd-input:focus, .jd-select:focus { border-color: var(--jd-gold); outline: none; }
.jd-textarea {
    background: var(--jd-bg); border: 1px solid var(--jd-border); border-radius: 8px;
    padding: 8px 12px; color: var(--jd-text); font-size: .85rem;
    width: 100%; min-height: 80px; resize: vertical;
}
.jd-btn {
    padding: 8px 16px; border: none; border-radius: 8px; font-weight: 700;
    font-size: .82rem; cursor: pointer; transition: all .2s;
}
.jd-btn-primary { background: var(--jd-gold); color: #0a0a14; }
.jd-btn-primary:hover { filter: brightness(1.1); }
.jd-btn-danger { background: rgba(239,68,68,.15); color: var(--jd-red); border: 1px solid var(--jd-red); }
.jd-btn-danger:hover { background: rgba(239,68,68,.25); }
.jd-btn-sm { padding: 4px 10px; font-size: .75rem; }

/* ─ Empty State ─ */
.jd-empty { text-align: center; padding: 40px; color: var(--jd-dim); }
.jd-empty i { font-size: 2rem; margin-bottom: 10px; display: block; }

/* ─ Loading ─ */
.jd-loading { text-align: center; padding: 30px; color: var(--jd-dim); }

@media (max-width: 768px) {
    .jd-wrap { padding: 10px; }
    .jd-hero h1 { font-size: 1.4rem; }
    .jd-stats { grid-template-columns: repeat(2, 1fr); }
    .jd-form { grid-template-columns: 1fr; }
    .jd-tabs { gap: 2px; }
    .jd-tab { padding: 8px 12px; font-size: .78rem; }
}
</style>

<div class="jd-wrap">
    <!-- Hero -->
    <div class="jd-hero">
        <h1><i class="fas fa-shield-alt"></i> Justice & Threat Intelligence</h1>
        <div class="jd-sub">Threat detection &bull; Accountability &bull; Court system &bull; Fleet security</div>
    </div>

    <!-- Stats Bar (loaded dynamically) -->
    <div class="jd-stats" id="jd-overview-stats">
        <div class="jd-stat"><div class="jd-stat-num" style="color:var(--jd-red)" id="stat-threats">—</div><div class="jd-stat-lbl">Active Threats</div></div>
        <div class="jd-stat"><div class="jd-stat-num" style="color:var(--jd-orange)" id="stat-blocked">—</div><div class="jd-stat-lbl">Blocked Actors</div></div>
        <div class="jd-stat"><div class="jd-stat-num" style="color:var(--jd-gold)" id="stat-infractions">—</div><div class="jd-stat-lbl">Infractions</div></div>
        <div class="jd-stat"><div class="jd-stat-num" style="color:var(--jd-cyan)" id="stat-cases">—</div><div class="jd-stat-lbl">Open Cases</div></div>
        <div class="jd-stat"><div class="jd-stat-num" style="color:var(--jd-purple)" id="stat-inmates">—</div><div class="jd-stat-lbl">Inmates</div></div>
        <div class="jd-stat"><div class="jd-stat-num" style="color:var(--jd-green)" id="stat-ledger">—</div><div class="jd-stat-lbl">Ledger Actions</div></div>
    </div>

    <!-- Tabs -->
    <div class="jd-tabs">
        <button class="jd-tab active" onclick="JD.showTab('threats')"><i class="fas fa-exclamation-triangle"></i> Threats <span class="jd-badge jd-badge-red" id="tab-threats-count">0</span></button>
        <button class="jd-tab" onclick="JD.showTab('blocked')"><i class="fas fa-ban"></i> Blocked</button>
        <button class="jd-tab" onclick="JD.showTab('ledger')"><i class="fas fa-book"></i> Ledger <span class="jd-badge jd-badge-blue" id="tab-ledger-count">0</span></button>
        <button class="jd-tab" onclick="JD.showTab('court')"><i class="fas fa-gavel"></i> Court Cases</button>
        <button class="jd-tab" onclick="JD.showTab('jail')"><i class="fas fa-lock"></i> Jail</button>
        <button class="jd-tab" onclick="JD.showTab('report')"><i class="fas fa-plus-circle"></i> Report Threat</button>
    </div>

    <!-- ═══ THREATS PANEL ═══ -->
    <div class="jd-panel active" id="panel-threats">
        <div class="jd-card">
            <h3><i class="fas fa-exclamation-triangle"></i> Threat Intelligence Feed</h3>
            <div class="jd-form">
                <select class="jd-select" id="threat-filter-type" onchange="JD.loadThreats()">
                    <option value="">All Types</option>
                    <option value="attack">Attack</option>
                    <option value="fraud">Fraud</option>
                    <option value="abuse">Abuse</option>
                    <option value="intrusion">Intrusion</option>
                    <option value="reconnaissance">Recon</option>
                    <option value="malware">Malware</option>
                    <option value="social_engineering">Social Eng.</option>
                    <option value="insider">Insider</option>
                    <option value="infrastructure">Infrastructure</option>
                </select>
                <select class="jd-select" id="threat-filter-severity" onchange="JD.loadThreats()">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                    <option value="info">Info</option>
                </select>
                <select class="jd-select" id="threat-filter-status" onchange="JD.loadThreats()">
                    <option value="">All Statuses</option>
                    <option value="detected">Detected</option>
                    <option value="investigating">Investigating</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="mitigated">Mitigated</option>
                    <option value="resolved">Resolved</option>
                </select>
                <input class="jd-input" id="threat-filter-search" placeholder="Search threats..." onkeyup="JD.debounceLoadThreats()">
            </div>
            <div id="threats-table-wrap">
                <div class="jd-loading"><i class="fas fa-spinner fa-spin"></i> Loading threats...</div>
            </div>
        </div>
    </div>

    <!-- ═══ BLOCKED PANEL ═══ -->
    <div class="jd-panel" id="panel-blocked">
        <div class="jd-card">
            <h3><i class="fas fa-ban"></i> Currently Blocked Actors</h3>
            <div id="blocked-table-wrap">
                <div class="jd-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>

    <!-- ═══ LEDGER PANEL ═══ -->
    <div class="jd-panel" id="panel-ledger">
        <div class="jd-card">
            <h3><i class="fas fa-book"></i> Accountability Ledger — Full Audit Trail</h3>
            <div class="jd-form">
                <select class="jd-select" id="ledger-filter-type" onchange="JD.loadLedger()">
                    <option value="">All Actions</option>
                    <option value="threat_blocked">Threat Blocked</option>
                    <option value="threat_mitigated">Threat Mitigated</option>
                    <option value="agent_disciplined">Agent Disciplined</option>
                    <option value="investigation_opened">Investigation Opened</option>
                    <option value="investigation_closed">Investigation Closed</option>
                    <option value="policy_enforced">Policy Enforced</option>
                    <option value="access_revoked">Access Revoked</option>
                    <option value="system_hardened">System Hardened</option>
                </select>
                <select class="jd-select" id="ledger-filter-severity" onchange="JD.loadLedger()">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                </select>
                <input class="jd-input" id="ledger-filter-search" placeholder="Search ledger..." onkeyup="JD.debounceLoadLedger()">
            </div>
            <div id="ledger-table-wrap">
                <div class="jd-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>

    <!-- ═══ COURT CASES PANEL ═══ -->
    <div class="jd-panel" id="panel-court">
        <div class="jd-card">
            <h3><i class="fas fa-gavel"></i> Court Cases — Agent Justice System</h3>
            <div id="court-table-wrap">
                <div class="jd-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>

    <!-- ═══ JAIL PANEL ═══ -->
    <div class="jd-panel" id="panel-jail">
        <div class="jd-card">
            <h3><i class="fas fa-lock"></i> Current Jail Population</h3>
            <div id="jail-table-wrap">
                <div class="jd-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>

    <!-- ═══ REPORT THREAT PANEL ═══ -->
    <div class="jd-panel" id="panel-report">
        <div class="jd-card">
            <h3><i class="fas fa-plus-circle"></i> Report New Threat</h3>
            <div style="display:grid;gap:12px;max-width:600px;">
                <input class="jd-input" id="rpt-title" placeholder="Threat title (required)">
                <textarea class="jd-textarea" id="rpt-desc" placeholder="Description — what happened, what was targeted (required)"></textarea>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <select class="jd-select" id="rpt-type">
                        <option value="unknown">Type: Unknown</option>
                        <option value="attack">Attack</option>
                        <option value="fraud">Fraud</option>
                        <option value="abuse">Abuse</option>
                        <option value="intrusion">Intrusion</option>
                        <option value="reconnaissance">Recon</option>
                        <option value="malware">Malware</option>
                        <option value="social_engineering">Social Eng.</option>
                        <option value="insider">Insider Threat</option>
                        <option value="infrastructure">Infrastructure</option>
                    </select>
                    <select class="jd-select" id="rpt-severity">
                        <option value="medium">Severity: Medium</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
                        <option value="info">Info</option>
                    </select>
                </div>
                <input class="jd-input" id="rpt-ip" placeholder="Source IP (optional)">
                <input class="jd-input" id="rpt-vector" placeholder="Attack vector (optional, e.g. SQL injection, brute force)">
                <input class="jd-input" id="rpt-target" placeholder="Target resource (optional, e.g. /api/login)">
                <button class="jd-btn jd-btn-primary" onclick="JD.reportThreat()"><i class="fas fa-paper-plane"></i> Submit Threat Report</button>
                <div id="rpt-result" style="display:none;padding:10px;border-radius:8px;font-size:.85rem;"></div>
            </div>
        </div>
    </div>
</div>

<script>
const JD = (() => {
    const API = '/api/threat-intel.php';
    const JUSTICE_API = '/api/justice-system.php';
    let debounceTimer = null;
    let debounceTimerLedger = null;

    async function apiFetch(url) {
        const r = await fetch(url);
        return r.json();
    }
    async function apiPost(url, body) {
        const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        return r.json();
    }

    function sevBadge(sev) {
        return `<span class="jd-sev jd-sev-${sev}">${sev}</span>`;
    }
    function statusBadge(s) {
        return `<span class="jd-status jd-status-${s}">${s.replace('_', ' ')}</span>`;
    }
    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    function shortDate(d) { return d ? d.substring(0, 16) : '-'; }

    // ── Load Overview Stats ──
    async function loadOverview() {
        const d = await apiFetch(`${API}?action=overview`);
        document.getElementById('stat-threats').textContent = d.threats?.active ?? 0;
        document.getElementById('stat-blocked').textContent = d.blocked_actors ?? 0;
        document.getElementById('stat-infractions').textContent = d.justice?.infractions ?? 0;
        document.getElementById('stat-cases').textContent = d.justice?.open_cases ?? 0;
        document.getElementById('stat-inmates').textContent = d.justice?.inmates ?? 0;
        document.getElementById('stat-ledger').textContent = d.accountability?.total ?? 0;
        document.getElementById('tab-threats-count').textContent = d.threats?.active ?? 0;
        document.getElementById('tab-ledger-count').textContent = d.accountability?.total ?? 0;
    }

    // ── Threats ──
    async function loadThreats() {
        const type = document.getElementById('threat-filter-type').value;
        const sev = document.getElementById('threat-filter-severity').value;
        const status = document.getElementById('threat-filter-status').value;
        const search = document.getElementById('threat-filter-search').value;

        let url = `${API}?action=threats`;
        if (type) url += `&type=${encodeURIComponent(type)}`;
        if (sev) url += `&severity=${encodeURIComponent(sev)}`;
        if (status) url += `&status=${encodeURIComponent(status)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        const d = await apiFetch(url);
        const wrap = document.getElementById('threats-table-wrap');

        if (!d.threats || d.threats.length === 0) {
            wrap.innerHTML = '<div class="jd-empty"><i class="fas fa-shield-alt"></i> No threats found. The perimeter is clear.</div>';
            return;
        }

        let html = `<table class="jd-table"><thead><tr>
            <th>ID</th><th>Type</th><th>Severity</th><th>Status</th><th>Title</th><th>Source IP</th><th>Detected</th><th>Actions</th>
        </tr></thead><tbody>`;
        d.threats.forEach(t => {
            html += `<tr>
                <td style="font-family:monospace;font-size:.78rem">${esc(t.threat_id)}</td>
                <td>${esc(t.threat_type)}</td>
                <td>${sevBadge(t.severity)}</td>
                <td>${statusBadge(t.status)}</td>
                <td>${esc(t.title)}</td>
                <td style="font-family:monospace">${esc(t.source_ip || '-')}</td>
                <td style="font-size:.78rem">${shortDate(t.detected_at)}</td>
                <td>
                    ${t.response_action !== 'blocked' ? `<button class="jd-btn jd-btn-danger jd-btn-sm" onclick="JD.blockThreat(${t.id})"><i class="fas fa-ban"></i></button>` : '<span class="jd-status-blocked">Blocked</span>'}
                    <button class="jd-btn jd-btn-sm" style="background:rgba(52,211,153,.15);color:var(--jd-green);margin-left:4px" onclick="JD.resolveThreat(${t.id})"><i class="fas fa-check"></i></button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        if (d.total > 50) html += `<div style="text-align:center;padding:10px;color:var(--jd-dim);font-size:.82rem">Showing 50 of ${d.total}</div>`;
        wrap.innerHTML = html;
    }

    function debounceLoadThreats() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadThreats, 400);
    }

    // ── Blocked ──
    async function loadBlocked() {
        const d = await apiFetch(`${API}?action=blocked`);
        const wrap = document.getElementById('blocked-table-wrap');

        if (!d.blocked || d.blocked.length === 0) {
            wrap.innerHTML = '<div class="jd-empty"><i class="fas fa-check-circle"></i> No blocked actors. All clear.</div>';
            return;
        }

        let html = `<table class="jd-table"><thead><tr>
            <th>Threat ID</th><th>Type</th><th>Severity</th><th>Source IP</th><th>Title</th><th>Blocked Until</th><th>Actions</th>
        </tr></thead><tbody>`;
        d.blocked.forEach(b => {
            html += `<tr>
                <td style="font-family:monospace;font-size:.78rem">${esc(b.threat_id)}</td>
                <td>${esc(b.threat_type)}</td>
                <td>${sevBadge(b.severity)}</td>
                <td style="font-family:monospace">${esc(b.source_ip || '-')}</td>
                <td>${esc(b.title)}</td>
                <td style="font-size:.78rem">${b.blocked_until ? shortDate(b.blocked_until) : 'Indefinite'}</td>
                <td><button class="jd-btn jd-btn-sm" style="background:rgba(52,211,153,.15);color:var(--jd-green)" onclick="JD.unblockActor(${b.id})"><i class="fas fa-unlock"></i> Unblock</button></td>
            </tr>`;
        });
        wrap.innerHTML = html + '</tbody></table>';
    }

    // ── Ledger ──
    async function loadLedger() {
        const type = document.getElementById('ledger-filter-type').value;
        const sev = document.getElementById('ledger-filter-severity').value;
        const search = document.getElementById('ledger-filter-search').value;

        let url = `${API}?action=ledger`;
        if (type) url += `&action_type=${encodeURIComponent(type)}`;
        if (sev) url += `&severity=${encodeURIComponent(sev)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        const d = await apiFetch(url);
        const wrap = document.getElementById('ledger-table-wrap');

        if (!d.ledger || d.ledger.length === 0) {
            wrap.innerHTML = '<div class="jd-empty"><i class="fas fa-book"></i> No ledger entries yet.</div>';
            return;
        }

        let html = `<table class="jd-table"><thead><tr>
            <th>ID</th><th>Action</th><th>Severity</th><th>Actor</th><th>Title</th><th>Outcome</th><th>Time</th>
        </tr></thead><tbody>`;
        d.ledger.forEach(l => {
            html += `<tr>
                <td style="font-family:monospace;font-size:.78rem">${esc(l.ledger_id)}</td>
                <td>${esc(l.action_type.replace(/_/g, ' '))}</td>
                <td>${sevBadge(l.severity)}</td>
                <td>${esc(l.actor_name || l.actor_id || '-')}</td>
                <td>${esc(l.title)}</td>
                <td>${statusBadge(l.outcome)}</td>
                <td style="font-size:.78rem">${shortDate(l.created_at)}</td>
            </tr>`;
        });
        wrap.innerHTML = html + '</tbody></table>';
    }

    function debounceLoadLedger() {
        clearTimeout(debounceTimerLedger);
        debounceTimerLedger = setTimeout(loadLedger, 400);
    }

    // ── Court Cases ──
    async function loadCourt() {
        const d = await apiFetch(`${JUSTICE_API}?action=cases`);
        const wrap = document.getElementById('court-table-wrap');

        if (!d.cases || d.cases.length === 0) {
            wrap.innerHTML = '<div class="jd-empty"><i class="fas fa-gavel"></i> No court cases filed.</div>';
            return;
        }

        let html = `<table class="jd-table"><thead><tr>
            <th>Case #</th><th>Defendant</th><th>Dept</th><th>Status</th><th>Verdict</th><th>Judge</th><th>Filed</th>
        </tr></thead><tbody>`;
        d.cases.forEach(c => {
            html += `<tr>
                <td style="font-family:monospace;font-size:.78rem">${esc(c.case_number)}</td>
                <td>${esc(c.defendant_name)}</td>
                <td>${esc(c.department_jurisdiction)}</td>
                <td>${statusBadge(c.status)}</td>
                <td>${c.verdict && c.verdict !== 'pending' ? esc(c.verdict) : '<span style="color:var(--jd-dim)">Pending</span>'}</td>
                <td>${esc(c.judge_name || '-')}</td>
                <td style="font-size:.78rem">${shortDate(c.created_at)}</td>
            </tr>`;
        });
        wrap.innerHTML = html + '</tbody></table>';
    }

    // ── Jail ──
    async function loadJail() {
        const d = await apiFetch(`${JUSTICE_API}?action=jail`);
        const wrap = document.getElementById('jail-table-wrap');

        if (!d.inmates || d.inmates.length === 0) {
            wrap.innerHTML = '<div class="jd-empty"><i class="fas fa-dove"></i> No inmates. All agents are free citizens.</div>';
            return;
        }

        let html = `<table class="jd-table"><thead><tr>
            <th>Agent</th><th>Case</th><th>Sentence</th><th>Dept</th><th>Ends</th><th>Actions</th>
        </tr></thead><tbody>`;
        d.inmates.forEach(i => {
            html += `<tr>
                <td>${esc(i.agent_name)}</td>
                <td style="font-family:monospace;font-size:.78rem">${esc(i.case_number)}</td>
                <td>${esc(i.sentence_type)}</td>
                <td>${esc(i.department)}</td>
                <td style="font-size:.78rem">${shortDate(i.ends_at)}</td>
                <td><button class="jd-btn jd-btn-sm" style="background:rgba(52,211,153,.15);color:var(--jd-green)" onclick="JD.releaseInmate(${i.id})"><i class="fas fa-unlock"></i> Release</button></td>
            </tr>`;
        });
        wrap.innerHTML = html + '</tbody></table>';
    }

    // ── Report Threat ──
    async function reportThreat() {
        const title = document.getElementById('rpt-title').value.trim();
        const desc = document.getElementById('rpt-desc').value.trim();
        if (!title || !desc) { alert('Title and description are required.'); return; }

        const body = {
            title, description: desc,
            threat_type: document.getElementById('rpt-type').value,
            severity: document.getElementById('rpt-severity').value,
            source_ip: document.getElementById('rpt-ip').value.trim() || undefined,
            attack_vector: document.getElementById('rpt-vector').value.trim() || undefined,
            target_resource: document.getElementById('rpt-target').value.trim() || undefined,
            source_type: 'human_report'
        };

        const d = await apiPost(`${API}?action=report_threat`, body);
        const res = document.getElementById('rpt-result');
        if (d.reported) {
            res.style.display = 'block';
            res.style.background = 'rgba(52,211,153,.1)';
            res.style.color = 'var(--jd-green)';
            res.textContent = `Threat reported: ${d.threat_id}`;
            document.getElementById('rpt-title').value = '';
            document.getElementById('rpt-desc').value = '';
            document.getElementById('rpt-ip').value = '';
            document.getElementById('rpt-vector').value = '';
            document.getElementById('rpt-target').value = '';
            loadOverview();
        } else {
            res.style.display = 'block';
            res.style.background = 'rgba(239,68,68,.1)';
            res.style.color = 'var(--jd-red)';
            res.textContent = d.error || 'Failed to report threat';
        }
    }

    // ── Actions ──
    async function blockThreat(id) {
        const hours = prompt('Block for how many hours? (0 = indefinite)', '24');
        if (hours === null) return;
        await apiPost(`${API}?action=block_actor`, { id, duration_hours: parseInt(hours) || 0 });
        loadThreats();
        loadOverview();
    }

    async function resolveThreat(id) {
        const notes = prompt('Resolution notes (optional):');
        if (notes === null) return;
        await apiPost(`${API}?action=update_threat`, { id, status: 'resolved', resolution_notes: notes || 'Resolved by Commander' });
        loadThreats();
        loadOverview();
    }

    async function unblockActor(id) {
        if (!confirm('Unblock this actor?')) return;
        await apiPost(`${API}?action=unblock_actor`, { id });
        loadBlocked();
        loadOverview();
    }

    async function releaseInmate(sentenceId) {
        if (!confirm('Release this inmate?')) return;
        await apiPost(`${JUSTICE_API}?action=release`, { sentence_id: sentenceId, release_type: 'commuted' });
        loadJail();
        loadOverview();
    }

    // ── Tab Management ──
    function showTab(name) {
        document.querySelectorAll('.jd-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.jd-panel').forEach(p => p.classList.remove('active'));
        document.getElementById(`panel-${name}`).classList.add('active');
        event.currentTarget.classList.add('active');

        const loaders = {
            threats: loadThreats,
            blocked: loadBlocked,
            ledger: loadLedger,
            court: loadCourt,
            jail: loadJail
        };
        if (loaders[name]) loaders[name]();
    }

    // ── Init ──
    loadOverview();
    loadThreats();

    return {
        showTab, loadThreats, debounceLoadThreats, loadBlocked,
        loadLedger, debounceLoadLedger, loadCourt, loadJail,
        reportThreat, blockThreat, resolveThreat, unblockActor, releaseInmate
    };
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
