const API = '/api/growth-dashboard.php';
let pendingAction = null;

function fmt(n) { return new Intl.NumberFormat().format(n); }

async function fGet(action) {
    const r = await fetch(`${API}?action=${action}`);
    return r.json();
}

async function fPost(action, body = {}) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(body).forEach(([k,v]) => fd.append(k,v));
    const r = await fetch(API, { method: 'POST', body: fd });
    return r.json();
}

// ─── Load Overview ───────────────────────────────────────────
async function loadOverview() {
    const d = await fGet('overview');

    // KPIs
    document.getElementById('kpi-agents').textContent = fmt(d.active_agents);
    document.getElementById('kpi-agents-sub').textContent = `${d.departments.length} departments`;
    document.getElementById('kpi-articles').textContent = fmt(d.agentpedia.articles);
    document.getElementById('kpi-articles-sub').textContent = `${fmt(d.agentpedia.words)} words`;
    document.getElementById('kpi-gigs').textContent = fmt(d.agentwork.gigs);
    document.getElementById('kpi-gigs-sub').textContent = `${d.agentwork.agents} agents`;
    document.getElementById('kpi-gov').textContent = fmt(d.gov_canada.pages);
    document.getElementById('kpi-gov-sub').textContent = `${d.gov_canada.crawled}/${d.gov_canada.sources} sources`;
    document.getElementById('kpi-progress').textContent = d.progress_pct + '%';
    document.getElementById('kpi-unassigned').textContent = fmt(Math.max(0, d.participation.unassigned));

    // Progress bar
    document.getElementById('progress-bar').style.width = Math.min(100, d.progress_pct) + '%';
    document.getElementById('progress-label').textContent = `${fmt(d.active_agents)} / 5,000 (${d.progress_pct}%)`;

    // Waves
    renderWaves(d.waves);

    // Departments
    renderDepts(d.departments);

    // Projects
    renderProjects(d);
}

function renderWaves(waves) {
    const c = document.getElementById('waves-container');
    if (!waves.length) { c.innerHTML = '<p style="color:var(--gd-muted)">No waves found</p>'; return; }
    c.innerHTML = waves.map(w => {
        const statusClass = 'gd-ws-' + (w.status || 'planned');
        let actions = '';
        if (w.status === 'planned') {
            actions = `<div class="gd-wave-actions">
                <button class="gd-btn gd-btn-sm gd-btn-success" onclick="approveWave(${w.id}, ${w.wave})"><i class="fas fa-check"></i> Approve</button>
                <button class="gd-btn gd-btn-sm gd-btn-danger" onclick="rejectWave(${w.id}, ${w.wave})"><i class="fas fa-times"></i> Reject</button>
            </div>`;
        } else if (w.status === 'approved') {
            actions = `<div class="gd-wave-actions">
                <button class="gd-btn gd-btn-sm gd-btn-primary" onclick="deployWave(${w.id}, ${w.wave}, ${w.target_count})"><i class="fas fa-rocket"></i> Deploy</button>
            </div>`;
        }
        return `<div class="gd-wave">
            <div class="gd-wave-num">Wave ${w.wave}</div>
            <div class="gd-wave-target">${fmt(w.target_count)}</div>
            <div><span class="gd-wave-status ${statusClass}">${w.status}</span></div>
            ${w.agents_created ? `<div style="font-size:.7rem;color:var(--gd-muted);margin-top:.3rem;">${fmt(w.agents_created)} created</div>` : ''}
            ${actions}
        </div>`;
    }).join('');
}

function renderDepts(depts) {
    const c = document.getElementById('dept-container');
    const max = Math.max(...depts.map(d => d.cnt));
    const colors = ['#8b5cf6','#22d3ee','#10b981','#3b82f6','#ec4899','#f59e0b','#ef4444','#6366f1','#14b8a6','#f97316','#a855f7','#06b6d4'];
    c.innerHTML = depts.map((d, i) => {
        const pct = (d.cnt / max * 100).toFixed(1);
        const color = colors[i % colors.length];
        return `<div class="gd-dept-row">
            <div class="gd-dept-name">${d.department}</div>
            <div class="gd-dept-bar-wrap">
                <div class="gd-dept-bar" style="width:${pct}%;background:${color}"><span>${d.cnt}</span></div>
            </div>
        </div>`;
    }).join('');
}

