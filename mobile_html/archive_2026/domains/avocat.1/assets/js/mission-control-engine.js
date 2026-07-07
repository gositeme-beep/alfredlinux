// ═══ Panel Navigation ═══
function showPanel(name) {
    document.querySelectorAll('.mc-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.mc-nav-item').forEach(n => n.classList.remove('active'));
    const panel = document.getElementById('panel-' + name);
    if (panel) panel.classList.add('active');
    // Highlight nav item
    document.querySelectorAll('.mc-nav-item').forEach(n => {
        if (n.getAttribute('onclick') && n.getAttribute('onclick').includes(name)) n.classList.add('active');
    });
    // Lazy load panel data
    if (name === 'proposals') loadProposals();
    if (name === 'reports') loadReports();
    if (name === 'agents') { if (window.MCV2) MCV2.loadFleet(); else loadAgentFleet(); }
    if (name === 'fleetops') { if (window.MCV2) MCV2.loadFleetOps(); }
    if (name === 'monitoring') loadFleetStatus();
    if (name === 'advisory') loadAdvisoryBrief();
}

// ═══ API Calls ═══
async function apiCall(endpoint, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const resp = await fetch(endpoint, opts);
    return resp.json();
}

// ═══ Load Proposals ═══
async function loadProposals() {
    const status = document.getElementById('proposal-filter-status')?.value || '';
    const category = document.getElementById('proposal-filter-category')?.value || '';
    let url = '/api/agent-autonomy.php?action=proposals';
    if (status) url += '&status=' + status;
    if (category) url += '&category=' + category;
    
    try {
        const data = await apiCall(url);
        const list = document.getElementById('proposals-list');
        if (!data.proposals || data.proposals.length === 0) {
            list.innerHTML = '<div style="text-align:center;color:var(--mc-muted);padding:40px;"><i class="fas fa-inbox" style="font-size:32px;margin-bottom:12px;display:block;"></i>No proposals found</div>';
            return;
        }
        list.innerHTML = data.proposals.map(p => {
            const priorityColors = {urgent:'red',critical:'red',high:'amber',medium:'blue',low:'green'};
            const pc = priorityColors[p.priority] || 'blue';
            const scoreColor = p.advisory_score >= 75 ? 'var(--mc-green)' : p.advisory_score >= 50 ? 'var(--mc-amber)' : 'var(--mc-red)';
            const statusColors = {pending:'amber',advisory_review:'purple',approved:'green',rejected:'red',in_progress:'cyan',completed:'green',cancelled:'red'};
            const sc = statusColors[p.status] || 'blue';
            
            return `<div class="mc-proposal">
                <div class="mc-proposal-header">
                    <div>
                        <div class="mc-proposal-title">${esc(p.title)}</div>
                        <div class="mc-proposal-agent">${esc(p.agent_name)} · ${esc(p.category)} · ${esc(p.created_at)}</div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span class="mc-badge ${sc}">${p.status.replace('_',' ')}</span>
                        <span class="mc-badge ${pc}">${p.priority}</span>
                        ${p.advisory_score !== null ? `<div style="display:flex;align-items:center;gap:6px;">
                            <div class="mc-score-bar"><div class="mc-score-fill" style="width:${p.advisory_score}%;background:${scoreColor};"></div></div>
                            <span style="font-size:11px;color:var(--mc-muted);">${p.advisory_score}/100</span>
                        </div>` : ''}
                    </div>
                </div>
                <div class="mc-proposal-body">${esc(p.description ? p.description.substring(0,300) : '')}</div>
                <div class="mc-proposal-meta">
                    ${p.estimated_cost > 0 ? `<span class="mc-badge amber"><i class="fas fa-dollar-sign"></i> ${parseFloat(p.estimated_cost).toFixed(2)}</span>` : '<span class="mc-badge green">Free</span>'}
                    <span class="mc-badge ${p.risk_level === 'high' || p.risk_level === 'critical' ? 'red' : 'blue'}">${p.risk_level} risk</span>
                    ${p.estimated_hours > 0 ? `<span class="mc-badge purple"><i class="fas fa-clock"></i> ${p.estimated_hours}h</span>` : ''}
                </div>
                ${['pending','advisory_review'].includes(p.status) ? `<div class="mc-proposal-actions">
                    <button class="mc-btn mc-btn-approve" onclick="approveProposal(${p.id})"><i class="fas fa-check"></i> Approve</button>
                    <button class="mc-btn mc-btn-reject" onclick="rejectProposal(${p.id})"><i class="fas fa-times"></i> Reject</button>
                </div>` : ''}
            </div>`;
        }).join('');
    } catch (e) {
        document.getElementById('proposals-list').innerHTML = '<div style="color:var(--mc-red);">Error loading proposals</div>';
    }
}

