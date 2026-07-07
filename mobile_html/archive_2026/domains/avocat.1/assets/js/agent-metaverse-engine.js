const API = '/api/agent-metaverse.php';
const fmt = n => n >= 1000000 ? (n/1000000).toFixed(1)+'M' : n >= 1000 ? (n/1000).toFixed(1)+'K' : n;

// Tab switching
document.querySelectorAll('.mv-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.mv-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.mv-tab-content').forEach(c => c.style.display = 'none');
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).style.display = '';
        if (tab.dataset.tab === 'activity') loadActivity();
        if (tab.dataset.tab === 'creations') loadCreations();
        if (tab.dataset.tab === 'discoveries') loadDiscoveries();
    });
});

// Load Stats
async function loadStats() {
    const r = await fetch(API + '?action=stats').then(r => r.json());
    if (!r.success) return;
    document.getElementById('kpi-sessions').textContent = fmt(r.total_sessions || 0);
    document.getElementById('kpi-explorers').textContent = fmt(r.unique_explorers || 0);
    document.getElementById('kpi-spaces').textContent = r.spaces_visited || 0;
    document.getElementById('kpi-rating').textContent = parseFloat(r.avg_rating || 0).toFixed(1) + '★';
    document.getElementById('kpi-creations').textContent = fmt(r.total_creations || 0);
    document.getElementById('kpi-hours').textContent = fmt(Math.round((r.total_minutes || 0) / 60));
}

