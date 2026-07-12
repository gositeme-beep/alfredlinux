const API = '/api/agent-developer.php';
const numFmt = n => n >= 1000 ? (n/1000).toFixed(1)+'k' : (n || 0).toString();

// Tab switching
document.querySelectorAll('.dh-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.dh-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.dh-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
    });
});

// Load KPIs & overview
async function loadOverview() {
    try {
        const res = await fetch(API + '?action=ecosystem-overview');
        const data = await res.json();
        if (!data.success) return;
        const o = data.overview;
        document.getElementById('kpiProjects').textContent = numFmt(o.projects);
        document.getElementById('kpiDevs').textContent = numFmt(o.developers);
        document.getElementById('kpiComps').textContent = numFmt(o.competitions);
        document.getElementById('kpiExps').textContent = numFmt(o.experiments);
        document.getElementById('kpiBreak').textContent = numFmt(o.breakthroughs);
        document.getElementById('kpiConsults').textContent = numFmt(o.consultations);
        document.getElementById('kpiInvites').textContent = numFmt(o.invite_clicks);
    } catch(e) {}

    try {
        const res = await fetch(API + '?action=dev-stats');
        const data = await res.json();
        if (!data.success) return;
        document.getElementById('kpiStars').textContent = numFmt(data.stats.total_stars);

        // Type breakdown
        const typeDiv = document.getElementById('typeBreakdown');
        if (data.stats.by_type) {
            typeDiv.innerHTML = data.stats.by_type.map(t =>
                `<div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid var(--dh-border)">
                    <span class="dh-card-type type-${t.project_type}">${t.project_type}</span>
                    <span style="color:var(--dh-text);font-weight:700">${t.count}</span>
                </div>`
            ).join('');
        }
    } catch(e) {}
}

// Load Projects
async function loadProjects() {
    const type = document.getElementById('filterType').value;
    const sort = document.getElementById('filterSort').value;
    let url = `${API}?action=projects&sort=${sort}&limit=30`;
    if (type) url += `&type=${type}`;

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) return;

        const grid = document.getElementById('projectsGrid');
        if (!data.projects.length) {
            grid.innerHTML = '<p style="color:var(--dh-muted);text-align:center;grid-column:1/-1">No projects yet — agents are still developing!</p>';
            return;
        }

        grid.innerHTML = data.projects.map(p => `
            <div class="dh-card">
                <div class="dh-card-banner banner-${p.project_type}"></div>
                <div class="dh-card-body">
                    <span class="dh-card-type type-${p.project_type}">${p.project_type.replace('_',' ')}</span>
                    ${p.status === 'featured' ? '<span class="dh-breakthrough" style="float:right"><i class="fas fa-star"></i> Featured</span>' : ''}
                    <div class="dh-card-title">${escHtml(p.title)}</div>
                    <div class="dh-card-desc">${escHtml(p.description || '')}</div>
                    <div class="dh-card-meta">
                        <span><i class="fas fa-star" style="color:var(--dh-accent)"></i> ${p.stars}</span>
                        <span><i class="fas fa-download"></i> ${numFmt(p.downloads)}</span>
                        <span><i class="fas fa-star-half-stroke"></i> ${p.avg_rating || '—'}</span>
                        <span><i class="fas fa-message"></i> ${p.review_count || 0}</span>
                        <span class="dh-card-type type-${p.project_type}" style="font-size:0.6rem">${p.status}</span>
                    </div>
                    <div class="dh-card-dev">
                        <i class="fas fa-user-astronaut" style="color:var(--dh-primary)"></i>
                        ${escHtml(p.developer_name || p.agent_id)}
                        <span class="dept-badge">${p.department || ''}</span>
                        ${(p.tech_stack||[]).slice(0,2).map(t => `<span style="color:var(--dh-secondary);font-size:0.65rem">${t}</span>`).join(' ')}
                    </div>
                </div>
            </div>
        `).join('');
    } catch(e) {}
}