// ═══ Load Reports ═══
async function loadReports() {
    const type = document.getElementById('report-filter-type')?.value || '';
    const unread = document.getElementById('report-unread-only')?.checked;
    let url = '/api/agent-autonomy.php?action=reports';
    if (type) url += '&type=' + type;
    if (unread) url += '&unread=1';
    
    try {
        const data = await apiCall(url);
        const list = document.getElementById('reports-list');
        if (!data.reports || data.reports.length === 0) {
            list.innerHTML = '<div style="text-align:center;color:var(--mc-muted);padding:40px;">No reports found</div>';
            return;
        }
        const sevIcons = {info:'fa-info-circle',notice:'fa-bell',warning:'fa-exclamation-triangle',critical:'fa-exclamation-circle',emergency:'fa-skull-crossbones'};
        const sevColors = {info:'var(--mc-blue)',notice:'var(--mc-cyan)',warning:'var(--mc-amber)',critical:'var(--mc-red)',emergency:'var(--mc-red)'};
        
        list.innerHTML = '<div class="mc-card">' + data.reports.map(r => `
            <div class="mc-report">
                <div class="mc-report-icon" style="background:${sevColors[r.severity]}15;color:${sevColors[r.severity]};"><i class="fas ${sevIcons[r.severity] || 'fa-info-circle'}"></i></div>
                <div style="flex:1;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                        <div class="mc-report-title">${esc(r.title)}</div>
                        <div style="display:flex;gap:4px;">
                            <span class="mc-badge ${r.severity === 'critical' || r.severity === 'emergency' ? 'red' : r.severity === 'warning' ? 'amber' : 'blue'}">${r.severity}</span>
                            <span class="mc-badge purple">${r.report_type}</span>
                        </div>
                    </div>
                    <div class="mc-report-content">${esc(r.content ? r.content.substring(0,200) : '')}</div>
                    <div class="mc-report-time"><i class="fas fa-user-circle"></i> ${esc(r.agent_name)} · <i class="fas fa-clock"></i> ${esc(r.created_at)}</div>
                </div>
            </div>
        `).join('') + '</div>';
    } catch (e) {
        document.getElementById('reports-list').innerHTML = '<div style="color:var(--mc-red);">Error loading reports</div>';
    }
}

