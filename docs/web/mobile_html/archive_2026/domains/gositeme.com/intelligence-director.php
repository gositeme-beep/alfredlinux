<?php
/**
 * Intelligence Director — Covert Operations & Personnel Network
 * ═══════════════════════════════════════════════════════════════
 * AJAX-driven classified command panel. All data loaded via
 * /api/intel-director.php with pagination & search.
 *
 * ACCESS: Supreme Commander only (Danny Perez)
 */
$page_title = 'Intelligence Director — Classified';
$page_description = 'Classified intelligence operations panel.';
$page_canonical = 'https://gositeme.com/intelligence-director';
$page_robots = 'noindex, nofollow';

include __DIR__ . '/includes/auth-gate.inc.php';

$supremeAdmins = ['gositeme@gmail.com'];
if (!$clientEmail || !in_array(strtolower($clientEmail), $supremeAdmins)) {
    header('Location: /dashboard.php');
    exit;
}

include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#04040a;--surface:#0a0a16;--surface-2:#101024;--surface-3:#181838;
  --border:rgba(255,255,255,.05);--accent:#00ff88;--accent-dim:rgba(0,255,136,.1);
  --red:#ff3344;--gold:#ffd700;--cyan:#00e5ff;--purple:#8b5cf6;--blue:#3b82f6;
  --text:#d0d0e0;--text-muted:#6a6a8a;--text-dim:#444466;
  --radius:12px;--radius-sm:8px;
  --font:'Segoe UI',system-ui,-apple-system,sans-serif;
}
body{font-family:var(--font);background:var(--bg);color:var(--text);line-height:1.5;min-height:100vh}
a{color:var(--accent);text-decoration:none}a:hover{color:#fff}

.intel-wrap{max-width:1600px;margin:0 auto;padding:80px 20px 60px}

.intel-header{padding:24px 28px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:24px;position:relative;overflow:hidden}
.intel-header::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--cyan),var(--purple))}
.intel-header h1{font-size:1.6rem;font-weight:800;display:flex;align-items:center;gap:12px;margin-bottom:4px}
.intel-header h1 i{color:var(--accent);font-size:1.4rem}
.intel-header .sub{color:var(--text-muted);font-size:.85rem}
.intel-header .class-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 14px;border-radius:20px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;background:rgba(255,51,68,.1);color:var(--red);border:1px solid rgba(255,51,68,.2);margin-left:12px}

.intel-tabs{display:flex;gap:4px;padding:4px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;overflow-x:auto;scrollbar-width:none}
.intel-tabs::-webkit-scrollbar{display:none}
.intel-tab{padding:10px 18px;border-radius:var(--radius-sm);cursor:pointer;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);white-space:nowrap;border:none;background:none;font-family:var(--font);transition:all .15s}
.intel-tab:hover{color:var(--text);background:var(--surface-2)}
.intel-tab.active{background:var(--accent-dim);color:var(--accent);border:1px solid rgba(0,255,136,.2)}
.intel-tab .cnt{font-size:.6rem;background:var(--surface-3);padding:1px 6px;border-radius:8px;margin-left:4px}

.intel-panel{display:none}.intel-panel.active{display:block}

.intel-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px}
.intel-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;position:relative;overflow:hidden;cursor:pointer;transition:border-color .2s}
.intel-stat:hover{border-color:rgba(255,255,255,.12)}
.intel-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.intel-stat.green::before{background:var(--accent)}.intel-stat.blue::before{background:var(--blue)}
.intel-stat.purple::before{background:var(--purple)}.intel-stat.red::before{background:var(--red)}
.intel-stat.gold::before{background:var(--gold)}.intel-stat.cyan::before{background:var(--cyan)}
.intel-stat .label{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:4px}
.intel-stat .val{font-size:1.8rem;font-weight:800}
.intel-stat .sub{font-size:.68rem;color:var(--text-dim);margin-top:2px}

.intel-table{width:100%;border-collapse:collapse;font-size:.82rem}
.intel-table th{text-align:left;padding:10px 14px;background:var(--surface-2);color:var(--text-muted);font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1}
.intel-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.intel-table tr:hover td{background:rgba(0,255,136,.02)}
.intel-table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}

.badge{font-size:.65rem;font-weight:700;text-transform:uppercase;padding:3px 10px;border-radius:6px;letter-spacing:.3px;display:inline-block}
.badge-green{background:rgba(0,255,136,.1);color:var(--accent)}.badge-red{background:rgba(255,51,68,.1);color:var(--red)}
.badge-gold{background:rgba(255,215,0,.1);color:var(--gold)}.badge-blue{background:rgba(59,130,246,.1);color:var(--blue)}
.badge-purple{background:rgba(139,92,246,.1);color:var(--purple)}.badge-cyan{background:rgba(0,229,255,.1);color:var(--cyan)}
.badge-muted{background:rgba(255,255,255,.05);color:var(--text-muted)}