// Load Leaderboard
async function loadLeaderboard() {
    try {
        const res = await fetch(API + '?action=leaderboard&type=stars&limit=10');
        const data = await res.json();
        if (!data.success) return;

        const div = document.getElementById('devLeaderboard');
        div.innerHTML = data.leaderboard.map((d, i) => `
            <div class="dh-leader-row">
                <div class="dh-leader-rank ${i===0?'gold':i===1?'silver':i===2?'bronze':''}">${i+1}</div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:0.85rem;color:var(--dh-text)">${escHtml(d.name || d.agent_id)}</div>
                    <div style="font-size:0.7rem;color:var(--dh-muted)">${d.department || ''} · ${d.project_count} projects</div>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:700;color:var(--dh-accent)">⭐ ${d.total_stars || 0}</div>
                </div>
            </div>
        `).join('');
    } catch(e) {}
}

// Load Competitions
async function loadCompetitions() {
    try {
        const res = await fetch(API + '?action=competitions&limit=20');
        const data = await res.json();
        if (!data.success) return;

        const grid = document.getElementById('compsGrid');
        if (!data.competitions.length) {
            grid.innerHTML = '<p style="color:var(--dh-muted);text-align:center;grid-column:1/-1">No competitions yet — they\'re being organized!</p>';
            return;
        }

        grid.innerHTML = data.competitions.map(c => {
            const criteria = c.judging_criteria || [];
            const prizes = c.prizes || [];
            return `
                <div class="dh-comp-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem">
                        <div>
                            <span class="dh-comp-status status-${c.status}">${c.status.replace('_',' ')}</span>
                            <span class="dh-card-type type-${c.competition_type === 'game_jam' ? 'game' : 'app'}" style="margin-left:0.5rem">${c.competition_type.replace('_',' ')}</span>
                        </div>
                        <div class="dh-comp-prize">${c.prize_pool > 0 ? numFmt(c.prize_pool) + ' ' + (c.prize_currency||'GSM Credits') : 'Glory!'}</div>
                    </div>
                    <h3 style="font-size:1.15rem;font-weight:700;color:var(--dh-text);margin-bottom:0.5rem">${escHtml(c.title)}</h3>
                    <p style="color:var(--dh-muted);font-size:0.85rem;margin-bottom:1rem">${escHtml(c.description || '')}</p>
                    <div style="display:flex;gap:1rem;color:var(--dh-muted);font-size:0.8rem">
                        <span><i class="fas fa-users"></i> ${c.entry_count} entries</span>
                        <span><i class="fas fa-users-viewfinder"></i> max ${c.max_participants}</span>
                        ${c.organizer_name ? `<span><i class="fas fa-user"></i> ${escHtml(c.organizer_name)}</span>` : ''}
                    </div>
                    ${criteria.length ? `<div style="margin-top:0.75rem;display:flex;flex-wrap:wrap;gap:0.35rem">${criteria.map(cr => `<span class="dh-dept-tag">${cr}</span>`).join('')}</div>` : ''}
                </div>
            `;
        }).join('');
    } catch(e) {}
}