// ═══ Load Advisory Brief ═══
async function loadAdvisoryBrief() {
    try {
        const data = await apiCall('/api/agent-autonomy.php?action=panel_brief');
        
        // Overview brief
        const brief = document.getElementById('advisory-brief');
        if (brief && data.stats) {
            brief.innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
                    <div style="text-align:center;padding:12px;background:rgba(255,255,255,0.02);border-radius:8px;">
                        <div style="font-size:20px;font-weight:800;color:var(--mc-amber);">${data.stats.pending_proposals}</div>
                        <div style="font-size:10px;color:var(--mc-muted);text-transform:uppercase;">Awaiting Review</div>
                    </div>
                    <div style="text-align:center;padding:12px;background:rgba(255,255,255,0.02);border-radius:8px;">
                        <div style="font-size:20px;font-weight:800;color:var(--mc-green);">${data.stats.approved_today}</div>
                        <div style="font-size:10px;color:var(--mc-muted);text-transform:uppercase;">Approved Today</div>
                    </div>
                    <div style="text-align:center;padding:12px;background:rgba(255,255,255,0.02);border-radius:8px;">
                        <div style="font-size:20px;font-weight:800;color:var(--mc-cyan);">${data.stats.in_progress}</div>
                        <div style="font-size:10px;color:var(--mc-muted);text-transform:uppercase;">In Progress</div>
                    </div>
                </div>
                ${data.advisory_recommendations ? data.advisory_recommendations.map(r => {
                    const c = r.type === 'critical' ? 'red' : r.type === 'warning' ? 'amber' : 'blue';
                    return `<div style="padding:10px 14px;background:rgba(255,255,255,0.02);border-radius:8px;margin-bottom:8px;font-size:13px;border-left:3px solid var(--mc-${c});">
                        ${esc(r.message)}
                    </div>`;
                }).join('') : ''}
            `;
        }

        // Recommendations in advisory panel
        const recs = document.getElementById('advisory-recommendations');
        if (recs && data.advisory_recommendations) {
            recs.innerHTML = data.advisory_recommendations.map(r => {
                const c = r.type === 'critical' ? 'red' : r.type === 'warning' ? 'amber' : 'blue';
                return `<div style="padding:14px 18px;background:rgba(255,255,255,0.02);border-radius:10px;margin-bottom:10px;font-size:13px;line-height:1.6;border-left:3px solid var(--mc-${c});">
                    <span class="mc-badge ${c}" style="margin-right:8px;">${r.type.toUpperCase()}</span>
                    ${esc(r.message)}
                </div>`;
            }).join('');
        }
    } catch (e) {
        console.error('Advisory brief error:', e);
    }
}

// ═══ Load Agent Fleet ═══
async function loadAgentFleet() {
    const list = document.getElementById('agent-fleet-list');
    try {
        const resp = await fetch('/api/agentos/agents.php?action=list&limit=120');
        const data = await resp.json();
        if (data.agents && data.agents.length > 0) {
            list.innerHTML = `<div class="mc-card"><table class="mc-table"><thead><tr>
                <th>Agent</th><th>Role</th><th>Domain</th><th>Status</th><th>Success</th><th>Tasks</th>
            </tr></thead><tbody>` + data.agents.map(a => `<tr>
                <td style="font-weight:600;">${esc(a.name || a.agent_id)}</td>
                <td>${esc(a.role || '-')}</td>
                <td><span class="mc-badge blue">${esc(a.domain || 'general')}</span></td>
                <td><span class="mc-badge ${a.status === 'active' ? 'green' : a.status === 'idle' ? 'amber' : 'red'}">${a.status || 'unknown'}</span></td>
                <td>${a.success_rate ? a.success_rate + '%' : '-'}</td>
                <td>${a.total_tasks || 0}</td>
            </tr>`).join('') + '</tbody></table></div>';
        } else {
            list.innerHTML = '<div style="text-align:center;color:var(--mc-muted);padding:40px;">No agent data available. Agent fleet managed via Internal API.</div>';
        }
    } catch (e) {
        list.innerHTML = '<div style="text-align:center;color:var(--mc-muted);padding:40px;">Agent fleet data will populate as agents register. Currently running: ' + document.getElementById('stat-agents').textContent + ' agents.</div>';
    }
}

// ═══ Approve / Reject ═══
async function approveProposal(id) {
    if (!confirm('Approve this proposal?')) return;
    const data = await apiCall('/api/agent-autonomy.php?action=approve', 'POST', { proposal_id: id });
    if (data.success) {
        alert('Proposal #' + id + ' approved!');
        loadProposals();
        refreshStats();
    } else {
        alert('Error: ' + (data.error || 'Unknown'));
    }
}

async function rejectProposal(id) {
    const reason = prompt('Rejection reason (optional):');
    if (reason === null) return;
    const data = await apiCall('/api/agent-autonomy.php?action=reject', 'POST', { proposal_id: id, reason: reason || 'Rejected by owner' });
    if (data.success) {
        alert('Proposal #' + id + ' rejected.');
        loadProposals();
        refreshStats();
    }
}

function viewProposal(id) {
    showPanel('proposals');
    loadProposals();
}

// ═══ Refresh ═══
async function refreshStats() {
    try {
        const data = await apiCall('/api/agent-autonomy.php?action=stats');
        if (data.proposals) {
            document.getElementById('stat-pending').textContent = data.proposals.pending;
            document.getElementById('stat-reports').textContent = data.reports.unread;
            document.getElementById('stat-alerts').textContent = data.reports.critical;
        }
    } catch(e) {}
}

function refreshAll() {
    refreshStats();
    loadAdvisoryBrief();
}

// ═══ Utils ═══
function esc(str) { return GDS.esc(str); }

// ═══ Monitoring Fleet ═══
async function registerFleet() {
    if (!confirm('Register all 100 monitoring agents into the database?')) return;
    try {
        const data = await apiCall('/api/monitoring-fleet.php?action=register_fleet', 'POST');
        if (data.success) {
            alert('Fleet registered: ' + data.registered + ' agents initialized');
            loadFleetStatus();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    } catch (e) { alert('Network error: ' + e.message); }
}

async function runAllChecks() {
    if (!confirm('Run health checks across all 100 monitoring agents? This may take a moment.')) return;
    document.getElementById('monitor-fleet-list').innerHTML = '<div style="text-align:center;padding:40px;color:var(--mc-amber);"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i><br><br>Running checks across 100 agents...</div>';
    try {
        const data = await apiCall('/api/monitoring-fleet.php?action=run_checks', 'POST');
        if (data.success) {
            loadFleetStatus();
            alert(`Checks complete: ${data.summary.healthy} healthy, ${data.summary.degraded} degraded, ${data.summary.critical} critical`);
        }
    } catch (e) { alert('Error running checks: ' + e.message); }
}

async function loadFleetStatus() {
    const division = document.getElementById('monitor-filter-division')?.value || '';
    let url = '/api/monitoring-fleet.php?action=fleet_status';
    if (division) url += '&division=' + encodeURIComponent(division);
    
    try {
        const data = await apiCall(url);
        if (data.summary) {
            document.getElementById('mon-total').textContent = data.summary.total;
            document.getElementById('mon-healthy').textContent = data.summary.healthy;
            document.getElementById('mon-degraded').textContent = data.summary.degraded;
            document.getElementById('mon-critical').textContent = data.summary.critical;
            document.getElementById('mon-unknown').textContent = data.summary.unknown;
        }

        // Division breakdown
        const divGrid = document.getElementById('division-grid');
        if (data.agents && data.agents.length > 0) {
            const divisions = {};
            data.agents.forEach(a => {
                if (!divisions[a.division]) divisions[a.division] = { total: 0, healthy: 0, degraded: 0, critical: 0, unknown: 0 };
                divisions[a.division].total++;
                divisions[a.division][a.last_status]++;
            });
            const divIcons = {uptime:'fa-signal',services:'fa-server',security:'fa-shield-alt',performance:'fa-tachometer-alt',seo:'fa-search',crawler:'fa-spider',ecosystem:'fa-globe',ux:'fa-user',compliance:'fa-balance-scale',innovation:'fa-flask'};
            divGrid.innerHTML = Object.entries(divisions).map(([name, d]) => {
                const healthPct = d.total > 0 ? Math.round(d.healthy / d.total * 100) : 0;
                const barColor = healthPct >= 80 ? 'var(--mc-green)' : healthPct >= 50 ? 'var(--mc-amber)' : 'var(--mc-red)';
                return `<div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);border-radius:10px;padding:14px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <i class="fas ${divIcons[name] || 'fa-folder'}" style="color:var(--mc-cyan);"></i>
                        <span style="font-weight:600;font-size:13px;text-transform:capitalize;">${esc(name)}</span>
                        <span style="margin-left:auto;font-size:11px;color:var(--mc-muted);">${d.total} agents</span>
                    </div>
                    <div style="height:6px;background:rgba(255,255,255,0.06);border-radius:3px;overflow:hidden;margin-bottom:6px;">
                        <div style="height:100%;width:${healthPct}%;background:${barColor};border-radius:3px;transition:width 0.3s;"></div>
                    </div>
                    <div style="display:flex;gap:8px;font-size:11px;color:var(--mc-muted);">
                        <span style="color:var(--mc-green);">${d.healthy} <i class="fas fa-check"></i></span>
                        <span style="color:var(--mc-amber);">${d.degraded} <i class="fas fa-minus"></i></span>
                        <span style="color:var(--mc-red);">${d.critical} <i class="fas fa-times"></i></span>
                    </div>
                </div>`;
            }).join('');
        }

        // Agent table
        const list = document.getElementById('monitor-fleet-list');
        if (data.agents && data.agents.length > 0) {
            const statusIcon = {healthy:'fa-check-circle',degraded:'fa-exclamation-circle',critical:'fa-times-circle',unknown:'fa-question-circle'};
            const statusColor = {healthy:'var(--mc-green)',degraded:'var(--mc-amber)',critical:'var(--mc-red)',unknown:'var(--mc-muted)'};
            list.innerHTML = `<table class="mc-table"><thead><tr>
                <th>Status</th><th>Agent</th><th>Division</th><th>Domain</th><th>Response</th><th>Failures</th><th>Last Check</th>
            </tr></thead><tbody>` + data.agents.map(a => `<tr>
                <td><i class="fas ${statusIcon[a.last_status] || 'fa-question-circle'}" style="color:${statusColor[a.last_status] || 'var(--mc-muted)'};font-size:16px;"></i></td>
                <td style="font-weight:500;font-size:12px;">${esc(a.agent_name)}</td>
                <td><span class="mc-badge blue" style="text-transform:capitalize;">${esc(a.division)}</span></td>
                <td style="font-size:12px;color:var(--mc-muted);">${esc(a.domain)}</td>
                <td style="font-size:12px;">${a.last_response_ms ? a.last_response_ms + 'ms' : '—'}</td>
                <td style="font-size:12px;color:${a.consecutive_failures > 0 ? 'var(--mc-red)' : 'var(--mc-muted)'};">${a.consecutive_failures || 0}</td>
                <td style="font-size:11px;color:var(--mc-muted);">${a.last_check_at || 'Never'}</td>
            </tr>`).join('') + '</tbody></table>';
        } else {
            list.innerHTML = '<div style="text-align:center;color:var(--mc-muted);padding:40px;"><i class="fas fa-satellite" style="font-size:32px;margin-bottom:12px;display:block;"></i>No monitoring agents registered yet. Click "Register Fleet" to initialize.</div>';
        }
    } catch (e) {
        document.getElementById('monitor-fleet-list').innerHTML = '<div style="color:var(--mc-red);text-align:center;padding:40px;">Error loading fleet data: ' + esc(e.message) + '</div>';
    }
}

// ═══ Init ═══
document.addEventListener('DOMContentLoaded', () => {
    loadAdvisoryBrief();
});