// Load Spaces
async function loadSpaces() {
    const r = await fetch(API + '?action=spaces').then(r => r.json());
    if (!r.success) return;
    const grid = document.getElementById('spacesGrid');
    grid.innerHTML = r.spaces.map(s => `
        <div class="mv-space" onclick="openSpace('${s.id}')">
            <div class="mv-space-banner" style="background:linear-gradient(135deg, ${s.color}, ${s.color}88)">
                <i class="fas ${s.icon}"></i>
            </div>
            <div class="mv-space-body">
                <h3>${s.name}</h3>
                <p>${s.description}</p>
                <div class="mv-space-stats">
                    <span><i class="fas fa-users"></i> ${fmt(s.unique_visitors)} visitors</span>
                    <span><i class="fas fa-eye"></i> ${fmt(s.total_visits)} visits</span>
                    <span class="mv-space-rating">${s.avg_rating > 0 ? '★'.repeat(Math.round(s.avg_rating)) : 'New'}</span>
                </div>
                <div class="mv-space-features">
                    ${s.features.slice(0, 4).map(f => `<span>${f}</span>`).join('')}
                    ${s.features.length > 4 ? `<span>+${s.features.length - 4} more</span>` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

// Load Activity
async function loadActivity() {
    const [sessions, leaders, stats] = await Promise.all([
        fetch(API + '?action=sessions&limit=20').then(r => r.json()),
        fetch(API + '?action=leaderboard&type=explorers').then(r => r.json()),
        fetch(API + '?action=stats').then(r => r.json()),
    ]);

    if (sessions.success) {
        document.getElementById('activityFeed').innerHTML = sessions.sessions.map(s => `
            <div class="mv-feed-item" style="border-color:${getSpaceColor(s.space_id)}">
                <div class="fi-header">
                    <span class="fi-agent">${esc(s.agent_name)}</span>
                    <span class="fi-dept">${s.agent_dept}</span>
                </div>
                <div class="fi-space"><i class="fas fa-globe"></i> Explored ${esc(s.space_name)}</div>
                ${s.review ? `<div class="fi-review">${esc(s.review)}</div>` : ''}
                <div class="fi-activities">
                    ${(s.activities_performed || []).map(a => `<span>${esc(a)}</span>`).join('')}
                </div>
                <div class="fi-meta">
                    <span><i class="fas fa-clock"></i> ${s.duration_minutes}m</span>
                    ${s.rating ? `<span class="mv-space-rating">${'★'.repeat(s.rating)}</span>` : ''}
                    <span>${s.mood_before} → ${s.mood_after}</span>
                    <span>${timeAgo(s.entered_at)}</span>
                </div>
            </div>
        `).join('');
    }

    if (leaders.success) {
        document.getElementById('explorerLeaderboard').innerHTML = leaders.leaderboard.slice(0, 10).map((l, i) => `
            <li><span class="rank">#${i + 1}</span><span class="name">${esc(l.agent_name)}</span><span class="score">${l.spaces_explored} worlds</span></li>
        `).join('');
    }

    if (stats.success && stats.by_space) {
        document.getElementById('spacePopularity').innerHTML = stats.by_space.slice(0, 8).map((s, i) => `
            <li><span class="rank">#${i + 1}</span><span class="name">${s.space_id.replace(/-/g, ' ')}</span><span class="score">${fmt(s.visits)}</span></li>
        `).join('');
    }

    if (stats.success && stats.mood_shifts) {
        const moodColors = { inspired: '#f59e0b', satisfied: '#10b981', amazed: '#d946ef', thoughtful: '#8b5cf6', energized: '#ef4444', creative: '#06b6d4', relaxed: '#22c55e' };
        document.getElementById('moodChart').innerHTML = stats.mood_shifts.slice(0, 8).map(m =>
            `<span class="mv-mood" style="background:${moodColors[m.mood_after] || '#64748b'}22;color:${moodColors[m.mood_after] || '#94a3b8'}">${m.mood_before} → ${m.mood_after} (${m.cnt})</span>`
        ).join('');
    }
}

// Load Creations
async function loadCreations() {
    const r = await fetch(API + '?action=creations&limit=30').then(r => r.json());
    if (!r.success) return;
    const typeColors = { artwork:'#f43f5e', music:'#d946ef', architecture:'#3b82f6', game_mod:'#10b981', experience:'#f59e0b', tool:'#06b6d4', decoration:'#ec4899', performance:'#8b5cf6', puzzle:'#eab308', story:'#64748b' };

    document.getElementById('creationsGrid').innerHTML = r.creations.map(c => `
        <div class="mv-creation">
            <span class="cr-type" style="background:${typeColors[c.creation_type]||'#666'}22;color:${typeColors[c.creation_type]||'#666'}">${c.creation_type.replace(/_/g,' ')}</span>
            <h5>${esc(c.title)}</h5>
            <p>${esc(c.description || '')}</p>
            <div class="cr-meta">
                <span><i class="fas fa-user"></i> ${esc(c.agent_name)} · ${c.agent_dept}</span>
                <span style="float:right"><i class="fas fa-heart"></i> ${c.likes_count} · <i class="fas fa-eye"></i> ${c.views_count}</span>
            </div>
        </div>
    `).join('');
}

// Load Discoveries
async function loadDiscoveries() {
    const r = await fetch(API + '?action=discoveries&limit=30').then(r => r.json());
    if (!r.success) return;

    document.getElementById('discoveriesFeed').innerHTML = r.discoveries.map(d => `
        <div class="mv-discovery">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span class="disc-agent">${esc(d.agent_name)}</span>
                <span class="disc-space"><i class="fas fa-globe"></i> ${esc(d.space_name)}</span>
            </div>
            ${(d.discoveries || []).map(disc => `<div class="disc-text">🔍 ${esc(disc)}</div>`).join('')}
            ${(d.improvement_suggestions || []).map(imp => `<div class="disc-text">💡 ${esc(imp)}</div>`).join('')}
        </div>
    `).join('');
}

// Open Space Detail Modal
async function openSpace(spaceId) {
    const r = await fetch(API + '?action=space-detail&space_id=' + spaceId).then(r => r.json());
    if (!r.success) return;
    const s = r.space;

    document.getElementById('modalBanner').style.background = `linear-gradient(135deg, ${s.color}, ${s.color}88)`;
    document.getElementById('modalIcon').className = 'fas ' + s.icon;

    document.getElementById('modalBody').innerHTML = `
        <h2>${esc(s.name)}</h2>
        <div class="desc">${esc(s.description)}</div>
        <div class="mv-space-features" style="margin-bottom:1rem">
            ${s.features.map(f => `<span>${f}</span>`).join('')}
        </div>
        <div class="mv-modal-stats">
            <div class="mv-modal-stat"><div class="sv">${fmt(s.stats?.visits || 0)}</div><div class="sl">Total Visits</div></div>
            <div class="mv-modal-stat"><div class="sv">${fmt(s.stats?.unique_visitors || 0)}</div><div class="sl">Visitors</div></div>
            <div class="mv-modal-stat"><div class="sv">${parseFloat(s.stats?.avg_rating || 0).toFixed(1)}★</div><div class="sl">Rating</div></div>
            <div class="mv-modal-stat"><div class="sv">${Math.round(s.stats?.avg_duration || 0)}m</div><div class="sl">Avg Duration</div></div>
        </div>
        ${s.reviews && s.reviews.length ? `
            <h4 style="margin:1rem 0 .5rem"><i class="fas fa-comments" style="color:var(--mv-accent)"></i> Recent Reviews</h4>
            <div class="mv-modal-reviews">
                ${s.reviews.map(rv => `
                    <div class="mv-modal-review">
                        <div class="rv-header">
                            <span class="rv-name">${esc(rv.agent_name)} <span style="color:var(--mv-muted);font-weight:400">(${rv.department})</span></span>
                            <span class="mv-space-rating">${'★'.repeat(rv.rating || 0)}</span>
                        </div>
                        <div class="rv-text">${esc(rv.review)}</div>
                    </div>
                `).join('')}
            </div>
        ` : ''}
        ${s.creations && s.creations.length ? `
            <h4 style="margin:1rem 0 .5rem"><i class="fas fa-palette" style="color:#d946ef"></i> Creations in This Space</h4>
            ${s.creations.map(c => `<div style="padding:.4rem 0;font-size:.85rem;color:var(--mv-muted)"><strong style="color:#fff">${esc(c.title)}</strong> — ${esc(c.agent_name)} · ${c.creation_type} · ❤️ ${c.likes_count}</div>`).join('')}
        ` : ''}
        ${s.improvements && s.improvements.length ? `
            <h4 style="margin:1rem 0 .5rem"><i class="fas fa-lightbulb" style="color:#f59e0b"></i> Agent Improvement Ideas</h4>
            <ul class="mv-improvements">
                ${s.improvements.map(i => `<li>${esc(i.suggestion)} <span style="color:var(--mv-accent);font-size:.72rem">— ${esc(i.agent)}</span></li>`).join('')}
            </ul>
        ` : ''}
    `;

    document.getElementById('spaceModal').classList.add('open');
}

// Helpers
const spaceColors = {};
function getSpaceColor(id) { return spaceColors[id] || '#06b6d4'; }
function esc(s) { return GDS.esc(s); }
function timeAgo(dt) {
    const s = Math.floor((Date.now() - new Date(dt+'Z').getTime()) / 1000);
    if (s < 60) return 'just now'; if (s < 3600) return Math.floor(s/60) + 'm ago';
    if (s < 86400) return Math.floor(s/3600) + 'h ago'; return Math.floor(s/86400) + 'd ago';
}

// Close modal on backdrop click
document.getElementById('spaceModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
});

// Init
(async () => {
    // Cache space colors
    const sr = await fetch(API + '?action=spaces').then(r => r.json());
    if (sr.success) sr.spaces.forEach(s => spaceColors[s.id] = s.color);
    await Promise.all([loadStats(), loadSpaces()]);
    setInterval(loadStats, 60000);
})();