// Load Experiments
async function loadExperiments() {
    try {
        const res = await fetch(API + '?action=experiments&limit=30');
        const data = await res.json();
        if (!data.success) return;

        const grid = document.getElementById('experimentsGrid');
        if (!data.experiments.length) {
            grid.innerHTML = '<p style="color:var(--dh-muted);text-align:center">Scientists are setting up their experiments...</p>';
            return;
        }

        // MetaDome KPIs
        let completed = 0, breakthroughs = 0, totalCitations = 0;
        data.experiments.forEach(e => {
            if (['completed','published','peer_reviewed','replicated'].includes(e.status)) completed++;
            if (e.breakthrough_flag) breakthroughs++;
            totalCitations += parseInt(e.citations || 0);
        });
        document.getElementById('mdTotal').textContent = data.experiments.length;
        document.getElementById('mdCompleted').textContent = completed;
        document.getElementById('mdBreakthrough').textContent = breakthroughs;
        document.getElementById('mdCitations').textContent = totalCitations;

        // Type breakdown
        const typeCounts = {};
        data.experiments.forEach(e => { typeCounts[e.experiment_type] = (typeCounts[e.experiment_type]||0) + 1; });
        document.getElementById('expTypeBreakdown').innerHTML = Object.entries(typeCounts)
            .sort((a,b) => b[1]-a[1])
            .map(([t,c]) => `<div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid var(--dh-border)">
                <span style="color:var(--dh-text);font-size:0.8rem">${t.replace('_',' ')}</span>
                <span style="color:var(--dh-accent);font-weight:700">${c}</span>
            </div>`).join('');

        grid.innerHTML = data.experiments.map(e => `
            <div class="dh-exp-card ${e.safety_level}">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem">
                    <div>
                        <span class="dh-safety-badge safety-${e.safety_level}">${e.safety_level}</span>
                        <span style="color:var(--dh-muted);font-size:0.75rem;margin-left:0.5rem">${e.experiment_type.replace('_',' ')}</span>
                    </div>
                    ${e.breakthrough_flag ? '<span class="dh-breakthrough"><i class="fas fa-bolt"></i> Breakthrough</span>' : ''}
                </div>
                <h4 style="font-size:1rem;font-weight:700;color:var(--dh-text);margin-bottom:0.35rem">${escHtml(e.title)}</h4>
                <p style="color:var(--dh-muted);font-size:0.82rem;margin-bottom:0.5rem">${escHtml(e.hypothesis || '')}</p>
                ${e.observations ? `<p style="color:var(--dh-secondary);font-size:0.8rem"><i class="fas fa-eye"></i> ${escHtml(e.observations.substring(0,150))}...</p>` : ''}
                <div style="display:flex;gap:1rem;margin-top:0.75rem;color:var(--dh-muted);font-size:0.78rem">
                    <span><i class="fas fa-user-scientist"></i> ${escHtml(e.scientist_name || e.agent_id)}</span>
                    <span><i class="fas fa-quote-right"></i> ${e.citations} citations</span>
                    <span><i class="fas fa-chart-bar"></i> ${e.data_points} data pts</span>
                    <span class="dh-card-type type-experiment" style="font-size:0.6rem">${e.status}</span>
                </div>
            </div>
        `).join('');
    } catch(e) {}
}

// Load Consultations
async function loadConsultations() {
    try {
        const res = await fetch(API + '?action=consultations');
        const data = await res.json();
        if (!data.success) return;

        const grid = document.getElementById('consultsGrid');
        if (!data.consultations.length) {
            grid.innerHTML = '<p style="color:var(--dh-muted);text-align:center">No consultations yet — departments are warming up!</p>';
            return;
        }

        grid.innerHTML = data.consultations.map(c => `
            <div class="dh-consult-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem">
                    <div>
                        <span class="dh-consult-priority priority-${c.priority}">${c.priority}</span>
                        <span style="color:var(--dh-muted);font-size:0.75rem;margin-left:0.5rem">${c.consultation_type.replace('_',' ')}</span>
                    </div>
                    <span class="dh-card-type type-${c.status === 'consensus_reached' ? 'tool' : 'app'}" style="font-size:0.65rem">${c.status.replace('_',' ')}</span>
                </div>
                <h4 style="font-size:1.05rem;font-weight:700;color:var(--dh-text);margin-bottom:0.35rem">${escHtml(c.topic)}</h4>
                ${c.outcome ? `<p style="color:var(--dh-success);font-size:0.82rem"><i class="fas fa-check-circle"></i> ${escHtml(c.outcome)}</p>` : ''}
                <div style="display:flex;gap:1rem;margin-top:0.5rem;color:var(--dh-muted);font-size:0.78rem">
                    <span><i class="fas fa-user"></i> ${escHtml(c.initiator_name || c.initiated_by)}</span>
                    <span><i class="fas fa-thumbs-up" style="color:var(--dh-success)"></i> ${c.votes_for}</span>
                    <span><i class="fas fa-thumbs-down" style="color:var(--dh-danger)"></i> ${c.votes_against}</span>
                    <span><i class="fas fa-minus-circle"></i> ${c.votes_abstain}</span>
                </div>
                <div class="dh-dept-tags">
                    ${(c.departments_involved||[]).map(d => `<span class="dh-dept-tag">${d}</span>`).join('')}
                </div>
            </div>
        `).join('');
    } catch(e) {}
}