.section-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin:24px 0 12px;display:flex;align-items:center;gap:8px}
.section-title i{color:var(--accent);font-size:.7rem}

.intel-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:12px}
.intel-card h3{font-size:.9rem;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.intel-card p{font-size:.82rem;color:var(--text-muted);line-height:1.5}

.intel-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px}

.channel-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px}
.channel-dot.active{background:var(--accent);box-shadow:0 0 6px rgba(0,255,136,.4)}

.intel-search{width:100%;padding:10px 16px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--surface-2);color:var(--text);font-size:.85rem;font-family:var(--font);outline:none;margin-bottom:12px}
.intel-search:focus{border-color:rgba(0,255,136,.3)}
.intel-search::placeholder{color:var(--text-dim)}

.pager{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-top:12px;font-size:.78rem}
.pager-info{color:var(--text-muted)}
.pager-btns{display:flex;gap:6px}
.pager-btn{padding:6px 14px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--surface-2);color:var(--text);font-size:.75rem;cursor:pointer;font-family:var(--font);font-weight:600}
.pager-btn:hover{background:var(--surface-3);border-color:rgba(255,255,255,.12)}
.pager-btn:disabled{opacity:.3;cursor:default}
.pager-btn.active{background:var(--accent-dim);color:var(--accent);border-color:rgba(0,255,136,.3)}

.loading-spinner{text-align:center;padding:40px;color:var(--text-muted)}
.loading-spinner i{font-size:1.4rem;margin-bottom:8px}

/* Role breakdown mini chart */
.role-bar{display:flex;align-items:center;gap:10px;margin:4px 0;font-size:.78rem}
.role-bar-label{width:100px;text-align:right;color:var(--text-muted);text-transform:capitalize}
.role-bar-track{flex:1;height:8px;background:var(--surface-3);border-radius:4px;overflow:hidden}
.role-bar-fill{height:100%;border-radius:4px;transition:width .5s ease}
.role-bar-val{width:60px;font-family:monospace;color:var(--text-dim)}

@media(max-width:768px){
  .intel-wrap{padding:70px 12px 40px}
  .intel-stats{grid-template-columns:repeat(2,1fr)}
  .intel-grid{grid-template-columns:1fr}
  .intel-table{font-size:.72rem}
  .intel-table th,.intel-table td{padding:8px 10px}
}
</style>