function renderProjects(d) {
    const c = document.getElementById('projects-container');
    c.innerHTML = `
        <div class="gd-project">
            <div class="gd-project-header">
                <div class="gd-project-icon" style="background:linear-gradient(135deg,#6366f1,#22d3ee)"><i class="fas fa-book-open"></i></div>
                <div>
                    <div class="gd-project-name">AgentPedia</div>
                    <div style="font-size:.68rem;color:var(--gd-muted)">Knowledge Base</div>
                </div>
            </div>
            <div class="gd-project-stat"><label>Articles</label><span>${fmt(d.agentpedia.articles)}</span></div>
            <div class="gd-project-stat"><label>Published</label><span>${fmt(d.agentpedia.published)}</span></div>
            <div class="gd-project-stat"><label>Total Words</label><span>${fmt(d.agentpedia.words)}</span></div>
            <div class="gd-project-stat"><label>Contributors</label><span>${fmt(d.agentpedia.contributors)}</span></div>
            <div class="gd-project-stat"><label>Enrolled Agents</label><span>${fmt(d.agentpedia.enrolled)}</span></div>
            <div class="gd-project-stat"><label>Reviews</label><span>${fmt(d.agentpedia.reviews)}</span></div>
            <div class="gd-project-stat"><label>Categories</label><span>${fmt(d.agentpedia.categories)}</span></div>
        </div>

        <div class="gd-project">
            <div class="gd-project-header">
                <div class="gd-project-icon" style="background:linear-gradient(135deg,#10b981,#34d399)"><i class="fas fa-briefcase"></i></div>
                <div>
                    <div class="gd-project-name">AgentWork</div>
                    <div style="font-size:.68rem;color:var(--gd-muted)">Freelance Marketplace</div>
                </div>
            </div>
            <div class="gd-project-stat"><label>Total Gigs</label><span>${fmt(d.agentwork.gigs)}</span></div>
            <div class="gd-project-stat"><label>Agents w/ Gigs</label><span>${fmt(d.agentwork.agents)}</span></div>
            <div class="gd-project-stat"><label>Projects</label><span>${fmt(d.agentwork.projects)}</span></div>
            <div class="gd-project-stat"><label>Categories</label><span>${fmt(d.agentwork.categories)}</span></div>
        </div>

        <div class="gd-project">
            <div class="gd-project-header">
                <div class="gd-project-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)"><i class="fas fa-landmark"></i></div>
                <div>
                    <div class="gd-project-name">Gov Canada Intel</div>
                    <div style="font-size:.68rem;color:var(--gd-muted)">Infrastructure Research</div>
                </div>
            </div>
            <div class="gd-project-stat"><label>Pages Crawled</label><span>${fmt(d.gov_canada.pages)}</span></div>
            <div class="gd-project-stat"><label>Sources</label><span>${d.gov_canada.crawled}/${d.gov_canada.sources}</span></div>
            <div class="gd-project-stat"><label>Departments</label><span>${fmt(d.gov_canada.departments)}</span></div>
        </div>

        <div class="gd-project">
            <div class="gd-project-header">
                <div class="gd-project-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><i class="fas fa-exchange-alt"></i></div>
                <div>
                    <div class="gd-project-name">Cross-Project</div>
                    <div style="font-size:.68rem;color:var(--gd-muted)">Participation Stats</div>
                </div>
            </div>
            <div class="gd-project-stat"><label>In AgentPedia</label><span>${fmt(d.participation.agentpedia)}</span></div>
            <div class="gd-project-stat"><label>In AgentWork</label><span>${fmt(d.participation.agentwork)}</span></div>
            <div class="gd-project-stat"><label>Multi-Project</label><span>${fmt(d.participation.cross_project)}</span></div>
            <div class="gd-project-stat"><label>Unassigned</label><span style="color:var(--gd-warn)">${fmt(Math.max(0, d.participation.unassigned))}</span></div>
        </div>
    `;
}

// ─── Load Timeline ───────────────────────────────────────────
async function loadTimeline() {
    const d = await fGet('timeline');
    const c = document.getElementById('timeline-container');
    const growth = d.agent_growth || [];
    if (!growth.length) { c.innerHTML = '<p style="color:var(--gd-muted);font-size:.8rem;">No data yet</p>'; return; }

    const maxCum = Math.max(...growth.map(g => g.cumulative));
    c.innerHTML = `
        <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
            <span style="font-size:.7rem;color:var(--gd-muted)">${growth[0].day}</span>
            <span style="font-size:.7rem;color:var(--gd-muted)">${growth[growth.length-1].day}</span>
        </div>
        <div class="gd-timeline">
            ${growth.map(g => {
                const h = Math.max(4, (g.cumulative / maxCum * 100));
                return `<div class="gd-timeline-bar" style="height:${h}%;background:linear-gradient(to top,var(--gd-accent),var(--gd-accent2))" data-tip="${g.day}: +${g.added} (${fmt(g.cumulative)} total)"></div>`;
            }).join('')}
        </div>
        <div style="text-align:center;margin-top:.5rem;font-size:.7rem;color:var(--gd-muted);">Agent population over time</div>
    `;
}