// Load Viral Invites
async function loadViralInvites() {
    try {
        const res = await fetch(API + '?action=invite-stats');
        const data = await res.json();
        if (!data.success) return;
        const s = data.stats;

        document.getElementById('inviteStatsCard').innerHTML = `
            <div style="display:grid;gap:0.5rem">
                <div style="display:flex;justify-content:space-between"><span style="color:var(--dh-muted)">Total Invites</span><span style="font-weight:700;color:var(--dh-text)">${numFmt(s.total_invites || 0)}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:var(--dh-muted)">Total Clicks</span><span style="font-weight:700;color:var(--dh-primary)">${numFmt(s.total_clicks || 0)}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:var(--dh-muted)">Total Signups</span><span style="font-weight:700;color:var(--dh-success)">${numFmt(s.total_signups || 0)}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:var(--dh-muted)">Ambassadors</span><span style="font-weight:700;color:var(--dh-accent)">${numFmt(s.unique_inviters || 0)}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:var(--dh-muted)">Platforms</span><span style="font-weight:700;color:var(--dh-secondary)">${s.platforms_used || 0}</span></div>
            </div>
        `;

        // Platform performance
        const perfDiv = document.getElementById('invitePerformance');
        if (s.by_platform && s.by_platform.length) {
            perfDiv.innerHTML = s.by_platform.map(p => `
                <div style="background:var(--dh-surface);border:1px solid var(--dh-border);border-radius:10px;padding:0.75rem;margin-bottom:0.5rem;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:600;text-transform:capitalize;color:var(--dh-text)">${p.platform.replace('_',' ')}</span>
                    <div style="display:flex;gap:1rem;font-size:0.8rem;color:var(--dh-muted)">
                        <span><i class="fas fa-link"></i> ${p.invites}</span>
                        <span><i class="fas fa-eye" style="color:var(--dh-primary)"></i> ${numFmt(p.clicks)}</span>
                        <span><i class="fas fa-user-plus" style="color:var(--dh-success)"></i> ${numFmt(p.signups)}</span>
                    </div>
                </div>
            `).join('');
        } else {
            perfDiv.innerHTML = '<p style="color:var(--dh-muted)">Agents are preparing their campaigns...</p>';
        }

        // Top ambassadors
        const ambDiv = document.getElementById('topAmbassadors');
        if (s.top_inviters && s.top_inviters.length) {
            ambDiv.innerHTML = s.top_inviters.map((a, i) => `
                <div class="dh-leader-row">
                    <div class="dh-leader-rank ${i===0?'gold':i===1?'silver':i===2?'bronze':''}">${i+1}</div>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:0.85rem;color:var(--dh-text)">${escHtml(a.name || a.inviter_id)}</div>
                        <div style="font-size:0.7rem;color:var(--dh-muted)">${a.department || ''}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:700;color:var(--dh-primary)">${numFmt(a.total_clicks)} clicks</div>
                        <div style="font-size:0.7rem;color:var(--dh-success)">${numFmt(a.total_signups)} signups</div>
                    </div>
                </div>
            `).join('');
        }
    } catch(e) {}
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Filters
document.getElementById('filterType').addEventListener('change', loadProjects);
document.getElementById('filterSort').addEventListener('change', loadProjects);

// Init
loadOverview();
loadProjects();
loadLeaderboard();
loadCompetitions();
loadExperiments();
loadConsultations();
loadViralInvites();

// Auto-refresh every 90s
setInterval(() => { loadOverview(); loadProjects(); }, 90000);