<div class="intel-wrap">
    <div class="intel-header">
        <h1>
            <i class="fas fa-satellite"></i>
            Intelligence Director
            <span class="class-badge"><i class="fas fa-lock"></i> CLASSIFIED</span>
        </h1>
        <div class="sub">Global Ecosystem Intelligence &bull; Personnel Network &bull; Operations Oversight &bull; OIC Coordination</div>
    </div>

    <div class="intel-tabs" id="intel-tabs">
        <button class="intel-tab active" data-panel="overview"><i class="fas fa-globe" style="margin-right:6px"></i> Overview</button>
        <button class="intel-tab" data-panel="personnel"><i class="fas fa-users" style="margin-right:6px"></i> Personnel <span class="cnt" id="cnt-personnel">—</span></button>
        <button class="intel-tab" data-panel="agents"><i class="fas fa-robot" style="margin-right:6px"></i> Agent Fleet <span class="cnt" id="cnt-agents">—</span></button>
        <button class="intel-tab" data-panel="channels"><i class="fas fa-tower-broadcast" style="margin-right:6px"></i> Channels</button>
        <button class="intel-tab" data-panel="ops"><i class="fas fa-crosshairs" style="margin-right:6px"></i> Operations</button>
        <button class="intel-tab" data-panel="veil"><i class="fas fa-mask" style="margin-right:6px"></i> Veil Log</button>
        <button class="intel-tab" data-panel="comms"><i class="fas fa-phone-volume" style="margin-right:6px"></i> Intercepts</button>
        <button class="intel-tab" data-panel="oic"><i class="fas fa-satellite-dish" style="margin-right:6px"></i> OIC</button>
    </div>

    <!-- ═══ OVERVIEW ═══ -->
    <div class="intel-panel active" id="panel-overview">
        <div class="intel-stats" id="overview-stats">
            <div class="intel-stat green" onclick="showPanel('agents')"><div class="label">Agent Fleet</div><div class="val" id="s-agents">—</div><div class="sub">Deployed Assets</div></div>
            <div class="intel-stat blue" onclick="showPanel('personnel')"><div class="label">Personnel</div><div class="val" id="s-clients">—</div><div class="sub">Known Contacts</div></div>
            <div class="intel-stat purple" onclick="showPanel('channels')"><div class="label">Comm Channels</div><div class="val" id="s-channels">—</div><div class="sub">Active Networks</div></div>
            <div class="intel-stat gold" onclick="showPanel('ops')"><div class="label">Active Ops</div><div class="val" id="s-directives">—</div><div class="sub">Running Directives</div></div>
            <div class="intel-stat cyan"><div class="label">Standing Orders</div><div class="val" id="s-orders">—</div><div class="sub">Permanent Watch</div></div>
            <div class="intel-stat red" onclick="showPanel('veil')"><div class="label">Veil Events</div><div class="val" id="s-veil">—</div><div class="sub">Access Attempts</div></div>
            <div class="intel-stat blue" onclick="showPanel('comms')"><div class="label">Call Intercepts</div><div class="val" id="s-calls">—</div><div class="sub">Voice Records</div></div>
            <div class="intel-stat green"><div class="label">Active Tasks</div><div class="val" id="s-tasks">—</div><div class="sub">In Queue</div></div>
        </div>

        <!-- Role breakdown -->
        <div class="section-title"><i class="fas fa-chart-bar"></i> Fleet Composition</div>
        <div class="intel-card" id="role-breakdown"><div class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i><br>Loading...</div></div>

        <div class="intel-grid" style="margin-top:16px">
            <div class="intel-card">
                <h3><i class="fas fa-tower-broadcast" style="color:var(--accent)"></i> Communication Network</h3>
                <p style="margin-bottom:12px">Alfred responds from every endpoint.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.78rem">
                    <div><span class="channel-dot active"></span> Web Chat</div>
                    <div><span class="channel-dot active"></span> SMS (Telnyx)</div>
                    <div><span class="channel-dot active"></span> Telegram Bot</div>
                    <div><span class="channel-dot active"></span> Discord Bot</div>
                    <div><span class="channel-dot active"></span> Slack Bot</div>
                    <div><span class="channel-dot active"></span> WhatsApp</div>
                    <div><span class="channel-dot active"></span> Email (SendGrid)</div>
                    <div><span class="channel-dot active"></span> Voice (VAPI)</div>
                </div>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-brain" style="color:var(--purple)"></i> AI Infrastructure</h3>
                <div style="font-size:.78rem;margin-top:8px">
                    <div style="margin-bottom:4px"><span class="badge badge-green">PRIMARY</span> Anthropic Sonnet 4</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 1</span> Groq llama-3.3-70b</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 2</span> OpenAI gpt-4.1-mini</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 3</span> Google gemini-2.5-flash</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 4</span> xAI grok-3-mini</div>
                    <div><span class="badge badge-red">LOCAL</span> Ollama qwen2.5:3b (port 11434)</div>
                </div>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-shield-halved" style="color:var(--red)"></i> Security Posture</h3>
                <p style="margin-bottom:8px">Threat level: <span class="badge badge-green">LOW</span></p>
                <p>Sentinel + Cipher active. Veil Protocol armed. AI cascade: 6 providers + Ollama.</p>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-satellite-dish" style="color:var(--cyan)"></i> OIC Initiative</h3>
                <p>Open Intelligence Collective — Phase 1 (Foundation). <a href="#" onclick="showPanel('oic');return false">View Full Brief &rarr;</a></p>
            </div>
        </div>
    </div>

    <!-- ═══ PERSONNEL ═══ -->
    <div class="intel-panel" id="panel-personnel">
        <input type="text" class="intel-search" id="personnel-search" placeholder="Search personnel by name or email...">
        <div class="intel-table-wrap"><table class="intel-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th>Last Seen</th><th>Clearance</th></tr></thead><tbody id="personnel-tbody"><tr><td colspan="7" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading personnel...</td></tr></tbody></table></div>
        <div class="pager" id="personnel-pager"></div>
    </div>

    <!-- ═══ AGENTS ═══ -->
    <div class="intel-panel" id="panel-agents">
        <input type="text" class="intel-search" id="agent-search" placeholder="Search agents by name, role, or domain...">
        <div class="intel-table-wrap"><table class="intel-table"><thead><tr><th>Agent ID</th><th>Name</th><th>Role</th><th>Domain</th><th>Status</th><th>Success</th><th>Tasks</th><th>Last Active</th></tr></thead><tbody id="agents-tbody"><tr><td colspan="8" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading agents...</td></tr></tbody></table></div>
        <div class="pager" id="agents-pager"></div>
    </div>

    <!-- ═══ CHANNELS ═══ -->
    <div class="intel-panel" id="panel-channels">
        <div class="section-title"><i class="fas fa-chart-bar"></i> Channel Traffic Analysis</div>
        <div class="intel-table-wrap"><table class="intel-table"><thead><tr><th>Channel</th><th>Total Messages</th><th>Last Activity</th><th>Status</th></tr></thead><tbody id="channels-tbody"><tr><td colspan="4" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr></tbody></table></div>

        <div class="section-title" style="margin-top:24px"><i class="fas fa-network-wired"></i> Channel Infrastructure</div>
        <div class="intel-grid">
            <div class="intel-card"><h3><i class="fab fa-telegram" style="color:#26A5E4"></i> Telegram Bot</h3><p>/start, /ask, /help, /status, /tools, /goals, /balance, /news</p><p style="margin-top:6px"><span class="badge badge-green">BOT TOKEN ACTIVE</span> <span class="badge badge-green">WEBHOOK SET</span></p></div>
            <div class="intel-card"><h3><i class="fab fa-discord" style="color:#5865F2"></i> Discord Bot</h3><p>35 modules. /ask, /search, /research, /status, /tools, /help. Stripe billing.</p><p style="margin-top:6px"><span class="badge badge-green">BOT TOKEN</span> <span class="badge badge-green">APP ID</span></p></div>
            <div class="intel-card"><h3><i class="fab fa-slack" style="color:#4A154B"></i> Slack Bot</h3><p>@mention handler, channel processing, signature verification.</p><p style="margin-top:6px"><span class="badge badge-green">BOT TOKEN</span> <span class="badge badge-green">SIGNING SECRET</span></p></div>
            <div class="intel-card"><h3><i class="fab fa-whatsapp" style="color:#25D366"></i> WhatsApp</h3><p>Meta Cloud API v18.0. Webhook challenge validation.</p><p style="margin-top:6px"><span class="badge badge-green">TOKEN</span> <span class="badge badge-green">PHONE ID</span></p></div>
            <div class="intel-card"><h3><i class="fas fa-comment-sms" style="color:var(--accent)"></i> SMS (Telnyx)</h3><p>Inbound/outbound SMS. +1(807)798-2850.</p><p style="margin-top:6px"><span class="badge badge-green">API KEY</span> <span class="badge badge-green">FROM NUMBER</span></p></div>
            <div class="intel-card"><h3><i class="fas fa-phone" style="color:var(--gold)"></i> Voice (VAPI)</h3><p>Inbound: 1-833-GOSITEME. Outbound: auto-calls.</p><p style="margin-top:6px"><span class="badge badge-green">API KEY</span> <span class="badge badge-green">PHONE ID</span></p></div>
        </div>
    </div>

    <!-- ═══ OPERATIONS ═══ -->
    <div class="intel-panel" id="panel-ops">
        <div class="section-title"><i class="fas fa-scroll"></i> Active Directives</div>
        <div class="intel-table-wrap"><table class="intel-table"><thead><tr><th>Priority</th><th>Title</th><th>Type</th><th>Agent</th><th>Status</th><th>Deadline</th></tr></thead><tbody id="ops-directives-tbody"><tr><td colspan="6" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr></tbody></table></div>
        <div class="section-title" style="margin-top:24px"><i class="fas fa-repeat"></i> Standing Orders</div>
        <div class="intel-table-wrap"><table class="intel-table"><thead><tr><th>Priority</th><th>Title</th><th>Schedule</th><th>Agent</th><th>Next Run</th></tr></thead><tbody id="ops-orders-tbody"><tr><td colspan="5" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr></tbody></table></div>
    </div>

    <!-- ═══ VEIL LOG ═══ -->
    <div class="intel-panel" id="panel-veil">
        <div class="section-title"><i class="fas fa-mask"></i> Veil Protocol Access Log</div>
        <div class="intel-table-wrap"><table class="intel-table"><thead><tr><th>Timestamp</th><th>Action</th><th>Channel</th><th>Phone</th><th>Client ID</th><th>IP Address</th><th>Details</th></tr></thead><tbody id="veil-tbody"><tr><td colspan="7" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr></tbody></table></div>
        <div class="pager" id="veil-pager"></div>
    </div>

    <!-- ═══ CALL INTERCEPTS ═══ -->
    <div class="intel-panel" id="panel-comms">
        <div class="section-title"><i class="fas fa-phone-volume"></i> Voice Call Intelligence</div>
        <div class="intel-table-wrap"><table class="intel-table"><thead><tr><th>Call ID</th><th>Phone</th><th>Client</th><th>Type</th><th>Duration</th><th>Summary</th><th>Timestamp</th></tr></thead><tbody id="comms-tbody"><tr><td colspan="7" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr></tbody></table></div>
        <div class="pager" id="comms-pager"></div>
    </div>

    <!-- ═══ OIC ═══ -->
    <div class="intel-panel" id="panel-oic">
        <div class="intel-card" style="border-left:3px solid var(--cyan)">
            <h3><i class="fas fa-globe" style="color:var(--cyan)"></i> Open Intelligence Collective (OIC)</h3>
            <p style="margin-bottom:12px">Decentralized global investigation and intelligence network operating through open data, transparent analysis, and distributed participation.</p>
            <p><span class="badge badge-cyan">PHASE 1 — FOUNDATION</span></p>
        </div>
        <div class="section-title"><i class="fas fa-scroll"></i> Mission</div>
        <div class="intel-card"><p>Establish an open, distributed intelligence system documenting systemic risks, corruption, conflict activity, and exploitation through verifiable open-source evidence.</p></div>
        <div class="section-title"><i class="fas fa-compass"></i> Guiding Principles</div>
        <div class="intel-grid">
            <div class="intel-card"><h3>Personhood-Based Participation</h3><p>Participation derives from human personhood, not nationality or institutional affiliation.</p></div>
            <div class="intel-card"><h3>Transparency</h3><p>Evidence, analytical methods, and investigative processes are publicly accessible.</p></div>
            <div class="intel-card"><h3>Decentralization</h3><p>No single institution maintains permanent control. Authority is distributed across independent nodes.</p></div>
            <div class="intel-card"><h3>Accountability</h3><p>Leadership roles are rotational and subject to transparent review and removal.</p></div>
            <div class="intel-card"><h3>Evidence Integrity</h3><p>All findings must be supported by verifiable data and reproducible analytical methodology.</p></div>
        </div>
        <div class="section-title"><i class="fas fa-sitemap"></i> Structural Components</div>
        <div class="intel-grid">
            <div class="intel-card"><h3><i class="fas fa-map-marker-alt" style="color:var(--accent)"></i> Distributed Research Nodes</h3><p>Regional/thematic groups for open data collection, geospatial analysis, and field documentation.</p></div>
            <div class="intel-card"><h3><i class="fas fa-project-diagram" style="color:var(--blue)"></i> Analytical Coordination Layer</h3><p>Shared infrastructure for cross-node collaboration, data exchange, and joint investigations.</p></div>
            <div class="intel-card"><h3><i class="fas fa-check-double" style="color:var(--gold)"></i> Verification &amp; Integrity Board</h3><p>Rotating review body for evidence validation, methodological review, and replication testing.</p></div>
            <div class="intel-card"><h3><i class="fas fa-database" style="color:var(--purple)"></i> Open Data Repository</h3><p>Public archive: geospatial datasets, environmental monitoring, infrastructure mapping, event timelines.</p></div>
        </div>
        <div class="section-title"><i class="fas fa-road"></i> Implementation Phases</div>
        <div class="intel-card">
            <div style="display:flex;flex-direction:column;gap:10px;font-size:.82rem">
                <div><span class="badge badge-green">PHASE 1</span> <strong>Infrastructure Formation</strong> — Governance protocols, initial systems</div>
                <div><span class="badge badge-blue">PHASE 2</span> <strong>Pilot Node Deployment</strong> — Investigative nodes, test workflows</div>
                <div><span class="badge badge-purple">PHASE 3</span> <strong>Network Expansion</strong> — Increase participation, datasets, capacity</div>
                <div><span class="badge badge-gold">PHASE 4</span> <strong>Persistent Global Network</strong> — Fully operational distributed intelligence</div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    'use strict';
    const API = '/api/intel-director.php';
    const state = { agentsPage: 1, personnelPage: 1, veilPage: 1, commsPage: 1, agentsQ: '', personnelQ: '' };
    let searchTimers = {};

    async function api(action, params = {}) {
        const url = new URL(API, location.origin);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== '') url.searchParams.set(k, v); });
        const r = await fetch(url, { credentials: 'same-origin' });
        return r.json();
    }

    function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
    function fmt(n) { return Number(n).toLocaleString(); }
    function short(s, n) { return s && s.length > n ? s.substring(0, n) + '...' : (s || '—'); }

    // ── Tab switching ──
    document.getElementById('intel-tabs').addEventListener('click', e => {
        const btn = e.target.closest('.intel-tab');
        if (!btn) return;
        showPanel(btn.dataset.panel);
    });

    window.showPanel = function(id) {
        document.querySelectorAll('.intel-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.intel-tab').forEach(t => t.classList.remove('active'));
        document.getElementById('panel-' + id)?.classList.add('active');
        document.querySelector(`[data-panel="${id}"]`)?.classList.add('active');
        if (id === 'agents' && !document.getElementById('agents-tbody').dataset.loaded) loadAgents();
        if (id === 'personnel' && !document.getElementById('personnel-tbody').dataset.loaded) loadPersonnel();
        if (id === 'channels' && !document.getElementById('channels-tbody').dataset.loaded) loadChannels();
        if (id === 'ops' && !document.getElementById('ops-directives-tbody').dataset.loaded) loadOps();
        if (id === 'veil' && !document.getElementById('veil-tbody').dataset.loaded) loadVeil();
        if (id === 'comms' && !document.getElementById('comms-tbody').dataset.loaded) loadComms();
    };

    // ── Search inputs ──
    document.getElementById('agent-search').addEventListener('input', e => {
        clearTimeout(searchTimers.agents);
        searchTimers.agents = setTimeout(() => { state.agentsQ = e.target.value; state.agentsPage = 1; loadAgents(); }, 300);
    });
    document.getElementById('personnel-search').addEventListener('input', e => {
        clearTimeout(searchTimers.personnel);
        searchTimers.personnel = setTimeout(() => { state.personnelQ = e.target.value; state.personnelPage = 1; loadPersonnel(); }, 300);
    });

    // ── Pager builder ──
    function renderPager(containerId, page, pages, total, onPage) {
        const c = document.getElementById(containerId);
        if (!c) return;
        const start = (page - 1) * 25 + 1;
        const end = Math.min(page * 25, total);
        c.innerHTML = `<span class="pager-info">Showing ${start}-${end} of ${fmt(total)}</span>
            <div class="pager-btns">
                <button class="pager-btn" ${page <= 1 ? 'disabled' : ''} data-p="${page-1}">&laquo; Prev</button>
                <span style="padding:6px 10px;color:var(--text-muted);font-size:.75rem">Page ${page} / ${pages}</span>
                <button class="pager-btn" ${page >= pages ? 'disabled' : ''} data-p="${page+1}">Next &raquo;</button>
            </div>`;
        c.querySelectorAll('.pager-btn:not([disabled])').forEach(b => b.addEventListener('click', () => onPage(parseInt(b.dataset.p))));
    }

    // ── Badge helpers ──
    function roleBadge(r) { return r === 'commander' ? 'badge-gold' : r === 'director' ? 'badge-purple' : 'badge-cyan'; }
    function statusBadge(s) { return (s === 'active' || s === 'idle') ? 'badge-green' : s === 'busy' ? 'badge-gold' : s === 'error' ? 'badge-red' : 'badge-muted'; }
    function actionBadge(a) { return (a === 'success' || a === 'activated') ? 'badge-green' : (a === 'denied' || a === 'failed' || a === 'rate_limited') ? 'badge-red' : 'badge-gold'; }

    // ── Load overview stats ──
    async function loadStats() {
        const d = await api('stats');
        if (!d.success) return;
        const s = d.stats;
        document.getElementById('s-agents').textContent = fmt(s.agents);
        document.getElementById('s-clients').textContent = fmt(s.clients);
        document.getElementById('s-channels').textContent = s.channels;
        document.getElementById('s-directives').textContent = s.directives;
        document.getElementById('s-orders').textContent = s.standing_orders;
        document.getElementById('s-veil').textContent = fmt(s.veil_events);
        document.getElementById('s-calls').textContent = fmt(s.calls);
        document.getElementById('s-tasks').textContent = s.active_tasks;
        document.getElementById('cnt-agents').textContent = fmt(s.agents);
        document.getElementById('cnt-personnel').textContent = fmt(s.clients);

        // Role breakdown chart
        if (d.roles && d.roles.length) {
            const maxCnt = Math.max(...d.roles.map(r => parseInt(r.cnt)));
            const colors = ['var(--accent)', 'var(--purple)', 'var(--cyan)', 'var(--blue)', 'var(--gold)', 'var(--red)'];
            let html = '';
            d.roles.forEach((r, i) => {
                const pct = maxCnt > 0 ? (parseInt(r.cnt) / maxCnt * 100) : 0;
                html += `<div class="role-bar"><span class="role-bar-label">${esc(r.role)}</span><div class="role-bar-track"><div class="role-bar-fill" style="width:${pct}%;background:${colors[i % colors.length]}"></div></div><span class="role-bar-val">${fmt(r.cnt)}</span></div>`;
            });
            // Status summary
            if (d.statuses) {
                html += '<div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">';
                d.statuses.forEach(s => {
                    html += `<span class="badge ${statusBadge(s.status)}">${esc(s.status)} (${fmt(s.cnt)})</span>`;
                });
                html += '</div>';
            }
            document.getElementById('role-breakdown').innerHTML = html;
        }
    }

    // ── Agents (paginated) ──
    async function loadAgents() {
        const tb = document.getElementById('agents-tbody');
        tb.innerHTML = '<tr><td colspan="8" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr>';
        const d = await api('agents', { page: state.agentsPage, q: state.agentsQ });
        if (!d.success) { tb.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-dim);padding:20px">Failed to load</td></tr>'; return; }
        tb.dataset.loaded = '1';
        if (!d.agents.length) { tb.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-dim);padding:20px">No agents found</td></tr>'; document.getElementById('agents-pager').innerHTML = ''; return; }
        tb.innerHTML = d.agents.map(a => `<tr>
            <td style="font-family:monospace;font-size:.72rem;color:var(--text-dim)">${esc(a.agent_id)}</td>
            <td style="font-weight:700">${esc(a.name)}</td>
            <td><span class="badge ${roleBadge(a.role)}">${esc(a.role?.toUpperCase())}</span></td>
            <td style="font-size:.78rem">${esc(a.domain || '—')}</td>
            <td><span class="badge ${statusBadge(a.status)}">${esc(a.status?.toUpperCase())}</span></td>
            <td style="font-family:monospace">${Math.round((parseFloat(a.success_rate)||0)*100)}%</td>
            <td style="font-family:monospace">${parseInt(a.total_tasks)||0}</td>
            <td style="font-size:.72rem;color:var(--text-dim)">${a.last_active_at ? a.last_active_at.substring(0,16) : '—'}</td>
        </tr>`).join('');
        renderPager('agents-pager', d.page, d.pages, d.total, p => { state.agentsPage = p; loadAgents(); });
    }

    // ── Personnel (paginated) ──
    async function loadPersonnel() {
        const tb = document.getElementById('personnel-tbody');
        tb.innerHTML = '<tr><td colspan="7" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr>';
        const d = await api('personnel', { page: state.personnelPage, q: state.personnelQ });
        if (!d.success) { tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-dim);padding:20px">Failed to load</td></tr>'; return; }
        tb.dataset.loaded = '1';
        if (!d.personnel.length) { tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-dim);padding:20px">No personnel found</td></tr>'; document.getElementById('personnel-pager').innerHTML = ''; return; }
        tb.innerHTML = d.personnel.map(c => {
            const clearance = parseInt(c.id) === 33 ? 'COMMANDER' : 'STANDARD';
            const clearBadge = parseInt(c.id) === 33 ? 'badge-gold' : 'badge-muted';
            const stBadge = c.status === 'Active' ? 'badge-green' : 'badge-red';
            return `<tr>
                <td style="color:var(--text-dim);font-family:monospace;font-size:.75rem">#${esc(c.id)}</td>
                <td style="font-weight:600">${esc(c.name)}</td>
                <td style="font-size:.78rem;color:var(--text-muted)">${esc(c.email)}</td>
                <td><span class="badge ${stBadge}">${esc(c.status || 'Unknown')}</span></td>
                <td style="font-size:.75rem;color:var(--text-dim)">${esc((c.datecreated||'').substring(0,10))}</td>
                <td style="font-size:.75rem;color:var(--text-dim)">${c.lastlogin ? esc(c.lastlogin.substring(0,16)) : 'Never'}</td>
                <td><span class="badge ${clearBadge}">${clearance}</span></td>
            </tr>`;
        }).join('');
        renderPager('personnel-pager', d.page, d.pages, d.total, p => { state.personnelPage = p; loadPersonnel(); });
    }

    // ── Channels ──
    async function loadChannels() {
        const tb = document.getElementById('channels-tbody');
        const d = await api('channels');
        if (!d.success) return;
        tb.dataset.loaded = '1';
        if (!d.channels.length) { tb.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-dim);padding:20px">No channel data yet</td></tr>'; return; }
        tb.innerHTML = d.channels.map(ch => `<tr>
            <td style="font-weight:700;text-transform:capitalize"><span class="channel-dot active"></span> ${esc(ch.channel)}</td>
            <td style="font-family:monospace;font-weight:600">${fmt(ch.total)}</td>
            <td style="font-size:.78rem;color:var(--text-muted)">${esc(ch.last_activity || '—')}</td>
            <td><span class="badge badge-green">ONLINE</span></td>
        </tr>`).join('');
    }

    // ── Operations ──
    async function loadOps() {
        const dtb = document.getElementById('ops-directives-tbody');
        const otb = document.getElementById('ops-orders-tbody');
        const [dd, od] = await Promise.all([api('directives'), api('standing_orders')]);
        dtb.dataset.loaded = '1';
        if (dd.success && dd.directives.length) {
            dtb.innerHTML = dd.directives.map(d => {
                const pColor = parseInt(d.priority) >= 8 ? 'var(--red)' : parseInt(d.priority) >= 5 ? 'var(--gold)' : 'var(--accent)';
                return `<tr>
                    <td style="font-weight:800;color:${pColor};font-family:monospace">P${parseInt(d.priority)}</td>
                    <td style="font-weight:600">${esc(d.title)}</td>
                    <td><span class="badge badge-purple">${esc((d.type||'general').toUpperCase())}</span></td>
                    <td style="font-size:.78rem">${esc(d.assigned_agent || '—')}</td>
                    <td><span class="badge badge-gold">${esc(d.status.toUpperCase())}</span></td>
                    <td style="font-size:.75rem;color:var(--text-dim)">${d.deadline ? d.deadline.substring(0,16) : '—'}</td>
                </tr>`;
            }).join('');
        } else { dtb.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-dim);padding:20px">No active directives</td></tr>'; }

        if (od.success && od.orders.length) {
            otb.innerHTML = od.orders.map(o => `<tr>
                <td style="font-weight:800;font-family:monospace">P${parseInt(o.priority)}</td>
                <td style="font-weight:600">${esc(o.title)}</td>
                <td style="font-size:.78rem"><span class="badge badge-cyan">${esc(o.schedule || '—')}</span></td>
                <td style="font-size:.78rem">${esc(o.assigned_agent || '—')}</td>
                <td style="font-size:.75rem;color:var(--text-dim)">${o.next_run_at ? o.next_run_at.substring(0,16) : '—'}</td>
            </tr>`).join('');
        } else { otb.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-dim);padding:20px">No standing orders</td></tr>'; }
    }

    // ── Veil Log ──
    async function loadVeil() {
        const tb = document.getElementById('veil-tbody');
        tb.innerHTML = '<tr><td colspan="7" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr>';
        const d = await api('veil_log', { page: state.veilPage });
        if (!d.success) return;
        tb.dataset.loaded = '1';
        if (!d.log.length) { tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-dim);padding:20px">No access attempts recorded</td></tr>'; return; }
        tb.innerHTML = d.log.map(v => `<tr>
            <td style="font-family:monospace;font-size:.72rem">${esc(v.timestamp)}</td>
            <td><span class="badge ${actionBadge(v.action)}">${esc((v.action||'').toUpperCase())}</span></td>
            <td style="text-transform:capitalize">${esc(v.channel || '—')}</td>
            <td style="font-family:monospace;font-size:.75rem">${esc(v.phone || '—')}</td>
            <td style="font-family:monospace">${esc(v.client_id || '—')}</td>
            <td style="font-family:monospace;font-size:.72rem">${esc(v.ip_address || '—')}</td>
            <td style="font-size:.72rem;color:var(--text-dim);max-width:200px;overflow:hidden;text-overflow:ellipsis">${esc(short(v.details, 80))}</td>
        </tr>`).join('');
        renderPager('veil-pager', d.page, d.pages, d.total, p => { state.veilPage = p; loadVeil(); });
    }

    // ── Call Intercepts ──
    async function loadComms() {
        const tb = document.getElementById('comms-tbody');
        tb.innerHTML = '<tr><td colspan="7" class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr>';
        const d = await api('call_log', { page: state.commsPage });
        if (!d.success) return;
        tb.dataset.loaded = '1';
        if (!d.calls.length) { tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-dim);padding:20px">No call records</td></tr>'; return; }
        tb.innerHTML = d.calls.map(c => `<tr>
            <td style="font-family:monospace;font-size:.72rem">#${esc(c.call_id)}</td>
            <td style="font-family:monospace;font-size:.78rem">${esc(c.caller_phone || '—')}</td>
            <td style="font-family:monospace">${c.client_id ? '#' + esc(c.client_id) : '—'}</td>
            <td><span class="badge badge-cyan">${esc((c.call_type||'inbound').toUpperCase())}</span></td>
            <td style="font-family:monospace">${parseInt(c.duration_seconds)||0}s</td>
            <td style="font-size:.75rem;max-width:300px;overflow:hidden;text-overflow:ellipsis">${esc(short(c.summary, 120))}</td>
            <td style="font-size:.72rem;color:var(--text-dim)">${(c.created_at||'').substring(0,16)}</td>
        </tr>`).join('');
        renderPager('comms-pager', d.page, d.pages, d.total, p => { state.commsPage = p; loadComms(); });
    }

    // ── Init ──
    loadStats();
})();
</script>
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