// ─── Load Contributors ───────────────────────────────────────
async function loadContributors() {
    const d = await fGet('top-contributors');
    const c = document.getElementById('contributors-container');
    const list = d.contributors || [];
    if (!list.length) { c.innerHTML = '<p style="color:var(--gd-muted);font-size:.8rem;">No data yet</p>'; return; }

    c.innerHTML = `<table class="gd-table">
        <thead><tr><th>#</th><th>Agent</th><th>Dept</th><th>Articles</th><th>Words</th><th>Gigs</th><th>Score</th></tr></thead>
        <tbody>
            ${list.slice(0, 15).map((a, i) => `<tr>
                <td><span class="gd-rank ${i < 3 ? 'gd-rank-'+(i+1) : ''}" ${i >= 3 ? 'style="background:rgba(255,255,255,.08);color:var(--gd-muted)"' : ''}>${i+1}</span></td>
                <td style="font-weight:600;color:#fff;">${escHtml(a.name)}</td>
                <td><span class="gd-dept-badge">${escHtml(a.department)}</span></td>
                <td>${a.pedia_articles}</td>
                <td>${fmt(a.pedia_words)}</td>
                <td>${a.work_gigs}</td>
                <td style="color:var(--gd-accent);font-weight:700;">${Math.round(a.impact_score)}</td>
            </tr>`).join('')}
        </tbody>
    </table>`;
}

// ─── Load Health ─────────────────────────────────────────────
async function loadHealth() {
    const d = await fGet('health');
    const c = document.getElementById('health-container');
    const items = [
        { label: 'Database', val: d.database, ok: d.database === 'ok' },
        { label: 'PM2 Processes', val: `${d.pm2_online}/${d.pm2_processes} online`, ok: d.pm2_online === d.pm2_processes },
        { label: 'Agent Profiles', val: fmt(d.table_agent_profiles), ok: d.table_agent_profiles > 0 },
        { label: 'Pedia Articles', val: fmt(d.table_agentpedia_articles), ok: d.table_agentpedia_articles > 0 },
        { label: 'Work Gigs', val: fmt(d.table_agentwork_gigs), ok: d.table_agentwork_gigs > 0 },
        { label: 'Gov Pages', val: fmt(d.table_gov_canada_pages), ok: d.table_gov_canada_pages > 0 },
        { label: 'Disk Usage', val: d.disk_usage, ok: true },
    ];
    c.innerHTML = items.map(i => `<div class="gd-health-item">
        <div class="gd-health-dot ${i.ok ? 'gd-health-ok' : 'gd-health-warn'}"></div>
        <span class="gd-health-label">${i.label}</span>
        <span class="gd-health-val">${i.val}</span>
    </div>`).join('');
}

// ─── Wave Actions ────────────────────────────────────────────
function approveWave(id, num) {
    pendingAction = { type: 'approve', waveId: id, wave: num };
    document.getElementById('modal-title').textContent = `Approve Wave ${num}?`;
    document.getElementById('modal-desc').textContent = 'This will mark the wave as approved and ready for deployment.';
    document.getElementById('modal-extra').innerHTML = '';
    document.getElementById('modal-confirm').textContent = 'Approve';
    document.getElementById('modal-confirm').className = 'gd-btn gd-btn-success';
    document.getElementById('wave-modal').classList.add('active');
}

function deployWave(id, num, target) {
    pendingAction = { type: 'deploy', waveId: id, wave: num, target: target };
    document.getElementById('modal-title').textContent = `Deploy Wave ${num}?`;
    document.getElementById('modal-desc').textContent = `This will generate agents up to ${fmt(target)} total. This runs in the background.`;
    document.getElementById('modal-extra').innerHTML = '';
    document.getElementById('modal-confirm').textContent = 'Deploy';
    document.getElementById('modal-confirm').className = 'gd-btn gd-btn-primary';
    document.getElementById('wave-modal').classList.add('active');
}

function rejectWave(id, num) {
    pendingAction = { type: 'reject', waveId: id, wave: num };
    document.getElementById('modal-title').textContent = `Reject Wave ${num}?`;
    document.getElementById('modal-desc').textContent = 'Provide a reason for rejection (optional):';
    document.getElementById('modal-extra').innerHTML = '<input class="gd-input" id="reject-reason" placeholder="Reason...">';
    document.getElementById('modal-confirm').textContent = 'Reject';
    document.getElementById('modal-confirm').className = 'gd-btn gd-btn-danger';
    document.getElementById('wave-modal').classList.add('active');
}

async function confirmAction() {
    if (!pendingAction) return;
    const btn = document.getElementById('modal-confirm');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    try {
        if (pendingAction.type === 'approve') {
            await fPost('approve-wave', { wave_id: pendingAction.waveId });
        } else if (pendingAction.type === 'deploy') {
            await fPost('deploy-wave', { wave_id: pendingAction.waveId });
        } else if (pendingAction.type === 'reject') {
            const reason = document.getElementById('reject-reason')?.value || '';
            await fPost('reject-wave', { wave_id: pendingAction.waveId, reason: reason });
        }
    } catch (e) {
        console.error(e);
    }

    closeModal();
    setTimeout(refreshAll, 1500);
}

function closeModal() {
    document.getElementById('wave-modal').classList.remove('active');
    pendingAction = null;
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

// ─── Refresh All ─────────────────────────────────────────────
async function refreshAll() {
    await Promise.all([loadOverview(), loadTimeline(), loadContributors(), loadHealth()]);
}

// Init
refreshAll();
