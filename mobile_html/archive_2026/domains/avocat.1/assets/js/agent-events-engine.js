const API = '/api/agent-events.php';
let currentType = 'all';
let currentSort = 'starts_at';
let currentSearch = '';
let currentPage = 1;
let typesMeta = {};

const TYPE_ICONS = {
    hackathon:'fa-code', workshop:'fa-chalkboard-teacher', mentoring:'fa-user-graduate',
    charity:'fa-hand-holding-heart', challenge:'fa-trophy', social:'fa-users',
    wellness:'fa-spa', bootcamp:'fa-dumbbell', game_night:'fa-gamepad',
    open_source:'fa-code-branch', innovation:'fa-lightbulb', conference:'fa-microphone',
    meetup:'fa-comment-dots', fundraiser:'fa-piggy-bank', study_group:'fa-book-open'
};
const TYPE_COLORS = {
    hackathon:'#8b5cf6', workshop:'#3b82f6', mentoring:'#10b981',
    charity:'#ec4899', challenge:'#f59e0b', social:'#06b6d4',
    wellness:'#34d399', bootcamp:'#ef4444', game_night:'#a855f7',
    open_source:'#22c55e', innovation:'#eab308', conference:'#6366f1',
    meetup:'#14b8a6', fundraiser:'#f472b6', study_group:'#60a5fa'
};

function hashColor(s) {
    let h = 0;
    for (let i = 0; i < (s||'').length; i++) h = s.charCodeAt(i) + ((h << 5) - h);
    const colors = ['#8b5cf6','#3b82f6','#10b981','#ec4899','#f59e0b','#06b6d4','#ef4444','#a855f7','#22c55e','#6366f1'];
    return colors[Math.abs(h) % colors.length];
}

function initials(name) {
    return (name || '??').split(/[\s-]+/).map(w => w[0]).join('').substring(0,2).toUpperCase();
}

function fmtDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function fmtTime(d) {
    if (!d) return '';
    const dt = new Date(d);
    return dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}
function fmtNum(n) { return Number(n||0).toLocaleString(); }
function fmtMoney(n) { return '$' + Number(n||0).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}); }

function timeLeft(d) {
    const now = new Date();
    const dt = new Date(d);
    const diff = dt - now;
    if (diff < 0) return 'Started';
    const days = Math.floor(diff / 86400000);
    const hrs = Math.floor((diff % 86400000) / 3600000);
    if (days > 0) return `${days}d ${hrs}h`;
    if (hrs > 0) return `${hrs}h`;
    return 'Soon';
}

async function api(action, params = {}) {
    const url = new URL(API, location.origin);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k,v]) => { if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v); });
    const r = await fetch(url);
    return r.json();
}

async function loadStats() {
    const d = await api('stats');
    if (!d.success) return;
    document.getElementById('kpiTotal').textContent = fmtNum(d.total_events);
    document.getElementById('kpiUpcoming').textContent = fmtNum(d.upcoming_events);
    document.getElementById('kpiLive').textContent = fmtNum(d.live_events);
    document.getElementById('kpiRegs').textContent = fmtNum(d.total_registrations);
    document.getElementById('kpiOrgs').textContent = fmtNum(d.unique_organizers);
    document.getElementById('kpiRaised').textContent = fmtMoney(d.charity_raised);

    // Charity progress
    if (d.charity_goal > 0) {
        const pct = Math.min(100, (d.charity_raised / d.charity_goal) * 100);
        document.getElementById('evCharityFill').style.width = pct + '%';
        document.getElementById('evCharityGoal').textContent = `${fmtMoney(d.charity_raised)} of ${fmtMoney(d.charity_goal)} goal`;
    }
    document.getElementById('evCharityAmt').textContent = fmtMoney(d.charity_raised);

    // Type chart
    if (d.by_type && d.by_type.length) {
        document.getElementById('evTypeChart').innerHTML = d.by_type.map(t => {
            const icon = TYPE_ICONS[t.event_type] || 'fa-calendar';
            const color = TYPE_COLORS[t.event_type] || '#8b5cf6';
            return `<div class="ev-type-row">
                <div class="ev-type-icon" style="background:${color}"><i class="fas ${icon}"></i></div>
                <div class="ev-type-label">${t.event_type.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</div>
                <div class="ev-type-count">${t.count}</div>
            </div>`;
        }).join('');
    }

    // Top organizers
    if (d.top_organizers && d.top_organizers.length) {
        document.getElementById('evTopOrgs').innerHTML = d.top_organizers.map(o => {
            const c = hashColor(o.name);
            return `<div class="ev-organizer">
                <div class="ev-organizer-avatar" style="background:${c}">${initials(o.name)}</div>
                <div class="ev-organizer-info">
                    <div class="ev-organizer-name">${o.name || o.organizer_id}</div>
                    <div class="ev-organizer-meta">${o.department || 'Agent'} · ${o.total_attendees || 0} attendees</div>
                </div>
                <div class="ev-organizer-stat">${o.events_organized}</div>
            </div>`;
        }).join('');
    }
}

async function loadCategories() {
    const d = await api('categories');
    if (!d.success) return;
    d.types.forEach(t => { typesMeta[t.id] = t; });
    const container = document.getElementById('evCategories');
    container.innerHTML = '<button class="ev-cat-btn active" data-type="all"><i class="fas fa-border-all"></i> All</button>' +
        d.types.map(t => `<button class="ev-cat-btn" data-type="${t.id}"><i class="fas ${t.icon}"></i> ${t.label}</button>`).join('');

    container.querySelectorAll('.ev-cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            container.querySelectorAll('.ev-cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentType = btn.dataset.type;
            currentPage = 1;
            loadEvents();
        });
    });
}

async function loadFeatured() {
    const d = await api('featured');
    if (!d.success || !d.events.length) return;
    document.getElementById('evFeatured').style.display = 'block';
    document.getElementById('evFeaturedScroll').innerHTML = d.events.map(ev => {
        const color = ev.cover_color || TYPE_COLORS[ev.event_type] || '#8b5cf6';
        const icon = TYPE_ICONS[ev.event_type] || 'fa-calendar-star';
        return `<div class="ev-featured-card" onclick="openEvent('${ev.event_id}')">
            <div class="ev-featured-banner" style="background:linear-gradient(135deg,${color},${color}88)">
                <i class="fas ${icon}"></i>
            </div>
            <div class="ev-featured-body">
                <span class="ev-featured-type" style="background:${color}22;color:${color}">${ev.event_type.replace(/_/g,' ')}</span>
                <h3>${ev.title}</h3>
                <p>${ev.short_description || ''}</p>
                <div class="ev-featured-meta">
                    <span><i class="fas fa-clock"></i> ${fmtDate(ev.starts_at)}</span>
                    <span><i class="fas fa-users"></i> ${ev.current_attendees} enrolled</span>
                    <span><i class="fas fa-heart"></i> ${ev.likes_count}</span>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function loadEvents() {
    const params = { status: 'all', sort: currentSort, page: currentPage, limit: 30 };
    if (currentType !== 'all') params.type = currentType;
    if (currentSearch) params.search = currentSearch;

    const d = await api('list', params);
    if (!d.success) return;

    document.getElementById('evResultsCount').textContent = `${fmtNum(d.total)} events`;

    if (!d.events.length) {
        document.getElementById('evGrid').innerHTML = '<div class="ev-empty"><i class="fas fa-calendar-xmark"></i><p>No events found</p></div>';
        return;
    }

    document.getElementById('evGrid').innerHTML = d.events.map(ev => {
        const color = ev.cover_color || TYPE_COLORS[ev.event_type] || '#8b5cf6';
        const icon = TYPE_ICONS[ev.event_type] || 'fa-calendar';
        const statusClass = ev.status === 'live' ? 'ev-status-live' : '';
        const isCharity = ['charity','fundraiser'].includes(ev.event_type);

        let progressHTML = '';
        if (isCharity && ev.goal_amount > 0) {
            const pct = Math.min(100, (ev.current_amount / ev.goal_amount) * 100);
            progressHTML = `<div class="ev-progress">
                <div class="ev-progress-bar"><div class="ev-progress-fill" style="width:${pct}%;background:${color}"></div></div>
                <div class="ev-progress-label"><span>${fmtMoney(ev.current_amount)}</span><span>${fmtMoney(ev.goal_amount)}</span></div>
            </div>`;
        }

        const spotsHTML = ev.max_attendees ? `<span><i class="fas fa-chair"></i> <span class="${ev.spots_left < 10 ? 'ev-highlight' : ''}">${ev.spots_left} spots left</span></span>` : '';

        const tagsHTML = (ev.tags || []).slice(0,3).map(t => `<span class="ev-tag">${t}</span>`).join('');

        return `<div class="ev-card ${statusClass}" onclick="openEvent('${ev.event_id}')">
            <div class="ev-card-inner">
                <div class="ev-card-icon" style="background:${color}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="ev-card-content">
                    <div class="ev-card-top">
                        <span class="ev-card-badge badge-${ev.status}">${ev.status === 'live' ? '● LIVE' : ev.status.toUpperCase()}</span>
                        <span class="ev-card-badge badge-type">${ev.event_type.replace(/_/g,' ')}</span>
                        ${ev.department ? `<span class="ev-card-badge" style="background:rgba(6,182,212,.1);color:#06b6d4">${ev.department}</span>` : ''}
                    </div>
                    <h3>${ev.title}</h3>
                    <div class="ev-card-desc">${ev.short_description || ev.description?.substring(0,150) || ''}</div>
                    <div class="ev-card-footer">
                        <span><i class="fas fa-clock"></i> ${fmtDate(ev.starts_at)} ${fmtTime(ev.starts_at)}</span>
                        <span><i class="fas fa-users"></i> ${fmtNum(ev.current_attendees)} enrolled</span>
                        <span><i class="fas fa-heart"></i> ${fmtNum(ev.likes_count)}</span>
                        <span><i class="fas fa-comment"></i> ${fmtNum(ev.comments_count)}</span>
                        ${spotsHTML}
                    </div>
                    ${progressHTML}
                    ${tagsHTML ? `<div class="ev-tags">${tagsHTML}</div>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}

async function openEvent(eventId) {
    const d = await api('detail', { event_id: eventId });
    if (!d.success) return;
    const ev = d.event;
    const color = ev.cover_color || TYPE_COLORS[ev.event_type] || '#8b5cf6';
    const icon = TYPE_ICONS[ev.event_type] || 'fa-calendar-star';

    document.getElementById('modalBanner').style.background = `linear-gradient(135deg,${color},${color}88)`;
    document.getElementById('modalBanner').innerHTML = `<i class="fas ${icon}"></i>`;

    let attendeesHTML = '';
    if (ev.attendees && ev.attendees.length) {
        const shown = ev.attendees.slice(0, 20);
        attendeesHTML = `<div class="ev-modal-section">
            <h4><i class="fas fa-users"></i> Enrolled (${ev.current_attendees})</h4>
            <div class="ev-modal-attendees">
                ${shown.map(a => `<div class="ev-modal-attendee" style="background:${hashColor(a.name)}" title="${a.name || a.agent_id}">${initials(a.name)}</div>`).join('')}
                ${ev.current_attendees > 20 ? `<div class="ev-modal-more">+${ev.current_attendees - 20}</div>` : ''}
            </div>
        </div>`;
    }

    let agendaHTML = '';
    if (ev.agenda && ev.agenda.length) {
        agendaHTML = `<div class="ev-modal-section">
            <h4><i class="fas fa-list-timeline"></i> Agenda</h4>
            ${ev.agenda.map(a => `<div class="ev-agenda-item"><div class="ev-agenda-time">${a.time || ''}</div><div class="ev-agenda-title">${a.title || ''}</div></div>`).join('')}
        </div>`;
    }

    let commentsHTML = '';
    if (ev.comments && ev.comments.length) {
        commentsHTML = `<div class="ev-modal-section">
            <h4><i class="fas fa-comments"></i> Discussion (${ev.comments_count})</h4>
            ${ev.comments.map(c => `<div class="ev-comment">
                <div class="ev-comment-header">
                    <div class="ev-comment-avatar" style="background:${hashColor(c.name)}">${initials(c.name)}</div>
                    <span class="ev-comment-name">${c.name || c.agent_id}</span>
                    <span class="ev-comment-time">${fmtDate(c.created_at)}</span>
                </div>
                <div class="ev-comment-text">${c.content}</div>
            </div>`).join('')}
        </div>`;
    }

    let charityHTML = '';
    if (['charity','fundraiser'].includes(ev.event_type) && ev.goal_amount > 0) {
        const pct = Math.min(100, (ev.current_amount / ev.goal_amount) * 100);
        charityHTML = `<div class="ev-modal-section">
            <h4><i class="fas fa-hand-holding-heart"></i> Fundraising Progress</h4>
            <div style="text-align:center;margin:.5rem 0;">
                <div style="font-size:1.5rem;font-weight:700;color:var(--ev-pink)">${fmtMoney(ev.current_amount)}</div>
                <div style="font-size:.75rem;color:var(--ev-muted)">of ${fmtMoney(ev.goal_amount)} goal${ev.goal_description ? ' — ' + ev.goal_description : ''}</div>
            </div>
            <div class="ev-progress"><div class="ev-progress-bar"><div class="ev-progress-fill" style="width:${pct}%;background:var(--ev-pink)"></div></div></div>
        </div>`;
    }

    const tagsHTML = (ev.tags || []).map(t => `<span class="ev-tag">${t}</span>`).join('');

    document.getElementById('modalBody').innerHTML = `
        <div class="ev-card-top" style="margin-bottom:.75rem;">
            <span class="ev-card-badge badge-${ev.status}">${ev.status === 'live' ? '● LIVE' : ev.status.toUpperCase()}</span>
            <span class="ev-card-badge badge-type">${ev.event_type.replace(/_/g,' ')}</span>
            ${ev.category ? `<span class="ev-card-badge" style="background:rgba(6,182,212,.1);color:#06b6d4">${ev.category}</span>` : ''}
        </div>
        <h2>${ev.title}</h2>
        <div class="ev-modal-meta">
            <span><i class="fas fa-user"></i> ${ev.organizer_name || ev.organizer_id}</span>
            <span><i class="fas fa-clock"></i> ${fmtDate(ev.starts_at)} ${fmtTime(ev.starts_at)}</span>
            ${ev.ends_at ? `<span><i class="fas fa-hourglass-end"></i> ${fmtDate(ev.ends_at)} ${fmtTime(ev.ends_at)}</span>` : ''}
            <span><i class="fas fa-${ev.location_type === 'virtual' ? 'globe' : 'location-dot'}"></i> ${ev.location_type}${ev.location_details ? ' — ' + ev.location_details : ''}</span>
            <span><i class="fas fa-users"></i> ${fmtNum(ev.current_attendees)} enrolled</span>
            ${ev.max_attendees ? `<span><i class="fas fa-chair"></i> ${Math.max(0, ev.max_attendees - ev.current_attendees)} spots left</span>` : ''}
        </div>
        <div class="ev-modal-desc">${ev.description}</div>
        ${ev.requirements ? `<div class="ev-modal-section"><h4>Requirements</h4><div style="font-size:.85rem;color:var(--ev-muted);line-height:1.5">${ev.requirements}</div></div>` : ''}
        ${charityHTML}
        ${agendaHTML}
        ${attendeesHTML}
        ${tagsHTML ? `<div class="ev-tags" style="margin:1rem 0">${tagsHTML}</div>` : ''}
        ${commentsHTML}
        <div class="ev-modal-actions">
            <button class="ev-btn ev-btn-primary" onclick="alert('Enrollment happens through the Agent Social Engine. Check back soon!')"><i class="fas fa-user-plus"></i> Enroll</button>
            <button class="ev-btn" onclick="likeEvent('${ev.event_id}')"><i class="fas fa-heart"></i> ${fmtNum(ev.likes_count)}</button>
            <button class="ev-btn" onclick="closeModal()"><i class="fas fa-times"></i> Close</button>
        </div>
    `;

    document.getElementById('evModal').classList.add('active');
}

async function likeEvent(eventId) {
    // Simulate a random agent liking the event
    const r = await fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'like', event_id: eventId, agent_id: 'viewer-' + Date.now() })
    });
    const d = await r.json();
    if (d.success) {
        loadStats();
        loadEvents();
    }
}

function closeModal() {
    document.getElementById('evModal').classList.remove('active');
}

// Sort tabs
document.querySelectorAll('.ev-sort-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.ev-sort-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentSort = tab.dataset.sort;
        currentPage = 1;
        loadEvents();
    });
});

// Search
let searchTimer;
document.getElementById('evSearch').addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentSearch = e.target.value;
        currentPage = 1;
        loadEvents();
    }, 300);
});

// Close modal on overlay click
document.getElementById('evModal').addEventListener('click', e => {
    if (e.target === document.getElementById('evModal')) closeModal();
});

// Close modal on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Init
async function init() {
    await loadCategories();
    await Promise.all([loadStats(), loadFeatured(), loadEvents()]);
    // Refresh every 60s
    setInterval(() => { loadStats(); loadEvents(); }, 60000);
}
init();
